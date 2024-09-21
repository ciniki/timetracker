<?php
//
// Description
// -----------
// This method searchs for a Projectss for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Projects for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function ciniki_timetracker_projectSearch($ciniki) {
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
    $rc = ciniki_timetracker_checkAccess($ciniki, $args['tnid'], 'ciniki.timetracker.projectSearch');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of projects
    //
    $strsql = "SELECT ciniki_timetracker_projects.id, "
        . "ciniki_timetracker_projects.name, "
        . "ciniki_timetracker_projects.status "
        . "FROM ciniki_timetracker_projects "
        . "WHERE ciniki_timetracker_projects.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ("
            . "name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 50 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.timetracker', array(
        array('container'=>'projects', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'status')),
        ));
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
