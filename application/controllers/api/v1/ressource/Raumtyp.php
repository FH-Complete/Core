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

class Raumtyp extends APIv1_Controller
{
	/**
	 * Raumtyp API constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		// Load model RaumtypModel
		$this->load->model('ressource/raumtyp_model', 'RaumtypModel');
		// Load set the uid of the model to let to check the permissions
		$this->RaumtypModel->setUID($this->_getUID());
	}

	/**
	 * @return void
	 */
	public function getRaumtyp()
	{
		$raumtypID = $this->get('raumtyp_id');
		
		if(isset($raumtypID))
		{
			$result = $this->RaumtypModel->load($raumtypID);
			
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
	public function postRaumtyp()
	{
		if($this->_validate($this->post()))
		{
			if(isset($this->post()['raumtyp_id']))
			{
				$result = $this->RaumtypModel->update($this->post()['raumtyp_id'], $this->post());
			}
			else
			{
				$result = $this->RaumtypModel->insert($this->post());
			}
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
	}
	
	private function _validate($raumtyp = NULL)
	{
		return true;
	}
}