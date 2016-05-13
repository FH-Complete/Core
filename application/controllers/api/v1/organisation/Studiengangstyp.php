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

class Studiengangstyp extends APIv1_Controller
{
	/**
	 * Studiengangstyp API constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		// Load model StudiengangstypModel
		$this->load->model('organisation/studiengangstyp_model', 'StudiengangstypModel');
		// Load set the uid of the model to let to check the permissions
		$this->StudiengangstypModel->setUID($this->_getUID());
	}

	/**
	 * @return void
	 */
	public function getStudiengangstyp()
	{
		$studiengangstypID = $this->get('studiengangstyp_id');
		
		if(isset($studiengangstypID))
		{
			$result = $this->StudiengangstypModel->load($studiengangstypID);
			
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
	public function postStudiengangstyp()
	{
		if($this->_validate($this->post()))
		{
			if(isset($this->post()['studiengangstyp_id']))
			{
				$result = $this->StudiengangstypModel->update($this->post()['studiengangstyp_id'], $this->post());
			}
			else
			{
				$result = $this->StudiengangstypModel->insert($this->post());
			}
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
	}
	
	private function _validate($studiengangstyp = NULL)
	{
		return true;
	}
}