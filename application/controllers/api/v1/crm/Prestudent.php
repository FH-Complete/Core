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

class Prestudent extends APIv1_Controller
{
	/**
	 * Prestudent API constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		// Load model PrestudentModel
		$this->load->model('crm/prestudent_model', 'PrestudentModel');
	}

	/**
	 * @return void
	 */
	public function getPrestudent()
	{
		$prestudentID = $this->get('prestudent_id');
		
		if (isset($prestudentID))
		{
			$result = $this->PrestudentModel->load($prestudentID);
			
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
	public function getPrestudentByPersonID()
	{
		$person_id = $this->get('person_id');
		
		if (isset($person_id))
		{
			$result = $this->PrestudentModel->load(array('person_id' => $person_id));
			
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
	public function getLastStatus()
	{
		$prestudent_id = $this->get('prestudent_id');
		$studiensemester_kurzbz = $this->get('studiensemester_kurzbz');
		$status_kurzbz = $this->get('status_kurzbz');
		
		if (isset($prestudent_id))
		{
			$result = $this->PrestudentModel->getLastStatus($prestudent_id, $studiensemester_kurzbz, $status_kurzbz);
			
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
	public function postPrestudent()
	{
		if ($this->_validate($this->post()))
		{
			if (isset($this->post()['prestudent_id']))
			{
				$result = $this->PrestudentModel->update($this->post()['prestudent_id'], $this->post());
			}
			else
			{
				$result = $this->PrestudentModel->insert($this->post());
			}
			
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
	public function postAddReihungstest()
	{
		$ddReihungstest = $this->_parseData($this->post());
		
		if ($this->_validateAddReihungstest($ddReihungstest))
		{
			$this->load->model('crm/RtPerson_model', 'RtPersonModel');
			
			if(isset($ddReihungstest['new']) && $ddReihungstest['new'] === true)
			{
				// Remove new parameter to avoid DB insert errors
				unset($ddReihungstest['new']);
				
				$result = $this->RtPersonModel->insert($ddReihungstest);
			}
			else
			{
				$pksArray = array($ddReihungstest['person_id'], $ddReihungstest['rt_id']);
				
				$result = $this->RtPersonModel->update($pksArray, $ddReihungstest);
			}
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
	}
	
	private function _validate($prestudent = NULL)
	{
		return true;
	}
	
	private function _validateAddReihungstest($ddReihungstest = NULL)
	{
		if (!isset($ddReihungstest['person_id']) || !isset($ddReihungstest['rt_id']) ||
			!isset($ddReihungstest['ort_kurzbz']))
		{
			return false;
		}
		
		return true;
	}
}