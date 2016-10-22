<?php

require_once(LIB_DIR. "form.class.php");
require_once("display_parent.abst.php");

class Display_View extends Display_Parent
{
    protected $module_name;
    protected $transport;

    public function __construct(ECash_Transport $transport, $module_name)
    {
        $this->module_name = $module_name;
        $this->transport = $transport;
    }

    public function Get_Header()
    {
        return(NULL);
    }

    public function Get_Body_Tags()
    {
        return(NULL);
    }

    public function Get_Module_HTML()
    {
		$data = ECash::getTransport()->Get_Data();

        if(is_object($data->search_results))
        {
            header("Accept-Ranges: bytes\n");
            header("Content-Disposition: attachment; filename={$data->search_results->file_name}\n");
            header("Content-Length: ".strlen($data->search_results->file_contents)."\n");
            header("Content-Type: application/octet-stream\n\n");
            print($data->search_results->file_contents);
            die(0);
        }
        else
        {
            $form = new Form(CLIENT_VIEW_DIR."/corrections.html");
            $subs = new stdClass();
            $subs->not_yet_downloaded = $data->not_yet_downloaded;
            $subs->search_dates = $data->search_dates;
            $subs->search_results = $data->search_results;
            return($form->As_String($subs));
        }
    }
}

?>
