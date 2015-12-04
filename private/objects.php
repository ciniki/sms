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
        'o_name'=>'account',
        'o_container'=>'accounts',
		'sync'=>'yes',
		'table'=>'ciniki_sms_accounts',
		'fields'=>array(
            'name'=>array('name'=>'Name'),
            'status'=>array('name'=>'Status'),
			'api_method'=>array('name'=>'API Method'),
			'api_endpoint'=>array('name'=>'API URL'),
			'cell_arg'=>array('name'=>'Cell Argument Name'),
			'msg_arg'=>array('name'=>'Message Argument Name'),
			'key_arg'=>array('name'=>'Account Key Argument Name'),
			'account_key'=>array('name'=>'Account Key', 'default'=>''),
			'sms_5min_limit'=>array('name'=>'5 Minute Sending Limit', 'default'=>'1'),
            'disclaimer'=>array('name'=>'Disclaimer', 'default'=>''),
			),
		'history_table'=>'ciniki_sms_history',
		);
	$objects['message'] = array(
		'name'=>'SMS Message',
        'o_name'=>'message',
        'o_container'=>'messages',
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
        'o_name'=>'objref',
        'o_container'=>'objrefs',
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
        'o_name'=>'log',
        'o_container'=>'logs',
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
