//
// This is the main app for the timetracker module
//
function ciniki_timetracker_main() {
    //
    // The panel to list the project
    //
    this.menu = new M.panel('Time Tracker', 'ciniki_timetracker_main', 'menu', 'mc', 'large', 'sectioned', 'ciniki.timetracker.main.menu');
    this.menu.data = {};
    this.menu.nplist = [];
    this.menu._tabs = {'label':'', 'type':'paneltabs', 'selected':'monthly', 'tabs':{
        'daily':{'label':'Daily', 'fn':'M.ciniki_timetracker_main.menu.switchTab("daily");'},
        'weekly':{'label':'Weekly', 'fn':'M.ciniki_timetracker_main.menu.switchTab("weekly");'},
        'monthly':{'label':'Monthly', 'fn':'M.ciniki_timetracker_main.menu.switchTab("monthly");'},
//        'entries':{'label':'Entries', 'fn':'M.ciniki_timetracker_main.menu.switchTab("entries");'},
        'projects':{'label':'Projects', 'fn':'M.ciniki_timetracker_main.menu.switchTab("projects");'},
        }};
    this.menu.start_dt = '';
    this.menu.end_dt = '';
    this.menu.sections = {}
/*    this.menu.liveSearchCb = function(s, i, v) {
        if( s == 'search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.timetracker.projectSearch', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'25'}, function(rsp) {
                M.ciniki_timetracker_main.menu.liveSearchShow('search',null,M.gE(M.ciniki_timetracker_main.menu.panelUID + '_' + s), rsp.menu);
                });
        }
    }
    this.menu.liveSearchResultValue = function(s, f, i, j, d) {
        return d.name;
    }
    this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.ciniki_timetracker_main.project.open(\'M.ciniki_timetracker_main.menu.open();\',\'' + d.id + '\');';
    } */
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'projects' ) {
            switch(j) {
                case 0: return d.sequence;
                case 1: return d.name;
                case 2: return d.userlist;
            }
        }
        else {
            return d[this.data.columns[j].field];
        }
    }
    this.menu.cellFn = function(s, i, j, d) {
        if( s != 'projects' && j > 0 && j < (this.sections[s].num_cols-1) ) {
            return 'M.ciniki_timetracker_main.entries.open(\'M.ciniki_timetracker_main.menu.open();\',\'' + (d.id != null ? d.id : '*') + '\',\'' + (d.module != null ? d.module : '*') + '\',\'' + (d.customer_id != null ? d.customer_id : '*') + '\',\'' + this.data.columns[j].start_dt + '\',\'' + this.data.columns[j].end_dt + '\');';
        }
    }
