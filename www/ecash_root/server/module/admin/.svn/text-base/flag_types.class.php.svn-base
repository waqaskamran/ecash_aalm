<?php

/**
 * @author Jeff Day
 *
 */
class Flag_Type_Config
{
	/**
	 * @var Server
	 */
	private $server;

	/**
	 * @var stdClass
	 */
	private $request;

	/**
	 * @param Server $server
	 * @param stdClass $request
	 */
	public function __construct(Server $server, $request)
	{
        $this->server = $server;
        $this->request = $request;

        ECash::getTransport()->Set_Data($request);

	}
	
	/**
	 * Saves the flag type by either making a new one or by modifying the existing one
	 * depending on whether the flag_type_id is there.
	 * Doesn't do any verification since that's done on the front end.
	 *
	 */
	public function saveFlagType()
	{
		$request = $this->request;
		/* @var $flag_type ECash_Models_FlagType */
		if($request->flag_type_id)
		{
			$flag_type = ECash::getFactory()->getModel('FlagType');
			$flag_type->loadBy(array('flag_type_id' => $this->request->flag_type_id));
		} 
		else 
		{
			$flag_type = ECash::getFactory()->getModel('FlagType');
			$flag_type->date_modified = date('Y-m-d H:i:s');
			$flag_type->date_created = date('Y-m-d H:i:s');
		}	
		$flag_type->active_status = $request->active_status;
		$flag_type->name = $request->name;
		$flag_type->name_short = $request->name_short;
		$flag_type->save();
		$request->result = 'Flag type successfully saved';
		ECash::getTransport()->Set_Data($request);
	}

}

?>
