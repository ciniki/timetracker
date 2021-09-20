<?php
//
// Description
// -----------
// This method will return the list of Projectss for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Projects for.
//
// Returns
// -------
//
function ciniki_timetracker_tracker($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'action'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Action'),
        'project_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Project'),
        'module'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Module'),
        'customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer'),
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'),
        'entry_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Entry'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'timetracker', 'private', 'checkAccess');
    $rc = ciniki_timetracker_checkAccess($ciniki, $args['tnid'], 'ciniki.timetracker.tracker');
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
    // Check for start
    //
    if( isset($args['action']) && $args['action'] == 'start' ) {
        $dt = new DateTime('now', new DateTimezone('UTC'));
        $entry = array(
            'project_id' => $args['project_id'],
            'module' => isset($args['module']) ? $args['module'] : '',
            'customer_id' => isset($args['customer_id']) ? $args['customer_id'] : 0,
            'user_id' => $ciniki['session']['user']['id'],
            'start_dt' => $dt->format('Y-m-d H:i:s'),
            'end_dt' => '',
            'notes' => isset($args['notes']) ? $args['notes'] : '',
            );
        //
        // Add entry
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.timetracker.entry', $entry, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.timetracker.14', 'msg'=>'Unable to add the entry', 'err'=>$rc['err']));
        }
    }

    //
    // Stop entry
    //
    if( isset($args['action']) && $args['action'] == 'stop' ) {
        $dt = new DateTime('now', new DateTimezone('UTC'));
        //
        // Update entry
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.timetracker.entry', $args['entry_id'], array(
            'end_dt' => $dt->format('Y-m-d H:i:s'),
            ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.timetracker.15', 'msg'=>'Unable to update the entry', 'err'=>$rc['err']));
        }
    }

    //
    // Get the list of recent entries in the last 30 days
    //
    $start_dt = new DateTime('now', new DateTimezone($intl_timezone));
    $start_dt->sub(new DateInterval('P31D'));
    $strsql = "SELECT entries.id, "
        . "entries.project_id, "
        . "projects.name AS project_name, "
        . "entries.module, "
        . "entries.start_dt AS start_day, "
        . "entries.start_dt AS start_dt_display, "
        . "entries.end_dt AS end_dt_display, "
        . "entries.customer_id, "
        . "IFNULL(customers.display_name, '') AS display_name, "
        . "IF( entries.end_dt <> '0000-00-00 00:00:00', "
            . "(UNIX_TIMESTAMP(entries.end_dt)-UNIX_TIMESTAMP(entries.start_dt)), "
            . "(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(entries.start_dt)) "
            . ") AS length, "
        . "entries.notes "
        . "FROM ciniki_timetracker_entries AS entries "
        . "LEFT JOIN ciniki_timetracker_projects AS projects ON ("
            . "entries.project_id = projects.id "
            . "AND projects.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "entries.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND entries.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
        . "AND start_dt > '" . ciniki_core_dbQuote($ciniki, $start_dt->format('Y-m-d')) . "' "
        . "ORDER BY start_dt DESC "
        . "LIMIT 50 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.timetracker', array(
        array('container'=>'entries', 'fname'=>'id', 
            'fields'=>array('id', 'project_id', 'project_name', 'module', 'customer_id', 'display_name', 'start_day', 'start_dt_display', 'end_dt_display', 'length', 'notes'),
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
                if( !isset($project_times[$entry['project_id']]) ) {
                    $project_times[$entry['project_id']] = 0;
                }
                $project_times[$entry['project_id']] += $entry['length'];
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

    //
    // Get the list of projects
    //
    $strsql = "SELECT projects.id, "
        . "projects.name, "
        . "projects.status, "
        . "IFNULL(entries.id, 0) AS entry_id, "
        . "IFNULL(entries.notes, '') AS notes "
        . "FROM ciniki_timetracker_projects AS projects "
        . "INNER JOIN ciniki_timetracker_users AS users ON ("
            . "projects.id = users.project_id "
            . "AND users.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
            . "AND users.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_timetracker_entries AS entries ON ("
            . "projects.id = entries.project_id "
            . "AND entries.end_dt = '0000-00-00 00:00:00' "
            . "AND entries.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
            . "AND entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE projects.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND projects.status = 10 "
        . "ORDER BY projects.sequence "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.timetracker', array(
        array('container'=>'projects', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'status', 'entry_id', 'notes')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $today_length = 0;
    if( isset($rc['projects']) ) {
        $projects = $rc['projects'];
        $project_ids = array();
        foreach($projects as $iid => $project) {
            if( isset($project_times[$project['id']]) ) {
                $length = $project_times[$project['id']];
                $projects[$iid]['today_length'] = $length;
                $today_length += $length;
                $minutes = (int)($length/60);
                $hours = (int)($minutes/60);
                $hour_minutes = ($minutes%60);
                $projects[$iid]['today_length_display'] = $hours . ':' . sprintf("%02d", $hour_minutes);
    
            }
            $project_ids[] = $project['id'];
        }
    } else {
        $projects = array();
        $project_ids = array();
    }

    $minutes = (int)($today_length/60);
    $hours = (int)($minutes/60);
    $hour_minutes = ($minutes%60);
    $today_length_display = $hours . ':' . sprintf("%02d", $hour_minutes);

    return array('stat'=>'ok', 'projects'=>$projects, 'nplist'=>$project_ids, 'entries'=>$entries, 'today_length_display'=>$today_length_display);
}
?>
