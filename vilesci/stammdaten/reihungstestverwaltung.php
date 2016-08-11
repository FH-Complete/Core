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
 * Authors: Christian Paminger 	< christian.paminger@technikum-wien.at >
 *          Andreas Oesterreicher 	< andreas.oesterreicher@technikum-wien.at >
 *          Rudolf Hangl 		< rudolf.hangl@technikum-wien.at >
 *          Gerald Simane-Sequens 	< gerald.simane-sequens@technikum-wien.at >
 *          Manfred Kindl		< manfred.kindl@technikum-wien.at >
 */
/**
 * Reihungstest
 *
 * - Anlegen und Bearbeiten von Terminen
 * - Export von Anwesenheitslisten als Excel
 * - Uebertragung der Ergebnis-Punkte ins FAS
 *
 * Parameter:
 * excel ... wenn gesetzt, dann wird die Anwesenheitsliste als Excel exportiert
 */
require_once('../../config/vilesci.config.inc.php');
require_once('../../config/global.config.inc.php');
require_once('../../include/functions.inc.php');
require_once('../../include/studiengang.class.php');
require_once('../../include/reihungstest.class.php');
require_once('../../include/ort.class.php');
require_once('../../include/datum.class.php');
require_once('../../include/benutzerberechtigung.class.php');
require_once('../../include/pruefling.class.php');
require_once('../../include/person.class.php');
require_once('../../include/prestudent.class.php');
require_once('../../include/Excel/excel.php');
require_once('../../include/adresse.class.php');
require_once('../../include/studiensemester.class.php');
require_once('../../include/benutzer.class.php');
require_once('../../include/studienplan.class.php');
require_once('../../include/sprache.class.php');
require_once('../../include/organisationsform.class.php');

// @todo Allgemein: Beim kopieren auch die Studienplanzuordnungen übernehmen
//					"Teilgenommen" und "Punkte" werden immer mit false bzw. 0 gespeichert

define('REIHUNGSTEST_ARBEITSPLAETZE_SCHWUND', '5');
define('REIHUNGSTEST_ERGEBNISSE_BERECHNEN', false);

if (!$db = new basis_db())
{
	die('Es konnte keine Verbindung zum Server aufgebaut werden.');
}

$stsem_akt = new studiensemester();
$stsem_akt = $stsem_akt->getaktorNext();

$user = get_uid();
$datum_obj = new datum();
$stg_kz = (isset($_GET['stg_kz']) ? $_GET['stg_kz'] : '');
$reihungstest_id = (isset($_GET['reihungstest_id']) ? $_GET['reihungstest_id'] : '');
$studiensemester_kurzbz = (isset($_GET['studiensemester_kurzbz']) ? $_GET['studiensemester_kurzbz'] : $stsem_akt);
$studienplan_id = (isset($_GET['studienplan_id']) ? $_GET['studienplan_id'] : '');
$prestudent_id = (isset($_GET['prestudent_id']) ? $_GET['prestudent_id'] : '');
$rtpunkte = (isset($_GET['rtpunkte']) ? $_GET['rtpunkte'] : '');
$neu = (isset($_GET['neu']) ? true : false);
$stg_arr = array();
$error = false;

$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);
/*
if ($reihungstest_id != '' || isset($_POST['reihungstest_id']))
{
	if ($reihungstest_id != '')
	{
		$rt = new Reihungstest();
		$rt->load($reihungstest_id);
		$studiensemester_kurzbz = $rt->studiensemester_kurzbz;
	}
	elseif (isset($_POST['reihungstest_id']))
	{
		$rt = new Reihungstest();
		$rt->load($_POST['reihungstest_id']);
		$studiensemester_kurzbz = $rt->studiensemester_kurzbz;
	}
	else
		$studiensemester_kurzbz = $stsem_akt;
}*/

if ($stg_kz == '' && ($reihungstest_id != '' || isset($_POST['reihungstest_id'])))
{
	if ($reihungstest_id != '')
	{
		$rt = new Reihungstest();
		$rt->load($reihungstest_id);
		$stg_kz = $rt->studiengang_kz;
	}
	elseif (isset($_POST['reihungstest_id']))
	{
		$rt = new Reihungstest();
		$rt->load($_POST['reihungstest_id']);
		$stg_kz = $rt->studiengang_kz;
	}
	else
		$stg_kz = '-1';
}

if(!$rechte->isBerechtigt('lehre/reihungstest'))
{
	die($rechte->errormsg);
}
	
$studiengang = new studiengang();
$studiengang->getAll('typ, kurzbz', false);

$studiensemester = new Studiensemester();
$studiensemester->getAll('DESC');

$sprachen_obj = new sprache();
$sprachen_obj->getAll();
$sprachen_arr=array();

foreach($sprachen_obj->result as $row)
{
	if(isset($row->bezeichnung_arr[$sprache]))
		$sprachen_arr[$row->sprache]=$row->bezeichnung_arr[$sprache];
	else
		$sprachen_arr[$row->sprache]=$row->sprache;
}

$orgform_obj = new organisationsform();
$orgform_obj->getAll();
$orgform_arr=array();
foreach($orgform_obj->result as $row)
	$orgform_arr[$row->orgform_kurzbz]=$row->bezeichnung;

