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

class Fotostatus extends APIv1_Controller
{
	/**
	 * Fotostatus API constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		// Load model FotostatusModel
		$this->load->model('person/fotostatus_model', 'FotostatusModel');
		// Load set the uid of the model to let to check the permissions
		$this->FotostatusModel->setUID($this->_getUID());
	}

	/**
	 * @return void
	 */
	public function getFotostatus()
	{
		$fotostatusID = $this->get('fotostatus_id');
		
		if(isset($fotostatusID))
		{
			$result = $this->FotostatusModel->load($fotostatusID);
			
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
	public function postFotostatus()
	{
		if($this->_validate($this->post()))
		{
			if(isset($this->post()['fotostatus_id']))
			{
				$result = $this->FotostatusModel->update($this->post()['fotostatus_id'], $this->post());
			}
			else
			{
				$result = $this->FotostatusModel->insert($this->post());
			}
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
	}
	
	private function _validate($fotostatus = NULL)
	{
		return true;
	}
}