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

class Buchungstyp extends APIv1_Controller
{
	/**
	 * Buchungstyp API constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		// Load model BuchungstypModel
		$this->load->model('crm/buchungstyp_model', 'BuchungstypModel');
		// Load set the uid of the model to let to check the permissions
		$this->BuchungstypModel->setUID($this->_getUID());
	}

	/**
	 * @return void
	 */
	public function getBuchungstyp()
	{
		$buchungstypID = $this->get('buchungstyp_id');
		
		if(isset($buchungstypID))
		{
			$result = $this->BuchungstypModel->load($buchungstypID);
			
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
	public function postBuchungstyp()
	{
		if($this->_validate($this->post()))
		{
			if(isset($this->post()['buchungstyp_id']))
			{
				$result = $this->BuchungstypModel->update($this->post()['buchungstyp_id'], $this->post());
			}
			else
			{
				$result = $this->BuchungstypModel->insert($this->post());
			}
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
	}
	
	private function _validate($buchungstyp = NULL)
	{
		return true;
	}
}