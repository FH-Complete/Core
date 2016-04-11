<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class FHC_Model extends CI_Model 
{
	//protected errormsg;
	function __construct()  
	{
        parent::__construct();
		$this->load->helper('language');
        $this->lang->load('fhcomplete');
    }
	
	/** ---------------------------------------------------------------
     * Success
     *
     * @param   mixed  $retval
     * @return  array
     */
    protected function _success($retval = '', $message = FHC_SUCCESS)
    {
        return array(
            'err'    => 0,
            'code'   => FHC_SUCCESS,
            'msg'    => lang('fhc_' . $message),
            'retval' => $retval
        );
    }

    /** ---------------------------------------------------------------
     * General Error
     *
     * @return  array
     */
    protected function _general_error($retval = '', $message = FHC_ERR_GENERAL)
    {
        return array(
            'err'  => 1,
            'code' => FHC_ERR_GENERAL,
            'msg'  => lang('fhc_'.$message),
            'retval' => $retval
        );
    }
}

class DB_Model extends FHC_Model 
{

	protected $dbTable=null;  // Name of the DB-Table for CI-Insert, -Update, ...
	
	function __construct($uid=null)  
	{
        parent::__construct();
		$this->load->database();
		$this->load->helper('language');
        $this->lang->load('fhc_db');

		// UID must be set in Production Mode
		if (ENVIRONMENT=='production' && is_null($uid))
			log_message('error', 'UID must be set in Production Mode.');
		elseif (is_null($uid))
			log_message('info', 'UID is not set.');
		
		// Loading Tools for Access Control (Benutzerberechtigungen)
		$this->load->library('FHC_DB_ACL',array('uid' => $uid));
    }

	public function insert($data)
	{
		if (! is_null($this->dbTable))
		{
			$this->db->insert($this->dbTable, $data);
			return true;
		}
		else
			return false;
	}

	/** ---------------------------------------------------------------
     * Invalid ID
     *
     * @param   integer  config.php error code numbers
     * @return  array
     */
    protected function _invalid_id($error = '')
    {
        return array(
            'err'  => 1,
            'code' => $error,
            'msg'  => lang('fhc_'.$error)
        );
    }
}
