<?php
/* Copyright (C) 2006 Technikum-Wien
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307, USA.
 *
 * Authors: Christian Paminger <christian.paminger@technikum-wien.at>,
 *          Andreas Oesterreicher <andreas.oesterreicher@technikum-wien.at> and
 *          Rudolf Hangl <rudolf.hangl@technikum-wien.at>.
 */
require_once(dirname(__FILE__).'/basis_db.class.php');
require_once(dirname(__FILE__).'/log.class.php');

class lehreinheit extends basis_db
{
	public $new;      					// boolean
	public $lehreinheiten = array();	// lehreinheit Objekt

	//Tabellenspalten
	public $lehreinheit_id;			// integer
	public $lehrveranstaltung_id;		// integer
	public $studiensemester_kurzbz; 	// varchar(16)
	public $lehrfach_id;				// integer
	public $lf_kurzbz;
	public $lf_bez;
	public $lf_aktiv;
	public $lehrform_kurzbz;			// varchar(8)
	public $stundenblockung;			// smalint
	public $wochenrythmus;				// smalint
	public $start_kw;					// smalint
	public $raumtyp;					// varchar(8)
	public $raumtypalternativ;			// varchar(8)
	public $lehre;						// boolean
	public $anmerkung;					// varchar(255)
	public $unr;						// integer
	public $lvnr;						// bigint
	public $insertamum;					// timestamp
	public $insertvon;					// varchar(16)
	public $updateamum;					// timestamp
	public $updatevon;					// varchar(16)
	public $sprache;					// varchar(16)
	public $ext_id;						// bigint

	public $anz=0;						//Zahler fuer erweiterte Attribute
	public $mitarbeiter_uid=array();
	public $studiengang_kz=array();
	public $semester=array();
	public $verband=array();
	public $gruppe=array();
	public $gruppe_kurzbz=array();
	public $titel=array();
	public $lehrform=array();

	/**
	 * Konstruktor - Laedt optional eine LE
	 * @param $gruppe_kurzbz
	 */
	public function __construct($lehreinheit_id=null)
	{
		parent::__construct();
		
		if($lehreinheit_id!=null)
			$this->load($lehreinheit_id);
	}

	/**
	 * Laedt die LE
	 * @param lehreinheit_id
	 */
	public function load($lehreinheit_id)
	{
		if(!is_numeric($lehreinheit_id))
		{
			$this->errormsg = 'Lehreinheit_id muss eine gueltige Zahl sein';
			return false;
		}

		$qry = "SELECT * FROM lehre.tbl_lehreinheit WHERE lehreinheit_id='$lehreinheit_id'";

		if($this->db_query($qry))
		{
			if($row = $this->db_fetch_object())
			{
				$this->lehreinheit_id = $row->lehreinheit_id;
				$this->lehrveranstaltung_id = $row->lehrveranstaltung_id;
				$this->studiensemester_kurzbz = $row->studiensemester_kurzbz;
				$this->lehrfach_id = $row->lehrfach_id;
				$this->lehrform_kurzbz = $row->lehrform_kurzbz;
				$this->stundenblockung = $row->stundenblockung;
				$this->wochenrythmus = $row->wochenrythmus;
				$this->start_kw = $row->start_kw;
				$this->raumtyp = $row->raumtyp;
				$this->raumtypalternativ = $row->raumtypalternativ;
				$this->lehre = ($row->lehre=='t'?true:false);
				$this->anmerkung = $row->anmerkung;
				$this->unr = $row->unr;
				$this->lvnr = $row->lvnr;
				$this->sprache = $row->sprache;
				$this->insertamum = $row->insertamum;
				$this->insertvon = $row->insertvon;
				$this->updateamum = $row->updateamum;
				$this->updatevon = $row->updatevon;
				$this->ext_id = $row->ext_id;
				return true;
			}
			else 
			{
				$this->errormsg = 'Es existiert keine Lehreinheit mit dieser ID';
				return false;
			}
		}
		else
		{
			$this->errormsg = 'Fehler beim Laden der Lehreinheit';
			return false;
		}
	}
	
