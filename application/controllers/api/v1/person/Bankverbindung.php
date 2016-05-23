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

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Bankverbindung extends APIv1_Controller
{
	/**
	 * Bankverbindung API constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		// Load model BankverbindungModel
		$this->load->model('person/bankverbindung_model', 'BankverbindungModel');
		// Load set the uid of the model to let to check the permissions
		$this->BankverbindungModel->setUID($this->_getUID());
	}

	/**
	 * @return void
	 */
	public function getBankverbindung()
	{
		$bankverbindungID = $this->get('bankverbindung_id');
		
		if (isset($bankverbindungID))
		{
			$result = $this->BankverbindungModel->load($bankverbindungID);
			
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
	public function postBankverbindung()
	{
		if ($this->_validate($this->post()))
		{
			if (isset($this->post()['bankverbindung_id']))
			{
				$result = $this->BankverbindungModel->update($this->post()['bankverbindung_id'], $this->post());
			}
			else
			{
				$result = $this->BankverbindungModel->insert($this->post());
			}
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
	}
	
	private function _validate($bankverbindung = NULL)
	{
		return true;
	}
}