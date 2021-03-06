<?php
//
// Description
// -----------
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to the sms belongs to.
// sms_id:          The ID of the sms message to send.
// 
// Returns
// -------
//
function ciniki_sms_sendMessage(&$ciniki, $tnid, $sms_id, $account) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');

    //
    // This function is run after the API has returned status, or from cron,
    // so all errors should be send to sms log
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sms', 'private', 'logMsg');
    
    //
    // Query for sms details
    //
    $strsql = "SELECT id, "
        . "account_id, "
        . "flags, "
        . "status, "
        . "customer_id, "
        . "cell_number, "
        . "content "
        . "FROM ciniki_sms_messages "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $sms_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sms', 'sms');
    if( $rc['stat'] != 'ok' ) {
        return ciniki_sms_logMsg($ciniki, $tnid, array('code'=>'ciniki.sms.18', 'msg'=>'Unable to find message',
            'sms_id'=>$sms_id, 'severity'=>50, 'err'=>$rc['err'],
            ));
    }
    if( !isset($rc['sms']) ) {
        return ciniki_sms_logMsg($ciniki, $tnid, array('code'=>'ciniki.sms.19', 'msg'=>'Message does not exist.',
            'sms_id'=>$sms_id, 'severity'=>50, 
            ));
    }
    $message = $rc['sms'];

    //
    // Check to make sure the status is unsent
    //
    if( $message['status'] != '10' && $message['status'] != '15' ) {
        return ciniki_sms_logMsg($ciniki, $tnid, array('code'=>'ciniki.sms.20', 'msg'=>'Message does not exist.',
            'sms_id'=>$sms_id, 'severity'=>10, 
            ));
    }

    //
    // Get the account if not specified
    //
    if( !isset($account) || $account == null ) {
        $strsql = "SELECT id, api_method, api_endpoint, cell_arg, msg_arg, key_arg, account_key, sms_5min_limit "
            . "FROM ciniki_sms_accounts "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND status = 10 "
            . "";
        if( isset($message['account_id']) && $message['account_id'] > 0 ) {
            $strsql .= "AND id = '" . ciniki_core_dbQuote($ciniki, $message['account_id']) . "' ";
        }
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sms', 'account');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['account']) ) {
            //
            // Check for default account
            //
            $strsql = "SELECT id, api_method, api_endpoint, cell_arg, msg_arg, key_arg, account_key, sms_5min_limit "
                . "FROM ciniki_sms_accounts "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND status = 10 "
                . "LIMIT 1 "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sms', 'account');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( !isset($rc['account']) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sms.10', 'msg'=>'No account specified'));
            }
        }
        $account = $rc['account'];
    }

    //
    // Check if we can lock the message, by updating to status 20
    //
    $strsql = "UPDATE ciniki_sms_messages "
        . "SET status = 20, last_updated = UTC_TIMESTAMP() "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $sms_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.sms');
    if( $rc['stat'] != 'ok' ) {
        return ciniki_sms_logMsg($ciniki, $tnid, array('code'=>'ciniki.sms.21', 'msg'=>'Unable to acquire lock.', 'pmsg'=>'Failed to update status=20',
            'sms_id'=>$sms_id, 'severity'=>50, 'err'=>$rc['err'],
            ));
    }
    if( $rc['num_affected_rows'] < 1 ) {
        return ciniki_sms_logMsg($ciniki, $tnid, array('code'=>'ciniki.sms.22', 'msg'=>'Unable to acquire lock.', 'pmsg'=>'No rows updated',
            'sms_id'=>$sms_id, 'severity'=>50, 'err'=>$rc['err'],
            ));
    }
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.sms', 'ciniki_sms_history', $tnid, 
        2, 'ciniki_sms_messages', $sms_id, 'status', '20');

    //
    // Send the message
    //
    if( $account['api_method'] == 10 ) {
        $url = $account['api_endpoint'] . '?';
        if( isset($ciniki['config']['ciniki.sms']['force.number']) && $ciniki['config']['ciniki.sms']['force.number'] != '' ) {
            $url .= $account['cell_arg'] . '=' . rawurlencode($ciniki['config']['ciniki.sms']['force.number']) . '&';
        } else {
            $url .= $account['cell_arg'] . '=' . rawurlencode($message['cell_number']) . '&';
        }
        $url .= $account['msg_arg'] . '=' . rawurlencode($message['content']) . '&';
        $url .= $account['key_arg'] . '=' . rawurlencode($account['account_key']) . '&';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    //  curl_setopt($ch, CURLOPT_SSLVERSION, 1);
        
        $rsp = curl_exec($ch);
        if( $rsp === false ) {
            $error_rsp = ciniki_sms_logMsg($ciniki, $tnid, array('code'=>'ciniki.sms.23', 'msg'=>'Unable to send message.', 'pmsg'=>'No rows updated',
                'sms_id'=>$sms_id, 'severity'=>50, 'err'=>$rc['err'],
                ));
            if( $message['status'] == '10' ) {
                $strsql = "UPDATE ciniki_sms_messages "
                    . "SET status = 15, last_updated = UTC_TIMESTAMP() "
                    . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $sms_id) . "' "
                    . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . "AND status = 20 "
                    . "";
                $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.sms');
                if( $rc['stat'] != 'ok' ) {
                    return ciniki_sms_logMsg($ciniki, $tnid, array('code'=>'ciniki.sms.24', 'msg'=>'Unable to send message, trying again.', 'pmsg'=>'Failed to update status=15',
                        'sms_id'=>$sms_id, 'severity'=>50, 'err'=>$rc['err'],
                        ));
                }
            } elseif( $message['status'] == '15' ) {
                $strsql = "UPDATE ciniki_sms_messages "
                    . "SET status = 50, last_updated = UTC_TIMESTAMP() "
                    . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $sms_id) . "' "
                    . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . "AND status = 20 "
                    . "";
                $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.sms');
                if( $rc['stat'] != 'ok' ) {
                    return ciniki_sms_logMsg($ciniki, $tnid, array('code'=>'ciniki.sms.25', 'msg'=>'Unable to send message.', 'pmsg'=>'Failed to update status=50',
                        'sms_id'=>$sms_id, 'severity'=>50, 'err'=>$rc['err'],
                        ));
                }
            }
            return $error_rsp;
        }
       
        //
        // Check if the messages was accepted
        //
        if( $rsp != 'Message queued successfully' ) {
            //
            // Log the error
            //
            $error_rsp = ciniki_sms_logMsg($ciniki, $tnid, array('code'=>'ciniki.sms.26', 'msg'=>'Unable to send message.', 'pmsg'=>$rsp,
                'sms_id'=>$sms_id, 'severity'=>50,
                ));
            if( $message['status'] == '10' ) {
                $strsql = "UPDATE ciniki_sms_messages "
                    . "SET status = 15, last_updated = UTC_TIMESTAMP() "
                    . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $sms_id) . "' "
                    . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . "AND status = 20 "
                    . "";
                $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.sms');
                if( $rc['stat'] != 'ok' ) {
                    return ciniki_sms_logMsg($ciniki, $tnid, array('code'=>'ciniki.sms.27', 'msg'=>'Unable to send message, trying again.', 'pmsg'=>'Failed to update status=15',
                        'sms_id'=>$sms_id, 'severity'=>50, 'err'=>$rc['err'],
                        ));
                }
            } elseif( $message['status'] == '15' ) {
                $strsql = "UPDATE ciniki_sms_messages "
                    . "SET status = 50, last_updated = UTC_TIMESTAMP() "
                    . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $sms_id) . "' "
                    . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . "AND status = 20 "
                    . "";
                $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.sms');
                if( $rc['stat'] != 'ok' ) {
                    return ciniki_sms_logMsg($ciniki, $tnid, array('code'=>'ciniki.sms.28', 'msg'=>'Unable to send message.', 'pmsg'=>'Failed to update status=50',
                        'sms_id'=>$sms_id, 'severity'=>50, 'err'=>$rc['err'],
                        ));
                }
            }

            //
            // FIXME: Check for hooks to other modules to update message sent
            //
            return $error_rsp;
        } else {
            //
            // Update the status of the message
            //
            $dt = new DateTime('now', new DateTimeZone('UTC'));
            $strsql = "UPDATE ciniki_sms_messages "
                . "SET status = 30, "
                    . "date_sent = '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d H:i:s')) . "', "
                    . "last_updated = UTC_TIMESTAMP() "
                . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $sms_id) . "' "
                . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND status = 20 "
                . "";
            $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.sms');
            if( $rc['stat'] != 'ok' ) {
                return ciniki_sms_logMsg($ciniki, $tnid, array('code'=>'ciniki.sms.29', 'msg'=>'Unable to acquire lock.', 'pmsg'=>'Failed to update status=30',
                    'sms_id'=>$sms_id, 'severity'=>50, 'err'=>$rc['err'],
                    ));
            }
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.sms', 'ciniki_sms_history', $tnid, 
                2, 'ciniki_sms_messages', $sms_id, 'status', '30');
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.sms', 'ciniki_sms_history', $tnid, 
                2, 'ciniki_sms_messages', $sms_id, 'date_sent', $dt->format('Y-m-d H:i:s'));
        } 
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sms.11', 'msg'=>'Invalid sms account'));
    }

    return array('stat'=>'ok');
}
