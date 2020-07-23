<?php
//
// Description
// -----------
// Return the report of upcoming customer reminders
//
// Arguments
// ---------
// ciniki:
// tnid:         
// args:        
//
// Additional Arguments
// --------------------
// days:       
// 
// Returns
// -------
//
function ciniki_timetracker_reporting_blockDaily(&$ciniki, $tnid, $args) {
    //
    // Get the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'mysql');

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'timetracker', 'private', 'maps');
    $rc = ciniki_timetracker_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    $days = 7;

    $start_dt = new DateTime('now', new DateTimezone($intl_timezone));
    if( $start_dt->format('N') != 1 ) {
        $start_dt->sub(new DateInterval('P' . ($start_dt->format('N') - 1) . 'D'));
    }
    $start_dt->setTime(0,0,0);
    $start_dt->sub(new DateInterval('P7D'));

    $end_dt = clone $start_dt;
    $end_dt->add(new DateInterval('P' . $days . 'D'));
    $end_dt->setTime(0, 0, 0);

    //
    // Store the report block chunks
    //
    $chunks = array();

    ciniki_core_loadMethod($ciniki, 'ciniki', 'timetracker', 'private', 'stats');
    $rc = ciniki_timetracker_stats($ciniki, $tnid, array(
        'report' => 'daily',
        'start_dt' => $start_dt->format('Y-m-d'),
        ));
    $stats = $rc;

    $stats['columns'][0]['pdfwidth'] = '36%';
    $stats['columns'][1]['pdfwidth'] = '8%';
    $stats['columns'][2]['pdfwidth'] = '8%';
    $stats['columns'][3]['pdfwidth'] = '8%';
    $stats['columns'][4]['pdfwidth'] = '8%';
    $stats['columns'][5]['pdfwidth'] = '8%';
    $stats['columns'][6]['pdfwidth'] = '8%';
    $stats['columns'][7]['pdfwidth'] = '8%';
    $stats['columns'][8]['pdfwidth'] = '8%';

    $stats['columns'][0]['txtwidth'] = '30';
    $stats['columns'][1]['txtwidth'] = '8';
    $stats['columns'][2]['txtwidth'] = '8';
    $stats['columns'][3]['txtwidth'] = '8';
    $stats['columns'][4]['txtwidth'] = '8';
    $stats['columns'][5]['txtwidth'] = '8';
    $stats['columns'][6]['txtwidth'] = '8';
    $stats['columns'][7]['txtwidth'] = '8';
    $stats['columns'][8]['txtwidth'] = '8';
    
    // 
    // Setup a chunk for each user
    //
    foreach($stats['users'] as $user) {
        //
        // Create the report blocks
        //
        $chunk = array(
            'title'=>$user['name'],
            'type'=>'table',
            'columns' => $stats['columns'],
            'data'=>$user['projects'],
            'textlist'=>''
            );
        $chunk['textlist'] .= "\n";
        foreach($stats['columns'] as $column) {
            $chunk['textlist'] .= sprintf("%{$column[txtwidth]}s", $column['label']);
        }
        foreach($user['projects'] as $project) {
            $chunk['textlist'] .= "\n";
            foreach($stats['columns'] as $column) {
                $chunk['textlist'] .= sprintf("%{$column[txtwidth]}s", (isset($project[$column['field']]) ? $project[$column['field']]: ''));
            }
        }
        $chunk['textlist'] .= "\n\n";
        $chunks[] = $chunk;
    }

    return array('stat'=>'ok', 'chunks'=>$chunks);
}
?>
