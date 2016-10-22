// SCRIPT FILE rules_hotkeys.js
// Hotkey stuff
function ShowKeyMap()
{
	var  helpstr = "";
	helpstr = helpstr + 'Rules Hotkeys:\n\n';
	helpstr = helpstr + '\tAlt-F:\tTransaction\n';
	//helpstr = helpstr + '\tAlt-N:\tShow Received Documents\n';
	helpstr = helpstr + '\tAlt-R:\tReporting\n';

	
	alert(helpstr);
}
function Key_Handler(e)
{
	var key;
	
	var alt_key_map = new Array();

	//eCash hot keys
	alt_key_map[70] = 'Alt_F';
	alt_key_map[78] = 'Alt_N';
	alt_key_map[82] = 'Alt_R';

	key = e.which;

	//Uncomment this next line to print the key mapping number that is pressed
	//alert('key presssed ' + key);

	//check to see if a modifier was pressed in conjunction with the keystroke
	if(e.altKey) //can also use ctrlKey and shiftKey and combinations
	{
		//alert('alt pressed');
		this.Run_Function(alt_key_map[key]);
	}
	// SHow Help
	if(e.keyCode == e.DOM_VK_F1)
	{
		ShowKeyMap();
	}		
}

function Run_Function(function_name)
{
	//see if this function exists
	if(self[function_name])
	{
		//alert('monkey');
		this.eval('this.' + function_name + '();');
	}
}

function Alt_F()
{
	location.href = '/?module=transaction';
}

function Alt_R()
{
	location.href = '/?module=reporting';	
}

//this is the key line that assigns our custom Key_Handler function
//to the default 'onkeyup' listener that gets called when the user
//releases the key
onkeyup = Key_Handler;

