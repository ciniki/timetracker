//
// This is the main app for the timetracker module
//
function ciniki_timetracker_tracker() {
    //
    // The panel to list the project
    //
    this.menu = new M.panel('Projects', 'ciniki_timetracker_tracker', 'menu', 'mc', 'medium', 'sectioned', 'ciniki.timetracker.main.menu');
    this.menu.data = {};
    this.menu.nplist = [];
    this.menu.sections = {
        'projects':{'label':'', 'type':'simplegrid', 'num_cols':3,
            'cellClasses':['', '', 'alignright'],
            'footerClasses':['', '', 'alignright'],
            'noData':'No projects',
            },
        'entries':{'label':'Recent', 'type':'simplegrid', 'num_cols':3,
            'cellClasses':['multiline', 'multiline', ''],
            'limit':15,
            },
    }
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'projects' ) {
            switch(j) {
                case 0: return d.name;
                case 1: 
                    if( d.entry_id > 0 ) {
                        return 'Stop';
                    } else {
                        return 'Start';
                    }
                case 2: return (d.today_length_display != null ? d.today_length_display : '-');
            }
        }
        if( s == 'entries' ) {
            switch(j) {
                case 0: return M.multiline(d.project_name, d.notes);
                case 1: return M.multiline(d.start_dt_display, (d.end_dt_display != '' ? d.end_dt_display : '-'));
                case 2: return d.length_display;
            }
        }
    }
/*    this.menu.footerValue = function(s, i, j, d) {
        if( s == 'projects' ) {
            if( i == 2 ) {
                return this.data.today_length_display;
            }
            return '';
        }
        return null;
    } */
    this.menu.rowClass = function(s, i, d) {
        if( s == 'projects' ) {
            if( d.entry_id > 0 ) {
                return 'statusgreen';
            } else {
                return 'statusred';
            }
        }
    }
    this.menu.rowFn = function(s, i, d) {
        if( s == 'projects' ) {
            if( d.entry_id > 0 ) {
                return 'M.ciniki_timetracker_tracker.menu.stopEntry(\'' + d.entry_id + '\');';
            } else {
                return 'M.ciniki_timetracker_tracker.menu.startEntry(\'' + d.id + '\');';
            }
        }
        if( s == 'entries' ) {
            return 'M.ciniki_timetracker_tracker.entry.open(\'M.ciniki_timetracker_tracker.menu.open();\',\'' + d.id + '\');';
        }
    }
