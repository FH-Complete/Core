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

if (! defined('FCPATH')) exit('No direct script access allowed');

require_once(FCPATH.'include/basis_db.class.php');
require_once(FCPATH.'include/organisationseinheit.class.php');
require_once(FCPATH.'include/studiengang.class.php');
require_once(FCPATH.'include/fachbereich.class.php');
require_once(FCPATH.'include/functions.inc.php');
require_once(FCPATH.'include/wawi_kostenstelle.class.php');
require_once(FCPATH.'include/benutzerberechtigung.class.php');

class PermissionLib
{
	// Available rights
	const SELECT_RIGHT = 's';
	const UPDATE_RIGHT = 'u';
	const INSERT_RIGHT = 'i';
	const DELETE_RIGHT = 'd';
	const REPLACE_RIGHT = 'ui';

	private $bb; // benutzerberechtigung
	private $acl; // conversion array from a source to a permission

	/**
	 *
	 */
	function __construct()
	{
		// Loads CI instance
		$this->ci =& get_instance();

		// Loads the library to manage the rights system
		//$this->ci->load->library('FHC_DB_ACL');

		// Loads the auth helper
		$this->ci->load->helper('fhcauth');

		// Loads the array of resources
		$this->acl = $this->ci->config->item('fhc_acl');

		//
		$this->bb = new benutzerberechtigung();
	}

	/**
	 * Check if the user is entitled to get access to a source with the given access type
	 *
	 * @return bool <b>true</b> if a user has the right to access to the specified
	 *				resource with a specified permission type, <b>false</b> otherwise
	 */
	public function isEntitled($sourceName, $permissionType)
	{
		// If the resource exists
		if (isset($this->acl[$sourceName]))
		{
			// Checks permission
			return $this->isBerechtigt($this->acl[$sourceName], $permissionType);
		}
		// if the resource does not exist, do not lose useful clock cycles
		else
		{
			return false;
		}
	}

	/**
	 * Get a permission by a given source
	 */
	public function getBerechtigungKurzbz($sourceName)
	{
		if (isset($this->acl[$sourceName]))
		{
			return $this->acl[$sourceName];
		}
		else
		{
			return null;
		}
	}

	/**
	 *
	 */
	private function isBerechtigt($berechtigung_kurzbz, $art = null,  $oe_kurzbz = null,  $kostenstelle_id = null)
	{
		if (!is_null($berechtigung_kurzbz))
		{
			$this->bb->getBerechtigungen(getAuthUID());
			if($this->bb->isBerechtigt($berechtigung_kurzbz, $oe_kurzbz, $art, $kostenstelle_id))
			{
				log_message('debug','Permission '.$berechtigung_kurzbz.' granted');
				return true;
			}
			else
			{
				log_message('debug','Permission '.$berechtigung_kurzbz.' failed');
				return false;
			}
		}
		else
		{
			return false;
		}
	}
}