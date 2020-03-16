<?php
/* Copyright (C) 2020 fhcomplete.org
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
 * Authors: Cristina Hainberger < cristina.hainberger@technikum-wien.at >
 */
require_once('../../config/vilesci.config.inc.php');
require_once('../../include/studiensemester.class.php');
require_once('../../include/datum.class.php');
require_once('../../include/benutzerberechtigung.class.php');
require_once('../../include/functions.inc.php');
require_once('../../include/bisverwendung.class.php');
require_once('../../include/mitarbeiter.class.php');
require_once('../../include/studiengang.class.php');
require_once('../../include/lehreinheitmitarbeiter.class.php');
require_once('../../include/benutzerfunktion.class.php');
require_once('../../include/organisationseinheit.class.php');
require_once('../../include/entwicklungsteam.class.php');
require_once('../../include/erhalter.class.php');

$uid = get_uid();

$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($uid);

if(!$rechte->isBerechtigt('mitarbeiter/stammdaten',null,'suid'))
	die('Sie haben keine Berechtigung für diese Seite');

if (!$db = new basis_db())
	die('Es konnte keine Verbindung zum Server aufgebaut werden.');

if (!defined('BIS_VOLLZEIT_ARBEITSSTUNDEN') || empty('BIS_VOLLZEIT_ARBEITSSTUNDEN'))
{
	die('config var BIS_VOLLZEIT_ARBEITSSTUNDEN fehlt');
}

if (!defined('BIS_VOLLZEIT_SWS_EINZELSTUNDENBASIS') || empty('BIS_VOLLZEIT_SWS_EINZELSTUNDENBASIS'))
{
	die('config var BIS_VOLLZEIT_SWS_EINZELSTUNDENBASIS fehlt');
}

if (!defined('BIS_VOLLZEIT_SWS_INKLUDIERTE_LEHRE') || empty('BIS_VOLLZEIT_SWS_INKLUDIERTE_LEHRE'))
{
	die('config var BIS_VOLLZEIT_SWS_INKLUDIERTE_LEHRE fehlt');
}

if (!defined('BIS_EXCLUDE_STG') || empty('BIS_EXCLUDE_STG'))
{
	die('config var BIS_EXCLUDE_STG fehlt');
}

if (!defined('BIS_HALBJAHRES_GEWICHTUNG_SWS') || empty('BIS_HALBJAHRES_GEWICHTUNG_SWS'))
{
	die('config var BIS_HALBJAHRES_GEWICHTUNG_SWS fehlt');
}

if (!defined('BIS_PAUSCHALE_STUDENTISCHE_HILFSKRAFT') || empty('BIS_PAUSCHALE_STUDENTISCHE_HILFSKRAFT'))
{
	die('config var BIS_PAUSCHALE_STUDENTISCHE_HILFSKRAFT fehlt');
}

if (!defined('BIS_PAUSCHALE_SONSTIGES_DIENSTVERHAELTNIS') || empty('BIS_PAUSCHALE_SONSTIGES_DIENSTVERHAELTNIS'))
{
	die('config var BIS_PAUSCHALE_SONSTIGES_DIENSTVERHAELTNIS fehlt');
}

if (!defined('BIS_FUNKTIONSCODE_1234_ARR') || empty('BIS_FUNKTIONSCODE_1234_ARR'))
{
	die('config var BIS_FUNKTIONSCODE_1234_ARR fehlt');
}

if (!defined('BIS_FUNKTIONSCODE_5_ARR') || empty('BIS_FUNKTIONSCODE_5_ARR'))
{
	die('config var BIS_FUNKTIONSCODE_5_ARR fehlt');
}

if (!defined('BIS_FUNKTIONSCODE_6_ARR') || empty('BIS_FUNKTIONSCODE_6_ARR'))
{
	die('config var BIS_FUNKTIONSCODE_6_ARR fehlt');
}


// Prüfe Zeitraum zur Erstellung einer BIS-Meldung
$studiensemester = new studiensemester();
$stsem = (isset($_GET['stsem'])) ? $_GET['stsem'] : $studiensemester->getaktorNext();	// aktuelles Studiensemester

$datum_obj = new datum();
if(mb_strstr($stsem,'SS'))
{
	$studiensemester->load($stsem);
	$jahr = $datum_obj->formatDatum($studiensemester->start, 'Y');
	$stichtag = date("Y-m-d",  mktime(0, 0, 0, 12, 31, $jahr - 1)); // 31.12. des vorangehenden Kalenderjahres zur BIS Meldung TODO: oder Abfragetag mitgeben?
}
else
{
	echo "Fehler: Studiensemester muss ein Sommersemester sein";
	exit;
}

$beginn_imJahr = new DateTime(($jahr - 1). '-01-01');
$ende_imJahr = new DateTime(($jahr - 1). '-12-31');

$tage_imJahr = $ende_imJahr->diff($beginn_imJahr)->days + 1;
$wochen_imJahr = $tage_imJahr / 7;

// Sommer- und Wintersemester im BIS Meldungsjahr
$ss_kurzbz = $studiensemester->getBeforePrevious();
$ws_kurzbz = $studiensemester->getStudienjahrStudiensemester($stsem);
$ss = new studiensemester($ss_kurzbz);
$ws = new studiensemester($ws_kurzbz);

// Alle gemeldeten Mitarbeiter holen
$mitarbeiter = new mitarbeiter();
$mitarbeiter->getMitarbeiterBISMeldung($beginn_imJahr->format('Y-m-d'));
$mitarbeiter_arr = $mitarbeiter->result;

// *********************************************************************************************************************
// Container Person generieren
// *********************************************************************************************************************
$person_arr = array();

