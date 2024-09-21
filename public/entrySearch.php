<?php
//
// Description
// -----------
// This method searchs for a entrys for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get entry for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function ciniki_timetracker_entrySearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'timetracker', 'private', 'checkAccess');
    $rc = ciniki_timetracker_checkAccess($ciniki, $args['tnid'], 'ciniki.timetracker.entrySearch');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
        
    //
    // Load the date format strings for the user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    $time_format = ciniki_users_timeFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Search for entries
    //
    $strsql = "SELECT entries.id, "
        . "entries.type, "
        . "entries.project, "
        . "entries.task, "
        . "entries.project_id, "
        . "entries.module, "
        . "entries.start_dt AS start_day, "
        . "entries.start_dt AS start_dt_display, "
        . "entries.end_dt AS end_dt_display, "
        . "entries.customer, "
        . "IF( entries.end_dt <> '0000-00-00 00:00:00', "
            . "(UNIX_TIMESTAMP(entries.end_dt)-UNIX_TIMESTAMP(entries.start_dt)), "
            . "(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(entries.start_dt)) "
            . ") AS length, "
        . "entries.notes "
        . "FROM ciniki_timetracker_entries AS entries "
        . "WHERE entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND entries.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
        . "AND ("
            . "type LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR type LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR project LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR project LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR task LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR task LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR customer LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR customer LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR notes LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR notes LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") "
        . "ORDER BY start_dt DESC "
        . "LIMIT 75 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.timetracker', array(
        array('container'=>'entries', 'fname'=>'id', 
            'fields'=>array('id', 'type', 'project', 'task', 'module', 'customer', 'start_day', 'start_dt_display', 'end_dt_display', 'length', 'notes'),
            'utctotz'=>array(
                'start_day'=>array('timezone'=>$intl_timezone, 'format'=>'M d'),
                'start_dt_display'=>array('timezone'=>$intl_timezone, 'format'=>'M j - ' . $time_format),
                'end_dt_display'=>array('timezone'=>$intl_timezone, 'format'=>'M j - ' . $time_format),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $project_times = array();
    $dt = new DateTime('now', new DateTimezone($intl_timezone));
    // Set 4 am as reset time
    if( $dt->format('H') < 4 ) {
        $dt->sub(new DateInterval('P1D'));
    }
    $start_day = $dt->format('M d');
    if( isset($rc['entries']) ) {
        $entries = $rc['entries'];
        $entry_ids = array();
        foreach($entries as $iid => $entry) {
            if( $entry['start_day'] == $start_day ) {
                if( !isset($type_times[$entry['type']]) ) {
                    $type_times[$entry['type']] = 0;
                }
                $type_times[$entry['type']] += $entry['length'];
            }
            if( $entry['length'] > 0 ) {
                $minutes = (int)($entry['length']/60);
                $hours = (int)($minutes/60);
                $hour_minutes = ($minutes%60);
                $entries[$iid]['length_display'] = $hours . ':' . sprintf("%02d", $hour_minutes);
            }
            $entry_ids[] = $entry['id'];
        }
    } else {
        $entries = array();
        $entry_ids = array();
    }

    return array('stat'=>'ok', 'entries'=>$entries, 'nplist'=>$entry_ids);
}
?>
