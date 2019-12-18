<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

class FASMessages extends Auth_Controller
{
	/**
	 *
	 */
	public function __construct()
	{
		parent::__construct(
			array(
				'write' => 'basis/message:rw',
				'writeReply' => 'basis/message:rw'
			)
		);

		// Loads the message library
		$this->load->library('MessageLib');

		// Loads the widget library
		$this->load->library('WidgetLib');

		$this->loadPhrases(
			array(
				'global',
				'ui'
			)
		);
	}

	// -----------------------------------------------------------------------------------------------------------------
	// Public methods

	/**
	 * Write a new message
	 */
	public function write($sender_id)
 	{
		$prestudent_id = $this->input->post('prestudent_id'); // recipients prestudend_id(s)

		if (!is_numeric($sender_id))
 		{
 			show_error('The current logged user person_id is not defined');
 		}

		$msgVarsData = $this->_getMsgVarsData($prestudent_id);

		// Retrieves message vars for a person from view view vw_msg_vars_person
		$variablesArray = $this->_getMessageVarsPerson();

 		// Organisation units used to get the templates
		$oe_kurzbz = $this->_getOeKurzbz($sender_id);

 		// Admin or commoner?
 		$isAdmin = $this->_getIsAdmin($sender_id);

 		$data = array(
 			'recipients' => getData($msgVarsData),
 			'variables' => $variablesArray,
 			'oe_kurzbz' => $oe_kurzbz, // used to get the templates
 			'isAdmin' => $isAdmin
 		);

 		$this->load->view('system/messages/messageWrite', $data);
 	}

	/**
	 * Write a reply
	 */
	public function writeReply($sender_id, $msg_id, $receiver_id)
 	{
		$prestudent_id = $this->input->post('prestudent_id'); // recipients prestudend_id(s)

		if (!is_numeric($sender_id))
 		{
 			show_error('The current logged user person_id is not defined');
 		}

		if (!is_numeric($msg_id))
 		{
 			show_error('The msg_id must be a number');
 		}

		if (!is_numeric($receiver_id))
 		{
 			show_error('The receiver_id must be a number');
 		}

		$msg = $this->_getMessage($msg_id, $receiver_id);

		$msgVarsData = $this->_getMsgVarsData($prestudent_id);

		// Retrieves message vars for a person from view view vw_msg_vars_person
		$variablesArray = $this->_getMessageVarsPerson();

 		// Organisation units used to get the templates
		$oe_kurzbz = $this->_getOeKurzbz($sender_id);

 		// Admin or commoner?
 		$isAdmin = $this->_getIsAdmin($sender_id);

 		$data = array(
 			'recipients' => getData($msgVarsData),
 			'message' => $msg,
 			'variables' => $variablesArray,
 			'oe_kurzbz' => $oe_kurzbz, // used to get the templates
 			'isAdmin' => $isAdmin
 		);

 		$this->load->view('system/messages/messageWrite', $data);
 	}

	// -----------------------------------------------------------------------------------------------------------------
	// Private methods

	/**
	 *
	 */
	private function _getMessage($msg_id, $receiver_id)
	{
		$msg = $this->messagelib->getMessage($msg_id, $receiver_id);
		if (isError($msg))
		{
			show_error(getError($msg));
		}
		elseif (!hasData($msg))
		{
			show_error('The selected message does not exist');
		}
		else
		{
			$msg = getData($msg)[0];
		}

		return $msg;
	}

	/**
	 * Retrieves message vars from view vw_msg_vars
	 */
	private function _getMsgVarsData($prestudent_id)
	{
		$this->load->model('system/Message_model', 'MessageModel');
		$msgVarsData = $this->MessageModel->getMsgVarsDataByPrestudentId($prestudent_id);

		if (isError($msgVarsData))
		{
			show_error(getError($msgVarsData));
		}

		return $msgVarsData;
	}

	/**
	 * Wrapper method to call messagelib->getMessageVarsPerson
	 */
	private function _getMessageVarsPerson()
	{
		$variables = $this->messagelib->getMessageVarsPerson();

		if (isError($variables)) show_error(getError($variables));

		return getData($variables);
	}

	/**
	 * Wrapper method to call messagelib->getOeKurzbz
	 */
	private function _getOeKurzbz($sender_id)
	{
		$oe_kurzbz = $this->messagelib->getOeKurzbz($sender_id);

		if (isError($oe_kurzbz)) show_error(getError($oe_kurzbz));

		return getData($oe_kurzbz);
	}

	/**
	 * Wrapper method to call messagelib->getIsAdmin
	 */
	private function _getIsAdmin($sender_id)
	{
		$isAdmin = $this->messagelib->getIsAdmin($sender_id);

		if (isError($isAdmin)) show_error(getError($isAdmin));

		return getData($isAdmin);
	}
}
