<?php
/**
 * @package Reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

require_once("advanced_sort.1.php");
require_once( SERVER_MODULE_DIR . "reporting/report_generic.class.php" );
require_once( SERVER_CODE_DIR . "base_report_query.class.php" );

class Report_Wizard_Query extends Base_Report_Query
{

		//We will use this to get the version tracking
		public $version = 1;
		private $db_master;
		private $db_slave;

		public function init()
		{

			try
			{
				$this->db_master = ECash::getMasterDb();
				$this->db_slave = ECash::getSlaveDb();
			}
			catch (Exception $e)
			{
				throw new Exception("Database Connection Failed");
			}
			return true;
		}
		public function DeleteXMLQuery($query_id)
		{
			$query = "
				delete
				from reports_listing
				where reports_listing_id = ?
			";

			try 
			{
				$this->db_master->queryPrepared($query, $query_id);
			}
			catch (Exception $e)
			{
				$report_log = get_log('reporting');
				$report_log->Write(var_export($e, true));
				die();
			}
		}

		public function SaveQuery($xml,$title = NULL, $version_update = "false")
		{
			$xmlpar = new XMLWorker();
			$arrinfo = $xmlpar->parse($xml);
			$result_items 	= $arrinfo[0]["children"][1]["children"];
			$query_items 	= $arrinfo[0]["children"][0]["children"];
			$company_id 	= $arrinfo[0]["children"][2]['tagData'];
			$company_name 	= $arrinfo[0]["children"][3]['tagData'];
			$parent_info	= @$arrinfo[0]["children"][5]['children'];

			if(@$title == "save" && @is_numeric($version_update))
			{
				$query = "
					select
						rl1.title,
						(
							select MAX(version)
							from reports_listing as rl2
							where rl2.title = rl1.title
						) as version
					from reports_listing as rl1
					where rl1.reports_listing_id = ?
				";

				try
				{
					$st = $this->db_master->queryPrepared($query, array($version_update));
				}
				catch (Exception $e)
				{
					$report_log = get_log('reporting');
					$report_log->Write(var_export($e,true));
					die();
				}

				while ($row = $st->fetch(PDO::FETCH_ASSOC))
				{
					$title = $row['title'];
					$version = intval($row['version']) + 1;
					$parent_id = $version_update;
				}
			}
			else
			{
				$title .= " ($company_name)";
				$version = 1;
				$parent_id = NULL;
			}

			/*we will need to cycle through to get the next version number */

			$query = "
				INSERT INTO reports_listing
				(reports_listing_parent_id, title, query_xml, agent_id, company_id,version)
				VALUES (?, ?, ?, ?, ?, ?)
			";
			$args = array($parent_id, $title, $xml, $_SESSION["agent_id"], $this->company_id, $version);

			try
			{
				$this->db_master->queryPrepared($query, $args);
			}
			catch (Exception $e)
			{
				$report_log = get_log('reporting');
				$report_log->Write(var_export($e,true));
				die();
			}
		}


		public function RetriveXML($xmlid)
		{
			try
			{
				$query = "
					select query_xml
					from reports_listing
					where reports_listing_id = ?
				";
				$data = $this->db_master->querySingleValue($query, array($xmlid));
			}
			catch (Exception $e)
			{
				$report_log = get_log('reporting');
				$report_log->Write(var_export($e,true));
				die();
			}
			return $data;
		}

		function GetSavedQueries()
		{

			try
			{
				$query = "
					select
						date_created,
						reports_listing_id,
						reports_listing_parent_id,
						title,
						agent_id,
						version,
						company_id
					from reports_listing
					order by title, version
				";
				$st = $this->db_master->query($query);
			}
			catch (Exception $e)
			{
				$report_log = get_log('reporting');
				$report_log->Write(var_export($e,true));
				die();
			}

			$data = array();

			while ($row = $st->fetch(PDO::FETCH_ASSOC))
			{
				$data[$row["reports_listing_id"]] = $row;
			}

			return $data;
		}

		public function GetViewDetails($table_view)
		{
			try
			{
				$st = $this->db_slave->query('DESC '.$table_view);
			}
			catch (Exception $e)
			{
				$report_log = get_log('reporting');
				$report_log->Write(var_export($e,true));
				die();
			}

			$view_type_arr = array();
			$items = array();
			$oper_array = array();

			while ($row = $st->fetch(PDO::FETCH_ASSOC))
			{
				$oper_array		= array();
				$view_type_arr = split("\(",$row['Type']);
				$view_type = $view_type_arr[0];
				$view_details_arr = array();
				$view_details = "";

				switch($view_type)
				{
					case"char":
					case"varchar":
					case"binary":
					case"varbinary":
					case"tinyblob":
					case"tinytext":
					case"blob":
					case"text":
					case"mediumblob":
					case"mediumtext":
					case"longblob":
					case"longtext":
					case"enum":
						$oper_array = array("in" => "In",
											"notin" => "Not In",
											"like" => "Like",
											"equalto" => "Equals");
						// Fix enums that are strings
						$view_type_arr[1] = str_replace("'","",$view_type_arr[1]);
						break;
					case"time":
					case"year":
					case"datetime":
					case"timestamp":
					case"date":
						$oper_array = array("between" => "Between");
						$row['Null'] = "NO";
						break;
					case"bool":
					case"boolean":
						$oper_array = array("equalto" => "Equals");
						break;
					case"tinyint":
					case"smallint":
					case"mediumint":
					case"int":
					case"integer":
					case"bigint":
					case"float":
					case"double":
					case"double precision":
					case"decimal":
					case"dec":
					case"date":
						$oper_array = array("in" => "In",
											"notin" => "Not In",
											"lessthan" => "Less Than",
											"lessthanequalto" => "Is Less Than or Equal To",
											"greaterthan" => "Greater Than",
											"greaterthanequalto" => "Is Greater Than or Equal To",
											"equalto" => "Equals");
						break;
				}

				if (count($view_type_arr) == 2)
				{
					$view_details_arr = split(")", $view_type_arr[1]);
					$view_details = htmlspecialchars($view_details_arr[0], ENT_QUOTES);
				}

				if($row['Null'] == "YES")
				{
					$oper_array = array_merge($oper_array, array("isnull" => "Is Null","isnotnull" => "Is Not Null"));
				}

				$items[$row['Field']] = array("Type" => $view_type, "Desc" => $view_details, "Operations" => $oper_array);
			}

			return $items;
		}

		public function GetItemList($table_field)
		{
			$data = array();

			$table_details = split("\.", $table_field);

			// We can look up application table no matter how fun it sounds to do so.
			if($table_details[0] != "application")
			{
				try
				{
					$query = "
						SELECT DISTINCT($table_field)
						FROM {$table_details[0]}
						ORDER BY $table_field
					";
					$st = $this->db_slave->query($query);
				}
				catch (Exception $e)
				{
					$report_log = get_log('reporting');
					$report_log->Write(var_export($e,true));
					die();
				}

				while ($row = $st->fetch(PDO::FETCH_NUM))
				{
					$data[] = htmlspecialchars($row[0]);
				}
			}
			return $data;
		}

		public function GetFieldValues($table_col_desc,$table_view)
		{
			$row_data = array();
			$view_details 	= $this->GetViewDetails($table_view);
			$table_details	= $this->GetReportTypes($table_view);

			$view_info		= array_merge($table_details[$table_col_desc],$view_details[$table_col_desc]);
			// We already know our values
			if($view_info["Type"] == "enum")
			{
				$view_info[$view_info['drop_down_column']] = split(",",$view_info["Desc"]);
			}
			else
			{
				if(!is_null($view_info['drop_down_column']))
				{
					$view_info[$view_info['drop_down_column']] = $this->GetItemList($view_info['drop_down_column']);
				}
			}

			return $view_info;
		}

		public function GetReportTypes($report_type = "report_applications")
		{
			$query = "
				select
					table_name,
					column_name,
					human_readable_name,
					drop_down_column
				from reports_columns
				where table_name = '$report_type'
				order by human_readable_name
			";

			try
			{
				$st = $this->db_slave->query($query);
			}
			catch (Exception $e)
			{
				$report_log = get_log('reporting');
				$report_log->Write(var_export($e,true));
				die();
			}

			$data = array();
			while ($row = $st->fetch(PDO::FETCH_ASSOC))
			{
				$data[$row["column_name"]] = $row;
			}
			return $data;
		}

		public function GetReportGroups()
		{
			$query = "
				select DISTINCT(table_name)
				from reports_columns
				order by table_name
			";
			try
			{
				$st = $this->db->query($query);
			}
			catch (Exception $e)
			{
				$report_log = get_log('reporting');
				$report_log->Write(var_export($e,true));
				die();
			}

			$data = array();
			$table_name_arr = array();

			while ($row = $st->fetch(PDO::FETCH_ASSOC))
			{
				if(isset($this->permissions[$this->company_id][$row['table_name']]))
				{
					$row['display_name'] = $this->permissions[$this->company_id][$row['table_name']]->description;
					$data[] = $row;
				}
			}

			return $data;
		}

		public function swapSQLOperation($value)
		{
			$oper_array = array("in" => "IN",
								"notin" => "NOT IN",
								"lessthan" => "<",
								"lessthanequalto" => "<=",
								"greaterthan" => ">",
								"greaterthanequalto" => ">=",
								"between" => "IN BETWEEN",
								"notbetween" => "NOT IN Between",
								"equalto" => "=",
								"notequalto" => "!=",
								"like" => "LIKE",
								"notlike" => "NOT LIKE");
			return $oper_array[$value];
		}

		public function QueryXML($xml,$company_id)
		{

			$xmlpar = new XMLWorker();
			$arrinfo = $xmlpar->parse($xml);

			// Result Select

			$result_items 	= $arrinfo[0]["children"][1]["children"];
			$query_items 	= $arrinfo[0]["children"][0]["children"];
			$table_report 	= $arrinfo[0]["children"][2]['tagData'];
			$data 			= array();
			// Switch Report
			$data = $this->QueryApplications($arrinfo,$company_id);
			return $data;
		}

		public function QueryApplications($arrinfo,$company_id)
		{
			$FILE = __FILE__;
			$METHOD = __METHOD__;
			$LINE = __LINE__;
			$_SESSION['reports']["report_wizard"]["table_details"] = null;

			$result_items 	= $arrinfo[0]["children"][1]["children"];
			$query_items 	= $arrinfo[0]["children"][0]["children"];
			$company_id 	= $arrinfo[0]["children"][2]['tagData'];
			$company_name 	= $arrinfo[0]["children"][3]['tagData'];
			$table_report 	= $arrinfo[0]["children"][4]['tagData'];
			$where_clause 	= "";
			$query_select 	= "";
			$query_where 	= "";
			$list_vals		= "";
			$operation 		= "";
			$case_prop		= "";
			$st				= "";
			$dbIntArray		= array("tinyint","bool","boolean","smallint","mediumint","int","integer",
									"bigint","float","double","double precision","decimal","dec","date",
									"datetime","timestamp","time","year");

			$table_desc 	= $this->GetViewDetails($table_report);

			for($i=0; $i<count($result_items); $i++)
			{
				$query_select[] = "\t".$result_items[$i]["attrs"]["NAME"]." as '".$result_items[$i]["attrs"]["VALUE"]."'";
				$_SESSION['reports']["report_wizard"]["table_details"][$result_items[$i]["attrs"]["NAME"]] = $result_items[$i]["attrs"]["VALUE"];
			}

			// Where Clause
			for($i=0; $i<count($query_items); $i++)
			{
				$query_item = $query_items[$i]["children"];
				for($x=0; $x<count($query_item); $x++)
				{
					if($x==0) $where_clause = "{$where_clause}\n";

					// Gather Tables and format field

					switch($query_item[$x]["attrs"]["NAME"])
					{
						case "case":
							$case_prop = $query_item[$x]["attrs"]["VALUE"];
							break;
						case "operation":
							$operation = $this->swapSQLOperation($query_item[$x]["attrs"]["VALUE"]);
							$likesearch = in_array($query_item[$x]["attrs"]["VALUE"],array("like","notlike"));
							break;
						case "field_one":
							$where_clause .= "\n{$case_prop} {$query_item[$x]["attrs"]["VALUE"]} ";
							$field_prop = $table_desc[$query_item[$x]["attrs"]["VALUE"]];
							break;
						case "field_two":
							$where_clause .= " {$operation} {$query_item[$x]["attrs"]["VALUE"]} ";
							$operation = "";
							break;
						case "constant":
						case "field_list":
							$st = ($likesearch) ? "%": "";

							$list_vals = split(",",$query_item[$x]["attrs"]["VALUE"]);
							for($y=0; $y<count($list_vals); $y++)
							{
								if(!in_array($field_prop["Type"],$dbIntArray))
								{
									$list_vals[$y] = "'{$st}".$list_vals[$y]."{$st}'";
								}
							}

							$where_clause .= "{$operation} (".implode(",",$list_vals).")";

							$operation = "";

							break;
						case "timestamp":
							$list_vals = split(",",$query_item[$x]["attrs"]["VALUE"]);
							$date_one = date("Ymd000000",strtotime($list_vals[0]));
							$date_two = date("Ymd235959",strtotime($list_vals[1]));
							$where_clause .= " between {$date_one} AND {$date_two}";
							break;
						case "date":
							$list_vals = split(",",$query_item[$x]["attrs"]["VALUE"]);
							$date_one = date("Y-m-d",strtotime($list_vals[0]));
							$date_two = date("Y-m-d",strtotime($list_vals[1]));
							$where_clause .= " between '{$date_one}' AND '{$date_two}'";
							break;
						case "datetime":
							$list_vals = split(",",$query_item[$x]["attrs"]["VALUE"]);
							$date_one = date("Y-m-d 00:00:00",strtotime($list_vals[0]));
							$date_two = date("Y-m-d 23:59:59",strtotime($list_vals[1]));
							$where_clause .= " between {$date_one} AND {$date_two}";
							break;
						case"time":
							$list_vals = split(",",$query_item[$x]["attrs"]["VALUE"]);
							$date_one = date("00:00:00",strtotime($list_vals[0]));
							$date_two = date("23:59:59",strtotime($list_vals[1]));
							$where_clause .= " between {$date_one} AND {$date_two}";
							break;
						case"year":
							$list_vals = split(",",$query_item[$x]["attrs"]["VALUE"]);
							$date_one = date("Y",strtotime($list_vals[0]));
							$date_two = date("Y",strtotime($list_vals[1]));
							$where_clause .= " between {$date_one} AND {$date_two}";
							break;
					}

				}
				// Set Company ID
				if($table_desc["_company_id"])
				{
					if(isset($_SESSION) && is_array($_SESSION['auth_company']['id']) && count($_SESSION['auth_company']['id']) > 0)
					{
						$auth_company_ids = $_SESSION['auth_company']['id'];
					}
					else
					{
						$auth_company_ids = array(-1);
					}

					if( $company_id > 0 )
						$company_list = "'{$company_id}'";
					else
						$company_list = "'" . implode("','", $auth_company_ids) . "'";

					$where_clause .= " AND _company_id IN ($company_list)";
				}

			}
			//$where_clause .= " LIMIT 1000";
			$where_clause = "SELECT DISTINCT \n".implode($query_select,",\n")." \nFROM {$table_report} \n\t\nWHERE".$where_clause;

			$query = "-- eCash 3.0, File: $FILE , Method: $METHOD, Line: $LINE\n $where_clause";
			file_put_contents("/tmp/wiz.txt",$query);

			try 
			{
				$st = $this->db_slave->query($query);
			}
			catch (Exception $e)
			{
				$report_log = get_log('reporting');
				$report_log->Write(var_export($e,true));
				die();
			}

			return $st->fetchAll(PDO::FETCH_ASSOC);
		}
}

