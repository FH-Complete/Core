<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

class UDF extends VileSci_Controller 
{
	public function __construct()
    {
        parent::__construct();
        
        // Load session library
        $this->load->library('session');
        
        // Loads the widget library
		$this->load->library('WidgetLib');
		
		// 
		$this->load->model('person/Person_model', 'PersonModel');
		$this->load->model('crm/Prestudent_model', 'PrestudentModel');
    }
	
	/**
	 * 
	 */
	public function index()
	{
		$person_id = $this->input->get('person_id');
		if (isset($this->session->person_id))
		{
			if (!isset($person_id))
			{
				$person_id = $this->session->person_id;
			}
			unset($this->session->person_id);
		}
		
		$prestudent_id = $this->input->get('prestudent_id');
		if (isset($this->session->prestudent_id))
		{
			if (!isset($prestudent_id))
			{
				$prestudent_id = $this->session->prestudent_id;
			}
			unset($this->session->prestudent_id);
		}
		
		$result = null;
		if (isset($this->session->result))
		{
			$result = clone $this->session->result;
			$this->session->set_userdata('result', null);
		}
		
		$person = $this->PersonModel->load($person_id);
		$prestudent = $this->PrestudentModel->load($prestudent_id);
		
		$personUdfs = $this->PersonModel->getUDFs();
		$prestudentUdfs = $this->PrestudentModel->getUDFs();
		
		$personUdfs['person_id'] = $person_id;
		$prestudentUdfs['prestudent_id'] = $prestudent_id;
		
		$data = array(
			'personUdfs' => $personUdfs,
			'prestudentUdfs' => $prestudentUdfs,
			'result' => $result
		);
		
		$this->load->view('system/udf', $data);
	}
	
	/**
	 * 
	 */
	public function saveUDF()
	{
		$udfs = $this->input->post();
		$validation = $this->_validate($udfs);
		
		$userdata = array(
			'person_id' => $udfs['person_id'],
			'prestudent_id' => $udfs['prestudent_id']
		);
		
		if (isSuccess($validation))
		{
			// Load model UDF_model
			$this->load->model('system/UDF_model', 'UDFModel');
			
			$result = $this->UDFModel->saveUDFs($udfs);
			
			$userdata['result'] = $result;
		}
		else
		{
			$userdata['result'] = $validation;
		}
		
		$this->session->set_userdata($userdata);
		redirect('system/UDF');
	}
	
	/**
	 * 
	 */
	private function _validate($udfs)
	{
		$validation = error('person_id or prestudent_id is missing');
		
		if((isset($udfs['person_id']) && !(is_null($udfs['person_id'])) && ($udfs['person_id'] != ''))
			|| (isset($udfs['prestudent_id']) && !(is_null($udfs['prestudent_id'])) && ($udfs['prestudent_id'] != '')))
		{
			$validation = success(true);
		}
		
		return $validation;
	}
}