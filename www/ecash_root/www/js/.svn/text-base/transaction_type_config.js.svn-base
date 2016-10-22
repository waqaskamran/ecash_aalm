if (typeof TSS == "undefined"){TSS = {};}
if (typeof TSS.eCash == "undefined"){TSS.eCash = {};}

TSS.eCash.Config = 
{
	layout: null,
	transaction_type_record: null, 
	transaction_types: null,
	form_fields: {
		date_created: null,
		date_modified: null,
		active_status: null,
		transaction_type_id: null,
		name: null,
		name_short: null,
		clearing_type: null,
		affects_principal: null,
		pending_period: null,
		end_status: null,
		period_type: null,
		event_type_id: null,
		submit: null
	},
	event_types: null,
	form: null,
	message: null,
	
	/**
	 * Call when the dom has loaded
	 */
	init: function(container_id)
	{
		var container = Ext.get(container_id);
		container.setHeight(Ext.get(document).getViewSize().height - container.getY() - 8);
		this.initLayout(container);
		this.initForm();
	}
	,
	/**
	 * Setup the basic layout and toolbar
	 */
	initLayout: function(container)
	{
		this.layout = new Ext.BorderLayout(container, {
			west: {
				id: 'top_layout_west',
				split:true,
				initialSize: 200,
				minSize: 100,
				maxSize: 400,
				initialSize: 250,
				titlebar: true,
				collapsible: true,
				autoScroll: true
			},
			center: {
				id: 'top_layout_center',
				titlebar: true,
				autoScroll:true,
				minTabWidth: 50,
			}
		});

		toolbar = new Ext.Toolbar(this.layout.getRegion('west').titleEl);
		toolbar.add('Transaction Types');
		toolbar.addFill();
		toolbar.addButton({
					cls: 'x-btn-text-icon add_icon',
					text: 'Add',
					handler: this.handleAddTransactionTypeClick,
					scope: this			
		});

		var view = new Ext.View(this.layout.add('west', new Ext.ContentPanel(this.layout.getEl().createChild({tag: 'div', cls: 'view_basic'}), {autoCreate:true, closable: false})).getEl().dom.id,
			'<div class="transaction_type_select">{name_short} ({active_status})</div>', // auto create template
			{
				singleSelect: true,
				selectedClass: "light_blue_bg",
				store: TSS.eCash.Config.transaction_types,
				scroll: true,
				resizeEl: true
			});
		view.on('click', this.handleTransactionClick, this);
		view.getEl().unselectable();
		
		this.layout.getRegion('west').getEl().dom.style.overflow = 'auto';
		
		//start the form information
		toolbar = new Ext.Toolbar(this.layout.getRegion('center').getEl().dom.id);
		toolbar.add('Transaction Type Info');
		toolbar.addFill();
		toolbar.addButton({});
		
		//message bar
		var result = this.message;
		this.message = this.layout.getRegion('center').getEl().createChild({tag: 'div' }).dom;
		this.message.style.color = '#ff0000';
		this.message.style.textAlign = 'center';
		this.message.innerHTML = result;
		this.layout.layout();
	}
	,
	initForm: function()
	{
		var form = new Ext.form.Form({
			labelAlign: 'right',
			method: 'POST',
			id: 'transaction_type_form',
			align: 'center'
		});
		this.form_fields.date_created = new Ext.form.TextField({
			fieldLabel: 'Date Created',
			disabled: true,
			width: 140
		});
		this.form_fields.date_modified = new Ext.form.TextField({
			fieldLabel: 'Date Modified',
			disabled: true,
			width: 140
		});
		this.form_fields.name = new Ext.form.TextField({
			fieldLabel: 'Name',
			name: 'name',
			disabled: true,
			growMin: 140,
			maxLength: 100,
			grow: true,
			width: 140
		});
		this.form_fields.name_short = new Ext.form.TextField({
			fieldLabel: 'Name Short',
			name: 'name_short',
			growMin: 140,
			disabled: true,
			maxLength: 25,
			grow: true,
			width: 140
		});
		this.form_fields.active_status = new Ext.form.ComboBox({
			name: 'active_status',
			disabled:true,
			fieldLabel: 'Active Status',
			store: new Ext.data.SimpleStore({ fields: ['key','name'], data: this.createArray(['active','inactive'])}),
			value: 'inactive',
			valueField: 'key',
			displayField: 'name',
			width: 140,
			typeAhead: true,
			triggerAction: 'all',
			resizable: true,
			editable: false
		});
		this.form_fields.clearing_type = new Ext.form.ComboBox({
			name: 'clearing_type',
			disabled:true,
			fieldLabel: 'Clearing Type',
			store: new Ext.data.SimpleStore({ fields: ['key','name'], data: this.createArray(['ach','quickcheck','accrued charge','adjustment'])}),
			value: 'ach',
			valueField: 'key',
			displayField: 'name',
			width: 140,
			typeAhead: true,
			triggerAction: 'all',
			resizable: true,
			editable: false
		});
		this.form_fields.affects_principal = new Ext.form.ComboBox({
			name: 'affects_principal',
			disabled:true,
			fieldLabel: 'Affects Principal',
			store: new Ext.data.SimpleStore({ fields: ['key','name'], data: this.createArray(['yes','no'])}),
			value: 'yes',
			valueField: 'key',
			displayField: 'name',
			width: 140,
			typeAhead: true,
			triggerAction: 'all',
			resizable: true,
			editable: false
		});
		this.form_fields.pending_period = new Ext.form.TextField({
			name: 'pending_period',
			disabled:true,
			fieldLabel: 'Pending Period',
			width: 140,
		});
		this.form_fields.end_status = new Ext.form.ComboBox({
			name: 'end_status',
			disabled:true,
			fieldLabel: 'End Status',
			store: new Ext.data.SimpleStore({ fields: ['key','name'], data: this.createArray(['complete','failed'])}),
			value: 'complete',
			valueField: 'key',
			displayField: 'name',
			width: 140,
			typeAhead: true,
			triggerAction: 'all',
			resizable: true,
			editable: false
		});
		this.form_fields.period_type = new Ext.form.ComboBox({
			name: 'period_type',
			disabled:true,
			fieldLabel: 'Period Type',
			store: new Ext.data.SimpleStore({ fields: ['key','name'], data: this.createArray(['calendar', 'business'])}),
			value: 'calendar',
			valueField: 'key',
			displayField: 'name',
			width: 140,
			typeAhead: true,
			triggerAction: 'all',
			resizable: true,
			editable: false
		});
		this.form_fields.event_type_id = new Ext.form.ComboBox({
			name: 'event_type_id',
			disabled:true,
			fieldLabel: 'Event Type',
			store: this.event_types,
			valueField: 'event_type_id',
			value: this.event_types.getAt(0).get('event_type_id'),
			displayField: 'name_short',
			width: 140,
			typeAhead: true,
			triggerAction: 'all',
			resizable: true,
			editable: false
		});
		form.add(
			this.form_fields.date_created, 
			this.form_fields.date_modified,
			this.form_fields.active_status, 
			this.form_fields.name, 
			this.form_fields.name_short, 
			this.form_fields.clearing_type, 
			this.form_fields.affects_principal,
			this.form_fields.pending_period,
			this.form_fields.end_status,
			this.form_fields.period_type,
			this.form_fields.event_type_id
		);
		this.form_fields.submit = form.addButton('Submit', this.submitForm, this);
		this.form_fields.submit.disable();
		this.form_ext = form;
		form.render(this.layout.getRegion('center').getEl().createChild({tag: 'div'}).dom.id);
		form = Ext.get('transaction_type_form');
		this.form_fields.transaction_type_id = form.createChild({
			tag: 'input',
			type: 'hidden',
			name: 'transaction_type_id'
		}).dom;
		form.createChild({
			tag: 'input',
			type: 'hidden',
			name: 'action',
			value: 'save_transaction_type',
		});
		form.createChild({
			tag: 'input',
			type: 'hidden',
			name: 'mode',
			value: 'transaction_type_config',
		});
		form.createChild({
			tag: 'input',
			type: 'hidden',
			name: 'module',
			value: 'admin',
		});
		this.form = form;
	}
	,
	createArray: function(arr)
	{
		var retval = new Array();
		for(var i = 0; i < arr.length; ++i)
		{
			retval[retval.length] = [arr[i], arr[i]];
		}
		return retval;
	}
	,
	submitForm: function()
	{
		var result = '';
		//alert(Ext.Ajax.serializeForm(this.form.dom));
		if(this.form_fields.name.getValue() == '')
		{
			result += 'You must enter a name to save this transaction type.<br>';
		}
		if(this.form_fields.name_short.getValue() == '')
		{
			result += 'You must add a name short to save this transaction type.<br>';
		}
		if(this.form_fields.event_type_id.getValue() != this.transaction_events[this.form_fields.transaction_type_id.value])
		{
			for(var i = 0; i < this.maxed_events.length; ++i)
			{
				if(this.form_fields.event_type_id.getValue() == this.maxed_events[i])
				{
					result += 'There are already two transaction types with this event type.';
				}
			}
		}
		
		if(result == '')
		{
			this.form.dom.submit();
		}
		else
		{
			this.message.innerHTML = result;
		}
	}
	,
	handleTransactionClick: function(view, index)
	{
		this.enableFormFields();
		this.setFormValues(this.transaction_types.getAt(index));
	}
	,
	handleAddTransactionTypeClick: function()
	{
			var msg = Ext.MessageBox.prompt('Name', 'Please enter a name:', this.addTransactionType, this);		
	}
	,
	addTransactionType: function(button, name)
	{
		if(button == 'ok')
		{
			this.clearForm();
			this.form_fields.name_short.setValue(name);
		}
	}
	,
	enableFormFields: function()
	{
		this.message.innerHTML = '';
		this.form_fields.active_status.enable();
		this.form_fields.name.enable();
		this.form_fields.name_short.enable();
		this.form_fields.clearing_type.enable();
		this.form_fields.affects_principal.enable();
		this.form_fields.pending_period.enable();
		this.form_fields.end_status.enable();
		this.form_fields.period_type.enable();
		this.form_fields.event_type_id.enable();
		this.form_fields.submit.enable();	
	}
	,
	clearForm: function()
	{
		this.enableFormFields();
		this.form_fields.active_status.setValue('inactive');
		this.form_fields.name.setValue('');
		this.form_fields.name_short.setValue('');
		this.form_fields.date_created.setValue('');
		this.form_fields.date_modified.setValue('');
		this.form_fields.clearing_type.setValue('ach');
		this.form_fields.affects_principal.setValue('yes');
		this.form_fields.pending_period.setValue('');
		this.form_fields.end_status.setValue('complete');
		this.form_fields.period_type.setValue('calendar');
		this.form_fields.event_type.setValue(this.event_types.getAt(0).get('event_type_id'));
	}
	,
	setFormValues: function(record)
	{
		for(var i in this.form_fields)
		{
			if(record.get(i) && this.form_fields[i].setValue)
			{
				this.form_fields[i].setValue(record.get(i));
			} else if(record.get(i)) {
				this.form_fields[i].value = record.get(i);
			}
		}
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
};
var_dump = TSS.eCash.Config.var_dump;
