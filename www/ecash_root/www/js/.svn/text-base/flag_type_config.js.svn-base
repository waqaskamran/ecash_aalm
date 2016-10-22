if (typeof TSS == "undefined"){TSS = {};}
if (typeof TSS.eCash == "undefined"){TSS.eCash = {};}

TSS.eCash.Config = 
{
	layout: null,
	flag_type_record: null, 
	flag_types: null,
	form_fields: {
		date_created: null,
		date_modified: null,
		active_status: {
			active: null,
			inactive: null
		},
		flag_type_id: null,
		name: null,
		name_short: null,
		submit: null
	},
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
		this.layout.layout();
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
				minSize: 50,
				maxSize: 400,
				titlebar: true,
				collapsible: true
			},
			center: {
				id: 'top_layout_center',
				titlebar: true,
				autoScroll:true,
				minTabWidth: 50,
			}
		});

		toolbar = new Ext.Toolbar(this.layout.getRegion('west').getEl().dom.id);
		toolbar.add('Flag Types');
		toolbar.addFill();
		toolbar.addButton({
					cls: 'x-btn-text-icon add_icon',
					text: 'Add',
					handler: this.handleAddFlagTypeClick,
					scope: this			
		});

		var view = new Ext.View(this.layout.getRegion('west').getEl().createChild({tag: 'div', cls: 'view_basic'}),
			'<div class="flag_type_select">{name_short} ({active_status})</div>', // auto create template
			{
				singleSelect: true,
				selectedClass: "light_blue_bg",
				store: TSS.eCash.Config.flag_types,
			});
		view.on('click', this.handleFlagClick, this);
		view.getEl().unselectable();
		
		//start the form information
		toolbar = new Ext.Toolbar(this.layout.getRegion('center').getEl().dom.id);
		toolbar.add('Flag Info');
		toolbar.addFill();
		toolbar.addButton({});
		
		//message bar
		var result = this.message;
		this.message = this.layout.getRegion('center').getEl().createChild({tag: 'div' }).dom;
		this.message.style.color = '#ff0000';
		this.message.style.textAlign = 'center';
		this.message.innerHTML = result;
			
		var form = new Ext.form.Form({
			labelAlign: 'right',
			method: 'POST',
			id: 'flag_type_form',
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
		this.form_fields.active_status.active = new Ext.form.Radio({
			name: 'active_status',
			fieldLabel: 'Active',
			disabled: true,
			value: 'active'
		});
		this.form_fields.active_status.inactive = new Ext.form.Radio({
			name: 'active_status',
			fieldLabel: 'Inactive',
			disabled: true,
			value: 'inactive',
			checked: true
		});
		form.add(this.form_fields.date_created, this.form_fields.date_modified, this.form_fields.active_status.active, this.form_fields.active_status.inactive, this.form_fields.name, this.form_fields.name_short);
		this.form_fields.submit = form.addButton('Submit', this.submitForm, this);
		this.form_fields.submit.disable();
		form.render(this.layout.getRegion('center').getEl().createChild({tag: 'div'}).dom.id);
		form = Ext.get('flag_type_form');
		this.form_fields.flag_type_id = form.createChild({
			tag: 'input',
			type: 'hidden',
			name: 'flag_type_id'
		}).dom;
		form.createChild({
			tag: 'input',
			type: 'hidden',
			name: 'action',
			value: 'save_flag_type'
		}).dom;
		this.form = form;
	}
	,
	submitForm: function()
	{
		var result = '';
		//alert(Ext.Ajax.serializeForm(this.form.dom));
		if(this.form_fields.name.getValue() == '')
		{
			result += 'You must enter a name to save this flag type.<br>';
		}
		if(this.form_fields.name_short.getValue() == '')
		{
			result += 'You must add a name short to save this flag type.<br>';
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
	handleFlagClick: function(view, index)
	{
		this.enableFormFields();
		this.setFormValues(this.flag_types.getAt(index));
	}
	,
	handleAddFlagTypeClick: function()
	{
			var msg = Ext.MessageBox.prompt('Name', 'Please enter a name:', this.addFlagType, this);		
	}
	,
	addFlagType: function(button, name)
	{
		if(button == 'ok')
		{
			this.enableFormFields();
			this.setFormValues(new this.flag_type_record({
				name_short: name
			}));
		}
	}
	,
	enableFormFields: function()
	{
		this.message.innerHTML = '';
		this.form_fields.active_status.active.enable();
		this.form_fields.active_status.active.getEl().dom.value='active';
		this.form_fields.active_status.inactive.enable();
		this.form_fields.active_status.inactive.getEl().dom.value='inactive';
		this.form_fields.name.enable();
		this.form_fields.name_short.enable();
		this.form_fields.submit.enable();	
	}
	,
	clearForm: function()
	{
		this.enableFormFields();
		this.form_fields.active_status.inactive.getEl().dom.checked = true;
		this.form_fields.name.setValue('');
		this.form_fields.name_short.setValue('');
		this.form_fields.date_created.setValue('');
		this.form_fields.date_modified.setValue('');
	}
	,
	setFormValues: function(record)
	{
		if(record.get('active_status') == 'active')
		{
			this.form_fields.active_status.active.getEl().dom.checked=true;
		} 
		else 
		{
			this.form_fields.active_status.inactive.getEl().dom.checked=true;
		}
		this.form_fields.name.setValue(record.get('name'));
		this.form_fields.name_short.setValue(record.get('name_short'));
		this.form_fields.flag_type_id.value = typeof record.get('flag_type_id') != 'undefined' ? record.get('flag_type_id') : '';
		this.form_fields.date_created.setValue(record.get('date_created'));
		this.form_fields.date_modified.setValue(record.get('date_modified'));
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
