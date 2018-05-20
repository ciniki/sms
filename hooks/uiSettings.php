<?php
//
// Description
// -----------
// This function will return a list of user interface settings for the module.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get sms for.
//
// Returns
// -------
//
function ciniki_sms_hooks_uiSettings($ciniki, $tnid, $args) {

    //
    // Setup the default response
    //
    $rsp = array('stat'=>'ok', 'menu_items'=>array(), 'settings_menu_items'=>array());

    //
    // Get the settings
    //
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_sms_settings', 'tnid', $tnid, 'ciniki.sms', 'settings', '');
    if( $rc['stat'] == 'ok' && isset($rc['settings']) ) {
        $rsp['settings'] = $rc['settings'];
    }

    //
    // Check permissions for what menu items should be available
    //
    if( isset($ciniki['tenant']['modules']['ciniki.sms'])
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
/*        $menu_item = array(
            'priority'=>2000,
            'label'=>'SMS', 
            'edit'=>array('app'=>'ciniki.sms.main'),
            );
        $rsp['menu_items'][] = $menu_item; */
    } 

    if( isset($ciniki['tenant']['modules']['ciniki.sms'])
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $rsp['settings_menu_items'][] = array('priority'=>2000, 'label'=>'SMS', 'edit'=>array('app'=>'ciniki.sms.settings'));
    }

    return $rsp;
}
?>
