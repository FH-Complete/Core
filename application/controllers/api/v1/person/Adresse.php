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

if (! defined('BASEPATH')) exit('No direct script access allowed');

class Adresse extends APIv1_Controller
{
    /**
     * Person API constructor.
     */
    public function __construct()
    {
		parent::__construct();
		// Load model PersonModel
		$this->load->model('person/Adresse_model', 'AdresseModel');
		
		
    }

    public function getAdresse()
    {
		$personID = $this->get("person_id");
		
		if (isset($personID))
		{
			$result = $this->AdresseModel->loadWhere(array('person_id' => $personID));
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
    }
    
    public function postAdresse()
    {
		$post = $this->_parseData($this->post());
		
		if (is_array($post))
		{
			if (isset($post['adresse_id']))
			{
				$result = $this->AdresseModel->update($post['adresse_id'], $post);
			}
			else
			{
				$result = $this->AdresseModel->insert($post);
			}
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
    }
}
