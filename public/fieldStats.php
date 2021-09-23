<?php
//
// Description
// -----------
// This method will return the projects and stats for the manager.
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
function ciniki_timetracker_fieldStats($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'report'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Report'),
        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Field'),
        'start_dt'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Start Date'),
        'end_dt'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'End Date'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'timetracker', 'private', 'checkAccess');
    $rc = ciniki_timetracker_checkAccess($ciniki, $args['tnid'], 'ciniki.timetracker.fieldStats');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'timetracker', 'private', 'stats');
    $rc = ciniki_timetracker_stats($ciniki, $args['tnid'], $args);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.timetracker.25', 'msg'=>'', 'err'=>$rc['err']));
    }

    return $rc;
}
?>