	/**
	 * Laedt die LE von der View mit erweiterten Attributen
	 * @param lehreinheit_id
	 */
	public function loadLE($lehreinheit_id)
	{
		if(!is_numeric($lehreinheit_id))
		{
			$this->errormsg = 'Lehreinheit_id muss eine gueltige Zahl sein';
		}

		$qry = "SELECT * FROM campus.vw_lehreinheit WHERE lehreinheit_id='$lehreinheit_id'";

		if($this->db_query($qry))
		{
			$this->anz=0;
			while($row = $this->db_fetch_object())
			{	
				$this->lehreinheit_id = $row->lehreinheit_id;
				$this->lehrveranstaltung_id = $row->lehrveranstaltung_id;
				$this->studiensemester_kurzbz = $row->studiensemester_kurzbz;
				$this->lehrfach_id = $row->lehrfach_id;
				$this->lehrform_kurzbz = $row->lehrform_kurzbz;
				$this->stundenblockung = $row->stundenblockung;
				$this->wochenrythmus = $row->wochenrythmus;
				$this->start_kw = $row->start_kw;
				$this->raumtyp = $row->raumtyp;
				$this->raumtypalternativ = $row->raumtypalternativ;
				$this->sprache = $row->sprache;
				$this->lehre = ($row->lehre=='t'?true:false);
				$this->anmerkung = $row->anmerkung;
				$this->unr = $row->unr;
				$this->lvnr = $row->lvnr;
				$this->insertamum = $row->insertamum;
				$this->insertvon = $row->insertvon;
				$this->updateamum = $row->updateamum;
				$this->updatevon = $row->updatevon;
				//$this->ext_id = $row->ext_id;
				$this->farbe = $row->farbe;
				$this->lf_kurzbz= $row->lehrfach;
				$this->lf_bez= $row->lehrfach_bez;
				$this->lf_aktiv= $row->aktiv;

				$this->mitarbeiter_uid[$this->anz]	= $row->mitarbeiter_uid;
				$this->studiengang_kz[$this->anz] 	= $row->studiengang_kz;
				$this->semester[$this->anz] 			= $row->semester;
				$this->verband[$this->anz] 			= $row->verband;
				$this->gruppe[$this->anz] 			= $row->gruppe;
				$this->gruppe_kurzbz[$this->anz] 		= $row->gruppe_kurzbz;
				$this->titel[$this->anz] 			= '';
				
				$this->anz++;
			}
			return true;
		}
		else
		{
			$this->errormsg = 'Fehler beim Laden der Lehreinheit';
			return false;
		}
	}

	/**
	 * Laedt die Lehreinheiten zu einer Lehrveranstaltung
	 *
	 * @param $lehrveranstaltung_id
	 * @param $studiensemester_kurzbz
	 * @param $uid
	 * @param $fachbereich_kurzbz
	 * @return boolean
	 */
	public function load_lehreinheiten($lehrveranstaltung_id, $studiensemester_kurzbz, $uid='', $fachbereich_kurzbz='')
	{
		$qry = "SELECT * FROM lehre.tbl_lehreinheit WHERE 
				lehrveranstaltung_id='".addslashes($lehrveranstaltung_id)."' 
				AND studiensemester_kurzbz='".addslashes($studiensemester_kurzbz)."'";

		if($uid!='')
			$qry .= " AND lehreinheit_id IN ( SELECT lehreinheit_id FROM lehre.tbl_lehreinheitmitarbeiter WHERE mitarbeiter_uid='".addslashes($uid)."')";

		if($fachbereich_kurzbz!='')
			$qry .= " AND lehrfach_id IN ( SELECT lehrfach_id FROM lehre.tbl_lehrfach WHERE fachbereich_kurzbz='".addslashes($fachbereich_kurzbz)."')";

		$qry.= "ORDER BY lehreinheit_id";
		
		if($this->db_query($qry))
		{
			while($row = $this->db_fetch_object())
			{
				$le_obj = new lehreinheit();

				$le_obj->lehreinheit_id = $row->lehreinheit_id;
				$le_obj->lehrveranstaltung_id = $row->lehrveranstaltung_id;
				$le_obj->studiensemester_kurzbz = $row->studiensemester_kurzbz;
				$le_obj->lehrfach_id = $row->lehrfach_id;
				$le_obj->lehrform_kurzbz = $row->lehrform_kurzbz;
				$le_obj->stundenblockung = $row->stundenblockung;
				$le_obj->wochenrythmus = $row->wochenrythmus;
				$le_obj->start_kw = $row->start_kw;
				$le_obj->raumtyp = $row->raumtyp;
				$le_obj->raumtypalternativ = $row->raumtypalternativ;
				$le_obj->lehre = ($row->lehre=='t'?true:false);
				$le_obj->anmerkung = $row->anmerkung;
				$le_obj->unr = $row->unr;
				$le_obj->lvnr = $row->lvnr;
				$le_obj->sprache = $row->sprache;
				$le_obj->insertamum = $row->insertamum;
				$le_obj->insertvon = $row->insertvon;
				$le_obj->updateamum = $row->updateamum;
				$le_obj->updatevon = $row->updatevon;
				$le_obj->ext_id = $row->ext_id;

				$this->lehreinheiten[] = $le_obj;
			}
			return true;
		}
		else
		{
			$this->errormsg = 'Fehler beim Laden der Lehreinheiten';
			return false;
		}
	}

