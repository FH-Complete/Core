<?php  
	if (! defined('BASEPATH'))
		exit('No direct script access allowed');
/**
* Name:        Messaging Library for FH-Complete
*
*
*/

class VorlageLib
{
	private $recipients = array();
	
    public function __construct($params = null)
    {
        require_once APPPATH.'config/message.php';

		$this->ci =& get_instance();
		$this->ci->load->library('parser');
		$this->ci->load->model('system/Vorlage_model', 'VorlageModel');
		$this->ci->load->model('system/Vorlagestudiengang_model', 'VorlageStudiengangModel');
		
		if (is_array($params) && isset($params['uid']))
		{
			$this->ci->VorlageModel->setUID($params['uid']);
			$this->ci->VorlageStudiengangModel->setUID($params['uid']);
		}
		
        $this->ci->load->helper('language');
        //$this->ci->lang->load('fhcomplete');
    }

   	/**
     * getVorlage() - will load a spezific Template
     *
     * @param   integer  $vorlage_kurzbz    REQUIRED
     * @return  struct
     */
    function getVorlage($vorlage_kurzbz)
    {
        if (empty($vorlage_kurzbz))
        	return $this->_error(MSG_ERR_INVALID_MSG_ID);
		
        $vorlage = $this->ci->VorlageModel->load($vorlage_kurzbz);
        return $vorlage;
    }

    /**
     * getSubMessages() - will return all Messages subordinated from a specified message.
     *
     * @param   integer  $msg_id    REQUIRED
     * @return  array
     */
    function getVorlageByMimetype($mimetype = null)
    {
	    $vorlage = $this->ci->VorlageModel->loadWhere(array('mimetype' => $mimetype));
        return $vorlage;
    }
 	

	/**
     * saveVorlage() - will save a spezific Template.
     *
     * @param   array  $data    REQUIRED
     * @return  array
     */
    function saveVorlage($vorlage_kurzbz, $data)
    {
        if (empty($data))
        	return $this->_error(MSG_ERR_INVALID_MSG_ID);
		
        $vorlage = $this->ci->VorlageModel->update($vorlage_kurzbz, $data);
        return $vorlage;
    }


	/**
     * getVorlagetextByVorlage() - will load tbl_vorlagestudiengang for a spezific Template.
     *
     * @param   string  $vorlage_kurzbz    REQUIRED
     * @return  array
     */
    function getVorlagetextByVorlage($vorlage_kurzbz)
	{
        if (empty($vorlage_kurzbz))
        	return $this->_error($this->ci->lang->line('fhc_'.FHC_INVALIDID, false));
		
        $vorlage = $this->ci->VorlageStudiengangModel->loadWhere(array('vorlage_kurzbz' =>$vorlage_kurzbz));
        return $vorlage;
    }

	/**
     * loadVorlagetext() - will load the best fitting Template.
     *
     * @param   string  $vorlage_kurzbz REQUIRED
     * @param   string  $oe_kurzbz    	OPTIONAL
     * @param   string  $orgform_kurzbz OPTIONAL
     * @return  array
     */
    function loadVorlagetext($vorlage_kurzbz, $oe_kurzbz=null, $orgform_kurzbz=null)
	{
        if (empty($vorlage_kurzbz))
        	return $this->_error($this->ci->lang->line('fhc_'.FHC_INVALIDID, false));
		
        $vorlage = $this->ci->VorlageStudiengangModel->loadWhere(array(
			'vorlage_kurzbz' => $vorlage_kurzbz,
			'oe_kurzbz' => $oe_kurzbz,
			'orgform_kurzbz' => $orgform_kurzbz)
		);
        return $vorlage;
    }

	/**
     * insertVorlagetext() - will load tbl_vorlagestudiengang for a spezific Template.
     *
     * @param   string  $vorlage_kurzbz    REQUIRED
     * @return  array
     */
    function insertVorlagetext($data)
	{
        $vorlagetext = $this->ci->VorlageStudiengangModel->insert($data);
        return $vorlagetext;
    }

	/**
     * loadVorlagetext() - will load tbl_vorlagestudiengang for a spezific Template.
     *
     * @param   string  $vorlage_kurzbz    REQUIRED
     * @return  array
     */
    function getVorlagetextById($vorlagestudiengang_id)
	{
        $vorlagetext = $this->ci->VorlageStudiengangModel->load($vorlagestudiengang_id);
        return $vorlagetext;
    }

	/**
     * saveVorlagetext() - will load tbl_vorlagestudiengang for a spezific Template.
     *
     * @param   string  $vorlage_kurzbz    REQUIRED
     * @return  array
     */
    function updateVorlagetext($vorlagestudiengang_id, $data)
	{
        $vorlagetext = $this->ci->VorlageStudiengangModel->update($vorlagestudiengang_id, $data);
        return $vorlagetext;
    }

	/**
     * parseVorlagetext() - will parse a Vorlagetext.
     *
     * @param   string  $text    REQUIRED
     * @param   array  $data    REQUIRED
     * @return  string
     */
    function parseVorlagetext($text, $data = array())
	{
        if (empty($text))
        	return $this->_error($this->ci->lang->line('fhc_'.FHC_INVALIDID, false));
		$text = $this->ci->parser->parse_string($text, $data, TRUE);
		return $text;
    }
	
	/** ---------------------------------------------------------------
	 * Success
	 *
	 * @param   mixed  $retval
	 * @return  array
	 */
	protected function _success($retval, $message = EXIT_SUCCESS)
	{
		$return = new stdClass();
		$return->error = EXIT_SUCCESS;
		$return->Code = $message;
		$return->msg = lang('message_' . $message);
		$return->retval = $retval;
		return $return;
	}
	
	/** ---------------------------------------------------------------
	 * General Error
	 *
	 * @return  array
	 */
	protected function _error($retval = '', $message = EXIT_ERROR)
	{
		$return = new stdClass();
		$return->error = EXIT_ERROR;
		$return->Code = $message;
		$return->msg = lang('message_' . $message);
		$return->retval = $retval;
		return $return;
	}
}