class Report extends Report_Generic
{

	public $MAX_REPORT_SHOW;
	private $repQuery;

	public function __construct(Server $server, $request, $module_name, $report_name)
	{
		parent::__construct($server, $request, $module_name, $report_name);

		$this->MAX_REPORT_SHOW = 1000;
		try 
		{
			$this->permissions = $this->server->acl->Get_Allowed_Sections($this->server->agent_id);
			$this->repQuery = new Report_Wizard_Query($this->server);
			$this->repQuery->init();
		}
		catch (Exception  $e)
		{
			print("<font color=red size=2>Unable to execute report. Reporting server may be unavailable.</font>");
			die();
		}
	}

	private function pageParse()
	{
		$pageitems = array();
		$starter = ($this->request->page - 1) * $this->request->pageSize;
		$result_temp = $_SESSION['reports']["report_wizard"]["result"];
		$result = array();
		for($i=0; $i<count($result_temp); $i++)
		{
			$item = array();
			$save_item = true;
			foreach ($result_temp[$i] as $key => $value)
			{
				$key = array_search($key,$_SESSION['reports']["report_wizard"]["table_details"]);
				if(($this->request->filter_field ==  $key) && $this->request->filter_text != "")
				{
					if(!stristr($value,$this->request->filter_text))
					{
						$save_item = false;
						break;
					}
				}
				$item[$key] = htmlentities($value);
			}
			if($save_item)
				$result[] = $item;
		}

		$_SESSION['reports']["report_wizard"]["totalitems"] = count($result);

		if($this->request->sort && $this->request->sortDir)
		{
			$direction = ($this->request->sortDir == "DESC") ? SORT_DESC : SORT_ASC;
			$result = Advanced_Sort::Sort_Data($result, $this->request->sort, $direction);
		}

		$ender = (count($result) < ($starter + $this->request->pageSize))
					? count($result)
					: ($starter + $this->request->pageSize);
		for($i = $starter; $i<$ender; $i++)
		{
			$pageitems[] = $result[$i];
		}
		return $pageitems;
	}

