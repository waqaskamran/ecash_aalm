
var Tabs = {
	tabs : '',
    init : function(){
        // basic tabs 1, built from existing content
        this.tabs = new YAHOO.ext.TabPanel( 'tabs1' );
        this.tabs.resizeTabs = true;
        
        tab1 = this.tabs.addTab( 'tree', "Reports" );
        //tab1.onActivate.subscribe(this.sizeActiveTab, this, true );
        this.tabs.activate('tree');
        
        tab2 = this.tabs.addTab( 'search', " Report Search" );
		tab2.onActivate.subscribe(this.sizeActiveTab, this, true );
    }
    ,
    openInTab : function( name, url )
    {
            var tab = this.tabs.getTab( 'Report_' + name );

            if( 'undefined' == typeof tab )
            {
                        tab = this.tabs.addTab( 'Report_' + name, name, '<IFRAME border="0" class="report_iframe" SRC="'+ url +'">', true );
                        tab.onActivate.subscribe( this.sizeActiveTab, this, true );
            }
            tab.activate();
            return false;
    }
    ,
    sizeActiveTab :function ()
    {
    	//this.tabs.getActiveTab().bodyEl.setHeight( YAHOO.util.Dom.getViewportHeight() - this.tabs.getActiveTab().bodyEl.getTop() );
    	getEl( 'tabs1').setHeight( '570px' );
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
			new YAHOO.widget.HTMLNode('<img class="tab_image" alt="Open In Tab" src="image/tab.gif" onclick="return Tabs.openInTab(\''+ tree_data[ group ][ index ].name +'\', \''+ tree_data[ group ][ index ].url +'\');" />' + tree_data[ group ][ index ][ 'name' ], groupNode, false); 
		}
	}

	tree.draw();
}
treeInit();

/*
   	tabchange : (YAHOO.ext.TabPanel this, YAHOO.ext.TabPanelItem activePanel)  	TabPanel
Fires when the active tab changes
*/

var filter = {
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
				else if( map_data[ i ].description.toLowerCase().indexOf( keyword_array[ key_index ] ) != -1 )
				{
					match = true;
				}
				else if( map_data[ i ].url.toLowerCase().indexOf( keyword_array[ key_index ] ) != -1 )
				{
					match = true;
				}
				else if( map_data[ i ].company.toLowerCase().indexOf( keyword_array[ key_index ] ) != -1 )
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

		// I'd like to do this some other way but it's works for right now
		var result_html = '<table align="left" cellpadding=0 cellspacing="2" width=100%>';
		var odd = true;
		var color = '#EFF7FF';
		for( var i in result )
		{
			//result_html += '<tr style="background-color:'+ color +'"><td width="20%" ><a target="_blank" href="'+ result[i].url +'"><img class="tab_image" alt="Open In Tab" src="image/tab.gif" onclick="return Tabs.openInTab(\''+ result[i].name +'\', \''+ result[i].url +'\');" />'+ result[i].name +'</a></td><td width="60%" >'+ result[i].description + ' </td><td width="20%" style="padding-left:5px;" >'+ result[i].company +'</td></tr>';
			result_html += '<tr style="background-color:'+ color +'"><td width="20%" style="float:left;" nowrap align=left><img class="tab_image" alt="Open In Tab" src="image/tab.gif" onclick="return Tabs.openInTab(\''+ result[i].name +'\', \''+ result[i].url +'\');" />'+ result[i].name +'</a></td><td width="100%" >'+ result[i].company + ' </td></tr>';			
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