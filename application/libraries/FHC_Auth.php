<?php
/**
 * FH-Complete
 *
 * @package	FHC-Helper
 * @author	FHC-Team
 * @copyright	Copyright (c) 2016 fhcomplete.org
 * @license	GPLv3
 * @link	https://fhcomplete.org
 * @since	Version 1.0.0
 * @filesource
 */
if (! defined('BASEPATH')) exit('No direct script access allowed');
require_once FCPATH.'include/authentication.class.php';
require_once FCPATH.'include/AddonAuthentication.php';

/**
 * FHC-Auth Helpers
 *
 * @package		FH-Complete
 * @subpackage	Helpers
 * @category	Helpers
 * @author		FHC-Team
 * @link		http://fhcomplete.org/user_guide/helpers/fhcauth_helper.html
 */

// ------------------------------------------------------------------------

class FHC_Auth extends authentication
{
	/**
	 * Construct
	 */
	public function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * Auth Username, Password over FH-Complete
	 *
	 * @param	string	$username
	 * @param	string	$password
	 * @return	bool	
	 */
	public function basicAuthentication($username, $password)
	{
		if ($this->checkpassword($username, $password))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * 
	 * TO BE UPDATED
	 * 
	 * Get the md5 hashed password by the addon username
	 *
	 * @param	string	$username addon username
	 * @return	string	md5 hashed string
	 */
	public function digestAuthentication($username)
	{
		$aam = new AddonAuthentication();
		
		return md5($aam->getPasswordByUsername($username));
	}
}