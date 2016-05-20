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

class Betreuerart extends APIv1_Controller
{
	/**
	 * Betreuerart API constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		// Load model BetreuerartModel
		$this->load->model('education/betreuerart', 'BetreuerartModel');
		// Load set the uid of the model to let to check the permissions
		$this->BetreuerartModel->setUID($this->_getUID());
	}

	/**
	 * @return void
	 */
	public function getBetreuerart()
	{
		$betreuerart_id = $this->get('betreuerart_kurzbz');
		
		if (isset($betreuerart_id))
		{
			$result = $this->BetreuerartModel->load($betreuerart_id);
			
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
	public function postBetreuerart()
	{
		if ($this->_validate($this->post()))
		{
			if (isset($this->post()['betreuerart_kurzbz']))
			{
				$result = $this->BetreuerartModel->update($this->post()['betreuerart_kurzbz'], $this->post());
			}
			else
			{
				$result = $this->BetreuerartModel->insert($this->post());
			}
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
	}
	
	private function _validate($betreuerart = NULL)
	{
		return true;
	}
}