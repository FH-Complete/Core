<?php
class Vertrag_model extends DB_Model
{

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'lehre.tbl_vertrag';
		$this->pk = 'vertrag_id';
	}

    /**
     * Saves Vertrag for a Lehrauftrag and sets Vertragsstatus to 'bestellt'.
     * Also updates vertrag_id in tbl_lehreinheitmitarbeiter or tbl_projektbetreuer.
     * @param $person_id
     * @param $lehrveranstaltung_id
     * @param $lehreinheit_id
     * @param $projektarbeit_id
     * @param $betrag                   Monetary amount of that Lehreinheit / Projektbetreuung.
     * @param $vertragsstunden          Working hours of that Lehreinheit / Projektbetreuung.
     * @param $studiensemester_kurzbz
     * @param $vertragstyp_kurzbz
     * @return array|null               On success object. On failure null.
     */
	public function save($person_id, $mitarbeiter_uid, $lehrveranstaltung_id, $lehreinheit_id, $projektarbeit_id = null, $vertragsstunden, $betrag, $studiensemester_kurzbz){

        // Cast input params
        $person_id = (!isset($person_id) || empty($person_id)) ? null : intval($person_id);
        $lehreinheit_id = (!isset($lehreinheit_id) || empty($lehreinheit_id)) ? null : intval($lehreinheit_id);
        $lehrveranstaltung_id = (!isset($lehrveranstaltung_id) || empty($lehrveranstaltung_id)) ? null : intval($lehrveranstaltung_id);
        $projektarbeit_id = (!isset($projektarbeit_id) || empty($projektarbeit_id)) ? null : intval($projektarbeit_id);
        $vertragsstunden = (!isset($vertragsstunden) || empty($vertragsstunden)) ? null : floatval($vertragsstunden);
        $betrag = (!isset($betrag) || empty($betrag)) ? null : floatval($betrag);
        $mitarbeiter_uid = (!isset($mitarbeiter_uid) || empty($mitarbeiter_uid)) ? null : $mitarbeiter_uid;

        $vertragstyp_kurzbz = (is_null($projektarbeit_id)) ? 'Lehrauftrag' : 'Betreuung';

	    $result = array();
	    $user = getAuthUID();

        // First check if Vertrag already exists for that Lehrauftrag or for that Projektbetreuerauftrag
        if ($vertragstyp_kurzbz == 'Lehrauftrag')
        {
            $this->load->model('education/Lehreinheitmitarbeiter_model', 'LehreinheitmitarbeiterModel'); //
            if ($this->LehreinheitmitarbeiterModel->hasVertrag($mitarbeiter_uid, $lehreinheit_id))
            {
                return $result = success(null);   // Exit if Lehrauftrag already has Vertrag
            }
        }
        elseif ($vertragstyp_kurzbz == 'Betreuung')
        {
            $this->load->model('education/Projektbetreuer_model', 'ProjektbetreuerModel');
            if ($this->ProjektbetreuerModel->hasVertrag($person_id, $projektarbeit_id))
            {
                return $result = success(null);   // Exit if Projektbetreuung already has Vertrag
            }
        }

        // If Vertrag does not exist, create now

        // Vertragsbezeichnung
        $bezeichnung = $this->_writeVertragsbezeichung($lehrveranstaltung_id, $studiensemester_kurzbz);

        // Start DB transaction
        $this->db->trans_start(false);

        // Insert Vertragsdata
        $result = $this->insert(array(
            'person_id' => $person_id,
            'lehrveranstaltung_id' => $lehrveranstaltung_id,
            'vertragstyp_kurzbz' => $vertragstyp_kurzbz,
            'bezeichnung' => $bezeichnung,
            'betrag' => $betrag,
            'insertamum' => 'NOW()',
            'insertvon' => $user,
            'vertragsdatum' => 'NOW()',
            'vertragsstunden' => $vertragsstunden,
            'vertragsstunden_studiensemester_kurzbz' => $studiensemester_kurzbz
        ));

        // Retrieve primary key
        $vertrag_id = $result->retval;

        // If Vertrag was created successfully, update vertrag_id
        if (isSuccess($result))
        {
            // if Lehrtätigkeit, update vertrag_id in tbl_lehreinheitmitarbeiter
            if ($vertragstyp_kurzbz == 'Lehrauftrag')
            {
                $this->load->model('education/Lehreinheitmitarbeiter_model', 'LehreinheitmitarbeiterModel');
                $result = $this->LehreinheitmitarbeiterModel->update(
                    array(
                        'lehreinheit_id' =>  $lehreinheit_id,
                        'mitarbeiter_uid' =>$mitarbeiter_uid
                    ),
                    array(
                        'vertrag_id' => $vertrag_id
                    )
                );
            }
            // if (Projekt-)Betreuung, update vertrag_id in tbl_projektbetreuer
            elseif ($vertragstyp_kurzbz == 'Betreuung')
            {
                $this->load->model('education/Projektbetreuer_model', 'ProjektbetreuerModel');
                $result = $this->ProjektbetreuerModel->update(
                    array(
                        'person_id' =>  $person_id,
                        'projektarbeit_id' => $projektarbeit_id
                    ),
                    array(
                        'vertrag_id' => $vertrag_id
                    )
                );
            }
        }

        // If updating vertrag_id was successfully, set Status to 'bestellt'
        if (isSuccess($result))
        {
            $result = $this->setStatus($vertrag_id, $mitarbeiter_uid,'bestellt');
        }

        // Transaction complete!
        $this->db->trans_complete();

        // Check if everything went ok during the transaction
        if ($this->db->trans_status() === false || isError($result))
        {
            $this->db->trans_rollback();
            $result = error($result->msg, EXIT_ERROR);
        }
        else
        {
            $this->db->trans_commit();
            $result = success($vertrag_id);
        }

        return $result;

    }

    /**
     * Check if Vertrag has the given Vertragsstatus.
     * @param integer $vertrag_id
     * @param string $mitarbeiter_uid
     * @param string $vertragsstatus_kurzbz
     * @return array
     */
    public function hasStatus($vertrag_id, $mitarbeiter_uid, $vertragsstatus_kurzbz)
    {
        $this->addSelect('1');
        $this->addJoin('lehre.tbl_vertrag_vertragsstatus', 'vertrag_id');
        $this->addLimit(1);

        return $this->loadWhere(array(
            'vertrag_id' => $vertrag_id,
            'uid' => $mitarbeiter_uid,
            'vertragsstatus_kurzbz' => $vertragsstatus_kurzbz
        ));
    }

    /**
     * Sets Vertragsstatus for the given Vertrag and Mitarbeiter.
     * @param $vertrag_id
     * @param $vertragsstatus_kurzbz
     * @param $mitarbeiter_uid
     * @return array|null           On success object, retval is true. Null if status already exist for this vertrag.
     */
    public function setStatus($vertrag_id, $mitarbeiter_uid, $vertragsstatus_kurzbz){

        // First check if vertrag has already this status
        $this->addJoin('lehre.tbl_vertrag_vertragsstatus', 'vertrag_id');

        $result = $this->loadWhere(array(
            'vertrag_id' => $vertrag_id,
            'uid' => $mitarbeiter_uid,
            'vertragsstatus_kurzbz' => $vertragsstatus_kurzbz
        ));

        if (!isEmptyArray($result->retval))
        {
            return success(null); // return null if status already set
        }

        // Set new status
        $query = '
            INSERT INTO lehre.tbl_vertrag_vertragsstatus(
                vertragsstatus_kurzbz,
                vertrag_id,
                uid,
                datum,
                insertvon,
                updatevon,
                updateamum
            ) VALUES (?, ?, ?, ?, ?, ?, ?);';

        return $this->execQuery($query, array($vertragsstatus_kurzbz, $vertrag_id, $mitarbeiter_uid, 'NOW()', getAuthUID(), null, null));
    }

    /**
     * Get the latest Vertragsstatus for the given Vertrag and Mitarbeiter
     * @param integer $vertrag_id
     * @param string $mitarbeiter_uid
     * @return array
     */
    public function getLastStatus($vertrag_id, $mitarbeiter_uid)
    {
        $this->addSelect('vertragsstatus_kurzbz');
        $this->addJoin('lehre.tbl_vertrag_vertragsstatus', 'vertrag_id');
        $this->addOrder('datum', 'DESC');
        $this->addLimit(1);
        return $this->loadWhere(
            array(
                'vertrag_id' => $vertrag_id,
                'uid' => $mitarbeiter_uid
            )
        );
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Private methods

    /**
     * Generate contract description.
     * Example: WS2017-BEE3-LIA-LAB
     * @param $lehrveranstaltung_id
     * @param $studiensemester_kurzbz   Studiensemester of Lehrauftrag (= when the lector will teach the lehrveranstaltung)
     * @return string
     */
    private function _writeVertragsbezeichung($lehrveranstaltung_id, $studiensemester_kurzbz)
    {
        $bezeichnung = '';
        $this->load->model('education/Lehrveranstaltung_model', 'LehrveranstaltungModel');
        $this->LehrveranstaltungModel->addSelect('tbl_lehrveranstaltung.semester, tbl_lehrveranstaltung.kurzbz, lehrform_kurzbz, public.tbl_studiengang.kurzbzlang');
        $this->LehrveranstaltungModel->addJoin('lehre.tbl_studienplan_lehrveranstaltung', 'lehrveranstaltung_id');
        $this->LehrveranstaltungModel->addJoin('lehre.tbl_studienplan', 'studienplan_id');
        $this->LehrveranstaltungModel->addJoin('lehre.tbl_studienordnung', 'studienordnung_id');
        $this->LehrveranstaltungModel->addJoin('public.tbl_studiengang', 'public.tbl_studiengang.studiengang_kz = lehre.tbl_studienordnung.studiengang_kz');
        $result = $this->LehrveranstaltungModel->load($lehrveranstaltung_id);

        if (hasData($result))
        {
            $bezeichnung = $studiensemester_kurzbz. '-';
            $bezeichnung.= $result->retval[0]->kurzbzlang. $result->retval[0]->semester. '-';
            $bezeichnung.= $result->retval[0]->kurzbz. '-';
            $bezeichnung.= $result->retval[0]->lehrform_kurzbz;
        }

        return $bezeichnung;
    }
}
