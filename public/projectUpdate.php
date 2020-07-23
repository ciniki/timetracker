<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_timetracker_projectUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'project_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Projects'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Order'),
        'user_ids'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Users'),
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
    $rc = ciniki_timetracker_checkAccess($ciniki, $args['tnid'], 'ciniki.timetracker.projectUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.timetracker');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the Projects in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.timetracker.project', $args['project_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.timetracker');
        return $rc;
    }

    //
    // Check if the assigned users has changed
    //
    if( isset($args['user_ids']) && is_array($args['user_ids']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
        //
        // Get the list of currently assigned users
        //
        $strsql = "SELECT id, uuid, user_id "
            . "FROM ciniki_timetracker_users "
            . "WHERE project_id = '" . ciniki_core_dbQuote($ciniki, $args['project_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.timetracker', 'user');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.timetracker.22', 'msg'=>'Unable to load users', 'err'=>$rc['err']));
        }
        $cur_users = isset($rc['rows']) ? $rc['rows'] : array();

        // 
        // Remove users no longer assigned
        //
        $cur_user_ids = array();
        foreach($cur_users as $user) {
            if( !in_array($user['user_id'], $args['user_ids']) ) {
                $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.timetracker.user', $user['id'], $user['uuid'], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.timetracker.24', 'msg'=>'Unable to remove user', 'err'=>$rc['err']));
                }
            } else {
                $cur_user_ids[] = $user['user_id'];
            }
        }
        foreach($args['user_ids'] as $user_id) {
            if( !in_array($user_id, $cur_user_ids) ) {
                $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.timetracker.user', array(
                    'project_id' => $args['project_id'],
                    'user_id' => $user_id,
                    ), 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.timetracker.23', 'msg'=>'Unable to add user', 'err'=>$rc['err']));
                }
                
            }
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.timetracker');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'timetracker');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.timetracker.project', 'object_id'=>$args['project_id']));

    return array('stat'=>'ok');
}
?>
