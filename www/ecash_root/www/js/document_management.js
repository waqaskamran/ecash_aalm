<script type='text/javascript'>
var DocumentList;
var EditedDocumentList = new Array();
var DocumentOptions = new Array();
var NewDocumentList = new Array();
var PackageList;
var EditedPackageList = new Array();
var PackageOptions = new Array();
var NewPackageList = new Array();
var EditedSortingList = new Array();
var SortingOptions = new Array();
var NewSortingList = new Array();
var CondorDocs;

function JSON_RPC(request) {
	if ('undefined' != typeof(Ajax)) {
		var req = new Ajax.Request(request.url, {
				parameters: Object.toJSON({
					method:request.method,
					id:request.id,
					params:request.params,
				}),
				onFailure: request.onFailure,
				onSuccess: request.onSuccess,
				contentType: "text/x-json"
			});
		return req;
	}
	return null;
}

function ecash_JSON_RPC(request) {
	request.url = '/?api=json-rpc';
	return JSON_RPC(request);

}
function ecash_reference(request) {
	request.method = 'Reference';
	return ecash_JSON_RPC(request)
}
function ecash_documents(request) {
	request.method = 'Documents';
	return ecash_JSON_RPC(request)
}
function document_ini()
{
	getCompanies();
	getCondorList();
}
function sort_ini()
{
	getCompanies();
	//getCondorList();
}
function package_ini()
{
	getCompanies();
	getCondorList();
}
/*
Ajax call to fetch list of Condor documents
*/
function getCondorList()
{
	var req = ecash_documents({
		id: 0,
		params:[{   
			action:'documents',
			function:'getCondorList'

		}], 
		onSuccess: function(transport){
			
			var result = transport.responseText.parseJSON();
			if (result) {
				loadCondorList(result.result);

			} else {
				error_overlay(transport.responseText);
			}
		},
		onFailure: function (transport) {
			alert('ajax fail');
		}
	});

}
/*
callback function for getCondorList
*/
function loadCondorList(documents)
{
	if(document.getElementById('mode').value == 'Document')	
	{
		var select = document.getElementById('dm_condor_list');

		select.length = 0;
		select.options[0] = new Option("No Condor Document", 0);
		var i = 1;	
		for(var document_id in documents)
		{	
			//parseJSon likes to attach a function to the array
			if(typeof(documents[document_id]) != 'function')
			{
				select.options[i] = new Option(documents[document_id], documents[document_id]);
				i++;
			}
		}
	}
	CondorDocs = documents;

}

