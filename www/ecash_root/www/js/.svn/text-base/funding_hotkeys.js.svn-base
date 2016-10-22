// SCRIPT FILE funding_hotkeys.js
var co_abbrev = null;
var agent_id = null;
var allow_cashline = false;

// Hotkey stuff
function ShowKeyMap()
{
	var  helpstr = "";
	helpstr = helpstr + 'Funding Hotkeys:\n\n';
	helpstr = helpstr + '\tAlt-C:\tCustomer Service\n';
	helpstr = helpstr + '\tAlt-D:\tShow Received Documents\n';
	helpstr = helpstr + '\tAlt-E:\tShow Employment\n';
	helpstr = helpstr + '\tAlt-I:\t Show Identity\n';
	helpstr = helpstr + '\tAlt-N:\tGet Next App\n';
	helpstr = helpstr + '\tAlt-P:\tShow Personal\n';
	helpstr = helpstr + '\tAlt-Q:\tShow Cashline Queue\n';
	helpstr = helpstr + '\tAlt-R:\tGoto Reporting\n';
	helpstr = helpstr + '\tAlt-S:\tShow Send Documents\n';
	helpstr = helpstr + '\tAlt-T:\tShow Transactions\n';
	helpstr = helpstr + '\tAlt-U:\tShow Underwriting\n';
	helpstr = helpstr + '\tAlt-V:\tShow Verification Overview\n';
	//helpstr = helpstr + '\tCtrl-B:\t\n';
	//helpstr = helpstr + '\tCtrl-D:\t\n';
	helpstr = helpstr + '\tCtrl-E:\tEdit left pane.\n';
	//helpstr = helpstr + '\tCtrl-F:\t\n';
	//helpstr = helpstr + '\tCtrl-G:\t\n';
	//helpstr = helpstr + '\tCtrl-L:\t\n';
	helpstr = helpstr + '\tCtrl-S:\tShow Search\n';
	helpstr = helpstr + '\tCtrl-T:\tShow Customer Service Search\n';
	//helpstr = helpstr + '\tCtrl-U:\t\n';
	//helpstr = helpstr + '\tCtrl-W:\t\n';	
	
	alert(helpstr);
}

