if (typeof TSS == "undefined"){TSS = {};}
if (typeof TSS.eCash == "undefined"){TSS.eCash = {};}

TSS.eCash.Config = 
{
	layout: null,
	loan_types_url: null,
	loan_type_tree: null,
	company_tree: null,
	company_id: 1,
	loan_type_store: null,
	business_rules_config: null,
	conditions_button: null,
	actions_dropdown: null,
	else_dropdown: null,
	inner_center_layout: null,
	stop_signs: null,
	status_tree_data: null,
	duplicate_loan_id: null,
	add_loan_button: null,

	CompanyRecord: Ext.data.Record.create([
	{name: 'name'},
	{name: 'name_short'},
	{name: 'company_id'}])
	,
	/**
	 * Loan Type record class
	 */
	LoanTypeRecord: Ext.data.Record.create([
		{name: 'name'},
		{name: 'short_name'},
		{name: 'created'},
		{name: 'created_by'},
		{name: 'id'},
		{name: 'unique_id'},
		{name: 'min_age'},
		{name: 'max_loans'},
		{name: 'states'},
		{name: 'rules'},
		{name: 'rule_set_id'},
		{name: 'company_id'}
	])
	,
	/**
	 * Call when the dom has loaded
	 */
	init: function()
	{
		Ext.Msg.wait('Please Wait', 'Loading...');

		Ext.Ajax.request({url: 'json.php?action=getBusinessRuleConfig', callback: this.handleBusinessRulesResponse, scope: this});

		this.initLayout();

		// Setup loan type data store		
		var reader = new Ext.data.JsonReader({
			id: 'unique_id',
			root: 'rows'
		}, this.LoanTypeRecord);

		this.loan_type_store = new Ext.data.Store({reader: reader, proxy: new Ext.data.MemoryProxy()});

		this.loan_type_store.on('update', this.handelLoanTypeUpdate, this);

	//	this.initLoanTypeView();
		this.initCompanyView();
	}
	,
	getActiveTab: function()
	{
		return this.layout.getRegion('center').getActivePanel();
	}
	,
	adjustBottom: function(el)
	{
		el.setHeight(Ext.get(document.body).getViewSize().height - el.getTop() );
	}
	,
	/**
	 * Setup the basic layout and toolbar
	 */
	initLayout: function()
	{
		var container = Ext.get('container');
		this.adjustBottom(container);
		Ext.EventManager.onWindowResize(this.adjustBottom.createDelegate(this, [container]));

		this.layout = new Ext.BorderLayout(container, {
			west: {
				split:true,
				initialSize: 200,
				minSize: 50,
				maxSize: 400,
				titlebar: false,
				collapsible: true
			},
			center: {
				titlebar: false,
				autoScroll:true,
				resizeTabs: true,
				minTabWidth: 50,
				closeOnTab: true,
				alwaysShowTabs: true
			}
		});

		this.layout.beginUpdate();
		this.layout.on('regionresized', function(){Ext.EventManager.fireResize();}); // Will force sub layouts to resize
		
		toolbar = new Ext.Toolbar('company_tb');
		toolbar.add('Companies');
		toolbar.addFill();

		
		this.layout.add('west', new Ext.ContentPanel('west', {title: 'Companies',toolbar: toolbar}));
		this.layout.endUpdate();
		
		this.layout.beginUpdate();
		
		toolbar = new Ext.Toolbar('loan_type_tb');
		toolbar.add('Loan Types');
		toolbar.addFill();
		this.add_loan_button = toolbar.addButton(
				{
					cls: 'x-btn-text-icon add_icon',
					text: 'Copy',
					handler: this.handleAddLoanClick,
					scope: this
				}
		);
		this.add_loan_button.disable();
		this.add_loan_button.getEl().dom.tabIndex = 1;
				
	
		this.layout.add('west', new Ext.ContentPanel('west', {title: 'Loan Types',toolbar: toolbar}));

		this.layout.endUpdate();
	}
	,
	handleAddLoanClick: function()
	{
		Ext.Msg.wait('Loading loan data, please wait.', 'Loading...');
		Ext.Ajax.request({url: 'json.php?action=getLoanTypesForCopy&company_id=' + this.company_id, callback: this.showAddLoan, scope: this});	
	}
	,
	showAddLoan: function(options, success, response)
	{
		try
		{
			//alert(response);
			Ext.Msg.hide();
			var msg = Ext.MessageBox.prompt('Name', 'Please enter a name:', this.onAddNewLoanType, this);
			var store = new Ext.data.SimpleStore({fields: ['key','name'], data: eval(response.responseText)});
	
			var combo = new Ext.form.ComboBox({
				store: store,
				displayField:'name',
				valueField: 'key',
				typeAhead: true,
				mode: 'local',
				triggerAction: 'all',
				emptyText:'Select a loan type to duplicate...',
				selectOnFocus:true,
				resizable:true,
				value: this.duplicate_loan_id,
				width: 300
			});
			combo.on('select', this.handleCopyLoanChange, this);
			combo.render(msg.getDialog().getEl().child('.ext-mb-text'));
			msg.getDialog().resizeTo(325,200);
		}
		catch(err)
		{
			Ext.Msg.hide();
			Ext.Msg.alert("Error on load","Error while trying to run the response - " + err + "\nPlease fix the error and try again.\nResponse: " + response.responseText);
		}
	}
	,
	handleCopyLoanChange: function(el, record, index)
	{
		this.duplicate_loan_id = el.getValue();
	}
	,
	handleBusinessRulesResponse: function(options, success, response)
	{
		if (success)
		{
			try {
				this.business_rules_config = eval("("+response.responseText+")");
				Ext.Msg.hide();
			} 
			catch(err) {
				Ext.Msg.hide();
				Ext.Msg.alert("Error on load","Error while trying to run the response - " + err + "\nPlease fix the error and try again.\nResponse: " + response.responseText);
			}
		}
	}
	,
	/**
	 * Listens to the loan type data source and takes global actions
	 * @param {Object} store
	 * @param {Object} record
	 * @param {Object} action
	 */
	handelLoanTypeUpdate: function(store, record, action)
	{
		var panel;

		panel = this.layout.getRegion('center').getActivePanel();
		
		if ('edit' == action)
		{
			if ('*' != panel.getTitle().substr(0,1))
			{
				panel.setTitle('*'+ panel.getTitle());
			}
		}
		else if ('commit'== action)
		{
			if ('*' == panel.getTitle().substr(0,1))
			{
				panel.setTitle(panel.getTitle().substr(1));
			}
		}
	}
	,
	/**
	 * Handeler for the add loan type prompt
	 * @param {Object} btn
	 * @param {Object} text
	 * @param {Object} tree
	 */
	onAddNewLoanType: function(btn, text, tree)
	{
		if ('ok' == btn)
		{
			Ext.Ajax.request({url: 'json.php?action=copyLoanType&params='+this.duplicate_loan_id+','+text+','+this.company_id, callback: this.updateCompany, scope: this});

		}
	}
	,
	initCompanyView: function()
	{
		
		var i, p, root, tree, node, version_node;

		tree = new Ext.tree.TreePanel('company_view', {
				animate:true,
				containerScroll: true,
				rootVisible:false,
				loader: new Ext.tree.TreeLoader({ dataUrl:'json.php?action=getCompanies' })
		});
		tree.on('click', this.onCompanyClick, this);

		root = new Ext.tree.AsyncTreeNode({text: 'Companies', id:'0'});

		tree.setRootNode(root);
		
		this.company_tree = tree;

		tree.render();
		root.expand();
	},
	onStatusWidgetChange: function(el, record, index)
	{
		var value = el.getValue();
		var target_id = el.getId().replace("status","") + '_op2';
		Ext.get(target_id).dom.value = value;
	}
	,
	showStatusWidget: function(el, record, index)
	{
		if(el.getValue() != 'application_status')
		{
			return;
		}
		var box = Ext.Msg.prompt("Please select a status: ", "Select a status");
		box.getDialog().resizeTo(500,200)
		var data = [];
		var value = '*root';
		data = this.buildStatusTree(data, value, this.business_rules_config.application_statuses, 0);
		

		var store = new Ext.data.SimpleStore({fields: ['key','name'], data: data});
		var base_value = Ext.get(el.getId() + "_op2").dom.value;

		var combo = new Ext.form.ComboBox({
			store: store,
			displayField:'name',
			valueField: 'key',
			typeAhead: true,
			mode: 'local',
			triggerAction: 'all',
			emptyText:'Add action...',
			selectOnFocus:true,
			resizable:true,
			value: base_value? base_value : data[0][0],
			width: 400
		});
		combo.id = el.getId() + 'status';
		box.getDialog().getEl().child('.ext-mb-input').hide();
		combo.render(box.getDialog().getEl().child('.ext-mb-text'));
		this.status_widget_combobox = combo;
		combo.on('select', this.onStatusWidgetChange, this);
	}
	,
	buildStatusTree: function(data, value, status_array, depth)
	{
		for(name_short in status_array)
		{
			if('object' != typeof status_array[name_short] || 'string' != typeof status_array[name_short].name)
			{
				continue;
			}
			var display = status_array[name_short].name + " (" + name_short + ")";
			for(var i = 0; i < depth; ++i)
			{
				display = ' - ' + display;
			}
			data[data.length] = [name_short + '::' + value, display];
			data = this.buildStatusTree(data, name_short + '::' + value, status_array[name_short].branches, depth + 1);
		}
		return data;
	}
	,
	onCompanyClick: function(node, event)
	{
		this.company_id = node.attributes.company_id;
		this.loan_types_url = 'json.php?action=getLoanTypeForTree&params='+this.company_id;
		this.add_loan_button.enable();
		this.updateCompany();
	}
	,
	updateCompany: function()
	{
		if(this.loan_type_tree == null)
		{
			this.initLoanTypeView(this.company_id);
		}
		else
		{
			this.loan_type_tree.getLoader().dataUrl = this.loan_types_url;
			this.loan_type_tree.getLoader().load(this.loan_type_tree.getRootNode());			
		}
	},
	/**
	 * Gets the loan type toolbar and tree setup
	 */
	initLoanTypeView: function()
	{
		var i, p, root, tree, node, version_node;
		
		treeLoader = new Ext.tree.TreeLoader({ dataUrl:this.loan_types_url,clearOnLoad:true});
				
		tree = new Ext.tree.TreePanel('loan_type_view', {
				animate:true,
				containerScroll: true,
				rootVisible:false,
				loader: treeLoader
		});
		tree.on('click', this.onLoanTypeClick, this);
		
		root = new Ext.tree.AsyncTreeNode({text: 'Loan Types', id:'0'});
		
		tree.setRootNode(root);
		this.loan_type_tree = tree;

		tree.render();
		root.expand();
	}
	,
	/**
	 * Handler for clicks on the loan type tree 
	 * @param {Object} node
	 * @param {Object} event
	 */
	onLoanTypeClick: function(node, event)
	{
		this.fetchLoanType(node.id, node.attributes.rule_set_id, this.renderLoanType, this);
	}
	,
	/**
	 * Creates a view for a loan type 
	 * @param {Object} records
	 */
	renderLoanType: function(record)
	{
		var p, tab, tab_id, tab_title, tree_panel, tree, rules, rule_nodes, dh, panel_box;

		dh = Ext.DomHelper;
		
		tab_id = record.get('short_name') +'-'+ record.get('rule_set_id');

		tab = this.layout.getRegion('center').getPanel(tab_id);
		if ( 'undefined' == typeof tab)
		{
			tab_title = (record.dirty ? '*' : '') + record.get('name');
			if (tab_title.length > 10)
			{
				tab_title = tab_title.substr(0, 8) + '...';
			}
			tab_title += ' ('+ record.get('created').substr(5,5) +')';
			
			tab = this.layout.add('center', new Ext.ContentPanel(tab_id, {autoCreate:true, title: tab_title, closable: true}));
			tab.loan_type = record;

			panel_box = tab.getEl().createChild({tag: 'div', cls: 'rule_box'});

			this.renderInfoSection(panel_box);

			//this.renderSelectionSection(panel_box);
			//this.renderSelectedStates(state_panel, record);
			//var state_panel = Ext.get(Ext.query('.state_fieldset', tab.getEl().dom)[0]).createChild({tag: 'div', cls: 'current_state_panel'});

			this.renderRuleSection(panel_box, tab);
		}
		else
		{
			this.layout.getRegion('center').showPanel(tab);
		}
	}
	,
	/**
	 * 
	 * @param {Object} target
	 * @param {Object} record
	 */
	renderInfoSection: function(target)
	{
		var panel, record;

		record = this.getActiveTab().loan_type;

		panel = target.createChild({tag: 'div', cls: 'rule_panel', children: [{tag: 'div', cls: 'white_box', children: [{tag: 'div', cls: 'selection_form_panel'}]}]});

		form = new Ext.form.Form({
			labelAlign: 'right',
			labelWidth: 100
		});
		
		form.add(
			new Ext.form.TextField({
				fieldLabel: 'Name',
				name: 'Name',
				width:150,
				value: record.get('name'),
				disabled: true
			}),
			new Ext.form.TextField({
				fieldLabel: 'Short Name',
				name: 'Name',
				width:150,
				value: record.get('name'),
				disabled: true
			}),
			new Ext.form.TextField({
				fieldLabel: 'Created',
				name: 'Created',
				width:150,
				value: record.get('created'),
				disabled: true
			})
		);
		form.addButton('Update', this.handelFormSave.createDelegate(this, [record])).tabIndex = 2;
		form.addButton('Create New Version', this.handelFormNew.createDelegate(this, [record])).tabIndex = 3;
		form.addButton('Delete', this.handelFormDelete.createDelegate(this, [record])).tabIndex = 4;
		form.end();
		form.render(panel.dom.firstChild.firstChild);
	}
	,
	handelFormSave: function(record)
	{
		Ext.Ajax.request(
		{
			url: 'json.php' ,
			callback: this.handleSaveResponse.createDelegate(this,[record]),
			method: 'POST', 
			params: 
			{
				action: 'saveLoanType', 
				json:Ext.util.JSON.encode(
				[
					record.get('name'),
					record.get('short_name'),
					record.get('id'),
					record.get('min_age'),
					record.get('max_loans'),
					record.get('states'),
					record.get('rules'),
					record.get('rule_set_id'),
					record.get('company_id')
				])
			}
		});
		
		record.commit();
	}
	,
	handelFormNew: function(record)
	{
		Ext.Ajax.request(
		{
			url: 'json.php' ,
			callback: this.handleNewResponse.createDelegate(this,[record]),
			method: 'POST', 
			params: 
			{
				action: 'saveLoanType', 
				json:Ext.util.JSON.encode(
				[
					record.get('name'),
					record.get('short_name'),
					record.get('id'),
					record.get('min_age'),
					record.get('max_loans'),
					record.get('states'),
					record.get('rules'),
					'new',
					record.get('company_id')
				])
			}
		});
		
		record.reject();
	}
	,
	handelFormDelete: function(record)
	{
		Ext.Ajax.request({
			url			: 'json.php' ,
			callback	: this.handleDeleteResponse,
			scope		: this, method: 'GET', params: {action: 'deleteLoanType', params: record.get('id') +','+ record.get('short_name') +','+ record.get('rule_set_id')}
		});
	}
	,
	handleSaveResponse: function(record)
	{
		if('new_' == record.get('id').substr(0, 4))
		{
			this.loan_type_tree.getLoader().load(this.loan_type_tree.getRootNode());
		}
	}
	,
	handleDeleteResponse: function()
	{
		this.loan_type_tree.getLoader().load(this.loan_type_tree.getRootNode());
	
		this.layout.getRegion("center").getActivePanel().getEl().hide(true);
		setTimeout(function(layout){layout.getRegion("center").remove(layout.getRegion("center").getActivePanel());}.createDelegate(this,[this.layout]), 500)
		//this.layout.getRegion("center").remove(this.layout.getRegion("center").getActivePanel());
	}
	,
	handleNewResponse: function()
	{
		this.loan_type_tree.getLoader().load(this.loan_type_tree.getRootNode());
	}
	,
	/**
	 * 
	 * @param {Object} target
	 * @param {Object} record
	 */
	renderSelectionSection: function(target)
	{
		var toolbar, panel;

		toolbar = new Ext.Toolbar(target.createChild({tag: 'div'}));
		toolbar.add('Selection Parameters');

		panel = target.createChild({tag: 'div', cls: 'rule_panel', children: [{tag: 'div', cls: 'white_box', children: [{tag: 'div', cls: 'selection_form_panel'}]}]});

		this.renderSelectionForm(panel.dom.firstChild.firstChild);
	}
	,
	/**
	 * 
	 * @param {Object} target
	 * @param {Object} record
	 */
	renderSelectionForm: function(target)
	{
		var form, combo, record;
		
		record = 
		record = this.getActiveTab().loan_type;

		form = new Ext.form.Form({
			labelAlign: 'right',
			labelWidth: 65
		});

		form.column({width:200, labelWidth:130});
		form.add(
			new Ext.form.NumberField({
				fieldLabel: 'Minimum Age',
				name: 'min_age',
				width:65,
				allowBlank:false,
				value: record.get('min_age')
			}),

			new Ext.form.NumberField({
				fieldLabel: 'Max Concurrent Loans',
				name: 'max_loans',
				width:65,
				value: record.get('max_loans')
			})
		);
		form.end();

		form.column( {width:190, clear:true,labelAlign: 'top'} );
		
		combo = new Ext.form.ComboBox(
		{
			store: new Ext.data.SimpleStore({
				fields: ['abbr', 'state'],
			data : this.states
			}),
			displayField:'state',
			typeAhead: true,
			mode: 'local',
			triggerAction: 'all',
			emptyText:'Add a state...',
			selectOnFocus:true,
			width:150,
			resizable:true
		});
		combo.on('select', this.handleAddState.createDelegate(this, record, true), this);

		form.fieldset({cls: 'state_fieldset', legend:'States', style:'margin-left:20px', hideLabels:true}, combo);
		form.end();

		form.render(target);
	}
	,
	/** 
	 * 
	 */
	renderRuleSection: function(target, tab)
	{
		var tb = new Ext.Toolbar(target.createChild({tag: 'div'}));
		tb.add('Rule Set');

		layout = this.initRulesetLayout(target.createChild({tag: 'div'}).createChild({tag: 'div', cls: 'rule_layout'}));

		layout.beginUpdate();

		layout.add('west', this.initEventPanel(layout.getEl().createChild({tag: 'div'}), tab));

		layout.add('center',this.initRulesLayout(layout.getEl().createChild({tag: 'div'}), tab));

		layout.endUpdate();
	}
	,
	/**
	 * 
	 * @param {Object} panel
	 */
	initRulesetLayout: function(target)
	{
		return new Ext.BorderLayout(target, {
			west: {
				split:true,
				initialSize: 120,
				minSize: 50,
				maxSize: 400,
				collapsible: true,
				titlebar: true
			},
			center: {
				autoScroll: false
			}
		});
	}
	,
	/**
	 * 
	 * @param {Object} panel
	 */
	initEventPanel: function(target, tab)
	{
		tab.event_view = new Ext.View(target.createChild({tag: 'div', cls: 'view_basic'}),
		'<div>{name}</div>', // auto create template
		{
			singleSelect: true,
			selectedClass: "light_blue_bg",
			store: new Ext.data.SimpleStore({fields: ['key','name']})
		});

		this.updateEventsView(tab.loan_type);

		tab.event_view.on("click", this.handelEventClick.createDelegate(this, [tab.loan_type], true));

		return new Ext.ContentPanel(target, {title: 'Events'});
	}
	,
	updateEventsView: function(record, selection)
	{
		var event, events, event_data;
		events = this.findAllEventsInRules(record.get('rules'));
		// simple array store
		event_data = [];
		for(i = 0; i < events.length; i++)
		{
			event = this.business_rules_config.events[events[i]];
			event_data[event_data.length] = [event.key, event.name];
		}
		this.getActiveTab().event_view.store.loadData(event_data);
	}
	,
	findAllEventsInRules: function(rules)
	{
		var events = [], i;
		var index = {};

		for(i = 0; i < rules.length; i++)
		{
			if ('undefined' == typeof index[rules[i].event])
			{
				events[events.length] = rules[i].event;
				index[rules[i].event] = true;
			}
		}
		return events;
	}
	,
	handelEventClick: function(vw, index, node, e, record, layout)
	{
		var event_key, rule_ids;

		event_key = vw.store.getAt(index).get('key');

		this.updateRulesView(event_key);
		this.updateActionsView([]);
		this.updateElseActionsView([]);
		this.updateConditionsView([]);
	}
	,
	initRulesView: function(target, record)
	{
		target.addClass('view_basic');
		var store = new Ext.data.SimpleStore({fields: ['rule', 'salience']});
		store.sort('salience');
		var view = new Ext.View(target,
		'<div></div>', // auto create template
		{
			singleSelect: true,
			selectedClass: "light_blue_bg",
			store: store
		});

		view.on("click",this.handelRuleViewClick.createDelegate(this, [record], true));
		
		return view;
	}
	,
	updateRulesView: function(event_key)
	{
		var i, rules_array, selected_rules; 

		rules_array = this.getActiveTab().loan_type.get('rules');
		selected_rules = [];
		for(i = 0; i < rules_array.length; i++)
		{
			if ( event_key == rules_array[i].event)
			{
				selected_rules[selected_rules.length] = [rules_array[i], rules_array[i].salience];
			}
		}

		this.getActiveTab().rules_view.store.removeAll();
		this.getActiveTab().rules_view.store.loadData(selected_rules);

		this.postUpdateRulesView(this.getActiveTab().rules_view);
	}
	,
	postUpdateRulesView: function(view)
	{
		var i, j, node, rule_record, combo_data, row, combo, dd, node_offset, drop_target;
		this.stop_signs = {};
		
		// simple array store
		combo_data = [];
		if (null !== this.business_rules_config)
		{
			for(p in this.business_rules_config.events)
			{
				combo_data[combo_data.length] = [this.business_rules_config.events[p].key,this.business_rules_config.events[p].name];
			}
		}
		
		i = 0;
		while((node = Ext.get(view.getNode(i))))
		{
			rule = view.store.getAt(i).get('rule');

			drop_target = new TSS.eCash.Config.DropTargetRule(node.createChild({tag: 'div', cls: 'drop_target'}), {overClass: 'drop_target_over', index: i, app: this});
			drop_target.addToGroup('rules');
			drop_target.setPadding(20);

/*
			
			row = node.createChild({tag: 'table', width: '250px'}).createChild({tag: 'tbody'}).createChild({tag: 'tr'});

			row.createChild({tag: 'th', html: rule.name});

			combo = new Ext.form.ComboBox({
				store: new Ext.data.SimpleStore({fields: ['key', 'name'], data: combo_data, id: 0}),
				displayField:'name',
				typeAhead: true,
				mode: 'local',
				triggerAction: 'all',
				emptyText:'Select event...',
				selectOnFocus:true,
				resizable:true,
				width: 100
			});
			combo.setValue(combo.store.getById(rule.event).get('name'));
			combo.on('select', this.handelChangeRuleEvent, this);
			combo.render(row.createChild({tag: 'td'}).createChild({tag: 'div'}));

			new Ext.Button(row.createChild({tag: 'td'}).createChild({tag: 'div'}), {text: 'Delete', handler: this.handelRemoveRule.createDelegate(this, [rule])});
//*/
//*
			//row = node.createChild({tag: 'table', width: '250px'}).createChild({tag: 'tbody'}).createChild({tag: 'tr'});
			row = node.createChild({tag: 'div'});
			rule.name = rule.name + ' ';
			var sign = row.createChild({tag: 'span', html: rule.name}).createChild({tag: 'img', src:'images/Stop_sign.png', height:15, width:15});
			sign.hide();
			sign.id = 'stop_sign_' + rule.event + rule.salience;
			this.stop_signs['stop_sign_' + rule.event + rule.salience] = sign;
			
			this.updateRuleBreakImage(i);

			combo = new Ext.form.ComboBox({
				store: new Ext.data.SimpleStore({fields: ['key', 'name'], data: combo_data, id: 0}),
				displayField:'name',
				typeAhead: true,
				mode: 'local',
				triggerAction: 'all',
				emptyText:'Select event...',
				selectOnFocus:true,
				resizable:true,
				width: 200
			});
			combo.setValue(combo.store.getById(rule.event).get('name'));
			combo.on('select', this.handelChangeRuleEvent, this);
			combo.render(row.createChild({tag: 'span'}).createChild({tag: 'div'}));

			new Ext.Button(row.createChild({tag: 'div'}), {text: 'Delete', handler: this.handelRemoveRule.createDelegate(this, [rule])});
//*/
			i++;
		}

		// Add drags
		i = 0;
		node_offset = Ext.get(view.getNode(0)).getHeight() / 2;
		while((node = Ext.get(view.getNode(i))))
		{
			rule = view.store.getAt(i).get('rule');

			dd = new Ext.dd.DragSource(node.dom.lastChild,{groups: 'rules', centerFrame: true, dragData: {rule: rule, loan_type: this.getActiveTab().loan_type}});
			dd.setXConstraint(0,0);
			dd.setYConstraint(node.getTop() - view.getEl().getTop() + node_offset,(view.getEl().getBottom() - node.getBottom()) + (node_offset * 3));

			i++;
		}

		// Bottom target
		drop_target = new TSS.eCash.Config.DropTargetRule(view.getEl().createChild({tag: 'div', cls: 'drop_target'}), {overClass: 'drop_target_over', index: 'end', app: this});
		drop_target.addToGroup('rules');
		drop_target.setPadding(20);
		
	}
	,
	updateRuleBreakImage: function(i)
	{
		if('undefined' == typeof i)
		{
			var rule = this.getSelectedRule();
		} else {
			var rule = this.getActiveTab().rules_view.store.getAt(i).get('rule');
		}
		var image = this.stop_signs['stop_sign_' + rule.event + rule.salience];
		var actions_array = this.convertToArray(rule.actions);
		for(var a = 0; a < actions_array.length; a++)
		{
			if(this.business_rules_config.actions[actions_array[a][0]].name == 'Break')
			{
				image.show();
				return;
			}
		}
		image.hide();
	}
	,
	handelChangeRuleEvent: function(combo, record, index)
	{
		this.getSelectedRule().event = record.get('key');
		this.applyRuleChanges();
		this.updateEventsView(this.getActiveTab().loan_type);
	}
	,
	handelRuleViewClick: function(vw, index, node, e, loan_type_record, layout)
	{
		var rule_id, condition_id, rule;
		
		if ('BUTTON' == e.getTarget().tagName)
		{
			if ( 'rulecondel_' == e.getTarget().id.substr(0,11))
			{
				this.removeCondition(e.getTarget().id.substr(11), loan_type_record, layout);
			}
		}
		else
		{
			rule = vw.store.getAt(index).get('rule');

			this.updateActionsView(rule.actions);
			this.updateElseActionsView(rule.else_actions);
			this.updateConditionsView(rule.conditions);
		}

		var layout = this.inner_center_layout.getLayout();
		layout.getRegion('west').resizeTo('33%');
		layout.getRegion('east').resizeTo('33%');
	}
	,
	removeCondition: function(condition_id, record, layout)
	{
		var p, i, rules, a_copy, match;
		
		rules = record.get('rules');
		
		for( p in rules)
		{
			a_copy = [];
			match = false;
			for(i = 0; i < rules[p].conditions.length; i++)
			{
				if(condition_id == rules[p].conditions[i])
				{
					match = true;
				}
				else
				{
					a_copy[a_copy.length] = rules[p].conditions[i];
				}
			}

			if(match)
			{
				rules[p].conditions = a_copy;
				record.set('rules', this.shallowCopy(rules));

				this.updateRulesView(record.get('events')[rules[p].event], record, this.getActiveTab().rules_view);

				break;	
			}
		}
	}
	,
	/**
	 * 
	 * @param {Object} nesteted_container_el
	 */
	initRulesLayout: function(target, tab)
	{
		var layout = new Ext.BorderLayout(target, {
			center: {
				split:true,
				autoScroll:true
			}
			,
			south: {
				initialSize: 225,
				split:true
			}
		});

		layout.beginUpdate();
		
		layout.add('center', this.initRulesPanel(target, tab));

		layout.add('south', this.initRulePartsLayout(target.createChild({tag: 'div'}), tab));

		layout.endUpdate();

		return new Ext.NestedLayoutPanel(layout);
	}
	,
	initRulePartsLayout: function(target, tab)
	{
		var innerCenterLayout = new Ext.BorderLayout(target, {
			west: {
				split:true,
				autoScroll:true,
				initialSize:'28%',
			}
			,
			center: {
				autoScroll:true,
				initialSize: '28%',
				split:true
			}
			,
			east: {
				split:true,
				initialSize: '28%',
				autoScroll:true
			}
		});

		innerCenterLayout.add('west', this.initConditionPanel(target));
		innerCenterLayout.add('center', this.initActionPanel(target));
		innerCenterLayout.add('east', this.initElseActionPanel(target));

		this.inner_center_layout = new Ext.NestedLayoutPanel(innerCenterLayout);
		return this.inner_center_layout;
	}
	,
	/**
	 * 
	 * @param {Object} nesteted_container_el
	 */
	initRulesPanel: function(target, tab)
	{
		var inner_center_el, tb, combo, combo_data, P;

		panel_el = target.createChild({tag: 'div'});
		
		tb = new Ext.Toolbar(panel_el.createChild({tag: 'div'}));
		tb.add('Business Rules');
		tb.addFill();
		
		var name_field = new Ext.form.TextField({width: 65, grow: true, growMin: 65,	growMax: 140, emptyText: "Name..."});
		tb.addField(name_field);
		name_field.getEl().dom.tabIndex = 5;

		combo_data = [];
		for(p in this.business_rules_config.events)
		{
			combo_data[combo_data.length] = [this.business_rules_config.events[p].key, this.business_rules_config.events[p].name];
		}
		
		tb.addSpacer();

		combo = new Ext.form.ComboBox({
			store: new Ext.data.SimpleStore({fields: ['key', 'name'], data: combo_data, id: 0}),
			displayField:'name',
			valueField: 'key',
			typeAhead: true,
			mode: 'local',
			triggerAction: 'all',
			emptyText:'Select event...',
			selectOnFocus:true,
			resizable:true,
			//width: 100,
			selectedIndex: 1,
			value: combo_data[0][0]
		});
		tb.add(combo);
		combo.getEl().dom.tabIndex = 6;
		
		tb.addButton({cls: 'x-btn-text-icon add_icon', text: 'Add', handler : this.hanelAddRule.createDelegate(this, [name_field, combo])}).getEl().dom.tabIndex = 7;
		
		tab.rules_view = this.initRulesView(panel_el.createChild({tag: 'div'}));

		return new Ext.ContentPanel(panel_el, {toolbar: tb });
	}
	,
	/**
	 * 
	 * @param {Object} nesteted_container_el
	 */
	initActionPanel: function(target)
	{
		var inner_south_el, tb, data, combo;

		inner_south_el = target.createChild({tag: 'div'});
		tb = new Ext.Toolbar(inner_south_el.createChild({tag: 'div'}));
		tb.add('Actions');
		tb.addFill();

		data = [];
		if (null !== this.business_rules_config)
		{
			for(p in this.business_rules_config.actions)
			{
				data[data.length] = [p, this.business_rules_config.actions[p].name + (this.business_rules_config.actions[p].is_ecash_only ? ' (ecash)' : '')];
			}
		}

		var store = new Ext.data.SimpleStore({fields: ['key','name'], data: data});

		combo = new Ext.form.ComboBox({
			store: store,
			displayField:'name',
			valueField: 'key',
			typeAhead: true,
			mode: 'local',
			triggerAction: 'all',
			emptyText:'Add action...',
			selectOnFocus:true,
			resizable:true,
			value: data[0][0],
			//width: 113
		});
		combo.on('select', this.handleAddAction, this);
		
		this.actions_dropdown = tb.addField(combo);
		this.actions_dropdown.disable();
		combo.getEl().dom.tabIndex = 9;
		
		this.initActionsView(inner_south_el.createChild({tag: 'div'}));
		
		return new Ext.ContentPanel(inner_south_el, {toolbar: tb });
	}
	,
	initActionsView: function(target)
	{
		target.addClass('view_basic');
		var action_store = new Ext.data.SimpleStore({fields: ['action', 'params', 'sequence_no', 'ref']});
		action_store.sort('sequence_no');
		var view = new Ext.View(target,
		'<div></div>',
		{
			singleSelect: true,
			selectedClass: "light_blue_bg",
			store: action_store
		});
		view.on('click', this.handleActionClick, this);

		this.getActiveTab().action_view = view;
	}
	,
	updateActionsView: function(actions, select)
	{
		var i, view, node, form, action_types, action_type_params, label, value;
		
		if(this.getSelectedRuleIndex()) 
		{
			this.actions_dropdown.enable();
		} else {
			this.actions_dropdown.disable();
		}

		action_types = this.business_rules_config.actions;
		
		view = this.getActiveTab().action_view;

		view.store.removeAll();
		view.store.loadData(this.convertToArray(actions));

		i = 0;
		while((node = Ext.get(view.getNode(i))))
		{
			drop_target = new TSS.eCash.Config.DropTargetAction(node.createChild({tag: 'div', cls: 'drop_target'}), {overClass: 'drop_target_over', index: i, app: this});
			drop_target.addToGroup('actions');
			drop_target.setPadding(20);

			action = view.store.getAt(i);

			label = node.createChild({tag: 'div', cls: 'action_label', children: [{tag: 'div', cls: 'action_title', html: action_types[action.get('action')].name}]});
			label.setStyle('overflow','hidden');

			action_type_params = action_types[action.get('action')].params;

			for(j = 0; j < action_type_params.length; j++)
			{
				value = typeof action.get('params')[action_type_params[j].name] ? action.get('params')[action_type_params[j].name] : '';
				label.createChild({tag: 'div', cls: 'action_param', html: action_type_params[j].name +': '+ value});
			}
			i++;
		}
		
		// Add drags
		if ('undefined' != typeof view.getNode(0))
		{
			node_offset = Ext.get(view.getNode(0)).getHeight() / 2;
		}
		i = 0;
		while((node = Ext.get(view.getNode(i))))
		{
			action = view.store.getAt(i);

			dd = new Ext.dd.DragSource(node.dom.lastChild,{groups: 'actions', centerFrame: true, dragData: action.get('ref')});
			dd.setXConstraint(0,0);
			dd.setYConstraint(node.getTop() - view.getEl().getTop() + node_offset,(view.getEl().getBottom() - node.getBottom()) + (node_offset * 3));

			i++;
		}

		if ('undefined' != typeof select)
		{
			var evObj = document.createEvent('MouseEvents');
			evObj.initMouseEvent( 'click', true, true, window, 1, 12, 345, 7, 220, false, false, true, false, 0, null );
			view.getNode(--i).dispatchEvent(evObj);
		}

		// Bottom target
		if(Ext.get(view.getNode(0)))
		{
			drop_target = new TSS.eCash.Config.DropTargetAction(view.getEl().createChild({tag: 'div', cls: 'drop_target'}), {overClass: 'drop_target_over', index: 'end', app: this});
			drop_target.addToGroup('actions');
			drop_target.setPadding(20);
		}
		
	}
	,
	deselectActions: function(view)
	{
		if('undefined' == typeof view)
		{
			var view = this.getActiveTab().action_view;
			view.select(new Array());
		}
		view.getEl().select('.action_form{display!=none}').setDisplayed('none');
		view.getEl().select('.action_label{display=none}').setDisplayed('block');
		
	},
	handleActionClick: function(view, index, node, event)
	{
		var action, form_el, action_type_params;
		node = Ext.get(node);
		//this.updateConditionsView();
		this.deselectConditions();
		this.deselectElseActions();

		var layout = this.inner_center_layout.getLayout();
		layout.getRegion('east').resizeTo('25%');
		layout.getRegion('west').resizeTo('25%');
		
		if(node.select('.action_form').elements.length < 1)
		{
			this.deselectActions(view);
			
			node.select('.action_label{display!=none}').setDisplayed('none');
	
			action = view.store.getAt(index);
			action_types = this.business_rules_config.actions;
			action_type_params = action_types[action.get('action')].params;
			
			form = new Ext.form.Form({labelWidth: 150, labelAlign: 'right'});
			form.fieldset({legend:action_types[action.get('action')].name});
			
			var new_element;
			for(j = 0; j < action_type_params.length; j++)
			{
				if(action_type_params[j].reference_data.length) {
					var store = new Ext.data.SimpleStore({fields: ['key','name','company_id'], data: this.filterByCompany(action_type_params[j].reference_data)});
					new_element = new Ext.form.ComboBox({
						fieldLabel: action_type_params[j].name,
						name: action_type_params[j].name,
						allowBlank:false,
						store: store,
						displayField:'name',
						valueField: 'key',
						typeAhead: true,
						mode: 'local',
						triggerAction: 'all',
						emptyText:'Add action...',
						selectOnFocus:true,
						resizable:true,
						value: action.get('params')[action_type_params[j].name] || action_type_params[j].reference_data[0][0]
					});
				} else {
					new_element = new Ext.form.TextField({
						fieldLabel: action_type_params[j].name,
						name: action_type_params[j].name,
						//width:50,
						allowBlank:false,
						value: 'undefined' != typeof action.get('params')[action_type_params[j].name] ? action.get('params')[action_type_params[j].name] : ''
				 	});
				}
				form.add(new_element);
			}
			form.addButton({text:'Update', minWidth: 50}, this.hanelSaveAction.createDelegate(this, [form, action]));
			form.addButton({text:'Delete', minWidth: 50}, this.hanelRemoveAction.createDelegate(this, [action]));
			form.end();
			form_el = node.createChild({tag: 'div', cls: 'action_form'});
			form.render(form_el);
			form_el.show(true);
		}
		else if(node.select('.action_form{display=none}').elements.length > 0)
		{
			this.deselectActions(view);
	
			node.select('.action_label{display!=none}').setDisplayed('none');
			node.select('.action_form{display=none}').show(true);
			node.select('.action_form{display=none}').setDisplayed('block');
		}
	}
	,
	filterByCompany: function(data)
	{
		var retval = new Array();
		for(var i = 0; i < data.length; ++i)
		{
			if(data[i][2] < 1 || data[i][2] == this.company_id)
			{
				retval[retval.length] = data[i];
			}
		}
		return retval;
	}
	,
	handleAddAction: function(combo, record, index)
	{
		var rule, action, empty_params, sequence_no;

		if('undefined' == typeof (rule = this.getSelectedRule()))
		{
			Ext.Msg.alert('Add Action Error', 'No Rule Selected');
		}
		else
		{	
			action = this.business_rules_config.actions[record.get('key')];
			
			empty_params = {};
			for(i = 0; i < action.params.length; i++)
			{
				empty_params[action.params[i].name] = '';
			}

			sequence_no = 0;
			if ('undefined' != typeof rule.actions[(rule.actions.length -1)])
			{
				sequence_no = rule.actions[(rule.actions.length -1)].sequence_no + 1;
			}

			rule.actions[rule.actions.length] = {action: record.get('key'), params: empty_params, sequence_no: sequence_no};
			this.applyRuleChanges();
			this.updateActionsView(rule.actions, true);
		}
		this.updateRuleBreakImage();
	}
	,
	hanelSaveAction: function(form, action)
	{
		action.get('ref').params = this.getValues(form);
		this.applyRuleChanges();
		this.updateActionsView(this.getSelectedRule().actions);
	}
	,
	getValues: function(form)
	{
		var values = form.getValues();
		for(i in values)
		{
			values[i] = form.findField(i).getValue();
		}
		return values;
	}
	,
	hanelRemoveAction: function(action)
	{
		var rule, i, a_copy;
		
		rule = this.getSelectedRule();
		a_copy = [];
		
		for(i = 0; i < rule.actions.length; i++)
		{
			if (rule.actions[i] != action.get('ref'))
			{
				a_copy[a_copy.length]  = rule.actions[i];
			}
		}
		rule.actions = a_copy;
		this.applyRuleChanges();
		this.updateActionsView(rule.actions);
		this.updateRuleBreakImage();
	}
	,
	/**
	 * 
	 * @param {Object} nesteted_container_el
	 */
	initElseActionPanel: function(target)
	{
		var inner_south_el, tb, data, combo;

		inner_south_el = target.createChild({tag: 'div'});
		tb = new Ext.Toolbar(inner_south_el.createChild({tag: 'div'}));
		tb.add('Else Actions');
		tb.addFill();

		data = [];
		if (null !== this.business_rules_config)
		{
			for(p in this.business_rules_config.actions)
			{
				data[data.length] = [p, this.business_rules_config.actions[p].name + (this.business_rules_config.actions[p].is_ecash_only ? ' (ecash)' : '')];
			}
		}

		var store = new Ext.data.SimpleStore({fields: ['key','name'], data: data});

		combo = new Ext.form.ComboBox({
			store: store,
			displayField:'name',
			valueField: 'key',
			typeAhead: true,
			mode: 'local',
			triggerAction: 'all',
			emptyText:'Add Else action...',
			selectOnFocus:true,
			resizable:true,
			value: data[0][0],
			//width: 113
		});
		combo.on('select', this.handleAddElseAction, this);
		this.else_dropdown = tb.addField(combo);
		this.else_dropdown.disable();
		combo.getEl().dom.tabIndex = 10;
		
		this.initElseActionsView(inner_south_el.createChild({tag: 'div'}));
		
		return new Ext.ContentPanel(inner_south_el, {toolbar: tb });
	}
	,
	initElseActionsView: function(target)
	{
		target.addClass('view_basic');
		var action_store = new Ext.data.SimpleStore({fields: ['action', 'params', 'sequence_no', 'ref']});
		action_store.sort('sequence_no');
		var view = new Ext.View(target,
		'<div></div>',
		{
			singleSelect: true,
			selectedClass: "light_blue_bg",
			store: action_store
		});
		view.on('click', this.handleElseActionClick, this);

		this.getActiveTab().else_action_view = view;
	}
	,
	updateElseActionsView: function(actions, select)
	{
		var i, view, node, form, action_types, action_type_params, label, value;
		if(this.getSelectedRuleIndex()) 
		{
			this.else_dropdown.enable();
		} else {
			this.else_dropdown.disable();
		}
		
		action_types = this.business_rules_config.actions;
		
		view = this.getActiveTab().else_action_view;

		view.store.removeAll();
		view.store.loadData(this.convertToArray(actions));

		i = 0;
		while((node = Ext.get(view.getNode(i))))
		{
			drop_target = new TSS.eCash.Config.DropTargetElseAction(node.createChild({tag: 'div', cls: 'drop_target'}), {overClass: 'drop_target_over', index: i, app: this});
			drop_target.addToGroup('actions');
			drop_target.setPadding(20);

			action = view.store.getAt(i);

			label = node.createChild({tag: 'div', cls: 'action_label', children: [{tag: 'div', cls: 'action_title', html: action_types[action.get('action')].name}]});
			label.setStyle('overflow','hidden');

			action_type_params = action_types[action.get('action')].params;

			for(j = 0; j < action_type_params.length; j++)
			{
				value = typeof action.get('params')[action_type_params[j].name] ? action.get('params')[action_type_params[j].name] : '';
				label.createChild({tag: 'div', cls: 'action_param', html: action_type_params[j].name +': '+ value});
			}
			i++;
		}
		
		// Add drags
		if ('undefined' != typeof view.getNode(0))
		{
			node_offset = Ext.get(view.getNode(0)).getHeight() / 2;
		}
		i = 0;
		while((node = Ext.get(view.getNode(i))))
		{
			action = view.store.getAt(i);

			dd = new Ext.dd.DragSource(node.dom.lastChild,{groups: 'actions', centerFrame: true, dragData: action.get('ref')});
			dd.setXConstraint(0,0);
			dd.setYConstraint(node.getTop() - view.getEl().getTop() + node_offset,(view.getEl().getBottom() - node.getBottom()) + (node_offset * 3));

			i++;
		}

		// Bottom target
		if(Ext.get(view.getNode(0)))
		{
			drop_target = new TSS.eCash.Config.DropTargetElseAction(view.getEl().createChild({tag: 'div', cls: 'drop_target'}), {overClass: 'drop_target_over', index: 'end', app: this});
			drop_target.addToGroup('actions');
			drop_target.setPadding(20);
		}

		if ('undefined' != typeof select)
		{
			var evObj = document.createEvent('MouseEvents');
			evObj.initMouseEvent( 'click', true, true, window, 1, 12, 345, 7, 220, false, false, true, false, 0, null );
			view.getNode(--i).dispatchEvent(evObj);
		}
	}
	,
	deselectElseActions: function(view)
	{
		if('undefined' == typeof view)
		{
			var view = this.getActiveTab().else_action_view;
			view.select(new Array());
		}
		view.getEl().select('.action_form{display!=none}').setDisplayed('none');
		view.getEl().select('.action_label{display=none}').setDisplayed('block');

	}
	,
	handleElseActionClick: function(view, index, node, event)
	{
		var action, form_el, action_type_params;
		node = Ext.get(node);
		
		this.deselectActions();
		this.deselectConditions();
		
		var layout = this.inner_center_layout.getLayout();

		layout.getRegion('east').resizeTo('50%');
		layout.getRegion('west').resizeTo('25%');
		
		if(node.select('.action_form').elements.length < 1)
		{
			this.deselectElseActions(view);
			node.select('.action_label{display!=none}').setDisplayed('none');
	
			action = view.store.getAt(index);
			action_types = this.business_rules_config.actions;
			action_type_params = action_types[action.get('action')].params;
			
			form = new Ext.form.Form({labelWidth: 50});
			form.fieldset({legend:action_types[action.get('action')].name});
			
			for(j = 0; j < action_type_params.length; j++)
			{
				form.add(
					new Ext.form.TextField({
						fieldLabel: action_type_params[j].name,
						name: action_type_params[j].name,
						width:50,
						allowBlank:false,
						value: 'undefined' != typeof action.get('params')[action_type_params[j].name] ? action.get('params')[action_type_params[j].name] : ''
				 }));
			}
			form.addButton({text:'Update', minWidth: 50}, this.hanelSaveElseAction.createDelegate(this, [form, action]));
			form.addButton({text:'Delete', minWidth: 50}, this.hanelRemoveElseAction.createDelegate(this, [action]));
			form.end();
			form_el = node.createChild({tag: 'div', cls: 'action_form'});
			form.render(form_el);
			form_el.show(true);
		}
		else if(node.select('.action_form{display=none}').elements.length > 0)
		{
			this.deselectElseActions(view);
	
			node.select('.action_label{display!=none}').setDisplayed('none');
			node.select('.action_form{display=none}').show(true);
			node.select('.action_form{display=none}').setDisplayed('block');
		}
	}
	,
	handleAddElseAction: function(combo, record, index)
	{
		var rule, action, empty_params, sequence_no;

		if('undefined' == typeof (rule = this.getSelectedRule()))
		{
			Ext.Msg.alert('Add Action Error', 'No Rule Selected');
		}
		else
		{	
			action = this.business_rules_config.actions[record.get('key')];

			empty_params = {};
			for(i = 0; i < action.params.length; i++)
			{
				empty_params[action.params[i].name] = '';
			}

			sequence_no = 0;
			if ('undefined' != typeof rule.else_actions[(rule.else_actions.length -1)])
			{
				sequence_no = rule.else_actions[(rule.else_actions.length -1)].sequence_no + 1;
			}

			rule.else_actions[rule.else_actions.length] = {action: record.get('key'), params: empty_params, sequence_no: sequence_no};
			this.applyRuleChanges();
			this.updateElseActionsView(rule.else_actions, true);
		}
	}
	,
	hanelSaveElseAction: function(form, action)
	{
		action.get('ref').params = form.getValues();
		this.applyRuleChanges();
		this.updateElseActionsView(this.getSelectedRule().else_actions);
	}
	,
	hanelRemoveElseAction: function(action)
	{
		var rule, i, a_copy;
		
		rule = this.getSelectedRule();
		a_copy = [];
		
		for(i = 0; i < rule.else_actions.length; i++)
		{
			if (rule.else_actions[i] != action.get('ref'))
			{
				a_copy[a_copy.length]  = rule.else_actions[i];
			}
		}
		rule.else_actions = a_copy;
		this.applyRuleChanges();
		this.updateElseActionsView(rule.else_actions);
	}
	,
	getSelectedRule: function()
	{		
		return this.getActiveTab().loan_type.get('rules')[this.getSelectedRuleIndex()];
	}
	,
	getSelectedRuleIndex: function()
	{
		var match_rule, rule, rules_view, index, i;
		
		match_rule	= false;
		rules_view	= this.getActiveTab().rules_view;
		loan_type	= this.getActiveTab().loan_type;
		
		if('undefined' != typeof rules_view.getSelectedIndexes()[0])
		{
			index		= rules_view.getSelectedIndexes()[0];
			rule		= rules_view.store.getAt(index).get('rule');
			rule_array	= loan_type.get('rules');

			for(i = 0; i < rule_array.length; i++)
			{
				if (rule_array[i] == rule)
				{
					match_rule = i;
					break;
				}
			}
		}
		return match_rule;
	}
	,
	applyRuleChanges: function()
	{
		return this.getActiveTab().loan_type.set('rules', this.getActiveTab().loan_type.get('rules').slice());
	}
	,
	hanelRemoveCondition: function(condition)
	{
		var rule, i, a_copy;
		
		rule = this.getSelectedRule();
		a_copy = [];
		
		for(i = 0; i < rule.conditions.length; i++)
		{
			if (rule.conditions[i] != condition.get('ref'))
			{
				a_copy[a_copy.length]  = rule.conditions[i];
			}
		}
		rule.conditions = a_copy;
		this.applyRuleChanges();
		this.updateConditionsView(rule.conditions);
	}
	,
	hanelSaveCondition: function(form, condition)
	{
		condition.get('ref').operator = form.findField('operator').getValue();
		condition.get('ref').operand1 = form.findField('operand1').getValue();
		condition.get('ref').operand2 = form.findField('operand2').getValue();
		this.applyRuleChanges();
		this.updateConditionsView(this.getSelectedRule().conditions);
	}
	,
	/**
	 * 
	 * @param {Object} nesteted_container_el
	 */
	initConditionPanel: function(container_el)
	{
		var panel_el, tb;

		panel_el = container_el.createChild({tag: 'div'});
		tb = new Ext.Toolbar(panel_el.createChild({tag: 'div'}));
		tb.add('Conditions');
		tb.addFill();
		this.conditions_button = tb.addButton({cls: 'x-btn-text-icon add_icon', text: 'Add', handler : this.handleAddCondition, scope: this});
		this.conditions_button.disable();
		tb.getEl().child('button').dom.tabIndex = 8;
		
		this.initConditionView(panel_el.createChild({tag: 'div'}));
		
		return new Ext.ContentPanel(panel_el, {toolbar: tb });
	}
	,
	initConditionView: function(target)
	{
		target.addClass('view_basic');
		var store = new Ext.data.SimpleStore({fields: ['operand1', 'operand1_type', 'operand2', 'operand2_type', 'operator', 'sequence_no', 'ref']});
		var view = new Ext.View(target,
		'<div></div>',
		{
			singleSelect: true,
			selectedClass: "light_blue_bg",
			store: store
		});

		this.getActiveTab().condition_view = view;

	}
	,
	updateConditionsView: function(conditions, select)
	{
		if('undefined' == typeof conditions) 
		{
			var conditions = this.getSelectedRule().conditions;
		}
		var view, node, form, condition, data, variables;

		if(this.getSelectedRuleIndex()) 
		{
			this.conditions_button.enable();
		} else {
			this.conditions_button.disable();
		}

		variables = this.business_rules_config.variables;
		
		view = this.getActiveTab().condition_view;

		view.store.removeAll();
		view.store.loadData(this.convertToArray(conditions));
		view.on('click', this.handleConditionClick, this);
		
		data = [];
		for(p in this.business_rules_config.variables)
		{
			data[data.length] = [p, this.business_rules_config.variables[p].name, this.business_rules_config.variables[p].type];
		}

		var store = new Ext.data.SimpleStore({fields: ['key', 'name','type'], data: data});

		i = 0;
		while((node = Ext.get(view.getNode(i))))
		{
			condition = view.store.getAt(i);
			
			var operators = {equals : '=', notequals :'!=', greater: '>', less: '<'};
			
			var label = 'undefined' != typeof variables[condition.get('operand1')] ? variables[condition.get('operand1')].name : '""';
			label += ' ' + operators[condition.get('operator')];
			label += ' ' + '' != condition.get('operand2') ? condition.get('operand2') : '""';

			label_el = node.createChild({tag: 'div', cls: 'condition_label', html: label});
			label_el.setStyle('overflow','hidden');

			form = new Ext.form.Form({});

			form.column({width:172,hideLabels: true});
			var op1 = new Ext.form.ComboBox({
				name: 'operand1',
				store: store,
				displayField:'name',
				valueField: 'key',
				typeAhead: true,
				mode: 'local',
				triggerAction: 'all',
				selectOnFocus:true,
				resizable:true,
				width: 170,
				value: condition.get('operand1')
			});
			op1.on('select', this.showStatusWidget, this);

			form.add(op1);
			form.end();
				
			form.column({width:42,hideLabels: true});
			form.add( new Ext.form.ComboBox({
				name: 'operator',
				store: new Ext.data.SimpleStore({fields: ['key', 'name'], data: [['equals','='],['notequals','!='],['greater','>'],['less','<']]}),
				displayField:'name',
				valueField: 'key',
				typeAhead: true,
				mode: 'local',
				triggerAction: 'all',
				selectOnFocus:true,
				width: 40,
				value: condition.get('operator')
			}));
			form.end();
			
			form.column({hideLabels: true});
			var op2 = new Ext.form.TextField({
					name: 'operand2',
					//width:75,
					allowBlank:false,
					value: condition.get('operand2'),
					grow:true,
					growMax:250,
			});
			op2.id = op1.getId() + '_op2';
			form.add(op2);
			form.end();
			form.addButton({text: 'Update', minWidth: 50}, this.hanelSaveCondition.createDelegate(this, [form, condition]));
			form.addButton({text: 'Delete', minWidth: 50}, this.hanelRemoveCondition.createDelegate(this, [condition]));

			var form_el = node.createChild({tag: 'div', cls: 'condition_form'});
			form_el.beginMeasure();
			form.render(form_el);
			form_el.endMeasure();

			i++;
		}
		
		if ('undefined' != typeof select)
		{
			var evObj = document.createEvent('MouseEvents');
			evObj.initMouseEvent( 'click', true, true, window, 1, 12, 345, 7, 220, false, false, true, false, 0, null );
			view.getNode(--i).dispatchEvent(evObj);
		}
		
	}
	,
	deselectConditions: function(view)
	{
		if('undefined' == typeof view)
		{
			var view = this.getActiveTab().condition_view;
			view.select(new Array());
		}
		view.getEl().select('.condition_form{display!=none}').setDisplayed('none');
		view.getEl().select('.condition_label{display=none}').setDisplayed('block');
	}
	,
	handleConditionClick: function(view, index, node, event)
	{
		node = Ext.get(node);
		
		if(node.select('.condition_form{display=none}').elements.length > 0)
		{
			this.deselectConditions(view);
			this.deselectActions();
			this.deselectElseActions();
	
			node.select('.condition_label{display!=none}').setDisplayed('none');
			node.select('.condition_form{display=none}').show(true);
			node.select('.condition_form{display=none}').setDisplayed('block');
		}
		var layout = this.inner_center_layout.getLayout();
		layout.getRegion('east').resizeTo('25%');
		layout.getRegion('west').resizeTo('50%');
	}
	,
	handleAddCondition: function(sender, event)
	{
		var rule, condition;

		if('undefined' == typeof (rule = this.getSelectedRule()))
		{
			Ext.Msg.alert('Add Action Error', 'No Rule Selected');
		}
		else
		{
			condition = {operand1: '', operand1_type: 0,operand2: '', operand2_type: 1, operator: 'equals', sequence_no: 1};
			rule.conditions[rule.conditions.length] = condition;
			this.applyRuleChanges();
			this.updateConditionsView(rule.conditions, condition);
		}
	}
	,
	/**
	 * 
	 * @param {Object} sender
	 * @param {Object} event
	 * @param {Object} record
	 * @param {Object} combo
	 */
	hanelAddRule: function(field, combo)
	{
		var next_sail, rule_array, i, test_sail;

		if ('' == combo.getValue())
		{
			combo.markInvalid();
		}
		if ('' == field.getValue())
		{
			field.markInvalid();
		}

		if ('' == field.getValue())
		{
			Ext.Msg.alert('Add Rule Error', 'Please Enter A Name');
		}
		else if ('' == combo.getValue())
		{
			Ext.Msg.alert('Add Rule Error', 'Please Select An Event');
		}
		else
		{	
			rule_array = this.getActiveTab().loan_type.get('rules').slice();
			
			next_sail = 0;
			for(i = 0; i < rule_array.length; i++)
			{
				test_sail = parseInt(rule_array[i].salience, 10) + 1;
				if (test_sail > next_sail)
				{
					next_sail = test_sail;
				}
			}

			rule_array[rule_array.length] = 
			{
				actions: [],
				'else_actions': [],
				conditions: [],
				event: combo.getValue(),
				name: field.getValue(),
				salience: next_sail
			};

			this.getActiveTab().loan_type.set('rules',rule_array);

			this.updateEventsView(this.getActiveTab().loan_type, combo.getValue());
			this.updateRulesView(combo.getValue());
		}
	}
	,
	handelRemoveRule: function(rule)
	{
		var rule_array, i, a_copy;

		a_copy = [];
		rule_array = this.getActiveTab().loan_type.get('rules');
		for(i = 0; i < rule_array.length; i++)
		{
			if (rule != rule_array[i])
			{
				a_copy[a_copy.length] = rule_array[i];
			}
		}
		this.getActiveTab().loan_type.set('rules', a_copy);

		this.updateEventsView(this.getActiveTab().loan_type);
		this.updateRulesView(rule.event);
		this.updateConditionsView([]);
		this.updateActionsView([]);
		this.updateElseActionsView([]);
	}
	,
	/**
	 * 
	 * @param {Object} panel
	 * @param {Object} record
	 */
	renderSelectedStates: function(panel, record)
	{
		var store, states, z, view;
	
		states = record.get('states');

		z = function()
		{
			if (states.length === 0)
			{
				panel.update('None');
			}
			else
			{
				store = new Ext.data.SimpleStore({
							fields: ['abbr', 'state'],
							data : this.states
						});
				
				store.filterBy( function( record, index ){ if ( -1 !== states.indexOf( record.get( 'abbr' ) ) ){ return true; } } );

				view = new Ext.View(panel, '<div><img align="top" src="images/delete.gif" border="0" /> {state}</div>', // auto create template 
				{
					singleSelect: true, store: store 
				});

				// listen for node click (remove old listeners)
				Ext.EventManager.removeListener(panel.dom, "click", this.removeState, this, {record:record, view:view});
				Ext.EventManager.addListener(panel.dom, "click", this.removeState, this, {record:record, view:view});
			}
			panel.slideIn();
		};

		panel.slideOut('t', {callback: z, scope: this});
	}
	,
	/**
	 * 
	 * @param {Object} record
	 */
	handelSelectedStatesUpdate: function(record)
	{
		var panel = Ext.get(Ext.query('.state_fieldset .current_state_panel', this.layout.getRegion('center').getActivePanel().getEl().dom)[0]);
		this.renderSelectedStates(panel, record);
	}
	,
	/**
	 * 
	 * @param {Object} combo
	 * @param {Object} state_record
	 * @param {Object} index
	 * @param {Object} loan_type_record
	 */
	handleAddState: function(combo, state_record, index, loan_type_record)
	{
		var states;

		states = loan_type_record.get('states').slice(); //Make a copy
		states[states.length] = state_record.get('abbr');
		loan_type_record.set('states', states);
		
		this.handelSelectedStatesUpdate(loan_type_record);
		
		combo.reset();
	}
	,
	/**
	 * 
	 * @param {Object} vw
	 * @param {Object} index
	 * @param {Object} node
	 * @param {Object} e
	 * @param {Object} record
	 */
	removeState: function(e, sender, options, record)
	{	
		var abbr, states, clone, i, index;
		
		index = options.view.getSelectedIndexes()[0];
		
		if (e.getTarget().tagName == 'IMG')
		{
			abbr = options.view.store.getAt(index).get('abbr');
			states = options.record.get('states');
			clone = [];
			
			for(i = 0;i < states.length; i++)
			{
				if (abbr != states[i])
				{
					clone[clone.length] = states[i];
				}
			}
			options.record.set('states', clone);

			this.handelSelectedStatesUpdate(options.record);
		}
	}
	,
	/**
	 * 
	 */
	fetchLoanTypeList: function()
	{
		var p,i;
		var loan_list = {};

		for(p in this.loan_type_array)
		{
			for(i in this.loan_type_array[p])
			{
				if ("undefined" == typeof loan_list[p])
				{
					loan_list[p] = {};
					loan_list[p].id = this.loan_type_array[p][i].id;
					loan_list[p].name = this.loan_type_array[p][i].name;
					loan_list[p].versions = [];
				}

				loan_list[p].versions[loan_list[p].versions.length] = 
				{
					version: this.loan_type_array[p][i].version,
					created: this.loan_type_array[p][i].created
				};
			}
		}

		return loan_list;
	}
	,
	fetchLoanType: function(lt_short_name, rule_set_id, callback, scope)
	{
		if ("undefined" == typeof this.loan_type_store.getById(lt_short_name +'-'+ rule_set_id))
		{
			Ext.Ajax.request({url: 'json.php?action=getLoanType&params='+ lt_short_name +','+ rule_set_id ,callback: this.handleLoanTypeResponse, scope: this, client_callback: callback, client_scope: scope});
		}
		else
		{
			callback.call(scope, this.loan_type_store.getById(lt_short_name +'-'+ rule_set_id));
		}
	}
	,
	handleLoanTypeResponse: function(options, success, response)
	{
		var json;
		if (success)
		{
			json = eval("("+response.responseText+")");
			this.loan_type_store.loadData(json, true);

			options.client_callback.call(options.client_scope, this.loan_type_store.getById(json.rows[0].unique_id));
		}
		else
		{
			alert('ERROR!!!!!!');
		}
	}
	,
	shallowCopy: function(target)
	{
		a_copy = {};
		for( p in target)
		{
			a_copy[p] = target[p];
		}
		return a_copy;
	}
	,
	convertToArray: function(object_array)
	{
		var a_array = [], o_copy;
		for(var i = 0; i < object_array.length; i++)
		{
			o_copy = [];
			for(var p in object_array[i])
			{
				o_copy[o_copy.length] = object_array[i][p];
			}
			//Add referance, used for matching
			o_copy[o_copy.length] = object_array[i];

			a_array[a_array.length] = o_copy;
		}
		return a_array;
	}
	,
	var_dump: function(target)
	{
		var alert_string = '';
		for(m in target)
		{
			alert_string += m + ' -> ' + target[m] + '\n';
		}
		alert(alert_string);
	}
	,
	states: [
		['AL', 'Alabama'],
		['AK', 'Alaska'],
		['AZ', 'Arizona'],
		['AR', 'Arkansas'],
		['CA', 'California'],
		['CO', 'Colorado'],
		['CT', 'Connecticut'],
		['DE', 'Delaware'],
		['DC', 'District of Columbia'],
		['FL', 'Florida'],
		['GA', 'Georgia'],
		['HI', 'Hawaii'],
		['ID', 'Idaho'],
		['IL', 'Illinois'],
		['IN', 'Indiana'],
		['IA', 'Iowa'],
		['KS', 'Kansas'],
		['KY', 'Kentucky'],
		['LA', 'Louisiana'],
		['ME', 'Maine'],
		['MD', 'Maryland'],
		['MA', 'Massachusetts'],
		['MI', 'Michigan'],
		['MN', 'Minnesota'],
		['MS', 'Mississippi'],
		['MO', 'Missouri'],
		['MT', 'Montana'],
		['NE', 'Nebraska'],
		['NV', 'Nevada'],
		['NH', 'New Hampshire'],
		['NJ', 'New Jersey'],
		['NM', 'New Mexico'],
		['NY', 'New York'],
		['NC', 'North Carolina'],
		['ND', 'North Dakota'],
		['OH', 'Ohio'],
		['OK', 'Oklahoma'],
		['OR', 'Oregon'],
		['PA', 'Pennsylvania'],
		['RI', 'Rhode Island'],
		['SC', 'South Carolina'],
		['SD', 'South Dakota'],
		['TN', 'Tennessee'],
		['TX', 'Texas'],
		['UT', 'Utah'],
		['VT', 'Vermont'],
		['VA', 'Virginia'],
		['WA', 'Washington'],
		['WV', 'West Virginia'],
		['WI', 'Wisconsin'],
		['WY', 'Wyoming']
	]
};