foreach ($mitarbeiter_arr as $mitarbeiter)
{
	$person_obj = new StdClass();

	$person_obj->personalnummer = setLeadingZero(intval($mitarbeiter->personalnummer), 15);
	$person_obj->uid = $mitarbeiter->uid;
	$person_obj->vorname = $mitarbeiter->vorname;
	$person_obj->nachname = $mitarbeiter->nachname;
	$person_obj->geschlecht = $mitarbeiter->geschlecht;
	$person_obj->geschlechtX = $mitarbeiter->geschlechtX;
	$person_obj->geburtsjahr = $datum_obj->formatDatum($mitarbeiter->gebdatum, 'Y');
	$person_obj->staatsangehoerigkeit = $mitarbeiter->staatsbuergerschaft;
	$person_obj->hoechste_abgeschlossene_ausbildung = $mitarbeiter->ausbildungcode;

	// Habilitation
	$bisverwendung = new bisverwendung();
	$person_obj->habilitation = $bisverwendung->isHabilitiert($mitarbeiter->uid) ? 'j' : 'n';

	// Alle im FAS gemeldeten BIS-Verwendungen holen
	$bisverwendung = new bisverwendung();
	$bisverwendung->getVerwendungenBISMeldung($mitarbeiter->uid, $stichtag);
	$bisverwendung_arr = $bisverwendung->result;	// alle im FAS gemeldeten BIS Verwendungen

	// BIS Verwendungsdauer und -gewichtung ergaenzen
	$bisverwendung_arr = _addDauerGewichtung_imBISMeldungsjahr($bisverwendung_arr);

	// Hauptberufcode
	// -----------------------------------------------------------------------------------------------------------------
	$is_hauptberuflich = $bisverwendung_arr[count($bisverwendung_arr) - 1]->hauptberuflich;

	// wenn Hauptberuf / Nebenberuf im gleichen Jahr - laengere Dauer melden (Ueberwiegenheitprinzip)
	if (in_array(true, array_column($bisverwendung_arr, 'hauptberuflich')) &&	// hauptberuflich UND
		in_array(false, array_column($bisverwendung_arr, 'hauptberuflich')))		// nebenberuflich
	{
		$is_hauptberuflich = _getUeberwiegendeTaetigkeit_HauptNebenberuf($bisverwendung_arr);
	}

	/**
	 * Hauptberufcode
	 * - hauptberuflich Lehrender: NULL,
	 * - nebenberuflich Lehrender: Hauptberufscode der letzten BIS-Verwendung
	 */
	$person_obj->hauptberufcode = ($is_hauptberuflich == true)
		? NULL
		: $bisverwendung_arr[count($bisverwendung_arr) - 1]->hauptberufcode;


	// *****************************************************************************************************************
	// Container Verwendung generieren (mit JVZAE und VZAE)
	// *****************************************************************************************************************
	$verwendung_arr = array();

	// Relatives Beschaeftigungsausmass / Anteilige JVZAE ermitteln
	// -----------------------------------------------------------------------------------------------------------------
	$bisverwendung_arr = _add_relativesBA_und_anteiligeJVZAE($person_obj->uid, $bisverwendung_arr);

	// JVZAE und VZAE ermitteln und Container Verwendung generieren
	// -----------------------------------------------------------------------------------------------------------------
	foreach ($bisverwendung_arr as $bisverwendung)
	{
		if (empty($verwendung_arr) || 																					// wenn erster Durchlauf ODER
			(!(in_array($bisverwendung->ba1code, array_column($verwendung_arr, 'ba1code')) &&               	// im verwendung_arr Beschaeftigungsart1 UND
				in_array($bisverwendung->ba2code, array_column($verwendung_arr, 'ba2code')) &&					// Beschaeftigungsart2 UND
				in_array($bisverwendung->verwendung_code, array_column($verwendung_arr, 'verwendung_code')))))  // Verwendung_code noch NICHT vorhanden
		{

			// Temporaeren array mit Verwendungen mit gleichem Beschaeftigungsverhaeltnis und gleichem Verwendungscode erstellen
			$verwendung_tmp_arr = array_filter($bisverwendung_arr, function ($obj) use ($bisverwendung) {
				return
					$obj->ba1code == $bisverwendung->ba1code &&
					$obj->ba2code == $bisverwendung->ba2code &&
					$obj->verwendung_code == $bisverwendung->verwendung_code;
			});

			// Neue Verwendung fuer Container Verwendung erstellen
			$verwendung_obj = new StdClass();
			$verwendung_obj->ba1code = $bisverwendung->ba1code;
			$verwendung_obj->ba2code = $bisverwendung->ba2code;
			$verwendung_obj->verwendung_code = $bisverwendung->verwendung_code;
			$verwendung_obj->jvzae = 0.00;
			$verwendung_obj->vzae = -1;	// default

			// Loop innerhalb Verwendungen mit selben Beschaeftigungsverhaeltnissen und Verwendung_codes
			foreach ($verwendung_tmp_arr as $verwendung_tmp)
			{

			//	Jahresvollzeitaequivalenz JVZAE ermitteln
			// ---------------------------------------------------------------------------------------------------------
				/**
				 * Berechnung:
				 * JVZAE wird aus der Summe aller anteiligen JVZE gebildet.
				 */
				$verwendung_obj->jvzae += (isset($verwendung_tmp->jvzae_anteilig))
					? number_format($verwendung_tmp->jvzae_anteilig * 100, 2)
					: NULL; // TODO: not null...


			//	Vollzeitaequivalenz VZAE ermitteln (Beschaeftigungsausmass zum Stichtag 31.12)
			// ---------------------------------------------------------------------------------------------------------
				/**
				 * Berechnung:
				 * - Wenn Karenz zum Stichtag 31.12. vorhanden: VZAE = 0.00
				 * - Wenn Beschaeftigung zum Stichtag 31.12. vorhanden: VZAE = Beschaeftigungsausmass relativ zu VZ
				 * - Wenn keine Beschaeftigung zum Stichtag 31.12 vorhanden: VZAE = -1;
				 */
				$ende_imBISMeldungsJahr = new DateTime($verwendung_tmp->ende_imBISMeldungsJahr);
				$is_karenziert_VZ = $verwendung_tmp->beschausmasscode == 5 && $verwendung_tmp->jvzae_anteilig == 0;
				if ($ende_imBISMeldungsJahr == $ende_imJahr)
				{
					if ($is_karenziert_VZ)
					{
						$verwendung_obj->vzae = number_format(0.00, 2);
						break;
					}
					else
					{
						$verwendung_obj->vzae = (isset($verwendung_tmp->beschaeftigungsausmass_relativ)) ? $verwendung_tmp->beschaeftigungsausmass_relativ * 100 : NULL;	// TODO: not null...
					}
				}
			}

			// Neue Verwendung im finalen Verwendungcontainer speichern
			$verwendung_arr [] = $verwendung_obj;
		}
	}

	// Container Verwendung dem Container Person anhaengen
	// -----------------------------------------------------------------------------------------------------------------
	$person_obj->verwendung_arr = $verwendung_arr;


	// *****************************************************************************************************************
	// Container Funktion generieren (nicht für Lehrgaenge)
	// *****************************************************************************************************************
	$funktion_arr = array();

	// Alle Benutzerfunktionen im BIS Meldungsjahr holen
	$benutzerfunktion = new Benutzerfunktion();
	$benutzerfunktion->getBenutzerFunktionByUid(
		$person_obj->uid, null,
		$beginn_imJahr->format('Y-m-d'),
		$ende_imJahr->format('Y-m-d')
	);
	$bisfunktion_arr = $benutzerfunktion->result;

	/**
	 * Funktionscode 1 - 6 anhand Benutzerfunktionen ermitteln
	 * Exkludiert Funktionen, die einem Lehrgang bzw. STG, die nicht BIS-gemeldet werden, zugeordnet sind.
	 */
	// -------------------------------------------------------------------------------------------------------------
	$funktion_arr = _getFunktionscontainer_Funktionscode123456($bisfunktion_arr);

	/**
	 * Funktionscode 7 aus Entwicklungsteam-Tabelle ermitteln
	 * Exkludiert Funktionen, die einem Lehrgang bzw. STG, die nicht BIS-gemeldet werden, zugeordnet sind.
	 */
	// -------------------------------------------------------------------------------------------------------------
	$funktion_arr = _addFunktionscontainer_Funktionscode7($person_obj->uid, $funktion_arr);

	// Container Funktion dem Container Person anhaengen
	// -----------------------------------------------------------------------------------------------------------------
	$person_obj->funktion_arr = $funktion_arr;


	// *****************************************************************************************************************
	// Container Lehre generieren (nicht für Lehrgaenge)
	// *****************************************************************************************************************
	$lehre_arr = array();

	// Alle Semesterwochenstunden, summiert nach STG und Studiensemester
	$lehreinheitmitarbeiter = new Lehreinheitmitarbeiter();
	$lehreinheitmitarbeiter->get_SWS_groupByStg(
		$person_obj->uid,
		$beginn_imJahr->format('Y-m-d'),
		$ende_imJahr->format('Y-m-d')
	);
	$sws_proStg_arr = $lehreinheitmitarbeiter->result;

	/**
	 * Lehrtaetigkeit (Semesterwochenstunden) pro STG ermitteln.
	 * Exkludiert Lehrtaetigkeit an Lehrgaengen bzw. Lehrtaetigkeit an STG, die nicht BIS-gemeldet werden.
	 */
	$lehre_arr = _getLehrecontainer($sws_proStg_arr);

	// Container Lehre dem Container Person anhaengen
	// -----------------------------------------------------------------------------------------------------------------
	$person_obj->lehre_arr = $lehre_arr;


	// Container Person dem Gesamt-Container anhaengen
	// -----------------------------------------------------------------------------------------------------------------
	$person_arr []= $person_obj;
}

