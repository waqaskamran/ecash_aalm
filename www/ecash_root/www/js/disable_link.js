// SCRIPT FILE disable_link.js

// links that are currently disabled
var savedLinks = new Array();

// milliseconds
if( ! defaultTimeoutLength )
	var defaultTimeoutLength = 5000;

/**
* Disables/enables the get_next_app link (fraud & funding areas)
*/
function Get_Next_App_Checker( link_id, destination, timeoutLength )
{
	if( timeoutLength == null )
		timeoutLength = defaultTimeoutLength;

	link = document.getElementById(link_id);

	// If the link is found in the savedLinks array
	   // Then they've clicked on the link within the last timeoutLength milliseconds
	   // So, disable the link
	if( savedLinks.toString().indexOf(link_id) !== -1 )
	{
		// disable
		link.href = '/';
		return false;
	}
	// Otherwise, it should be available
	else
	{
		// If the data has been changed and the user chose to save the data
		   // just save it and get out
		if( Check_Data(true) )
		{
			return false;
		}

		// disable the link for next time
		savedLinks.push( link_id );

		// Technically this is the right way of doing things...
		   // But firefox/javascript sucks so this doesn't work sometimes
		link.href = destination;

		// Re-enable the link later
		setTimeout( "Shift_Link_Array('"+link_id+"')", timeoutLength );

		// instead of link.href since that is not working
		window.location.href = destination;
	}

	return true;
}

/**
* enables the get_next_app button
*
* @param string link_id id of get next app link tag
*/
function Shift_Link_Array( link_id )
{
	if( savedLinks.toString().indexOf(link_id) !== -1 )
	{
		savedLinks.shift();
		document.getElementById(link_id).href = '/';
	}
}

/**
* Disables something after clicking, then restores it after timeoutLength milliseconds
*
* @param string button_id the id of the button/link to disable
*/
function Disable_Button( button_id, timeoutLength )
{
	var myButton = document.getElementById( button_id );

	if( timeoutLength == null )
		timeoutLength = defaultTimeoutLength;

	// Its an input tag (input button, input submit, etc)
	if( myButton.tagName == "input" )
	{
		myButton.disabled = true;

		setTimeout( "Enable_Button('" + button_id + "')", timeoutLength );
	}
	// Its a link, probably
	else if( myButton.tagName == "a" && myButton.href.length > 0 )
	{
		// Dont do anything if it has already been saved
		if (savedLinks.toString().indexOf(button_id) === -1)
			setTimeout( "Disable_Link('" + button_id + "')", 5 );
	}
}

/**
* Disables an <a href> tag by pushing the link and setting the href to void()
*
* @param string link_id the id of the link to disable
*/
function Disable_Link( link_id, timeoutLength )
{
	if( timeoutLength == null )
		timeoutLength = defaultTimeoutLength;

	myButton = document.getElementById( link_id );
	savedLinks.push(new Array(link_id, myButton.href));
	myButton.href = '';
	setTimeout( "Enable_Button('" + link_id + "')", timeoutLength );
}

/**
* Enables a button/link that has been disabled
*
* @param string button_id the id of the button/link to enable
*/
function Enable_Button( button_id )
{
	myButton = document.getElementById( button_id );

	// Its a form button
	if( myButton.toString() == "[object HTMLInputElement]" )
	{
		myButton.disabled = false;
	}
	// Its a link
	else
	{
		var stuff = savedLinks.shift();
		myButton.href = stuff[1];
		myButton.style.display = 'inline';
	}
}


// links that should be disabled
var disabledLinks = new Array();

/**
* Disables the ability to click the same link more than once.
* This is for those excitable left-clickers.
* 
* This function checks a global array to see if the id of the
* element is in a disabled list.  If it is, clicking the link
* does nothing, otherwise the browser is directed to the url
* passed to it.
*
* @param id link_id of the anchor
* @param string link_url of the link to check
*/
function Click_Link_Once ( id, url )
{
	var found = false;
	for (i in disabledLinks)
	{
		if (disabledLinks[i] == id)
		{
			//alert('removing');
			//disabledLinks.pop();
			found = true;
		}
	}
	if (found == false)
	{
		location = url;
		Disable_One_Link (id, 0);
	}
	
}

/**
* This function adds the passed array of link ids to the
* global disabled links array, then after the specified
* timeout, removes them from the disabled links array.
*
* @param array link_ids - links to disable
* @param int timeout - time to wait for setTimeout
*/
function Disable_Many_Links (link_ids, timeout)
{
	for (i in link_ids)
	{
		disabledLinks.push(link_ids[i]);
	}
	// the setTimeout function converts the arguments to
	// the function being called into a string, so we
	// have to turn the array into a comma delimited string
	var myArgs = link_ids.toString();

	if (timeout != 0)
	{
		setTimeout("ReEnable_Links('" +myArgs+ "')", timeout);
	}

}

/**
* This function adds the passed link id to the
* global disabled links array, then after the specified
* timeout, removes it from the disabled links array.
*
* @param array link_ids - links to disable
* @param int timeout - time to wait for setTimeout
*/
function Disable_One_Link (link_id, timeout)
{
	disabledLinks.push(link_id);

	if (timeout != 0)
	{
		setTimeout("ReEnable_Links('" + link_id + "')", timeout);
	}
}


/**
* This function removes the list of links passed to it
* from the global disabled links array.
*
* @param string remove_args - comma delimited list of links to remove
*/
function ReEnable_Links (remove_args)
{
	// convert our arguments back into an array
	var remove_ids = remove_args.split(",");
	
	// loop through the array passed to us and the global
	// disabled links array and remove any matches
	for (a in remove_ids)
	{
		for (b in disabledLinks)
		{ 
			if (remove_ids[a] == disabledLinks[b])
			{	
//				alert('Disabling: ' + disabledLinks[b]);
				disabledLinks.splice(b,1);
			}
		}
	}
}
