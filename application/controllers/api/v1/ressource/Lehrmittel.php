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

class Lehrmittel extends APIv1_Controller
{
	/**
	 * Lehrmittel API constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		// Load model LehrmittelModel
		$this->load->model('ressource/lehrmittel_model', 'LehrmittelModel');
		// Load set the uid of the model to let to check the permissions
		$this->LehrmittelModel->setUID($this->_getUID());
	}

	/**
	 * @return void
	 */
	public function getLehrmittel()
	{
		$lehrmittelID = $this->get('lehrmittel_id');
		
		if(isset($lehrmittelID))
		{
			$result = $this->LehrmittelModel->load($lehrmittelID);
			
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
	public function postLehrmittel()
	{
		if($this->_validate($this->post()))
		{
			if(isset($this->post()['lehrmittel_id']))
			{
				$result = $this->LehrmittelModel->update($this->post()['lehrmittel_id'], $this->post());
			}
			else
			{
				$result = $this->LehrmittelModel->insert($this->post());
			}
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
	}
	
	private function _validate($lehrmittel = NULL)
	{
		return true;
	}
}