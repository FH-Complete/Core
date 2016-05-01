<?php

class DB_Model extends FHC_Model
{
	protected $dbTable;  // Name of the DB-Table for CI-Insert, -Update, ...
	protected $pk;  // Name of the PrimaryKey for DB-Update, Load, ...
	protected $acl;  // Name of the PrimaryKey for DB-Update, Load, ...
	
	// Addon ID, stored to let to check the permissions
	private $_addonID;

	function __construct($dbTable = null, $pk = null)
	{
		parent::__construct();
		$this->dbTable = $dbTable;
		$this->pk = $pk;
		$this->load->database();
		$this->acl = $this->config->item('fhc_acl');
	}

	/** ---------------------------------------------------------------
	 * Insert Data into DB-Table
	 *
	 * @param   array $data  DataArray for Insert
	 * @return  array
	 */
	public function insert($data)
	{
		// Check Class-Attributes
		if(is_null($this->dbTable))
			return $this->_error(lang('fhc_'.FHC_NODBTABLE), FHC_MODEL_ERROR);

		// Check rights
		if (! $this->fhc_db_acl->isBerechtigt((string)($this->acl[$this->dbTable]), 'i'))
			return $this->_error(lang('fhc_'.FHC_NORIGHT), FHC_MODEL_ERROR);

		// DB-INSERT
		if ($this->db->insert($this->dbTable, $data))
			return $this->_success($this->db->insert_id());
		else
			return $this->_error($this->db->error(), FHC_DB_ERROR);
	}

	/** ---------------------------------------------------------------
	 * Replace Data in DB-Table
	 *
	 * @param   array $data  DataArray for Replacement
	 * @return  array
	 */
	public function replace($data)
	{
		// Check Class-Attributes
		if(is_null($this->dbTable))
			return $this->_error(lang('fhc_'.FHC_NODBTABLE), FHC_MODEL_ERROR);
		
		// Check rights
		if (! $this->fhc_db_acl->isBerechtigt((string)($this->acl[$this->dbTable]), 'ui'))
			return $this->_error(lang('fhc_'.FHC_NORIGHT), FHC_MODEL_ERROR);

		// DB-REPLACE
		if ($this->db->replace($this->dbTable, $data))
			return $this->_success($this->db->insert_id());
		else
			return $this->_error($this->db->error(), FHC_DB_ERROR);
	}

	/** ---------------------------------------------------------------
	 * Update Data in DB-Table
	 *
	 * @param   string $id  PK for DB-Table
	 * @param   array $data  DataArray for Insert
	 * @return  array
	 */
	public function update($id, $data)
	{
		// Check Class-Attributes
		if(is_null($this->dbTable))
			return $this->_error(lang('fhc_'.FHC_NODBTABLE), FHC_MODEL_ERROR);
		if(is_null($this->pk))
			return $this->_error(lang('fhc_'.FHC_NOPK), FHC_MODEL_ERROR);
		
		// Check rights
		if (! $this->fhc_db_acl->isBerechtigt((string)($this->acl[$this->dbTable]), 'u'))
			return $this->_error(lang('fhc_'.FHC_NORIGHT), FHC_MODEL_ERROR);

		// DB-UPDATE
		$this->db->where($this->pk, $id);
		if ($this->db->update($this->dbTable, $data))
			return $this->_success($id);
		else
			return $this->_error($this->db->error(), FHC_DB_ERROR);
	}

	/** ---------------------------------------------------------------
	 * Replace Data in DB-Table
	 *
	 * @param   array $data  DataArray for Replacement
	 * @return  array
	 */
	public function load($id)
	{
		// Check Class-Attributes
		if(is_null($this->dbTable))
			return $this->_error(lang('fhc_'.FHC_NODBTABLE), FHC_MODEL_ERROR);
		if(is_null($this->pk))
			return $this->_error(lang('fhc_'.FHC_NOPK), FHC_MODEL_ERROR);
		
		// Check rights
		if (! $this->fhc_db_acl->isBerechtigt((string)($this->acl[$this->dbTable]), 's'))
			return $this->_error(lang('fhc_'.FHC_NORIGHT), FHC_MODEL_ERROR);

		// DB-SELECT
		if ($this->db->get_where($this->dbTable, array($this->pk => $id)))
			return $this->_success($id);
		else
			return $this->_error($this->db->error(), FHC_DB_ERROR);
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
			'err' => 1,
			'code' => $error,
			'msg' => lang('fhc_' . $error)
		);
	}
	
	/**
	 * Method setAddonID
	 * 
	 * @param $addonID
	 * @return void
	 */
	public function setAddonID($addonID)
	{
		$this->_addonID = $addonID;
	}
	
	/**
	 * Method getAddonID
	 * 
	 * @return string _addonID
	 */
	public function getAddonID()
	{
		return $this->_addonID;
	}
}
