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

require_once(FHCPATH.'include/basis_db.class.php');
require_once(FHCPATH.'include/organisationseinheit.class.php');
require_once(FHCPATH.'include/studiengang.class.php');
require_once(FHCPATH.'include/fachbereich.class.php');
require_once(FHCPATH.'include/functions.inc.php');
require_once(FHCPATH.'include/wawi_kostenstelle.class.php');
require_once(FHCPATH.'include/benutzerberechtigung.class.php');

class PermissionLib
{
	// Available rights in the DB
	const SELECT_RIGHT = 's';
	const UPDATE_RIGHT = 'u';
	const INSERT_RIGHT = 'i';
	const DELETE_RIGHT = 'd';
	const REPLACE_RIGHT = 'ui';

	// Available rights to access a controller
	const READ_RIGHT = 'r';
	const WRITE_RIGHT = 'w';
	const READ_WRITE_RIGHT = 'rw';

	const PERMISSION_SEPARATOR = ':'; // used as separator berween permission and right

	private $acl; // conversion array from a source to a permission
	private static $bb; // benutzerberechtigung

	/**
	 * PermissionLib's constructor
	 * Here is initialized the static property bb with all the rights of the user (API caller)
	 */
	public function __construct()
	{
		// Loads CI instance
		$this->ci =& get_instance();

		// Loads the auth helper
		$this->ci->load->helper('fhcauth');

		// Loads the array of resources
		$this->acl = $this->ci->config->item('fhc_acl');

		if (!is_cli())
		{
			// API Caller rights initialization
			self::$bb = new benutzerberechtigung();
			self::$bb->getBerechtigungen(getAuthUID());
		}
	}

	/**
	 * Check if the user is entitled to get access to a source with the given access type
	 *
	 * @return bool <b>true</b> if a user has the right to access to the specified
	 *				resource with a specified permission type, <b>false</b> otherwise
	 */
	public function isEntitled($sourceName, $permissionType)
	{
		$isEntitled = false;

		// If it's called from command line than it's trusted
		if (!is_cli())
		{
			// If the resource exists
			if (isset($this->acl[$sourceName]))
			{
				// Checks permission
				$isEntitled = $this->isBerechtigt($this->acl[$sourceName], $permissionType);
			}
		}
		else
		{
			$isEntitled	= true;
		}

		return $isEntitled;
	}

	/**
	 * Get a permission by a given source
	 */
	public function getBerechtigungKurzbz($sourceName)
	{
		$returnValue = null;

		if (isset($this->acl[$sourceName]))
		{
			$returnValue = $this->acl[$sourceName];
		}

		return $returnValue;
	}

	/**
	 * Checks user's (API caller) rights
	 */
	public function isBerechtigt($berechtigung_kurzbz, $art = null, $oe_kurzbz = null, $kostenstelle_id = null)
	{
		$isBerechtigt = false;

		if (!is_null($berechtigung_kurzbz))
		{
			if (self::$bb->isBerechtigt($berechtigung_kurzbz, $oe_kurzbz, $art, $kostenstelle_id))
			{
				$isBerechtigt = true;
			}
		}

		return $isBerechtigt;
	}
}
