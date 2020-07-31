<?php
//
// Description
// -----------
// Return the report of the list of time tracker entries for today.
//
// Arguments
// ---------
// ciniki:
// tnid:         
// args:        
//
// Additional Arguments
// --------------------
// days:       
// 
// Returns
// -------
//
function ciniki_timetracker_reporting_blockTodaysEntries(&$ciniki, $tnid, $args) {
    //
    // Get the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'mysql');

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'timetracker', 'private', 'maps');
    $rc = ciniki_timetracker_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    if( isset($args['days']) && $args['days'] != '' && $args['days'] > 0 ) {
        $days = $args['days'];
    } else {
        $days = 1;
    }

    $start_dt = new DateTime('now', new DateTimezone($intl_timezone));
    $start_dt->setTime(0,0,0);

    $end_dt = clone $start_dt;
    $end_dt->add(new DateInterval('P' . $days . 'D'));
    $end_dt->setTime(0, 0, 0);

    //
    // Store the report block chunks
    //
    $chunks = array();

    //
    // Load the entries
    //
    $strsql = "SELECT entries.id, "
        . "entries.user_id, "
        . "users.display_name, "
        . "entries.project_id, "
        . "projects.name AS project_name, "
        . "entries.start_dt AS year, "
        . "entries.start_dt AS month, "
        . "entries.start_dt AS day, "
        . "entries.start_dt AS week, "
        . "entries.start_dt AS start_time, "
        . "entries.end_dt AS end_time, "
        . "IF( entries.end_dt <> '0000-00-00 00:00:00', "
            . "(UNIX_TIMESTAMP(entries.end_dt)-UNIX_TIMESTAMP(entries.start_dt)), "
            . "(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(entries.start_dt)) "
            . ") AS length, "
        . "entries.notes "
        . "FROM ciniki_timetracker_entries AS entries "
        . "LEFT JOIN ciniki_users AS users ON ("
            . "entries.user_id = users.id "
            . ") "
        . "LEFT JOIN ciniki_timetracker_projects AS projects ON ("
            . "entries.project_id = projects.id "
            . "AND projects.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE entries.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND entries.start_dt >= '" . ciniki_core_dbQuote($ciniki, $start_dt->format('Y-m-d H:i:s')) . "' "
        . "AND entries.end_dt <= '" . ciniki_core_dbQuote($ciniki, $end_dt->format('Y-m-d H:i:s')) . "' "
        . "ORDER BY users.display_name, entries.start_dt "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.timetracker', array(
        array('container'=>'entries', 'fname'=>'id', 
            'fields'=>array('id', 'user_id', 'display_name', 'project_name', 'year', 'month', 'day', 'week', 'start_time', 'end_time', 'length', 'notes'),
            'utctotz'=>array(
                'year'=>array('timezone'=>$intl_timezone, 'format'=>'Y'),
                'month'=>array('timezone'=>$intl_timezone, 'format'=>'m'),
                'day'=>array('timezone'=>$intl_timezone, 'format'=>'d'),
                'week'=>array('timezone'=>$intl_timezone, 'format'=>'W'),
                'start_time'=>array('timezone'=>$intl_timezone, 'format'=>'g:i a'),
                'end_time'=>array('timezone'=>$intl_timezone, 'format'=>'g:i a'),
                ), 
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $entries = isset($rc['entries']) ? $rc['entries'] : array();
    $textlist = sprintf("%30s %40s %8s %8s %8s\n", 'Employee', 'Project', 'Start', 'End', 'Duration');
    foreach($entries as $eid => $entry) {
        $minutes = (int)($entry['length']/60);
        $hours = (int)($minutes/60);
        $hour_minutes = (int)($minutes%60);
        $entries[$eid]['length_display'] = $hours . ':' . sprintf("%02d", $hour_minutes);
        $textlist .= sprintf("%30s %40s %8s %8s %8s\n", $entry['display_name'], $entry['project_name'], $entry['start_time'], $entry['end_time'], $entries[$eid]['length_display']);
    }

    if( count($entries) > 0 ) {
        $chunk = array(
            'type'=>'table',
            'columns' => array(
                array('label'=>'Employee', 'pdfwidth'=>'30%', 'field'=>'display_name'),
                array('label'=>'Project', 'pdfwidth'=>'40%', 'field'=>'project_name'),
                array('label'=>'Start', 'pdfwidth'=>'10%', 'field'=>'start_time'),
                array('label'=>'End', 'pdfwidth'=>'10%', 'field'=>'end_time'),
                array('label'=>'Duration', 'pdfwidth'=>'10%', 'field'=>'length_display'),
                ),
            'data'=>$entries,
            'textlist'=>$textlist,
            );
        $chunks[] = $chunk;
    } else {
        $chunks[] = array('type'=>'message', 'content'=>'No time logged today.');
    }

    return array('stat'=>'ok', 'chunks'=>$chunks);
}
?>