/*
Ajax call to fetch companies
*/
function getCompanies() {
	var req = ecash_reference({
			id: 0,
			params:[{   
				action:'reference',
				function:'getCompanies'
			}], 
			onSuccess: function(transport){
				var result = transport.responseText.parseJSON();
				if (result) {
						loadCompanies(result.result);

				} else {
					error_overlay(transport.responseText);
				}
			},
			onFailure: function (transport) {
				alert('ajax fail');
			}
		});
}
/*
Callback function for the ajax call to fetch companies
*/
function loadCompanies(companies)
{
	lockall();
	var select = document.getElementById('company');
	select.length = 0;
	select.options[0]= new Option("(Global)", "0"); 
	var i = 1;	

	for(var company in companies)
	{	
		//parseJSon likes to attach a function to the array
		if(typeof(companies[company]) == 'string')
		{
			select.options[i] = new Option(companies[company], company);
			i++;
		}
	}
	getLoanTypes(0);
}
/*
Will fetch loan type for selected company
*/
function getLoanTypes(company)
{
	if(checkForNeworEdited(document.getElementById('mode').value))
		return false;

	lockall();
	var req = ecash_reference({
		id: 0,
		params:[{   
			action:'reference',
			function:'getLoanTypesByCompany',
			company_id: company
		}], 
		onSuccess: function(transport){
			var result = transport.responseText.parseJSON();
			if (result) {
				loadLoanTypes(result.result);

			} else {
				error_overlay(transport.responseText);
			}
		},
		onFailure: function (transport) {
			alert('ajax fail');
		}
	});
	return true;
}
/*
Will populate the loan type array
*/
function loadLoanTypes(loanTypes)
{
	var select = document.getElementById('loan_type');
	select.length = 0;
	select.options[0]= new Option("Company Specific", "0"); 
	var i = 1;	
	for(var loanType in loanTypes)
	{	
		//parseJSon likes to attach a function to the array
		if(typeof(loanTypes[loanType]) == 'string')
		{
			select.options[i] = new Option(loanTypes[loanType], loanType);
			i++;
		}
	}
	if(select.length > 1)
		document.getElementById('loantypediv').style.display = 'block';
	else
		document.getElementById('loantypediv').style.display = 'none';
	
	if(document.getElementById('mode').value == 'Document')
	{
		getDocuments();
	}	
	else if(document.getElementById('mode').value == 'Package')
	{
		getPackages();
		getDocuments();
	}
	else if(document.getElementById('mode').value == 'Sorting')
	{
		getSorted();
	}
}
/*
Ajax call to fetch list of sorted ECash Documents
*/
function getSorted()
{

	var sendselect = document.getElementById('dm_send_sort_list');
	var receiveselect = document.getElementById('dm_recv_sort_list');
	sendselect.length = 0;	
	sendselect.options[0] = new Option('Fetching Documents...', 0);
	sendselect.disabled = true;
	receiveselect.length = 0;	
	receiveselect.options[0] = new Option('Fetching Documents...', 0);
	receiveselect.disabled = true;

	var company = document.getElementById('company').options[document.getElementById('company').selectedIndex].value;
	var loanType = document.getElementById('loan_type').options[document.getElementById('loan_type').selectedIndex].value;

	var req = ecash_documents({
		id: 0,
		params:[{   
			action:'documents',
			function:'getSorted',
			company_id: company,
			loan_type_id: loanType
		}], 
		onSuccess: function(transport){
			
			var result = transport.responseText.parseJSON();
			if (result) {
				loadSorted(result.result);

			} else {
				error_overlay(transport.responseText);
			}
		},
		onFailure: function (transport) {
			alert('ajax fail');
		}
	});

	return true;
}
/*
callback to load Sorted ECash Document Lists
*/
function loadSorted(documents)
{
	var sendselect = document.getElementById('dm_send_sort_list');
	var receiveselect = document.getElementById('dm_recv_sort_list');

	sendselect.length = 0;
	var i = 0;	
	for(var doc_id in documents['send'])
	{	
		//parseJSon likes to attach a function to the array
		if(typeof(documents['send'][doc_id]) != 'function')
		{
			sendselect.options[i] = new Option(documents['send'][doc_id]['name_short'], doc_id);
			i++;
		}
	}
	sendselect.disabled = false;
	
	receiveselect.length = 0;
	var i = 0;	
	for(var doc_id in documents['receive'])
	{	
		//parseJSon likes to attach a function to the array
		if(typeof(documents['receive'][doc_id]) != 'function')
		{
			receiveselect.options[i] = new Option(documents['receive'][doc_id]['name_short'], doc_id);
			i++;
		}
	}
	receiveselect.disabled = false;

}
/*
Ajax call to fetch list of ECash Documents
*/
function getDocuments()
{
	if(checkForNeworEdited('Document'))
		return false;

	var select = document.getElementById('dm_document_list');
	
	select.length = 0;	
	select.options[0] = new Option('Fetching Documents...', 0);
	select.disabled = true;

	if(document.getElementById('mode').value != 'Sorting')	
	{	
		var altselect = document.getElementById('dm_sp_altbody');
		altselect.length = 0;
		altselect.options[0] = new Option('Fetching Documents...', 0);
		altselect.disabled = true;
	}
	lockall();
	var company = document.getElementById('company').options[document.getElementById('company').selectedIndex].value;
	var loanType = document.getElementById('loan_type').options[document.getElementById('loan_type').selectedIndex].value;
	var req = ecash_documents({
		id: 0,
		params:[{   
			action:'documents',
			function:'getDocuments',
			company_id: company,
			loan_type_id: loanType
		}], 
		onSuccess: function(transport){
			
			var result = transport.responseText.parseJSON();
			if (result) {
				loadDocuments(result.result);

			} else {
				error_overlay(transport.responseText);
			}
		},
		onFailure: function (transport) {
			alert('ajax fail');
		}
	});

	return true;
}
/*
Ajax call to fetch list of ECash Packages
*/
function getPackages()
{
	if(checkForNeworEdited('Package'))
		return false;

	var select = document.getElementById('dm_package_list');
	
	select.length = 0;	
	select.options[0] = new Option('Fetching Packages...', 0);
	select.disabled = true;

	lockall();
	var company = document.getElementById('company').options[document.getElementById('company').selectedIndex].value;
	var loanType = document.getElementById('loan_type').options[document.getElementById('loan_type').selectedIndex].value;
	var req = ecash_documents({
		id: 0,
		params:[{   
			action:'documents',
			function:'getPackages',
			company_id: company,
			loan_type_id: loanType
		}], 
		onSuccess: function(transport){
			
			var result = transport.responseText.parseJSON();
			if (result) {
				loadPackages(result.result);

			} else {
				error_overlay(transport.responseText);
			}
		},
		onFailure: function (transport) {
			alert('ajax fail');
		}
	});

	return true;
}
/*
callback to load ECash packages
*/
function loadPackages(packages)
{
	var select = document.getElementById('dm_package_list');

	select.length = 0;
	var i = 0;	
	for(var package_id in packages)
	{	
		//parseJSon likes to attach a function to the array
		if(typeof(packages[package_id]) != 'function')
		{
			select.options[i] = new Option(packages[package_id]['name'], package_id);
	
			PackageOptions[package_id] = select.options[i];
			i++;
		}
	}
	PackageList = packages;
	select.disabled = false;

}
/*
callback to load ECash Documents
*/
function loadDocuments(documents)
{

	var select = document.getElementById('dm_document_list');

	select.length = 0;
	var i = 0;	

	for(var document_id in documents)
	{	
		//parseJSon likes to attach a function to the array
		if(typeof(documents[document_id]) != 'function')
		{
			if(in_array(documents[document_id]['name'], CondorDocs) || documents[document_id]['only_receivable'] == 'yes')
				select.options[i] = new Option(documents[document_id]['name_short'], document_id);
			else
				select.options[i] = new Option(documents[document_id]['name_short'] + ' (*Not in Condor*)', document_id);	
			DocumentOptions[document_id] = select.options[i];
			i++;
		}
	}

	DocumentList = documents;
	select.disabled = false;
	if(document.getElementById('mode').value != 'Sorting')	
		loadAltDocSelect(documents);


}
function in_array( what, where ){
	var a=false;
	for(var i=0;i<where.length;i++){
	  if(what == where[i]){
	    a=true;
        break;
	  }
	}
	return a;
}
function loadAltDocSelect(documents)
{
	var select = document.getElementById('dm_sp_altbody');
	var i = 1;
	select.length = 0;
	select.options[0] = new Option('None', 0);
	for(var document_id in documents)
	{	
		//parseJSon likes to attach a function to the array
		if(typeof(documents[document_id]) != 'function')
		{
			if(in_array(documents[document_id]['name'], CondorDocs) || documents[document_id]['only_receivable'] == 'yes')
				select.options[i] = new Option(documents[document_id]['name_short'], document_id);
			else
				select.options[i] = new Option(documents[document_id]['name_short'] + ' (*Not in Condor*)', document_id);	
			DocumentOptions[document_id] = select.options[i];
			i++;
		}
	}
	select.disabled = false;
}
/*
Load and display a selected Package
*/
function loadPackage(id)
{
	var updatepackagebutton = document.getElementById('updatepackage');
	var revertpackagebutton = document.getElementById('revertpackage');
	var deletepackagebutton = document.getElementById('deletepackage');
	deletepackagebutton.style.display = 'block';
	var packagediv = document.getElementById('package_panel');
	var flagdiv = document.getElementById('flag_panel');
	var configdiv = document.getElementById('config_panel');

	packagediv.style.display = 'block';
	flagdiv.style.display = 'block';
	configdiv.style.display = 'block';
	//add check if it is new or edited
	if(NewPackageList[id])
	{
		var currentpackage = NewPackageList[id];
		updatepackagebutton.style.display = 'block';
	}
	else if(EditedPackageList[id])
	{
		var currentpackage = EditedPackageList[id];
		updatepackagebutton.style.display = 'block';
		revertpackagebutton.style.display = 'block';
	}	
	else	
	{	
		var currentpackage = PackageList[id];
		updatepackagebutton.style.display = 'none';
		revertpackagebutton.style.display = 'none';
	}

	if(!setSelect('dm_sp_altbody', currentpackage['package_body_id']))
		setSelect('dm_sp_altbody', 0);
	document.getElementById('dm_pkg_name_short').value = currentpackage['name_short'];
	document.getElementById('dm_pkg_name').value = currentpackage['name'];

	var docSelect = document.getElementById('dm_docpkg_list');
	docSelect.options.length = 0;
	var i =0;
	for(var doc in currentpackage['docs'])
	{
		if(doc != null && typeof(currentpackage['docs'][doc]) != 'function')
		{
			docSelect.options[i] = new Option(currentpackage['docs'][doc]['name_short'], doc);
			i++;
		}	
	}

	if(currentpackage['active_status'] == 'active')
		document.getElementById('dm_chk_active').checked = true;
	else
		document.getElementById('dm_chk_active').checked = false;
	
}
/*
Load and display a selected Document
*/
function loadDocumentData(id)
{
	var updatedocumentbutton = document.getElementById('updatedocument');
	var revertdocumentbutton = document.getElementById('revertdocument');
	var deletedocumentbutton = document.getElementById('deletedocument');
	
	var documentdiv = document.getElementById('document_panel');
	var flagdiv = document.getElementById('flag_panel');
	var altbodydiv = document.getElementById('alt_body_panel');

	documentdiv.style.display = 'block';
	flagdiv.style.display = 'block';
	altbodydiv.style.display = 'block';
	//add check if it is new or edited
	if(NewDocumentList[id])
	{
		var currentdoc = NewDocumentList[id];
		updatedocumentbutton.style.display = 'block';
	}
	else if(EditedDocumentList[id])
	{
		var currentdoc = EditedDocumentList[id];
		updatedocumentbutton.style.display = 'block';
		revertdocumentbutton.style.display = 'block';
	}	
	else	
	{	
		var currentdoc = DocumentList[id];
		updatedocumentbutton.style.display = 'none';
		revertdocumentbutton.style.display = 'none';
	}

	if(currentdoc['can_delete'])
		deletedocumentbutton.style.display = 'block';
	else
		deletedocumentbutton.style.display = 'none';

	if(!setSelect('dm_condor_list', currentdoc['name']))
		setSelect('dm_condor_list', 0);
	document.getElementById('dm_doc_name_short').value = currentdoc['name_short'];

	if(currentdoc['send_method'])
	{
		delivery_methods = currentdoc['send_method'].split(',');
	}
	else
	{
		delivery_methods = Array();
	}
	resetSelect('dm_doc_sendmethod');
	for(var option in delivery_methods)
	{
		if(typeof(delivery_methods[option]) != 'function')
			setSelect('dm_doc_sendmethod', delivery_methods[option]);
	}

	if(currentdoc['active_status'] == 'active')
		document.getElementById('dm_chk_active').checked = true;
	else
		document.getElementById('dm_chk_active').checked = false;
	if(currentdoc['required'] == 'yes')
		document.getElementById('dm_chk_receivable').checked = true;
	else
		document.getElementById('dm_chk_receivable').checked = false;
	if(currentdoc['esig_capable'] == 'yes')
		document.getElementById('dm_chk_esig').checked = true;
	else
		document.getElementById('dm_chk_esig').checked = false;
	if(currentdoc['only_receivable'] == 'yes')
		document.getElementById('dm_chk_pseudodoc').checked = true;
	else
		document.getElementById('dm_chk_pseudodoc').checked = false;
	if(currentdoc['document_body_id'])
		setSelect('dm_sp_altbody', currentdoc['document_body_id']);
	else
		setSelect('dm_sp_altbody', 0);		
}
/*
onChange event for a Document
*/
function changeDocument()
{
	var name_short = document.getElementById('dm_doc_name_short');
	var sendselect = document.getElementById('dm_doc_sendmethod');
	var altselect = document.getElementById('dm_sp_altbody');
	var select = document.getElementById('dm_document_list');
	var doc_id = select.options[select.selectedIndex].value;
	var CondorSelect = document.getElementById('dm_condor_list');	
	var updatedocumentbutton = document.getElementById('updatedocument');
	var revertdocumentbutton = document.getElementById('revertdocument');
	var editedDocument = new Array();	
	if(NewDocumentList[doc_id])
	{
		var doc = NewDocumentList[doc_id];
		updatedocumentbutton.style.display = 'block';
	}
	else
	{
		var doc = DocumentList[doc_id];
		updatedocumentbutton.style.display = 'block';
		revertdocumentbutton.style.display = 'block';
	}


	editedDocument['name_short'] = name_short.value;
	editedDocument['company_id'] = doc['company_id'];
	editedDocument['loan_type_id'] = doc['loan_type_id'];
	editedDocument['name'] = CondorSelect.options[CondorSelect.selectedIndex].value;
	editedDocument['date_created'] = doc['date_created'];
	editedDocument['date_modified'] = doc['date_modified'];
	//set send method
	var send_method_array = Array();
	var i = 0;
	for(var option in sendselect.options)
	{
		if(sendselect.options[option].selected)
			send_method_array[i++] = sendselect.options[option].value;
	}
	
	editedDocument['send_method'] = send_method_array.join(',');
	//set active flag
	if(document.getElementById('dm_chk_active').checked)
		editedDocument['active_status'] = 'active';
	else
		editedDocument['active_status'] = 'inactive';
	//set is receivable flag
	if(document.getElementById('dm_chk_receivable').checked)
		editedDocument['required'] = 'yes';
	else
		editedDocument['required'] = 'no';
	//set esig flag
	if(document.getElementById('dm_chk_esig').checked)
		editedDocument['esig_capable'] = 'yes';
	else
		editedDocument['esig_capable'] = 'no';		
	//set pseudo document
	if(document.getElementById('dm_chk_pseudodoc').checked)
		editedDocument['only_receivable'] = 'yes';
	else
		editedDocument['only_receivable'] = 'no';	
	//set altbodyid
	editedDocument['document_body_id'] = altselect.options[altselect.selectedIndex].value;

	if(NewDocumentList[doc_id])
	{
		NewDocumentList[doc_id] = editedDocument;
	}
	else
	{
		EditedDocumentList[select.options[select.selectedIndex].value] = editedDocument;
		DocumentOptions[select.options[select.selectedIndex].value].text = '*' + name_short.value;
	}
}
/*
onChange event for a Package
*/
function changePackage()
{
	var name_short = document.getElementById('dm_pkg_name_short');
	var name = document.getElementById('dm_pkg_name');
	var bodyselect = document.getElementById('dm_sp_altbody');
	var select = document.getElementById('dm_package_list');
	var package_id = select.options[select.selectedIndex].value;
	var doclistSelect = document.getElementById('dm_docpkg_list');	
	var updatepackagebutton = document.getElementById('updatepackage');
	var revertpackagebutton = document.getElementById('revertpackage');
	var editedPackage = new Array();	
	if(NewPackageList[package_id])
	{
		var package = NewPackageList[package_id];
		updatepackagebutton.style.display = 'block';
	}
	else
	{
		var package = PackageList[package_id];
		updatepackagebutton.style.display = 'block';
		revertpackagebutton.style.display = 'block';
	}


	editedPackage['name_short'] = name_short.value;
	editedPackage['company_id'] = package['company_id'];
	editedPackage['loan_type_id'] = package['loan_type_id'];
	editedPackage['name'] = name.value;
	editedPackage['date_created'] = package['date_created'];
	editedPackage['date_modified'] = package['date_modified'];
	//set send method
	var document_array = Array();
	var i = 0;
	for(var option in doclistSelect.options)
	{
		if(doclistSelect.options[option] && doclistSelect.options[option].value)
			document_array[doclistSelect.options[option].value] = {'document_list_id':doclistSelect.options[option].value, 'name':doclistSelect.options[option].text, 'name_short': doclistSelect.options[option].text};
	}
	editedPackage['docs'] = document_array;
	editedPackage['package_body_id'] = bodyselect.options[bodyselect.selectedIndex].value;
	//set active flag
	if(document.getElementById('dm_chk_active').checked)
		editedPackage['active_status'] = 'active';
	else
		editedPackage['active_status'] = 'inactive';


	if(NewPackageList[package_id])
	{
		NewPackageList[package_id] = editedPackage;
	}
	else
	{
		EditedPackageList[select.options[select.selectedIndex].value] = editedPackage;
		PackageOptions[select.options[select.selectedIndex].value].text = '*' + name.value;
	}
}
/*
iterates through a select elemtn and sets the selected to an option with a given value
*/
function setSelect(element, value)
{
	var found = false;
	var select = document.getElementById(element);
	for (var item in select.options)
	{
		if(select.options[item] && select.options[item].value == value)
		{
			select.options[item].selected = true;
			found = true;		
		}
	}	
	return found;
}
function resetSelect(element)
{
	var select = document.getElementById(element);
	for (var item in select.options)
	{
		select.options[item].selected = false;

	}	
}
/*
Ajax call to save the edited or new document to the db
*/
function updateDocument()
{
	var select = document.getElementById('dm_document_list');
	var doc_id = select.options[select.selectedIndex].value;
	if(NewDocumentList[doc_id])
	{
		var doc = NewDocumentList[doc_id];
		doc_id = null;
	}
	else
	{
		var doc = EditedDocumentList[doc_id];	
	}	
	var req = ecash_documents({
		id: 0,
		params:[{   
			action:'documents',
			function:'updateDocument',
			document_list_id:doc_id,
			params: {
					name: doc['name'],
					company_id: doc['company_id'],
					loan_type_id: doc['loan_type_id'],
					send_method: doc['send_method'],
					name_short: escape(doc['name_short']),
					active_status: doc['active_status'],
					required: doc['required'],
					only_receivable: doc['only_receivable'],
					required: doc['required'],
					esig_capable: doc['esig_capable'],
					document_body_id: doc['document_body_id'],

				}
		}], 
		onSuccess: function(transport){
			var result = transport.responseText.parseJSON();
			if (result) {
				documentUpdated(result.result);

			} else {
				error_overlay(transport.responseText);
			}
		},
		onFailure: function (transport) {
			alert('ajax fail');
		}
	});

}
/*
Ajax call to save the edited or new document to the db
*/
function updatePackage()
{
	var select = document.getElementById('dm_package_list');
	var package_id = select.options[select.selectedIndex].value;
	if(NewPackageList[package_id])
	{
		var package = NewPackageList[package_id];
		package_id = null;
	}
	else
	{
		var package = EditedPackageList[package_id];	
	}	
	var req = ecash_documents({
		id: 0,
		params:[{   
			action:'documents',
			function:'updatePackage',
			document_package_id:package_id,
			params: {
					name: escape(package['name']),
					company_id: package['company_id'],
					loan_type_id: package['loan_type_id'],
					docs: package['docs'],
					name_short: escape(package['name_short']),
					active_status: package['active_status'],
					document_package_id: package_id,
					package_body_id: package['package_body_id'],


				}
		}], 
		onSuccess: function(transport){
			var result = transport.responseText.parseJSON();
			if (result) {
				packageUpdated(result.result);

			} else {
				error_overlay(transport.responseText);
			}
		},
		onFailure: function (transport) {
			alert('ajax fail');
		}
	});

}
/*
callback for updatePackage
updates PackageList and PackageOptions to reflect Package being saved
*/
function packageUpdated(result)
{
	if(result)
	{
		//need to remap all the entries that are by name, to by id

		if(NewPackageList[result['name_short']])
		{
			
			PackageList[result['id']] = NewPackageList[result['name_short']];
			PackageList[result['id']]['date_created'] = result['date_created'];
			PackageList[result['id']]['date_modified'] = result['date_modified'];
			delete NewPackageList[result['name_short']];
			PackageOptions[result['id']] = PackageOptions[result['name_short']];
			delete PackageOptions[result['name_short']];
			PackageOptions[result['id']].text = PackageList[result['id']]['name'];
			PackageOptions[result['id']].value = result['id']	
		}
		else
		{
			PackageList[result['id']] = EditedPackageList[result['id']];
			PackageList[result['id']]['date_created'] = result['date_created'];
			PackageList[result['id']]['date_modified'] = result['date_modified'];
			delete EditedPackageList[result['id']];
			PackageOptions[result['id']].text = PackageList[result['id']]['name'];
		}
		
		loadPackage(result['id']);		
	}	
	else
	{
		alert("update failure");
	}
}
/*
Ajax call to save the send sort order to the db
*/
function updateSendSort()
{
	var select = document.getElementById('dm_send_sort_list');
	var documents = new Array();	
	var i =0;
	for(var option in select.options)
	{
		//javascript hates associative array with numbers so changing to a string
		documents['' + select.options[option].index] = select.options[option].value;

	}

	var req = ecash_documents({
		id: 0,
		params:[{   
			action:'documents',
			function:'updateSendSort',
			params: {
					docs: documents

				}
		}], 
		onSuccess: function(transport){
			var result = transport.responseText.parseJSON();
			if (result) {
				sendSortUpdated(result.result);

			} else {
				error_overlay(transport.responseText);
			}
		},
		onFailure: function (transport) {
			alert('ajax fail');
		}
	});

}
function sortChange(ele)
{
	for(var option in ele.options)
	{
		ele.options[option].text = '*' + ele.options[option].text;
	}
	eval("document.getElementById('" + ele.id + "save').disabled=false;");
}
/*
Ajax call to save the receive sort order to the db
*/
function updateReceiveSort()
{
	var select = document.getElementById('dm_recv_sort_list');
	var documents = new Array();	
	var i =0;
	for(var option in select.options)
	{
		//javascript hates associative array with numbers so changing to a string
		documents['' + select.options[option].index] = select.options[option].value;

	}

	var req = ecash_documents({
		id: 0,
		params:[{   
			action:'documents',
			function:'updateReceiveSort',
			params: {
					docs: documents

				}
		}], 
		onSuccess: function(transport){
			var result = transport.responseText.parseJSON();
			if (result) {
				receiveSortUpdated(result.result);

			} else {
				error_overlay(transport.responseText);
			}
		},
		onFailure: function (transport) {
			alert('ajax fail');
		}
	});

}
/*
callback for updateReceiveSort

*/
function receiveSortUpdated(result)
{
	var select = document.getElementById('dm_recv_sort_list');
	var save = document.getElementById('dm_recv_sort_listsave');	
	save.disabled = true;
	for(var option in select.options)
	{
		select.options[option].text = select.options[option].text.substring(1);
	}
}
/*
callback for updateSendSort

*/
function sendSortUpdated(result)
{
	var select = document.getElementById('dm_send_sort_list');
	var save = document.getElementById('dm_send_sort_listsave');	
	save.disabled = true;
	for(var option in select.options)
	{
		select.options[option].text = select.options[option].text.substring(1);
	}
}
/*
callback for updateDocument
updates DocumentList and DocumentOptions to reflect document being saved
*/
function documentUpdated(result)
{
	if(result)
	{
		//need to remap all the entries that are by name, to by id

		if(NewDocumentList[result['name_short']])
		{
			
			DocumentList[result['id']] = NewDocumentList[result['name_short']];
			DocumentList[result['id']]['date_created'] = result['date_created'];
			DocumentList[result['id']]['date_modified'] = result['date_modified'];
			delete NewDocumentList[result['name_short']];
			DocumentOptions[result['id']] = DocumentOptions[result['name_short']];
			delete DocumentOptions[result['name_short']];
			DocumentOptions[result['id']].text = DocumentList[result['id']]['name_short'];
			DocumentOptions[result['id']].value = result['id']	
		}
		else
		{
			DocumentList[result['id']] = EditedDocumentList[result['id']];
			DocumentList[result['id']]['date_created'] = result['date_created'];
			DocumentList[result['id']]['date_modified'] = result['date_modified'];
			delete EditedDocumentList[result['id']];
			DocumentOptions[result['id']].text = DocumentList[result['id']]['name_short'];
		}
		
		loadDocumentData(result['id']);	
	}	
	else
	{
		alert("update failure");
	}
}
/*
reverts an edited token back to the saved values
*/
function revertDocument()
{
	var select = document.getElementById('dm_document_list');
	var doc_id = select.options[select.selectedIndex].value;
	var doc = DocumentList[select.options[select.selectedIndex].value];
	DocumentOptions[doc_id].text = doc['name_short'];	
	delete EditedDocumentList[doc_id];
	loadDocumentData(doc_id);

}
/*
reverts an edited token back to the saved values
*/
function revertPackage()
{
	var select = document.getElementById('dm_package_list');
	var package_id = select.options[select.selectedIndex].value;
	var package = PackageList[select.options[select.selectedIndex].value];
	PackageOptions[package_id].text = package['name'];	
	delete EditedPackageList[package_id];
	loadPackage(package_id);

}
/*
Checks if any New ord Edited Tokens exist and if so prompts the user to verify his actions
*/
function checkForNeworEdited(type)
{
	var New = 0;
	var Edited = 0;
	//This is done because javascript doesn't have a valid way to determine number of elements in an array that can be unordered and non numeric in indexes
	for(var item in eval('New' + type + 'List'))
	{
		if(typeof(eval('New' + type + 'List[item]')) != 'function')
		   New++;
	}
	for(var item in eval('Edited' + type + 'List'))
	{
		if(typeof(eval('Edited' + type + 'List[item]')) != 'function')
		   Edited++;
	}

	if(New !=0 || Edited != 0)
	{
		if(areYouSure('change companies losing all current additions and edits'))
		{
			//Since setting length to zero doesn't truncate arrays that are indexed by non numbers
			for(var item in eval('New' + type + 'List'))
			{
				if(typeof(eval('New' + type + 'List[item]')) != 'function')
		   			eval('delete New' + type + 'List[item]');		
			}
			eval('Edited' + type + 'List.length = 0;');
			return false;
		}
		else
		{
			return true;
		}

	}
	return false;

}
/*
Asks user if he is sure about an action
*/
function areYouSure(action)
{
	return confirm('Are you sure you want to ' + action + ' ?');
}
/*
Logic to create a new Package
Asks for new name
Checks if name exists already in list
adds new Package to the list
*/
function addPackage()
{
	var name = prompt("Enter Name of New Package");
	if(!name)
		return;

	var select = document.getElementById('dm_package_list');
	for (var item in select.options)
	{
		if(select.options[item] && select.options[item].text && (select.options[item].text == name || (select.options[item].text.substring(0,1) == '*' && select.options[item].text.substring(1) == name)))
		{
			alert('Name already exists');
			return;
		}
	}
	
	var company = document.getElementById('company').options[document.getElementById('company').selectedIndex].value;
	var loanType = document.getElementById('loan_type').options[document.getElementById('loan_type').selectedIndex].value;
	var updatepackagebutton = document.getElementById('updatepackage');
	var revertpackagebutton = document.getElementById('revertpackage');
	var newPackage = new Array();
	updatepackagebutton.style.display = 'block';

	newPackage['name_short'] = name;
	newPackage['company_id'] = company;
	newPackage['loan_type_id'] = loanType;
	newPackage['name'] = name;	
	NewPackageList[name] = newPackage;
	select.options[select.length] = new Option('*' + name, name);
	select.options[select.length - 1].selected = true;
	PackageOptions[name] = select.options[select.length - 1];
	loadPackage(name);
	
}
/*
Logic to create a new Document
Asks for new name
Checks if name exists already in list
adds new Document to the list
*/
function addDocument()
{
	var name = prompt("Enter Name of New Document");
	if(!name)
		return;

	var select = document.getElementById('dm_document_list');
	for (var item in select.options)
	{
		if(select.options[item] && select.options[item].text && (select.options[item].text == name || (select.options[item].text.substring(0,1) == '*' && select.options[item].text.substring(1) == name)))
		{
			alert('Name already exists');
			return;
		}
	}
	
	var company = document.getElementById('company').options[document.getElementById('company').selectedIndex].value;
	var loanType = document.getElementById('loan_type').options[document.getElementById('loan_type').selectedIndex].value;
	var updatedocumentbutton = document.getElementById('updatedocument');
	var revertdocumentbutton = document.getElementById('revertdocument');
	var newDoc = new Array();
	updatedocumentbutton.style.display = 'block';

	newDoc['name_short'] = name;
	newDoc['company_id'] = company;
	newDoc['loan_type_id'] = loanType;
	newDoc['name'] = '';	
	newDoc['can_delete'] = true;
	NewDocumentList[name] = newDoc;
	select.options[select.length] = new Option('*' + name, name);
	select.options[select.length - 1].selected = true;
	DocumentOptions[name] = select.options[select.length - 1];
	loadDocumentData(name);
}
/*
Logic to delete a document
if document is new, then only have to remove for local arrays
if document is edited or old then an ajax call to delete it from the db is called
*/
function deleteDocument()
{
	if(!areYouSure('delete this'))
		return;

	var select = document.getElementById('dm_document_list');
	var doc_id = select.options[select.selectedIndex].value;

	if(NewDocumentList[doc_id])
	{
		delete NewDocumentList[doc_id];
		delete DocumentOptions[doc_id];
		select.remove(select.selectedIndex);
		lockall();


	}
	else
	{
		var req = ecash_documents({
			id: 0,
			params:[{   
				action:'documents',
				function:'deleteDocument',
				document_id:doc_id,
			}], 
			onSuccess: function(transport){
				var result = transport.responseText.parseJSON();
				if (result) {
					hasDeletedDocument(result.result, doc_id, select.selectedIndex);

				} else {
					error_overlay(transport.responseText);
				}
			},
			onFailure: function (transport) {
				alert('ajax fail');
			}
		});


	}
	

}
/*
callback for deleteDocument
removes document from local document lists
*/
function hasDeletedDocument(result, doc_id, index)
{
	if(result == 1)
	{
		var select = document.getElementById('dm_document_list');
		delete DocumentList[doc_id];
		delete EditedDocumentList[doc_id];
		delete DocumentOptions[doc_id];
		select.remove(index);
		lockall();		
		var adddocumentbutton = document.getElementById('adddocument');
		adddocumentbutton.disabled = false;
	}
	else
	{
		alert("Deletion was not successful, the document may be \nreferenced by an application and not deletable");	
	}
}
/*
Logic to delete a package
if package is new, then only have to remove for local arrays
if package is edited or old then an ajax call to delete it from the db is called
*/
function deletePackage()
{
	if(!areYouSure('delete this'))
		return;

	var select = document.getElementById('dm_package_list');
	var package_id = select.options[select.selectedIndex].value;

	if(NewPackageList[package_id])
	{
		delete NewPackageList[package_id];
		delete PackageOptions[package_id];
		select.remove(select.selectedIndex);
		lockall();


	}
	else
	{
		var req = ecash_documents({
			id: 0,
			params:[{   
				action:'documents',
				function:'deletePackage',
				package_id:package_id,
			}], 
			onSuccess: function(transport){
				var result = transport.responseText.parseJSON();
				if (result) {
					hasDeletedPackage(result.result, package_id, select.selectedIndex);

				} else {
					error_overlay(transport.responseText);
				}
			},
			onFailure: function (transport) {
				alert('ajax fail');
			}
		});


	}
	

}
/*
callback for deletePackage
removes document from local document lists
*/
function hasDeletedPackage(result, package_id, index)
{
	if(result == 1)
	{
		var select = document.getElementById('dm_package_list');
		delete PackageList[package_id];
		delete EditedPackageList[package_id];
		delete PackageOptions[package_id];
		select.remove(index);
		lockall();		
		var addpackagebutton = document.getElementById('addpackage');
		addpackagebutton.disabled = false;
	}
	else
	{
		alert('Deletion Failure');	
	}
}
function error_overlay(error) 
{
	var body = document.getElementsByTagName('body')[0];
	var div = body.appendChild(document.createElement('div'));
	div.style.position = 'fixed';
	div.style.display = 'block';
	div.style.top = '0px';
	div.style.bottom = '0px';
	div.style.right = '0px';
	div.style.left = '0px';
	div.style.opacity = '0.9';
	div.style.backgroundColor = 'white';
	div.style.borderColor = 'black';
	div.style.borderWidth = '3px';
	div.style.borderStyle = 'solid';
	div.style.whiteSpace = 'pre';
	div.style.padding = '5px';
	div.style.zIndex = '8';
	div.style.overflow = 'auto';
	var h1 = div.appendChild(document.createElement('H1'));
	h1.appendChild(document.createTextNode('Ajax Error Report'));
	var but = div.appendChild(document.createElement('button'));
	but.appendChild(document.createTextNode('CLOSE'));
	but.setAttribute('onclick', 'this.parentNode.style.display="none";this.parentNode.parentNode.removeChild(this.parentNode);');
	div.appendChild(document.createElement("hr"));
	div.appendChild(document.createElement('div')).innerHTML = error;
}
/*
Disables all boxes inorder to await return from an ajax call
*/
function lockall()
{
	var documentdiv = document.getElementById('document_panel');
	var flagdiv = document.getElementById('flag_panel');
	var altbodydiv = document.getElementById('alt_body_panel');
	var packagediv = document.getElementById('package_panel');
	var configdiv = document.getElementById('config_panel');
	var packagedeletebutton = document.getElementById('deletepackage');
	var documentdeletebutton = document.getElementById('deletedocument');
	var packageupdatebutton = document.getElementById('updatepackage');
	var documentupdatebutton = document.getElementById('updatedocument');
	var packagerevertbutton = document.getElementById('revertpackage');
	var documentrevertbutton = document.getElementById('revertdocument');

	if(documentdeletebutton)
		documentdeletebutton.style.display = 'none';
	if(packagedeletebutton)
		packagedeletebutton.style.display = 'none';
	if(documentupdatebutton)
		documentupdatebutton.style.display = 'none';
	if(packageupdatebutton)
		packageupdatebutton.style.display = 'none';
	if(documentrevertbutton)
		documentrevertbutton.style.display = 'none';
	if(packagerevertbutton)
		packagerevertbutton.style.display = 'none';
	if(documentdiv)
		documentdiv.style.display = 'none';
	if(flagdiv)	
		flagdiv.style.display = 'none';
	if(altbodydiv)	
		altbodydiv.style.display = 'none';
	if(packagediv)	
		packagediv.style.display = 'none';
	if(configdiv)	
		configdiv.style.display = 'none';

}


</script>
