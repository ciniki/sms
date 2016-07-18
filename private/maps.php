<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sms_maps($ciniki) {

    $maps = array();
    $maps['message'] = array(
        'status'=>array(
            '10'=>'Queued',
            '15'=>'Failed, trying again',
            '20'=>'Sending',
            '30'=>'Sent',
            '50'=>'Failed',
            ),
        );
    $maps['log'] = array(
        'severity'=>array(
            '10'=>'Information',
            '20'=>'Confirmation',
            '30'=>'Warning',
            '40'=>'Error',
            '50'=>'Error',
            ),
        );
    
    return array('stat'=>'ok', 'maps'=>$maps);
}
?>
