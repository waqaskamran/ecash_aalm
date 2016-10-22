
var Tabs = 
{
	tabs : '',
	previous_tab : '',
    init : function()
    {
        // basic tabs 1, built from existing content
        this.tabs = new YAHOO.ext.TabPanel( 'tabs1' );
        this.tabs.resizeTabs = true;
        
        tab1 = this.tabs.addTab( 'tree', "Reports" );
        this.tabs.activate('tree');
        
        tab2 = this.tabs.addTab( 'search', " Report Search" );
		tab2.onActivate.subscribe(this.sizeActiveTab, this, true );
		
		tabs = this.tabs;

		/**
		  * The following code is for the Online Help for Reporting within eCash.
		  * If a Help URL exists, the method addHelpTab() is called along with the 
		  * URL to use for the pop-up window.  The way the code works is that we
		  * store the previous tab's id in this object and if the user just so 
		  * happens to click on the Help tab, we'll immediately switch back so the
		  * user never sees a change and then open the pop-up window using the
		  * URL that is stored in the Help TabPanelItem object.
		  *
		  * It's not pretty, but it works.  Feel free to improve on it in any
		  * way you can. [BR]
		  */
		this.tabs.on('beforetabchange',function(tabs) {
			if(tabs.getActiveTab().getText() != 'Help')
			{
				this.previous_tab = tabs.getActiveTab().id;
			}
		});
		this.tabs.on('tabchange',function(tabs) {
			if(tabs.getActiveTab().getText() == 'Help')
			{
				help_tab = tabs.getActiveTab();
				last_tab = tabs.getTab(this.previous_tab);
				last_tab.activate();
				javascript:openOnlineHelpWindow(help_tab.helpUrl, 'Reporting Help');
			}
		});

    }
    ,
    openInTab : function( name, url )
    {
            var tab = this.tabs.getTab( 'Report_' + name );

            if( 'undefined' == typeof tab )
            {
                        tab = this.tabs.addTab( 'Report_' + name, name, '<IFRAME ID="'+name+'_iframe" border="0" class="report_iframe" SRC="'+ url +'">', true );
                        tab.onActivate.subscribe( this.sizeActiveTab, this, true );
                        tab.on('beforeclose',function()
                        					{ 
                        						var rep_main = document.getElementById('report_backend'); 
                        						var iframe = document.getElementById(name+'_iframe'); 
                        						rep_main.src=iframe.src + "&clear_session=true"; 
                        					});
            }
            tab.activate();
            return false;
    }
    ,
    sizeActiveTab :function ()
    {
    	//this.tabs.getActiveTab().bodyEl.setHeight( YAHOO.util.Dom.getViewportHeight() - this.tabs.getActiveTab().bodyEl.getTop() );
    	//getEl( 'tabs1').setHeight( '570px' );
    }
    ,
    addHelpTab : function( url )
    {
            var tab = this.tabs.getTab( 'Help' );
            var tabPanel = this.tabs;

            if( 'undefined' == typeof tab )
            {
                        tab = this.tabs.addTab( 'Help', 'Help', '', true );
                        
                        // We're just setting the URL here, and we'll grab it later.
                        tab.helpUrl = url;
                        tab.onActivate.subscribe( this.sizeActiveTab, this, true );
            }
            return false;
    }

}
YAHOO.ext.EventManager.onDocumentReady(Tabs.init, Tabs, true);
YAHOO.ext.EventManager.onWindowResize(Tabs.sizeActiveTab, Tabs, true);

var tree;
function treeInit() {
	tree = new YAHOO.widget.TreeView("treeDiv1");

	var root = tree.getRoot(); 

	for( var group in tree_data )
	{
		var groupNode = new YAHOO.widget.TextNode( group, root, false );
		for( var index in tree_data[ group ])
		{
			//new YAHOO.widget.HTMLNode('<a target="_blank" href="' + tree_data[ group ][ index ][ 'url' ] + '"><img class="tab_image" alt="Open In Tab" src="image/tab.gif" onclick="return Tabs.openInTab(\''+ tree_data[ group ][ index ].name +'\', \''+ tree_data[ group ][ index ].url +'\');" />' + tree_data[ group ][ index ][ 'name' ] + '</a><span>' + tree_data[ group ][ index ][ 'description' ] + '</span>', groupNode, false); 
			var linkinfo = '<table cellpadding=0 cellspacing=0 border=0><tr><td nowrap width=0%>';
			linkinfo += '<a href="#" style="text-align:left;" onclick="return Tabs.openInTab(\''+ tree_data[ group ][ index ].name +'\', \''+ tree_data[ group ][ index ].url +'\');" />' + tree_data[ group ][ index ][ 'name' ] + "</a>&nbsp;";
			linkinfo += '</td><td width=100% style="text-align:left;">';
			linkinfo += '<a href="#" onClick="LoadHelpScreen(\''+ tree_data[ group ][ index ][ 'name' ] +'\',\''+ tree_data[ group ][ index ][ 'description' ] +'\');"/>[details]</a>';
			linkinfo += '</td></tr></table>';
			new YAHOO.widget.HTMLNode(linkinfo, groupNode, false); 
		}
	}

	tree.draw();
}
treeInit();