/*    this.menu.updateButtons = function() {
        this.sections._buttons.buttons = {};
        for(var i in this.data.projects) {
            if( this.data.projects[i].entry_id > 0 ) {
                this.sections._buttons.buttons['_' + this.data.projects[i].id] = {
                    'label':this.data.projects[i].name + ' - Stop',
                    'fn':'M.ciniki_timetracker_tracker.menu.stopEntry(\'' + this.data.projects[i].entry_id + '\');',
                    };
            } else {
                this.sections._buttons.buttons['_' + this.data.projects[i].id] = {
                    'label':this.data.projects[i].name + ' - Start',
                    'fn':'M.ciniki_timetracker_tracker.menu.startEntry(\'' + this.data.projects[i].id + '\');',
                    };
            }
        }
    } */
    this.menu.startEntry = function(id) {
        M.api.getJSONCb('ciniki.timetracker.tracker', {'tnid':M.curTenantID, 'action':'start', 'project_id':id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_timetracker_tracker.menu;
            p.data = rsp;
            p.nplist = (rsp.nplist != null ? rsp.nplist : null);
//            p.updateButtons();
            p.refresh();
            p.show();
        });
    }
    this.menu.stopEntry = function(id) {
        M.api.getJSONCb('ciniki.timetracker.tracker', {'tnid':M.curTenantID, 'action':'stop', 'entry_id':id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_timetracker_tracker.menu;
            p.data = rsp;
            p.nplist = (rsp.nplist != null ? rsp.nplist : null);
//            p.updateButtons();
            p.refresh();
            p.show();
        });
    }
    this.menu.open = function(cb) {
        M.api.getJSONCb('ciniki.timetracker.tracker', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_timetracker_tracker.menu;
            p.data = rsp;
            p.title = 'Projects ' + rsp.today_length_display;
            p.nplist = (rsp.nplist != null ? rsp.nplist : null);
//            p.updateButtons();
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.addButton('refresh', 'Refresh', 'M.ciniki_timetracker_tracker.menu.open();');
    this.menu.addClose('Back');

    //
    // The panel to edit entry
    //
    this.entry = new M.panel('Time Entry', 'ciniki_timetracker_tracker', 'entry', 'mc', 'medium', 'sectioned', 'ciniki.timetracker.main.entry');
    this.entry.data = null;
    this.entry.entry_id = 0;
    this.entry.nplist = [];
    this.entry.sections = {
        'general':{'label':'', 'fields':{
            'start_dt':{'label':'Start', 'type':'text'},
            'end_dt':{'label':'End', 'type':'text'},
            'notes':{'label':'Notes', 'type':'textarea', 'size':'small'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_timetracker_tracker.entry.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_timetracker_tracker.entry.entry_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_timetracker_tracker.entry.remove();'},
            }},
        };
    this.entry.fieldValue = function(s, i, d) { return this.data[i]; }
    this.entry.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.timetracker.entryHistory', 'args':{'tnid':M.curTenantID, 'entry_id':this.entry_id, 'field':i}};
    }
    this.entry.open = function(cb, eid, list) {
        if( eid != null ) { this.entry_id = eid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.timetracker.entryGet', {'tnid':M.curTenantID, 'entry_id':this.entry_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_timetracker_tracker.entry;
            p.data = rsp.entry;
            p.refresh();
            p.show(cb);
        });
    }
    this.entry.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_timetracker_tracker.entry.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.entry_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.timetracker.entryUpdate', {'tnid':M.curTenantID, 'entry_id':this.entry_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.timetracker.entryAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_timetracker_tracker.entry.entry_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.entry.remove = function() {
        if( confirm('Are you sure you want to remove entry?') ) {
            M.api.getJSONCb('ciniki.timetracker.entryDelete', {'tnid':M.curTenantID, 'entry_id':this.entry_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_timetracker_tracker.entry.close();
            });
        }
    }
    this.entry.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.entry_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_timetracker_tracker.entry.save(\'M.ciniki_timetracker_tracker.entry.open(null,' + this.nplist[this.nplist.indexOf('' + this.entry_id) + 1] + ');\');';
        }
        return null;
    }
    this.entry.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.entry_id) > 0 ) {
            return 'M.ciniki_timetracker_tracker.entry.save(\'M.ciniki_timetracker_tracker.entry.open(null,' + this.nplist[this.nplist.indexOf('' + this.entry_id) - 1] + ');\');';
        }
        return null;
    }
    this.entry.addButton('save', 'Save', 'M.ciniki_timetracker_tracker.entry.save();');
    this.entry.addClose('Cancel');
    this.entry.addButton('next', 'Next');
    this.entry.addLeftButton('prev', 'Prev');

    //
    // Start the app
    // cb - The callback to run when the user leaves the main panel in the app.
    // ap - The application prefix.
    // ag - The app arguments.
    //
    this.start = function(cb, ap, ag) {
        args = {};
        if( ag != null ) {
            args = eval(ag);
        }

        if( M.curTenant.permissions['ciniki.owners'] == 'yes' ) {
            this.menu.addButton('settings', 'Manage', 'M.startApp(\'ciniki.timetracker.main\',null,\'M.ciniki_timetracker_tracker.menu.open();\');');
            //this.menu.addButton('settings', 'Manage', 'M.ciniki_timetracker_tracker.projects.open(\'M.ciniki_timetracker_tracker.menu.open();\');');
        } else {
            this.menu.delbutton('settings');
        }

        //
        // Create the app container
        //
        var ac = M.createContainer(ap, 'ciniki_timetracker_tracker', 'yes');
        if( ac == null ) {
            alert('App Error');
            return false;
        }

        if( args.entry_id != null && args.entry_id > 0 ) {
            this.entry.open(cb, args.entry_id);
        } else {
            this.menu.open(cb);
        }
    }
}
