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
function ciniki_sms_hooks_objectMessages($ciniki, $tnid, $args) {

    //
    // Load the status maps for the text description of each status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sms', 'private', 'maps');
    $rc = ciniki_sms_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Load intl date settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    $time_format = ciniki_users_timeFormat($ciniki, 'php');

    //
    // Check for messages
    //
    if( isset($args['object']) && $args['object'] != '' 
        && isset($args['object_id']) && $args['object_id'] != ''
        ) {
        //
        // Check if there is any sms for this object
        //
        $strsql = "SELECT ciniki_sms_messages.id, "
            . "ciniki_sms_messages.status, "
            . "ciniki_sms_messages.status AS status_text, "
            . "ciniki_sms_messages.date_sent, "
            . "ciniki_sms_messages.customer_id, "
            . "ciniki_sms_messages.cell_number, "
            . "ciniki_sms_messages.content "
            . "FROM ciniki_sms_objrefs, ciniki_sms_messages "
            . "WHERE ciniki_sms_objrefs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_sms_objrefs.object = '" . ciniki_core_dbQuote($ciniki, $args['object']) . "' "
            . "AND ciniki_sms_objrefs.object_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND ciniki_sms_objrefs.sms_id = ciniki_sms_messages.id "
            . "AND ciniki_sms_messages.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        if( isset($args['customer_id']) && $args['customer_id'] > 0 ) {
            $strsql .= "AND ciniki_sms_messages.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
        }
        $strsql .= "ORDER BY ciniki_sms_messages.date_sent DESC "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sms', array(
            array('container'=>'messages', 'fname'=>'id',
                'fields'=>array('id', 'status', 'status_text', 'date_sent', 'cell_number', 'content'),
                'maps'=>array('status_text'=>$maps['message']['status']),
                'utctotz'=>array('date_sent'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format)),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['messages']) ) {
            return array('stat'=>'ok', 'messages'=>$rc['messages']);
        }
    }

    elseif( isset($args['object']) && $args['object'] != '' 
        && isset($args['customer_id']) && $args['customer_id'] != ''
        ) {
        //
        // Check if there is any sms for this object
        //
        $strsql = "SELECT ciniki_sms_messages.id, "
            . "ciniki_sms_messages.status, "
            . "ciniki_sms_messages.status AS status_text, "
            . "ciniki_sms_messages.date_sent, "
            . "ciniki_sms_messages.date_sent AS sms_date, "
            . "ciniki_sms_messages.date_sent AS sms_time, "
            . "ciniki_sms_messages.customer_id, "
            . "ciniki_sms_messages.cell_number, "
            . "ciniki_sms_messages.content "
            . "FROM ciniki_sms_objrefs, ciniki_sms_messages "
            . "WHERE ciniki_sms_objrefs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_sms_objrefs.object = '" . ciniki_core_dbQuote($ciniki, $args['object']) . "' "
            . "AND ciniki_sms_objrefs.sms_id = ciniki_sms_messages.id "
            . "AND ciniki_sms_messages.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "AND ciniki_sms_messages.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $strsql .= "ORDER BY ciniki_sms_messages.date_sent DESC "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sms', array(
            array('container'=>'messages', 'fname'=>'id',
                'fields'=>array('id', 'status', 'status_text', 'date_sent', 'sms_date', 'sms_time', 'cell_number', 'content'),
                'maps'=>array('status_text'=>$maps['message']['status']),
                'utctotz'=>array('date_sent'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                    'sms_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                    'sms_time'=>array('timezone'=>$intl_timezone, 'format'=>$time_format)),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['messages']) ) {
            return array('stat'=>'ok', 'messages'=>$rc['messages']);
        }
    }

    return array('stat'=>'ok');
}
?>
