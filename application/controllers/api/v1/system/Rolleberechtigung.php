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

class Rolleberechtigung extends APIv1_Controller
{
	/**
	 * Rolleberechtigung API constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		// Load model RolleberechtigungModel
		$this->load->model('system/rolleberechtigung_model', 'RolleberechtigungModel');
		// Load set the uid of the model to let to check the permissions
		$this->RolleberechtigungModel->setUID($this->_getUID());
	}

	/**
	 * @return void
	 */
	public function getRolleberechtigung()
	{
		$rolleberechtigungID = $this->get('rolleberechtigung_id');
		
		if(isset($rolleberechtigungID))
		{
			$result = $this->RolleberechtigungModel->load($rolleberechtigungID);
			
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
	public function postRolleberechtigung()
	{
		if($this->_validate($this->post()))
		{
			if(isset($this->post()['rolleberechtigung_id']))
			{
				$result = $this->RolleberechtigungModel->update($this->post()['rolleberechtigung_id'], $this->post());
			}
			else
			{
				$result = $this->RolleberechtigungModel->insert($this->post());
			}
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
	}
	
	private function _validate($rolleberechtigung = NULL)
	{
		return true;
	}
}