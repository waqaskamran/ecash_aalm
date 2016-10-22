
var reports_grid = {
	ds: null,
	init : function (data_store_url, column_names, column_model, filter_options, display_object) {
		// create the Data Store
		this.ds = new Ext.data.Store({
			// load using HTTP
			proxy: new Ext.data.HttpProxy({
				url: data_store_url
			}),

			// the return will be XML, so lets set up a reader
			reader: new Ext.data.XmlReader({
				// records will have an 'Item' tag
				totalRecords: 'TOTAL_ITEMS',
				record: 'ITEM',
			}, column_names),

			remoteSort: true
		});

		// Provide a convience method for loading a specific page.
		this.ds.loadPage = function(page) {
			this.load({
				params:{
					start: (page - 1) * paging.pageSize,
					limit: paging.pageSize
				}
			});
		}

		var grid = new Ext.grid.Grid(display_object, {
			ds: this.ds,
			cm: column_model,
			selModel: new Ext.grid.RowSelectionModel({singleSelect:true}),
			loadMask: true
		});

		grid.render();

		var gridFoot = grid.getView().getFooterPanel(true);

		var paging = new Ext.PagingToolbar(gridFoot, this.ds, {
			pageSize: 100,
			displayInfo: true,
			displayMsg: '{0} - {1} of {2} total record(s)',
			emptyMsg: 'No records to display'
		});

		if (filter_options) {
			filter_select_options = '<select name="filter_field" id="filter_field">';
			for (i in filter_options) {
				filter_select_options = filter_select_options + '<option value="' + i + '">' + filter_options[i] + '</option>';
			}

			paging.add(
				'-',
				'Filter: ',
				filter_select_options,
				'<input type="text" name="filter_text" id="filter_text" />',
				'<input type="button" name="button_submit" id="button_submit" value="Update" onClick="reports_grid.ds.loadPage(1);" />',
				'<input type="button" name="button_clear" id="button_clear" value="Reset" onClick="document.getElementById(\'filter_text\').value=\'\'; reports_grid.ds.loadPage(1);" />'
			);
		}

		this.ds.on('beforeload', function() {
			if (document.getElementById('filter_field')) {
				this.baseParams['filter_field'] = document.getElementById('filter_field').value;
			}
			if (document.getElementById('filter_text')) {
				this.baseParams['filter_text'] = document.getElementById('filter_text').value;
			}
		});

		// trigger the data store load
		this.ds.loadPage(1);
	}
}
