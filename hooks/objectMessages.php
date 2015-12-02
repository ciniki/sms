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
function ciniki_sms_hooks_objectMessages($ciniki, $business_id, $args) {

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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $business_id);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	$datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

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
			. "ciniki_sms_messages.cell_phone, "
			. "ciniki_sms_messages.content "
			. "FROM ciniki_sms_objrefs, ciniki_sms_messages "
			. "WHERE ciniki_sms_objrefs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_sms_objrefs.object = '" . ciniki_core_dbQuote($ciniki, $args['object']) . "' "
			. "AND ciniki_sms_objrefs.object_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
			. "AND ciniki_sms_objrefs.sms_id = ciniki_sms.id "
			. "AND ciniki_sms_messages.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		if( isset($args['customer_id']) && $args['customer_id'] > 0 ) {
			$strsql .= "AND ciniki_sms_messages.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
		}
		$strsql .= "ORDER BY ciniki_sms_messages.date_sent DESC "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
		$rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sms', array(
			array('container'=>'messages', 'fname'=>'id',
				'fields'=>array('id', 'status', 'status_text', 'date_sent', 'customer_name', 'cell_phone', 'content'),
				'maps'=>array('status_text'=>$maps['sms']['status']),
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

	return array('stat'=>'ok');
}
?>
