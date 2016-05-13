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

class Zgvdoktor extends APIv1_Controller
{
	/**
	 * Zgvdoktor API constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		// Load model ZgvdoktorModel
		$this->load->model('codex/zgvdoktor_model', 'ZgvdoktorModel');
		// Load set the uid of the model to let to check the permissions
		$this->ZgvdoktorModel->setUID($this->_getUID());
	}

	/**
	 * @return void
	 */
	public function getZgvdoktor()
	{
		$zgvdoktorID = $this->get('zgvdoktor_id');
		
		if(isset($zgvdoktorID))
		{
			$result = $this->ZgvdoktorModel->load($zgvdoktorID);
			
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
	public function postZgvdoktor()
	{
		if($this->_validate($this->post()))
		{
			if(isset($this->post()['zgvdoktor_id']))
			{
				$result = $this->ZgvdoktorModel->update($this->post()['zgvdoktor_id'], $this->post());
			}
			else
			{
				$result = $this->ZgvdoktorModel->insert($this->post());
			}
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
	}
	
	private function _validate($zgvdoktor = NULL)
	{
		return true;
	}
}