/*    this.menu.footerValue = function(s, i, d) {
        if( s == 'projects' ) {
            return null;
        }
    } */
    this.menu.switchTab = function(t) {
        if( t != this._tabs.selected ) {
            if( t == 'daily' ) {
                // Current week
                this.start_dt = '';
                this.end_dt = '';
            } else if( t == 'weekly' ) {
                var dt = new Date();
                this.end_dt = dt.getFullYear() + '-' + (dt.getMonth()+1) + '-' + dt.getDate();
                dt.setSeconds(dt.getSeconds() - (21*24*60*60));
                this.start_dt = dt.getFullYear() + '-' + (dt.getMonth()+1) + '-' + dt.getDate();
            } else if( t == 'monthly' ) {
                var dt = new Date();
                this.end_dt = dt.getFullYear() + '-12-31';
                this.start_dt = dt.getFullYear() + '-01-01';
            }
        }
        this._tabs.selected = t;
        this.open();
    }
    this.menu.open = function(cb) {
        if( cb != null ) { this.cb = cb; }
        if( this._tabs.selected == 'projects' ) {
            M.api.getJSONCb('ciniki.timetracker.projectList', {'tnid':M.curTenantID}, this.openFinish);
        } else if( this._tabs.selected == 'entries' ) {
            M.api.getJSONCb('ciniki.timetracker.entries', {'tnid':M.curTenantID}, this.openFinish);
        } else {
            M.api.getJSONCb('ciniki.timetracker.projectStats', {'tnid':M.curTenantID, 'report':this._tabs.selected, 'start_dt':this.start_dt, 'end_dt':this.end_dt}, this.openFinish);
        }
    }
    this.menu.openFinish = function(rsp) {
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        var p = M.ciniki_timetracker_main.menu;
        p.data = rsp;
        p.sections = {
            '_tabs':p._tabs,
            };
        if( p._tabs.selected == 'daily' || p._tabs.selected == 'weekly' || p._tabs.selected == 'monthly' ) {
            p.size = 'full';
            p.start_dt = rsp.start_dt;
            p.end_dt = rsp.end_dt;
            p.sections['datepicker'] = {'label':'', 'type':'weekpicker', 'date2':'yes',
                'fn':'M.ciniki_timetracker_main.menu.startSelected',
                'fn2':'M.ciniki_timetracker_main.menu.endSelected',
                };
            var headerValues = [];
            for(var i in rsp.columns) {
                headerValues[i] = rsp.columns[i].label;
            }
            for(var i in rsp.users) {
                var footerValues = [];
                for(var j in rsp.columns) {
                    footerValues[j] = rsp.users[i].projects.total[rsp.columns[j].field];
                    if( footerValues[j] == null ) {
                        footerValues[j] = '';
                    }
                }
                p.sections['user_' + rsp.users[i].id] = {
                    'label':rsp.users[i].name, 'type':'simplegrid',
                    'num_cols':rsp.columns.length,
                    'headerValues':headerValues,
                    'noData':'Nothing tracker',
                    'footerValues':footerValues,
                };
                delete rsp.users[i].projects.total;
                p.data['user_' + rsp.users[i].id] = rsp.users[i].projects;
            }
//        } else if( p._tabs.selected == 'entries' ) {
//            p.sections['
        } else if( p._tabs.selected == 'projects' ) {
            p.sections['projects'] = {'label':'Projects', 'type':'simplegrid', 'num_cols':3,
                'headerValues':['Order', 'Project', 'Users'],
                'noData':'No project',
                'addTxt':'Add Projects',
                'addFn':'M.ciniki_timetracker_main.project.open(\'M.ciniki_timetracker_main.menu.open();\',0,null);',
                'rowFn':function(i, d) { return 'M.ciniki_timetracker_main.project.open(\'M.ciniki_timetracker_main.menu.open();\',\'' + d.id + '\',M.ciniki_timetracker_main.project.nplist);';},
                };
        }
        p.nplist = (rsp.nplist != null ? rsp.nplist : null);
        p.refresh();
        p.show();
    }
    this.menu.datePickerValue = function(s, d) {
        return this.start_dt;
    }
    this.menu.datePickerValue2 = function(s, d) {
        return this.end_dt;
    }
    this.menu.startSelected = function(s, d) {
        var odt = new Date(this.start_dt);
        var ndt = new Date(d);
        var edt = new Date(this.end_dt + ' 23:59:59');
        edt.setTime(edt.getTime() + (parseInt(ndt - odt)));
        this.start_dt = d;
        this.end_dt = edt.getFullYear() + '-' + (edt.getMonth()+1) + '-' + edt.getDate();
        this.open();
    }
    this.menu.endSelected = function(s, d) {
        this.end_dt = d;
        this.open();
    }
    this.menu.addClose('Back');

    //
    // The panel to edit Projects
    //
    this.project = new M.panel('Projects', 'ciniki_timetracker_main', 'project', 'mc', 'medium', 'sectioned', 'ciniki.timetracker.main.project');
    this.project.data = null;
    this.project.project_id = 0;
    this.project.nplist = [];
    this.project.sections = {
        'general':{'label':'', 'fields':{
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'status':{'label':'Status', 'type':'toggle', 'toggles':{'10':'Active', '90':'Archived'}},
            'sequence':{'label':'Order', 'type':'text', 'size':'small'},
            'user_ids':{'label':'Assigned', 'type':'multiselect', 'none':'yes', 'options':M.curTenant.employees, 'history':'yes'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_timetracker_main.project.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_timetracker_main.project.project_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_timetracker_main.project.remove();'},
            }},
        };
    this.project.fieldValue = function(s, i, d) { return this.data[i]; }
    this.project.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.timetracker.projectHistory', 'args':{'tnid':M.curTenantID, 'project_id':this.project_id, 'field':i}};
    }
    this.project.open = function(cb, pid, list) {
        if( pid != null ) { this.project_id = pid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.timetracker.projectGet', {'tnid':M.curTenantID, 'project_id':this.project_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_timetracker_main.project;
            p.data = rsp.project;
            p.sections.general.fields.user_ids.options = M.curTenant.employees;
            p.refresh();
            p.show(cb);
        });
    }
    this.project.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_timetracker_main.project.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.project_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.timetracker.projectUpdate', {'tnid':M.curTenantID, 'project_id':this.project_id}, c, function(rsp) {
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
            M.api.postJSONCb('ciniki.timetracker.projectAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_timetracker_main.project.project_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.project.remove = function() {
        if( confirm('Are you sure you want to remove project?') ) {
            M.api.getJSONCb('ciniki.timetracker.projectDelete', {'tnid':M.curTenantID, 'project_id':this.project_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_timetracker_main.project.close();
            });
        }
    }
    this.project.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.project_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_timetracker_main.project.save(\'M.ciniki_timetracker_main.project.open(null,' + this.nplist[this.nplist.indexOf('' + this.project_id) + 1] + ');\');';
        }
        return null;
    }
    this.project.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.project_id) > 0 ) {
            return 'M.ciniki_timetracker_main.project.save(\'M.ciniki_timetracker_main.project.open(null,' + this.nplist[this.nplist.indexOf('' + this.project_id) - 1] + ');\');';
        }
        return null;
    }
    this.project.addButton('save', 'Save', 'M.ciniki_timetracker_main.project.save();');
    this.project.addClose('Cancel');
    this.project.addButton('next', 'Next');
    this.project.addLeftButton('prev', 'Prev');


    //
    // The panel to list entries
    //
    this.entries = new M.panel('Entries', 'ciniki_timetracker_main', 'entries', 'mc', 'large', 'sectioned', 'ciniki.timetracker.main.entries');
    this.entries.data = null;
    this.entries.project_id = 0;
    this.entries.module = '';
    this.entries.customer_id = 0;
    this.entries.start_dt = '';
    this.entries.end_dt = '';
    this.entries.nplist = [];
    this.entries.sections = {
        'entries':{'label':'', 'type':'simplegrid', 'num_cols':3,
            'headerValues':[''],
            'dataMaps':[''],
            },
        };
    this.entries.cellValue = function(s, i, j, d) {
        return d[this.sections[s].dataMaps[j]];
    }
    this.entries.rowFn = function(s, i, d) {
        return 'M.startApp(\'ciniki.timetracker.tracker\', null, \'M.ciniki_timetracker_main.entries.open();\', \'mc\', {\'entry_id\':' + d.id + '});';
    }
    this.entries.open = function(cb, pid, module, cid, start_dt, end_dt) {
        if( pid != null ) { this.project_id = pid; }
        if( module != null ) { this.module = module; }
        if( cid != null ) { this.customer_id = cid; }
        if( start_dt != null ) { this.start_dt = start_dt; }
        if( end_dt != null ) { this.end_dt = end_dt; }
        M.api.getJSONCb('ciniki.timetracker.entryList', {'tnid':M.curTenantID, 'project_id':this.project_id, 'module':this.module, 'customer_id':this.customer_id, 'start_dt':this.start_dt, 'end_dt':this.end_dt}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_timetracker_main.entries;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    }
    this.entries.addClose('Close');

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

        //
        // Create the app container
        //
        var ac = M.createContainer(ap, 'ciniki_timetracker_main', 'yes');
        if( ac == null ) {
            alert('App Error');
            return false;
        }

        if( M.modFlagOn('ciniki.timetracker', 0x03) ) {
            this.entries.sections.entries.num_cols = 6;
            this.entries.sections.entries.headerValues = ['Project', 'Module', 'Customer', 'Start', 'End', 'Notes'];
            this.entries.sections.entries.dataMaps = ['project_name', 'module', 'display_name', 'start_display', 'end_display', 'notes'];
        } else if( M.modFlagOn('ciniki.timetracker', 0x01) ) {
            this.entries.sections.entries.num_cols = 5;
            this.entries.sections.entries.headerValues = ['Project', 'Module', 'Start', 'End', 'Notes'];
            this.entries.sections.entries.dataMaps = ['project_name', 'module', 'start_display', 'end_display', 'notes'];
        } else if( M.modFlagOn('ciniki.timetracker', 0x02) ) {
            this.entries.sections.entries.num_cols = 5;
            this.entries.sections.entries.headerValues = ['Project', 'Customer', 'Start', 'End', 'Notes'];
            this.entries.sections.entries.dataMaps = ['project_name', 'display_name', 'start_display', 'end_display', 'notes'];
        } else {
            this.entries.sections.entries.num_cols = 4;
            this.entries.sections.entries.headerValues = ['Project', 'Start', 'End', 'Notes'];
            this.entries.sections.entries.dataMaps = ['project_name', 'start_display', 'end_display', 'notes'];
        }

        //
        // Reset dates
        //
        this.menu.start_dt = '';
        this.menu.end_dt = '';
        this.menu._tabs.selected = 'daily';

        this.menu.open(cb);
    }
}