//Studierende als Excel Exportieren
if(isset($_GET['excel']))
{
	$reihungstest = new reihungstest();
	if($reihungstest->load($_GET['reihungstest_id']))
	{
		$studienplaene_arr = array();
		$studienplaene = new reihungstest();
		$studienplaene->getStudienplaeneReihungstest($reihungstest->reihungstest_id);
		foreach ($studienplaene->result AS $row)
		{
			$studienplan = new studienplan();
			$studienplan->loadStudienplan($row->studienplan_id);
			$studienplaene_arr[ $row->studienplan_id] = $studienplan->bezeichnung;
		}
		
		$studienplaene_list = implode(',', array_keys($studienplaene_arr));
		$qry = "
				SELECT 
				rt_id,
				prestudent_id,
				tbl_rt_person.person_id,
				vorname,
				nachname,
				ort_kurzbz,
				studienplan_id,
				studiengang_kz,
				gebdatum,
				geschlecht,
				rt_punkte1
				,(
					SELECT kontakt
					FROM tbl_kontakt
					WHERE kontakttyp = 'email'
						AND person_id = tbl_rt_person.person_id
						AND zustellung = true LIMIT 1
					) AS email
				,(
					SELECT ausbildungssemester
					FROM public.tbl_prestudentstatus
					WHERE prestudent_id = tbl_prestudent.prestudent_id
						AND datum = (
							SELECT MAX(datum)
							FROM public.tbl_prestudentstatus
							WHERE prestudent_id = tbl_prestudent.prestudent_id
								AND status_kurzbz = 'Interessent'
							) LIMIT 1
					) AS ausbildungssemester
				,(
					SELECT orgform_kurzbz
					FROM public.tbl_prestudentstatus
					WHERE prestudent_id = tbl_prestudent.prestudent_id
						AND datum = (
							SELECT MAX(datum)
							FROM public.tbl_prestudentstatus
							WHERE prestudent_id = tbl_prestudent.prestudent_id
								AND status_kurzbz = 'Interessent'
							) LIMIT 1
					) AS orgform_kurzbz
				FROM public.tbl_rt_person
				JOIN public.tbl_person USING (person_id)
				JOIN public.tbl_prestudent ON (tbl_rt_person.person_id=tbl_prestudent.person_id AND tbl_rt_person.rt_id=tbl_prestudent.reihungstest_id)
				WHERE rt_id = ".$db->db_add_param($reihungstest->reihungstest_id, FHC_INTEGER)."
				AND tbl_rt_person.studienplan_id IN (".$studienplaene_list.")
				ORDER BY ort_kurzbz NULLS FIRST,nachname,vorname
						
				/*		
				SELECT 
				prestudent_id,
				person_id,
				vorname,
				nachname,
				(SELECT ort_kurzbz FROM public.tbl_rt_person WHERE rt_id = ".$db->db_add_param($reihungstest_id, FHC_INTEGER)." AND person_id=tbl_person.person_id) AS ort_kurzbz,
	 			(SELECT studienplan_id FROM public.tbl_rt_person WHERE rt_id = ".$db->db_add_param($reihungstest_id, FHC_INTEGER)." AND person_id=tbl_person.person_id) AS studienplan_id,
				studiengang_kz,
				gebdatum,
				geschlecht,
				rt_punkte1
				,(
					SELECT kontakt
					FROM tbl_kontakt
					WHERE kontakttyp = 'email'
						AND person_id = tbl_prestudent.person_id
						AND zustellung = true LIMIT 1
					) AS email
				,(
					SELECT ausbildungssemester
					FROM public.tbl_prestudentstatus
					WHERE prestudent_id = tbl_prestudent.prestudent_id
						AND datum = (
							SELECT MAX(datum)
							FROM public.tbl_prestudentstatus
							WHERE prestudent_id = tbl_prestudent.prestudent_id
								AND status_kurzbz = 'Interessent'
							) LIMIT 1
					) AS ausbildungssemester
				,(
					SELECT orgform_kurzbz
					FROM public.tbl_prestudentstatus
					WHERE prestudent_id = tbl_prestudent.prestudent_id
						AND datum = (
							SELECT MAX(datum)
							FROM public.tbl_prestudentstatus
							WHERE prestudent_id = tbl_prestudent.prestudent_id
								AND status_kurzbz = 'Interessent'
							) LIMIT 1
					) AS orgform_kurzbz
				FROM public.tbl_prestudent
				JOIN public.tbl_person USING (person_id)
				WHERE reihungstest_id = ".$db->db_add_param($reihungstest->reihungstest_id, FHC_INTEGER)."
				ORDER BY ort_kurzbz NULLS FIRST,nachname,vorname */";
		
		$gebietbezeichnungen = array();
		$qry_gebiete = "SELECT gebiet_id, reihung, bezeichnung FROM testtool.tbl_ablauf JOIN testtool.tbl_gebiet USING (gebiet_id) WHERE studienplan_id = ".$db->db_add_param($row->studienplan_id)." ORDER BY reihung";
		if($result_gebiete = $db->db_query($qry_gebiete))
		{
			while($row_gebiete = $db->db_fetch_object($result_gebiete))
			{
				$gebietbezeichnungen[$row_gebiete->gebiet_id] = $row_gebiete->bezeichnung;
			}
		}

		// Creating a workbook
		$workbook = new Spreadsheet_Excel_Writer();
		$workbook->setVersion(8);
		// sending HTTP headers
		$workbook->send("Anwesenheitsliste_Aufnahmetermin_".$reihungstest->datum.".xls");
		
		//Formate Definieren
		$format_bold =& $workbook->addFormat();
		$format_bold->setBold();
		
		$format_border =& $workbook->addFormat();
		$format_border->setBorder(1);
		$format_border->setTextWrap();
		$format_border->setVAlign ('top');
		
		$format_border_center =& $workbook->addFormat();
		$format_border_center->setBorder(1);
		$format_border_center->setTextWrap();
		$format_border_center->setVAlign ('top');
		$format_border_center->setHAlign ('center');
		
		if($result = $db->db_query($qry))
		{
			$ort_kurzbz = '0';
			while($row = $db->db_fetch_object($result))
			{
				if ($ort_kurzbz == '0' || $ort_kurzbz != $row->ort_kurzbz)
				{
					// Creating a worksheet
					if ($row->ort_kurzbz=='')
						$worksheet =& $workbook->addWorksheet("Ohne Raumzuteilung");
					else 
						$worksheet =& $workbook->addWorksheet("Raum ".$row->ort_kurzbz);
					$worksheet->setInputEncoding('utf-8');
					//$worksheet->setZoom (85);
					$worksheet->hideScreenGridlines();
					$worksheet->hideGridlines();
					$worksheet->setLandscape();
					$worksheet->centerHorizontally(1);
					$worksheet->fitToPages ( 1, 1);
					$worksheet->setMargins_LR (0.4);
					$worksheet->setMarginTop (0.79);
					$worksheet->setMarginBottom (0.59);

					// Titelzeilen
					$worksheet->write(0,0,'Anwesenheitsliste Aufnahmetermin vom '.$datum_obj->convertISODate($reihungstest->datum).' '.$reihungstest->uhrzeit.' Uhr, '.$reihungstest->anmerkung.', erstellt am '.date('d.m.Y'), $format_bold);
					if ($row->ort_kurzbz=='')
						$worksheet->write(1,0,'Ohne Raumzuteilung', $format_bold);
					else 
						$worksheet->write(1,0,'Raum '.$row->ort_kurzbz, $format_bold);
					$worksheet->write(2,0,'Studienpläne: '.implode(', ', $studienplaene_arr));
					$worksheet->write(3,0,'Stufe: '.$reihungstest->stufe);
					$worksheet->write(4,0,'Testmodule: '.implode(', ', $gebietbezeichnungen));
					
					//Ueberschriften
					$zeile=6;
					$col=0;
					$worksheet->write($zeile,$col,"Vorname", $format_bold);
					$maxlength[$col] = 7;
					$worksheet->write($zeile,++$col,"Nachname", $format_bold);
					$maxlength[$col] = 8;
					$worksheet->write($zeile,++$col,"G", $format_bold);
					$maxlength[$col] = 2;
					$worksheet->write($zeile,++$col,"Geburtsdatum", $format_bold);
					$maxlength[$col] = 12;
					$worksheet->write($zeile,++$col,"Studiengang", $format_bold);
					$maxlength[$col] = 11;
					$worksheet->write($zeile,++$col,"S", $format_bold);
					$maxlength[$col] = 2;
					$worksheet->write($zeile,++$col,"Bereits absolvierte RTs", $format_bold);
					$maxlength[$col] = 20;
					$worksheet->write($zeile,++$col,"Sonstige Termine", $format_bold);
					$maxlength[$col] = 20;
					$worksheet->write($zeile,++$col,"EMail", $format_bold);
					$maxlength[$col] = 5;
					$worksheet->write($zeile,++$col,"Strasse", $format_bold);
					$maxlength[$col] = 6;
					$worksheet->write($zeile,++$col,"PLZ", $format_bold);
					$maxlength[$col] = 3;
					$worksheet->write($zeile,++$col,"Ort", $format_bold);
					$maxlength[$col] = 3;
					$worksheet->write($zeile,++$col,"Unterschrift", $format_bold);
					$maxlength[$col] = 30;
					
					$ort_kurzbz = $row->ort_kurzbz;
					$zeile++;
				}
				
				$pruefling = new pruefling();

				$prestudent = new prestudent();
				$prestudent->getPrestudenten($row->person_id);
				$rt_in_anderen_stg='';
				$erg = '';
				if(defined('REIHUNGSTEST_ERGEBNISSE_BERECHNEN') && REIHUNGSTEST_ERGEBNISSE_BERECHNEN)
				{
					foreach($prestudent->result as $item)
					{
						if($item->prestudent_id!=$row->prestudent_id)
						{
							if(defined('FAS_REIHUNGSTEST_PUNKTE') && FAS_REIHUNGSTEST_PUNKTE)
								$erg = $pruefling->getReihungstestErgebnis($item->prestudent_id, true);
							else
								$erg = $pruefling->getReihungstestErgebnis($item->prestudent_id);
							if($erg!=0)
							{
								$rt_in_anderen_stg.=number_format($erg,2).' Punkte im Studiengang '.$studiengang->kuerzel_arr[$item->studiengang_kz]."; ";
							}
						}
					}
				}
				$weitere_zuteilungen = array();
				/*$qry_zuteilungen = "SELECT DISTINCT	tbl_studienplan.bezeichnung,tbl_reihungstest.datum,tbl_rt_person.studienplan_id
								FROM public.tbl_rt_person JOIN public.tbl_reihungstest ON (rt_id = reihungstest_id)
								JOIN lehre.tbl_studienplan USING (studienplan_id)
								JOIN testtool.tbl_ablauf USING (studienplan_id)
								WHERE person_id=62563
								AND studiensemester_kurzbz='SS2017'
								ORDER BY bezeichnung";*/
				$qry_zuteilungen = "SELECT DISTINCT	tbl_studienplan.bezeichnung,tbl_reihungstest.datum,tbl_rt_person.studienplan_id
								FROM public.tbl_rt_person JOIN public.tbl_reihungstest ON (rt_id = reihungstest_id)
								JOIN lehre.tbl_studienplan USING (studienplan_id)
								JOIN testtool.tbl_ablauf USING (studienplan_id)
								WHERE person_id=".$db->db_add_param($row->person_id)."
								AND studiensemester_kurzbz=".$db->db_add_param($reihungstest->studiensemester_kurzbz)."
								ORDER BY bezeichnung";
				
				if($result_zuteilungen = $db->db_query($qry_zuteilungen))
				{
					while($row_zuteilungen = $db->db_fetch_object($result_zuteilungen))
					{
						$testmodule = array();
						$qry_gebiete = "SELECT gebiet_id, bezeichnung, reihung FROM testtool.tbl_ablauf JOIN testtool.tbl_gebiet USING (gebiet_id) WHERE studienplan_id = ".$db->db_add_param($row_zuteilungen->studienplan_id)." ORDER BY reihung";
						if($result_gebiete = $db->db_query($qry_gebiete))
						{
							while($row_gebiete = $db->db_fetch_object($result_gebiete))
							{
								$testmodule[$row_gebiete->gebiet_id] = $row_gebiete->bezeichnung;
							}
						}
						$weitere_zuteilungen[] = $row_zuteilungen->bezeichnung.' am '.$datum_obj->formatDatum($row_zuteilungen->datum, 'd.m.Y').' ('.implode(', ', $testmodule).')';
					}
				}

				$col=0;
				$worksheet->write($zeile,$col, $row->vorname, $format_border);
				if(strlen($row->vorname)>$maxlength[$col])
					$maxlength[$col] = strlen($row->vorname);

				$worksheet->write($zeile,++$col,$row->nachname, $format_border);
				if(strlen($row->nachname)>$maxlength[$col])
					$maxlength[$col] = strlen($row->nachname);
				
				$worksheet->write($zeile,++$col, $row->geschlecht, $format_border_center);
				if(strlen($row->geschlecht)>$maxlength[$col])
					$maxlength[$col] = strlen($row->geschlecht);

				$worksheet->write($zeile,++$col,$datum_obj->convertISODate($row->gebdatum), $format_border);
				if(strlen($row->gebdatum)>$maxlength[$col])
					$maxlength[$col] = strlen($row->gebdatum);

				$worksheet->write($zeile,++$col,$studiengang->kuerzel_arr[$row->studiengang_kz], $format_border);
				if(strlen($studiengang->kuerzel_arr[$row->studiengang_kz])>$maxlength[$col])
					$maxlength[$col] = strlen($studiengang->kuerzel_arr[$row->studiengang_kz]);

				$worksheet->write($zeile,++$col,$row->ausbildungssemester, $format_border_center);
				if(strlen($row->ausbildungssemester)>$maxlength[$col])
					$maxlength[$col] = strlen($row->ausbildungssemester);

				$worksheet->write($zeile,++$col,$rt_in_anderen_stg, $format_border);
				if(strlen($rt_in_anderen_stg)>$maxlength[$col])
					$maxlength[$col] = strlen($rt_in_anderen_stg);

				$worksheet->write($zeile,++$col,implode("\n", $weitere_zuteilungen), $format_border);
				foreach ($weitere_zuteilungen as $items)
				{
					if (strlen($items)>$maxlength[$col])
						$maxlength[$col] = strlen($items);
				}

				$worksheet->write($zeile,++$col,$row->email, $format_border);
				if(strlen($row->email)>$maxlength[$col])
					$maxlength[$col] = strlen($row->email);

 				$adresse = new adresse();
				$adresse->loadZustellAdresse($row->person_id);

				$worksheet->write($zeile,++$col,$adresse->strasse, $format_border);
				if(strlen($adresse->strasse)>$maxlength[$col])
					$maxlength[$col] = strlen($adresse->strasse);

				$worksheet->write($zeile,++$col,$adresse->plz, $format_border);
				if(strlen($adresse->plz)>$maxlength[$col])
					$maxlength[$col] = strlen($adresse->plz);

				$worksheet->write($zeile,++$col,$adresse->ort, $format_border);
				if(strlen($adresse->ort)>$maxlength[$col])
					$maxlength[$col] = strlen($adresse->ort);
				
				$worksheet->write($zeile,++$col,'', $format_border);
				
				if(count($weitere_zuteilungen)>2)
					$worksheet->setRow($zeile, count($weitere_zuteilungen)*14);
				else
					$worksheet->setRow($zeile, 35);

				$zeile++;
				
				//Die Breite der Spalten setzen
				foreach($maxlength as $col=>$breite)
					$worksheet->setColumn($col, $col, $breite+2);
			}
		}
		$workbook->close();
	}
	else
	{
		echo 'Reihungstest wurde nicht gefunden!';
	}
	return;
} ?>
<!DOCTYPE HTML>
<html>
	<head>
		<title>Reihungstest</title>
		<link rel="stylesheet" href="../../skin/vilesci.css" type="text/css">
		<!--<link rel="stylesheet" href="../../include/js/tablesort/table.css" type="text/css">-->
		<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
		<!--<script src="../../include/js/tablesort/table.js" type="text/javascript"></script>-->
		<script type="text/javascript" src="../../include/js/jquery.js"></script>
		<link rel="stylesheet" href="../../skin/tablesort.css" type="text/css"/>
		<link href="../../skin/jquery-ui-1.9.2.custom.min.css" rel="stylesheet" type="text/css">
		<script src="../../include/js/jquery1.9.min.js" type="text/javascript"></script>
		<script src="../../include/js/jquery.checkboxes-1.0.7.min.js" type="text/javascript"></script>
		
		<link href="../../skin/jquery.ui.timepicker.css" rel="stylesheet" type="text/css"/>
		<script src="../../include/js/jquery.ui.timepicker.js" type="text/javascript" ></script>

		<script type="text/javascript">
			$(document).ready(function()
			{
				$(".datepicker_datum").datepicker($.datepicker.regional['de']);

				$( ".timepicker" ).timepicker({
					showPeriodLabels: false,
					hourText: "Stunde",
					minuteText: "Minute",
					hours: {starts: 7,ends: 22},
					rows: 4,
				});

				$("#ort").autocomplete({
					source: "reihungstestverwaltung_autocomplete.php?autocomplete=ort_aktiv",
					minLength:2,
					response: function(event, ui)
					{
						//Value und Label fuer die Anzeige setzen
						for(i in ui.content)
						{
							ui.content[i].value=ui.content[i].ort_kurzbz;
							ui.content[i].label=ui.content[i].ort_kurzbz+" "+ui.content[i].bezeichnung;
						}
					},
					select: function(event, ui)
					{
						//Ausgewaehlte Ressource zuweisen und Textfeld wieder leeren
						$("#ort_kurzbz").val(ui.item.uid);
					}
				});

				$(".aufsicht_uid").autocomplete({
					source: "reihungstestverwaltung_autocomplete.php?autocomplete=kunde",
					minLength:2,
					response: function(event, ui)
					{
						//Value und Label fuer die Anzeige setzen
						for(i in ui.content)
						{
							//ui.content[i].value=ui.content[i].uid;
							ui.content[i].value=ui.content[i].vorname+" "+ui.content[i].nachname+" ("+ui.content[i].uid+")";
							ui.content[i].label=ui.content[i].vorname+" "+ui.content[i].nachname+" ("+ui.content[i].uid+")";
						}
					},
					select: function(event, ui)
					{
						//Ausgeaehlte Ressource zuweisen und Textfeld wieder leeren
						$(this.id).val(ui.item.uid);
					}	
				});

				$("#studienplan_autocomplete").autocomplete({
					source: "reihungstestverwaltung_autocomplete.php?autocomplete=studienplan",
					minLength:2,
					response: function(event, ui)
					{
						//Value und Label fuer die Anzeige setzen
						for(i in ui.content)
						{
							ui.content[i].value=ui.content[i].bezeichnung;
							ui.content[i].label=ui.content[i].bezeichnung;
						}
					},
					select: function(event, ui)
					{
						//Ausgewaehlte Ressource zuweisen und Textfeld wieder leeren
						$("#studienplan_id").val(ui.item.studienplan_id);
					}
				});

				if (typeof(Storage) !== 'undefined') 
				{
					let arr = ['clm_prestudent_id','clm_person_id','clm_geschlecht','clm_studiengang','clm_studienplan','clm_orgform','clm_einstiegssemester','clm_geburtsdatum','clm_email','clm_absolviert','clm_ergebnis','clm_fas'];
					for (let i of arr) 
					{
						if (localStorage.getItem(i) != null) 
						{
							$('.'+i).css('display', localStorage.getItem(i));
							if (localStorage.getItem(i) == 'none')
								document.getElementById(i).className = 'inactive';
							else
								document.getElementById(i).className = 'active';
						}
					}
				} 
				else 
				{
					alert('Local Storage nicht unterstuetzt');
				}

				$(".tablesorter").each(function(i,v)
				{
					$("#"+v.id).tablesorter(
					{
						widgets: ["zebra"],
						sortList: [[2,0],[3,0]],
						headers: {0: { sorter: false}}
					});
					
					$("#toggle_"+v.id).on('click', function(e) {
						$("#"+v.id).checkboxes('toggle');
						e.preventDefault();
					});

					$("#uncheck_"+v.id).on('click', function(e) {
						$("#"+v.id).checkboxes('uncheck');
						e.preventDefault();
					});
					
					$("#"+v.id).checkboxes('range', true);
				});
			});

			function hideColumn(column)
			{
				if ($('.'+column).css('display') == 'table-cell')
				{
					$('.'+column).css('display', 'none');
					localStorage.setItem(column, 'none');
					if (localStorage.getItem(column) == 'none')
						document.getElementById(column).className = 'inactive';
					else
						document.getElementById(column).className = 'active';
				}
				else
				{
					$('.'+column).css('display', 'table-cell');
					localStorage.setItem(column, 'table-cell');
					if (localStorage.getItem(column) == 'none')
						document.getElementById(column).className = 'inactive';
					else
						document.getElementById(column).className = 'active';
				}
			}

			function LoadStudienplan(type)
			{
				if(typeof type=='undefined')
					type='';

				var studiengang_kz = $('#studiengang_dropdown'+type).val();
				var studiensemester_kurzbz = $('#studiensemester_dropdown'+type).val();
				
				$.ajax({
					url: "reihungstestverwaltung_autocomplete.php",
					data: { 'autocomplete':'getStudienplan',
							'stg_kz':studiengang_kz,
							'studiensemester_kurzbz': studiensemester_kurzbz
						 },
					type: "POST",
					dataType: "json",
					success: function(data) 
					{
						$("#studienplan_dropdown"+type).empty();
						$("#studienplan_dropdown"+type).append('<option value="">Studienplan auswaehlen</option>');
						$.each(data, function(i, data){
							if (typeof data['sto_bezeichnung']!='undefined')
								$("#studienplan_dropdown"+type).append('<option value="" disabled>Studienordnung: '+data['sto_bezeichnung']+'</option>');

							$("#studienplan_dropdown"+type).append('<option value="'+data['stpid']+'">'+data['bezeichnung']+'</option>');
						});
					},
					error: function(data) 
					{
						alert("Fehler beim Laden der Daten: "+data);
					}
				});
			}
		</script>
		<style type="text/css">
		.active
		{
			cursor: pointer; 
			color: #000000; 
			margin-right: 5px; 
			text-decoration: none; 
			border-radius: 3px; 
			-webkit-border-radius: 3px; 
			-moz-border-radius: 3px; 
			background-color: #8dbdd8; 
			border-top: 3px solid #8dbdd8; 
			border-bottom: 3px solid #8dbdd8; 
			border-right: 8px solid #8dbdd8; 
			border-left: 8px solid #8dbdd8; 
			display: inline-block;
		}
		.inactive
		{
			cursor: pointer; 
			color: #000000; 
			margin-right: 5px; 
			text-decoration: none; 
			border-radius: 3px; 
			-webkit-border-radius: 3px; 
			-moz-border-radius: 3px; 
			background-color: #DCE4EF; 
			border-top: 3px solid #DCE4EF; 
			border-bottom: 3px solid #DCE4EF; 
			border-right: 8px solid #DCE4EF; 
			border-left: 8px solid #DCE4EF; 
			display: inline-block;
		}
		.buttongreen, a.buttongreen
		{
			cursor: pointer; 
			color: #FFFFFF; 
			margin: 0 5px 5px 0; 
			text-decoration: none; 
			border-radius: 3px; 
			-webkit-border-radius: 3px; 
			-moz-border-radius: 3px; 
			background-color: #5cb85c; 
			border-top: 3px solid #5cb85c; 
			border-bottom: 3px solid #5cb85c; 
			border-right: 8px solid #5cb85c; 
			border-left: 8px solid #5cb85c; 
			display: inline-block;
			vertical-align: middle;
		}
		.buttonorange, a.buttonorange
		{
			cursor: pointer; 
			color: #FFFFFF; 
			margin: 0 5px 5px 0; 
			text-decoration: none; 
			border-radius: 3px; 
			-webkit-border-radius: 3px; 
			-moz-border-radius: 3px; 
			background-color: #EC971F; 
			border-top: 3px solid #EC971F; 
			border-bottom: 3px solid #EC971F; 
			border-right: 8px solid #EC971F; 
			border-left: 8px solid #EC971F; 
			display: inline-block;
			vertical-align: middle;
		}
		.listitem
		{
			background-color: lightgray;
			padding: 0 5px 0 6px; 
			border-radius: 3px; 
			-webkit-border-radius: 3px; 
			-moz-border-radius: 3px; 
			vertical-align: middle;
		}
		.feldtitel
		{
			text-align: right;
			padding-right: 5px;
		}
		input
		{
			padding-left: 6px;
		}
		</style>
	</head>
	<body class="Background_main">
		<h2>Reihungstest - Verwaltung</h2>
