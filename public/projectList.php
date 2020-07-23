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
function ciniki_timetracker_projectList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'timetracker', 'private', 'checkAccess');
    $rc = ciniki_timetracker_checkAccess($ciniki, $args['tnid'], 'ciniki.timetracker.projectList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of projects
    //
    $strsql = "SELECT projects.id, "
        . "projects.sequence, "
        . "projects.name, "
        . "projects.status, "
        . "users.display_name AS userlist "
        . "FROM ciniki_timetracker_projects AS projects "
        . "LEFT JOIN ciniki_timetracker_users AS projectusers ON ("
            . "projects.id = projectusers.project_id "
            . "AND projectusers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_users AS users ON ("
            . "projectusers.user_id = users.id "
            . ") "
        . "WHERE projects.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY projects.status, projects.sequence, users.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.timetracker', array(
        array('container'=>'projects', 'fname'=>'id', 
            'fields'=>array('id', 'sequence', 'name', 'status', 'userlist'),
            'dlists'=>array('userlist'=>', '),
            ),
        ));

        error_log(print_r($rc,true));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['projects']) ) {
        $projects = $rc['projects'];
        $project_ids = array();
        foreach($projects as $iid => $project) {
            $project_ids[] = $project['id'];
        }
    } else {
        $projects = array();
        $project_ids = array();
    }

    return array('stat'=>'ok', 'projects'=>$projects, 'nplist'=>$project_ids);
}
?>