	private function xmlPageExtras($currentpage = 0)
	{
		$xmlextra = "";
		$totpage  = 0;
		$xmlextra = "<?xml version=\"1.0\" ?>\n<root>\n";
		$totalitems = count($_SESSION['reports']["report_wizard"]["result"]);
		$xmlextra .= "<totalitems value='".$totalitems."'>".$totalitems."</totalitems>\n";
		$xmlextra .= "<maxshow value='{$this->MAX_REPORT_SHOW}'>{$this->MAX_REPORT_SHOW}</maxshow>\n";
		$totpage  = ceil($totalitems / $this->MAX_REPORT_SHOW);
		$xmlextra .= "<totalpages value='{$totpage}'>{$totpage}</totalpages>\n";

		$xmlextra .= "<header_items>\n";
		// Was having problems with foreach for some reason.
		$td_keys = array_keys($_SESSION['reports']["report_wizard"]["table_details"]);
		for($i=0; $i<count($td_keys); $i++)
		{
			$key = $td_keys[$i];
			$value = $_SESSION['reports']["report_wizard"]["table_details"][$key];
			$xmlextra .= "\t<item name='{$key}' value='{$value}'>{$value}</item>\n";
		}
		$xmlextra .= "</header_items>\n";

		$xmlextra .= "<reportheader value='{$totpage}'>{$totpage}</reportheader>\n";
		$xmlextra .= "<currentpage value='".($currentpage + 1)."'>".($currentpage + 1)."</currentpage>\n";
		$xmlextra .= "</root>";
		return $xmlextra;
	}

