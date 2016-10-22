/**
  * Custom Drag & Drop Tutorial
  * by Jozef Sakalos, aka Saki
  * http://extjs.com/learn/Tutorial:Custom_Drag_and_Drop_Part_1
  */
 
Ext.override(Ext.dd.DDProxy, {
    startDrag: function(x, y) {
        var dragEl = Ext.get(this.getDragEl());
        var el = Ext.get(this.getEl());
 
        dragEl.applyStyles({border:'','z-index':2000});
        dragEl.update(el.dom.innerHTML);
        dragEl.addClass(el.dom.className + ' dd-proxy');
    }
/*	,
	onDragOver: function(e, targetId) {
	    //console.log('dragOver: ' + targetId);
	    if('dd1-ct' === targetId || 'dd2-ct' === targetId) {
	        var target = Ext.get(targetId);
	        this.lastTarget = target;
	        target.addClass('dd-over');
	    }
	},
	onDragOut: function(e, targetId) {
	    //console.log('dragOut: ' + targetId);
	    if('dd1-ct' === targetId || 'dd2-ct' === targetId) {
	        var target = Ext.get(targetId);
	        this.lastTarget = null;
	        target.removeClass('dd-over');
	    }
	}
*/
});
 
// reference local blank image
Ext.BLANK_IMAGE_URL = 'lib/ext/resources/images/default/s.gif';
 
Ext.namespace('Tutorial');
 
// create application
Tutorial.dd = function() {
 
    // public space
    return {
 
        // public methods
        init: function() {
 
	    // drop zones
	    var dz1 = new Ext.dd.DropZone('dd1-ct', {ddGroup:'group'});
	    var dz2 = new Ext.dd.DropTarget('dd2-ct', {ddGroup:'group'});
dz2.onDragOver = function(){console.log('aaa')};
	 
	    // container 1
	    var dd11 = Ext.get('dd1-item1');
	    dd11.dd = new Ext.dd.DDProxy('dd1-item1', 'group');
dd11.dd.onDragEnter = function(){console.log('aaa')};
	    var dd12 = Ext.get('dd1-item2');
	    dd12.dd = new Ext.dd.DDProxy('dd1-item2', 'group');

	    var dd13 = Ext.get('dd1-item3');
	    dd13.dd = new Ext.dd.DDProxy('dd1-item3', 'group');
	 
	 
	    // container 2
	    var dd21 = Ext.get('dd2-item1');
	    dd21.dd = new Ext.dd.DDProxy('dd2-item1', 'group');
	 
	    var dd22 = Ext.get('dd2-item2');
	    dd22.dd = new Ext.dd.DDProxy('dd2-item2', 'group');
	 
	    var dd23 = Ext.get('dd2-item3');
	    dd23.dd = new Ext.dd.DDProxy('dd2-item3', 'group');
 
        }
    };
}(); // end of app
 
// end of file