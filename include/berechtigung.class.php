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
require_once(dirname(__FILE__).'/datum.class.php');

// CI
require_once(dirname(__FILE__).'/../ci_hack.php');
require_once(dirname(__FILE__).'/../application/models/system/Berechtigung_model.php');

class berechtigung extends Berechtigung_model
{
	use db_extra; //CI Hack
	
	public $result=array();
	public $new;

	public $rolle_kurzbz;
	public $beschreibung;
	public $berechtigung_kurzbz;
	
	/**
	 * Konstruktor
	 * @param 
	 */
	public function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * Laedt eine Berechtigung
	 * @param $berechtigung_kurzbz
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function load($berechtigung_kurzbz)
	{
		$result = parent::load($berechtigung_kurzbz);
		
		if (is_object($result) && $result->error == EXIT_SUCCESS && is_array($result->retval))
		{
			if (count($result->retval) > 0)
			{
				$this->berechtigung_kurzbz = $row->berechtigung_kurzbz;
				$this->beschreibung = $row->beschreibung;
				return true;
			}
			else
			{
				$this->errormsg = 'Eintrag wurde nicht gefunden';
				return false;
			}
		}
		else
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return false;
		}
	}
	
	/**
	 * Speichert eine Berechtigung
	 *
	 */
	public function save($new=null)
	{
		if(is_null($new))
			$new = $this->new;
			
		if($new)
		{
			$qry = "INSERT INTO system.tbl_berechtigung(berechtigung_kurzbz, beschreibung) VALUES(".
					$this->db_add_param($this->berechtigung_kurzbz).','.
					$this->db_add_param($this->beschreibung).');';
		}
		else 
		{
			$qry = 'UPDATE system.tbl_berechtigung 
					SET beschreibung='.$this->db_add_param($this->beschreibung).'
					WHERE berechtigung_kurzbz='.$this->db_add_param($this->berechtigung_kurzbz).';';
		}
		
		if($this->db_query($qry))
		{
			return true;
		}
		else 
		{
			$this->errormsg = 'Fehler beim Speichern: '.$this->db_last_error();
			return false;
		}
	}
	
	/**
	 * Holt alle BerechtigungsRollen
	 * @return true wenn erfolgreich, false im Fehlerfall
	 */
	public function getRollen()
	{
		$qry = 'SELECT * FROM system.tbl_rolle';

		if($this->db_query($qry))
		{
			while($row = $this->db_fetch_object())
			{
				$obj = new berechtigung();
	
				$obj->rolle_kurzbz=$row->rolle_kurzbz;
				$obj->beschreibung=$row->beschreibung;
				
				$this->result[] = $obj;
			}
			return true;	
		}
		else
		{
			$this->errormsg = 'Datensatz konnte nicht geladen werden';
			return false;
		}		
	}
	
	/**
	 * Laedt alle Berechtigungen zu einer rolle
	 *
	 * @param $rolle_kurzbz
	 */
	public function getRolleBerechtigung($rolle_kurzbz)
	{
		$qry = "SELECT * FROM system.tbl_rolleberechtigung JOIN system.tbl_berechtigung USING(berechtigung_kurzbz)
				WHERE rolle_kurzbz=".$this->db_add_param($rolle_kurzbz)." ORDER BY berechtigung_kurzbz, beschreibung";
		
		if($this->db_query($qry))
		{
			while($row = $this->db_fetch_object())
			{
				$obj = new berechtigung();
				
				$obj->berechtigung_kurzbz = $row->berechtigung_kurzbz;
				$obj->rolle_kurzbz = $row->rolle_kurzbz;
				$obj->art = $row->art;
				$obj->beschreibung = $row->beschreibung;
				
				$this->result[] = $obj;
			}
			return true;
		}
		else 
		{
			$this->errormsg = 'Fehler beim Laden der Berechtigungen';
			return false;
		}
	}
	
	/**
	 * Laedt die Berechtigungen
	 *
	 * @return boolean
	 */
	public function getBerechtigungen()
	{
		$this->result=array();
		$qry = 'SELECT * FROM system.tbl_berechtigung ORDER BY berechtigung_kurzbz';
		
		if($this->db_query($qry))
		{
			while($row = $this->db_fetch_object())
			{
				$obj = new berechtigung();
				
				$obj->berechtigung_kurzbz = $row->berechtigung_kurzbz;
				$obj->beschreibung = $row->beschreibung;
				
				$this->result[] = $obj;
			}
			return true;
		}
		else 
		{
			$this->errormsg = 'Fehler beim Laden der Berechtigungen';
			return false;
		}
	}
	
	/**
	 * Loescht eine rolleberechtigung zuordnung
	 *
	 * @param $rolle_kurzbz
	 * @param $berechtigung_kurzbz
	 */
	public function deleteRolleBerechtigung($rolle_kurzbz, $berechtigung_kurzbz)
	{
		$qry = "DELETE FROM system.tbl_rolleberechtigung WHERE rolle_kurzbz=".$this->db_add_param($rolle_kurzbz)." AND berechtigung_kurzbz=".$this->db_add_param($berechtigung_kurzbz).";";
		
		if($this->db_query($qry))
		{
			return true;
		}
		else 
		{
			$this->errormsg = 'Fehler beim Löschen der Zuordnung:'.$this->db_last_error();
			return false;
		}
	}
	
	/**
	 * Loescht eine Rolle
	 *
	 * @param $rolle_kurzbz
	 */
	public function deleteRolle($rolle_kurzbz)
	{
		$qry = "DELETE FROM system.tbl_rolleberechtigung WHERE rolle_kurzbz=".$this->db_add_param($rolle_kurzbz).";
				DELETE FROM system.tbl_benutzerrolle WHERE rolle_kurzbz=".$this->db_add_param($rolle_kurzbz).";
				DELETE FROM system.tbl_rolle WHERE rolle_kurzbz=".$this->db_add_param($rolle_kurzbz).";";
		
		if($this->db_query($qry))
		{
			return true;
		}
		else 
		{
			$this->errormsg = 'Fehler beim Löschen der Zuordnung:'.$this->db_last_error();
			return false;
		}
	}
	
	/**
	 * Speichert eine RolleBerechtigung Zuordnung
	 *
	 */
	public function saveRolleBerechtigung()
	{
		$qry = "SELECT 1 FROM system.tbl_rolleberechtigung 
				WHERE rolle_kurzbz=".$this->db_add_param($this->rolle_kurzbz)."
				AND berechtigung_kurzbz=".$this->db_add_param($this->berechtigung_kurzbz);
		
		if($this->db_query($qry))
		{
			if($this->db_num_rows()>0)
			{
				//Update
				$qry = "UPDATE system.tbl_rolleberechtigung SET art=".$this->db_add_param($this->art)." WHERE rolle_kurzbz=".$this->db_add_param($this->rolle_kurzbz)." AND berechtigung_kurzbz=".$this->db_add_param($this->berechtigung_kurzbz).";";
			}
			else 
			{
				//Insert
				$qry = "INSERT INTO system.tbl_rolleberechtigung (rolle_kurzbz, berechtigung_kurzbz, art) VALUES(".
						$this->db_add_param($this->rolle_kurzbz).",".
						$this->db_add_param($this->berechtigung_kurzbz).",".
						$this->db_add_param($this->art).");";
			}
			
			if($this->db_query($qry))
			{
				return true;
			}
			else 
			{
				$this->errormsg = 'Fehler beim Speichern: '.$this->db_last_error();
				return false;
			}
		}
		else 
		{
			$this->errormsg = 'Fehler beim Speichern der Zuteilung:'.$this->db_last_error();
			return false;
		}
	}
	
	/**
	 * Speichert eine Rolle
	 *
	 */
	public function saveRolle($new=null)
	{
		if(is_null($new))
			$new = $this->new;
			
		if($new)
		{
			$qry = "INSERT INTO system.tbl_rolle(rolle_kurzbz, beschreibung) VALUES(".
					$this->db_add_param($this->rolle_kurzbz).','.
					$this->db_add_param($this->beschreibung).');';
		}
		else 
		{
			$qry = 'UPDATE system.tbl_rolle 
					SET beschreibung='.$this->db_add_param($this->beschreibung).'
					WHERE rolle_kurzbz='.$this->db_add_param($this->rolle_kurzbz).';';
		}
		
		if($this->db_query($qry))
		{
			return true;
		}
		else 
		{
			$this->errormsg = 'Fehler beim Speichern: '.$this->db_last_error();
			return false;
		}
	}
}
?>