	public function escapeCsv($value)
	{
		$value = str_replace('"', '""', $value);
		$value = '"' . $value . '"';

		return $value;
	}

	public function getCsvFromArray($array)
	{
		$output = '';
		$first = true;

		foreach ($array as $key)
		{
			if (!$first)
			{
				$output .= ',';
			}
			else
			{
				$first = false;
			}

			$output .= $this->escapeCsv($key);
		}

		return $output . "\n";
	}

	public function Download_Report()
	{

		$company_id = $this->server->company_id;
		$xmlextra = null;
		if(@$this->request->csv)
		{
			$export = "";
			$keys 	= array();

			if(@$this->request->build_query)
			{
				$result = $this->repQuery->QueryXML($this->request->build_query,$company_id);
				$_SESSION['reports']["report_wizard"]["result"] = $result;
			}
			elseif (@$_SESSION['reports']["report_wizard"]["result"])
			{
				$result = $_SESSION['reports']["report_wizard"]["result"];
			}

			if(@$this->request->csv_headers)
			{
				$export .= $this->getCsvFromArray(array_keys($result[0]));
			}

			foreach ($result as $row)
			{
				$export .= $this->getCsvFromArray($row);
			}

			header("Accept-Ranges: bytes\n");
			header("Content-Disposition: attachment; filename=report.csv\n");
			header("Content-Length: ".strlen($export)."\n");
			header("Content-Type: text/csv\n\n");
			print($export);
		}
		else
		{
			$result = array();
			if(@$this->request->delete_query)
			{
				$result = $this->repQuery->DeleteXMLQuery($this->request->delete_query);
			}
			elseif(@$this->request->get_report_types)
			{
				// Export Report Types
				$result = $this->repQuery->GetReportTypes($this->request->get_report_types);
			}
			elseif(@$this->request->get_query)
			{
				// Create XML Results
				$_SESSION['reports']["report_wizard"]["result"] = $this->repQuery->QueryXML($this->repQuery->RetriveXML($this->request->get_query),$company_id);
				$xmlextra = $this->xmlPageExtras(0);
				//$result = $this->pageParse(0);
			}
			elseif(@$this->request->get_xml)
			{
				// Create XML Results
				header('Content-Type: text/xml');
				print($this->repQuery->RetriveXML($this->request->get_xml));
			}
			elseif(@$this->request->build_query)
			{
				// Create XML Results
				if (get_magic_quotes_gpc())
				{
					$this->request->build_query = stripslashes($this->request->build_query);
					$this->request->savequery	= stripslashes($this->request->savequery);
				}

				// We want to Save the query?
				if(@$this->request->savequery != "")
				{
					$this->repQuery->SaveQuery($this->request->build_query,$this->request->savequery,$this->request->version_update);
				}

				$_SESSION['reports']["report_wizard"]["result"] = $this->repQuery->QueryXML($this->request->build_query,$company_id);
				$xmlextra = $this->xmlPageExtras(0);
				//$result = $this->pageParse(0);
			}
			elseif (@$this->request->get_report_groups)
			{
				// Export Report Types
				$result = $this->repQuery->GetReportGroups();
			}
			elseif (@$this->request->get_distinct_field && @$this->request->table_view)
			{
				// Export Report Types
				$result = $this->repQuery->GetFieldValues($this->request->get_distinct_field,$this->request->table_view);
			}
			elseif (@$this->request->get_reports)
			{
				$result = $this->repQuery->GetSavedQueries();
			}
			elseif (@$this->request->get_page)
			{
				$xmlpar = new XMLWorker();
				$xmlextra = $xmlpar->encode_result_rows($this->pageParse($this->request));
			}

			if(count($result))
			{
				$xmlpar = new XMLWorker();
				$xml_result = $xmlpar->encode($result,1,$xmlextra);
				header('Content-Type: text/xml');
				print($xml_result);
			}
			elseif (!is_null($xmlextra))
			{
				header('Content-Type: text/xml');
				print($xmlextra);
			}

		}
		die();
	}
}

