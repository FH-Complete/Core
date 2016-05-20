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

class Legesamtnote extends APIv1_Controller
{
	/**
	 * Legesamtnote API constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		// Load model LegesamtnoteModel
		$this->load->model('education/legesamtnote', 'LegesamtnoteModel');
		// Load set the uid of the model to let to check the permissions
		$this->LegesamtnoteModel->setUID($this->_getUID());
	}

	/**
	 * @return void
	 */
	public function getLegesamtnote()
	{
		$lehreinheit_id = $this->get('lehreinheit_id');
		$student_uid = $this->get('student_uid');
		
		if (isset($lehreinheit_id) && isset($student_uid))
		{
			$result = $this->LegesamtnoteModel->load(array($lehreinheit_id, $student_uid));
			
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
	public function postLegesamtnote()
	{
		if ($this->_validate($this->post()))
		{
			if (isset($this->post()['lehreinheit_id']) && isset($this->post()['student_uid']))
			{
				$result = $this->LegesamtnoteModel->update(array($this->post()['lehreinheit_id'], $this->post()['student_uid']), $this->post());
			}
			else
			{
				$result = $this->LegesamtnoteModel->insert($this->post());
			}
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
	}
	
	private function _validate($legesamtnote = NULL)
	{
		return true;
	}
}