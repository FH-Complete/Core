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

class Variable extends APIv1_Controller
{
	/**
	 * Variable API constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		// Load model VariableModel
		$this->load->model('system/variable_model', 'VariableModel');
		// Load set the uid of the model to let to check the permissions
		$this->VariableModel->setUID($this->_getUID());
	}

	/**
	 * @return void
	 */
	public function getVariable()
	{
		$uid = $this->get('uid');
		$name = $this->get('name');
		
		if(isset($uid) && isset($name))
		{
			$result = $this->VariableModel->load(array($uid, $name));
			
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
	public function postVariable()
	{
		if($this->_validate($this->post()))
		{
			if(isset($this->post()['variable_id']))
			{
				$result = $this->VariableModel->update($this->post()['variable_id'], $this->post());
			}
			else
			{
				$result = $this->VariableModel->insert($this->post());
			}
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
	}
	
	private function _validate($variable = NULL)
	{
		return true;
	}
}