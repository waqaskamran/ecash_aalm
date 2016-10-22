// SCRIPT FILE menu.js
var last_menu = null;

function Toggle_Menu(element_id, parent_id)
{

	var visibility;
	var element = document.getElementById(element_id);
	var parentObj = document.getElementById(parent_id);

	if(element != null)
	{
		var is_vis = element.style.visibility;
		if(is_vis == "visible")
		{
			visibility = "hidden";
		}
		else
		{
			visibility = "visible";
		}
		element.style.zIndex=11;
	}
	if ((last_menu != null) && (last_menu != element))
	{
		last_menu.style.visibility = "hidden";
	}
	if(parentObj != null)
	{
		element.style.left = parentObj.offsetLeft + "px";
	}
	element.style.visibility = visibility;
	last_menu = element;
	
}

/*
This is called on the search screen and clears the search values.
*/
function Clear_Search()
{
	document.getElementById("AppSearchAllCriteriaType1").value = "application_id";
	document.getElementById("AppSearchAllCriteriaType2").value = "";
	document.getElementById("AppSearchAllDeliminator1").value = "is";
	document.getElementById("AppSearchAllDeliminator2").value = "is";
	document.getElementById("AppSearchAllCriteria1").value = "";
	document.getElementById("AppSearchAllCriteria2").value = "";
	document.getElementById("AppSearchAllOption").checked = false;
}

function changeCompany()
{
	var selectedCompanyForm = document.getElementById('change_company_form');
	selectedCompanyForm.submit();
}

function tooltip(e, contents, offsetX, offsetY)
{
    var tt = document.getElementById('tooltip');
    if(!e){
        tt.style.visibility = "hidden";
        return;
    }
    offsetX = offsetX ? offsetX : 30;
    offsetY = offsetY ? offsetY : 30;

    tt.innerHTML = contents;
    tt.style.left = e.pageX +"px";
    var newtop = eval(e.pageY) + offsetY;
    tt.style.top = newtop +"px";
    tt.style.visibility = "visible";

}

function openOnlineHelpWindow(url, popName)
{
	var opts = 'toolbar=no,location=no,directories=no,status=no,menubar=no';
	opts += ',scrollbars=yes,resizable=yes,copyhistory=no,width=940';
	opts += ',height=400,left=200,top=200,screenX=200,screenY=200';

	var win = window.open(url, popName, opts);
	win.focus();
}