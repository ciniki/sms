<?php
//
// Description
// -----------
//
// Arguments
// ---------
// ciniki:
// 
// Returns
// -------
//
function ciniki_sms_cron_jobs($ciniki) {

    ciniki_cron_logMsg($ciniki, 0, array('code'=>'0', 'msg'=>'Checking for sms jobs', 'severity'=>'5'));

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    //
    // Get the list of businesses which have sms waiting to be sent
    //
    $strsql = "SELECT DISTINCT business_id "
        . "FROM ciniki_sms_messages "
        . "WHERE status = 10 OR status = 15 "
        . "";
    $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.sms', 'businesses', 'business_id');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sms.1', 'msg'=>'Unable to get list of businesses with sms', 'err'=>$rc['err']));
    }
    if( !isset($rc['businesses']) || count($rc['businesses']) == 0 ) {
        $businesses = array();
    } else {
        $businesses = $rc['businesses'];
    }

    //
    // For each business, load their sms settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sms', 'private', 'sendMessage');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    foreach($businesses as $business_id) {
        $limit = 0;     // Default to really slow sending, 1 every 5 minutes
        // FIXME: Add rate limiting
        //
        // Load the active accounts for the business
        //
        $strsql = "SELECT id, api_method, api_endpoint, cell_arg, msg_arg, key_arg, account_key, sms_5min_limit "
            . "FROM ciniki_sms_accounts "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND status = 10 "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.sms', array(
            array('container'=>'accounts', 'fname'=>'id', 
                'fields'=>array('id', 'api_method', 'api_endpoint', 'cell_arg', 'msg_arg', 'key_arg', 'account_key', 'sms_5min_limit')),
            ));
        if( $rc['stat'] != 'ok' ) {
            ciniki_cron_logMsg($ciniki, $business_id, array('code'=>'ciniki.sms.30', 'msg'=>'No accounts setup to send SMS.', 
                'severity'=>50, 'err'=>$rc['err']));
            continue;
        }
        if( !isset($rc['accounts']) ) {
            ciniki_cron_logMsg($ciniki, $business_id, array('code'=>'ciniki.sms.31', 'msg'=>'No accounts setup to send SMS.', 
                'severity'=>50, 'err'=>$rc['err']));
            continue;
        }
        if( count($rc['accounts']) < 1 ) {
            ciniki_cron_logMsg($ciniki, $business_id, array('code'=>'ciniki.sms.32', 'msg'=>'No accounts setup to send SMS.', 
                'severity'=>50, 'err'=>$rc['err']));
            continue;
        }
        $accounts = $rc['accounts'];
        $default_account_id = key($accounts);

        //
        // Load messages to send
        //
        $strsql = "SELECT id, account_id "
            . "FROM ciniki_sms_messages "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND (status = 10 OR status = 15) "
            . "ORDER BY status DESC, last_updated " // Any that we have tried to send will get their last_updated changed and be bumped to back of the line
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sms', 'sms');
        if( $rc['stat'] != 'ok' ) {
            ciniki_cron_logMsg($ciniki, $business_id, array('code'=>'ciniki.sms.33', 'msg'=>'Unable to load the list of messages to send', 
                'severity'=>50, 'err'=>$rc['err']));
            continue;
        }
        if( isset($rc['rows']) ) {
            $messages = $rc['rows'];
            foreach($messages as $sms_id => $message) {
                //
                // Send the message
                //
                $rc = ciniki_sms_sendMessage($ciniki, $business_id, $message['id'], 
                    (isset($accounts[$message['account_id']])?$accounts[$message['account_id']]:$accounts[$default_account_id]));
                if( $rc['stat'] != 'ok' ) {
                    ciniki_cron_logMsg($ciniki, $business_id, array('code'=>'ciniki.sms.34', 'msg'=>'Unable to send message',
                        'severity'=>50, 'err'=>$rc['err']));
                    continue;
                }
            }
        }
    }

    return array('stat'=>'ok');
}
?>
