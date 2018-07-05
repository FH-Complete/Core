<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

class Auth_Controller extends FHC_Controller
{
	/**
	 * Extends this controller if authentication is required
	 */
    public function __construct($requiredPermissions)
	{
        parent::__construct();

		// Loads authentication helper
		$this->load->helper('fhcauth');

		// Checks if the caller is allowed to access to this content
		$this->_isAllowed($requiredPermissions);
	}

	/**
	 * Checks if the caller is allowed to access to this content with the given permissions
	 * If it is not allowed will set the HTTP header with code 401
	 * Wrapper for permissionlib->isEntitled
	 */
	private function _isAllowed($requiredPermissions)
	{
		// Loads permission lib
		$this->load->library('PermissionLib');

		// Checks if this user is entitled to access to this content
		if (!$this->permissionlib->isEntitled($requiredPermissions, $this->router->method))
		{
			header('HTTP/1.0 401 Unauthorized'); // set the HTTP header as unauthorized

			$this->load->library('EPrintfLib'); // loads the EPrintfLib to format the output

			// Prints the main error message
			$this->eprintflib->printError('You are not allowed to access to this content');
			// Prints the called controller name
			$this->eprintflib->printInfo('Controller name: '.$this->router->class);
			// Prints the called controller method name
			$this->eprintflib->printInfo('Method name: '.$this->router->method);
			// Prints the required permissions needed to access to this method
			$this->eprintflib->printInfo('Required permissions: '.$this->_rpsToString($requiredPermissions, $this->router->method));

			exit; // immediately terminate the execution
		}
	}

	/**
	 * Converts an array of permissions to a string that contains them as a comma separated list
	 * Ex: "<permission 1>, <permission 2>, <permission 3>"
	 */
	private function _rpsToString($requiredPermissions, $method)
	{
		$strRequiredPermissions = ''; // string that contains all the required permissions needed to access to this method

		if (isset($requiredPermissions[$method])) // if the called method is present in the permissions array
		{
			// If it is NOT then convert it into an array
			$rpsMethod = $requiredPermissions[$method];
			if (!is_array($rpsMethod))
			{
				$rpsMethod = array($rpsMethod);
			}

			// Copy all the permissions into $strRequiredPermissions separated by a comma
			for ($i = 0; $i < count($rpsMethod); $i++)
			{
				$strRequiredPermissions .= $rpsMethod[$i].', ';
			}

			$strRequiredPermissions = rtrim($strRequiredPermissions, ', ');
		}

		return $strRequiredPermissions;
	}
}