<?php

// Speichern eines Termines
if(isset($_POST['speichern']) || isset($_POST['kopieren']))
{

	if(!$rechte->isBerechtigt('lehre/reihungstest', null, 'sui'))
	{
		die($rechte->errormsg);
	}

	$reihungstest = new reihungstest();

	if(isset($_POST['reihungstest_id']) && $_POST['reihungstest_id']!='' && !isset($_POST['kopieren']))
	{
		//Reihungstest laden
		if(!$reihungstest->load($_POST['reihungstest_id']))
		{
			die($reihungstest->errormsg);
		}

		$reihungstest->new = false;
	}
	else
	{
		//Neuen Reihungstest anlegen
		$reihungstest->new=true;
		$reihungstest->insertvon = $user;
		$reihungstest->insertamum = date('Y-m-d H:i:s');
	}

	//Datum und Uhrzeit pruefen
	if($_POST['datum']!='' && !$datum_obj->checkDatum($_POST['datum']))
	{
		echo '<span class="input_error">Datum ist ungueltig. Das Datum muss im Format DD.MM.JJJJ eingegeben werden<br></span>';
		$error = true;
	}
	if($_POST['uhrzeit']!='' && !$datum_obj->checkUhrzeit($_POST['uhrzeit']))
	{
		echo '<span class="input_error">Uhrzeit ist ungueltig. Die Uhrzeit muss im Format HH:MM angegeben werden!<br></span>';
		$error = true;
	}

	if(!$error)
	{
		if (isset($_POST['kopieren']))
		{
			
			$reihungstest->freigeschaltet = false;
			$reihungstest->max_teilnehmer = '';
			$reihungstest->oeffentlich = false;
			$reihungstest->stufe = filter_input(INPUT_POST, 'stufe', FILTER_VALIDATE_INT);
			$reihungstest->anmeldefrist = $datum_obj->formatDatum($_POST['anmeldefrist']);
			$reihungstest->updateamum = date('Y-m-d H:i:s');
			$reihungstest->updatevon = $user;
		}
		else
		{
			$reihungstest->freigeschaltet = isset($_POST['freigeschaltet']);
			$reihungstest->max_teilnehmer = filter_input(INPUT_POST, 'max_teilnehmer', FILTER_VALIDATE_INT);
			$reihungstest->oeffentlich = filter_input(INPUT_POST, 'oeffentlich', FILTER_VALIDATE_BOOLEAN);
			$reihungstest->stufe = filter_input(INPUT_POST, 'stufe', FILTER_VALIDATE_INT);
			$reihungstest->anmeldefrist = $datum_obj->formatDatum($_POST['anmeldefrist']);
			$reihungstest->updateamum = date('Y-m-d H:i:s');
			$reihungstest->updatevon = $user;
		}
		$reihungstest->studiengang_kz = $_POST['studiengang_kz'];
		//$reihungstest->ort_kurzbz = $_POST['ort_kurzbz'];
		$reihungstest->studiensemester_kurzbz = filter_input(INPUT_POST, 'studiensemester_kurzbz');
		$reihungstest->anmerkung = $_POST['anmerkung'];
		$reihungstest->datum = $datum_obj->formatDatum($_POST['datum']);
		$reihungstest->uhrzeit = $_POST['uhrzeit'];
		

		if($reihungstest->save())
		{
			if (isset($_POST['ort_kurzbz']) && $_POST['ort_kurzbz']!='')
			{
				$ort = new ort();
				
				if (!$ort->load($_POST['ort_kurzbz']))
					echo '<span class="input_error">Die Bezeichnung des Ortes ist ungueltig oder wurde nicht gefunden</span>';
				else 
				{
					if($rechte->isBerechtigt('lehre/reihungstestOrt', null, 'sui'))
					{
						$orte_zugeteilt = new reihungstest();
						$orte_zugeteilt->getOrteReihungstest($reihungstest->reihungstest_id);
						$zugeteilt = false;
						foreach ($orte_zugeteilt->result AS $row)
						{
							if ($row->ort_kurzbz == $_POST['ort_kurzbz'])
							{
								$zugeteilt = true;
								break;
							}
						}
						// Check, ob der Raum schon diesem RT zugeteilt ist
						if ($zugeteilt == false)
						{
							$add_ort = new reihungstest();
							$add_ort->new = true;
							$add_ort->rt_id = $reihungstest->reihungstest_id;
							$add_ort->ort_kurzbz = $_POST['ort_kurzbz'];
							$add_ort->uid = null;
							
							if ($add_ort->saveOrtReihungstest())
							{
								echo '<b>Daten wurden erfolgreich gespeichert</b> <script>window.opener.StudentReihungstestDropDownRefresh();</script>';
							}
							else
								echo '<span class="input_error">Fehler beim Speichern der Raumzuordnung: '.$db->convert_html_chars($reihungstest->errormsg).'</span>';
						}
						else 
							echo '<span class="input_error">Der Raum '.$_POST['ort_kurzbz'].' ist bereits diesem Reihungstest zugeteilt</span>';
					}
					else 
						die($rechte->errormsg);
				}
			}
			if (isset($_POST['studienplan_id']) && $_POST['studienplan_id']!='')
			{
				/*$rt_stpl = new reihungstest();
				$rt_stpl->getOrteReihungstest($reihungstest->reihungstest_id);
				$zugeteilt = false;
				foreach ($orte_zugeteilt->result AS $row)
				{
					if ($row->ort_kurzbz == $_POST['ort_kurzbz'])
					{
						$zugeteilt = true;
						break;
					}
				}
				// Check, ob der Raum schon diesem RT zugeteilt ist
				if ($zugeteilt == false)
				{*/
					$rt_stpl = new reihungstest();
					$rt_stpl->new = true;
					$rt_stpl->rt_id = $reihungstest->reihungstest_id;
					$rt_stpl->studienplan_id = $_POST['studienplan_id'];
					
					if ($rt_stpl->saveStudienplanReihungstest())
					{
						echo '<b>Daten wurden erfolgreich gespeichert</b> <script>window.opener.StudentReihungstestDropDownRefresh();</script>';
					}
					else
						echo '<span class="input_error">Fehler beim Speichern des Studienplans: '.$db->convert_html_chars($rt_stpl->errormsg).'</span>';
				/*}
				else 
					echo '<span class="input_error">Der Raum '.$_POST['ort_kurzbz'].' ist bereits diesem Reihungstest zugeteilt</span>';*/
			}
			$reihungstest_id = $reihungstest->reihungstest_id;
			$stg_kz = $reihungstest->studiengang_kz;
			$studiensemester_kurzbz = $reihungstest->studiensemester_kurzbz;
		}
		else
		{
			echo '<span class="input_error">Fehler beim Speichern der Daten: '.$db->convert_html_chars($reihungstest->errormsg).'</span>';
		}
	}
	$neu=false;
}

