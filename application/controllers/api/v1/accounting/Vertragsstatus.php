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

class Vertragsstatus extends APIv1_Controller
{
	/**
	 * Vertragsstatus API constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		// Load model VertragsstatusModel
		$this->load->model('accounting/vertragsstatus_model', 'VertragsstatusModel');
		// Load set the uid of the model to let to check the permissions
		$this->VertragsstatusModel->setUID($this->_getUID());
	}

	/**
	 * @return void
	 */
	public function getVertragsstatus()
	{
		$vertragsstatusID = $this->get('vertragsstatus_id');
		
		if(isset($vertragsstatusID))
		{
			$result = $this->VertragsstatusModel->load($vertragsstatusID);
			
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
	public function postVertragsstatus()
	{
		if($this->_validate($this->post()))
		{
			if(isset($this->post()['vertragsstatus_id']))
			{
				$result = $this->VertragsstatusModel->update($this->post()['vertragsstatus_id'], $this->post());
			}
			else
			{
				$result = $this->VertragsstatusModel->insert($this->post());
			}
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
	}
	
	private function _validate($vertragsstatus = NULL)
	{
		return true;
	}
}