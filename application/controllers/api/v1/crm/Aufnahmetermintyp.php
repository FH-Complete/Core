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

class Aufnahmetermintyp extends APIv1_Controller
{
	/**
	 * Aufnahmetermintyp API constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		// Load model AufnahmetermintypModel
		$this->load->model('crm/aufnahmetermintyp_model', 'AufnahmetermintypModel');
		// Load set the uid of the model to let to check the permissions
		$this->AufnahmetermintypModel->setUID($this->_getUID());
	}

	/**
	 * @return void
	 */
	public function getAufnahmetermintyp()
	{
		$aufnahmetermintypID = $this->get('aufnahmetermintyp_id');
		
		if(isset($aufnahmetermintypID))
		{
			$result = $this->AufnahmetermintypModel->load($aufnahmetermintypID);
			
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
	public function postAufnahmetermintyp()
	{
		if($this->_validate($this->post()))
		{
			if(isset($this->post()['aufnahmetermintyp_id']))
			{
				$result = $this->AufnahmetermintypModel->update($this->post()['aufnahmetermintyp_id'], $this->post());
			}
			else
			{
				$result = $this->AufnahmetermintypModel->insert($this->post());
			}
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
	}
	
	private function _validate($aufnahmetermintyp = NULL)
	{
		return true;
	}
}