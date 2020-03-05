<?php

require_once('../../config/vilesci.config.inc.php');
require_once('../../include/studiensemester.class.php');
require_once('../../include/datum.class.php');
require_once('../../include/benutzerberechtigung.class.php');
require_once('../../include/functions.inc.php');
require_once('../../include/bisverwendung.class.php');
require_once('../../include/mitarbeiter.class.php');
require_once('../../include/studiengang.class.php');
require_once('../../include/lehreinheitmitarbeiter.class.php');

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


// Prüfe Zeitraum zur Erstellung einer BIS-Meldung
$studiensemester = new studiensemester();
$stsem = (isset($_GET['stsem'])) ? $_GET['stsem'] : $studiensemester->getaktorNext(1);	// aktuelles Studiensemester

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

	$person_obj->personalnummer = $mitarbeiter->personalnummer;
	$person_obj->uid = $mitarbeiter->uid;
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

	// Hauptberufcode: wenn Hauptberuf / Nebenberuf im gleichen Jahr - laengere Dauer entscheidet
	// -----------------------------------------------------------------------------------------------------------------
	$is_hauptberuflich = $bisverwendung_arr[count($bisverwendung_arr) - 1]->hauptberuflich;  // default: hauptberuflich der letzten BIS-Verwendung

	// Wenn im selben Jahr hauptberuflich UND nebenberuflich -> laengere Dauer wird gemeldet (Ueberwiegenheitprinzip)
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
	$person_obj->hauptberufcode = ($is_hauptberuflich == true) ? NULL : $bisverwendung_arr[count($bisverwendung_arr) - 1]->hauptberufcode;

	// -----------------------------------------------------------------------------------------------------------------
	// Relatives Beschaeftigungsausmass / Anteilige JVZAE ermitteln
	// -----------------------------------------------------------------------------------------------------------------

	// Lehrtaetigkeit ermitteln
	$lema = new lehreinheitmitarbeiter();
	$lema->getLehreinheiten_SWS_BISMeldung($person_obj->uid, $ss_kurzbz);
	$lehre_ss_sws = $lema->result[0];	// Anzahl SS - Semesterwochenstunden

	$lema = new lehreinheitmitarbeiter();
	$lema->getLehreinheiten_SWS_BISMeldung($person_obj->uid, $ws_kurzbz);
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
						$verwendung_lehre_obj->gewichtung = ($tage_lehre_imSemester == 182) ? BIS_HALBJAHRES_GEWICHTUNG_SWS : round(BIS_HALBJAHRES_GEWICHTUNG_SWS / ($tage_imJahr / 2) * $tage_lehre_imSemester, 2);
						$verwendung_lehre_obj->jvzae_anteilig = round($verwendung_lehre_obj->beschaeftigungsausmass_relativ * $verwendung_lehre_obj->gewichtung, 3);

						/**
						 * Relativen Beschaeftigungsausmass der BIS-Verwendung berichtigen
						 * (durch Abzug des eben erstellten relativen Beschaeftigungsausmass fuer Lehrtaetigkeiten)
						 * */
						$bisverwendung->beschaeftigungsausmass_relativ -= $verwendung_lehre_obj->beschaeftigungsausmass_relativ;

						/**
						 * Anteilige JVZAE der BIS-Verwendung berichtigen
						 * (durch Abzug der eben erstellten anteiligen JVZAE fuer Lehrtaetigkeiten)
						 */
						$bisverwendung->jvzae_anteilig -= $verwendung_lehre_obj->jvzae_anteilig;
						$bisverwendung_arr [] = $verwendung_lehre_obj;
					}
				}
			}
			// TODO: Interner Check: if ($bisverwendung->jvzae_anteilig < 0) -> Anteil für 'Nicht-Lehre'-Teil muss gegeben sein.
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
	}

	// *****************************************************************************************************************
	// JVZAE und VZAE ermitteln  (Jahresvollzeitaequivalenz, Vollzeitaequivalenz)
	// *****************************************************************************************************************

	// Container Verwendung aus dem bisverwendung_arr generieren, formatieren und dem Container Person anhängen
	$verwendung_arr = array();
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
			$verwendung_obj->jvzae = 0;
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
				$verwendung_obj->jvzae += (isset($verwendung_tmp->jvzae_anteilig)) ? $verwendung_tmp->jvzae_anteilig * 100 : NULL; // TODO: not null...


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
	$person_arr []= $person_obj;
}

// ---------------------------------------------------------------------------------------------------------------------
// Private Functions
// ---------------------------------------------------------------------------------------------------------------------
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


