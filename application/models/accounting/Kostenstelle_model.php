<?php
class Kostenstelle_model extends DB_Model
{

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'wawi.tbl_kostenstelle';
		$this->pk = 'kostenstelle_id';
	}

	/**
	 * Gets all active Kostenstellen for a geschaeftsjahr, as determined by the geschaeftsjahrvon and bis fields
	 * @param $geschaeftsjahr
	 * @return array|null
	 */
	public function getActiveKostenstellenForGeschaeftsjahr($geschaeftsjahr = null)
	{
		$this->load->model('organisation/geschaeftsjahr_model', 'GeschaeftsjahrModel');

		if ($geschaeftsjahr === null)
		{
			$lgj = $this->GeschaeftsjahrModel->getLastGeschaeftsjahr();

			if ($lgj->error)
				return error($lgj->retval);

			if (count($lgj->retval) < 1)
				return success(array());

			$geschaeftsjahr = $lgj->retval[0]->geschaeftsjahr_kurzbz;
		}

		$this->GeschaeftsjahrModel->addSelect('start, ende');
		$gj = $this->GeschaeftsjahrModel->load($geschaeftsjahr);

		if ($gj->error)
			return error($gj->retval);

		if (count($gj->retval) < 1)
			return success(array());

		$gjstart = $gj->retval[0]->start;

		$query = 'SELECT kostenstelle_id, kostenstelle_nr, kurzbz, wawi.tbl_kostenstelle.bezeichnung, kgjvon.start, kgjbis.ende 
					FROM wawi.tbl_kostenstelle 
					LEFT JOIN public.tbl_geschaeftsjahr kgjvon on wawi.tbl_kostenstelle.geschaeftsjahrvon = kgjvon.geschaeftsjahr_kurzbz
					LEFT JOIN public.tbl_geschaeftsjahr kgjbis on wawi.tbl_kostenstelle.geschaeftsjahrbis = kgjbis.geschaeftsjahr_kurzbz 
					WHERE
					(wawi.tbl_kostenstelle.geschaeftsjahrbis IS NULL AND wawi.tbl_kostenstelle.geschaeftsjahrvon IS NULL)
					OR 
					(DATE ? >= kgjvon.start AND (DATE ? < kgjbis.ende OR wawi.tbl_kostenstelle.geschaeftsjahrbis IS NULL))
					OR
					(DATE ? < kgjbis.ende AND (DATE ? >= kgjvon.start OR wawi.tbl_kostenstelle.geschaeftsjahrvon IS NULL))
					ORDER BY wawi.tbl_kostenstelle.bezeichnung';

		return $this->execQuery($query, array($gjstart, $gjstart, $gjstart, $gjstart));
	}
}