if ($reihungstest_id != '' || isset($_POST['reihungstest_id']))
{
	$orte = new Reihungstest();
	$orte->getOrteReihungstest($reihungstest_id != ''?$reihungstest_id:$_POST['reihungstest_id']);
	$orte_array = array();
	foreach ($orte->result AS $row)
	{
		// Wenn Arbeitsplaetze in DB gepflegt sind, Schwund herausrechnen (wenn gesetzt) sonst max_person verwenden und Schwund herausrechnen (wenn gesetzt)
		$raum = new Ort();
		$raum->load($row->ort_kurzbz);
		if ($raum->arbeitsplaetze != '')
		{
			if(defined('REIHUNGSTEST_ARBEITSPLAETZE_SCHWUND') || REIHUNGSTEST_ARBEITSPLAETZE_SCHWUND > 0)
				$orte_array[$row->ort_kurzbz] = $raum->arbeitsplaetze - ceil(($raum->arbeitsplaetze/100)*REIHUNGSTEST_ARBEITSPLAETZE_SCHWUND);
				else
					$orte_array[$row->ort_kurzbz] = $raum->arbeitsplaetze;
		}
		else
		{
			if(defined('REIHUNGSTEST_ARBEITSPLAETZE_SCHWUND') || REIHUNGSTEST_ARBEITSPLAETZE_SCHWUND > 0)
				$orte_array[$row->ort_kurzbz] = $raum->max_person - ceil(($raum->max_person/100)*REIHUNGSTEST_ARBEITSPLAETZE_SCHWUND);
				else
					$orte_array[$row->ort_kurzbz] = $raum->max_person;
		}
	}
	$arbeitsplaetze_gesamt = array_sum($orte_array);
}

if(isset($_POST['raumzuteilung_speichern']))
{
	if(!$rechte->isBerechtigt('lehre/reihungstest', null, 'su'))
	{
		die($rechte->errormsg);
	}
	
	$raumzuteilung = new reihungstest();
	
	if(isset($_POST['reihungstest_id']) && $_POST['reihungstest_id']!='')
	{
		//Reihungstest laden
		if(!$raumzuteilung->load($_POST['reihungstest_id']))
		{
			die($raumzuteilung->errormsg);
		}
		if (isset($_POST['checkbox']))
		{
			$person_ids = $_POST['checkbox'];
	
			foreach ($person_ids AS $key=>$value)
			{
				//Pruefen ob Person schon Zuteilung in tbl_rt_person hat @todo: Kann weggelassen werden, wenn sichergestellt ist, dass das schon uebers FAS passiert
				$checkperson = new Reihungstest();
				$checkperson->getReihungstestPerson($key);
				foreach ($checkperson->result as $row)
				{
					if ($row->rt_id == $_POST['reihungstest_id'])
					{
						$raumzuteilung->new = false;
						break;
					}
					else
						$raumzuteilung->new = true;	
				}
				
				$load_person = new reihungstest();
				if ($load_person->getPersonReihungstest($key, $_POST['reihungstest_id']))
				{
					$raumzuteilung->new = false;
					$raumzuteilung->rt_person_id = $load_person->rt_person_id;
					$raumzuteilung->anmeldedatum = $load_person->anmeldedatum;
					$raumzuteilung->teilgenommen = $load_person->teilgenommen;
					$raumzuteilung->punkte = $load_person->punkte;
					$raumzuteilung->studienplan_id = $load_person->studienplan_id;
					
					$raumzuteilung->rt_id = $load_person->rt_id;
					$raumzuteilung->rt_id_old = $load_person->rt_id;
					$raumzuteilung->person_id = $key;
					$raumzuteilung->ort_kurzbz = $_POST['raumzuteilung'];
				}
				else 
				{
					$raumzuteilung->new = true;
					$raumzuteilung->anmeldedatum = date('Y-m-d H:i:s');
					$raumzuteilung->teilgenommen = false;
					$raumzuteilung->punkte = 0;
					$raumzuteilung->studienplan_id = '1'; //@todo: Falls noch kein rt_person-Eintrag vorhanden ist, muss ein Studienplan ermittelt werden. Kann das vorkommen? Wenn ja, welchen Studienplan sollen wir nehmen?
						
					$raumzuteilung->rt_id = $_POST['reihungstest_id'];
					$raumzuteilung->rt_id_old = $_POST['reihungstest_id'];
					$raumzuteilung->person_id = $key;
					$raumzuteilung->ort_kurzbz = $_POST['raumzuteilung'];
				}
				
				
				
				if (!$raumzuteilung->savePersonReihungstest())
				{
					echo '<span class="input_error">Fehler beim Speichern der Daten: '.$db->convert_html_chars($reihungstest->errormsg).'</span>';
				}
			}
		}
		$reihungstest_id = $_POST['reihungstest_id'];
	}
	$neu=false;
}

// Uebertraegt die Punkte eines Prestudenten ins FAS
if(isset($_GET['type']) && $_GET['type']=='savertpunkte')
{
	$prestudent = new prestudent();
	$prestudent->load($prestudent_id);

	if($rechte->isBerechtigt('admin') || $rechte->isBerechtigt('assistenz', $prestudent->studiengang_kz, 'suid'))
	{
		$prestudent->rt_punkte1 = str_replace(',','.',$rtpunkte);
		$prestudent->punkte = str_replace(',','.',$prestudent->rt_punkte1 + $prestudent->rt_punkte2);
		$prestudent->reihungstestangetreten=true;
		$prestudent->save(false);
	}
	else
	{
		echo '<span class="input_error"><br>Sie haben keine Berechtigung zur Uebernahme der Punkte fuer '.$db->convert_html_chars($row->nachname).' '.$db->convert_html_chars($row->vorname).'</span>';
	}
}

// Uebertraegt alle Punkte eines Reihungstests ins FAS
if(isset($_GET['type']) && $_GET['type']=='saveallrtpunkte')
{
	$errormsg='';
	$qry = "SELECT prestudent_id, tbl_prestudent.studiengang_kz, nachname, vorname, tbl_studiengang.oe_kurzbz
			FROM public.tbl_prestudent JOIN public.tbl_person USING(person_id) JOIN public.tbl_studiengang USING(studiengang_kz)
			WHERE reihungstest_id=".$db->db_add_param($reihungstest_id, FHC_INTEGER);
	// AND (rt_punkte1='' OR rt_punkte1 is null)";
	if($result = $db->db_query($qry))
	{
		while($row = $db->db_fetch_object($result))
		{
			if($rechte->isBerechtigt('student/stammdaten', $row->oe_kurzbz,'suid'))
			{
				$prestudent = new prestudent();
				$prestudent->load($row->prestudent_id);

				$pruefling = new pruefling();
				if(defined('FAS_REIHUNGSTEST_PUNKTE') && FAS_REIHUNGSTEST_PUNKTE)
					$rtpunkte = $pruefling->getReihungstestErgebnis($row->prestudent_id,true);
				else
					$rtpunkte = $pruefling->getReihungstestErgebnis($row->prestudent_id);

				$prestudent->rt_punkte1 = str_replace(',','.',$rtpunkte);
				$prestudent->punkte = str_replace(',','.',$prestudent->rt_punkte1 + $prestudent->rt_punkte2);
				$prestudent->reihungstestangetreten=true;

				$prestudent->save(false);
			}
			else
			{
				$errormsg .= "<br>Sie haben keine Berechtigung zur Uebernahme der Punkte fuer $row->nachname $row->vorname";
			}
		}
		if($errormsg!='')
		{
			echo '<span class="input_error">'.$db->convert_html_chars($errormsg).'</span>';
		}
	}
}

// Verteilt alle BewerberInnen gleichmaessig auf die Raeume
if(isset($_GET['type']) && $_GET['type']=='verteilen')
{
	if(!$rechte->isBerechtigt('lehre/reihungstest', null, 'sui'))
	{
		die($rechte->errormsg);
	}
	
	if($reihungstest_id!='')
	{
		$errormsg='';
		$qry = "SELECT 
				person_id,
				vorname,
				nachname,
				ort_kurzbz
				FROM public.tbl_prestudent
				JOIN public.tbl_person USING (person_id)
				LEFT JOIN public.tbl_rt_person USING (person_id)
				WHERE reihungstest_id = ".$db->db_add_param($reihungstest_id, FHC_INTEGER)."
				/*AND tbl_rt_person.ort_kurzbz IS NULL*/
				ORDER BY nachname,vorname ";
		
		$raumzuteilung = new reihungstest();
		if($result = $db->db_query($qry))
		{
			$anz_personen = $db->db_num_rows($result);
			
			$multiplikator = $anz_personen/$arbeitsplaetze_gesamt;
			foreach ($orte->result AS $ort)
			{
				$counter = 0;
				
				$anz_zugeteilte = new Reihungstest();
				$anz_zugeteilte->getPersonReihungstestOrt($reihungstest_id, $ort->ort_kurzbz);
				$anz_zugeteilte = count($anz_zugeteilte->result);
				
				$anteil = round(($orte_array[$ort->ort_kurzbz] * $multiplikator))-$anz_zugeteilte;
				
				//if ($orte_array[$ort->ort_kurzbz] == 0 || ($orte_array[$ort->ort_kurzbz]-$anz_zugeteilte)<=0)
				if ($orte_array[$ort->ort_kurzbz] == 0 || ($anteil - $anz_zugeteilte)<=0)
					continue;

				while($row = $db->db_fetch_object($result))
				{
					//Nur Personen ohne Raumzuteilung verteilen
					if ($row->ort_kurzbz == '')
					{
						//Pruefen ob Person schon Zuteilung in tbl_rt_person hat @todo: Kann weggelassen werden, wenn sichergestellt ist, dass das schon uebers FAS passiert
						$checkperson = new Reihungstest();
						$checkperson->getReihungstestPerson($row->person_id);
						if ($checkperson->result)
							$raumzuteilung->new = false;
						else 
						{
							$raumzuteilung->new = true;
						}
						$load_person = new reihungstest();
						if ($load_person->getPersonReihungstest($row->person_id, $reihungstest_id))
						{
							$raumzuteilung->new = false;
							$raumzuteilung->rt_person_id = $load_person->rt_person_id;
							$raumzuteilung->anmeldedatum = $load_person->anmeldedatum;
							$raumzuteilung->teilgenommen = $load_person->teilgenommen;
							$raumzuteilung->punkte = $load_person->punkte;
							$raumzuteilung->studienplan_id = $load_person->studienplan_id;
								
							$raumzuteilung->rt_id = $load_person->rt_id;
							$raumzuteilung->rt_id_old = $load_person->rt_id;
							$raumzuteilung->person_id = $row->person_id;
							$raumzuteilung->ort_kurzbz = $ort->ort_kurzbz;
						}
						else
						{
							$raumzuteilung->new = true;
							$raumzuteilung->anmeldedatum = date('Y-m-d H:i:s');
							$raumzuteilung->teilgenommen = false;
							$raumzuteilung->punkte = 0;
							$raumzuteilung->studienplan_id = '1'; //@todo: Falls noch kein rt_person-Eintrag vorhanden ist, muss ein Studienplan ermittelt werden. Kann das vorkommen? Wenn ja, welchen Studienplan sollen wir nehmen?
							
							$raumzuteilung->rt_id = $reihungstest_id;
							$raumzuteilung->rt_id_old = $reihungstest_id;
							$raumzuteilung->person_id = $row->person_id;
							$raumzuteilung->ort_kurzbz = $ort->ort_kurzbz;
						}
						if (!$raumzuteilung->savePersonReihungstest())
						{
							echo '<span class="input_error">Fehler beim Speichern der Daten: '.$db->convert_html_chars($raumzuteilung->errormsg).'</span>';
						}
						$counter++;
						
						//Wenn 0 Arbeitsplaetze vorhanden sind oder die max. Arbeitsplatzanzahl erreicht ist
						if ($orte_array[$ort->ort_kurzbz] == 0 || ($anteil - $counter)<=0)
							break;
						
						/*if ($counter==$pers_pro_raum || $counter==$arbeitsplaetze)
							break;*/
					}
				}
			}
		}
		
	}
	$neu=false;
}

