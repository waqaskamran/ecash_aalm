// SCRIPT FILE documents.js

function sendDocumentList(form)
{
	this.form = form;	

	this.setDocumentList = function(list) {
		this.allDocs = list.split(",");
		this.allDocs.sort();

		this.selCondor = new Array();
		this.selCopia =  new Array();
		this.selEmail =  new Array();
		this.selFax = new Array();
		
		this.masked = new Array();
		
		this.selected = new Array();
		
	}

	this.setCondorList = function(list) {
		this.condor = list.split(",");
	}

	this.setCopiaList = function(list) {
		this.copia = list.split(",");
	}

	this.setEmailList = function(list) {
		this.email = list.split(",");
	}

	this.setFaxList = function(list) {
		this.fax = list.split(",");
	}

	this.in_array = function(needle,haystack) {
		for (var k = 0; k < haystack.length; k++) {
			if (haystack[k] == needle) {
				return true;
			}
		}
	
		return false;		
			
	}
	
	this.sortNumber = function(a,b) {
		return a - b;
	}

	this.array_intersect = function(array1, array2) {
		var common = new Array();
		array1.sort(this.sortNumber);
		array2.sort(this.sortNumber);

		for (var i = 0, n = array1.length, j = 0, k = array2.length; (i < n) && (j < k); i ++) {
		
			if (array1[i] == array2[j]) {
				common.push(array1[i]);
    			j ++;
  			} else if (array1[i] > array2[j]) {
		    	j ++;
    			i --;
  			}
  			
		}

		return common;
	
	}
	
	this.array_diff = function(array1, array2) {
		var diff = new Array();

		array1.sort(this.sortNumber);
		array2.sort(this.sortNumber);

		for ( i = 0 ; i < array1.length ; i++ ) {
			if (!this.in_array(array1[i],array2)) {
				diff.push(array1[i]);
			}
		}

		return diff;
		
	}
	
	this.array_merge = function(array1, array2) {
		var diff = this.array_diff(array2,array1);

		return array1.concat(diff);
		
	}
	
	this.array_search = function(needle, haystack) {
		for ( i = 0 ; i < haystack.length ; i++ ) {
			if(haystack[i] == needle) {
				return i;
			}
		}
	}
	
	this.isOn = function(element) {
		return element.checked;
	}

	this.getDocIdFromElement = function(element) {
		for ( i = 0 ; i < this.allDocs.length ; i++ ) {
			var name = 'document_list[' + this.allDocs[i] + ']';

			if(element.name == name) {
				return this.allDocs[i];
			}
			
		}		
		
	}

	this.fieldMask = function(doc_id) {
		var mask = new Array();	

		if (this.in_array(doc_id,this.copia)) {
			mask = this.array_merge(mask,this.condor);
		} else {
			mask = this.array_merge(mask,this.copia);
		}

		if (this.in_array(doc_id,this.email)) {
			mask = this.array_merge(mask,this.fax);
		} else if (this.in_array(doc_id,this.fax)) {
			mask = this.array_merge(mask,this.email);
		}
		
		return mask;
	
	}
	
	this.pushMaskStack = function(doc_id) {

		if (this.in_array(doc_id,this.copia)) {
			this.selCopia.push(doc_id);
		} else {
			this.selCondor.push(doc_id)
		}

		if (this.in_array(doc_id,this.email)) {
			this.selEmail.push(doc_id);
		} else if (this.in_array(doc_id,this.fax)) {
			this.selFax.push(doc_id);
		}

		this.selected.push(doc_id);

	}
	
	this.popMaskStack = function(doc_id) {

		if (this.in_array(doc_id,this.copia)) {
			this.selCopia.splice(this.array_search(doc_id,this.selCopia),1);
		} else {
			this.selCondor.splice(this.array_search(doc_id,this.selCondor),1)
		}

		if (this.in_array(doc_id,this.email)) {
			this.selEmail.splice(this.array_search(doc_id,this.selEmail),1);
		} else if (this.in_array(doc_id,this.fax)) {
			this.selFax.splice(this.array_search(doc_id,this.selFax),1);
		}

		this.selected.splice(this.array_search(doc_id,this.selected),1);

	}


	this.enableFields = function(list) {
		for ( i = 0 ; i < list.length ; i++ ) {
			var name = 'document_list[' + list[i] + ']';

			for ( j = 0 ; j < this.form.elements.length ; j++ ) { 
				if ( this.form.elements[j].name == name ) {
					this.form.elements[j].disabled = false;
				}
			}
		}	
	
	}

	this.disableFields = function(list) {
		for ( i = 0 ; i < list.length ; i++ ) {
			var name = 'document_list[' + list[i] + ']';

			for ( j = 0 ; j < this.form.elements.length ; j++ ) { 
				if ( this.form.elements[j].name == name ) {
					this.form.elements[j].checked = false;
					this.form.elements[j].disabled = true;
				}
			}
		}	
	}

	this.toggle = function(element) {
		var doc_id = this.getDocIdFromElement(element);

		if(this.isOn(element)) {
			var mask = this.fieldMask(doc_id);
			this.pushMaskStack(doc_id);
			this.disableFields(mask);
			this.array_merge(this.masked, mask);		
			
		} else {
			var mask = new Array();
			this.popMaskStack(doc_id);	

			for(var l = 0 ; l < this.selected.length ; l++) {
				mask  = this.array_merge(mask,this.fieldMask(this.selected[l]));
			}
		
			this.enableFields(this.allDocs);
			this.disableFields(mask);
								
		}
	
		this.maskSubmit();
		
		//alert('Condor: ' + this.selCondor + '\nCopia: ' + this.selCopia + '\nEmail: ' + this.selEmail + '\nFax: ' + this.selFax);
		
		
	}

	this.maskSubmit = function() {
	
		for (var i = 0; i < this.form.elements.length; i++) { 
			if(!email_field && form.elements[i].name == 'customer_email') {		
				var email_field = form.elements[i];
			}	
			if(!fax_field && form.elements[i].name == 'phone_fax') {
				var fax_field = form.elements[i];
			}
			if(!email_button && form.elements[i].id == 'send_email') {
				var email_button = form.elements[i];
			}
			if(!fax_button && form.elements[i].id == 'send_fax') {
				var fax_button = form.elements[i];
			}
				
		}	

		if(email_button && this.array_intersect(this.selFax,this.fax).length > 0) {
			email_field.disabled = true;
			email_button.disabled = true;					
		} else if(email_button) {
			email_field.disabled = false;
			email_button.disabled = false;							
		}
		
		if (fax_button && this.array_intersect(this.selEmail,this.email).length > 0) {
			fax_field.disabled = true;
			fax_button.disabled = true;					
		} else if(fax_button) {
			fax_field.disabled = false;
			fax_button.disabled = false;							
		}

	}
	
	this.validateSelected = function(field) {
		if(this.selected.length < 1) {
			is_doc_selected = false;
		} else {
			is_doc_selected = true;
		}
		
		return verifyDestination(field.value);
		
	}


}

