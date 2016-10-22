<script type='text/javascript'>
var TokenList;
var EditedTokenList = new Array();
var TokenOptions = new Array();
var NewTokenList = new Array();

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
function ecash_tokens(request) {
	request.method = 'Tokens';
	return ecash_JSON_RPC(request)
}
function token_ini()
{
	getCompanies();
	getApplicationColumns();
}
/*
reenables the token selection box 
*/
function unlockall()
{
	var select = document.getElementById('tokenlist');
	select.disabled = false;
	var addtokenbutton = document.getElementById('addtoken');
	var deletetokenbutton = document.getElementById('deletetoken');
	addtokenbutton.disabled = false;
	deletetokenbutton.disabled = false;
}
/*
Disables all boxes inorder to await return from an ajax call
*/
function lockall()
{
	var staticdiv = document.getElementById('static');
	var applicationdiv = document.getElementById('application');
	var businessrulediv = document.getElementById('business_rule');
	var tokentypediv = document.getElementById('tokentypediv');
	var addtokenbutton = document.getElementById('addtoken');
	var deletetokenbutton = document.getElementById('deletetoken');
	var updatetokenbutton = document.getElementById('updatetoken');
	var dateCreatedDiv = document.getElementById('date_created');
	var dateModifiedDiv = document.getElementById('date_modified');
	var changemessagediv = document.getElementById('changemessage');
	changemessagediv.style.display = 'none';
	dateCreatedDiv.innerHTML = 'No Token Selected';
	dateModifiedDiv.innerHTML = 'No Token Selected';
	staticdiv.style.display = 'none';
	applicationdiv.style.display = 'none';
	businessrulediv.style.display = 'none';
	tokentypediv.style.display = 'none';
	addtokenbutton.disabled = true;
	deletetokenbutton.style.display = 'none';
	updatetokenbutton.style.display = 'none';
}
/*
Will fetch the current tokens for the company/loantype combination

*/
function getTokens()
{
	if(checkForNeworEdited())
		return false;

	var select = document.getElementById('tokenlist');
	select.length = 0;	
	select.options[0] = new Option('Fetching Tokens...', 0);
	select.disabled = true;
	lockall();
	var company = document.getElementById('company').options[document.getElementById('company').selectedIndex].value;
	var loanType = document.getElementById('loan_type').options[document.getElementById('loan_type').selectedIndex].value;
	var req = ecash_tokens({
		id: 0,
		params:[{   
			action:'tokens',
			function:'getTokens',
			company_id: company,
			loan_type_id: loanType
		}], 
		onSuccess: function(transport){
			var result = transport.responseText.parseJSON();
			if (result) {
				loadTokens(result.result);

			} else {
				error_overlay(transport.responseText);
			}
		},
		onFailure: function (transport) {
			alert('ajax fail');
		}
	});
	if(loanType != 0)
		getComponents(loanType);

	return true;
	
}

/*
Determines which token types are available for the selected level
and populates the token type select box
*/
function setTokenTypeSelect()
{
	var company = document.getElementById('company').options[document.getElementById('company').selectedIndex].value;
	var loanType = document.getElementById('loan_type').options[document.getElementById('loan_type').selectedIndex].value;
	var TypeSelect = document.getElementById('tokentype');
	TypeSelect.length = 0;	
	TypeSelect.options[0]= new Option("Static", "static"); 

	if(loanType != 0)
	{
		TypeSelect.options[1]= new Option("Application", "application"); 
		TypeSelect.options[2]= new Option("Business Rule", "business_rule"); 
	}

}
/*
Callback function for ajax call to get token list
loads the Token select list and deems the available token types
*/
function loadTokens(tokens)
{
	var select = document.getElementById('tokenlist');
	select.length = 0;
	var i = 0;	
	for(var token in tokens)
	{	
		//parseJSon likes to attach a function to the array
		if(typeof(tokens[token]) == 'object')
		{
			select.options[i] = new Option(tokens[token]['name'], token);
			TokenOptions[token] = select.options[i];
			i++;
		}
	}
	TokenList = tokens;
	setTokenTypeSelect();
	unlockall();
}