	/**
	 * Prueft die Variablen vor dem Speichern
	 * auf Gueltigkeit.
	 * @return true wenn ok, false im Fehlerfall
	 */
	protected function validate()
	{
		if($this->lehreinheit_id!='' && !is_numeric($this->lehreinheit_id))
		{
			$this->errormsg = 'Lehreinheit_id muss eine gueltige Zahl sein';
			return false;
		}
		if(!is_numeric($this->lehrveranstaltung_id))
		{
			$this->errormsg = 'LehrveranstaltungsNr muss eine gueltige Zahl sein';
			return false;
		}
		if(mb_strlen($this->studiensemester_kurzbz)>16)
		{
			$this->errormsg = 'Studiensemesterkurzbz darf nicht laenger als 16 Zeichen sein';
			return false;
		}
		if($this->studiensemester_kurzbz=='')
		{
			$this->errormsg = 'Studiensemester muss angegeben werden';
			return false;
		}
		if(!is_numeric($this->lehrfach_id))
		{
			$this->errormsg = 'Lehrfach_id muss eine gueltige Zahl sein';
			return false;
		}
		if(mb_strlen($this->lehrform_kurzbz)>8)
		{
			$this->errormsg = 'Lehrform_kurzbz darf nicht laenger als 8 Zeichen sein';
			return false;
		}
		if($this->lehrform_kurzbz=='')
		{
			$this->lehrform_kurzbz='SO';
			//TODO
			//$this->errormsg = 'Lehrform muss angegeben werden';
			//return false;
		}
		if(!is_numeric($this->stundenblockung))
		{
			$this->errormsg = 'Stundenblockung muss eine gueltige Zahl sein';
			return false;
		}
		if(!is_numeric($this->wochenrythmus))
		{
			$this->errormsg = 'Wochenrythmus muss eine gueltige Zahl sein';
			return false;
		}
		if($this->start_kw!='' && !is_numeric($this->start_kw))
		{
			$this->errormsg = 'StartKW muss eine gueltige Zahl sein';
			return false;
		}
		if($this->start_kw!='' && ($this->start_kw>53 || $this->start_kw<1))
		{
			$this->errormsg = 'StartKW muss zwischen 1 und 53 liegen';
			return false;
		}
		if(mb_strlen($this->raumtyp)>8)
		{
			$this->errormsg = 'Raumtyp darf nicht laenger als 8 Zeichen sein';
			return false;
		}
		if(mb_strlen($this->raumtypalternativ)>8)
		{
			$this->errormsg = 'Raumtypalternativ darf nicht alenger als 8 Zeichen sein';
			return false;
		}
		if($this->raumtypalternativ=='')
		{
			//TODO
			$this->raumtypalternativ='Dummy';
		}
		if(!is_bool($this->lehre))
		{
			$this->errormsg = 'Lehre muss ein boolscher Wert sein';
			return false;
		}
		if(mb_strlen($this->anmerkung)>255)
		{
			$this->errormsg = 'Anmerkung darf nicht laenger als 255 Zeichen sein';
			return false;
		}
		if($this->unr!='' && !is_numeric($this->unr))
		{
			$this->errormsg = 'UNR muss eine gueltige Zahl sein';
			return false;
		}
		if($this->ext_id!='' && !is_numeric($this->ext_id))
		{
			$this->errormsg = 'Ext_id muss eine gueltige Zahl sein';
			return false;
		}

		return true;
	}

