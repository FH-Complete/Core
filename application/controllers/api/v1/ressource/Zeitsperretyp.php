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

class Zeitsperretyp extends APIv1_Controller
{
	/**
	 * Zeitsperretyp API constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		// Load model ZeitsperretypModel
		$this->load->model('ressource/zeitsperretyp_model', 'ZeitsperretypModel');
		// Load set the uid of the model to let to check the permissions
		$this->ZeitsperretypModel->setUID($this->_getUID());
	}

	/**
	 * @return void
	 */
	public function getZeitsperretyp()
	{
		$zeitsperretypID = $this->get('zeitsperretyp_id');
		
		if(isset($zeitsperretypID))
		{
			$result = $this->ZeitsperretypModel->load($zeitsperretypID);
			
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
	public function postZeitsperretyp()
	{
		if($this->_validate($this->post()))
		{
			if(isset($this->post()['zeitsperretyp_id']))
			{
				$result = $this->ZeitsperretypModel->update($this->post()['zeitsperretyp_id'], $this->post());
			}
			else
			{
				$result = $this->ZeitsperretypModel->insert($this->post());
			}
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
	}
	
	private function _validate($zeitsperretyp = NULL)
	{
		return true;
	}
}