<?php
//
// Description
// -----------
// This method will return the list of entrys for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get entry for.
//
// Returns
// -------
//
function ciniki_timetracker_entryList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'project_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Project'),
        'type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Type'),
        'project'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Project'),
        'task'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Task'),
        'module'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Module'),
        'customer'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer'),
        'start_dt'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Start'),
        'end_dt'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'End'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'timetracker', 'private', 'checkAccess');
    $rc = ciniki_timetracker_checkAccess($ciniki, $args['tnid'], 'ciniki.timetracker.entryList');
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
    // Get the list of entries
    //
    $strsql = "SELECT entries.id, "
        . "entries.project_id, "
        . "entries.type, "
        . "entries.project, "
        . "entries.module, "
        . "entries.task, "
        . "entries.customer, "
        . "entries.start_dt, "
        . "entries.end_dt, "
        . "entries.start_dt AS start_display, "
        . "entries.end_dt AS end_display, "
        . "entries.notes "
        . "FROM ciniki_timetracker_entries AS entries "
        . "WHERE entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    if( isset($args['type']) && $args['type'] != '*' ) {
        $strsql .= "AND entries.type = '" . ciniki_core_dbQuote($ciniki, $args['type']) . "' ";
    }
    if( isset($args['project']) && $args['project'] != '*' ) {
        $strsql .= "AND entries.project = '" . ciniki_core_dbQuote($ciniki, $args['project']) . "' ";
    }
    if( isset($args['task']) && $args['task'] != '*' ) {
        $strsql .= "AND entries.task = '" . ciniki_core_dbQuote($ciniki, $args['task']) . "' ";
    }
    if( isset($args['module']) && $args['module'] != '*' ) {
        $strsql .= "AND entries.module = '" . ciniki_core_dbQuote($ciniki, $args['module']) . "' ";
    }
    if( isset($args['customer']) && $args['customer'] != '*' ) {
        $strsql .= "AND entries.customer = '" . ciniki_core_dbQuote($ciniki, $args['customer']) . "' ";
    }
    if( isset($args['start_dt']) && $args['start_dt'] != '' ) {
        $strsql .= "AND entries.start_dt >= '" . ciniki_core_dbQuote($ciniki, $args['start_dt']) . "' ";
    }
    if( isset($args['end_dt']) && $args['end_dt'] != '' ) {
        $strsql .= "AND entries.start_dt < '" . ciniki_core_dbQuote($ciniki, $args['end_dt']) . "' ";
    }
    $strsql .= "ORDER BY entries.start_dt "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.timetracker', array(
        array('container'=>'entries', 'fname'=>'id', 
            'fields'=>array('id', 'type', 'project', 'task', 'module', 'customer', 'start_dt', 'end_dt', 
                'start_display', 'end_display', 'notes'),
            'utctotz'=>array(
                'start_display'=>array('timezone'=>$intl_timezone, 'format'=>'D M j, Y - g:i A'),
                'end_display'=>array('timezone'=>$intl_timezone, 'format'=>'D M j, Y - g:i A'),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['entries']) ) {
        $entries = $rc['entries'];
        $entry_ids = array();
        foreach($entries as $iid => $entry) {
            $entry_ids[] = $entry['id'];
        }
    } else {
        $entries = array();
        $entry_ids = array();
    }

    return array('stat'=>'ok', 'entries'=>$entries, 'nplist'=>$entry_ids);
}
?>
