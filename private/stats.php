<?php
//
// Description
// -----------
// This function will calculate the status for the reporting period.
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
function ciniki_timetracker_stats($ciniki, $tnid, $args) {
    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
        
    //
    // If no start date specified, set it to the previous monday
    //
    if( !isset($args['start_dt']) || $args['start_dt'] == '' ) {
        $dt = new DateTime('now', new DateTimezone($intl_timezone));
        if( $dt->format('N') != 1 ) {
            $dt->sub(new DateInterval('P' . ($dt->format('N') - 1) . 'D'));
        }
        $dt->setTime(0,0,0);
        $dt->setTimezone(new DateTimezone('UTC'));
        $args['start_dt'] = $dt;
    } elseif( is_string($args['start_dt']) ) {
        $args['start_dt'] = new DateTime($args['start_dt'] . ' 00:00:00', new DateTimezone($intl_timezone));
        $args['start_dt']->setTimezone(new DateTimezone('UTC'));
    }
    // 
    // Make sure weekly reports start on a Monday
    //
    if( $args['report'] == 'weekly' ) {
        if( $args['start_dt']->format('N') != 1 ) {
            $args['start_dt']->sub(new DateInterval('P' . ($args['start_dt']->format('N') - 1) . 'D'));
        }
    }
    // 
    // Make sure monthly reports start on 1st of month
    //
    if( $args['report'] == 'monthly' && $args['start_dt']->format('d') != 1 ) {
        $args['start_dt']->setDate($args['start_dt']->format('Y'), $args['start_dt']->format('m'), 1);
    }
    //
    // Setup end date if none started
    //
    if( !isset($args['end_dt']) || $args['end_dt'] == '' ) {
        if( $args['report'] == 'daily' ) {
            $args['end_dt'] = clone($args['start_dt']);
            $args['end_dt']->add(new DateInterval('P7D'));
            $args['end_dt']->sub(new DateInterval('PT1S'));
        }
        elseif( $args['report'] == 'weekly' ) {
            $args['end_dt'] = clone($args['start_dt']);
            $args['end_dt']->add(new DateInterval('P4W'));
            $args['end_dt']->sub(new DateInterval('PT1S'));
        }
        elseif( $args['report'] == 'monthly' ) {
            $args['end_dt'] = clone($args['start_dt']);
            $args['end_dt']->add(new DateInterval('P12M'));
            $args['end_dt']->sub(new DateInterval('PT1S'));
        }
    } elseif( is_string($args['end_dt']) ) {
        $args['end_dt'] = new DateTime($args['end_dt'] . ' 23:59:59', new DateTimezone($intl_timezone));
        $args['end_dt']->setTimezone(new DateTimezone('UTC'));
    }
    // 
    // Make sure weekly reports ends on a Sunday
    //
    if( $args['report'] == 'weekly' ) {
        if( $args['end_dt']->format('N') != 7 ) {
            $args['end_dt']->add(new DateInterval('P' . (8 - $args['end_dt']->format('N')) . 'D'));
        }
    }
    // 
    // Make sure monthly reports start on 1st of month
    //
    if( $args['report'] == 'monthly' && $args['end_dt']->format('d') != $args['end_dt']->format('t') ) {
        $args['end_dt']->setDate($args['end_dt']->format('Y'), $args['end_dt']->format('m'), $args['end_dt']->format('t'));
    }

    //
    // Setup the date header
    //
    $columns = array(
        array(
            'label' => 'Project',
            'field' => 'name',
            ),
        );
    $dt = clone $args['start_dt'];
    while($dt < $args['end_dt']) {
        if( $args['report'] == 'daily' ) {
            $column = array(
                'label' => $dt->format('M j'),
                'field' => $dt->format('Y-m-d') . '_display',
                'start_dt' => $dt->format('Y-m-d'),
                );
            $dt->add(new DateInterval('P1D'));
            $column['end_dt'] = $dt->format('Y-m-d');
            $columns[] = $column;
        }
        elseif( $args['report'] == 'weekly' ) {
            $column = array(
                'label' => $dt->format('M j'),
                'field' => $dt->format('Y-W') . '_display',
                'start_dt' => $dt->format('Y-m-d'),
                );
            $dt->add(new DateInterval('P7D'));
            $column['end_dt'] = $dt->format('Y-m-d');
            $columns[] = $column;
        }
        elseif( $args['report'] == 'monthly' ) {
            $column = array(
                'label' => $dt->format('M Y'),
                'field' => $dt->format('Y-m') . '_display',
                'start_dt' => $dt->format('Y-m-d'),
                );
            $dt->add(new DateInterval('P1M'));
            $column['end_dt'] = $dt->format('Y-m-d');
            $columns[] = $column;
        }
    }
    $columns[] = array(
        'label' => 'Totals',
        'field' => 'total_display',
        );

    //
    // Get the list of active projects for the totals columns
    //
    $strsql = "SELECT projects.id, "
        . "projects.name "
        . "FROM ciniki_timetracker_projects AS projects "
        . "WHERE projects.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND projects.status = 10 "
        . "ORDER BY projects.sequence, projects.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.timetracker', array(
        array('container'=>'projects', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.timetracker.17', 'msg'=>'Unable to load projects', 'err'=>$rc['err']));
    }
    $projects = isset($rc['projects']) ? $rc['projects'] : array();
    $projects['total'] = array(
        'id' => 0,
        'name' => 'Totals',
        );

    //
    // Get the time tracker entries organized by user then project
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
        . "entries.start_dt, "
        . "entries.end_dt, "
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
        . "AND entries.start_dt >= '" . ciniki_core_dbQuote($ciniki, $args['start_dt']->format('Y-m-d H:i:s')) . "' "
        . "AND entries.end_dt <= '" . ciniki_core_dbQuote($ciniki, $args['end_dt']->format('Y-m-d H:i:s')) . "' "
        . "ORDER BY users.display_name, projects.sequence, projects.name, entries.start_dt "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.timetracker', array(
        array('container'=>'users', 'fname'=>'user_id', 'fields'=>array('id'=>'user_id', 'name'=>'display_name')),
        array('container'=>'projects', 'fname'=>'project_id', 'fields'=>array('id'=>'project_id', 'name'=>'project_name')),
        array('container'=>'entries', 'fname'=>'id', 
            'fields'=>array('id', 'project_id', 'year', 'month', 'day', 'week', 'start_dt', 'end_dt', 'length', 'notes'),
            'utctotz'=>array(
                'year'=>array('timezone'=>$intl_timezone, 'format'=>'Y'),
                'month'=>array('timezone'=>$intl_timezone, 'format'=>'m'),
                'day'=>array('timezone'=>$intl_timezone, 'format'=>'d'),
                'week'=>array('timezone'=>$intl_timezone, 'format'=>'W'),
                'start_dt'=>array('timezone'=>$intl_timezone, 'format'=>'Y-m-d H:i:s'),
                ), 
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $users = isset($rc['users']) ? $rc['users'] : array();

    //
    // Rollup entries
    //
    foreach($users as $uid => $user) {
        $totals = array(
            'id' => 0,
            'name' => 'Totals',
            );
        foreach($user['projects'] as $pid => $project) {
            foreach($project['entries'] as $eid => $entry) {
                if( $args['report'] == 'daily' ) {
                    $field = $entry['year'] . '-' . $entry['month'] . '-' . $entry['day'];
                } elseif( $args['report'] == 'weekly' ) {
                    $field = $entry['year'] . '-' . $entry['week'];
                } elseif( $args['report'] == 'monthly' ) {
                    $field = $entry['year'] . '-' . $entry['month'];
                }
                
                //
                // Add to the user/project table
                //
                if( !isset($users[$uid]['projects'][$pid][$field]) ) {
                    $users[$uid]['projects'][$pid][$field] = 0;
                }
                if( !isset($users[$uid]['projects'][$pid]['total']) ) {
                    $users[$uid]['projects'][$pid]['total'] = 0;
                }
        
//                error_log($uid . ':' . $pid . '--' . $entry['length'] . ' [' . $entry['start_dt'] . ']');
                $users[$uid]['projects'][$pid][$field] += $entry['length'];
                $users[$uid]['projects'][$pid][$field . '_display'] = sprintf("%.01f", $users[$uid]['projects'][$pid][$field]/3600);
                $users[$uid]['projects'][$pid]['total'] += $entry['length'];
                $users[$uid]['projects'][$pid]['total_display'] = sprintf("%.01f", $users[$uid]['projects'][$pid]['total']/3600);

                //
                // Add the to the users daily totals
                //
                if( !isset($totals[$field]) ) {
                    $totals[$field] = 0;
                }
                if( !isset($totals['total']) ) {
                    $totals['total'] = 0;
                }
                $totals[$field] += $entry['length'];
                $totals[$field . '_display'] = sprintf("%.01f", $totals[$field]/3600);
                $totals['total'] += $entry['length'];
                $totals['total_display'] = sprintf("%.01f", $totals['total']/3600);

                //
                // Add to the projects total table that will be added at the end
                //
                if( !isset($projects[$project['id']][$field]) ) {
                    $projects[$project['id']][$field] = 0;
                }
                if( !isset($projects[$project['id']]['total']) ) {
                    $projects[$project['id']]['total'] = 0;
                }
                $projects[$project['id']][$field] += $entry['length'];
                $projects[$project['id']][$field . '_display'] = sprintf("%.01f", $projects[$project['id']][$field]/3600);
                $projects[$project['id']]['total'] += $entry['length'];
                $projects[$project['id']]['total_display'] = sprintf("%.01f", $projects[$project['id']]['total']/3600);

                //
                // Add to the projects total final line table that will be added at the end
                //
                if( !isset($projects['total'][$field]) ) {
                    $projects['total'][$field] = 0;
                }
                if( !isset($projects['total']['total']) ) {
                    $projects['total']['total'] = 0;
                }
                $projects['total'][$field] += $entry['length'];
                $projects['total'][$field . '_display'] = sprintf("%.01f", $projects['total'][$field]/3600);
                $projects['total']['total'] += $entry['length'];
                $projects['total']['total_display'] = sprintf("%.01f", $projects['total']['total']/3600);
            }
            unset($users[$uid]['projects'][$pid]['entries']);
        }
        $users[$uid]['projects']['total'] = $totals;
    }

    $totals = array_pop($projects);
    $projects = array_values($projects);
    $projects['total'] = $totals;
    $users['totals'] = array(
        'id' => '0',
        'name' => 'Totals',
        'projects' => $projects,
        );
   
    $args['start_dt']->setTimezone(new DateTimezone($intl_timezone));
    $args['end_dt']->setTimezone(new DateTimezone($intl_timezone));

    return array('stat'=>'ok', 'start_dt'=>$args['start_dt']->format('Y-m-d'), 'end_dt'=>$args['end_dt']->format('Y-m-d'), 'columns'=>$columns, 'users'=>$users);
}
?>
