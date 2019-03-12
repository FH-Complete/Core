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

	// Conversion from HTTP method to access type method
	const READ_HTTP_METHOD = 'GET';
	const WRITE_HTTP_METHOD = 'POST';

	private $_ci; // CI instance
	private static $bb; // benutzerberechtigung

	/**
	 * PermissionLib's constructor
	 * Here is initialized the static property bb with all the rights of the user (API caller)
	 */
	public function __construct()
	{
		// Loads CI instance
		$this->_ci =& get_instance();

		// If it's NOT called from command line
		if (!is_cli())
		{
			// API Caller rights initialization
			self::$bb = new benutzerberechtigung();
			self::$bb->getBerechtigungen(($this->_ci->authlib->getAuthObj())->{AuthLib::AO_USERNAME});
		}
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

	/**
	 * Checks if the caller is allowed to access to this content with the given permissions
	 * - if it's called from command line than it's trusted
	 * - checks if the parameter $requiredPermissions is set, is an array and contains at least one element
	 * - checks if the given $requiredPermissions parameter contains the called method of the controller
	 * - checks if the HTTP method used to call is GET or POST
	 * - convert the required permissions to an array if needed
	 * - loops through the required permissions
	 * - checks if the permission is well formatted
	 * - retrives permission and required access type from the $requiredPermissions array
	 * - checks if the required access type is compliant with the HTTP method (GET => r, POST => w)
	 * - if the user has one of the permissions than exit the loop
	 * - checks if the user has the same required permissiond with the same required access type
	 * - returns true if all the checks are ok, otherwise false
	 *
	 * NOTE: the displayed error messages are used to warn the developer about any issues that could occur,
	 * 		they are not intended for the final user!
	 */
	public function isEntitled($requiredPermissions, $calledMethod)
	{
		$checkPermissions = false;
		$requestMethod = $_SERVER['REQUEST_METHOD'];

		// If it's called from command line than it's trusted
		if (is_cli()) return true;

		// Checks if the parameter $requiredPermissions is set, is an array and contains at least one element
		if (isset($requiredPermissions) && !isEmptyArray($requiredPermissions))
		{
			// Checks if the given $requiredPermissions parameter contains the called method of the controller
			if (isset($requiredPermissions[$calledMethod]))
			{
				// Checks if the HTTP method used to call is GET or POST
				if ($requestMethod == self::READ_HTTP_METHOD || $requestMethod == self::WRITE_HTTP_METHOD)
				{
					$permissions = $requiredPermissions[$calledMethod];
					// Convert the required permissions to an array if needed
					if (!is_array($permissions))
					{
						$permissions = array($requiredPermissions[$calledMethod]);
					}

					if (!isEmptyArray($permissions))
					{
						// Loops through the required permissions
						for ($pCounter = 0; $pCounter < count($permissions); $pCounter++)
						{
							// Checks if the permission is well formatted
							if (strpos($permissions[$pCounter], PermissionLib::PERMISSION_SEPARATOR) !== false)
							{
								// Retrives permission and required access type from the $requiredPermissions array
								list($permission, $requiredAccessType) = explode(PermissionLib::PERMISSION_SEPARATOR, $permissions[$pCounter]);

								$accessType = '';

								// Checks if the required access type is compliant with the HTTP method (GET => r, POST => w)
								if (strpos($requiredAccessType, PermissionLib::READ_RIGHT) !== false)
								{
									$accessType = PermissionLib::SELECT_RIGHT; // S
								}
								if (strpos($requiredAccessType, PermissionLib::WRITE_RIGHT) !== false)
								{
									$accessType .= PermissionLib::REPLACE_RIGHT.PermissionLib::DELETE_RIGHT; // UID
								}

								if (!isEmptyString($accessType)) // if compliant
								{
									// Checks if the user has the same required permissiond with the same required access type
									$checkPermissions = $this->isBerechtigt($permission, $accessType);

									// If the user has one of the permissionsm than exit the loop
									if ($checkPermissions === true) break;
								}
							}
							else
							{
								show_error('The given permission does not use the correct format');
							}
						}
					}
					else
					{
						show_error('No permissions are set for this method, an empty array is given');
					}
				}
				else
				{
					show_error('You are trying to access to this content with a not valid HTTP method');
				}
			}
			else
			{
				show_error('The given permission array does not contain the called method or is not correctly set');
			}
		}
		else
		{
			show_error('You must give the permissions array as parameter to the constructor of the controller');
		}

		return $checkPermissions;
	}

	/**
	 * Checks if at least one of the permissions given as parameter (requiredPermissions) belongs to the authenticated user
	 * It checks the given permissions against a given method (controller method name) and a given permission type (R and/or W)
	 * If the $permissionType is not given then it is assumed that is already present inside requiredPermissions
	 * Wrapper method for isEntitled, it uses method to build an associative array of permissions having as key the method itself
	 */
	public function hasAtLeastOne($requiredPermissions, $method, $permissionType = null)
	{
		$isAllowed = false; // by default is NOT allowed

		// If the parameter requiredPermissions is NOT given, then no one is allow to use this FilterWidget
		if ($requiredPermissions != null)
		{
			// If requiredPermissions is NOT an array then converts it to an array
			if (!is_array($requiredPermissions))
			{
				$requiredPermissions = array($requiredPermissions);
			}

			// Checks if at least one of the permissions given as parameter belongs to the authenticated user...
			for ($p = 0; $p < count($requiredPermissions); $p++)
			{
				$pt = ''; // by default the permission is alredy present in $requiredPermissions[$p]
				if ($permissionType != null) // if is it given as parameter
				{
					$pt = self::PERMISSION_SEPARATOR.$permissionType; // then build the permission type string
				}

				$isAllowed = $this->_ci->permissionlib->isEntitled(
					array(
						$method => $requiredPermissions[$p].$pt
					),
					$method
				);

				if ($isAllowed === true) break; // ...if confirmed then is allowed to use this FilterWidget
			}
		}

		return $isAllowed;
	}
}
