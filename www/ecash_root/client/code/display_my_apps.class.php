<?php

require_once(CLIENT_CODE_DIR . "display_parent.abst.php");

class Display_View extends Display_Parent
{


	public function Get_Menu_HTML()
	{
		        if($_SESSION["myapps"]["show_past"])
        {
            $display_my_apps_menu .=          "[ <a href=\"?show_past=0\">Hide Expired Records</a> ]";
        }
        else
        {
            $display_my_apps_menu .=          "[ <a href=\"?show_past=1\">Show Expired Records</a> ]";
        }
        $display_my_apps_menu .=              "&nbsp;";
        if($_SESSION["myapps"]["show_future"])
        {
            $display_my_apps_menu .=          "[ <a href=\"?show_future=0\">Hide Records Waiting on Followup</a> ]";
        }
        else
        {
            $display_my_apps_menu .=          "[ <a href=\"?show_future=1\">Show Records Waiting on Followup</a> ]";
        }
        $display_my_apps_menu .=              "&nbsp;";
        if($_SESSION["myapps"]["show_unset"])
        {
            $display_my_apps_menu .=          "[ <a href=\"?show_unset=0\">Hide Records Without a Followup</a> ]";
        }
        else
        {
            $display_my_apps_menu .=          "[ <a href=\"?show_unset=1\">Show Records Without a Followup</a> ]";
        }
		 return $display_my_apps_menu;
	}


