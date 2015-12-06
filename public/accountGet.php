<?php
//
// Description
// ===========
// This method will return all the information about an sms account.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the sms account is attached to.
// account_id:          The ID of the sms account to get the details for.
//
// Returns
// -------
//
function ciniki_sms_accountGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'account_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'SMS Account'),
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
    $rc = ciniki_sms_checkAccess($ciniki, $args['business_id'], 'ciniki.sms.accountGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load business settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Return default for new SMS Account
    //
    if( $args['account_id'] == 0 ) {
        $account = array('id'=>0,
            'name'=>'',
            'status'=>'10',
            'api_method'=>'10',
            'api_endpoint'=>'',
            'cell_arg'=>'',
            'msg_arg'=>'',
            'key_arg'=>'',
            'account_key'=>'',
            'sms_5min_limit'=>'',
            'disclaimer'=>'',
        );
    }

    //
    // Get the details for an existing SMS Account
    //
    else {
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
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2802', 'msg'=>'SMS Account not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['account']) ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2812', 'msg'=>'Unable to find SMS Account'));
        }
        $account = $rc['account'];
    }

    return array('stat'=>'ok', 'account'=>$account);
}
?>
