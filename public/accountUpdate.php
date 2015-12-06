<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_sms_accountUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'account_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'SMS Account'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
        'status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Status'),
        'api_method'=>array('required'=>'no', 'blank'=>'no', 'name'=>'API Method'),
        'api_endpoint'=>array('required'=>'no', 'blank'=>'no', 'name'=>'API URL'),
        'cell_arg'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Cell Argument Name'),
        'msg_arg'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Message Argument Name'),
        'key_arg'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Account Key Argument Name'),
        'account_key'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Account Key'),
        'sms_5min_limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'5 Minute Sending Limit'),
        'disclaimer'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Disclaimer'),
        'sendtest'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Send Test Cell Number'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sms', 'private', 'checkAccess');
    $rc = ciniki_sms_checkAccess($ciniki, $args['business_id'], 'ciniki.sms.accountUpdate');
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
    // Update the SMS Account in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.sms.account', $args['account_id'], $args, 0x04);
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

    //
    // Check if test message should be sent
    //
    if( isset($args['sendtest']) && $args['sendtest'] != '' ) {
        $strsql = "SELECT ciniki_sms_accounts.id, "
            . "ciniki_sms_accounts.name, "
            . "ciniki_sms_accounts.status, "
            . "ciniki_sms_accounts.api_method, "
            . "ciniki_sms_accounts.api_endpoint, "
            . "ciniki_sms_accounts.cell_arg, "
            . "ciniki_sms_accounts.msg_arg, "
            . "ciniki_sms_accounts.key_arg, "
            . "ciniki_sms_accounts.account_key, "
            . "ciniki_sms_accounts.sms_5min_limit, "
            . "ciniki_sms_accounts.disclaimer "
            . "FROM ciniki_sms_accounts "
            . "WHERE ciniki_sms_accounts.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_sms_accounts.id = '" . ciniki_core_dbQuote($ciniki, $args['account_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sms', 'account');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2807', 'msg'=>'SMS Account not found', 'err'=>$rc['err']));
        }
        $account = $rc['account'];

        $url = $account['api_endpoint'] . '?';
        if( isset($ciniki['config']['ciniki.sms']['force.number']) && $ciniki['config']['ciniki.sms']['force.number'] != '' ) {
            $url .= $account['cell_arg'] . '=' . rawurlencode($ciniki['config']['ciniki.sms']['force.number']) . '&';
        } else {
            $url .= $account['cell_arg'] . '=' . rawurlencode($args['sendtest']) . '&';
        }
        $url .= '&' . $account['msg_arg'] . '=' . rawurlencode('Test SMS message') 
            . '&' . $account['key_arg'] . '=' . rawurlencode($account['account_key']);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $rsp = curl_exec($ch);
        if( $rsp === false ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2805', 'msg'=>'Unable to send SMS'));
        }
        if( $rsp != 'Message queued successfully' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2806', 'msg'=>'Unable to send SMS: ' . $rsp));
        }
        
    }

    return array('stat'=>'ok');
}
?>