function receiveDocumentList(form)
{
	this.form = form;	

	this.setDocumentList = function(list) {
		this.allDocs = list.split(",");
		this.allDocs.sort();

		this.selCondor = new Array();
		this.selCopia =  new Array();
		
		this.masked = new Array();
		
		this.selected = new Array();
		
	}

	this.setCondorList = function(list) {
		this.condor = list.split(",");
	}

	this.setCopiaList = function(list) {
		this.copia = list.split(",");
	}

	this.in_array = function(needle,haystack) {
		for (var i = 0; i < haystack.length; i++) {
			if (haystack[i] == needle) {
				return true;
			}
		}
	
		return false;		
			
	}
	
	this.sortNumber = function(a,b) {
		return a - b;
	}

	this.array_intersect = function(array1, array2) {
		var common = new Array();
		array1.sort(this.sortNumber);
		array2.sort(this.sortNumber);

		for (var i = 0, n = array1.length, j = 0, k = array2.length; (i < n) && (j < k); i ++) {
			if (array1[i] == array2[j]) {
				common.push(array1[i]);
    			j ++;
  			} else {
		    	j ++;
    			i --;
  			}
  			
		}

		return common;
	
	}
	
	this.array_diff = function(array1, array2) {
		var diff = new Array();

		array1.sort(this.sortNumber);
		array2.sort(this.sortNumber);

		for ( i = 0 ; i < array1.length ; i++ ) {
			if (!this.in_array(array1[i],array2)) {
				diff.push(array1[i]);
			}
		}

		return diff;
		
	}
	
	this.array_merge = function(array1, array2) {
		var diff = this.array_diff(array2,array1);

		return array1.concat(diff);
		
	}
	
	this.array_search = function(needle, haystack) {
		for ( i = 0 ; i < haystack.length ; i++ ) {
			if(haystack[i] == needle) {
				return i;
			}
		}
	}
	
	this.isOn = function(element) {
		return element.checked;
	}

	this.getDocIdFromElement = function(element) {
		for ( i = 0 ; i < this.allDocs.length ; i++ ) {
			var name = 'document_list[' + this.allDocs[i] + ']';

			if(element.name == name) {
				return this.allDocs[i];
			}
			
		}		
		
	}

	this.fieldMask = function(doc_id) {
		var mask = new Array();	

		if (this.in_array(doc_id,this.copia)) {
			mask = this.array_merge(mask,this.condor);
		} else {
			mask = this.array_merge(mask,this.copia);
		}

		return mask;
	
	}
	
	this.pushMaskStack = function(doc_id) {

		if (this.in_array(doc_id,this.copia)) {
			this.selCopia.push(doc_id);
		} else {
			this.selCondor.push(doc_id)
		}

		this.selected.push(doc_id);

	}
	
	this.popMaskStack = function(doc_id) {

		if (this.in_array(doc_id,this.copia)) {
			this.selCopia.splice(this.array_search(doc_id,this.selCopia),1);
		} else {
			this.selCondor.splice(this.array_search(doc_id,this.selCondor),1)
		}

		this.selected.splice(this.array_search(doc_id,this.selected),1);

	}


	this.enableFields = function(list) {
		for ( i = 0 ; i < list.length ; i++ ) {
			var name = 'document_list[' + list[i] + ']';

			for ( j = 0 ; j < this.form.elements.length ; j++ ) { 
				if ( this.form.elements[j].name == name ) {
					this.form.elements[j].disabled = false;
				}
			}
		}	
	
	}

	this.disableFields = function(list) {
		for ( i = 0 ; i < list.length ; i++ ) {
			var name = 'document_list[' + list[i] + ']';

			for ( j = 0 ; j < this.form.elements.length ; j++ ) { 
				if ( this.form.elements[j].name == name ) {
					this.form.elements[j].checked = false;
					this.form.elements[j].disabled = true;
				}
			}
		}	
	}

	this.toggle = function(element) {
		var doc_id = this.getDocIdFromElement(element);

		if(this.isOn(element)) {
			var mask = this.fieldMask(doc_id);
			this.pushMaskStack(doc_id);
			this.disableFields(mask);
			this.array_merge(this.masked, mask);		
			
		} else {
			var mask = new Array();
			this.popMaskStack(doc_id);	

			for(var l = 0 ; l < this.selected.length ; l++) {
				mask  = this.array_merge(mask,this.fieldMask(this.selected[l]));
			}
		
			this.enableFields(this.allDocs);
			this.disableFields(mask);
								
		}
	
		this.maskSubmit();
		
		//alert('Doc: ' + doc_id + '\nCopia: ' + this.selCopia + ': ' + this.copia + '\nCondor: ' + this.selCondor + ': ' + this.condor);

		
		
	}

	this.maskSubmit = function() {

		if(this.array_intersect(this.selCopia,this.copia).length > 0 ) {
			document.getElementById('layout0group1layer2edit_copia').style.display = 'block' ;
			document.getElementById('layout0group1layer2edit_condor').style.display = 'none' ;
		} else if (this.array_intersect(this.selCopia,this.copia).length == 0 ) {
			document.getElementById('layout0group1layer2edit_copia').style.display = 'none' ;
		}

		if(this.array_intersect(this.selCondor,this.condor).length > 0 ) {
			document.getElementById('layout0group1layer2edit_condor').style.display = 'block' ;
			document.getElementById('layout0group1layer2edit_copia').style.display = 'none' ;
		} else if (this.array_intersect(this.selCondor,this.condor).length == 0 ) {
			document.getElementById('layout0group1layer2edit_condor').style.display = 'none' ;
		}

		if(this.selected.length < 1) {
			document.getElementById('doc_send_recv_submit').disabled = true;
		} else {
			document.getElementById('doc_send_recv_submit').disabled = false;		
		}

	}

	this.validateSelected = function(field) {
	
		if(this.selected.length < 1) {
			is_doc_selected = false;
		} else {
			is_doc_selected = true;
		}
		
		verifyDestination(field.value);
		
	}
}