	/**
	 * Speichert LE in die Datenbank
	 * Wenn $new auf true gesetzt ist wird ein neuer Datensatz
	 * angelegt, ansonsten der Datensatz upgedated
	 * @return true wenn erfolgreich, false im Fehlerfall
	 */
	public function save($new=null)
	{
		if(is_null($new))
			$new = $this->new;

		//Variablen auf Gueltigkeit pruefen
		if(!$this->validate())
			return false;

		if($new)
		{
			if($this->unr=='')
				$unr="currval('lehre.tbl_lehreinheit_lehreinheit_id_seq')";
			else
				$unr = $this->addslashes($this->unr);
			//ToDo ID entfernen
			$qry = 'BEGIN; INSERT INTO lehre.tbl_lehreinheit (lehrveranstaltung_id, studiensemester_kurzbz,
			                                     lehrfach_id, lehrform_kurzbz, stundenblockung, wochenrythmus,
			                                     start_kw, raumtyp, raumtypalternativ, lehre, anmerkung, unr, lvnr, insertamum, insertvon, updateamum, updatevon,  ext_id, sprache)
			        VALUES('.$this->addslashes($this->lehrveranstaltung_id).','.
					$this->addslashes($this->studiensemester_kurzbz).','.
					$this->addslashes($this->lehrfach_id).','.
					$this->addslashes($this->lehrform_kurzbz).','.
					$this->addslashes($this->stundenblockung).','.
					$this->addslashes($this->wochenrythmus).','.
					$this->addslashes($this->start_kw).','.
					$this->addslashes($this->raumtyp).','.
					$this->addslashes($this->raumtypalternativ).','.
					($this->lehre?'true':'false').','.
					$this->addslashes($this->anmerkung).','.
					$unr.','.
					$this->addslashes($this->lvnr).','.
					$this->addslashes($this->insertamum).','.
					$this->addslashes($this->insertvon).','.
					$this->addslashes($this->updateamum).','.
					$this->addslashes($this->updatevon).','.
					$this->addslashes($this->ext_id).','.
					$this->addslashes($this->sprache).');';
		}
		else
		{
			$qry = 'UPDATE lehre.tbl_lehreinheit SET'.
			       ' lehrveranstaltung_id='.$this->addslashes($this->lehrveranstaltung_id).','.
			       ' studiensemester_kurzbz='.$this->addslashes($this->studiensemester_kurzbz).','.
			       ' lehrfach_id='.$this->addslashes($this->lehrfach_id).','.
			       ' lehrform_kurzbz='.$this->addslashes($this->lehrform_kurzbz).','.
			       ' stundenblockung='.$this->addslashes($this->stundenblockung).','.
			       ' wochenrythmus='.$this->addslashes($this->wochenrythmus).','.
			       ' start_kw='.$this->addslashes($this->start_kw).','.
			       ' raumtyp='.$this->addslashes($this->raumtyp).','.
			       ' raumtypalternativ='.$this->addslashes($this->raumtypalternativ).','.
			       ' lehre='.($this->lehre?'true':'false').','.
			       ' anmerkung='.$this->addslashes($this->anmerkung).','.
			       ' unr='.$this->addslashes($this->unr).','.
			       ' lvnr='.$this->addslashes($this->lvnr).','.
				   ' updateamum='.$this->addslashes($this->updateamum).','.
				   ' updatevon='.$this->addslashes($this->updatevon).','.
				   ' sprache='.$this->addslashes($this->sprache).','.
			       ' ext_id='.$this->addslashes($this->ext_id).
			       " WHERE lehreinheit_id=".$this->addslashes($this->lehreinheit_id).";";
		}
		
		if($this->db_query($qry))
		{
			if($new)
			{
				//Sequence auslesen
				$qry ="SELECT currval('lehre.tbl_lehreinheit_lehreinheit_id_seq') AS lehreinheit_id";
				if($this->db_query($qry))
				{
					if($row = $this->db_fetch_object())
					{
						$this->lehreinheit_id = $row->lehreinheit_id;
						$this->db_query('COMMIT;');
					}
					else
					{
						$this->errormsg = 'Fehler beim Auslesen der Sequence';
						$this->db_query('ROLLBACK;');
						return false;
					}
				}
				else
				{
					$this->errormsg = 'Fehler beim Auslesen der Sequence';
					$this->db_query('ROLLBACK;');
					return false;
				}
			}
			//Log schreiben
			return true;
		}
		else
		{
			$this->errormsg = 'Fehler beim Speichern der LE: '.$qry;
			return false;
		}
	}