/*
   	tabchange : (YAHOO.ext.TabPanel this, YAHOO.ext.TabPanelItem activePanel)  	TabPanel
Fires when the active tab changes
*/

var filter = 
{
	init: function()
	{
        this.reportFilter( '' );
	},

	reportFilter: function( keyword_input )
	{
		var result = [];
		var match = false;
		var keyword_array = keyword_input.toLowerCase().split( ' ' );

		for( var i in map_data )
		{
			for( var key_index = 0; key_index < keyword_array.length; key_index++)
			{
				// Skip empty strings in arrays bigger
				if( keyword_array.length > 1 && keyword_array[ key_index ] == '' )
				{
					break;
				}

				match = false;

				// Do we match name, description, url or company
				if( map_data[ i ].name.toLowerCase().indexOf( keyword_array[ key_index ] ) != -1 )
				{
					match = true;
				}


				// If we match add to result and stop looking at any more keywords
				if( match )
				{
					result[ result.length ] = map_data[ i ];
					break;
				}
			}
		}

		var result_html = '<table align="left" cellpadding=0 cellspacing="2" width=100% border=0>';
		var odd = true;
		var color = '#EFF7FF';
		result_html += '<tr style="background-color:white">';
		result_html += '<td style="text-align: left; float:left;width:400px;" nowrap align=center><b>Report Name</b>';			
		result_html += '</td><td width="100%" >';
		result_html += '<b>Report Type</b></td></tr>';			
		for( var i in result )
		{
			//result_html += '<tr style="background-color:'+ color +'"><td width="20%" ><a target="_blank" href="'+ result[i].url +'"><img class="tab_image" alt="Open In Tab" src="image/tab.gif" onclick="return Tabs.openInTab(\''+ result[i].name +'\', \''+ result[i].url +'\');" />'+ result[i].name +'</a></td><td width="60%" >'+ result[i].description + ' </td><td width="20%" style="padding-left:5px;" >'+ result[i].company +'</td></tr>';
			result_html += '<tr style="background-color:'+ color +'">';
			result_html += '<td style="text-align: left; float:left;width:400px; style="float:left;" nowrap align=left>';
			result_html += '<a href="#" style="text-align:left;background-color:'+ color +';"  onclick="return Tabs.openInTab(\''+ result[i].name +'\', \''+ result[i].url +'\');" />';
			result_html += result[i].name;
			result_html += '</a></td><td width="100%" >';
			result_html += result[i].category;
			result_html += '</td></tr>';			
			if( odd )
			{
				odd = false;
				color = '#FFFFFF'
			}
			else
			{
				odd = true;
				color = '#EFF7FF';
			}
		}
		result_html += '</table>';
		
		getEl( 'search_result' ).dom.innerHTML = result_html;
	}
}
YAHOO.ext.EventManager.onDocumentReady(filter.init, filter, true);

// Create the Report Details Block
var viewReportDetails = function()
{
    var dialog, showBtn;
    
    return {
        init : function()
        {
             showBtn = getEl('show-dialog-btn');
             showBtn.on('click', this.showDialog, this, true);
        },
        
        showDialog : function(){
            if(!dialog)
            {
                dialog = new YAHOO.ext.BasicDialog("report-help-dlg", { 
		        	modal:false,
		        	autoTabs:true,
		        	width:500,
		        	height:300,
		        	shadow:true,
		        	minWidth:300,
		        	minHeight:300
					});
                dialog.addKeyListener(27, dialog.hide, dialog);
            }
            dialog.show(showBtn.dom);
        }
    };
}();
YAHOO.ext.EventManager.onDocumentReady(viewReportDetails.init, viewReportDetails, true);

