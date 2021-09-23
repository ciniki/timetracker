<?php
//
// Description
// -----------
// This function returns the list of objects for the module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_timetracker_objects(&$ciniki) {
    //
    // Build the objects
    //
    $objects = array();
    $objects['project'] = array(
        'name' => 'Projects',
        'sync' => 'yes',
        'o_name' => 'project',
        'o_container' => 'projects',
        'table' => 'ciniki_timetracker_projects',
        'fields' => array(
            'name' => array('name'=>'Name'),
            'status' => array('name'=>'Status', 'default'=>'10'),
            'sequence' => array('name'=>'Order', 'default'=>'1'),
            ),
        'history_table' => 'ciniki_timetracker_history',
        );
    $objects['entry'] = array(
        'name' => 'entry',
        'sync' => 'yes',
        'o_name' => 'entry',
        'o_container' => 'entries',
        'table' => 'ciniki_timetracker_entries',
        'fields' => array(
//            'project_id' => array('name'=>'Project', 'ref'=>'ciniki.timetracker.project'),
            'type' => array('name'=>'Type', 'default'=>''),
            'project' => array('name'=>'Project', 'default'=>''),
            'task' => array('name'=>'Task', 'default'=>''),
            'module' => array('name'=>'Module', 'default'=>''),
            'customer' => array('name'=>'Customer', 'default'=>''),
//            'customer_id' => array('name'=>'Customer', 'ref'=>'ciniki.customers.customer', 'default'=>0),
            'user_id' => array('name'=>'User', 'ref'=>'ciniki.users.user'),
            'start_dt' => array('name'=>'Start', 'default'=>''),
            'end_dt' => array('name'=>'End', 'default'=>''),
            'notes' => array('name'=>'Notes', 'default'=>''),
            ),
        'history_table' => 'ciniki_timetracker_history',
        );
    $objects['user'] = array(
        'name' => 'Project User',
        'sync' => 'yes',
        'o_name' => 'user',
        'o_container' => 'users',
        'table' => 'ciniki_timetracker_users',
        'fields' => array(
            'project_id' => array('name'=>'Project', 'ref'=>'ciniki.timetracker.project'),
            'user_id' => array('name'=>'User', 'ref'=>'ciniki.users.user'),
            ),
        'history_table' => 'ciniki_timetracker_history',
        );
    //
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