/*
Loads the Type selection box and displays the correct value boxes for a selected token type
also enables and disables buttons based on if a token is new or edited
*/
function loadtoken()
{
	var select = document.getElementById('tokenlist');
	var updatetokenbutton = document.getElementById('updatetoken');
	var reverttokenbutton = document.getElementById('reverttoken');
	var deletetokenbutton = document.getElementById('deletetoken');
	deletetokenbutton.style.display = 'block';

	if(NewTokenList[select.options[select.selectedIndex].value])
	{
		var token = NewTokenList[select.options[select.selectedIndex].value];
		updatetokenbutton.style.display = 'block';
	}
	else if(EditedTokenList[select.options[select.selectedIndex].value])
	{
		var token = EditedTokenList[select.options[select.selectedIndex].value];
		updatetokenbutton.style.display = 'block';
		reverttokenbutton.style.display = 'block';
	}	
	else	
	{	
		var token = TokenList[select.options[select.selectedIndex].value];
		updatetokenbutton.style.display = 'none';
		reverttokenbutton.style.display = 'none';
	}	
	var staticdiv = document.getElementById('static');
	var applicationdiv = document.getElementById('application');
	var businessrulediv = document.getElementById('business_rule');
	var tokentypediv = document.getElementById('tokentypediv');
	staticdiv.style.display = 'none';
	applicationdiv.style.display = 'none';
	businessrulediv.style.display = 'none';

	setSelect('tokentype', token['type']);
	
	tokentypediv.style.display = 'block';

	switch(token.type)
	{
		case 'static':
			staticdiv.style.display = 'block';
			document.getElementById('value').value = token['value'];	 
		break;
		case 'application':
			applicationdiv.style.display = 'block';
			setApplicationColumnSelect(token['columnName']);
		break;
		case 'business_rule':
			businessrulediv.style.display = 'block';
			setBusinessRuleSelect(token['component'], token['componentParm']);
		break;
	
	}
	var dateCreatedDiv = document.getElementById('date_created');
	var dateModifiedDiv = document.getElementById('date_modified');
	if(token['date_created'])
		dateCreatedDiv.innerHTML = token['date_created'];
	else
		dateCreatedDiv.innerHTML = 'New';
	if(token['date_modified'])
		dateModifiedDiv.innerHTML = token['date_modified'];
	else
		dateModifiedDiv.innerHTML = 'New';
}
/*
Checks if any New ord Edited Tokens exist and if so prompts the user to verify his actions
*/
function checkForNeworEdited()
{
	var New = 0;
	var Edited = 0;
	//This is done because javascript doesn't have a valid way to determine number of elements in an array that can be unordered and non numeric in indexes
	for(var item in NewTokenList)
	{
		if(typeof(NewTokenList[item]) != 'function')
		   New++;
	}
	for(var item in EditedTokenList)
	{
		if(typeof(EditedTokenList[item]) != 'function')
		   Edited++;
	}

	if(New !=0 || Edited != 0)
	{
		if(areYouSure('change companies losing all current additions and edits'))
		{
			//Since setting length to zero doesn't truncate arrays that are indexed by non numbers
			for(var item in NewTokenList)
			{
				if(typeof(NewTokenList[item]) != 'function')
		   			delete NewTokenList[item];		
			}
			EditedTokenList.length = 0;
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
Will fetch loan type for selected company
*/
function getLoanTypes(company)
{
	if(checkForNeworEdited())
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
	getTokens();
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
Handles the logic for when a token is edited
*/
function tokenChange()
{
	var value = document.getElementById('value');
	var columnname = document.getElementById('columnname');
	var component = document.getElementById('component');
	var componentparm = document.getElementById('componentparm');
	var select = document.getElementById('tokenlist');
	var tokenid = select.options[select.selectedIndex].value;
	var TypeSelect = document.getElementById('tokentype');	
	var updatetokenbutton = document.getElementById('updatetoken');
	var reverttokenbutton = document.getElementById('reverttoken');
	var changemessagediv = document.getElementById('changemessage');
	changemessagediv.style.display = 'block';
	var editedToken = new Array();	
	if(NewTokenList[tokenid])
	{
		var token = NewTokenList[tokenid];
		updatetokenbutton.style.display = 'block';
	}
	else
	{
		var token = TokenList[tokenid];
		updatetokenbutton.style.display = 'block';
		reverttokenbutton.style.display = 'block';
	}


	editedToken['name'] = token['name'];
	editedToken['company_id'] = token['company_id'];
	editedToken['loan_type_id'] = token['loan_type_id'];
	editedToken['type'] = TypeSelect.options[TypeSelect.selectedIndex].value;
	editedToken['date_created'] = token['date_created'];
	editedToken['date_modified'] = token['date_modified'];
	switch(editedToken['type'])
	{
		case 'static':
			editedToken['value'] = value.value;		 
		break;
		case 'application':
			editedToken['columnName'] = columnname.options[columnname.selectedIndex].value;
		break;
		case 'business_rule':
			editedToken['component'] = component.options[component.selectedIndex].value;
			editedToken['componentParm'] = componentparm.options[componentparm.selectedIndex].value;
		break;
	}	

	if(NewTokenList[tokenid])
	{
		NewTokenList[tokenid] = editedToken;
	}
	else
	{
		EditedTokenList[select.options[select.selectedIndex].value] = editedToken;
		TokenOptions[select.options[select.selectedIndex].value].text = '*' + token['name'];
	}
}
function setValue()
{
	tokenChange();
}
function setParm()
{
	tokenChange();
}
function setColumnName()
{
	tokenChange();
}
/*
Ajax call to get business rule Params for a component
*/
function getParams(componentParm, fireTokenChange)
{
	var select = document.getElementById('componentparm');
	var loanType = document.getElementById('loan_type').options[document.getElementById('loan_type').selectedIndex].value;
	select.length = 0;	
	select.options[0] = new Option('Fetching Parms...', 0);
	select.disabled = true;
	var component = document.getElementById('component');
	var component_id = component.options[component.selectedIndex].value;
	var req = ecash_reference({
		id: 0,
		params:[{   
			component_id: component_id,
			loan_type_id:loanType,
			action:'reference',
			function:'getRuleParams'
			
		}], 
		onSuccess: function(transport){
			var result = transport.responseText.parseJSON();
			if (result) {
				loadParams(result.result, componentParm, fireTokenChange);

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
Callback function for the getParms ajax call

*/
function loadParams(params, componentParm, fireTokenChange)
{
	if(fireTokenChange == null)
		fireTokenChange = false;

	var select = document.getElementById('componentparm');
	select.length = 0;
	var i = 0;	
	for(var param in params)
	{	
		//parseJSon likes to attach a function to the array
		if(typeof(params[param]) == 'string')
		{
			select.options[i] = new Option(params[param], param);
			i++;
		}
	}
	select.disabled = false;
	setSelect('componentparm', componentParm);
	if(fireTokenChange)
	{
		tokenChange();
	}	
}

function setComponent()
{
	getParams(null, true);
}
/*
Ajax call to fetch business rule components for a loan type
*/
function getComponents(loan_type_id)
{
	var req = ecash_reference({
		id: 0,
		params:[{   
			action:'reference',
			function:'getRuleComponents',
			loan_type_id:loan_type_id,
		}], 
		onSuccess: function(transport){
			var result = transport.responseText.parseJSON();
			if (result) {
				loadcomponents(result.result);

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
Callback for getComponents
*/
function loadcomponents(components)
{
	var select = document.getElementById('component');
	select.length = 0;
	//select.options[0]= new Option("Universal", "0"); 
	var i = 0;	
	for(var component in components)
	{	
		//parseJSon likes to attach a function to the array
		if(typeof(components[component]) == 'string')
		{
			select.options[i] = new Option(components[component], component);
			i++;
		}
	}
	getParams();
}
/*
Ajax call to get Application Columns
*/
function getApplicationColumns()
{
	var req = ecash_reference({
			id: 0,
			params:[{   
				action:'reference',
				function:'getApplicationColumns'
			}], 
			onSuccess: function(transport){
				var result = transport.responseText.parseJSON();
				if (result) {
					loadApplicationColumns(result.result);

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
callback for getApplicationColumns
*/
function loadApplicationColumns(Columns)
{
	var select = document.getElementById('columnname');
	select.length = 0;
	var i = 0;	
	for(var Column in Columns)
	{	
		//parseJSon likes to attach a function to the array
		if(typeof(Columns[Column]) == 'string')
		{
			select.options[i] = new Option(Columns[Column], Columns[Column]);
			i++;
		}
	}
}
/*
Logic for when a user selects a token type from the select box
*/
function switchtype(selected)
{
	var staticdiv = document.getElementById('static');
	var applicationdiv = document.getElementById('application');
	var businessrulediv = document.getElementById('business_rule');
	
	staticdiv.style.display = 'none';
	applicationdiv.style.display = 'none';
	businessrulediv.style.display = 'none';

	switch(selected)
	{
		case 'static':
			staticdiv.style.display = 'block';
		break;
		case 'application':
			applicationdiv.style.display = 'block';
		break;
		case 'business_rule':
			businessrulediv.style.display = 'block';
		break;

	}

	tokenChange();

}
/*
sets current token's value for application column
*/
function setApplicationColumnSelect(columnName)
{
	setSelect('columnname', columnName);	
}
/*
iterates through a select elemtn and sets the selected to an option with a given value
*/
function setSelect(element, value)
{
	var select = document.getElementById(element);
	for (var item in select.options)
	{
		if(select.options[item] && select.options[item].value == value)
			select.options[item].selected = true;
	}	
}
/*
sets current token's value for a business rule
*/
function setBusinessRuleSelect(component, componentParm)
{
	setSelect('component', component);
	getParams(componentParm);	

}
/*
reverts an edited token back to the saved values
*/
function revertToken()
{
	var select = document.getElementById('tokenlist');
	var tokenid = select.options[select.selectedIndex].value;
	var token = TokenList[select.options[select.selectedIndex].value];
	TokenOptions[tokenid].text = token['name'];	
	delete EditedTokenList[tokenid];
	var changemessagediv = document.getElementById('changemessage');
	changemessagediv.style.display = 'none';
	for(var item in EditedTokenList)
	{
		if(typeof(EditedTokenList[item]) != 'function')
		{
			changemessagediv.style.display = 'block';
			break;		
		}	
	}
	
	loadtoken();

}
/*
Ajax call to save the edited or new token to the db
*/
function updateToken()
{
	var select = document.getElementById('tokenlist');
	var tokenid = select.options[select.selectedIndex].value;
	if(NewTokenList[tokenid])
	{
		var token = NewTokenList[tokenid];
		tokenid = null;
	}
	else
	{
		var token = EditedTokenList[tokenid];	
	}	
	var req = ecash_tokens({
		id: 0,
		params:[{   
			action:'tokens',
			function:'updateToken',
			token_id:tokenid,
			params: {
					name: token['name'],
					company_id: token['company_id'],
					loan_type_id: token['loan_type_id'],
					type: token['type'],
					value: escape(token['value']),
					columnName: token['columnName'],
					component: token['component'],
					componentParm: token['componentParm']

				}
		}], 
		onSuccess: function(transport){
			var result = transport.responseText.parseJSON();
			if (result) {
				tokenUpdated(result.result);

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
callback for updateToken
updates TokenList and TokenOptions to reflect token being saved
*/
function tokenUpdated(result)
{
	if(result)
	{
		//need to remap all the entries that are by name, to by id

		if(NewTokenList[result['name']])
		{
			
			TokenList[result['id']] = NewTokenList[result['name']];
			TokenList[result['id']]['date_created'] = result['date_created'];
			TokenList[result['id']]['date_modified'] = result['date_modified'];
			delete NewTokenList[result['name']];
			TokenOptions[result['id']] = TokenOptions[result['name']];
			delete TokenOptions[result['name']];
			TokenOptions[result['id']].text = TokenList[result['id']]['name'];
			TokenOptions[result['id']].value = result['id']	
		}
		else
		{
			TokenList[result['id']] = EditedTokenList[result['id']];
			TokenList[result['id']]['date_created'] = result['date_created'];
			TokenList[result['id']]['date_modified'] = result['date_modified'];
			delete EditedTokenList[result['id']];
			TokenOptions[result['id']].text = TokenList[result['id']]['name'];
		}
		
		loadtoken();		
	}	
	else
	{
		alert("update failure");
	}
}
/*
Logic to create a new token
Asks for new name
Checks if name exists already in list
adds new token to the list
*/
function addToken()
{
	var name = prompt("Enter Name of New Token");
	if(!name)
		return;

	var select = document.getElementById('tokenlist');
	for (var item in select.options)
	{
		if(select.options[item] && select.options[item].text == name)
		{
			alert('Name already exists');
			return;
		}
	}
	
	var company = document.getElementById('company').options[document.getElementById('company').selectedIndex].value;
	var loanType = document.getElementById('loan_type').options[document.getElementById('loan_type').selectedIndex].value;
	var updatetokenbutton = document.getElementById('updatetoken');
	var reverttokenbutton = document.getElementById('reverttoken');
	var newToken = new Array();
	updatetokenbutton.style.display = 'block';

	newToken['name'] = name;
	newToken['company_id'] = company;
	newToken['loan_type_id'] = loanType;
	newToken['type'] = 'static';
	newToken['value'] = '';	
	NewTokenList[name] = newToken;
	select.options[select.length] = new Option('*' + name, name);
	select.options[select.length - 1].selected = true;
	TokenOptions[name] = select.options[select.length - 1];
	loadtoken();
}
/*
Asks user if he is sure about an action
*/
function areYouSure(action)
{
	return confirm('Are you sure you want to ' + action + ' ?');
}
/*
Logic to delete a token
if token is new, then only have to remove for local arrays
if token is edited or old then an ajax call to delete it from the db is called
*/
function deleteToken()
{
	if(!areYouSure('delete this'))
		return;

	var select = document.getElementById('tokenlist');
	var tokenid = select.options[select.selectedIndex].value;

	if(NewTokenList[tokenid])
	{
		delete NewTokenList[tokenid];
		delete TokenOptions[tokenid];
		select.remove(select.selectedIndex);
		lockall();
		var addtokenbutton = document.getElementById('addtoken');
		addtokenbutton.disabled = false;	

	}
	else
	{
		var req = ecash_tokens({
			id: 0,
			params:[{   
				action:'tokens',
				function:'deleteToken',
				token_id:tokenid,
			}], 
			onSuccess: function(transport){
				var result = transport.responseText.parseJSON();
				if (result) {
					hasDeleted(result.result, tokenid, select.selectedIndex);

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
callback for deleteToken
removes token from local token lists
*/
function hasDeleted(result, tokenid, index)
{
	if(result == 1)
	{
		var select = document.getElementById('tokenlist');
		delete TokenList[tokenid];
		delete EditedTokenList[tokenid];
		delete TokenOptions[tokenid];
		select.remove(index);
		lockall();		
		var addtokenbutton = document.getElementById('addtoken');
		addtokenbutton.disabled = false;
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

</script>
