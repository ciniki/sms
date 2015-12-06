<?php
//
// Description
// -----------
// This method will return the list of SMS Accounts for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to get SMS Account for.
//
// Returns
// -------
//
function ciniki_sms_accountList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sms', 'private', 'checkAccess');
    $rc = ciniki_sms_checkAccess($ciniki, $args['business_id'], 'ciniki.sms.accountList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of accounts
    //
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
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sms', array(
        array('container'=>'accounts', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'status', 'api_method', 'api_endpoint', 'cell_arg', 'msg_arg', 'key_arg', 'account_key', 'sms_5min_limit', 'disclaimer')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['accounts']) ) {
        $accounts = $rc['accounts'];
    } else {
        $accounts = array();
    }

    return array('stat'=>'ok', 'accounts'=>$accounts);
}
?>
