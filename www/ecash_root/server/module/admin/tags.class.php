<?php

require_once (SQL_LIB_DIR.'tagging.lib.php');

class Tags
{
	private $transport;
	private $tags;
	private $request;

	/**
	 *
	 */
	public function __construct(Server $server, $request)
	{
		$this->request = $request;
		$this->transport = ECash::getTransport();
	}


	/**
	 *
	 */
	public function Modify_Weights()
	{
		$result = array();

		$weight_map = $this->request->weights;
		
		try 
		{
			Set_Tag_Weights($weight_map);
		} 
		catch (Exception $e) 
		{
			$extra_data['msg'] = 'Investor Group Weights Could Not be Adjusted: '.$e->getMessage();
			return $this->_Fetch_Data($extra_data);
		}
		
		$extra_data['msg'] = 'Investor Group Weights Adjusted';
		
		return $this->_Fetch_Data($extra_data);
	}

	/**
	 * 
	 */
	public function Add_Investor_Group()
	{
		$short_name = $this->request->new_ig_name_short;
		$name       = $this->request->new_ig_name;
		
		if(! empty($name) && !empty($short_name))
		{
			Create_Tag($short_name, $name);
			$extra_data['msg'] = 'Investor Group Added';
		}
		else
		{
			$extra_data['msg'] = 'Error adding Investor Group';
		}

		return $this->_Fetch_Data($extra_data);
	}


	/**
	 *
	 */
	public function Display()
	{
		return $this->_Fetch_Data();
	}


	private function _Fetch_Data($extra_data = array())
	{
		$this->tags = $data['tags'] = Load_Tags();
		$data['msg'] = '';

		ECash::getTransport()->Set_Data(array_merge($data, $extra_data));

		return TRUE;
	}
}

?>
