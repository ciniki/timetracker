<?php
//
// Description
// -----------
// This function will return the list of available blocks to the ciniki.reporting module.
//
// Arguments
// ---------
// ciniki:
// tnid:     
// args:            The possible arguments for.
//
//
// Returns
// -------
//
function ciniki_timetracker_reporting_blocks(&$ciniki, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.timetracker']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.timetracker.18', 'msg'=>"I'm sorry, the block you requested does not exist."));
    }

    $blocks = array();

    //
    // Return the list of blocks for the tenant
    //
    $blocks['ciniki.timetracker.daily'] = array(
        'name'=>'Last week',
        'module' => 'Time Tracker',
        'options'=>array(
            //'days'=>array('label'=>'Number of Days Previous', 'type'=>'text', 'size'=>'small', 'default'=>'7'),
            ),
        );
    $blocks['ciniki.timetracker.weekly'] = array(
        'name'=>'Last 4 weeks',
        'module' => 'Time Tracker',
        'options'=>array(
            // 'weeks'=>array('label'=>'Number of Weeks Previous', 'type'=>'text', 'size'=>'small', 'default'=>'4'),
            ),
        );
    $blocks['ciniki.timetracker.todaysentries'] = array(
        'name'=>'Today Time Logs',
        'module' => 'Time Tracker',
        'options'=>array(
            // 'weeks'=>array('label'=>'Number of Weeks Previous', 'type'=>'text', 'size'=>'small', 'default'=>'4'),
            ),
        );

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