// *********************************************************************************************************************
// XML generieren
// *********************************************************************************************************************
$xml = '';

_outputHTML($person_arr);
$xml = _generateXML($person_arr);

$xml_datei = 'bisdaten/bismeldung_mitarbeiter.xml';
$dateiausgabe = fopen($xml_datei,'w');
fwrite($dateiausgabe, $xml);
fclose($dateiausgabe);

echo '<a href="bisdaten/bismeldung_mitarbeiter.xml" download="Personalmeldung">XML Herunterladen</a>';

// ---------------------------------------------------------------------------------------------------------------------
// Private Functions
// ---------------------------------------------------------------------------------------------------------------------

/**
 * Funktion ermittelt relatives Beschaeftigungsausmass und anteilige Jahresvollzeitaequivalenz
 * @param String $uid
 * @param array $bisverwendung_arr Object-Array
 * @return array
 */
function _add_relativesBA_und_anteiligeJVZAE($uid, $bisverwendung_arr)
{
	global $beginn_imJahr;
	global $ende_imJahr;
	global $wochen_imJahr;
	global $tage_imJahr;
	global $ss_kurzbz;
	global $ws_kurzbz;

	// Lehrtaetigkeit ermitteln
	$lema = new lehreinheitmitarbeiter();
	$lema->getLehreinheiten_SWS_BISMeldung($uid, $ss_kurzbz);
	$lehre_ss_sws = $lema->result[0];	// Anzahl SS - Semesterwochenstunden

	$lema = new lehreinheitmitarbeiter();
	$lema->getLehreinheiten_SWS_BISMeldung($uid, $ws_kurzbz);
	$lehre_ws_sws = $lema->result[0];	// Anzahl WS - Semesterwochenstunden

	$has_lehrtaetigkeit = !is_null($lehre_ss_sws) || !is_null($lehre_ws_sws);

	foreach ($bisverwendung_arr as $index => $bisverwendung)
	{
		$has_vertragsstunden = !is_null($bisverwendung->vertragsstunden) && !empty($bisverwendung->vertragsstunden);
		$is_lektor = $bisverwendung->verwendung_code == 1 || $bisverwendung->verwendung_code == 2;

		/**
		 * NOTE: is_karenziert ist ein boolean fuer Vollzeit-Karenz, nicht fuer Teilzeit-(Bildungs-)Karenz!
		 * Die Unterscheidung ist wichtig fuer die weitere Ermittlung der JVZAE.
		 * Vollzeitkarenz: Anteiliger Beschaeftigungsausmass und JVZAE wird auf 0 gesetzt.
		 * Bildungs-Teilzeitkarenz:  entspricht im System
		 */
		$is_karenziert_VZ = $bisverwendung->beschausmasscode == 5 && !$has_vertragsstunden;	// VZ-Kinder- und Bildungskarenz
		$is_karenziert_TZ = $bisverwendung->beschausmasscode == 5 && $has_vertragsstunden;	// TZ-Bildungskarenz

		// Karenzzeit
		// -------------------------------------------------------------------------------------------------------------
		if ($is_karenziert_VZ)
		{
			// Relatives Beschaeftigungsausmass / Anteilige JVZAE ermitteln
			$bisverwendung->beschaeftigungsausmass_relativ = number_format(0.00, 2);
			$bisverwendung->jvzae_anteilig = 0;
			continue;
		}

		// Echter Dienstvertrag - d.h. Vertragsstunden sind vorhanden
		// Bsp. angestellte Lektoren, angestellte MA in Verwaltung/Management/Wartung
		// -------------------------------------------------------------------------------------------------------------
		else if ($has_vertragsstunden)
		{
			// Vertragsstunden koennen max. VZ Aequivalenz-Basiswert haben
			if ($bisverwendung->vertragsstunden > BIS_VOLLZEIT_ARBEITSSTUNDEN)
			{
				$bisverwendung->vertragsstunden = BIS_VOLLZEIT_ARBEITSSTUNDEN;
			}

			// Relatives Beschaeftigungsausmass / Anteilige JVZAE ermitteln
			$bisverwendung->beschaeftigungsausmass_relativ = round($bisverwendung->vertragsstunden / BIS_VOLLZEIT_ARBEITSSTUNDEN, 2);
			$bisverwendung->jvzae_anteilig = round($bisverwendung->beschaeftigungsausmass_relativ * $bisverwendung->gewichtung, 2);

			// Echter Dienstvertrag - mit Lehrtaetigkeit, jedoch kein Lektor.
			// Bsp. STG-Leiter mit Lehrtaetigkeit
			// ---------------------------------------------------------------------------------------------------------
			if (!$is_lektor && $has_lehrtaetigkeit)
			{
				/**
				 * Verwendungen ergänzen, wenn Mitarbeiter in Verwaltung/Managment/Wartung (jedenfalls nicht in Lehre)
				 * zugeteilt ist und dennoch lehrt.
				 * Die SWS werden sowohl fuer Sommer- als auch Wintersemster ermittelt und jeweils in einer eigenen
				 * Verwendung mit dem Verwendungscode 1 ergaenzt.
				 */
				$bisverwendung_beginn_BIS = new DateTime($bisverwendung->beginn_imBISMeldungsJahr);
				$bisverwendung_ende_BIS = new DateTime($bisverwendung->ende_imBISMeldungsJahr);

				foreach (array($ss_kurzbz => $lehre_ss_sws, $ws_kurzbz => $lehre_ws_sws) as $studsem_kurzbz => $lehre_sws)
				{
					$studsem = new studiensemester($studsem_kurzbz);
					$studsem_start = new DateTime($studsem->start);
					$studsem_ende = new DateTime($studsem->ende);

					// Wenn Lehrzeit in die BIS Verwendungszeit hineinfaellt, Verwendung erstellen
					if (!is_null($lehre_sws) &&
						(!($studsem_start > $bisverwendung_ende_BIS) &&
							!($studsem_ende < $bisverwendung_beginn_BIS)))
					{
						// Verwendung erstellen
						list($tage_lehre_imSemester, $verwendung_lehre_obj) = _addVerwendung_fuerLehre_inkludiert($studsem, $bisverwendung);

						// Relatives Beschaeftigungsausmass / Anteilige JVZAE ermitteln
						$verwendung_lehre_obj->beschaeftigungsausmass_relativ = round($lehre_sws / BIS_VOLLZEIT_SWS_INKLUDIERTE_LEHRE, 2);	// VZ-Basis fuer inkludierte Lehre
						$verwendung_lehre_obj->gewichtung = ($tage_lehre_imSemester == 182)
							? BIS_HALBJAHRES_GEWICHTUNG_SWS
							: round(BIS_HALBJAHRES_GEWICHTUNG_SWS / ($tage_imJahr / 2) * $tage_lehre_imSemester, 2);
						$verwendung_lehre_obj->jvzae_anteilig = round($verwendung_lehre_obj->beschaeftigungsausmass_relativ * $verwendung_lehre_obj->gewichtung, 3);

						/**
						 * Relativen Beschaeftigungsausmass der BIS-Verwendung berichtigen
						 * (durch Abzug des eben erstellten relativen Beschaeftigungsausmass fuer Lehrtaetigkeiten)
						 * NOTE: Abzug nur fuer Lehrtaetigkeiten im WS, da nur diese das Beschaeftigungsausmass der
						 * BIS-Verwendung (und in Folge die VZAE ) zum Stichtag 31.12. bestimmen.
						 * */
						if(substr($studsem_kurzbz, 0, 2) == 'WS')
						{
							$bisverwendung->beschaeftigungsausmass_relativ -= $verwendung_lehre_obj->beschaeftigungsausmass_relativ;
						}

						/**
						 * Anteilige JVZAE der BIS-Verwendung berichtigen
						 * (durch Abzug der eben erstellten anteiligen JVZAE fuer Lehrtaetigkeiten)
						 */
						$bisverwendung->jvzae_anteilig -= $verwendung_lehre_obj->jvzae_anteilig;
						$bisverwendung_arr [] = $verwendung_lehre_obj;
					}
				}
			}
		}

		// Sonstige Beschaeftigungsverhaeltnisse ohne Vertragsstunden
		// Freie Dienstvertraege auf Stundenbasis
		// -------------------------------------------------------------------------------------------------------------
		else if (!$has_vertragsstunden &&  $has_lehrtaetigkeit)
		{
			foreach (array($ss_kurzbz => $lehre_ss_sws, $ws_kurzbz => $lehre_ws_sws) as $studsem => $lehre_sws)
			{
				if (!is_null($lehre_sws))
				{
					// Verwendungen erstellen
					$verwendung_lehre_obj = _addVerwendung_fuerLehre_Stundenbasis($bisverwendung);

					// Relatives Beschaeftigungsausmass / Anteilige JVZAE ermitteln
					$verwendung_lehre_obj->beschaeftigungsausmass_relativ = round($lehre_sws / BIS_VOLLZEIT_SWS_EINZELSTUNDENBASIS, 2);	// VZ-Basis nach BIS-Vorgabe fuer Stundenbasis
					$verwendung_lehre_obj->gewichtung = BIS_HALBJAHRES_GEWICHTUNG_SWS;
					$verwendung_lehre_obj->jvzae_anteilig = round($verwendung_lehre_obj->beschaeftigungsausmass_relativ * $verwendung_lehre_obj->gewichtung, 2);
					$bisverwendung_arr []= $verwendung_lehre_obj;
				}
			}
		}
		else
		{
			// Studentische Hilfskraft
			// ---------------------------------------------------------------------------------------------------------
			$benutzerfunktion = new Benutzerfunktion();
			$is_studentische_hilfskraft = $benutzerfunktion->getBenutzerFunktionByUid(
				$uid,
				'hilfskraft',
				$beginn_imJahr->format('Y-m-d'),
				$ende_imJahr->format('Y-m-d')
			);

			if ($is_studentische_hilfskraft)
			{
				// Kalkulatorische Umrechnung der Jahrespauschale
				$pauschale_hilfskraft_inStunden = BIS_PAUSCHALE_STUDENTISCHE_HILFSKRAFT; // Pauschale pro Jahr und Person (in Stunden)
				$pauschale_hilfskraft_relativImJahr = $pauschale_hilfskraft_inStunden / 1; // Stundenpauschale in Verhaeltnis zu 1 Jahr
				$vollzeit_arbeitsstunden_imJahr = BIS_VOLLZEIT_ARBEITSSTUNDEN * $wochen_imJahr;

				// Relatives Beschaeftigungsausmass / Anteilige JVZAE ermitteln
				$bisverwendung->beschaeftigungsausmass_relativ = round($pauschale_hilfskraft_relativImJahr / $vollzeit_arbeitsstunden_imJahr, 4);
				$bisverwendung->jvzae_anteilig =round($pauschale_hilfskraft_relativImJahr / $vollzeit_arbeitsstunden_imJahr, 4);
			}
			// Mitarbeiter mit sonstigem Dienstverhaeltnis (zB. Werkvertrag)
			// ---------------------------------------------------------------------------------------------------------
			else
			{
				$pauschale_sonstigeDV_inStunden = BIS_PAUSCHALE_SONSTIGES_DIENSTVERHAELTNIS; // Pauschale pro Jahr und Person (in Stunden)
				$pauschale_sonstigeDV_relativImJahr = $pauschale_sonstigeDV_inStunden / 1; // Stundenpauschale in Verhaeltnis zu 1 Jahr
				$vollzeit_arbeitsstunden_imJahr = BIS_VOLLZEIT_ARBEITSSTUNDEN * $wochen_imJahr;

				// Relatives Beschaeftigungsausmass / Anteilige JVZAE ermitteln
				$bisverwendung->beschaeftigungsausmass_relativ = round($pauschale_sonstigeDV_relativImJahr / $vollzeit_arbeitsstunden_imJahr, 4);
				$bisverwendung->jvzae_anteilig =round($pauschale_sonstigeDV_relativImJahr / $vollzeit_arbeitsstunden_imJahr, 4);
			}
		}
	}

	return $bisverwendung_arr;
}

