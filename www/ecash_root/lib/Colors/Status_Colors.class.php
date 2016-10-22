<?php

// This class is so that we can override something for company specific coloration

class Status_Colors
{	
	public $color_map_ids = array(
		5   => '#43ff42',
		6   => '#9ac18f',
		7   => '#43cc42',
		8   => '#B0C4DE',
		9   => '#B0C4DE',
		10  => '#e3b9e9',
		11  => '#e3b9e9',
		12  => '#aa9ed5',
		13  => '#a7bad3',
		14  => '#d5addb',
		15  => '#8eb284',
		16  => '#43e242',
		17  => '#EFCAB9',
		18  => '#B5A8e3',
		19  => '#9e93c7',
		20  => '#ffe613',
		21  => '#EEF093',
		22  => '#EFA284',
		23  => '#b5b64a',
		24  => '#b5b64a',
		25  => '#a8a945',
		26  => '#8d8e3a',
		27  => '#93b989',
		100 => '#778d8e',
		101 => '#778d8e',
		102 => '#778d8e',
		103 => '#778d8e',
		104 => '#778d8e',
		105 => '#778d8e',
		106 => '#778d8e',
		107 => '#778d8e',
		108 => '#778d8e',
		109 => '#E0F4D9',
		110 => '#778d8e',
		111 => '#d9d9d9',
		112 => '#c2c2c2',
		113 => '#ababab',
		114 => '#EEC0A9',
		115 => '#8A94F3',
		116 => '#34E882',
		117 => '#778d8e',
		118 => '#778d8e',
		119 => '#778d8e',
		120 => '#778d8e',
		121 => '#ded373',
		122 => '#bfde75',
		123 => '#ee5f47',
		124 => '#de9e74',
		125 => '#b5b94c',
		126 => '#eba261',
		127 => '#bfde75',
		128 => '#CEA943',
		129 => '#CEA900',
		130 => '#ff669b',
		131 => '#ff69d3',
		132 => '#fb9746',
		133 => '#fbc080',
		134 => '#fb9746',
		135 => '#ef3c10',
		136 => '#ef860c',
		137 => '#e35d5d',
		138 => '#f36464',
		139 => '#ef630e',
		140 => '#ef1113',
		141 => '#bc99c1',
		142 => '#43f442',
		143 => '#43d542',
		144 => '#43e242',
		145 => '#ff6571',
		146 => '#778d8e',
		147 => '#e29f5d',
		148 => '#e29f5d',
		149 => '#d49557',
		153 => '#c4daf8',
		154 => '#9cadc5',
		155 => '#c8a3ce',
		156 => '#43eb42',
		157 => '#d05656',
		160 => '#999999',
		161 => '#cccccc',
		190 => '#df4f32',
                192 => '#df4f32',
		193 => '#FFD700',
                194 => '#9e93c7',
	);

	function getStatusColorByName($name)
	{
	}

	function getStatusColorById($application_status_id)
	{
		if (array_key_exists($application_status_id, $this->color_map_ids))
		{
			return $this->color_map_ids[$application_status_id];
		}

		// Return the "default color"
		return '#43eb42';
	}

	// This is what's called by build_display and such
	// The current system works by ID, I plan on deviating
	// from that, but I need a central entry point so I can
	// decide customer-specific behavior based on a number
	// of items.
	function getStatusColor($application_id, $application_status_id = NULL)
	{
		if ($application_status_id != NULL)
		{
			$color = $this->getStatusColorById($application_status_id);
			return $color;
		}
	
		$app = ECash::getApplicationById($application_id);

		return $this->getStatusColorById($app->application_status_id);
	}
}

?>
