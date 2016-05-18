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

if(!defined('BASEPATH')) exit('No direct script access allowed');

class Webservicetyp extends APIv1_Controller
{
	/**
	 * Webservicetyp API constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		// Load model WebservicetypModel
		$this->load->model('system/webservicetyp_model', 'WebservicetypModel');
		// Load set the uid of the model to let to check the permissions
		$this->WebservicetypModel->setUID($this->_getUID());
	}

	/**
	 * @return void
	 */
	public function getWebservicetyp()
	{
		$webservicetypID = $this->get('webservicetyp_id');
		
		if(isset($webservicetypID))
		{
			$result = $this->WebservicetypModel->load($webservicetypID);
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
	}

	/**
	 * @return void
	 */
	public function postWebservicetyp()
	{
		if($this->_validate($this->post()))
		{
			if(isset($this->post()['webservicetyp_id']))
			{
				$result = $this->WebservicetypModel->update($this->post()['webservicetyp_id'], $this->post());
			}
			else
			{
				$result = $this->WebservicetypModel->insert($this->post());
			}
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
	}
	
	private function _validate($webservicetyp = NULL)
	{
		return true;
	}
}