/**
 * Funktion ermittelt fuer jede BIS-Verwendung die Dauer (in Tagen) und Gewichtung (Dauer / Tage im Jahr)
 * @param array $bisverwendung_arr Array mit BIS-Verwendungsobjekten
 * @return array
 */
function _addDauerGewichtung_imBISMeldungsjahr($bisverwendung_arr)
{
	global $tage_imJahr;

	foreach ($bisverwendung_arr as &$bisverwendung)
	{
		$bisverwendung_beginn_imBISMeldungsJahr = new DateTime($bisverwendung->beginn_imBISMeldungsJahr);
		$bisverwendung_ende_imBISMeldungsJahr = new DateTime($bisverwendung->ende_imBISMeldungsJahr);

		$bisverwendung->dauer_imBISMeldungsJahr = $bisverwendung_ende_imBISMeldungsJahr->diff($bisverwendung_beginn_imBISMeldungsJahr)->days + 1;
		$bisverwendung->gewichtung = round($bisverwendung->dauer_imBISMeldungsJahr / $tage_imJahr, 2);
	}

	return $bisverwendung_arr;
}

/**
 * Funktion ermittelt, ob Person im BIS Meldungsjahr vorwiegend haupt- oder nebenberuflich taetig war.
 * @param $bisverwendung_arr Array mit BIS-Verwendungsobjekten
 * @return boolean	True wenn vorwiegend hauptberuflich
 */