function Key_Handler(e)
{
	var key;
	
	var alt_key_map = new Array();

	//eCash funding keys
	alt_key_map[67] = 'Alt_C';
	alt_key_map[68] = 'Alt_D';
	alt_key_map[69] = 'Alt_E';
	alt_key_map[73] = 'Alt_I';
	alt_key_map[78] = 'Alt_N';
	alt_key_map[80] = 'Alt_P';
	alt_key_map[81] = 'Alt_Q';
	alt_key_map[82] = 'Alt_R';
	alt_key_map[83] = 'Alt_S';
	alt_key_map[84] = 'Alt_T';
	alt_key_map[85] = 'Alt_U';
	alt_key_map[86] = 'Alt_V';
	
	var ctrl_key_map = new Array();

	//eCash funding keys
	ctrl_key_map[66] = 'Ctrl_B';
	ctrl_key_map[68] = 'Ctrl_D';
	ctrl_key_map[69] = 'Ctrl_E';
	ctrl_key_map[70] = 'Ctrl_F';
	ctrl_key_map[71] = 'Ctrl_G';
	ctrl_key_map[76] = 'Ctrl_L';
	ctrl_key_map[83] = 'Ctrl_S';
	ctrl_key_map[84] = 'Ctrl_T';
	ctrl_key_map[85] = 'Ctrl_U';
	ctrl_key_map[87] = 'Ctrl_W';

	key = e.which;

	//Uncomment this next line to print the key mapping number that is pressed
	//alert('key presssed ' + key);

	//check to see if a modifier was pressed in conjunction with the keystroke
	if(e.altKey) //can also use ctrlKey and shiftKey and combinations
	{
		//alert('alt pressed');
		this.Run_Function(alt_key_map[key]);
	}
	else if(e.ctrlKey)
	{
		this.Run_Function(ctrl_key_map[key]);
	}
	
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

//show customer service search
function Ctrl_T()
{
	//alert('tiffing');
	location.href = '/?mode=tiffing&action=show_search';
}
function Alt_C()
{
	//alert('customer_service');
	location.href = '/?mode=customer_service&action=show_search';	
}

//show the cashline queue
function Alt_Q()
{
	if(co_abbrev != null && agent_id != null && allow_cashline)
	{
		window.open('/cashline/paperless_queue.php?co_abbrev=' + co_abbrev + '&agent_id=' + agent_id, 'CashlineQ');		
	}
}

//show the reporting module
function Alt_R()
{
	location.href = '/?module=reporting';	
}

//show verification overview
function Alt_V()
{
	location.href = '/?mode=verification&action=get_verification_queue_stats';
}

//show underwriting overview
function Alt_U()
{
	location.href = '/?mode=underwriting&action=get_underwriting_queue_stats';
}

//show personal
function Alt_P()
{
	//make sure we're on the page with the documents layer (not search)
	if(document.getElementById("group0layer0view") != null)
	{
		Show_Layer(0,0,'view');Show_Layer(1,0,'view');
	}
}

//show identity
function Alt_I()
{
	//make sure we're on the page with the documents layer (not search)
	if(document.getElementById("group0layer1view") != null)
	{
		Show_Layer(0,1,'view');Show_Layer(1,0,'view');
	}
}

//show transaction
function Alt_T()
{
	//make sure we're on the page with the documents layer (not search)
	if(document.getElementById("group0layer2view") != null)
	{
		Show_Layer(0,2,'view');Show_Layer(1,0,'view');
	}
}

//show employment
function Alt_E()
{
	//make sure we're on the page with the documents layer (not search)
	if(document.getElementById("group0layer3view") != null)
	{
		Show_Layer(0,3,'view');Show_Layer(1,0,'view');
	}
}

//show send documents
function Alt_S()
{
	//make sure we're on the page with the documents layer (not search)
	if(document.getElementById("group1layer1edit") != null)
	{
		Show_Layer(0,4,'view');Show_Layer(1,1,'edit');
	}
}

//get next app
function Alt_N()
{
	//make sure we're on the page with the get next app menu
	if(document.getElementById("menu_label_nextapp") != null)
	{
//		Check_Data();
		location.href='/?action=get_next_app&' + Get_Flux();
	}
}

//show received documents
function Alt_D()
{
	//make sure we're on the page with the documents layer (not search)
	if(document.getElementById("group1layer2edit") != null)
	{
		Show_Layer(0,4,'view');Show_Layer(1,2,'edit');
	}
}

//show the edit mode of the current upper left pane
function Ctrl_E()
{
	//make sure we're on the overview page (not search)
	//by seeing if the function 'Get_Current_Layer_In_Group'
	//is defined
	if(self.Get_Current_Layer_In_Group)
	{
		var current_layer_element = Get_Current_Layer_In_Group(0);
		if(current_layer_element != null)
		{
			var current_layer_id = current_layer_element.id;
			//find the first portion of the layer name (w/o edit or view suffix)
			var mode_index = current_layer_id.indexOf(edit);
			if(mode_index < 0)
			{
				mode_index = current_layer_id.indexOf(view);
			}

			var layer_prefix = current_layer_id.substring(0, mode_index);
			//make sure this pane has an edit mode
			if(document.getElementById(layer_prefix + edit) != null)
			{
				//example group0layer0view
				var layer_index = current_layer_id.indexOf(layer);
				var current_layer = current_layer_id.substring(layer_index + layer.length, mode_index);
				Show_Layer(0,current_layer,'edit');
				Show_Layer(1,0,'view');
			}			
		}
	}
}

//show search
function Ctrl_S()
{
	location.href = '/?action=show_search';
}

//this is the key line that assigns our custom Key_Handler function
//to the default 'onkeyup' listener that gets called when the user
//releases the key
onkeyup = Key_Handler;
