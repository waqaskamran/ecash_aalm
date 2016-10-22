// SCRIPT FILE nada.js
var make_year;

function nada_lookup(request) {
	request.method = 'NADA';
	return ecash_JSON_RPC(request);
}


function getMakes(year)
{
	if(!year)
	{
		year = document.getElementById('vehicle_year').value;
	}
	document.getElementById('vehicle_make').innerHTML = "<option value=\"0\">Getting Makes...</option>";
	document.getElementById('vehicle_series').innerHTML = "<option value=\"0\">(Make Required)</option>";
	document.getElementById('vehicle_body').innerHTML = "<option value=\"0\">(Make Required)</option>";
	var req = nada_lookup({
				id: 123, 
				params:[{
					action:'nada_info',
					function:'getMakes',
					makes_year:year
				}], 
				onSuccess: function (transport) {
					var result = transport.responseText.parseJSON();
					var makes = result.result;
					update_select('vehicle_make', makes);
				},
				onFailure: function (transport) {
					alert('ajax fail');
				}
			});
}


function getSeries(make_id)
{
	var year = document.getElementById('vehicle_year').value;
	document.getElementById('vehicle_series').innerHTML = "<option value=\"0\">Getting Series...</option>";
	document.getElementById('vehicle_body').innerHTML = "<option value=\"0\">(Series Required)</option>";
	var req = nada_lookup({
				id: 1,
				params:[{
					action:'nada_info',
					function:'getSeries',
					makes_year:year,
					make:make_id
				}], 
				onSuccess: function (transport) {
					var result = transport.responseText.evalJSON();
					var series = result.result;
					update_select('vehicle_series', series);
				},
				onFailure: function (transport) {
					alert('ajax fail');
				}
			});
}

function getBodies()
{
	var year = document.getElementById('vehicle_year').value;
	var make = document.getElementById('vehicle_make').value;
	var series = document.getElementById('vehicle_series').value;
	document.getElementById('vehicle_body').innerHTML = "<option value=\"0\">Getting Styles...:</option>";
	var req = nada_lookup({
				id: 3, 
				params:[{
					action:'nada_info',
					function:'getBodies',
					bodies_year:year,
					bodies_make:make,
					bodies_series:series,
				}], 
				onSuccess: function (transport) {
					var result = transport.responseText.evalJSON();
					var bodies = result.result;
					update_select('vehicle_body', bodies);
				},
				onFailure: function (transport) {
					alert('ajax fail');
				}
			});
}

function getVehicleValue(vin_checked)
{
	vin_checked = typeof(vin_checked) != 'undefined' ? vin_checked : false;
	
	document.getElementById('vehicle_value').innerHTML = "...updating...";
	var year = document.getElementById('vehicle_year').value;
	var make = document.getElementById('vehicle_make').value;
	var series = document.getElementById('vehicle_series').value;
	var body = document.getElementById('vehicle_body').value;
	var state = document.getElementById('title_state').value;
	var vin   = document.getElementById('vehicle_vin').value;
	
	if (vin.length < 9 || vin_checked)
	{
		
	
		var req = nada_lookup({
					id: 3, 
					params:[{
						action:'nada_info',
						function:'getValue',
						value_year:year,
						value_make:make,
						value_series:series,
						value_body:body,
						value_state:state
					}], 
					onSuccess: function (transport) {
						var result = transport.responseText.evalJSON();
						var value = result.result;
						document.getElementById('value').value = value;
						if(value == 0 || value == null)
						{
							document.getElementById('vehicle_value').innerHTML = "Not Found";
						}else{
							document.getElementById('vehicle_value').innerHTML = "$" + value;
						}
					},
					onFailure: function (transport) {
						alert('ajax fail');
					}
				});
	}
	else
	{
		getVehicleValueFromVIN(vin);
	}
}

function getVehicleValueFromVIN(vin)
{
	document.getElementById('vehicle_value').innerHTML = "...updating...";
	var state = document.getElementById('title_state').value;
	
	if(vin.length < 9)
	{
		getVehicleValue();
		return;
	}
	var req = nada_lookup({
				id: 4, 
				params:[{
					action:'nada_info',
					function:'getValueFromVIN',
					value_vin:vin,
					value_state:state
				}], 
				onSuccess: function (transport) {
					var result = transport.responseText.evalJSON();
					if(result.result)
					{
						var value = result.result.value;
					}else{
						getVehicleValue(true);
						return;
					}
					document.getElementById('value').value = value;
					if(value == 0)
					{
						document.getElementById('vehicle_value').innerHTML = "Not Found";
						getVehicleValue(true);
						return;
					}else{
						document.getElementById('vehicle_value').innerHTML = "$" + value;
					}
				},
				onFailure: function (transport) {
					alert('ajax fail');
				}
			});
}
function update_select(select_id, arrVals)
{
	document.getElementById(select_id).innerHTML = "<option value=\"0\">Please Select:</option>";
	for (var y in arrVals)
	{
		if(typeof arrVals[y] === 'string')
		{
			document.getElementById(select_id).innerHTML += "<option value=" + y + ">" + trimAll(arrVals[y]) + "</option>";
		}
		
	}
}

function trimAll(sString)
{
	while (sString.substring(0,1) == ' ')
	{
		sString = sString.substring(1, sString.length);
	}
	while (sString.substring(sString.length-1, sString.length) == ' ')
	{
		sString = sString.substring(0,sString.length-1);
	}
	return sString;
}
