<?php
/**
 * @package PBX
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 * @created Feb 27, 2007
 *
 * @version $Revision$
 */

class eCash_PBX_History
{
	protected $pbx;
	protected $contact_id;
	protected $agent_id;

	/**
	 * Wrapper function around returning this class.
	 */
	static public function Factory(eCash_PBX $pbx, $contact_id)
	{
		return new eCash_PBX_History($pbx,$contact_id);
	}
	
	public function __construct(eCash_PBX $pbx, $contact_id)
	{
		$this->pbx = $pbx;
		$this->contact_id = $contact_id;
		$this->agent_id = $this->pbx->getServer()->agent_id;
	}

	/**
	 * Attempt to see if undefined methods can be passed to the PBX object.
	 *
	 * If the call happens to be to "Dial" then add the call ot the history.
	 */
	public function __call($name, $parameters)
	{
		if(is_callable(array($this->pbx,$name))) {
			
			$ret = call_user_func_array(array($this->pbx,$name),$parameters);
			
	
			switch (strtolower($name)) {
				case "dial":
					$this->addHistory("Originate", $parameters[0], $ret);
					break;
			}

			return $ret;
			
		}
		
		throw new BadMethodCallException();
		
	}
	
	public function setContact($contact_id)
	{
		$this->contact_id = $contact_id;
	}
	
	public function setAgent($agent_id)
	{
		$this->agent_id = $agent_id;
	}
	
	public function getContact()
	{
		return $this->contact_id;
	}
	
	public function getAgent()
	{
		return $this->agent_id;
	}
	
	public function getPBX()
	{
		return $this->pbx;
	}
	
	public function addHistory($event, $phone = NULL, $result = NULL)
	{
		$ph_sql = ($phone) ? "'{$phone}'" : "(select value from application_contact where application_contact_id = {$this->contact_id} and type='phone' order by date_created desc limit 1)" ;
		$ph_sql = ($this->contact_id) ? $ph_sql : "0";		
		
		$ct_sql = ($this->contact_id) ? "(select application_id from application_contact where application_contact_id = {$this->contact_id} limit 1)" : "0";
		
		$query = " -- ecash3.5 ".__FILE__.":".__LINE__.":".__METHOD__."()
				INSERT INTO pbx_history
					(
					date_created,
					company_id,
					application_id,
					agent_id,
					application_contact_id,
					phone,
					pbx_event,
					result
					)
				VALUES
					(
					now(),
					" . $this->pbx->getCompanyId() . ",
					{$ct_sql},
					{$this->agent_id},
					{$this->contact_id},
					{$ph_sql},
					'{$event}',
					'" . serialize($result) . "'
					)
		";
		
		eCash_PBX::Log()->write($query);
		
		$this->pbx->getSQL()->Query($query);
		
		return $this->pbx->getSQL()->Insert_Id();
					
	}

	
}

?>