function _getUeberwiegendeTaetigkeit_HauptNebenberuf($bisverwendung_arr)
{
	// Zeiten vergleichen
	$sum_dauer_hauptberuflich = 0;
	$sum_dauer_nebenberuflich = 0;

	foreach ($bisverwendung_arr as $bisverwendung)
	{
		if ($bisverwendung->hauptberuflich == true)
		{
			$sum_dauer_hauptberuflich += $bisverwendung->dauer_imBISMeldungsJahr;
		}
		else
		{
			$sum_dauer_nebenberuflich += $bisverwendung->dauer_imBISMeldungsJahr;
		}
	}

	// Laengere Dauer bestimmt Haupt- oder Nebenberuf
	$is_hauptberuflich = $sum_dauer_hauptberuflich > $sum_dauer_nebenberuflich;

	return array($is_hauptberuflich);
}

/**
 * Funktion erstellt Verwendung fuer Lehrtaetigkeiten fuer Personen mit echtem Dienstvertrag.
 * (zB STG Leiter mit Lehrtaetigkeit)
 * @param object $studiensemester	Sommer- / Winterstudiensemester
 * @param object $bisverwendung
 * @return array
 */
function _addVerwendung_fuerLehre_inkludiert($studiensemester, $bisverwendung)
{
	$verwendung_lehre_obj = new StdClass();

	$verwendung_lehre_obj->ba1code = $bisverwendung->ba1code;
	$verwendung_lehre_obj->ba2code = $bisverwendung->ba2code;
	$verwendung_lehre_obj->beschausmasscode = $bisverwendung->beschausmasscode;
	$verwendung_lehre_obj->verwendung_code = 1;
	$verwendung_lehre_obj->beginn_imBISMeldungsJahr = $bisverwendung->beginn_imBISMeldungsJahr;
	$verwendung_lehre_obj->ende_imBISMeldungsJahr = $bisverwendung->ende_imBISMeldungsJahr;

	/**
	 * Anteilige Lehrtage im Studiensemester ermitteln
	 * NOTE: Da die gesamte Lehrtaetigkeit fuer SS und WS im BIS Meldungsjahr gemeldet wird, muss die WS Lehrtaetigkeit
	 * jahresuebergreifend erfasst werden. Daher Datumsvergleiche mit dem tatsaechlichen BIS-Verwendungsende.
	 * */
	$tage_lehre_imSemester = 182;	// default Tage im Halbjahr entsprechend der default Gewichtung von 0.5
	$studsem_start = new DateTime($studiensemester->start);
	$studsem_ende = new DateTime($studiensemester->ende);
	$bisverwendung_beginn_BIS = new DateTime($bisverwendung->beginn_imBISMeldungsJahr);
	$bisverwendung_ende = new DateTime($bisverwendung->ende);

	$beginn_im_semester = ($bisverwendung_beginn_BIS < $studsem_start) ? $studsem_start : $bisverwendung_beginn_BIS;
	$ende_im_semester = (!is_null($bisverwendung_ende) && $bisverwendung_ende > $studsem_ende) ? $studsem_ende : $bisverwendung_ende;
	$tage_lehre_imSemester = $beginn_im_semester->diff($ende_im_semester)->days + 1;

	return array($tage_lehre_imSemester, $verwendung_lehre_obj);
}

