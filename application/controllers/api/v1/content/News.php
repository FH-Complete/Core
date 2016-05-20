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

class News extends APIv1_Controller
{
	/**
	 * News API constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		// Load model NewsModel
		$this->load->model('content/news_model', 'NewsModel');
		// Load set the uid of the model to let to check the permissions
		$this->NewsModel->setUID($this->_getUID());
	}

	/**
	 * @return void
	 */
	public function getNews()
	{
		$newsID = $this->get('news_id');
		
		if (isset($newsID))
		{
			$result = $this->NewsModel->load($newsID);
			
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
	public function postNews()
	{
		if ($this->_validate($this->post()))
		{
			if (isset($this->post()['news_id']))
			{
				$result = $this->NewsModel->update($this->post()['news_id'], $this->post());
			}
			else
			{
				$result = $this->NewsModel->insert($this->post());
			}
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
	}
	
	private function _validate($news = NULL)
	{
		return true;
	}
}