// Fuellt die Raeume aufsteigend mit BewerberInnen an
if(isset($_GET['type']) && $_GET['type']=='auffuellen')
{
	if(!$rechte->isBerechtigt('lehre/reihungstest', null, 'sui'))
	{
		die($rechte->errormsg);
	}
	
	if($reihungstest_id!='')
	{
		$orte = new Reihungstest();
		$orte->getOrteReihungstest($reihungstest_id);
	
		$errormsg='';
		$qry = "SELECT 
				person_id,
				vorname,
				nachname,
				ort_kurzbz
				FROM public.tbl_prestudent
				JOIN public.tbl_person USING (person_id)
				LEFT JOIN public.tbl_rt_person USING (person_id)
				WHERE reihungstest_id = ".$db->db_add_param($reihungstest_id, FHC_INTEGER)."
				AND tbl_rt_person.ort_kurzbz IS NULL
				ORDER BY nachname,vorname ";
		
		$raumzuteilung = new reihungstest();
		if($result = $db->db_query($qry))
		{
			//$anz_personen = $db->db_num_rows($result);
			//$pers_pro_raum = ceil($anz_personen/$anz_orte);
			foreach ($orte->result AS $ort)
			{
				$counter = 0;
				
				$anz_zugeteilte = new Reihungstest();
				$anz_zugeteilte->getPersonReihungstestOrt($reihungstest_id, $ort->ort_kurzbz);
				$anz_zugeteilte = count($anz_zugeteilte->result);				
				
				if ($orte_array[$ort->ort_kurzbz] == 0 || ($orte_array[$ort->ort_kurzbz]-$anz_zugeteilte)<=0)
					continue;
				
				while($row = $db->db_fetch_object($result))
				{
					//Pruefen ob Person schon Zuteilung in tbl_rt_person hat @todo: Kann weggelassen werden, wenn sichergestellt ist, dass das schon uebers FAS passiert
					$checkperson = new Reihungstest();
					$checkperson->getReihungstestPerson($row->person_id);
					if ($checkperson->result)
						$raumzuteilung->new = false;
					else 
					{
						$raumzuteilung->new = true;
					}
					$load_person = new reihungstest();
					if ($load_person->getPersonReihungstest($row->person_id, $reihungstest_id))
					{
						$raumzuteilung->new = false;
						$raumzuteilung->rt_person_id = $load_person->rt_person_id;
						$raumzuteilung->anmeldedatum = $load_person->anmeldedatum;
						$raumzuteilung->teilgenommen = $load_person->teilgenommen;
						$raumzuteilung->punkte = $load_person->punkte;
						$raumzuteilung->studienplan_id = $load_person->studienplan_id;
					
						$raumzuteilung->rt_id = $load_person->rt_id;
						$raumzuteilung->rt_id_old = $load_person->rt_id;
						$raumzuteilung->person_id = $row->person_id;
						$raumzuteilung->ort_kurzbz = $ort->ort_kurzbz;
					}
					else
					{
						$raumzuteilung->new = true;
						$raumzuteilung->anmeldedatum = date('Y-m-d H:i:s');
						$raumzuteilung->teilgenommen = false;
						$raumzuteilung->punkte = 0;
						
						$raumzuteilung->rt_id = $reihungstest_id;
						$raumzuteilung->rt_id_old = $reihungstest_id;
						$raumzuteilung->person_id = $row->person_id;
						$raumzuteilung->ort_kurzbz = $ort->ort_kurzbz;
					}
					if (!$raumzuteilung->savePersonReihungstest())
					{
						echo '<span class="input_error">Fehler beim Speichern der Daten: '.$db->convert_html_chars($raumzuteilung->errormsg).'</span>';
					}
					$counter++;
					
					//Wenn 0 Arbeitsplaetze vorhanden sind oder die max. Arbeitsplatzanzahl erreicht ist
					if ($orte_array[$ort->ort_kurzbz] == 0 || ($orte_array[$ort->ort_kurzbz]-($anz_zugeteilte+$counter))<=0)
						break;
				}
			}
		}
		
	}
	$neu=false;
}

if(isset($_POST['aufsicht']) && $_POST['aufsicht']!='' && !isset($_POST['kopieren']))
{

	if(!$rechte->isBerechtigt('lehre/reihungstest', null, 'su'))
	{
		die($rechte->errormsg);
	}

	$save_aufsicht = new reihungstest();

	if(isset($_POST['reihungstest_id']) && $_POST['reihungstest_id']!='')
	{
		//Reihungstest laden
		if(!$save_aufsicht->load($_POST['reihungstest_id']))
		{
			die($save_aufsicht->errormsg);
		}
		$aufsichtspersonen = $_POST['aufsicht'];

		foreach ($aufsichtspersonen AS $key=>$value)
		{
			// UID aus POST-String auslesen
			$length = (strrpos($value, ')')) - (strpos($value, '('));
			$uid = substr($value,strpos($value, '(')+1, $length-1);
			
			$benutzer = new benutzer();			
			if ($uid!='' && !$benutzer->load($uid))
				echo '<span class="input_error">Die UID '.$value.' konnte nicht gefunden werden</span>';
			else 
			{
				$save_aufsicht->new = false;
				$save_aufsicht->rt_id = $_POST['reihungstest_id'];
				$save_aufsicht->ort_kurzbz = $key;
				$save_aufsicht->uid = $uid;
				if (!$save_aufsicht->saveOrtReihungstest())
				{
					echo '<span class="input_error">Fehler beim Speichern der Daten: '.$db->convert_html_chars($reihungstest->errormsg).'</span>';
				}
			}
		}
		$reihungstest_id = $save_aufsicht->reihungstest_id;
		$stg_kz = $save_aufsicht->studiengang_kz;
	}
	$neu=false;
}

if(isset($_POST['delete_ort']))
{
	if(!$rechte->isBerechtigt('lehre/reihungstestOrt', null, 'suid'))
	{
		die($rechte->errormsg);
	}

	if(isset($_POST['reihungstest_id']) && $_POST['reihungstest_id']!='')
	{
		$delete_ort = new reihungstest();
		$delete_ort->getPersonReihungstestOrt($_POST['reihungstest_id'], $_POST['delete_ort']);
		
		if (count($delete_ort->result) == 0)
		{
			if (!$delete_ort->deleteOrtReihungstest($_POST['reihungstest_id'], $_POST['delete_ort']))
				echo '<span class="input_error">Fehler beim löschen der Raumzuordnung: '.$db->convert_html_chars($reihungstest->errormsg).'</span>';
		}
		else 
			echo '<span class="input_error">Dem Raum '.$_POST['delete_ort'].' sind noch '.count($delete_ort->result).' Personen zugeteilt. Bitte entfernen Sie zuerst diese Zuteilungen</span>';
		
		
		
		$reihungstest_id = $_POST['reihungstest_id'];
		$studiensemester_kurzbz = $_POST['studiensemester_kurzbz'];
	}
	$neu=false;
}

if(isset($_POST['delete_studienplan'])) //@todo: Check, ob Zuordnungen zu diesem Studienplan vorhanden sind. Wenn ja, nicht loeschen! 
{
	if(isset($_POST['reihungstest_id']) && $_POST['reihungstest_id']!='')
	{
		$delete_studienplan = new reihungstest();

		if (!$delete_studienplan->deleteStudienplanReihungstest($_POST['reihungstest_id'], $_POST['delete_studienplan']))
			echo '<span class="input_error">Fehler beim löschen der Studienplanzuteilung: '.$db->convert_html_chars($delete_studienplan->errormsg).'</span>';

		$reihungstest_id = $_POST['reihungstest_id'];
		$studiensemester_kurzbz = $_POST['studiensemester_kurzbz'];
	}
	$neu=false;
}
//var_dump($_POST);

echo '<table width="100%"><tr><td>';

// Studiengang DropDown
echo "<SELECT name='studiengang' onchange='window.location.href=this.value'>";
if($stg_kz==-1)
	$selected='selected';
else
	$selected='';

echo "<OPTION value='".$_SERVER['PHP_SELF']."?stg_kz=-1&studiensemester_kurzbz=".$studiensemester_kurzbz."' $selected>Alle Studiengaenge</OPTION>";
foreach ($studiengang->result as $row)
{
	$stg_arr[$row->studiengang_kz] = $row->kuerzel;
	if($stg_kz=='')
		$stg_kz=$row->studiengang_kz;
	if($row->studiengang_kz==$stg_kz)
		$selected='selected';
	else
		$selected='';

	echo "<OPTION value='".$_SERVER['PHP_SELF']."?stg_kz=$row->studiengang_kz&studiensemester_kurzbz=$studiensemester_kurzbz' $selected>".$db->convert_html_chars($row->kuerzel)."</OPTION>"."\n";
}
echo "</SELECT>";
$studienplan_obj = new studienplan();
$studienplan_obj->getStudienplaeneFromSem($stg_kz, $studiensemester_kurzbz);
$studienordnung_arr = array();
$studienplan_arr = array();
$studienplaene_verwendet = array();

foreach($studienplan_obj->result as $row_sto)
{
	$studienordnung_arr[$row_sto->studienordnung_id]['bezeichnung']=$row_sto->bezeichnung_studienordnung;
	$studienplan_arr[$row_sto->studienordnung_id][$row_sto->studienplan_id]['bezeichnung']=$row_sto->bezeichnung_studienplan;

	$studienplan_arr[$row_sto->studienordnung_id][$row_sto->studienplan_id]['orgform_kurzbz']=$row_sto->orgform_kurzbz;
	$studienplan_arr[$row_sto->studienordnung_id][$row_sto->studienplan_id]['sprache']=$sprachen_arr[$row_sto->sprache];
	$studienplaene_verwendet[$row_sto->studienplan_id] = $row_sto->bezeichnung_studienplan;
}

// Pruefen ob uebergebene StudienplanID in Auswahl enthalten
// ist und ggf auf leer setzen
if($studienplan_id!='')
{
	$studienplan_found=false;
	foreach($studienplan_arr as $stoid=>$row_sto)
	{
		if(array_key_exists($studienplan_id, $studienplan_arr[$stoid]))
		{
			$studienplan_found=true;
			break;
		}
	}
	if(!$studienplan_found)
	{
		$studienplan_id='';
	}
}
/*
// Studienplan DropDown
echo "<SELECT name='studienplan' onchange='window.location.href=this.value'>";

echo "<OPTION value='".$_SERVER['PHP_SELF']."?studienplan_id='>Studienplan auswaehlen</OPTION>";


foreach($studienordnung_arr as $stoid=>$row_sto)
{
	$selected='';

	echo '<option value="" disabled>Studienordnung: '.$db->convert_html_chars($row_sto['bezeichnung']).'</option>';

	foreach ($studienplan_arr[$stoid] as $stpid=>$row_stp)
	{
		$selected='';
		if($studienplan_id=='')
			$studienplan_id=$stpid;
		if($stpid == $studienplan_id)
			$selected='selected';
	
		//echo "<OPTION value='".$_SERVER['PHP_SELF']."?stg_kz=$row->studiengang_kz&studiensemester_kurzbz=$studiensemester_kurzbz' $selected>".$db->convert_html_chars($row->kuerzel)."</OPTION>"."\n";
		echo '<option value="?stg_kz='.$stg_kz.'&studiensemester_kurzbz='.$studiensemester_kurzbz.'&studienplan_id='.$stpid.'" '.$selected.'>'.$db->convert_html_chars($row_stp['bezeichnung']).' ('.$orgform_arr[$row_stp['orgform_kurzbz']].', '.$row_stp['sprache'].') </option>';
	}
}
echo "</SELECT>";
*/
// Studiensemester DropDown
echo "<SELECT name='studiensemester' onchange='window.location.href=this.value'>";

echo "<OPTION value='".$_SERVER['PHP_SELF']."?stg_kz=".$stg_kz."&studiensemester_kurzbz='>Alle Studiensemester</OPTION>";
foreach ($studiensemester->studiensemester as $row)
{
	if($row->studiensemester_kurzbz == $studiensemester_kurzbz)
		$selected='selected';
	else
		$selected='';

	echo '<OPTION value="'.$_SERVER['PHP_SELF'].'?studiensemester_kurzbz='.$row->studiensemester_kurzbz.'&stg_kz='.$stg_kz.'&reihungstest_id='.$reihungstest_id.'" '.$selected.'>'.$db->convert_html_chars($row->studiensemester_kurzbz).'</OPTION>'.'\n';
}
echo "</SELECT>";

//Reihungstest DropDown
$reihungstest = new reihungstest();
if($stg_kz==-1 && $studiensemester_kurzbz=='')
	$reihungstest->getAll(date('Y').'-01-01'); //Alle Reihungstests ab diesem Jahr laden