	public function Get_Rows_HTML()
	{
		$output="";
		if($this->data->rows)
        {
            $output .=      "<tr>";
            $output .=          "<td align=\"center\" colspan=\"7\">";
            $output .=              "<br>";
            $output .=          "</td>";
            $output .=      "</tr>";
            $output .=      "<tr>";
            $output .=          "<th align=\"center\">";
            if("application_id" == $_SESSION["myapps"]["sort_field"] && "ASC" == $_SESSION["myapps"]["sort_direction"])
            {
                $output .=          "<a href=\"?sort_field=application_id&sort_direction=DESC\">Application Id</a>";
            }
            else
            {
                $output .=          "<a href=\"?sort_field=application_id&sort_direction=ASC\">Application Id</a>";
            }
            $output .=          "</th>";
            $output .=          "<th align=\"center\">";
            if("name_first" == $_SESSION["myapps"]["sort_field"] && "ASC" == $_SESSION["myapps"]["sort_direction"])
            {
                $output .=          "<a href=\"?sort_field=name_first&sort_direction=DESC\">First Name</a>";
            }
            else
            {
                $output .=          "<a href=\"?sort_field=name_first&sort_direction=ASC\">First Name</a>";
            }
            $output .=          "</th>";
            $output .=          "<th align=\"center\">";
            if("name_last" == $_SESSION["myapps"]["sort_field"] && "ASC" == $_SESSION["myapps"]["sort_direction"])
            {
                $output .=          "<a href=\"?sort_field=name_last&sort_direction=DESC\">Last Name</a>";
            }
            else
            {
                $output .=          "<a href=\"?sort_field=name_last&sort_direction=ASC\">Last Name</a>";
            }
            $output .=          "</th>";
            $output .=          "<th align=\"center\">";
            if("ssn" == $_SESSION["myapps"]["sort_field"] && "ASC" == $_SESSION["myapps"]["sort_direction"])
            {
                $output .=          "<a href=\"?sort_field=ssn&sort_direction=DESC\">SSN</a>";
            }
            else
            {
                $output .=          "<a href=\"?sort_field=ssn&sort_direction=ASC\">SSN</a>";
            }
            $output .=          "</th>";
            $output .=          "<th align=\"center\">";
            if("date_expiration" == $_SESSION["myapps"]["sort_field"] && "ASC" == $_SESSION["myapps"]["sort_direction"])
            {
                $output .=          "<a href=\"?sort_field=date_expiration&sort_direction=DESC\">Expiration Date</a>";
            }
            else
            {
                $output .=          "<a href=\"?sort_field=date_expiration&sort_direction=ASC\">Expiration Date</a>";
            }
            $output .=          "</th>";
            $output .=          "<th align=\"center\">";
            if("date_next_contact" == $_SESSION["myapps"]["sort_field"] && "ASC" == $_SESSION["myapps"]["sort_direction"])
            {
                $output .=          "<a href=\"?sort_field=date_next_contact&sort_direction=DESC\">Follow-up Date</a>";
            }
            else
            {
                $output .=          "<a href=\"?sort_field=date_next_contact&sort_direction=ASC\">Follow-up Date</a>";
            }
            $output .=          "</th>";
            $output .=          "<th align=\"center\">";
            if("affiliation_area" == $_SESSION["myapps"]["sort_field"] && "ASC" == $_SESSION["myapps"]["sort_direction"])
            {
                $output .=          "<a href=\"?sort_field=affiliation_area&sort_direction=DESC\">Location</a>";
            }
            else
            {
                $output .=          "<a href=\"?sort_field=affiliation_area&sort_direction=ASC\">Location</a>";
            }
            $output .=          "</th>";
            $output .=      "</tr>";
            foreach($this->data->rows as $row)
            {
                $output .=  "<tr>";
                $output .=      "<td style=\"text-align: left;\">";
                switch($row["affiliation_area"])
                {

                    case "collections":
                        $output .=  "<a href=\"?module=collections&action=pull_followup_go_to_app&application_id={$row["application_id"]}\">";
                        break;

                    case "conversion":
                        $output .=  "<a href=\"?module=conversion&action=pull_followup_go_to_app&application_id={$row["application_id"]}\">";
                        break;

                    case "watch":
                        $output .=  "<a href=\"?module=watch&action=pull_followup_go_to_app&application_id={$row["application_id"]}\">";
                        break;

                    case "queue":
                    	/**
                    	 * wish this didn't have to be this way, but we don't know which queue an affiliated app came from
                    	 */
                    	$soogie = array_intersect(array("loan_servicing", "collections", "conversion", "watch"), $this->data->allowed_modules);
                    	$tmod = array_shift($soogie);
                    	$output .= "<a href=\"?module={$tmod}&action=pull_followup_go_to_app&application_id={$row["application_id"]}\">";
                        break;
                        
                    case "manual":
                    default:
                        $output .=  "<a href=\"?module=loan_servicing&mode=account_mgmt&action=pull_followup_go_to_app&application_id={$row["application_id"]}\">";
                        break;

                }
                $output .=              $row["application_id"];
                $output .=          "</a>";
                $output .=      "</td>";
                $output .=      "<td style=\"text-align: left;\">";
                $output .=          ucwords($row["name_first"]);
                $output .=      "</td>";
                $output .=      "<td style=\"text-align: left;\">";
                $output .=          ucwords($row["name_last"]);
                $output .=      "</td>";
                $output .=      "<td style=\"text-align: left;\">";
                $output .=          substr($row["ssn"],0,3)."-".substr($row["ssn"],3,2)."-".substr($row["ssn"],5,4);
                $output .=      "</td>";
                $output .=      "<td style=\"text-align: left; white-space: nowrap;\">";
                if(NULL === $row["date_expiration"])
                {
                    $output .=      "Never";
                }
                else
                {
//                    $output .=      "C.O.B. ".date('D M. jS, Y',strtotime($row["date_expiration"]));
                    $output .=      date('g:ia  D M. jS, Y',strtotime($row["date_expiration"]));
                }
                $output .=      "</td>";
                $output .=      "<td style=\"text-align: left; white-space: nowrap;\">";
                if(NULL === $row["date_next_contact"])
                {
                    $output .=      "Not Set";
                }
                else
                {
                    $output .=      date('g:ia D M. jS, Y',strtotime($row["date_next_contact"]));
                }
                $affiliation_area = preg_replace('/_/', ' ', $row['affiliation_area']);
                $output .=      "</td>";
                $output .=      "<td style=\"text-align: left;\">";
                $output .=          ucwords($affiliation_area);
                $output .=      "</td>";
                $output .=  "</tr>";
            }
        }
        else
        {
            $output .=  "<tr>";
            $output .=      "<td align=\"center\" colspan=\"7\">";
            $output .=          "<br><br><br>";
            $output .=          "No Records Found";
            $output .=          "<br><br><br>";
            $output .=      "</td>";
            $output .=  "</tr>";
        }
        
        return $output;
	}
	

	
}

?>
