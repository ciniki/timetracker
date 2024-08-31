<?php
//
// Description
// ===========
// This method will return all the information about an entry.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the entry is attached to.
// entry_id:          The ID of the entry to get the details for.
//
// Returns
// -------
//
function ciniki_timetracker_entryGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'entry_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'entry'),
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
    $rc = ciniki_timetracker_checkAccess($ciniki, $args['tnid'], 'ciniki.timetracker.entryGet');
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Return default for new entry
    //
    if( $args['entry_id'] == 0 ) {
        $entry = array('id'=>0,
            'type'=>'',
            'project'=>'',
            'task'=>'',
            'module'=>'',
            'customer'=>0,
            'start_dt'=>'',
            'end_dt'=>'',
            'notes'=>'',
        );
    }

    //
    // Get the details for an existing entry
    //
    else {
        $strsql = "SELECT ciniki_timetracker_entries.id, "
            . "ciniki_timetracker_entries.type, "
            . "ciniki_timetracker_entries.project, "
            . "ciniki_timetracker_entries.task, "
            . "ciniki_timetracker_entries.module, "
            . "ciniki_timetracker_entries.customer, "
            . "ciniki_timetracker_entries.start_dt, "
            . "ciniki_timetracker_entries.end_dt, "
            . "ciniki_timetracker_entries.notes "
            . "FROM ciniki_timetracker_entries "
            . "WHERE ciniki_timetracker_entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_timetracker_entries.id = '" . ciniki_core_dbQuote($ciniki, $args['entry_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.timetracker', array(
            array('container'=>'entries', 'fname'=>'id', 
                'fields'=>array('type', 'project', 'task', 'module', 'customer', 'start_dt', 'end_dt', 'notes'),
                'utctotz'=>array(
                    'start_dt'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                    'end_dt'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.timetracker.12', 'msg'=>'entry not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['entries'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.timetracker.13', 'msg'=>'Unable to find entry'));
        }
        $entry = $rc['entries'][0];
    }
    $rsp = array('stat'=>'ok', 'entry'=>$entry);
   
    //
    // Load customer details
    //
/*    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.timetracker', 0x02) && $entry['customer_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');
        $rc = ciniki_customers_hooks_customerDetails2($ciniki, $args['tnid'], 
            array('customer_id'=>$entry['customer_id'], 'phone'=>'yes', 'emails'=>'yes', 'address'=>'yes')
        );
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['entry']['customer'] = $rc['customer'];
        $rsp['entry']['customer_details'] = $rc['details'];
    }     */

    //
    // Get the list of projects
    //
/*    $strsql = "SELECT projects.id, "
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
        . "ORDER BY projects.status, projects.sequence, projects.name, projects.id, users.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.timetracker', array(
        array('container'=>'projects', 'fname'=>'id', 
            'fields'=>array('id', 'sequence', 'name', 'status', 'userlist'),
            'dlists'=>array('userlist'=>', '),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $rsp['projects'] = isset($rc['projects']) ? $rc['projects'] : array();
*/
    return $rsp;
}
?>
