<?php
//
// Description
// ===========
// This method will return all the information about an projects.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the projects is attached to.
// project_id:          The ID of the projects to get the details for.
//
// Returns
// -------
//
function ciniki_timetracker_projectGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'project_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Projects'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'timetracker', 'private', 'checkAccess');
    $rc = ciniki_timetracker_checkAccess($ciniki, $args['tnid'], 'ciniki.timetracker.projectGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Projects
    //
    if( $args['project_id'] == 0 ) {
        $project = array('id'=>0,
            'name'=>'',
            'status'=>'10',
            'sequence'=>'1',
        );
    }

    //
    // Get the details for an existing Projects
    //
    else {
        $strsql = "SELECT ciniki_timetracker_projects.id, "
            . "ciniki_timetracker_projects.name, "
            . "ciniki_timetracker_projects.status, "
            . "ciniki_timetracker_projects.sequence "
            . "FROM ciniki_timetracker_projects "
            . "WHERE ciniki_timetracker_projects.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_timetracker_projects.id = '" . ciniki_core_dbQuote($ciniki, $args['project_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.timetracker', array(
            array('container'=>'projects', 'fname'=>'id', 
                'fields'=>array('name', 'status', 'sequence'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.timetracker.7', 'msg'=>'Projects not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['projects'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.timetracker.8', 'msg'=>'Unable to find Projects'));
        }
        $project = $rc['projects'][0];

        //
        // Get the list of users attached to the bug
        //
        $strsql = "SELECT user_id "
            . "FROM ciniki_timetracker_users "
            . "WHERE project_id = '" . ciniki_core_dbQuote($ciniki, $args['project_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
        $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.timetracker', 'users', 'user_id');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.bugs.11', 'msg'=>'Unable to load bug information', 'err'=>$rc['err']));
        }
        $project['user_ids'] = isset($rc['users']) ? implode(',', $rc['users']) : ''; 
    }

    return array('stat'=>'ok', 'project'=>$project);
}
?>
