<?php
class DisplayElement
{
	public $name;
	public $style;
	protected $stylestring;

	public function __construct()
	{
		// Hash the styles
		$style = array();
	}

	public function Set_Visible($is_visible, $option = null)
	{
		if ($is_visible)
		{
			$this->style = preg_replace('/none/', "block", $this->style);
		}
		else
		{
			$this->style = preg_replace('/block/', "none", $this->style);
		}
	}

	public function render()
	{
		$this->stylestring = "style=\"";
		foreach($this->style as $key => $val)
		{
			$this->stylestring .= "{$key}: {$val}; ";
		}
		$this->stylestring .= "\"";
	}	
}

class Layer extends DisplayElement
{
	public $modes;
	public $button_source;
	public $content;

	public function __construct($name, $modes, $btnfile = null)
	{
		parent::__construct();
		$this->name = $name;
		switch($modes)
		{
		case 'edit': $this->modes = array ("edit" => "none"); break;
		case 'view': $this->modes = array ("view" => "none"); break;
		case 'both': $this->modes = array ("edit" => "none", 
						   "view" => "none"); break;
		}
		$this->style = array("display" => "none");
		$this->button_source = $btnfile;
		$content = null;
	}

	public function Set_Visible($is_visible, $option='view')
	{
		foreach($this->modes as $key => $display)
		{
			if ($key == $option)
			{
				$this->modes[$key] = ($is_visible) ? "block" : "none";
			}
			else
			{
				$this->modes[$key] = "none";
			}
		}
	}

	public function render()
	{	
		if (is_null($this->content))
		{
			if (file_exists(CUSTOMER_LIB . "view/{$this->name}.html"))
			{
				$content = file_get_contents(CUSTOMER_LIB . "view/{$this->name}.html");
			}
			else
			{
				$content = file_get_contents(CLIENT_VIEW_DIR. "{$this->name}.html");
			}			
		}
		else
		{
			$content = $this->content;
		}
		
		foreach ($this->modes as $mode => $display)
		{
			$stylename = "{$this->name}_{$mode}_layer";
			$this->style["display"] = $display;
			parent::render();
			$content = str_replace("%%%{$stylename}%%%", $this->stylestring, $content);
		}
		
		return $content;
	}
}

class Group
{
	public $layers;	

	public function __construct($styles = null)
	{
		if (isset($styles))
		{
			$this->style = $styles;
		}
		else 
		{
			$this->style = array();
		}
		
		$this->style["display"] = "none";
		$this->layers = array();
	}

	public function render()
	{
		$content = "";
		foreach ($this->layers as $layer)
		{
			if(! is_array($layer->style)) continue;
			
			$layer->style = array_merge($this->style, $layer->style);
			$content .= $layer->render();
		}
		
		return $content;
	}

	public function Add_Layers()
	{
		$count = func_num_args();
		for ($i = 0; $i < $count; $i++)
		{
			$arg = func_get_arg($i);
			if($arg instanceof Layer) $this->layers[] = $arg;
		}		
	}

	public function Get_Layer($layername)
	{
		foreach($this->layers as $layer)
		{
			if ($layer->name == $layername) return $layer;
		}
		return NULL;
	}

	public function Has_Layer($layername)
	{
		foreach($this->layers as $layer)
		{
			if ($layer->name == $layername) return true;
		}
		return false;
	}

	public function Activate_Layer($layername, $mode)
	{
		foreach($this->layers as $layer)
		{
			if ($layer->name == $layername)
			{
				$layer->Set_Visible(true, $mode);
			}
			else
			{
				$layer->Set_Visible(false, $mode);
			}
		}
	}
}

class Layout extends DisplayElement
{
	public $groups;
	
	public function __construct($name)
	{
		$this->name = $name;
		$this->style = array("position" => "relative", "left" => "0px",
				     "height" => "500px",
				     "top" => "5px", "display" => "none", "class" => "clear",
				     "overflow" => "hidden");
		$this->groups = array();
	}

	public function Add_Groups()
	{
		$count = func_num_args();
		for ($i = 0; $i < $count; $i++)
		{
			$arg = func_get_arg($i);
			$this->groups[] = $arg;
		}		
	}

	public function Has_Layer($layername)
	{
		foreach($this->groups as $group)
		{
			if ($group->Has_Layer($layername)) return true;
		}
		return false;
	}

	public function Activate_Layer($layername, $mode)
	{
		foreach($this->groups as $group)
		{
			if ($group->Has_Layer($layername))
			{
				$this->Set_Visible(true);
				$group->Activate_Layer($layername, $mode);
				break;
			}
		}
	}

	public function Get_Layer($layername)
	{
		foreach($this->groups as $group)
		{
			$layer = $group->Get_Layer($layername);
			if (isset($layer)) return $layer;
		}
		return NULL;
	}

	public function Set_Defaults()
	{
		foreach($this->groups as $group)
		{
			$group->layers[0]->Set_Visible(true);
		}
	}

	public function render()
	{
		parent::render();
		$content = "";
		foreach($this->groups as $g)
		{
			$content .= $g->render();
		}

		$str = <<<EOS
<div id="{$this->name}" {$this->stylestring}>
{$content}
</div>
EOS;

		return $str;
	}

	public function Get_Button_Content()
	{
		$button_content = "";

		foreach($this->groups as $group)
		{
			foreach ($group->layers as $layer)
			{
				if (isset($layer->button_source))
				{
					$button_content .= 
						file_get_contents(CLIENT_VIEW_DIR . $layer->button_source . ".html");
				}
			}
		}
		
		return $button_content;
	}
}
?>
