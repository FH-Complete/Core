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

class Benutzer extends APIv1_Controller
{
	/**
	 * Benutzer API constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		// Load model BenutzerModel
		$this->load->model('person/benutzer_model', 'BenutzerModel');
		// Load set the uid of the model to let to check the permissions
		$this->BenutzerModel->setUID($this->_getUID());
	}

	/**
	 * @return void
	 */
	public function getBenutzer()
	{
		$benutzerID = $this->get('benutzer_id');
		
		if(isset($benutzerID))
		{
			$result = $this->BenutzerModel->load($benutzerID);
			
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
	public function postBenutzer()
	{
		if($this->_validate($this->post()))
		{
			if(isset($this->post()['benutzer_id']))
			{
				$result = $this->BenutzerModel->update($this->post()['benutzer_id'], $this->post());
			}
			else
			{
				$result = $this->BenutzerModel->insert($this->post());
			}
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
	}
	
	private function _validate($benutzer = NULL)
	{
		return true;
	}
}