/**
 * Funktion erstellt Verwendung fuer Lehrtaetigkeiten fuer freie Lektoren.
 * @param object $bisverwendung
 * @return object Verwendung fuer Lehrtaetigkeit
 */
function _addVerwendung_fuerLehre_Stundenbasis($bisverwendung)
{
	$verwendung_lehre_obj = new StdClass();

	$verwendung_lehre_obj->ba1code = $bisverwendung->ba1code;
	$verwendung_lehre_obj->ba2code = $bisverwendung->ba2code;
	$verwendung_lehre_obj->beschausmasscode = $bisverwendung->beschausmasscode;
	$verwendung_lehre_obj->verwendung_code = 1;
	$verwendung_lehre_obj->beginn_imBISMeldungsJahr =$bisverwendung->beginn_imBISMeldungsJahr;
	$verwendung_lehre_obj->ende_imBISMeldungsJahr = $bisverwendung->ende_imBISMeldungsJahr;

	return $verwendung_lehre_obj;
}

/**
 * Funktionscode 1 - 6 anhand Benutzerfunktionen ermitteln
 * @param array $bisfunktion_arr
 * @return array
 */
function  _getFunktionscontainer_Funktionscode123456($bisfunktion_arr)
{
	$funktion_arr = array();

	foreach ($bisfunktion_arr as $bisfunktion)
	{
		$funktion_code = NULL;
		$has_oe_lehrgang = false;	// default

		$studiengang = new Studiengang();
		$studiengang->getStudiengangFromOe($bisfunktion->oe_kurzbz);

		// Wenn OE der Funktion eine STG-Kennzahl ist
		if (!is_null($studiengang->studiengang_kz))
		{
			// Pruefen ob STG-Kennzahl STG oder Lehrgang
			$has_oe_lehrgang = !($studiengang->studiengang_kz > 0 && $studiengang->studiengang_kz < 10000);

			// STG, die nicht BIS-bemeldet werden, ueberspringen
			if (in_array($studiengang->studiengang_kz, BIS_EXCLUDE_STG))
			{
				continue;
			}
		}

		// Funktionscode 1 - 6 anhand Benutzerfunktionen ermitteln
		// -------------------------------------------------------------------------------------------------------------
		// Wenn OE der Funktion nicht einem Lehrgang zugeordnet ist
		if (!$has_oe_lehrgang)
		{
			// FunktionsCode 1-4
			if (array_key_exists($bisfunktion->funktion_kurzbz, BIS_FUNKTIONSCODE_1234_ARR))
			{
				$funktion_code = BIS_FUNKTIONSCODE_1234_ARR[$bisfunktion->funktion_kurzbz];
			}

			if (in_array($bisfunktion->funktion_kurzbz, BIS_FUNKTIONSCODE_5_ARR))	// Leitung
			{
				// FunktionsCode 5 : STG-Leitung
				if (!is_null($studiengang->studiengang_kz))
				{
					$funktion_code = 5;
				}

				// FunktionsCode 6 : Leitung Organisationseinheit der postsekundaeren Bildungseinrichtung
				$organisationseinheit = new Organisationseinheit($bisfunktion->oe_kurzbz);
				if (is_null($studiengang->studiengang_kz) &&
					!in_array($organisationseinheit->oetyp_bezeichnung, BIS_FUNKTIONSCODE_6_ARR)) // nicht Teamleitung
				{
					$funktion_code = 6;
				}
			}
		}

		// Funktionsobjekt generieren
		if (!is_null($funktion_code) &&		// Funktionscode vorhanden UND
			(empty($funktion_arr) ||		// (Erster Durchlauf ODER
				!in_array($funktion_code, array_column($funktion_arr, 'funktionscode'))))	// Funktionsobjekt mit diesem Funktionscode nicht vorhanden)
		{
			$funktion_obj = new StdClass();
			$funktion_obj->funktionscode = $funktion_code;
			$funktion_obj->besondereQualifikationCode = NULL;
			$funktion_obj->studiengang = ($funktion_code == 5)
				? array(setLeadingZero(intval($studiengang->studiengang_kz), 4))		// STG bei Funktionscode 5 melden
				: NULL;

			// Funktionsobjekt dem Funktionscontainer anhaengen
			$funktion_arr []= $funktion_obj;
		}
		else if ($funktion_code == 5)		// Funktionscontainer vorhanden und Funktionscode 5
		{
			$funktion_obj_arr = array_filter($funktion_arr, function (&$obj) {
				return $obj->funktionscode == 5;
			});

			$funktion_obj_arr[0]->studiengang[]= setLeadingZero(intval($studiengang->studiengang_kz), 4);	// STG ergaenzen
		}
	}

	return $funktion_arr;
}

/**
 * 	Funktionscode 7 aus Entwicklungsteam-Tabelle ermitteln
 * @param String $uid
 * @param array $funktion_arr 	Object-Array
 * @return array
 *
 */