class XMLWorker {

   var $arrOutput = array();
   var $resParser;
   var $strXmlData;

   function parse($strInputXML) 
   {
		   $report_log = get_log('reporting');
           $this->resParser = xml_parser_create ();
           xml_set_object($this->resParser,$this);
           xml_set_element_handler($this->resParser, "tagOpen", "tagClosed");

           xml_set_character_data_handler($this->resParser, "tagData");
           $this->strXmlData = xml_parse($this->resParser,$strInputXML );

           if(!$this->strXmlData)
           {
           		//Strip and try again
				$strInputXML = stripslashes($strInputXML);
	            $this->resParser = xml_parser_create ();
	            xml_set_object($this->resParser,$this);
	            xml_set_element_handler($this->resParser, "tagOpen", "tagClosed");

	            xml_set_character_data_handler($this->resParser, "tagData");
	            $this->strXmlData = xml_parse($this->resParser,$strInputXML );
                if(!$this->strXmlData)
           		{
		           $report_log->Write(sprintf("XML error (2nd Try): %s at line %d\n$strInputXML",
		           xml_error_string(xml_get_error_code($this->resParser)),
		           xml_get_current_line_number($this->resParser)));
				   die("An error occured in the Report Wizard and has been logged.\nPlease contact support.");

           		}
           }

           return $this->arrOutput;
   }

