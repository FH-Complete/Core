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

class Veranstaltungskategorie extends APIv1_Controller
{
	/**
	 * Veranstaltungskategorie API constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		// Load model VeranstaltungskategorieModel
		$this->load->model('content/veranstaltungskategorie_model', 'VeranstaltungskategorieModel');
		// Load set the uid of the model to let to check the permissions
		$this->VeranstaltungskategorieModel->setUID($this->_getUID());
	}

	/**
	 * @return void
	 */
	public function getVeranstaltungskategorie()
	{
		$veranstaltungskategorieID = $this->get('veranstaltungskategorie_id');
		
		if(isset($veranstaltungskategorieID))
		{
			$result = $this->VeranstaltungskategorieModel->load($veranstaltungskategorieID);
			
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
	public function postVeranstaltungskategorie()
	{
		if($this->_validate($this->post()))
		{
			if(isset($this->post()['veranstaltungskategorie_id']))
			{
				$result = $this->VeranstaltungskategorieModel->update($this->post()['veranstaltungskategorie_id'], $this->post());
			}
			else
			{
				$result = $this->VeranstaltungskategorieModel->insert($this->post());
			}
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
	}
	
	private function _validate($veranstaltungskategorie = NULL)
	{
		return true;
	}
}