<?php
class Mitarbeiter_model extends DB_Model
{

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'public.tbl_mitarbeiter';
		$this->pk = 'mitarbeiter_uid';
	}

    /**
     * Checks if the user is a Mitarbeiter.
     * @param string $uid
     * @param boolean null $fixangestellt
     * @return array
     */
    public function isMitarbeiter($uid, $fixangestellt = null)
    {
        $this->addSelect('1');

        if (is_bool($fixangestellt))
        {
            $result = $this->loadWhere(array('mitarbeiter_uid' => $uid, 'fixangestellt' => $fixangestellt));
        }
        else    // default
        {
            $result = $this->loadWhere(array('mitarbeiter_uid' => $uid));
        }

        if(hasData($result))
        {
            return success(true);
        }
        else
        {
            return success(false);
        }
    }

	/**
	 * Laedt das Personal
	 *
	 * @param $fix wenn true werden nur fixangestellte geladen
	 * @param $aktiv wenn true werden nur aktive geladen, wenn false dann nur inaktve, wenn null dann alle
	 * @param $verwendung wenn true werden alle geladen die eine BIS-Verwendung eingetragen haben
	 * @return array
	 */
	public function getPersonal($aktiv, $fix, $verwendung)
	{
		$qry = "SELECT DISTINCT ON(mitarbeiter_uid) staatsbuergerschaft, geburtsnation, sprache, anrede, titelpost, titelpre,
									nachname, vorname, vornamen, gebdatum, gebort, gebzeit, tbl_person.anmerkung AS person_anmerkung, homepage, svnr, ersatzkennzeichen, familienstand,
									geschlecht, anzahlkinder, tbl_person.insertamum AS person_insertamum, tbl_person.updateamum as person_updateamum,
									tbl_person.updatevon AS person_updatevon, kompetenzen, kurzbeschreibung, zugangscode, zugangscode_timestamp, bpk,
									tbl_benutzer.*, tbl_mitarbeiter.*, akt_funk.oe_kurzbz AS funktionale_zuordnung, akt_funk.wochenstunden
					FROM ((public.tbl_mitarbeiter JOIN public.tbl_benutzer ON(mitarbeiter_uid=uid))
					JOIN public.tbl_person USING(person_id))
			   LEFT JOIN public.tbl_benutzerfunktion USING(uid)
			   LEFT JOIN public.tbl_benutzerfunktion akt_funk ON tbl_mitarbeiter.mitarbeiter_uid = akt_funk.uid AND akt_funk.funktion_kurzbz = 'fachzuordnung'
			   													AND (akt_funk.datum_von IS NULL OR akt_funk.datum_von <= now()) AND (akt_funk.datum_bis IS NULL OR akt_funk.datum_bis >= now())
				   WHERE true";

		if ($fix === true)
			$qry .= " AND fixangestellt=true";
		elseif ($fix === false)
			$qry .= " AND fixangestellt=false";

		if ($aktiv === true)
			$qry .= " AND tbl_benutzer.aktiv=true";
		elseif ($aktiv === false)
			$qry .= " AND tbl_benutzer.aktiv=false";

		if ($verwendung === true)
		{
			$qry.=" AND EXISTS(SELECT * FROM bis.tbl_bisverwendung WHERE (ende>now() or ende is null) AND tbl_bisverwendung.mitarbeiter_uid=tbl_mitarbeiter.mitarbeiter_uid)";
		}
		elseif ($verwendung === false)
		{
			$qry.=" AND NOT EXISTS(SELECT * FROM bis.tbl_bisverwendung WHERE (ende>now() or ende is null) AND tbl_bisverwendung.mitarbeiter_uid=tbl_mitarbeiter.mitarbeiter_uid)";
		}

		return $this->execQuery($qry);
	}

	/**
	 * Gibt ein Array mit den UIDs der Vorgesetzten zurück
	 * @return object
	 */
	public function getVorgesetzte($uid)
	{
		$qry = "SELECT
					DISTINCT uid  as vorgesetzter
				FROM
					public.tbl_benutzerfunktion
				WHERE
					funktion_kurzbz='Leitung' AND
					(datum_von is null OR datum_von<=now()) AND
					(datum_bis is null OR datum_bis>=now()) AND
					oe_kurzbz in (SELECT oe_kurzbz
								  FROM public.tbl_benutzerfunktion
								  WHERE
									funktion_kurzbz='oezuordnung' AND uid=? AND
									(datum_von is null OR datum_von<=now()) AND
									(datum_bis is null OR datum_bis>=now())
								  );";

		return $this->execQuery($qry, array($uid));
	}
}