	/**
	 * Prueft die geladene Lehrveranstaltung auf Kollisionen im Stundenplan.
	 * Rueckgabewert 'false' und die Fehlermeldung steht in '$this->errormsg'.
	 * @param string	datum	gewuenschtes Datum YYYY-MM-TT
	 * @param integer	stunde	gewuenschte Stunde
	 * @param string	ort		gewuenschter Ort
	 * @param string	db_stpl_table	Tabllenname des Stundenplans im DBMS
	 * @return boolean true=ok, false=error
	 */
	public function check_lva($datum,$stunde,$ort,$stpl_table)
	{
		$ignore_reservation=false;
		$ignore_zeitsperre=false;
		// Parameter Checken
		// Bezeichnung der Stundenplan-Tabelle und des Keys
		$stpl_id=$stpl_table.TABLE_ID;
		$stpl_table='lehre.'.TABLE_BEGIN.$stpl_table;
		
		//Lektoren SQL
		$sql_lkt='';
		foreach ($this->mitarbeiter_uid as $lkt)
			$sql_lkt.="OR mitarbeiter_uid='".addslashes($lkt)."' ";
		$sql_lkt=mb_substr($sql_lkt,3);
		$sql_lkt="(($sql_lkt) AND mitarbeiter_uid!='_DummyLektor')";

		// Datenbank abfragen
		$sql_query="SELECT $stpl_id FROM $stpl_table
					WHERE datum='".addslashes($datum)."' AND stunde='".addslashes($stunde)."'
					AND (ort_kurzbz='".addslashes($ort)."' OR $sql_lkt)";
		if (is_numeric($this->unr))
			$sql_query.=" AND unr!='".addslashes($this->unr)."'";
		if (!$this->db_query($sql_query))
		{
			$this->errormsg=$this->db_last_error();
			return false;
		}
		$erg_stpl=$this->db_result;
		
		$anzahl=$this->db_num_rows($erg_stpl);
		//Check
		if ($anzahl==0)
		{
			// Reservierungen pruefen?
			if (!$ignore_reservation)
			{
				// Datenbank abfragen  	( studiengang_kz, titel, beschreibung )	 		
				//Lektoren SQL
				$sql_lkt='';
				foreach ($this->mitarbeiter_uid as $lkt)
					$sql_lkt.="OR uid='$lkt' ";
				$sql_lkt=mb_substr($sql_lkt,3);
				$sql_lkt="(($sql_lkt) AND uid!='_DummyLektor')";
				$sql_query="SELECT reservierung_id AS id, uid AS lektor, stg_kurzbz, ort_kurzbz, semester, verband, gruppe, gruppe_kurzbz, datum, stunde 
							FROM lehre.vw_reservierung
							WHERE datum='".addslashes($datum)."' AND stunde='".addslashes($stunde)."' 
							AND (ort_kurzbz='".addslashes($ort)."' OR $sql_lkt)";
				// Verband SQL
				//$sql_query.="(studiengang_kz=$this->studiengang_kz AND semester=$this->sem";
				//if ($this->ver!=null && $this->ver!='' && $this->ver!=' ')
				//	$sql_query.=" AND (verband='$this->ver' OR verband IS NULL OR verband='' OR verband=' ')";
				//if ($this->grp!=null && $this->grp!='' && $this->grp!=' ')
				//	$sql_query.=" AND (gruppe='$this->grp' OR gruppe IS NULL OR gruppe='' OR gruppe=' ')";
				//if ($this->gruppe_kurzbz!=null && $this->gruppe_kurzbz!='' && $this->gruppe_kurzbz!=' ')
				//	$sql_query.=" AND (gruppe_kurzbz='$this->gruppe_kurzbz')";
				//$sql_query.="))";
				
				//echo $sql_query.'<br>';
				if (!$this->db_query($sql_query))
				{
					$this->errormsg=$sql_query.$this->db_last_error();
					return false;
				}
				$erg_res=$this->db_result;
				$anz_res=$this->db_num_rows($erg_res);
				//Check
				if ($anz_res==0)
				{
					// Zeitsperren pruefen?
					if (!$ignore_zeitsperre)
					{
						// Datenbank abfragen  	( studiengang_kz, titel, beschreibung )	 		
						//Lektoren SQL
						$sql_lkt='';
						foreach ($this->mitarbeiter_uid as $lkt)
							$sql_lkt.="OR mitarbeiter_uid='$lkt' ";
						$sql_lkt=mb_substr($sql_lkt,3);
						$sql_query="SELECT * FROM campus.tbl_zeitsperre
										WHERE ($sql_lkt) AND 
											(  (vondatum<'$datum' AND bisdatum>'$datum') 
											OR (vondatum='$datum' AND bisdatum='$datum' AND vonstunde<=$stunde AND bisstunde>=$stunde)
											OR (vondatum='$datum' AND bisdatum>'$datum' AND vonstunde<=$stunde)
											OR (vondatum<'$datum' AND bisdatum='$datum' AND bisstunde>=$stunde) )";	
						//echo $sql_query.'<br>';
						if (!$this->db_query($sql_query))
						{
							$this->errormsg=$sql_query.$this->db_last_error();
							return false;
						}
						$erg_zs=$this->db_result;
						$anz_zs=$this->db_num_rows($erg_zs);
						//Check
						if ($anz_zs==0)
							return true;
						else
						{
							$row=$this->db_fetch_object($erg_zs); 																							 	 	 	 	 	
							$this->errormsg="Kollision (Zeitsperre): $row->zeitsperre_id|$row->mitarbeiter_uid|$row->zeitsperretyp_kurzbz|$row->bezeichnung|$row->vondatum/$row->vonstunde-$row->bisdatum/$row->bisstunde - $row->vertretung_uid";
							return false;
						}				
					}
					return true;
				}
				else
				{
					$row=$this->db_fetch_object($erg_res);
					$this->errormsg="Kollision (Reservierung): $row->id|$row->lektor|$row->ort_kurzbz|$row->stg_kurzbz-$row->semester$row->verband$row->gruppe$row->gruppe_kurzbz - $row->datum/$row->stunde";
					return false;
				}				
			}
			return true;
		}
		else
		{
			$row=$this->db_fetch_row($erg_stpl);
			$this->errormsg="Kollision mit StundenplanID($stpl_table.$stpl_id): $row[0]";
			return false;
		}
	}

