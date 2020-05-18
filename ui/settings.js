//
function ciniki_sms_settings() {
    //
    // Initialize the panels
    //
    this.init = function() {
        //
        // The menu panel showing the list of accounts
        //
        this.menu = new M.panel('SMS Accounts',
            'ciniki_sms_settings', 'menu',
            'mc', 'medium', 'sectioned', 'ciniki.sms.settings.menu');
        this.menu.sections = {
            'accounts':{'label':'SMS Account', 'type':'simplegrid', 'num_cols':1,
                'addTxt':'Add Account',
                'addFn':'M.ciniki_sms_settings.accountEdit(\'M.ciniki_sms_settings.menuShow();\',0);',
                },
            };
        this.menu.sectionData = function(s) { return this.data[s]; }
        this.menu.cellValue = function(s, i, j, d) {
            return d.name;
        };
        this.menu.rowFn = function(s, i, d) {
            return 'M.ciniki_sms_settings.accountEdit(\'M.ciniki_sms_settings.menuShow();\',\'' + d.id + '\');';
        };
        this.menu.addButton('add', 'Add', 'M.ciniki_sms_settings.accountEdit(\'M.ciniki_sms_settings.menuShow();\',0);');
        this.menu.addClose('Back');

        //
        // The main panel, which lists the options for production
        //
        this.edit = new M.panel('Account',
            'ciniki_sms_settings', 'edit',
            'mc', 'medium', 'sectioned', 'ciniki.sms.settings.edit');
        this.edit.sections = {
            'account':{'label':'Account', 'fields':{
                'name':{'label':'Name', 'type':'text'},
                'status':{'label':'Status', 'type':'toggle', 'toggles':{'10':'Active', '50':'Archive'}},
                'account_key':{'label':'Key', 'type':'text'},
            }},
            'api':{'label':'API', 'fields':{
                'api_method':{'label':'Method', 'type':'toggle', 'default':'10', 'toggles':{'10':'Curl'}},
                'api_endpoint':{'label':'URL', 'type':'text'},
                'cell_arg':{'label':'Cell Arg', 'type':'text'},
                'msg_arg':{'label':'Msg Arg', 'type':'text'},
                'key_arg':{'label':'Key Arg', 'type':'text'},
            }},
            'throttling':{'label':'Sending Limits', 'fields':{
                'sms_5min_limit':{'label':'5 Minutes', 'type':'text', 'size':'small'},
            }},
            '_disclaimer':{'label':'Disclaimer', 'fields':{
                'disclaimer':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
            '_buttons':{'label':'', 'buttons':{
                'test':{'label':'Send Test Message', 'fn':'M.ciniki_sms_settings.accountTest();'},
                'save':{'label':'Save', 'fn':'M.ciniki_sms_settings.accountSave();'},
                'delete':{'label':'Delete', 'fn':'M.ciniki_sms_settings.accountDelete();'},
            }},
        };
        this.edit.fieldValue = function(s, i, d) { 
            return this.data[i];
        };
        this.edit.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.sms.accountHistory', 'args':{'tnid':M.curTenantID, 'setting':i}};
        };
        this.edit.addButton('save', 'Save', 'M.ciniki_sms_settings.accountSave();');
        this.edit.addClose('Cancel');
    }

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_sms_settings', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        this.menuShow(cb);
    }

    //
    // Grab the stats for the tenant from the database and present the list of orders.
    //
    this.menuShow = function(cb) {
        M.api.getJSONCb('ciniki.sms.accountList', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sms_settings.menu;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    }

    this.accountEdit = function(cb, aid) {
        if( aid != null ) { this.edit.account_id = aid; }
        this.edit.reset();
        this.edit.sections._buttons.buttons.delete.visible = (this.edit.account_id>0?'yes':'no');
        M.api.getJSONCb('ciniki.sms.accountGet', {'tnid':M.curTenantID, 'account_id':this.edit.account_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sms_settings.edit;
            p.data = rsp.account;
            p.refresh();
            p.show(cb);
        });
    };

    this.accountSave = function() {
        if( this.edit.account_id > 0 ) {
            var c = this.edit.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.sms.accountUpdate', {'tnid':M.curTenantID, 'account_id':this.edit.account_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_sms_settings.edit.close();
                });
            } else {
                this.edit.close();
            }
        } else {
            var c = this.edit.serializeForm('yes');
            M.api.postJSONCb('ciniki.sms.accountAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_sms_settings.edit.close();
            });
        }
    }

    this.accountTest = function() {
        var cell_number = prompt('Cell #');
        if( cell_number != '' ) {
            if( this.edit.account_id > 0 ) {
                var c = this.edit.serializeForm('no');
                M.api.postJSONCb('ciniki.sms.accountUpdate', {'tnid':M.curTenantID, 'account_id':this.edit.account_id, 'sendtest':cell_number}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.alert('SMS sent');
                    M.ciniki_sms_settings.accountEdit();
                });
            } else {
                var c = this.edit.serializeForm('yes');
                M.api.postJSONCb('ciniki.sms.accountAdd', {'tnid':M.curTenantID, 'sendtest':cell_number}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.alert('SMS sent');
                    M.ciniki_sms_settings.accountEdit(null,rsp.id);
                });
                
            }
        }
    }
}
