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

class Redirect extends FHC_Controller
{
	/**
	 * API constructor
	 */
	public function __construct()
	{
		parent::__construct();

		// Loads config file fhcomplete
		$this->config->load('fhcomplete');

		// Loads message helper
		$this->load->helper('message');

		// Loads model MessageTokenModel
		$this->load->model('system/MessageToken_model', 'MessageTokenModel');
	}

	/**
	 * redirectByToken
	 *
	 * - Loads the message using a token
	 * - Loads the root of the organisation unit tree using the oe_kurzbz present in the message
	 * - Redirect to the aufnahme related to the found organisation unit
	 */
	public function redirectByToken($token)
	{
		$msg = $this->MessageTokenModel->getMessageByToken($token);
		if ($msg->error)
		{
			show_error($msg->retval);
		}

		$oe_kurzbz = $msg->retval[0]->oe_kurzbz;

		if ($oe_kurzbz != null && $oe_kurzbz != '')
		{
			$organisationRoot = null;
			$getOERoot = $this->MessageTokenModel->getOERoot($oe_kurzbz);
			if ($getOERoot) // If no errors occurred
			{
				// If data are present
				if (is_array($getOERoot->result()) && count($getOERoot->result()) > 0)
				{
					$organisationseinheit = $getOERoot->result()[0];
					if ($organisationseinheit->oe_kurzbz == null)
					{
						show_error('No organisation unit present in the message');
					}
					else
					{
						$organisationRoot = $organisationseinheit->oe_kurzbz;
					}
				}
			}

			$addonAufnahmeUrls = $this->config->item('addons_aufnahme_url');

			if (isset($token)
				&& hasData($msg)
				&& is_array($addonAufnahmeUrls)
				&& $organisationRoot != null
				&& isset($addonAufnahmeUrls[$organisationRoot]))
			{
				redirect($addonAufnahmeUrls[$organisationRoot] . '?token=' . $token);
			}
		}
	}
}
