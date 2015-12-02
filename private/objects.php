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
function ciniki_sms_objects($ciniki) {
	
	$objects = array();
	$objects['account'] = array(
		'name'=>'SMS Account',
		'sync'=>'yes',
		'table'=>'ciniki_sms_accounts',
		'fields'=>array(
			'api_method'=>array(),
			'api_endpoint'=>array(),
			'cell_arg'=>array(),
			'msg_arg'=>array(),
			'key_arg'=>array(),
			'account_key'=>array('default'=>''),
			'sms_5min_limit'=>array('default'=>'1'),
			),
		'history_table'=>'ciniki_sms_history',
		);
	$objects['message'] = array(
		'name'=>'SMS Message',
		'sync'=>'yes',
		'table'=>'ciniki_sms_messages',
		'fields'=>array(
			'account_id'=>array('ref'=>'ciniki.sms.account', 'default'=>'0'),
			'customer_id'=>array('ref'=>'ciniki.customers.customer', 'default'=>'0'),
			'flags'=>array('default'=>'0'),
			'status'=>array('default'=>'0'),
			'cell_number'=>array(),
			'content'=>array(),
			'date_read'=>array('default'=>''),
			),
		'history_table'=>'ciniki_sms_history',
		);
	$objects['objref'] = array(
		'name'=>'SMS Object Reference',
		'sync'=>'yes',
		'table'=>'ciniki_sms_objrefs',
		'fields'=>array(
			'sms_id'=>array('ref'=>'ciniki.sms.message'),
			'object'=>array(),
			'object_id'=>array(),
			),
		'history_table'=>'ciniki_sms_history',
		);
	$objects['log'] = array(
		'name'=>'SMS Log',
		'sync'=>'yes',
		'table'=>'ciniki_sms_log',
		'fields'=>array(
			'sms_id'=>array('default'=>'0', 'ref'=>'ciniki.sms.message'),
			'severity'=>array('default'=>'10'),
			'log_date'=>array(),
			'code'=>array(),
			'msg'=>array(),
			'pmsg'=>array('default'=>''),
			'errors'=>array('default'=>''),
			'raw_logs'=>array('default'=>''),
			),
		);

	
	return array('stat'=>'ok', 'objects'=>$objects);
}
?>
