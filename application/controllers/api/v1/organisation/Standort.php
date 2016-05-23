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

class Standort extends APIv1_Controller
{
	/**
	 * Standort API constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		// Load model StandortModel
		$this->load->model('organisation/standort_model', 'StandortModel');
		// Load set the uid of the model to let to check the permissions
		$this->StandortModel->setUID($this->_getUID());
	}

	/**
	 * @return void
	 */
	public function getStandort()
	{
		$standortID = $this->get('standort_id');
		
		if (isset($standortID))
		{
			$result = $this->StandortModel->load($standortID);
			
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
	public function postStandort()
	{
		if ($this->_validate($this->post()))
		{
			if (isset($this->post()['standort_id']))
			{
				$result = $this->StandortModel->update($this->post()['standort_id'], $this->post());
			}
			else
			{
				$result = $this->StandortModel->insert($this->post());
			}
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
	}
	
	private function _validate($standort = NULL)
	{
		return true;
	}
}