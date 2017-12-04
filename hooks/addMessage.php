<?php
//
// Description
// -----------
// Add a message to the sms queue.
//
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sms_hooks_addMessage(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');

    //
    // Check arguments
    //
    if( !isset($args['customer_id']) || $args['customer_id'] == '' ) {
        $args['customer_id'] = 0;
    }
    if( !isset($args['cell_number']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sms.2', 'msg'=>'No cell number specified'));
    }
    if( !isset($args['flags']) ) {
        $args['flags'] = '0';
    }
    if( !isset($args['status']) ) {
        $args['status'] = '10';
    }
    if( !isset($args['content']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sms.3', 'msg'=>'No message specified'));
    }

    //
    // Add the message
    //
    $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.sms.message', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $sms_id = $rc['id'];    
    
    //
    // Add the object references
    //
    if( isset($args['object']) && $args['object'] != '' && isset($args['object_id']) && $args['object_id'] != '' ) {
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.sms.objref', array(
            'sms_id'=>$sms_id,
            'object'=>$args['object'],
            'object_id'=>$args['object_id'],
            ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sms.4', 'msg'=>'Unable to add object reference', 'err'=>$rc['err']));
        }
    }

    return array('stat'=>'ok', 'id'=>$sms_id);
}
?>
