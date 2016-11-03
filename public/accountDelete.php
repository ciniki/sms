<?php
//
// Description
// -----------
// This method will delete an sms account.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:            The ID of the business the sms account is attached to.
// account_id:            The ID of the sms account to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_sms_accountDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'account_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'SMS Account'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sms', 'private', 'checkAccess');
    $rc = ciniki_sms_checkAccess($ciniki, $args['business_id'], 'ciniki.sms.accountDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the sms account
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_sms_accounts "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['account_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sms', 'account');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['account']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sms.12', 'msg'=>'Airlock does not exist.'));
    }
    $account = $rc['account'];

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sms');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Remove the account
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.sms.account',
        $args['account_id'], $account['uuid'], 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sms');
        return $rc;
    }

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

    return array('stat'=>'ok');
}
?>
