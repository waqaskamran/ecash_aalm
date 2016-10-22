<?php

require_once SERVER_CODE_DIR.'module_interface.iface.php';
require_once SQL_LIB_DIR . 'util.func.php';

class NADA_Import
{
	private $transport;
	private $request;
	private $server;
	private $errors;
	private $results;

	public function __construct(Server $server, $request)
	{
		define('auto_detect_line_endings',true);
		set_time_limit(0);
		$this->errors = array();
		$this->results = array();
		$this->server = $server;
		$this->transport = ECash::getTransport();
	}

	public function Display()
	{
		ECash::getTransport()->Set_Data($data);
		return TRUE;
	}

	public function Import_Zip($request, $file)
	{

		$data = ECash::getTransport()->Get_Data();
		$filename = $file['nada_file']['tmp_name'];
		$za = new ZipArchive();
		$extracted_files = array();

		//check and see if we have a file
		if($filename)
		{
			//check if its a zip
			if( $za->open($filename) > 1)
			{
				$this->errors[] = "INVALID ZIP!";
			}
			else
			{
				for ($i=0; $i<$za->numFiles;$i++) 
				{
					$extracted_files[$za->getNameIndex($i)] = $za->getNameIndex($i);
				}

				//directory for extracting the files to
				$dir = ECash::getConfig()->NADA_TEMP_DIR;

				//create directory if directory doesn't exist
				if (!is_dir($dir))
				{
					mkdir($dir);
					$this->results[] = "Created Directory";
				}

			}

		}
		else
		{
			$this->errors[] = "No file was uploaded.";
		}



		//extract files to directory
		if(empty($this->errors))
		{
			if(!$za->extractTo($dir))
			{
				$this->errors[] =  "Error extracting to directory";
			}
		
			//files are extracted, done with zip stuffs
			$za->close();
		}
		//validate files that are uploaded
		//The files we're looking for, and the length of their records!
		$tables = array(
		'VicDescriptions.DAT' =>	array('name'=>'VicDescriptions.DAT','length'=>218),
		'VicValues.DAT' =>			array('name'=>'VicValues.DAT','length'=>29),
		'VehicleSegments.DAT' =>	array('name'=>'VehicleSegments.DAT','length'=>48),
		'TruckDuties.DAT' =>		array('name'=>'TruckDuties.DAT','length'=>46),
		'VicAttributes.DAT' =>		array('name'=>'VicAttributes.DAT','length'=>277),
		'AttributeTypes.DAT' =>		array('name'=>'AttributeTypes.DAT','length'=>48),
		'VacDescriptions.DAT' =>	array('name'=>'VacDescriptions.DAT','length'=>51),
		'VacValues.DAT' =>			array('name'=>'VacValues.DAT','length'=>30),
		'VacCategories.DAT' =>		array('name'=>'VacCategories.DAT','length'=>48),
		'VacExcludes.DAT' =>		array('name'=>'VacExcludes.DAT','length'=>23),
		'VacIncludes.DAT' =>		array('name'=>'VacIncludes.DAT','length'=>23),
		'VacBodyIncludes.DAT' =>	array('name'=>'VacBodyIncludes.DAT','length'=>22),
		'VacBodyNotAvailables.DAT'=>array('name'=>'VacBodyNotAvailables.DAT','length'=>22),
		'Mileage.DAT' =>			array('name'=>'Mileage.DAT','length'=>57),
		'VinPrefix.DAT' =>			array('name'=>'VinPrefix.DAT','length'=>40),
		'Regions.DAT' =>			array('name'=>'Regions.DAT','length'=>61),
		'States.DAT' =>				array('name'=>'States.DAT','length'=>63),
		'ValueTypes.DAT' =>			array('name'=>'ValueTypes.DAT','length'=>48),
		'BookFlags.DAT' =>			array('name'=>'BookFlags.DAT','length'=>58),
		'VinVacs.DAT' =>			array('name'=>'VinVacs.DAT','length'=>29),
		'VinAlternateVics.DAT' =>	array('name'=>'VinAlternateVics.DAT','length'=>29),
		'GvwRatings.DAT' =>			array('name'=>'GvwRatings.DAT','length'=>22),
		'TonRatings.DAT' =>			array('name'=>'TonRatings.DAT','length'=>15)
		);



		//files have been successfully uploaded and extracted, time to validate them!
		if(empty($this->errors))
		{
			//files that were uploaded and are being looked for
			$valid_upload = array_intersect_key($tables,$extracted_files);
			//files that were uploaded, but aren't being looked for
			$invalid_upload = array_diff_key($extracted_files,$tables);
			//files that weren't uploaded but ARE being looked for!
			$missing_upload = array_diff_key($tables,$extracted_files);
			
			//validate the files that were uploaded and are looking for
			foreach ($valid_upload as $file)
			{
				if($this->Validate_Formatting($file['name'],$file['length']))
				{
					$this->results[] = "{$file['name']} was extracted and validated";
				}
				else
				{
					$this->errors[] = "{$file['name']} was extracted, but is not a valid file!";
				}
			}

			//inform the user about files that aren't being used.
			foreach ($invalid_upload as $upload)
			{
				$this->errors[] = "{$upload} was extracted, but is not a recognized file.";
			}
			
			//check for previously existing files that weren't uploaded.
			foreach ($missing_upload as $upload)
			{
				if($this->Validate_Formatting($upload['name'],$upload['length']))
				{
					$this->results[] = "{$upload['name']} was not uploaded, but there is a valid file in the directory";
				}
				else
				{
					$this->errors[] = "{$upload['name']} was not uploaded! and does not exist in the directory";
				}
			}
			
			//The files have been uploaded and extracted, they've been validated, the messages have been created. 
			//time to make sure that the user gets blamed for it!
			$agent = ECash::getAgent();
			$agent->getTracking()->add('nada_update', null);
		}

		$data->errors = $this->errors;
		$data->results = $this->results;
		$data_obj = new stdclass();
		foreach($data as $key => $value)
		{
			$data_obj->$key = $value;
		}
		ECash::getTransport()->Set_Data($data_obj);

	}

	private function Validate_Formatting($filename,$record_length)
	{
		$dir = ECash::getConfig()->NADA_TEMP_DIR;
		$file = $dir."/".$filename;
		
		
		if(file_exists($file))
		{
			chmod($file, 0664);
			$fp	= fopen($file, "r");
			$i=0;
			//Read through the file and ensure that it has the first 6 characters specifying the period
			while (($contents = fgets($fp,$record_length)))
			{
				$i++;
				$period = substr($contents,0,6);

				if(!is_numeric($period))
				{
					//Something doesn't add up here!
					return false;
				}

			}
			//Is valid!
			return true;
		}
		else
		{
			return false;
		}
	}




}

?>
