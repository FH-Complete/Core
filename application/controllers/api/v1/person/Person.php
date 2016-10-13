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

if (!defined("BASEPATH")) exit("No direct script access allowed");

class Person extends APIv1_Controller
{
	/**
	 * Person API constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		// Load model PersonModel
		$this->load->model("person/person_model", "PersonModel");
	}

	/**
	 * @return void
	 */
	public function getPerson()
	{
		$person_id = $this->get("person_id");
		$code = $this->get("code");
		$email = $this->get("email");
		
		if (isset($code) || isset($email) || isset($person_id))
		{
			if (isset($code) && isset($email))
			{
				$result = $this->PersonModel->getPersonKontaktByZugangscode($code, $email);
			}
			else
			{
				$parametersArray = array();
				
				if (isset($code))
				{
					$parametersArray["zugangscode"] = $code;
				}
				else
				{
					$parametersArray["person_id"] = $person_id;
				}
				
				$result = $this->PersonModel->loadWhere($parametersArray);
			}
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
	}
	
	/**
	 * @return void
	 */
	public function getCheckBewerbung()
	{
		$email = $this->get("email");
		$studiensemester_kurzbz = $this->get("studiensemester_kurzbz");
		
		if (isset($email))
		{
			$result = $this->PersonModel->checkBewerbung($email, $studiensemester_kurzbz);
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response();
		}
	}

	/**
	 * @return void
	 */
	public function postPerson()
	{
		$person = $this->_parseData($this->post());
		$validation = $this->_validate($this->post());
		
		if (is_object($validation) && $validation->error == EXIT_SUCCESS)
		{
			if(isset($person["person_id"]) && !(is_null($person["person_id"])) && ($person["person_id"] != ""))
			{
				$result = $this->PersonModel->updatePerson($person);
			}
			else
			{
				$result = $this->PersonModel->insert($person);
			}
			
			$this->response($result, REST_Controller::HTTP_OK);
		}
		else
		{
			$this->response($validation, REST_Controller::HTTP_OK);
		}
	}
	
	private function _validate($person = NULL)
	{
		if (!isset($person))
		{
			return error("Parameter is null");
		}
		
		if (isset($person["nachname"]))
			$person["nachname"] = trim($person["nachname"]);
		if (isset($person["vorname"]))
			$person["vorname"] = trim($person["vorname"]);
		if (isset($person["vornamen"]))
			$person["vornamen"] = trim($person["vornamen"]);
		if (isset($person["anrede"]))
			$person["anrede"] = trim($person["anrede"]);
		if (isset($person["titelpost"]))
			$person["titelpost"] = trim($person["titelpost"]);
		if (isset($person["titelpre"]))
			$person["titelpre"] = trim($person["titelpre"]);
		
		if (isset($person["sprache"]) && mb_strlen($person["sprache"]) > 16)
		{
			return error("Sprache darf nicht laenger als 16 Zeichen sein");
		}
		if (isset($person["anrede"]) && mb_strlen($person["anrede"]) > 16)
		{
			return error("Anrede darf nicht laenger als 16 Zeichen sein");
		}
		if (isset($person["titelpost"]) && mb_strlen($person["titelpost"]) > 32)
		{
			return error("Titelpost darf nicht laenger als 32 Zeichen sein");
		}
		if (isset($person["titelpre"]) && mb_strlen($person["titelpre"]) > 64)
		{
			return error("Titelpre darf nicht laenger als 64 Zeichen sein");
		}
		if (isset($person["nachname"]) && mb_strlen($person["nachname"]) > 64)
		{
			return error("Nachname darf nicht laenger als 64 Zeichen sein");
		}
		if (isset($person["nachname"]) && ($person["nachname"] == "" || is_null($person["nachname"])))
		{
			return error("Nachname muss eingegeben werden");
		}

		if (isset($person["vorname"]) && mb_strlen($person["vorname"]) > 32)
		{
			return error("Vorname darf nicht laenger als 32 Zeichen sein");
		}
		if (isset($person["vornamen"]) && mb_strlen($person["vornamen"]) > 128)
		{
			return error("Vornamen darf nicht laenger als 128 Zeichen sein");
		}
		if (isset($person["gebort"]) && mb_strlen($person["gebort"]) > 128)
		{
			return error("Geburtsort darf nicht laenger als 128 Zeichen sein");
		}
		if (isset($person["homepage"]) && mb_strlen($person["homepage"]) > 256)
		{
			return error("Homepage darf nicht laenger als 256 Zeichen sein");
		}
		if (isset($person["matr_nr"]) && mb_strlen($person["matr_nr"]) > 32)
		{
			return error("Matrikelnummer darf nicht laenger als 32 Zeichen sein");
			return false;
		}
		if (isset($person["svnr"]) && $person["svnr"] != "" && mb_strlen($person["svnr"]) != 16 &&
			mb_strlen($person["svnr"]) != 12 && mb_strlen($person["svnr"]) != 10)
		{
			return error("SVNR muss 10, 12 oder 16 Zeichen lang sein");
		}
		if (isset($person["svnr"]) && (mb_strlen($person["svnr"]) == 10 || mb_strlen($person["svnr"]) == 12))
		{
			//SVNR mit Pruefziffer pruefen
			//Die 4. Stelle in der SVNR ist die Pruefziffer
			//(Summe von (gewichtung[i]*svnr[i])) modulo 11 ergibt diese Pruefziffer
			//Falls nicht, ist die SVNR ungueltig
			$gewichtung = array(3, 7, 9, 0, 5, 8, 4, 2, 1, 6);
			$erg = 0;
			$tmpSvnr = substr($person["svnr"], 0, 10);
			//Quersumme bilden
			for ($i = 0; $i < 10; $i++)
			{
				$erg += $gewichtung[$i] * $tmpSvnr{$i};
			}

			if ($tmpSvnr{3} != ($erg % 11)) //Vergleichen der Pruefziffer mit Quersumme Modulo 11
			{
				return error("SVNR ist ungueltig");
			}
			
			if (mb_strlen($person["svnr"]) == 12)
			{
				$last = substr($person["svnr"], 10, 12);
				if ($last{0} != "v" || !is_numeric($last{1}))
				{
					return error("SVNR ist ungueltig");
				}
			}
		}
		if (isset($person["ersatzkennzeichen"]) && mb_strlen($person["ersatzkennzeichen"]) > 10)
		{
			return error("Ersatzkennzeichen darf nicht laenger als 10 Zeichen sein");
		}
		if (isset($person["familienstand"]) && mb_strlen($person["familienstand"]) > 1)
		{
			return error("Familienstand ist ungueltig");
		}
		if (isset($person["anzahlkinder"]) && $person["anzahlkinder"] != "" && !is_numeric($person["anzahlkinder"]))
		{
			return error("Anzahl der Kinder ist ungueltig");
		}
		if (!isset($person["aktiv"]) || (isset($person["aktiv"]) && $person["aktiv"] != "t" && $person["aktiv"] != "f"))
		{
			return error("Aktiv ist ungueltig");
		}
		if (!isset($person["person_id"]) && isset($person["insertvon"]) && mb_strlen($person["insertvon"]) > 32)
		{
			return error("Insertvon darf nicht laenger als 32 Zeichen sein");
		}
		if (isset($person["updatevon"]) && mb_strlen($person["updatevon"]) > 32)
		{
			return error("Updatevon darf nicht laenger als 32 Zeichen sein");
		}
		if (!isset($person["geschlecht"]) || (isset($person["geschlecht"]) && mb_strlen($person["geschlecht"]) > 1))
		{
			return error("Geschlecht darf nicht laenger als 1 Zeichen sein");
		}
		if (isset($person["geburtsnation"]) && mb_strlen($person["geburtsnation"]) > 3)
		{
			return error("Geburtsnation darf nicht laenger als 3 Zeichen sein");
		}
		if (isset($person["staatsbuergerschaft"]) && mb_strlen($person["staatsbuergerschaft"]) > 3)
		{
			return error("Staatsbuergerschaft darf nicht laenger als 3 Zeichen sein");
		}
		if (isset($person["geschlecht"]) && $person["geschlecht"] != "m" && $person["geschlecht"] != "w" && $person["geschlecht"] != "u")
		{
			return error("Geschlecht muss w, m oder u sein!");
		}

		//Pruefen ob das Geburtsdatum mit der SVNR uebereinstimmt.
		if (isset($person["svnr"]) && isset($person["gebdatum"]) && $person["svnr"] != "" && $person["gebdatum"] != "")
		{
			if (mb_ereg("([0-9]{1,2}).([0-9]{1,2}).([0-9]{4})", $person["gebdatum"], $regs))
			{
				//$day = sprintf("%02s",$regs[1]);
				//$month = sprintf("%02s",$regs[2]);
				//$year = mb_substr($regs[3],2,2);
			}
			elseif (mb_ereg("([0-9]{4})-([0-9]{2})-([0-9]{2})", $person["gebdatum"], $regs))
			{
				//$day = sprintf("%02s",$regs[3]);
				//$month = sprintf("%02s",$regs[2]);
				//$year = mb_substr($regs[1],2,2);
			}
			else
			{
				return error("Format des Geburtsdatums ist ungueltig");
			}
		}

		return success("Input data are valid");
	}
}