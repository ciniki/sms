<?php
//
// Description
// -----------
// This method will return the list of actions that were applied to an element of an sms account.
// This method is typically used by the UI to display a list of changes that have occured
// on an element through time. This information can be used to revert elements to a previous value.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to get the details for.
// account_id:          The ID of the sms account to get the history for.
// field:                   The field to get the history for.
//
// Returns
// -------
// <history>
// <action user_id="2" date="May 12, 2012 10:54 PM" value="SMS Account Name" age="2 months" user_display_name="Andrew" />
// ...
// </history>
//
function ciniki_sms_accountHistory($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'account_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'SMS Account'),
        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'field'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sms', 'private', 'checkAccess');
    $rc = ciniki_sms_checkAccess($ciniki, $args['business_id'], 'ciniki.sms.accountHistory');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
    return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.sms', 'ciniki_sms_history', $args['business_id'], 'ciniki_sms_accounts', $args['account_id'], $args['field']);
}
?>