	/**
	 * Speichert die geladene Lehreinheit im Stundenplan.
	 * Rueckgabewert 'false' und die Fehlermeldung steht in '$this->errormsg'.
	 * @param string	datum	gewuenschtes Datum YYYY-MM-TT
	 * @param integer	stunde	gewuenschte Stunde
	 * @param string	ort		gewuenschter Ort
	 * @param string	db_stpl_table	Tabllenname des Stundenplans im DBMS
	 * @param string	user	UID des aktuellen Bentzers
	 * @return boolean true=ok, false=error
	 */
	public function save_stpl($datum,$stunde,$ort,$stpl_table,$user)
	{
		// Parameter Checken
		// Bezeichnung der Stundenplan-Tabelle und des Keys
		$stpl_table='lehre.'.TABLE_BEGIN.$stpl_table;

		// Variablen pruefen
		if (!is_numeric($this->unr))
		{
			$this->errormsg='Error: UNR ist nicht vorhanden!';
			return false;
		}


		for ($i=0;$i<$this->anz;$i++)
		{
			// Datenbank INSERT
			$sql_query="INSERT INTO $stpl_table
				(unr,mitarbeiter_uid,datum,	stunde,	ort_kurzbz,lehreinheit_id,studiengang_kz,semester,verband,
				gruppe,	gruppe_kurzbz,	titel, anmerkung, updatevon)
				VALUES ($this->unr,'".$this->mitarbeiter_uid[$i]."','$datum',$stunde,
				'$ort',$this->lehreinheit_id, ".$this->studiengang_kz[$i].",".$this->semester[$i].",
				'".$this->verband[$i]."','".$this->gruppe[$i]."'";
			if ($this->gruppe_kurzbz[$i]==null)
				$sql_query.=',NULL';
			else
				$sql_query.=",'".$this->gruppe_kurzbz[$i]."'";
			$sql_query.=",'".$this->titel[$i]."','$this->anmerkung','$user')";
			if (!$this->db_query($sql_query))
			{
				$this->errormsg=$this->db_last_error();
				return false;
			}
		}
		return true;
	}

	/**
	 * Rueckgabewert ist ein Array mit den Ergebnissen. Bei Fehler false und die
	 * Fehlermeldung liegt in errormsg.
	 * Wenn der Parameter stg_kz NULL ist tritt gruppe_kurzbzb in Kraft.
	 * @param string $gruppe_kurzbz    Einheit
	 * @param string grp    Gruppe
	 * @param string ver    Verband
	 * @param integer sem    Semester
	 * @param integer stg_kz    Kennzahl des Studiengangs
	 * @return variabel Array mit LVA; <b>false</b> bei Fehler
	 */
	public function getLehreinheitLVPL($db_stpl_table,$studiensemester, $type, $stg_kz, $sem, $lektor, $ver=null, $grp=null, $gruppe=null)
	{
		$lva_stpl_view=VIEW_BEGIN.'lva_'.$db_stpl_table;

		if (mb_strlen($studiensemester)<=0)
		{
			$this->errormsg='Studiensemester ist nicht gesetzt!(lehreinheit.getLehreinheitLVPL)';
			return false;
		}
		else $where=" studiensemester_kurzbz='$studiensemester'";

		if ($type=='lektor')
			$where.=" AND lektor_uid='$lektor'";
		elseif ($type=='gruppe')
			$where.=" AND gruppe_kurzbz='$gruppe'";
		elseif ($type=='verband')
		{
			$where.=" AND studiengang_kz='$stg_kz'";
			if ($sem>0)
				$where.=" AND semester=$sem";
			if (mb_strlen($ver)>0 && $ver!=' ')
				$where.=" AND verband='$ver'";
			if (mb_strlen($grp)>0 && $grp!=' ')
				$where.=" AND gruppe='$grp' ";
		}
		$sql_query='SELECT *, planstunden-verplant::smallint AS offenestunden
			FROM lehre.'.$lva_stpl_view.' JOIN lehre.tbl_lehrform ON '.$lva_stpl_view.'.lehrform=tbl_lehrform.lehrform_kurzbz
			WHERE '.$where.' AND verplanen ORDER BY offenestunden DESC, lehrfach, lehrform, semester, verband, gruppe, gruppe_kurzbz;';
	    
		if(!$this->db_query($sql_query))
		{
			$this->errormsg=$this->db_last_error().$sql_query;
			return false;
		}
		while($row = $this->db_fetch_object())
		{
			$this->lehreinheiten[$row->unr]->lehreinheit_id[]=$row->lehreinheit_id;
			$this->lehreinheiten[$row->unr]->lvnr[]=$row->lvnr;
			$this->lehreinheiten[$row->unr]->unr=$row->unr;
			$this->lehreinheiten[$row->unr]->fachbereich=$row->fachbereich_kurzbz;
			$this->lehreinheiten[$row->unr]->lehrfach_id=$row->lehrfach_id;
			$this->lehreinheiten[$row->unr]->lehrfach[]=$row->lehrfach;
			$this->lehreinheiten[$row->unr]->lehrfach_bez[]=$row->lehrfach_bez;
			$this->lehreinheiten[$row->unr]->lehrfach_farbe[]=$row->lehrfach_farbe;
			$this->lehreinheiten[$row->unr]->lehrform[]=$row->lehrform;
			$this->lehreinheiten[$row->unr]->lektor_uid[]=$row->lektor_uid;
			$this->lehreinheiten[$row->unr]->lektor[]=trim($row->lektor);
			$this->lehreinheiten[$row->unr]->stg_kz[]=$row->studiengang_kz;
			$this->lehreinheiten[$row->unr]->stg[]=$row->studiengang;
			$this->lehreinheiten[$row->unr]->gruppe_kurzbz[]=$row->gruppe_kurzbz;
			$this->lehreinheiten[$row->unr]->semester[]=$row->semester;
			$this->lehreinheiten[$row->unr]->verband[]=$row->verband;
			$this->lehreinheiten[$row->unr]->gruppe[]=$row->gruppe;
			$this->lehreinheiten[$row->unr]->gruppe_kurzbz[]=$row->gruppe_kurzbz;
			$this->lehreinheiten[$row->unr]->raumtyp=$row->raumtyp;
			$this->lehreinheiten[$row->unr]->raumtypalternativ=$row->raumtypalternativ;
			$this->lehreinheiten[$row->unr]->stundenblockung[]=$row->stundenblockung;
			$this->lehreinheiten[$row->unr]->wochenrythmus[]=$row->wochenrythmus;
			$this->lehreinheiten[$row->unr]->semesterstunden[]=$row->semesterstunden;
			$this->lehreinheiten[$row->unr]->planstunden[]=$row->planstunden;
			$this->lehreinheiten[$row->unr]->start_kw[]=$row->start_kw;
			$this->lehreinheiten[$row->unr]->anmerkung[]=$row->anmerkung;
			$this->lehreinheiten[$row->unr]->studiensemester_kurzbz=$row->studiensemester_kurzbz;
			$this->lehreinheiten[$row->unr]->verplant[]=$row->verplant;
			$this->lehreinheiten[$row->unr]->offenestunden[]=$row->offenestunden;
			if (isset($this->lehreinheiten[$row->unr]->verplant_gesamt))
				$this->lehreinheiten[$row->unr]->verplant_gesamt+=$row->verplant;
			else
				$this->lehreinheiten[$row->unr]->verplant_gesamt=$row->verplant;
			$lvb=$row->studiengang.'-'.$row->semester;
			if ($row->verband!='' && $row->verband!=' ' && $row->verband!='0' && $row->verband!=null)
				$lvb.=$row->verband;
			if ($row->gruppe!='' && $row->gruppe!=' ' && $row->gruppe!='0' && $row->gruppe!=null)
				$lvb.=$row->gruppe;
			if ($row->gruppe_kurzbz!='' && $row->gruppe_kurzbz!=null)
				$this->lehreinheiten[$row->unr]->lehrverband[]=$row->gruppe_kurzbz;
			else
				$this->lehreinheiten[$row->unr]->lehrverband[]=$lvb;
		}
		return true;
	}

	/**
	 * Loescht eine Lehreinheit
	 * @param lehreinheit_id
	 * @return boolean
	 */
	public function delete($lehreinheit_id)
	{
		if(!is_numeric($lehreinheit_id))
		{
			$this->errormsg = 'Lehreinheit_id muss eine gueltige Zahl sein';
			return false;
		}
		
		//Pruefen ob schon eine Kreuzerlliste fuer diese Lehreinheit angelegt wurde.
		//Falls ja dann wird das loeschen verweigert
		$qry = "SELECT count(*) as anzahl FROM campus.tbl_uebung WHERE lehreinheit_id='$lehreinheit_id'";
		if($this->db_query($qry))
		{
			if($row = $this->db_fetch_object())
			{
				if($row->anzahl>0)
				{
					$this->errormsg = 'Zu dieser Lehreinheit wurde bereits eine Kreuzerlliste angelegt. Solange fuer eine Lehreinheit Kreuzerllisten vorhanden sind, kann diese nicht geloescht werden.';
					return false;
				}
				else
				{
					$this->db_query('BEGIN');

					//UNDO Befehl zusammenbauen
					$undosql='';

					//LehreinheitMitarbeiter
					$qry = "SELECT * FROM lehre.tbl_lehreinheitmitarbeiter WHERE lehreinheit_id='$lehreinheit_id'";
					if($this->db_query($qry))
					{
						while($row = $this->db_fetch_object())
						{
							$undosql.=" INSERT INTO lehre.tbl_lehreinheitmitarbeiter(lehreinheit_id, mitarbeiter_uid, lehrfunktion_kurzbz, planstunden, stundensatz, faktor, anmerkung, bismelden, updateamum, updatevon, insertamum, insertvon, semesterstunden)
							            VALUES(".$this->addslashes($row->lehreinheit_id).",".$this->addslashes($row->mitarbeiter_uid).",".$this->addslashes($row->lehrfunktion_kurzbz).",".$this->addslashes($row->planstunden).",".$this->addslashes($row->stundensatz).",".$this->addslashes($row->faktor).",".
										$this->addslashes($row->anmerkung).",".($row->bismelden=='t'?'true':'false').",".$this->addslashes($row->updateamum).",".$this->addslashes($row->updatevon).",".$this->addslashes($row->insertamum).",".$this->addslashes($row->insertvon).",".$this->addslashes($row->semesterstunden).");";
						}
					}

					//LehreinheitGruppe
					$qry = "SELECT * FROM lehre.tbl_lehreinheitgruppe WHERE lehreinheit_id='$lehreinheit_id'";
					if($this->db_query($qry))
					{
						while($row = $this->db_fetch_object())
						{
							$undosql.=" INSERT INTO lehre.tbl_lehreinheitgruppe(lehreinheitgruppe_id, lehreinheit_id, studiengang_kz, semester, verband, gruppe, gruppe_kurzbz, updateamum, updatevon, insertamum, insertvon)
							            VALUES(".$this->addslashes($row->lehreinheitgruppe_id).",".$this->addslashes($row->lehreinheit_id).",".$this->addslashes($row->studiengang_kz).",'".addslashes($row->semester)."','".addslashes($row->verband)."','".addslashes($row->gruppe)."',".
										$this->addslashes($row->gruppe_kurzbz).",".$this->addslashes($row->updateamum).",".$this->addslashes($row->updatevon).",".$this->addslashes($row->insertamum).",".$this->addslashes($row->insertvon).");";
						}
					}

					//Lehreinheit
					$qry = "SELECT * FROM lehre.tbl_lehreinheit WHERE lehreinheit_id='$lehreinheit_id'";
					if($this->db_query($qry))
					{
						while($row = $this->db_fetch_object())
						{
							$undosql.=" INSERT INTO lehre.tbl_lehreinheit(lehreinheit_id, lehrveranstaltung_id, studiensemester_kurzbz, lehrfach_id, lehrform_kurzbz, stundenblockung, wochenrythmus, start_kw, raumtyp, raumtypalternativ, sprache, lehre, anmerkung, unr, lvnr,  updateamum, updatevon, insertamum, insertvon)
							            VALUES(".$this->addslashes($row->lehreinheit_id).",".$this->addslashes($row->lehrveranstaltung_id).",".$this->addslashes($row->studiensemester_kurzbz).",".$this->addslashes($row->lehrfach_id).",".$this->addslashes($row->lehrform_kurzbz).",".$this->addslashes($row->stundenblockung).",".
										$this->addslashes($row->wochenrythmus).",".$this->addslashes($row->startkw).",".$this->addslashes($row->raumtyp).",".$this->addslashes($row->raumtypalternativ).",".$this->addslashes($row->sprache).",".($row->wochenrythmus=='t'?'true':'false').",".
										$this->addslashes($row->anmerkung).",".$this->addslashes($row->unr).",".$this->addslashes($row->lvnr).",".$this->addslashes($row->updateamum).",".$this->addslashes($row->updatevon).",".$this->addslashes($row->insertamum).",".$this->addslashes($row->insertvon).");";
						}
					}

					$log = new log();

					//Gruppenzuteilung, Mitarbeiterzuteilung und Lehreinheit loeschen
					$qry = "DELETE FROM lehre.tbl_lehreinheitmitarbeiter WHERE lehreinheit_id='$lehreinheit_id';
							DELETE FROM lehre.tbl_lehreinheitgruppe WHERE lehreinheit_id='$lehreinheit_id';
							DELETE FROM lehre.tbl_lehreinheit WHERE lehreinheit_id='$lehreinheit_id';";

					$log->new = true;
					$log->sql = $qry;
					$log->sqlundo = $undosql;
					$log->executetime = date('Y-m-d H:i:s');
					$log->mitarbeiter_uid = get_uid();
					$log->beschreibung = "Lehreinheit loeschen - $lehreinheit_id";

					if(!$log->save())
					{
						$this->errormsg = 'Fehler beim Schreiben des Log-Eintrages';
						$this->db_query('ROLLBACK');
						return false;
					}
					else
					{
						if($this->db_query($qry))
						{
							$this->db_query('COMMIT');
							return true;
						}
						else
						{
							$this->db_query('ROLLBACK');
							$this->errormsg = $this->db_last_error();
							return false;
						}
					}
				}
			}
			else
			{
				$this->errormsg = 'Fehler beim Loeschen';
				return false;
			}
		}
		else
		{
			$this->errormsg = 'Fehler beim Loeschen';
			return false;
		}
	}
}
?>