TSS.eCash.Config.DropTargetRule = function(el, config)
{
    this.el = Ext.get(el);
    
    Ext.apply(this, config);
    
    if(this.containerScroll){
        Ext.dd.ScrollManager.register(this.el);
    }
    
    Ext.dd.DropTarget.superclass.constructor.call(this, this.el.dom, this.ddGroup || this.group, 
          {isTarget: true});
};

Ext.extend(TSS.eCash.Config.DropTargetRule, Ext.dd.DropTarget, 
{
    notifyDrop : function(dd, e, data)
	{
		var rules, rules_sort, inserted, i, salience;

		if (this.index != data.rule.salience)
		{
			rules = data.loan_type.get('rules');
			rules_sort = [];
			inserted = false;
			salience = 0;
			
			if('end' == this.index)
			{
				this.index = rules.length * 2; // Any number greater then other possible salience
			}

			for(i = 0; i < rules.length; i++)
			{
				if (data.rule != rules[i])
				{
					if (rules[i].salience >= this.index)
					{
						if (false == inserted)
						{
							data.rule.salience = salience;
							rules_sort[rules_sort.length] = data.rule;
							inserted = true;
							salience++;
						}
						rules[i].salience = salience;
					}
					else
					{
						 rules[i].salience = salience;
					}

					rules_sort[rules_sort.length] = rules[i];
					salience++;
				}
			}
			
			if(false == inserted)
			{
				data.rule.salience = salience;
				rules_sort[rules_sort.length] = data.rule;
			}
			
			data.loan_type.set('rules', rules_sort);
			setTimeout(this.app.updateRulesView.createDelegate(this.app, [data.rule.event]), 1);
        	return true;
		}
		return false;
    }
});

