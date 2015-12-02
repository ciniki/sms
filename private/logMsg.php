<?php
//
// Description
// -----------
//
// Arguments
// ---------
// ciniki:
// business_id:		The ID of the business to the sms belongs to.
// sms_id:			The ID of the mail message to send.
// 
// Returns
// -------
//
function ciniki_sms_logMsg($ciniki, $business_id, $args) {

	//
	// Log date on the server
	//
	$dt = new DateTime('now', new DateTimeZone('UTC'));
	$args['log_date'] = $dt->format('Y-m-d H:i:s');

	//
	// Setup error response. This allows the calling function to return the output of logMsg instead
	// of building a second array to return error code.
	//
	$rsp = array('stat'=>'ok', 'err'=>array('pkg'=>'ciniki'));
	$rsp['code'] = $args['code'];
	$rsp['msg'] = $args['msg'];
	if( isset($args['pmsg']) ) {
		$rsp['pmsg'] = $args['pmsg'];
	}

	//
	// Serialize error array
	//
	if( isset($args['err']) ) {
		$args['errors'] = serialize($args['err']);
		$rsp['err'] = $args['err'];
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	$rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.sms.log', $args, 0x07);
	if( $rc['stat'] != 'ok' ) {
		error_log("MAIL-ERR[$business_id]: Unable to add log message (" . print_r($args, true) . ")");
	}

	return $rsp;
}
