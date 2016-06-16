<?php
class Vorlage_model extends DB_Model
{

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'public.tbl_vorlage';
		$this->pk = 'vorlage_kurzbz';
	}

	public function getMimeTypes()
	{
		$qry = 'SELECT DISTINCT mimetype FROM public.tbl_vorlage ORDER BY mimetype;';

		
		if ($res = $this->db->query($qry))
			return $this->_success($res);
		else
			return $this->_error($this->db->error());
	}
}