TSS.eCash.Config.DropTargetAction = function(el, config)
{
    this.el = Ext.get(el);
    
    Ext.apply(this, config);
    
    if(this.containerScroll){
        Ext.dd.ScrollManager.register(this.el);
    }
    
    Ext.dd.DropTarget.superclass.constructor.call(this, this.el.dom, this.ddGroup || this.group, 
          {isTarget: true});
};

Ext.extend(TSS.eCash.Config.DropTargetAction, Ext.dd.DropTarget, 
{
    notifyDrop : function(dd, e, action)
	{
		var actions_sorted, inserted, i, sequence_no, rule;

		if (this.index != action.sequence_no)
		{
			rule = this.app.getSelectedRule();

			actions_sorted = [];
			inserted = false;
			sequence_no = 0;
			
			if('end' == this.index)
			{
				this.index = rule.actions.length * 2; // Any number greater then other possible salience
			}

			for(i = 0; i < rule.actions.length; i++)
			{
				if (action != rule.actions[i])
				{
					if (rule.actions[i].sequence_no >= this.index)
					{
						if (false == inserted)
						{
							action.sequence_no = sequence_no;
							actions_sorted[actions_sorted.length] = action;
							inserted = true;
							sequence_no++;
						}
						rule.actions[i].sequence_no = sequence_no;
					}
					else
					{
						 rule.actions[i].sequence_no = sequence_no;
					}

					actions_sorted[actions_sorted.length] = rule.actions[i];
					sequence_no++;
				}
			}

			if(false == inserted)
			{
				action.sequence_no = sequence_no;
				actions_sorted[actions_sorted.length] = action;
			}

			rule.actions = actions_sorted;

			this.app.applyRuleChanges();
			setTimeout(this.app.updateActionsView.createDelegate(this.app, [rule.actions]), 1);

        	return true;
		}

		return false;
    }
});

