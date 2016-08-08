<?php
/**
 * FH-Complete
 *
 * @package		FHC-API
 * @author		FHC-Team
 * @copyright	Copyright (c) 2016, fhcomplete.org
 * @license		GPLv3
 * @link		http://fhcomplete.org
 * @since		Version 1.0
 * @filesource
 */
// ------------------------------------------------------------------------

if (!defined("BASEPATH")) exit("No direct script access allowed");

class PCRM extends APIv1_Controller
{
	/**
	 * API constructor
	 */
	public function __construct()
	{
		parent::__construct();
		
		// Loads the PCRMLib
		$this->load->library("PCRMLib");
	}

	/**
	 * Manages a HTTP get call
	 */
	public function getCall()
	{
		// Start me up!
		$result = $this->pcrmlib->start($this->get(), PermissionLib::SELECT_RIGHT);

		// Print the result
		$this->response($result, REST_Controller::HTTP_OK);
	}

	/**
	 * @return void
	 */
	public function postCall()
	{
		// Start me up!
		$result = $this->pcrmlib->start($this->post(), PermissionLib::UPDATE_RIGHT);

		// Print the result
		$this->response($result, REST_Controller::HTTP_OK);
	}
	
	/**
	 * @return void
	 */
	public function putCall()
	{
		// Start me up!
		$result = $this->pcrmlib->start($this->put(), PermissionLib::INSERT_RIGHT);

		// Print the result
		$this->response($result, REST_Controller::HTTP_OK);
	}
	
	/**
	 * @return void
	 */
	public function deleteCall()
	{
		// Start me up!
		$result = $this->pcrmlib->start($this->delete(), PermissionLib::DELETE_RIGHT);

		// Print the result
		$this->response($result, REST_Controller::HTTP_OK);
	}
}