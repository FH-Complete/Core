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

class Firmatag extends APIv1_Controller
{
	/**
	 * Firmatag API constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		// Load model FirmatagModel
		$this->load->model('ressource/firmatag_model', 'FirmatagModel');
		// Load set the uid of the model to let to check the permissions
		$this->FirmatagModel->setUID($this->_getUID());
	}

	/**
	 * @return void
	 */
	public function getFirmatag()
	{
		$firmatagID = $this->get('firmatag_id');
		
		if(isset($firmatagID))
		{
			$result = $this->FirmatagModel->load($firmatagID);
			
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
	public function postFirmatag()
	{
		if($this->_validate($this->post()))
		{
			if(isset($this->post()['firmatag_id']))
			{
				$result = $this->FirmatagModel->update($this->post()['firmatag_id'], $this->post());
			}
			else
			{
				$result = $this->FirmatagModel->insert($this->post());
			}
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
	}
	
	private function _validate($firmatag = NULL)
	{
		return true;
	}
}