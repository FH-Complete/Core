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

class Message extends APIv1_Controller
{
	/**
	 * Message API constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		// Load library MessageLib
		$this->load->library('MessageLib');
	}

	/**
	 * @return void
	 */
	public function getMessagesByPersonID()
	{
		$person_id = $this->get('person_id');
		$all = $this->get('all');
		
		if (isset($person_id))
		{
			$result = $this->messagelib->getMessagesByPerson($person_id, $all);
			
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
	public function getMessagesByUID()
	{
		$uid = $this->get('uid');
		$all = $this->get('all');
		
		if (isset($uid))
		{
			$result = $this->messagelib->getMessagesByUID($uid, $all);
			
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
	public function postMessage()
	{
		$validation = $this->_validatePostMessage($this->post());
		
		if (is_object($validation) && $validation->error == EXIT_SUCCESS)
		{
			$this->messagelib->addRecipient($this->post()['person_id']);
			$result = $this->messagelib->sendMessage(
				$this->post()['person_id'],
				$this->post()['subject'],
				$this->post()['body'],
				PRIORITY_NORMAL,
				NULL,
				$this->post()['oe_kurzbz']
			);
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response($validation, REST_Controller::HTTP_OK);
		}
	}
	
	/**
	 * @return void
	 */
	public function postMessageVorlage()
	{
		$validation = $this->_validatePostMessageVorlage($this->post());
		
		if (is_object($validation) && $validation->error == EXIT_SUCCESS)
		{
			$result = $this->messagelib->sendMessageVorlage(
				$this->post()['sender_id'],
				$this->post()['receiver_id'],
				$this->post()['vorlage_kurzbz'],
				$this->post()['oe_kurzbz'],
				$this->post()['data'],
				$this->post()['orgform_kurzbz']
			);
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response($validation, REST_Controller::HTTP_OK);
		}
	}
	
	private function _validatePostMessage($message = null)
	{
		if (!isset($message))
		{
			return $this->_error('Parameter is null');
		}
		if (!isset($message['person_id']))
		{
			return $this->_error('person_id is not set');
		}
		if (!isset($message['subject']))
		{
			return $this->_error('subject is not set');
		}
		if( !isset($message['body']))
		{
			return $this->_error('body is not set');
		}
		if (!isset($message['oe_kurzbz']))
		{
			return $this->_error('oe_kurzbz is not set');
		}
		
		return $this->_success('Input data are valid');
	}
	
	private function _validatePostMessageVorlage($message = null)
	{
		if (!isset($message))
		{
			return $this->_error('Parameter is null');
		}
		if (!isset($message['sender_id']))
		{
			return $this->_error('person_id of sender is not set');
		}
		if (!isset($message['receiver_id']))
		{
			return $this->_error('person_id of receiver is not set');
		}
		if (!isset($message['vorlage_kurzbz']))
		{
			return $this->_error('vorlage_kurzbz is not set');
		}
		if( !isset($message['oe_kurzbz']))
		{
			return $this->_error('oe_kurzbz is not set');
		}
		if (!isset($message['data']))
		{
			return $this->_error('data is not set');
		}
		
		return $this->_success('Input data are valid');
	}
}