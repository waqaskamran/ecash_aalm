<?php

/**
 * The Commercial Vendor API Authenticator.
 * 
 * This class provides an adapter around the Commercial ECash_ACL and the ECash_Security
 * classes.
 *
 * @package VendorAPI
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECash_VendorAPI_Authenticator implements VendorAPI_IAuthenticator 
{
	const SYSTEM_NAME = 'eCash_RPC';
	const SECTION_NAME = 'vendor_api';
	
	/**
	 * @var int
	 */
	protected $company_id;
	
	/**
	 * @var ECash_Security
	 */
	protected $security;
	
	/**
	 * @var ECash_ACL
	 */
	protected $acl;
	
	/**
	 * Creates a new authenticator
	 *
	 * @param int $company_id
	 * @param ECash_Security $security
	 * @param ECash_ACL $acl
	 */
	public function __construct($company_id, ECash_Security $security, ECash_ACL $acl)
	{
		$this->company_id = $company_id;
		$this->security = $security;
		$this->acl = $acl;
	}
	
	/**
	 * Authenticates the user to the server. Returns true and success and 
	 * false on failure.
	 *
	 * @param string $user
	 * @param string $pass
	 * @param string $section
	 * @return bool
	 */
	public function authenticate($user, $pass, $section = NULL)
	{
		if (empty($section))
		{
			$section = self::SECTION_NAME;
		}
		if ($this->security->loginUser(self::SYSTEM_NAME, $user, $pass))
		{
			$this->acl->fetchUserACL($this->security->getAgent()->getAgentId(), $this->company_id);
			
			return $this->acl->Acl_Access_Ok($section, $this->company_id);
		}
		
		return FALSE;
	}

	public function getAgentId()
	{
		return $this->security->getAgent()->getAgentId();
	}
}

?>
