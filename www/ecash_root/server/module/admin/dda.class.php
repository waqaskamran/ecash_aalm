<?php

class dda
{
    private $resources;
    protected $request;
    protected $server;
	protected $log;

    // Override me if you would like, but always accept the same
    // arguments.
    public function __construct(Server $server, $request)
    {
        $this->server = $server;
        $this->request = $request;
		$this->log = new Applog(APPLOG_SUBDIRECTORY.'/repairs', APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT);

        if("dda" == strtolower(get_class($this)))
        {
            $this->resources = array(
                //'dummy',
                'application',
                'schedule',
                'controlling_agent',
                'adjustments',
                'queues',
                'reaffiliate',
                );

            foreach($this->resources as $resource_name)
            {
                $class_name = "dda_".$resource_name;
                $file_name = dirname(__FILE__)."/dda_".strtolower($resource_name).".class.php";

                require_once($file_name);
                $this->$resource_name = new $class_name($server, $request);
            }
        }
    }

    // You should never have to override this
    protected function build_dda_table($content)
    {
        $title = $this->get_resource_name();

        $return =   "<table width='100%' border='0' style='background: #EEEEEE;'>";
        $return .=      "<tr class='valign_top'>";
        $return .=          "<th style='background-color: #FBCD57;'>";
        $return .=              "Direct Data Administration";
        $return .=              ($title ? ": ".$title : "");
        $return .=          "</th>";
        $return .=      "</tr>";
        $return .=      "<tr class='valign_top'>";
        $return .=          "<td style='text-align: left;'>";
        $return .=              $content;
        $return .=          "</td>";
        $return .=      "</tr>";
        $return .=  "</table>";

        return($return);
    }

    // Override me to make me appear in the list of resources
    // Return string
    protected function get_resource_name()
    {
        return("");
    }

    // Pass this a string name and an optional pre-selected choice
    // It will pass back a string to be included in HTML
    protected function build_html_form_input($name, $selected=NULL)
    {
        $return = "<input type='text' name='".htmlentities($name)."' value='".htmlentities(strval($selected))."'>";

        return($return);
    }

    // Pass this a string name and an associative array of options
    // with an optional pre-selected choice (given by the KEY of the
    // array)
    // It will pass back a string to be included in HTML
    protected function build_html_form_select($name, $options_map, $selected=NULL)
    {
        $return = "<select name='".htmlentities($name)."'>";
        foreach($options_map as $value => $shown)
        {
            $return .= "<option value='".htmlentities($value)."'".(NULL !== $selected && $value == $selected ? " selected='selected'" : "" ).">".htmlentities($shown)."</option>";
        }
        $return .= "</select>";

        return($return);
    }

    // Returns TRUE on success, error message on error
    protected function save_history($mixed)
    {
        $db = ECash::getMasterDb();

        $mixed = serialize($mixed);
        $mixed = $db->quote($mixed);
		
        $query = "
            INSERT INTO `dda_history`
                SET `date`          = NOW()
                ,   `class`         = '".get_class($this)."'
                ,   `serialized`    = $mixed
            ";
        $db->exec($query);

        return(TRUE);
    }

    // Override me!
    // This should set ECash::getTransport()->Set_Data(...)
    // Make sure the data passed contains the following member
    // variables:
    // 'header' => A string containing things to appear in the page head
    // 'display' => A string containing things to display in the content section
    
    // mantis:3651
    public function main()
    {
    	
        $_SESSION['dda'] = NULL;
       
        if(isset($this->request->dda_resource) && is_string($this->request->dda_resource))
        {
            if(in_array($this->request->dda_resource, $this->resources))
            {
                $_SESSION['dda'] = $this->request->dda_resource;
            }
        }

        if($_SESSION['dda'] == NULL)
        {
            $_SESSION['dda_application'] = null;
            return false;
        }
        else
        {
            $obj = $_SESSION['dda'];
            $obj = $this->$obj;
            $obj->main();
        }
    }
/*
    public function main()
    {
        $dda = null;
        if(isset($this->request->dda_resource) && is_string($this->request->dda_resource))
        {
            if(in_array($this->request->dda_resource, $this->resources))
            {
                $dda = $this->request->dda_resource;
            }
            else
            {
                $dda = NULL;
            }
        }

        if(NULL === $dda)
        {
            $return = new stdClass();
            $return->header = "";
            $return->display = $this->build_dda_table("Unknown action");
            ECash::getTransport()->Set_Data($return);
        }
        else
        {
            $obj = $dda;
            $obj = $this->$obj;
            $obj->main();
        }
    }
*/
}

?>
