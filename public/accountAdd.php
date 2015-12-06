<?php
//
// Description
// -----------
// This method will add a new sms account for the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to add the SMS Account to.
//
// Returns
// -------
// <rsp stat="ok" id="42">
//
function ciniki_sms_accountAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'),
        'status'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Status'),
        'api_method'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'API Method'),
        'api_endpoint'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'API URL'),
        'cell_arg'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Cell Argument Name'),
        'msg_arg'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Message Argument Name'),
        'key_arg'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Account Key Argument Name'),
        'account_key'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Account Key'),
        'sms_5min_limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'5 Minute Sending Limit'),
        'disclaimer'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Disclaimer'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sms', 'private', 'checkAccess');
    $rc = ciniki_sms_checkAccess($ciniki, $args['business_id'], 'ciniki.sms.accountAdd');
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
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sms');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Add the sms account to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.sms.account', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sms');
        return $rc;
    }
    $account_id = $rc['id'];

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.sms');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the business modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
    ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'sms');

    return array('stat'=>'ok', 'id'=>$account_id);
}
?>