   function tagOpen($parser, $name, $attrs) 
   {
       $tag=array("name"=>$name,"attrs"=>$attrs);
       array_push($this->arrOutput,$tag);
   }

   function tagData($parser, $tagData) 
   {
       if(trim($tagData)) 
	   {
           if(isset($this->arrOutput[count($this->arrOutput)-1]['tagData'])) 
		   {
               $this->arrOutput[count($this->arrOutput)-1]['tagData'] .= $tagData;
           }
           else 
		   {
               $this->arrOutput[count($this->arrOutput)-1]['tagData'] = $tagData;
           }
       }
   }

   function tagClosed($parser, $name) 
   {
       $this->arrOutput[count($this->arrOutput)-2]['children'][] = $this->arrOutput[count($this->arrOutput)-1];
       array_pop($this->arrOutput);
   }

   // This function is very cutsomized for this report, not very friendly
    function encode($array, $level=1,$xml_extra = '') 
	{
		$xml = "<?xml version=\"1.0\" ?>\n<root>\n";

		if(array_keys($array))
		{
			$xml .= $this->encode_process($array);
		}
		else
		{
			for($i=0; $i<count($array); $i++)
			{
				$xml .= "\t<row>\n";
				$line_item = $array[$i];
				foreach($line_item as $key => $value)
				{
					if(is_array($value))
					{
						$xml .= $this->encode_process($value);
					}
					else
					{
						$xml .= "\t\t<col name=\"$key\" value=\"$value\">$value</col>\n";
					}
				}
				$xml .= "\t</row>\n";
			}
		}
		$xml .= $xml_extra;
		$xml .= "</root>";
	    return $xml;
	}