elseif($stg_kz==-1 && $studiensemester_kurzbz!='')
	$reihungstest->getReihungstest('','datum DESC,uhrzeit DESC',$studiensemester_kurzbz);
else
	$reihungstest->getReihungstest($stg_kz,'datum DESC,uhrzeit DESC',$studiensemester_kurzbz);
	

echo "<SELECT name='reihungstest' id='reihungstest' onchange='window.location.href=this.value'>";
foreach ($reihungstest->result as $row)
{
	//if($reihungstest_id=='')
	//	$reihungstest_id=$row->reihungstest_id;
	if($row->reihungstest_id==$reihungstest_id)
		$selected='selected';
	elseif ($selected=='' && $reihungstest_id=='' && $row->datum==date('Y-m-d'))
		$selected='selected';
	else
		$selected='';

	echo '<OPTION value="'.$_SERVER['PHP_SELF'].'?stg_kz='.$stg_kz.'&reihungstest_id='.$row->reihungstest_id.'&studiensemester_kurzbz='.$studiensemester_kurzbz.'" '.$selected.'>'.$db->convert_html_chars($row->datum.' '.$datum_obj->formatDatum($row->uhrzeit,'H:i').' '.$studiengang->kuerzel_arr[$row->studiengang_kz].' '.$row->ort_kurzbz.' '.$row->anmerkung).'</OPTION>';
	echo "\n";
}
echo '</SELECT>';
echo "<button onclick='window.location.href=document.getElementById(\"reihungstest\").value;'>Anzeigen</button>";
echo '&nbsp;&nbsp;<button onclick="window.location.href=\''.$_SERVER['PHP_SELF'].'?stg_kz='.$stg_kz.'&neu=true\'" >Neuen Termin anlegen</button>';
echo "</td></tr></table>";

if($reihungstest_id=='')
	$neu=true;
$reihungstest = new reihungstest();

if(!$neu)
{
	if(!$reihungstest->load($reihungstest_id))
		die('Reihungstest existiert nicht');
}
else
{
	if($stg_kz!=-1 && $stg_kz!='')
		$reihungstest->studiengang_kz = $stg_kz;
	$reihungstest_id='';
	$reihungstest->datum = date('Y-m-d');
	$reihungstest->uhrzeit = date('H:i:s');
	$reihungstest->anmeldefrist = date('Y-m-d', time() - 60 * 60 * 24);
}

$studienplaene_arr = array();
$studienplaene = new reihungstest();
$studienplaene->getStudienplaeneReihungstest($reihungstest->reihungstest_id);
foreach ($studienplaene->result AS $row)
{
	$studienplan = new studienplan();
	$studienplan->loadStudienplan($row->studienplan_id);
	$studienplaene_arr[ $row->studienplan_id] = $studienplan->bezeichnung;
}

$studienplaene_list = implode(',', array_keys($studienplaene_arr));

//Formular zum Bearbeiten des Reihungstests
?>
<hr>
<form id='rt_form' method='POST' action='<?php echo $_SERVER['PHP_SELF'] ?>'>
<input type='hidden' value='<?php echo $reihungstest->reihungstest_id ?>' name='reihungstest_id' />

	<table>
		<tr>
		<td style="vertical-align: top">
		<table>
		<tr>
			<td class="feldtitel">Studiengang</td>
			<td>
				<select id='studiengang_dropdown' name='studiengang_kz' onchange='LoadStudienplan()'>
				<?php if($reihungstest->studiengang_kz)
						$selected = '';
					else
						$selected = 'selected'; ?>

				<option value='' <?php echo $selected ?>>-- keine Auswahl --</option>
					<?php foreach ($studiengang->result as $row)
					{
						if($row->studiengang_kz==$reihungstest->studiengang_kz)
							$selected = 'selected';
						else
							$selected = ''; ?>

						<option value="<?php echo $row->studiengang_kz ?>" <?php echo $selected ?>><?php echo $db->convert_html_chars($row->kuerzel).' ('.$db->convert_html_chars($row->bezeichnung) . ')' ?></option>
					<?php } ?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="feldtitel">Stufe</td>
			<td>
				<select name='stufe'>
				<option value=''>-- keine Auswahl --</option>
					<?php for($i=1; $i<=3; $i++)
					{
						if($reihungstest->stufe==$i)
							$selected = 'selected="selected"';
						else
							$selected = ''; ?>

						<option value="<?php echo $i ?>" <?php echo $selected ?>><?php echo $i ?></option>
					<?php } ?>
				</select>
				&nbsp;&nbsp;Studiensemester
				<select id='studiensemester_dropdown' name='studiensemester_kurzbz'>
				<option value=''>-- keine Auswahl --</option>
					<?php 
						foreach ($studiensemester->studiensemester as $row)
						{
							if($row->studiensemester_kurzbz == $studiensemester_kurzbz)
								$selected='selected';
							else
								$selected='';
						
							echo '<OPTION value="'.$row->studiensemester_kurzbz.'" '.$selected.'>'.$db->convert_html_chars($row->studiensemester_kurzbz).'</OPTION>'.'\n';
						}
					?>
				</select>
			</td>
		</tr>
		<?php 
		if($neu)
		{
			echo '<tr>';
			echo '<td class="feldtitel">Studienplan</td>';
			echo '<td>';
			
			// Studienplan DropDown
			echo "<SELECT id='studienplan_dropdown' name='studienplan_id'>";
			
			echo "<OPTION value=''>Studienplan auswaehlen</OPTION>";
			// Pruefen ob uebergebene StudienplanID in Auswahl enthalten
			// ist und ggf auf leer setzen
			if($studienplan_id!='')
			{
				$studienplan_found=false;
				foreach($studienplan_arr as $stoid=>$row_sto)
				{
					if(array_key_exists($studienplan_id, $studienplan_arr[$stoid]))
					{
						$studienplan_found=true;
						break;
					}
				}
				if(!$studienplan_found)
				{
					$studienplan_id='';
				}
			}
			foreach($studienordnung_arr as $stoid=>$row_sto)
			{
				$selected='';
			
				echo '<option value="" disabled>Studienordnung: '.$db->convert_html_chars($row_sto['bezeichnung']).'</option>';
			
				foreach ($studienplan_arr[$stoid] as $stpid=>$row_stp)
				{
					$selected='';
					if($studienplan_id=='')
						$studienplan_id=$stpid;
					if($stpid == $studienplan_id)
						$selected='selected';
				
					echo '<option value="'.$stpid.'" '.$selected.'>'.$db->convert_html_chars($row_stp['bezeichnung']).' ('.$orgform_arr[$row_stp['orgform_kurzbz']].', '.$row_stp['sprache'].') </option>';
				}
			}
			echo "</SELECT>";
			echo '</td></tr>';
		}
		else 
		{		
			echo '<td class="feldtitel">Studienpläne</td>';
			
			if(!$neu)
			{
				//echo '<td><table>';
				echo '<td>';
				echo '<input id="studienplan_id" type="hidden" name="studienplan_id" value="">';
				echo '<input id="studienplan_autocomplete" type="text" name="studienplan" size="40" placeholder="Weiterer Studienplan" value="">';
				echo '<button type="submit" name="speichern"><img src="../../skin/images/list-add.png" alt="Studienplan hinzufügen" height="13px"></button></td>';
				echo '</tr>';
				
				foreach ($studienplaene->result AS $row)
				{
					$studienplan = new studienplan();
					$studienplan->loadStudienplan($row->studienplan_id);
					
					echo '<tr><td>&nbsp;</td>';
					echo '<td class="listitem">'.$studienplan->bezeichnung.' ('.$studienplan->studienplan_id.')</td>';
					echo '<td><button type="submit" name="delete_studienplan" value="'.$row->studienplan_id.'"><img src="../../skin/images/delete_x.png" alt="Studienplan entfernen" height="13px"></button></td>';
					echo '</tr>';
				}
				//echo '</table></td>';
			}
			else 
				echo '<td colspan="2">Nach dem Anlegen eines Termins, können Sie weitere Studienpläne zuordnen</td>';
		}

		$arbeitsplaetze_sum = 0;
		if(!$neu)
		{
			echo '<tr><td class="feldtitel">Ort</td>';
			//echo '<td>';
			if ($rechte->isBerechtigt('lehre/reihungstestOrt', null, 'sui'))
			{
				echo '<td><input id="ort" type="text" name="ort_kurzbz" placeholder="Ort eingeben" value="">';
				echo '<button type="submit" name="speichern"><img src="../../skin/images/list-add.png" alt="Ort hinzufügen" height="13px"></button></td>';
				echo '</td></tr>';
			}
			else 
			{
				echo '<td colspan="2">Keine Berechtigung zum zuteilen von Räumen</td>';
			}
			$orte = new Reihungstest();
			$orte->getOrteReihungstest($reihungstest->reihungstest_id);
			foreach ($orte->result AS $row)
			{
				$person = new Person();
				$person->getPersonFromBenutzer($row->uid);
				if ($row->uid != '')
					$anzeigename = $person->vorname.' '.$person->nachname.' ('.$row->uid.')';
				else 
					$anzeigename = '';

				echo '<tr><td>&nbsp;</td>';
				echo '<td class="listitem">'.$row->ort_kurzbz.' ('.$orte_array[$row->ort_kurzbz].' Personen)';
				echo ' <input type="text" id="aufsicht_'.$row->ort_kurzbz.'" class="aufsicht_uid" name="aufsicht['.$row->ort_kurzbz.']" value="'.$anzeigename.'" placeholder="Aufsichtsperson" size="32">';
				if ($rechte->isBerechtigt('lehre/reihungstestOrt', null, 'suid'))
					echo '</td><td><button type="submit" name="delete_ort" value="'.$row->ort_kurzbz.'"><img src="../../skin/images/delete_x.png" alt="Ort hinzufügen" height="13px"></button>';
				echo '</td></tr>';
				$arbeitsplaetze_sum = $arbeitsplaetze_sum + $orte_array[$row->ort_kurzbz];
			}
			//echo '</table></td>';
		}
		else 
		{
			echo '<tr><td class="feldtitel">Ort</td>';
			echo '<td colspan="2">Nach dem Anlegen eines Termins, können Sie Räume zuordnen</td></tr>';
		}
		?>
		</table>
		</td>
		<td style="padding-left: 20px; vertical-align: top">
		<table>
		<tr>
			<td class="feldtitel">Anmerkung</td>
			<td><input type="text" size="64" maxlength="64" name="anmerkung" value="<?php echo $db->convert_html_chars($reihungstest->anmerkung) ?>"> (max. 64 Zeichen)</td>
		</tr>
		<tr>
			<td class="feldtitel">Datum</td>
			<td><input class="datepicker_datum" type="text" name="datum" value="<?php echo $datum_obj->convertISODate($reihungstest->datum) ?>"></td>
		</tr>
		<tr>
			<td class="feldtitel">Uhrzeit</td>
			<td><input type="text" class="timepicker" name="uhrzeit" value="<?php echo $db->convert_html_chars($datum_obj->formatDatum($reihungstest->uhrzeit,'H:i')) ?>" placeholder="HH:MM"> (Format: HH:MM)</td>
		</tr>
		<tr>
			<td class="feldtitel">Anmeldefrist</td>
			<td><input class="datepicker_datum" type="text" name="anmeldefrist" value="<?php echo $datum_obj->convertISODate($reihungstest->anmeldefrist) ?>"></td>
		</tr>
		<tr>
			<td class="feldtitel">Max TeilnehmerInnen</td>
			<td>
				<input type="number" name="max_teilnehmer" id="max_teilnehmer" value="<?php echo ($reihungstest->max_teilnehmer!=''?$reihungstest->max_teilnehmer:'') ?>">
				(optional; <?php echo $arbeitsplaetze_sum; ?> laut Raumkapazität)
			</td>
		</tr>
		<tr>
			<td class="feldtitel">Öffentlich</td>
			<td>
				<input type="hidden" name="oeffentlich" value="0">
				<input type="checkbox" name="oeffentlich" value="1" <?php echo $reihungstest->oeffentlich ? 'checked="checked"' : '' ?>>
				(Für Bewerber sichtbar/auswählbar)
			</td>
		</tr>
		<tr>
			<td class="feldtitel">Freigeschaltet</td>
			<td>
				<input type="checkbox" name="freigeschaltet"<?php echo $reihungstest->freigeschaltet ? 'checked="checked"' : '' ?>>
				(Kurz vor Testbeginn aktivieren)
			</td>
		</tr>
		<!--<tr>
			<td>Plätze</td>
			<td>
				<?php echo $arbeitsplaetze_sum ?> (inkl. Schwund)
			</td>
		</tr>-->
		<tr>
			<td>&nbsp;</td>
		</tr>
		<?php if(!$neu)
				$val = 'Änderung Speichern';
			else
				$val = 'Neu anlegen'; ?>
		<tr>
			<td></td>
			<td>
				<button type="submit" name="speichern"><?php echo $val ?></button>
				<?php if(!$neu)
					echo '<button type="submit" name="kopieren" onclick="return confirm (\'Eine Kopie dieses Tests (ohne Raumzuordnung) erstellen?\')">Kopie erstellen</button>'; ?>
			</td>
		</tr>
		</table>
		</td>
		</tr>
	</table>
