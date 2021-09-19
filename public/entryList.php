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
        'module'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Module'),
        'customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer'),
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
        . "IFNULL(projects.name, '') AS project_name, "
        . "entries.module, "
        . "entries.customer_id, "
        . "IFNULL(customers.display_name, '') AS display_name, "
        . "entries.start_dt, "
        . "entries.end_dt, "
        . "entries.start_dt AS start_display, "
        . "entries.end_dt AS end_display, "
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
        . "";
    if( isset($args['project_id']) && $args['project_id'] != '*' ) {
        $strsql .= "AND entries.project_id = '" . ciniki_core_dbQuote($ciniki, $args['project_id']) . "' ";
    }
    if( isset($args['module']) && $args['module'] != '*' ) {
        $strsql .= "AND entries.module = '" . ciniki_core_dbQuote($ciniki, $args['module']) . "' ";
    }
    if( isset($args['customer_id']) && $args['customer_id'] != '*' ) {
        $strsql .= "AND entries.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
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
            'fields'=>array('id', 'project_id', 'project_name', 'module', 'customer_id', 'display_name', 'start_dt', 'end_dt', 
                'start_display', 'end_display', 'notes'),
            'utctotz'=>array(
                'start_display'=>array('timezone'=>$intl_timezone, 'format'=>'D j, Y - g:i A'),
                'end_display'=>array('timezone'=>$intl_timezone, 'format'=>'D j, Y - g:i A'),
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
