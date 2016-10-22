// SCRIPT FILE watch_hotkeys.js
// Eventually this will hold something of some significance.
// Hotkey stuff
function ShowKeyMap()
{
	var  helpstr = "";
	helpstr = helpstr + 'Watch Hotkeys:\n\n';
	//helpstr = helpstr + '\tAlt-F:\tTransaction\n';
	//helpstr = helpstr + '\tAlt-N:\tShow Received Documents\n';
	//helpstr = helpstr + '\tAlt-R:\tReporting\n';

	
	alert(helpstr);
}

function Key_Handler(e)
{
	var key;
	
	var alt_key_map = new Array();


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