TSS.eCash.Config.DropTargetElseAction = function(el, config)
{
    this.el = Ext.get(el);
    
    Ext.apply(this, config);
    
    if(this.containerScroll){
        Ext.dd.ScrollManager.register(this.el);
    }
    
    Ext.dd.DropTarget.superclass.constructor.call(this, this.el.dom, this.ddGroup || this.group, 
          {isTarget: true});
};

Ext.extend(TSS.eCash.Config.DropTargetElseAction, Ext.dd.DropTarget, 
{
    notifyDrop : function(dd, e, action)
	{
		var actions_sorted, inserted, i, sequence_no, rule;

		if (this.index != action.sequence_no)
		{
			rule = this.app.getSelectedRule();

			actions_sorted = [];
			inserted = false;
			sequence_no = 0;

			if('end' == this.index)
			{
				this.index = rule.else_actions.length * 2; // Any number greater then other possible salience
			}

			for(i = 0; i < rule.else_actions.length; i++)
			{
				if (action != rule.else_actions[i])
				{
					if (rule.else_actions[i].sequence_no >= this.index)
					{
						if (false == inserted)
						{
							action.sequence_no = sequence_no;
							actions_sorted[actions_sorted.length] = action;
							inserted = true;
							sequence_no++;
						}
						rule.else_actions[i].sequence_no = sequence_no;
					}
					else
					{
						 rule.else_actions[i].sequence_no = sequence_no;
					}

					actions_sorted[actions_sorted.length] = rule.else_actions[i];
					sequence_no++;
				}
			}

			if(false == inserted)
			{
				action.sequence_no = sequence_no;
				actions_sorted[actions_sorted.length] = action;
			}

			rule.else_actions = actions_sorted;

			this.app.applyRuleChanges();
			setTimeout(this.app.updateElseActionsView.createDelegate(this.app, [rule.else_actions]), 1);

        	return true;
		}

		return false;
    }
});