	function encode_result_rows($result)
	{
		$totalitems = $_SESSION['reports']["report_wizard"]["totalitems"];
		$xml = "<?xml version=\"1.0\" ?>\n<root>\n";
		$xml .= "<ITEMS>\n";
		for($i=0; $i<count($result); $i++)
		{
			$xml .= "\t<ITEM>\n";
			foreach ($result[$i] as $key => $value)
			{
				$xml .= "\t\t<{$key}>{$value}</{$key}>\n";
			}
			$xml .= "\t</ITEM>\n";
		}
		$xml .= "</ITEMS>\n";
		$xml .= "<totalitems value='".$totalitems."'>".$totalitems."</totalitems>\n";
		$xml .= "</root>";
		return $xml;
	}

	function encode_process($arr)
	{
		$xml = null;
		foreach($arr as $key => $value)
		{
			if(is_array($value))
			{
				$xml .= "\t\t<list name=\"$key\">\n";
				if(array_keys($value))
				{
					foreach($value as $key2 => $value2)
					{
						$xml .= "\t\t\t<item name=\"$key2\" value=\"$value2\">$value2</item>\n";
					}
				}
				else
				{
					for($i=0; $i<count($value); $i++)
					{
						$xml .= "\t\t\t<item value=\"{$value[$i]}\">{$value[$i]}</item>\n";
					}
				}
				$xml .= "\t\t</list>\n";
			}
			else
			{
				$xml .= "\t\t<col name=\"$key\" value=\"$value\">$value</col>\n";
			}
		}
		return $xml;
	}
}

?>
