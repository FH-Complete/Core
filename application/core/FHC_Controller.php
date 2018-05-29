<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

class FHC_Controller extends CI_Controller
{
	const FHC_CONTROLLER_ID = 'fhc_controller_id'; // name of the parameter used to identify uniquely a call to a controller

	private $_controllerId; // contains the unique identifier of a call to a controller

	/**
	 * Standard construct for all the controllers, loads the authentication system
	 */
    public function __construct()
	{
        parent::__construct();

		$this->_controllerId = null; // set _controllerId as null by default

		$this->load->helper('fhcauth');
	}

	//------------------------------------------------------------------------------------------------------------------
	// Public methods

	/**
	 * Wrapper to load phrases using the PhrasesLib
	 * NOTE: The library is loaded with the alias 'p', so must me used with this alias in the rest of the code.
	 *		EX: $this->p->t(<category>, <phrase name>)
	 */
	public function loadPhrases($categories, $language = null)
	{
		$this->load->library('PhrasesLib', array($categories, $language), 'p');
	}

	//------------------------------------------------------------------------------------------------------------------
	// Protected methods

	/**
	 * Sets the unique id for the called controller
	 * NOTE: it is only working with HTTP GET request, not neeaded with POST
	 *		because the first call to the controller is via HTTP GET,
	 *		therefore a fhc_controller_id is already generated
	 */
	protected function setControllerId()
	{
		if ($_SERVER['REQUEST_METHOD'] === 'GET')
		{
			$this->_controllerId = $this->input->get(self::FHC_CONTROLLER_ID);

			if (!isset($this->_controllerId) || empty($this->_controllerId))
			{
				$this->_controllerId = uniqid(); // generate a unique id
				// Redirect to the same URL, but giving FHC_CONTROLLER_ID as HTTP GET parameter
				header(
					sprintf(
						'Location: %s%s%s=%s',
						$_SERVER['REQUEST_URI'],
						strpos($_SERVER['REQUEST_URI'], '?') === false ? '?' : '&', // place the corret character to divide parameters
						self::FHC_CONTROLLER_ID,
						$this->_controllerId
					)
				);
				exit; // terminate immediately the execution of this controller
			}
		}
	}

	/**
	 * Return the value of the property _controllerId
	 */
	protected function getControllerId()
	{
		return $this->_controllerId;
	}

	/**
	 * Utility method to output a success using JSON as content type
	 * Wraps the private method _outputJson
	 */
	protected function outputJsonSuccess($mixed)
	{
		$this->_outputJson(success($mixed));
	}

	/**
	 * Utility method to output an error using JSON as content type
	 * Wraps the private method _outputJson
	 */
	protected function outputJsonError($mixed)
	{
		$this->_outputJson(error($mixed));
	}

	//------------------------------------------------------------------------------------------------------------------
	// Private methods

	/**
	 * Utility method to output using JSON as content type
	 */
	private function _outputJson($mixed)
	{
		$this->output->set_content_type('application/json')->set_output(json_encode($mixed));
	}
}