function _addFunktionscontainer_Funktionscode7($uid, $funktion_arr)
{
	$entwicklungsteam = new Entwicklungsteam();
	$entwicklungsteam->getEntwicklungsteam($uid);
	$entwicklungsteam_arr = $entwicklungsteam->result;

	if (!empty($entwicklungsteam_arr))
	{
		// Lehrgaenge und STG, die nicht BIS gemeldet werden, extrahieren
		$entwicklungsteam_arr = array_filter($entwicklungsteam_arr, function ($obj)
		{
			return
				!in_array($obj->studiengang_kz, BIS_EXCLUDE_STG) &&
				$obj->studiengang_kz > 0 &&
				$obj->studiengang_kz < 10000;
		});
	}

	if (!empty($entwicklungsteam_arr))
	{
		// Hoechste besondere Qualifikation
		$besondere_qualifikation_code_arr = array_values(array_column($entwicklungsteam_arr, 'besqualcode'));
		$besondere_qualifikation_code = max($besondere_qualifikation_code_arr);

		// Studiengaenge, wo Person Teil des Entwicklungsteams gewesen ist
		$studiengang_kz_arr = array_values(array_column($entwicklungsteam_arr, 'studiengang_kz'));
		sort($studiengang_kz_arr);							// sortieren
		foreach($studiengang_kz_arr as &$studiengang_kz)	// fuehrende Nullen fuer STG
		{
			$studiengang_kz = setLeadingZero(intval($studiengang_kz), 4);
		}

		// Funktionsobjekt generieren und dem Funktionscontainer anhaengen
		$funktion_obj = new StdClass();
		$funktion_obj->funktionscode = 7;
		$funktion_obj->besondereQualifikationCode = $besondere_qualifikation_code;
		$funktion_obj->studiengang = $studiengang_kz_arr;
		$funktion_arr []= $funktion_obj;
	}

	return $funktion_arr;
}

/**
 * Lehrecontainer fuer Lehrtaetigkeit (Semesterwochenstunden) pro STG erstellen.
 * @param array $sws_proStg_arr Object-Array
 * @return array
 */
function _getLehrecontainer($sws_proStg_arr)
{
	$lehre_arr = array();

	if (!empty($sws_proStg_arr))
	{
		// Lehrgaenge und STG, die nicht BIS gemeldet werden, extrahieren
		$sws_proStg_arr = array_filter($sws_proStg_arr, function ($obj)
	{
		return
			!in_array($obj->studiengang_kz, BIS_EXCLUDE_STG) &&
			$obj->studiengang_kz > 0 &&
			$obj->studiengang_kz < 10000;
	});
	}

	if (!empty($sws_proStg_arr))
	{
		foreach ($sws_proStg_arr as $sws_proStg)
		{
			$is_sommersemester = substr($sws_proStg->studiensemester_kurzbz, 0, 2) == 'SS';
			$is_wintersemester = substr($sws_proStg->studiensemester_kurzbz, 0, 2) == 'WS';

			// Lehreobjekt generieren
			if (empty($lehre_arr) ||																// Erster Durchlauf ODER
				!in_array($sws_proStg->studiengang_kz, array_column($lehre_arr, 'StgKz')))	// Neu
			{
				$lehre_obj = new StdClass();

				$lehre_obj->StgKz = setLeadingZero(intval($sws_proStg->studiengang_kz), 4);
				$lehre_obj->SommersemesterSWS = $is_sommersemester ? $sws_proStg->sws : 0.00;
				$lehre_obj->WintersemesterSWS = $is_wintersemester ? $sws_proStg->sws : 0.00;

				// Lehreobjekt dem Lehrecontainer anhaengen
				$lehre_arr []= $lehre_obj;
			}
			else	// Lehrecontainer mit STG schon vorhanden
			{
				$lehre_obj_arr = array_filter($lehre_arr, function (&$obj) use ($sws_proStg) {
					return $obj->StgKz == $sws_proStg->studiengang_kz;
				});

				// SWS ergaenzen
				if ($is_sommersemester)
				{
					current($lehre_obj_arr)->SommersemesterSWS = $sws_proStg->sws;
				}
				else if ($is_wintersemester)
				{
					current($lehre_obj_arr)->WintersemesterSWS = $sws_proStg->sws;
				}
			}
		}
	}


	return $lehre_arr;
}

function _generateXML($person_arr)
{
	$xml = '';
	$xml .= '<?xml version="1.0" encoding="UTF-8"?>';

	$erhalter = new erhalter();
	$erhalter->getAll();

	if(isset($erhalter->result[0]))
	{
		$erhalter = sprintf("%03s",trim($erhalter->result[0]->erhalter_kz));
	}
	else
		$erhalter = '';

	$xml .= '<Erhalter>';
	$xml .= '<ErhKz>'.$erhalter.'</ErhKz>';
	$xml .= '<MeldeDatum>1504'.date('Y').'</MeldeDatum>';
	$xml .= '<PersonalMeldung>';

	foreach ($person_arr as $person)
	{
		$xml .= '<Person>';

		$xml .= '<PersonalNummer><![CDATA['. $person->personalnummer. ']]></PersonalNummer>';
		$xml .= '<Geschlecht><![CDATA['. $person->geschlecht. ']]></Geschlecht>';
		if ($person->geschlecht == 'x')
			$xml .= '<GeschlechtX><![CDATA['. $person->geschlechtX. ']]></GeschlechtX>';
		$xml .= '<Geburtsjahr><![CDATA['. $person->geburtsjahr. ']]></Geburtsjahr>';
		$xml .= '<StaatsangehoerigkeitCode><![CDATA['. $person->staatsangehoerigkeit. ']]></StaatsangehoerigkeitCode>';
		$xml .= '<HoechsteAbgeschlosseneAusbildung><![CDATA['. $person->hoechste_abgeschlossene_ausbildung. ']]></HoechsteAbgeschlosseneAusbildung>';
		$xml .= '<Habilitation><![CDATA['. $person->habilitation. ']]></Habilitation>';
		$xml .= (!is_null($person->hauptberufcode))
			? '<HauptberufCode><![CDATA['. $person->hauptberufcode. ']]></HauptberufCode>'
			: '';

		foreach ($person->verwendung_arr as $verwendung)
		{
			$xml .= '<Verwendung>';
			$xml .= '<VerwendungsCode><![CDATA['. $verwendung->verwendung_code. ']]></VerwendungsCode>';
			$xml .= '<BeschaeftigungsArt1><![CDATA['. $verwendung->ba1code. ']]></BeschaeftigungsArt1>';
			$xml .= '<BeschaeftigungsArt2><![CDATA['. $verwendung->ba2code. ']]></BeschaeftigungsArt2>';
			$xml .= '<BeschaeftigungsAusmassVZAE><![CDATA['. $verwendung->vzae. ']]></BeschaeftigungsAusmassVZAE>';
			$xml .= '<BeschaeftigungsAusmassJVZAE><![CDATA['. $verwendung->jvzae. ']]></BeschaeftigungsAusmassJVZAE>';
			$xml .= '</Verwendung>';
		}

		foreach ($person->funktion_arr as $funktion)
		{
			$xml .= '<Funktion>';
			$xml .= '<FunktionsCode><![CDATA['. $funktion->funktionscode. ']]></FunktionsCode>';
			$xml .= (!is_null($funktion->besondereQualifikationCode))
				? '<BesondereQualifikationCode><![CDATA['. $funktion->besondereQualifikationCode. ']]></BesondereQualifikationCode>'
				: '';

			if ($funktion->funktionscode == 5 || $funktion->funktionscode == 7)
			{
				$xml .= '<Studiengang>';

				if (is_array($funktion->studiengang))
				{
					foreach ($funktion->studiengang as $studiengang)
					{
						$xml .= '<StgKz><![CDATA['. $studiengang. ']]></StgKz>';
					}
				}
				else if (!is_null($funktion->studiengang))
				{
					$xml .= '<StgKz><![CDATA['. $funktion->studiengang. ']]></StgKz>';

				}
				$xml .= '</Studiengang>';
			}

			$xml .= '</Funktion>';
		}

		foreach ($person->lehre_arr as $lehre)
		{
			$xml .= '<Lehre>';
			$xml .= '<StgKz><![CDATA['. $lehre->StgKz. ']]></StgKz>';
			$xml .= '<SommersemesterSWS><![CDATA['. $lehre->SommersemesterSWS. ']]></SommersemesterSWS>';
			$xml .= '<WintersemesterSWS><![CDATA['. $lehre->WintersemesterSWS. ']]></WintersemesterSWS>';
			$xml .= '</Lehre>';
		}

		$xml .= '</Person>';
	}

	$xml .= '</PersonalMeldung>';
	$xml .= '</Erhalter>';

	return $xml;
}

