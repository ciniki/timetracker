<?php
//
// Description
// -----------
// This function will return the report details for a requested report block.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant.
// args:            The possible arguments for.
//
//
// Returns
// -------
//
function ciniki_timetracker_reporting_block(&$ciniki, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.timetracker']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.timetracker.19', 'msg'=>"That report is not available."));
    }

    //
    // Check to make sure the report is specified
    //
    if( !isset($args['block_ref']) || !isset($args['options']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.timetracker.20', 'msg'=>"No block specified."));
    }

    //
    // The array to store the report data
    //

    //
    // Return the list of reports for the tenant
    //
    if( $args['block_ref'] == 'ciniki.timetracker.daily' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'timetracker', 'reporting', 'blockDaily');
        return ciniki_timetracker_reporting_blockDaily($ciniki, $tnid, $args['options']);
    } elseif( $args['block_ref'] == 'ciniki.timetracker.weekly' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'timetracker', 'reporting', 'blockWeekly');
        return ciniki_timetracker_reporting_blockWeekly($ciniki, $tnid, $args['options']);
    }

    return array('stat'=>'ok');
}
?>
