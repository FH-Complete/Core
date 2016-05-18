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

class Projekttask extends APIv1_Controller
{
	/**
	 * Projekttask API constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		// Load model ProjekttaskModel
		$this->load->model('project/projekttask_model', 'ProjekttaskModel');
		// Load set the uid of the model to let to check the permissions
		$this->ProjekttaskModel->setUID($this->_getUID());
	}

	/**
	 * @return void
	 */
	public function getProjekttask()
	{
		$projekttaskID = $this->get('projekttask_id');
		
		if(isset($projekttaskID))
		{
			$result = $this->ProjekttaskModel->load($projekttaskID);
			
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
	public function postProjekttask()
	{
		if($this->_validate($this->post()))
		{
			if(isset($this->post()['projekttask_id']))
			{
				$result = $this->ProjekttaskModel->update($this->post()['projekttask_id'], $this->post());
			}
			else
			{
				$result = $this->ProjekttaskModel->insert($this->post());
			}
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
	}
	
	private function _validate($projekttask = NULL)
	{
		return true;
	}
}