/**
 * Prints the HTML Table with all Persons
 * @param $person_arr Array of PersonData
 */
function _outputHTML($person_arr)
{
	echo '<html>
	<head>
		<title>BIS - Meldung Personal</title>
		<meta charset="utf-8">
		<link href="../../skin/vilesci.css" rel="stylesheet" type="text/css">';
		include('../../include/meta/jquery.php');
		include('../../include/meta/jquery-tablesorter.php');
	echo '
	</head>
	<body>
	<h1>Personalmeldung</h1>';

	echo '
	<script type="text/javascript">
		$(document).ready(function()
			{
				$("#t1").tablesorter(
				{
					sortList: [[2,0]],
					widgets: ["zebra"]
				});
			});
	</script>
	';

	echo "Anzahl der gemeldeten Personen: ".count($person_arr);

	echo '
	<table id="t1">
	<thead>
		<tr>
			<th>PersNr</th>
			<th>Vorname</th>
			<th>Nachname</th>
			<th>UID</th>
			<th>Geschlecht (X)</th>
			<th>Geb.Jahr</th>
			<th>Staat</th>
			<th>Höchste Ausb.</th>
			<th>Habil.</th>
			<th>Hautpberufcode</th>
			<th>Verwendung</th>
			<th>Funktion</th>
			<th>Lehre</th>
		</tr>
	</thead>
	<tbody>';


	foreach ($person_arr as $person)
	{
		echo '
		<tr>
			<td>'.$person->personalnummer.'</td>
			<td>'.$person->vorname.'</td>
			<td>'.$person->nachname.'</td>
			<td>'.$person->uid.'</td>
			<td>'.$person->geschlecht.($person->geschlecht=='x'?'('.$person->geschlechtX.')':'').'</td>
			<td>'.$person->geburtsjahr.'</td>
			<td>'.$person->staatsangehoerigkeit.'</td>
			<td>'.$person->hoechste_abgeschlossene_ausbildung.'</td>
			<td>'.$person->habilitation.'</td>
			<td>'.$person->hauptberufcode.'</td>';

		echo '<td style="vertical-align: top">';

		if (count($person->verwendung_arr) > 0)
		{
			echo '
				<table>
				<thead>
					<tr>
						<td>Verwendung</td>
						<td>Ba1Code</td>
						<td>Ba2Code</td>
						<td>VZÄ</td>
						<td>JVZÄ</td>
					</tr>
				</thead>
				<tbody>
				';

			foreach ($person->verwendung_arr as $verwendung)
			{
				echo '
				<tr>
					<td>'.$verwendung->verwendung_code.'</td>
					<td>'.$verwendung->ba1code.'</td>
					<td>'.$verwendung->ba2code.'</td>
					<td>'.$verwendung->vzae.'</td>
					<td>'.$verwendung->jvzae.'</td>
				</tr>';
			}
			echo '</tbody>
				</table>';
		}
		echo '</td>';

		echo '<td style="vertical-align: top">';

		if (count($person->funktion_arr) > 0)
		{
			echo '
				<table>
				<thead>
					<tr>
						<td>Funktion</td>
						<td>Bes.Qual</td>
						<td>StgKZ</td>
					</tr>
				</thead>
				<tbody>
			';

			foreach ($person->funktion_arr as $funktion)
			{
				echo '
				<tr>
					<td>'. $funktion->funktionscode. '</td>
					<td>'. $funktion->besondereQualifikationCode. '</td>
					<td>';

				if ($funktion->funktionscode == 5 || $funktion->funktionscode == 7)
				{
					if (is_array($funktion->studiengang))
					{
						foreach ($funktion->studiengang as $studiengang)
						{
							echo $studiengang.' ';
						}
					}
					else if (!is_null($funktion->studiengang))
					{
						echo $funktion->studiengang.' ';
					}
				}
				echo '</td>
				</tr>';
			}
			echo '</tbody>
				</table>';
		}
		echo '</td>';

		echo '<td style="vertical-align: top">';
		if ($person->lehre_arr > 0)
		{
			echo '
				<table>
				<thead>
				<tr>
					<td>StgKZ</td>
					<td>SommerSWS</td>
					<td>WinterSWS</td>
				</tr>
				</thead>
				<tbody>';

			foreach ($person->lehre_arr as $lehre)
			{
				echo '
				<tr>
					<td>'. $lehre->StgKz. '</td>
					<td>'. $lehre->SommersemesterSWS. '</td>
					<td>'. $lehre->WintersemesterSWS. '</td>
				</tr>';
			}

			echo '</tbody>
				</table>';
		}
		echo '</td>
		</tr>';
	}

	echo '</tbody>
	</table>';
}
