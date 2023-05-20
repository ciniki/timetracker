//
// This is the main app for the timetracker module
//
function ciniki_timetracker_tracker() {
    //
    // The panel to list the project
    //
    this.menu = new M.panel('Projects', 'ciniki_timetracker_tracker', 'menu', 'mc', 'xlarge narrowaside', 'sectioned', 'ciniki.timetracker.main.menu');
    this.menu.data = {};
    this.menu.nplist = [];
    this.menu.sections = {
        'types':{'label':'', 'type':'simplegrid', 'num_cols':3, 'aside':'yes',
            'cellClasses':['', '', 'alignright'],
            'footerClasses':['', '', 'alignright'],
            'noData':'No projects',
            'addTxt':'Add Entry',
            'addFn':'M.ciniki_timetracker_tracker.entry.open(\'M.ciniki_timetracker_tracker.menu.open();\', 0);',
            },
        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':6,
            'cellClasses':['multiline', 'multiline', ''],
            'dataMaps':['name', 'time', 'length'],
            'hint':'Search entries',
            'noData':'No entries found',
            },
        'entries':{'label':'Recent', 'type':'simplegrid', 'num_cols':3,
            'cellClasses':['multiline', 'multiline', ''],
            'dataMaps':['name', 'time', 'length'],
            },
    }
    this.menu.liveSearchCb = function(s, i, v) {
        if( s == 'search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.timetracker.entrySearch', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'25'}, function(rsp) {
                M.ciniki_timetracker_tracker.menu.liveSearchShow('search',null,M.gE(M.ciniki_timetracker_tracker.menu.panelUID + '_' + s), rsp.entries);
                });
        }
    }
    this.menu.liveSearchResultValue = function(s, f, i, j, d) {
        return this.cellValue(s, i, j, d);
    }
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'types' ) {
            switch(j) {
                case 0: return d.type;
                case 1: return (d.today_length_display != null ? d.today_length_display : '-');
                case 2: 
                    if( d.entry_id > 0 ) {
                        return '<button onclick="M.ciniki_timetracker_tracker.menu.stopEntry(\'' + d.entry_id + '\');">Stop</button>';
                    } else {
                        return '<button onclick="M.ciniki_timetracker_tracker.menu.startEntry(\'' + d.type + '\',\'\',\'\');">Start</button>';
                    }
            }
        }
        if( s == 'entries' || s == 'search' ) {
            if( this.sections['entries'].dataMaps[j] == 'type' ) {
                return M.multiline(d.type, d.module);
            }
            if( this.sections['entries'].dataMaps[j] == 'project' ) {
                return M.multiline(d.project, d.task);
            }
            if( this.sections['entries'].dataMaps[j] == 'customer' ) {
                return M.multiline(d.customer, d.notes);
            }
            if( this.sections['entries'].dataMaps[j] == 'time' ) {
                return M.multiline(d.start_dt_display, (d.end_dt_display != '' ? d.end_dt_display : '-'));
            }
            if( this.sections['entries'].dataMaps[j] == 'length' ) {
                return d.length_display;
            }
            if( this.sections['entries'].dataMaps[j] == 'start' ) {
                if( d.end_dt_display == '' ) {
                    return '<button onclick="event.stopPropagation();M.ciniki_timetracker_tracker.menu.stopEntry(\'' + d.id + '\');">Stop</button>';

                }
                return '<button onclick="event.stopPropagation();M.ciniki_timetracker_tracker.menu.startEntry(\'' + escape(d.type) + '\',\'' + escape(d.project) + '\',\'' + escape(d.task) + '\',\'' + escape(d.module) + '\',\'' + escape(d.customer) + '\',\'' + escape(d.notes) + '\');">Start</button>';
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
        if( s == 'types' ) {
            if( d.entry_id > 0 ) {
                return 'statusgreen';
            } else {
                return 'statusred';
            }
        }
    }
    this.menu.rowFn = function(s, i, d) {
/*        if( s == 'projects' ) {
            if( d.entry_id > 0 ) {
                return 'M.ciniki_timetracker_tracker.menu.stopEntry(\'' + d.entry_id + '\');';
            } else {
                return 'M.ciniki_timetracker_tracker.menu.startEntry(\'' + d.id + '\');';
            }
        } */
        if( s == 'entries' || s == 'search' ) {
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
    this.menu.startEntry = function(type,project,task,module,customer,notes) {
        M.api.getJSONCb('ciniki.timetracker.tracker', {'tnid':M.curTenantID, 'action':'start', 'type':unescape(type), 'project':unescape(project), 'task':unescape(task), 'module':(module != null ? unescape(module) : ''), 'customer':(customer != null ? customer : ''), 'notes':(notes != null ? unescape(notes) : '')}, function(rsp) {
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
            p.title = 'Projects ' + rsp.today_length_display;
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
//          Timeout causes this page to reload when navigated away from it. Need to fix
//          add shutoff for timer when close/click home.
//            p.timeout = setTimeout(M.ciniki_timetracker_tracker.menu.open, (5*60*1000));

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
/*        'customer_details':{'label':'Customer', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
            'visible':function() { return M.modFlagSet('ciniki.timetracker', 0x02); },
            'cellClasses':['label', ''],
            'changeTxt':'Change Customer',
            'changeFn':'M.ciniki_timetracker_tracker.entry.changeCustomer();',
            }, */
        'general':{'label':'', 'fields':{
//            'project_id':{'label':'Project', 'type':'select', 'options':{}, 'complex_options':{'value':'id', 'name':'name'}},
            'type':{'label':'Type', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
            'project':{'label':'Project', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
            'task':{'label':'Task', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
            'module':{'label':'Module', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes', 
                'visible':function() { return M.modFlagSet('ciniki.timetracker', 0x01); },
                },
            'customer':{'label':'Customer', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes', 
                'visible':function() { return M.modFlagSet('ciniki.timetracker', 0x02); },
                },
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
    this.entry.liveSearchCb = function(s, i, value) {
        if( i == 'type' || i == 'project' || i == 'module' || i == 'task' || i == 'customer' ) {
            M.api.getJSONBgCb('ciniki.timetracker.entryFieldSearch', {'tnid':M.curTenantID, 'field':i, 'start_needle':value, 'limit':25},
                function(rsp) {
                    M.ciniki_timetracker_tracker.entry.liveSearchShow(s, i, M.gE(M.ciniki_timetracker_tracker.entry.panelUID + '_' + i), rsp.results);
                });
        }
    };
    this.entry.liveSearchResultValue = function(s, f, i, j, d) {
        if( d != null ) { return d.value; }
        return '';
    };
    this.entry.liveSearchResultRowFn = function(s, f, i, j, d) { 
        if( d != null ) {
            return 'M.ciniki_timetracker_tracker.entry.updateField(\'' + s + '\',\'' + f + '\',\'' + escape(d.value) + '\');';
        }
    };
    this.entry.updateField = function(s, fid, v) {
        M.gE(this.panelUID + '_' + fid).value = unescape(v);
        this.removeLiveSearch(s, fid);
    };
    this.entry.cellValue = function(s, i, j, d) {
        if( s == 'customer_details' ) {
            switch(j) {
                case 0: return d.label;
                case 1: return (d.label == 'Email' ? M.linkEmail(d.value) : d.value);
            }
        }
    }
    this.entry.rowFn = function(s, i, d) {
        return '';
    }
    this.entry.changeCustomer = function() {
        this.save('M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_timetracker_tracker.entry.updateCustomer();\',\'mc\',{\'next\':\'M.ciniki_timetracker_tracker.entry.updateCustomer\',\'customer_id\':M.ciniki_timetracker_tracker.entry.data.customer_id,\'action\':\'change\',\'current_id\':M.ciniki_timetracker_tracker.entry.data.customer_id});');
    }
    this.entry.updateCustomer = function(cid) {
        if( cid != null && this.data.customer_id != cid ) {
            M.api.getJSONCb('ciniki.timetracker.entryUpdate', {'tnid':M.curTenantID, 'entry_id':this.entry_id, 'customer_id':cid}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_timetracker_tracker.entry.open();
            });
        } else {
            this.show();
        }
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
            // p.sections.general.fields.project_id.options = rsp.projects;
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
            this.menu.delButton('settings');
        }
    
        //
        // Setup for modules
        //
/*        if( M.modFlagOn('ciniki.timetracker', 0x02) ) {
            this.entry.size = 'medium mediumaside';
        } else {
            this.entry.size = 'medium';
        } */
        // Modules or Customers enabled
        if( M.modFlagAny('ciniki.timetracker', 0x03) == 'yes' ) {
            this.menu.sections.entries.num_cols = 6;
            this.menu.sections.entries.cellClasses = ['multiline', 'multiline', 'multiline', 'multiline', '', ''];
            this.menu.sections.entries.dataMaps = ['type', 'project', 'customer', 'time', 'length', 'start'];
        } else {
            this.menu.sections.entries.num_cols = 3;
            this.menu.sections.entries.cellClasses = ['multiline', 'multiline', ''];
            this.menu.sections.entries.dataMaps = ['type', 'project', 'time', 'length'];
        }
        this.menu.sections.search.livesearchcols = this.menu.sections.entries.num_cols;
        this.menu.sections.search.cellClasses = this.menu.sections.entries.cellClasses;
        this.menu.sections.search.dataMaps = this.menu.sections.entries.dataMaps;

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