</form>

<hr>
<?php
echo '<table width="100%"><tr><td>';
if($reihungstest_id!='')
{
	echo '<a class="buttongreen" href="'.$_SERVER['PHP_SELF'].'?reihungstest_id='.$reihungstest_id.'&excel=true">Excel Export</a>';
	echo '<a class="buttongreen" href="'.$_SERVER['PHP_SELF'].'?reihungstest_id='.$reihungstest_id.'&type=saveallrtpunkte">Punkte ins FAS &uuml;bertragen</a>';
}
echo '<a class="buttongreen" href="../../cis/testtool/admin/auswertung.php?'.($reihungstest_id!=''?"reihungstest=$reihungstest_id":'').'" target="_blank">Auswertung</a>';
echo '<a class="buttonorange" href="reihungstest_zusammenlegung.php">Anmeldungen zusammenlegen</a>';
if($rechte->isBerechtigt('basis/testtool', null, 'suid'))
{
	echo '<a class="buttonorange" href="reihungstest_administration.php">Administration</a><br>';
}
echo '</td></tr>';
echo '</td></tr></table>';
if($reihungstest_id!='')
{
//Liste der Interessenten die zum Reihungstest angemeldet sind
	$qry = "

SELECT 
rt_id,
prestudent_id,
tbl_rt_person.person_id,
vorname,
nachname,
ort_kurzbz,
studienplan_id,
studiengang_kz,
gebdatum,
geschlecht,
rt_punkte1
,(
	SELECT kontakt
	FROM tbl_kontakt
	WHERE kontakttyp = 'email'
		AND person_id = tbl_rt_person.person_id
		AND zustellung = true LIMIT 1
	) AS email
,(
	SELECT ausbildungssemester
	FROM public.tbl_prestudentstatus
	WHERE prestudent_id = tbl_prestudent.prestudent_id
		AND datum = (
			SELECT MAX(datum)
			FROM public.tbl_prestudentstatus
			WHERE prestudent_id = tbl_prestudent.prestudent_id
				AND status_kurzbz = 'Interessent'
			) LIMIT 1
	) AS ausbildungssemester
,(
	SELECT orgform_kurzbz
	FROM public.tbl_prestudentstatus
	WHERE prestudent_id = tbl_prestudent.prestudent_id
		AND datum = (
			SELECT MAX(datum)
			FROM public.tbl_prestudentstatus
			WHERE prestudent_id = tbl_prestudent.prestudent_id
				AND status_kurzbz = 'Interessent'
			) LIMIT 1
	) AS orgform_kurzbz
FROM public.tbl_rt_person
JOIN public.tbl_person USING (person_id)
JOIN public.tbl_prestudent ON (tbl_rt_person.person_id=tbl_prestudent.person_id AND tbl_rt_person.rt_id=tbl_prestudent.reihungstest_id)
WHERE rt_id = ".$db->db_add_param($reihungstest_id, FHC_INTEGER);
if ($studienplaene_list != '')
$qry .= "AND tbl_rt_person.studienplan_id IN (".$studienplaene_list.")";

$qry .= "ORDER BY ort_kurzbz NULLS FIRST,nachname,vorname

/*
SELECT 
prestudent_id,
person_id,
vorname,
nachname,
ort_kurzbz,
studienplan_id,
studiengang_kz,
gebdatum,
geschlecht,
rt_punkte1
,(
	SELECT kontakt
	FROM tbl_kontakt
	WHERE kontakttyp = 'email'
		AND person_id = tbl_prestudent.person_id
		AND zustellung = true LIMIT 1
	) AS email
,(
	SELECT ausbildungssemester
	FROM public.tbl_prestudentstatus
	WHERE prestudent_id = tbl_prestudent.prestudent_id
		AND datum = (
			SELECT MAX(datum)
			FROM public.tbl_prestudentstatus
			WHERE prestudent_id = tbl_prestudent.prestudent_id
				AND status_kurzbz = 'Interessent'
			) LIMIT 1
	) AS ausbildungssemester
,(
	SELECT orgform_kurzbz
	FROM public.tbl_prestudentstatus
	WHERE prestudent_id = tbl_prestudent.prestudent_id
		AND datum = (
			SELECT MAX(datum)
			FROM public.tbl_prestudentstatus
			WHERE prestudent_id = tbl_prestudent.prestudent_id
				AND status_kurzbz = 'Interessent'
			) LIMIT 1
	) AS orgform_kurzbz
FROM public.tbl_rt_person
JOIN public.tbl_person USING (person_id)
JOIN public.tbl_prestudent USING (person_id)
WHERE reihungstest_id = ".$db->db_add_param($reihungstest_id, FHC_INTEGER)."
AND tbl_rt_person.studienplan_id IN (".$studienplaene_list.")
ORDER BY ort_kurzbz NULLS FIRST,nachname,vorname */";
//echo $qry;
$mailto = '';
$result_arr = array();

$orte = new Reihungstest();
$orte->getOrteReihungstest($reihungstest_id);
$orte_zuteilung_array = array();
$orte_zuteilung_array['ohne'] = 0;
foreach ($orte->result AS $row)
	$orte_zuteilung_array[$row->ort_kurzbz] = 0;

if($result = $db->db_query($qry))
{
	while($row = $db->db_fetch_object($result))
	{
		$result_arr[] = $row;
		
		if (is_null($row->ort_kurzbz))
			$orte_zuteilung_array['ohne']++;
		else 
			$orte_zuteilung_array[$row->ort_kurzbz]++;
	}
}

	echo '<table width="100%"><tr><td>';
	echo '<tr><td>';
	echo '<span style="font-size: 9pt">Anzahl: '.$db->db_num_rows($result).'/'.($reihungstest->max_teilnehmer!=''?$reihungstest->max_teilnehmer:$arbeitsplaetze_sum).'</span>';
	echo '</td></tr>';
	echo '<tr><td>';
	echo '<div id="clm_prestudent_id" class="active" onclick="hideColumn(\'clm_prestudent_id\')">Prestudent ID</div>';
	echo '<div id="clm_person_id" class="active" onclick="hideColumn(\'clm_person_id\')">Person ID</div>';
	echo '<div id="clm_geschlecht" class="active" onclick="hideColumn(\'clm_geschlecht\')">Geschlecht</div>';
	echo '<div id="clm_studiengang" class="active" onclick="hideColumn(\'clm_studiengang\')">Studiengang</div>';
	echo '<div id="clm_orgform" class="active" onclick="hideColumn(\'clm_orgform\')">OrgForm</div>';
	echo '<div id="clm_studienplan" class="active" onclick="hideColumn(\'clm_studienplan\')">Studienplan</div>';
	echo '<div id="clm_einstiegssemester" class="active" onclick="hideColumn(\'clm_einstiegssemester\')">Einstiegssemester</div>';
	echo '<div id="clm_geburtsdatum" class="active" onclick="hideColumn(\'clm_geburtsdatum\')">Geburtsdatum</div>';
	echo '<div id="clm_email" class="active" onclick="hideColumn(\'clm_email\')">EMail</div>';
	echo '<div id="clm_absolviert" class="active" onclick="hideColumn(\'clm_absolviert\')">Absolvierte Tests</div>';
	echo '<div id="clm_ergebnis" class="active" onclick="hideColumn(\'clm_ergebnis\')">Ergebnis</div>';
	echo '<div id="clm_fas" class="active" onclick="hideColumn(\'clm_fas\')">FAS</div>';
	echo '</td></tr></table>';
	echo '<br>';
	echo '<table><tr>';

	
	$pruefling = new pruefling();
	
	$cnt = 0;
	if ($orte_zuteilung_array['ohne']>0)
	{
		echo '<td style="vertical-align: top">';
		echo '<div style="text-align: center; padding: 0 0 5px 0"><b>Ohne Raumzuteilung ('.$orte_zuteilung_array['ohne'].')</b></div>';
		echo '<div align="center"><a class="buttonorange" href="'.$_SERVER['PHP_SELF'].'?reihungstest_id='.$reihungstest_id.'&type=verteilen" onclick="return confirm(\'BewerberInnen gleichmaeßig auf alle Raeume verteilen?\');">Gleichmäßig verteilen</a>';
		echo '<a class="buttonorange" href="'.$_SERVER['PHP_SELF'].'?reihungstest_id='.$reihungstest_id.'&type=auffuellen" onclick="return confirm(\'Die Räume werden ansteigend mit BewerbeInnen aufgefuellt\');">Auffüllen</a></div>';
		echo '<form id="raumzuteilung_form[ohne]" method="POST" action="'.$_SERVER['PHP_SELF'].'">';
		echo '<input type="hidden" value="'.$reihungstest->reihungstest_id.'" name="reihungstest_id">';
		echo '<table class="tablesorter" id="t'.$cnt.'">
				<thead>
				<tr class="liste">
					<th style="text-align: center">
					<nobr>
						<a href="#" data-toggle="checkboxes" data-action="toggle" id="toggle_t'.$cnt.'"><img src="../../skin/images/checkbox_toggle.png" name="toggle"></a>
						<a href="#" data-toggle="checkboxes" data-action="uncheck" id="uncheck_t'.$cnt.'"><img src="../../skin/images/checkbox_uncheck.png" name="toggle"></a>
					</nobr>
					</th>
					<th style="display: table-cell" class="clm_prestudent_id" title="PrestudentID">Prestudent ID</th>
					<th style="display: table-cell" class="clm_person_id" title="PersonID">Person ID</th>
					<th>Nachname</th>
					<th>Vorname</th>
					<th style="display: table-cell" class="clm_geschlecht">Geschlecht</th>
					<th style="display: table-cell" class="clm_studiengang">Studiengang</th>
 					<th style="display: table-cell" class="clm_orgform">OrgForm</th>
					<th style="display: table-cell" class="clm_studienplan">Studienplan</th>
					<th style="display: table-cell" class="clm_einstiegssemester">Einstiegssemester</th>
					<th style="display: table-cell" class="clm_geburtsdatum">Geburtsdatum</th>
					<th style="display: table-cell" class="clm_email">EMail</th>
					<th style="display: table-cell" class="clm_absolviert">bereits absolvierte Verfahren</th>
					<th style="display: table-cell" class="clm_ergebnis">Ergebnis</th>
					<th style="display: table-cell" class="clm_fas">FAS</th>
				</tr>
				</thead>
				<tbody>';
		foreach ($result_arr AS $row)
		{
			$rt_in_anderen_stg='';
			$rtergebnis = '';
			if(defined('REIHUNGSTEST_ERGEBNISSE_BERECHNEN') && REIHUNGSTEST_ERGEBNISSE_BERECHNEN)
			{
				if(defined('FAS_REIHUNGSTEST_PUNKTE') && FAS_REIHUNGSTEST_PUNKTE)
					$rtergebnis = $pruefling->getReihungstestErgebnis($row->prestudent_id,true);
				else
					$rtergebnis = $pruefling->getReihungstestErgebnis($row->prestudent_id);
				$prestudent = new prestudent();
				$prestudent->getPrestudenten($row->person_id);
				$rt_in_anderen_stg='';
				foreach($prestudent->result as $item)
				{
					if($item->prestudent_id!=$row->prestudent_id)
					{
						if(defined('FAS_REIHUNGSTEST_PUNKTE') && FAS_REIHUNGSTEST_PUNKTE)
							$erg = $pruefling->getReihungstestErgebnis($item->prestudent_id, true);
						else
							$erg = $pruefling->getReihungstestErgebnis($item->prestudent_id);
						if($erg!=0)
						{
							$rt_in_anderen_stg.=number_format($erg,2).' Punkte im Studiengang '.$studiengang->kuerzel_arr[$item->studiengang_kz].'<br>';
						}
	
					}
				}
			}
			if ($row->ort_kurzbz == '')
			{
				echo '
					<tr>
						<td style="text-align: center"><input type="checkbox" class="chkbox" id="checkbox_'.$row->person_id.'" name="checkbox['.$row->person_id.']"></td>
						<td style="display: table-cell" class="clm_prestudent_id">'.$db->convert_html_chars($row->prestudent_id).'</td>
						<td style="display: table-cell" class="clm_person_id">'.$db->convert_html_chars($row->person_id).'</td>
						<td>'.$db->convert_html_chars($row->nachname).'</td>
						<td>'.$db->convert_html_chars($row->vorname).'</td>
						<td style="display: table-cell" class="clm_geschlecht">'.$db->convert_html_chars($row->geschlecht).'</td>
						<td style="display: table-cell" class="clm_studiengang">'.$db->convert_html_chars($stg_arr[$row->studiengang_kz]).'</td>
						<td style="display: table-cell" class="clm_orgform">'.$db->convert_html_chars($row->orgform_kurzbz!=''?$row->orgform_kurzbz:' ').'</td>
						<td style="display: table-cell" class="clm_studienplan">'.$db->convert_html_chars($studienplaene_arr[$row->studienplan_id]).' ('.$row->studienplan_id.')</td>
						<td style="display: table-cell" class="clm_einstiegssemester">'.$db->convert_html_chars($row->ausbildungssemester).'</td>
						<td style="display: table-cell" class="clm_geburtsdatum">'.$db->convert_html_chars($row->gebdatum!=''?$datum_obj->convertISODate($row->gebdatum):' ').'</td>
						<td style="display: table-cell; text-align: center" class="clm_email"><a href="mailto:'.$db->convert_html_chars($row->email).'"><img src="../../skin/images/button_mail.gif" name="mail"></a></td>
						<td style="display: table-cell" class="clm_absolviert">'.$rt_in_anderen_stg.'</td>
						<td style="display: table-cell; align: right" class="clm_ergebnis"">'.($rtergebnis==0?'-':number_format($rtergebnis,2,'.','')).'</td>
						<td style="display: table-cell; align: right" class="clm_fas">'.($rtergebnis!=0 && $row->rt_punkte1==''?'<a href="'.$_SERVER['PHP_SELF'].'?reihungstest_id='.$reihungstest_id.'&stg_kz='.$stg_kz.'&type=savertpunkte&prestudent_id='.$row->prestudent_id.'&rtpunkte='.$rtergebnis.'" >&uuml;bertragen</a>':$row->rt_punkte1).'</td>
					</tr>';

				$mailto.= ($mailto!=''?',':'').$row->email;
			}
		}
		echo '</tbody></table>';
	
		echo '<select name="raumzuteilung">';
		echo '<option value="">Ohne Zuteilung</option>';
	
		foreach ($orte->result AS $item)
		{
			if($item->ort_kurzbz=='')
				$selected = 'selected="selected"';
			else
				$selected = '';
			echo '<option value="'.$item->ort_kurzbz.'" '.$selected.'>'.$item->ort_kurzbz.'</option>';
		}
		echo '</select>';
		echo '<button type="submit" name="raumzuteilung_speichern">Speichern</button>';
		echo '</form>';
		echo '</td>';
	}
	foreach ($orte->result AS $ort)
	{
		$cnt++;
		
		echo '<td style="vertical-align: top">';
		if ($orte_array[$ort->ort_kurzbz] - $orte_zuteilung_array[$ort->ort_kurzbz] < 0)
			$style = 'text-align: center; margin: 0 5px 0 5px; color: red';
		else 
			$style = 'text-align: center; margin: 0 5px 0 5px;';
		echo '<div style="'.$style.'"><b>'.$ort->ort_kurzbz.' ('.$orte_zuteilung_array[$ort->ort_kurzbz].'/'.$orte_array[$ort->ort_kurzbz].')</b></div>';
		
		if ($orte_zuteilung_array[$ort->ort_kurzbz]>0)
		{
			echo '<form id="raumzuteilung_form['.$ort->ort_kurzbz.']" method="POST" action="'.$_SERVER['PHP_SELF'].'">';
			echo '<input type="hidden" value="'.$reihungstest->reihungstest_id.'" name="reihungstest_id">';
			echo '<table class="tablesorter" id="t'.$cnt.'">
					<thead>
					<tr class="liste">
						<th style="text-align: center">
						<nobr>
							<a href="#" data-toggle="checkboxes" data-action="toggle" id="toggle_t'.$cnt.'"><img src="../../skin/images/checkbox_toggle.png" name="toggle"></a>
							<a href="#" data-toggle="checkboxes" data-action="uncheck" id="uncheck_t'.$cnt.'"><img src="../../skin/images/checkbox_uncheck.png" name="toggle"></a>
						</nobr>
						</th>
						<th style="display: table-cell" class="clm_prestudent_id" title="PrestudentID">Prestudent ID</th>
						<th style="display: table-cell" class="clm_person_id" title="PersonID">Person ID</th>
						<th>Nachname</th>
						<th>Vorname</th>
						<th style="display: table-cell" class="clm_geschlecht">Geschlecht</th>
						<th style="display: table-cell" class="clm_studiengang">Studiengang</th>
	 					<th style="display: table-cell" class="clm_orgform">OrgForm</th>
						<th style="display: table-cell" class="clm_studienplan">Studienplan</th>
						<th style="display: table-cell" class="clm_einstiegssemester">Einstiegssemester</th>
						<th style="display: table-cell" class="clm_geburtsdatum">Geburtsdatum</th>
						<th style="display: table-cell" class="clm_email">EMail</th>
						<th style="display: table-cell" class="clm_absolviert">bereits absolvierte Verfahren</th>
						<th style="display: table-cell" class="clm_ergebnis">Ergebnis</th>
						<th style="display: table-cell" class="clm_fas">FAS</th>
					</tr>
					</thead>
					<tbody>';
			$cnt_personen = 0;
			foreach ($result_arr AS $row)
			{
				$rt_in_anderen_stg='';
				$rtergebnis = '';
				if(defined('REIHUNGSTEST_ERGEBNISSE_BERECHNEN') && REIHUNGSTEST_ERGEBNISSE_BERECHNEN)
				{
					if(defined('FAS_REIHUNGSTEST_PUNKTE') && FAS_REIHUNGSTEST_PUNKTE)
						$rtergebnis = $pruefling->getReihungstestErgebnis($row->prestudent_id,true);
					else
						$rtergebnis = $pruefling->getReihungstestErgebnis($row->prestudent_id);
					$prestudent = new prestudent();
					$prestudent->getPrestudenten($row->person_id);
					$rt_in_anderen_stg='';
					foreach($prestudent->result as $item)
					{
						if($item->prestudent_id!=$row->prestudent_id)
						{
							if(defined('FAS_REIHUNGSTEST_PUNKTE') && FAS_REIHUNGSTEST_PUNKTE)
								$erg = $pruefling->getReihungstestErgebnis($item->prestudent_id, true);
							else
								$erg = $pruefling->getReihungstestErgebnis($item->prestudent_id);
							if($erg!=0)
							{
								$rt_in_anderen_stg.=number_format($erg,2).' Punkte im Studiengang '.$studiengang->kuerzel_arr[$item->studiengang_kz].'<br>';
							}
		
						}
					}
				}
				if ($row->ort_kurzbz == $ort->ort_kurzbz)
				{
					$cnt_personen++;
					echo '
						<tr>
							<td style="text-align: center"><input class="chkbox" type="checkbox" id="checkbox_'.$row->person_id.'" name="checkbox['.$row->person_id.']"></td>
							<td style="display: table-cell" class="clm_prestudent_id">'.$db->convert_html_chars($row->prestudent_id).'</td>
							<td style="display: table-cell" class="clm_person_id">'.$db->convert_html_chars($row->person_id).'</td>
							<td>'.$db->convert_html_chars($row->nachname).'</td>
							<td>'.$db->convert_html_chars($row->vorname).'</td>
							<td style="display: table-cell" class="clm_geschlecht">'.$db->convert_html_chars($row->geschlecht).'</td>
							<td style="display: table-cell" class="clm_studiengang">'.$db->convert_html_chars($stg_arr[$row->studiengang_kz]).'</td>
							<td style="display: table-cell" class="clm_orgform">'.$db->convert_html_chars($row->orgform_kurzbz!=''?$row->orgform_kurzbz:' ').'</td>
							<td style="display: table-cell" class="clm_studienplan">'.$db->convert_html_chars($studienplaene_arr[$row->studienplan_id]).' ('.$row->studienplan_id.')</td>
							<td style="display: table-cell" class="clm_einstiegssemester">'.$db->convert_html_chars($row->ausbildungssemester).'</td>
							<td style="display: table-cell" class="clm_geburtsdatum">'.$db->convert_html_chars($row->gebdatum!=''?$datum_obj->convertISODate($row->gebdatum):' ').'</td>
							<td style="display: table-cell; text-align: center" class="clm_email"><a href="mailto:'.$db->convert_html_chars($row->email).'"><img src="../../skin/images/button_mail.gif" name="mail"></a></td>
							<td style="display: table-cell" class="clm_absolviert">'.$rt_in_anderen_stg.'</td>
							<td style="display: table-cell; align: right" class="clm_ergebnis"">'.($rtergebnis==0?'-':number_format($rtergebnis,2,'.','')).'</td>
							<td style="display: table-cell; align: right" class="clm_fas">'.($rtergebnis!=0 && $row->rt_punkte1==''?'<a href="'.$_SERVER['PHP_SELF'].'?reihungstest_id='.$reihungstest_id.'&stg_kz='.$stg_kz.'&type=savertpunkte&prestudent_id='.$row->prestudent_id.'&rtpunkte='.$rtergebnis.'" >&uuml;bertragen</a>':$row->rt_punkte1).'</td>
						</tr>';
		
					$mailto.= ($mailto!=''?',':'').$row->email;
				}
			}
			if (1==0)
			{
				echo '
					<tr>
						<td style="text-align: center">-</td>
						<td style="display: table-cell" class="clm_prestudent_id" title="PrestudentID">-</td>
						<td style="display: table-cell" class="clm_person_id" title="PersonID">-</td>
						<td>-</td>
						<td>-</td>
						<td style="display: table-cell" class="clm_geschlecht">-</td>
						<td style="display: table-cell" class="clm_studiengang">-</td>
						<td style="display: table-cell" class="clm_orgform">-</td>
						<td style="display: table-cell" class="clm_studienplan">-</td>
						<td style="display: table-cell" class="clm_einstiegssemester">-</td>
						<td style="display: table-cell" class="clm_geburtsdatum">-</td>
						<td style="display: table-cell; align: center" class="clm_email">-</td>
						<td style="display: table-cell" class="clm_absolviert">-</td>
						<td style="display: table-cell; align: right" class="clm_ergebnis"">-</td>
						<td style="display: table-cell; align: right" class="clm_fas">-</td>
					</tr>';
			}
			echo '</tbody></table>';
			
			echo '<select name="raumzuteilung">';
			echo '<option value="">Zuteilung entfernen</option>';
			
			foreach ($orte->result AS $item)
			{
				if($item->ort_kurzbz==$ort->ort_kurzbz)
					$selected = 'selected="selected"';
				else
					$selected = '';
				echo '<option value="'.$item->ort_kurzbz.'" '.$selected.'>'.$item->ort_kurzbz.'</option>';
			}
			echo '</select>';
			echo '<button type="submit" name="raumzuteilung_speichern">Speichern</button>';
			echo '</form>';
		}
		else 
			echo '<table style="width: 100%; margin: 10px 0pt 15px;"><tr><td style="text-align: center; background: #DCE4EF; border: 1px solid white; padding: 4px; font-size: 8pt"><b>Leer</b></td></tr></table>';
		
		echo '</td>';
	}
	
	
	
	echo '</tr></table>';
	//echo "<span style='font-size: 9pt'><a href='mailto:?bcc=$mailto'>Mail an alle senden</a></span>"; //@todo: Braucht man das noch oder eventuell gleich raumweise?
} ?>

	</body>
</html>
