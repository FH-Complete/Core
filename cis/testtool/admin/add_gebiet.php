<?php
/* Copyright (C) 2009 Technikum-Wien
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
 *          Andreas Oesterreicher <andreas.oesterreicher@technikum-wien.at>,
 *          Rudolf Hangl <rudolf.hangl@technikum-wien.at> and
 *			Gerald Simane-Sequens <gerald.simane-sequens@technikum-wien.at>
 */
/**
 * Seite zum Editieren von Testtool-Gebieten
 */

require_once('../../../config/cis.config.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/gebiet.class.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/studiengang.class.php');
require_once('../../../include/sprache.class.php');

if (!$user=get_uid())
	die('Sie sind nicht angemeldet. Es wurde keine Benutzer UID gefunden ! <a href="javascript:history.back()">Zur&uuml;ck</a>');

$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);

$sprache = new sprache();
$sprache->getAll(true);

echo '
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link href="../../../skin/style.css.php" rel="stylesheet" type="text/css">
	<link href="../../../skin/tablesort.css" rel="stylesheet" type="text/css">
	<script type="text/javascript" src="../../../include/js/jquery1.9.min.js" ></script>
	<script type="text/javascript">
    $(document).ready(function()
    {
        $("#t1").tablesorter(
        {
            sortList: [[0,0]],
            widgets: ["zebra"]
        });
    });

	function deleteZuordnung(ablauf_id)
	{
		if(confirm("Wollen Sie dieses Zuordnung wirklich entfernen?"))
        {
            $("#data").html(\'<form action="edit_gebiet.php" name="sendform" id="sendform" method="POST"><input type="hidden" name="action" value="deleteZuordnung" /><input type="hidden" name="ablauf_id" value="\'+ablauf_id+\'" /></form>\');
			document.sendform.submit();
        }
        return false;
	}

    </script>
</head>
<body>
<div id="data"></div>
';

$stg_kz = (isset($_GET['stg_kz'])?$_GET['stg_kz']:'-1');
$gebiet = new gebiet();

echo '<h1>&nbsp;Gebiet hinzuf&uuml;gen</h1>';

if(!$rechte->isBerechtigt('basis/testtool'))
	die($rechte->errormsg);

$studiengang = new studiengang();
$studiengang->getAll('typ, kurzbz', false);

echo '<a href="index.php?stg_kz='.$stg_kz.'" class="Item">Zurück zur Admin Seite</a><br /><br />';

//Dropdown Auswahl Studiengang
echo "Studiengang: <SELECT name='studiengang' id='studiengang' onchange='window.location.href=this.value'><OPTION value='-1'>-- Keine Auswahl --</OPTION>";
$i=0; $selected='';
for ($i=0; $i<count($studiengang->result); $i++) {
	if ($stg_kz == $studiengang->result[$i]->studiengang_kz) $selected = 'selected';
	echo "<OPTION value='".$_SERVER['PHP_SELF']."?stg_kz=".$studiengang->result[$i]->studiengang_kz."' ".$selected.">".strtoupper($studiengang->result[$i]->typ.$studiengang->result[$i]->kurzbz).' ('.$studiengang->result[$i]->bezeichnung.")</OPTION>";
	$selected = '';
}
echo "</SELECT><br /><br /><hr />";

echo '
<form action="'.$_SERVER['PHP_SELF'].'" method="POST">
 <table cellspacing="4">
  <tr>
   <td>ID</td>
   <td><input type="text" name="id" disabled value="'.(intval($gebiet->getHighestId())+1).'"/></td>
  </tr>
  <tr>
   <td>Kurzbz</td>
   <td><input type="text" name="kurzbz" placeholder="Pflichtfeld"/></td>
  </tr>
  <tr>
   <td>Bezeichnung German</td>
   <td><input type="text" name="bezeichnung_mehrsprachig_German"/></td>
  </tr>
  <tr>
  <tr>
   <td>Bezeichnung English</td>
   <td><input type="text" name="bezeichnung_mehrsprachig_English"/></td>
  </tr>
  <tr>
   <td>Beschreibung</td>
   <td><textarea rows="" cols="" name="beschreibung"></textarea></td>
  </tr>
  <tr>
   <td>Zeit</td>
   <td><input type="text" name="zeit" placeholder="Pflichtfeld"/> hh:mm:ss</td>
  </tr>
  <tr>
   <td>Multiple Response</td>
   <td><input type="checkbox" name="multiple_respone"/></td>
  </tr>
  <tr>
   <td>Kategorien</td>
   <td><input type="checkbox" name="kategorien"/></td>
  </tr>
  <tr>
   <td>Zuf&auml;llige Fragereihenfolge</td>
   <td><input type="checkbox" name="zufaellige_fragereihenfolge"/></td>
  </tr>
  <tr>
   <td>Zuf&auml;llige Vorschlagreihenfolge</td>
   <td><input type="checkbox" name="zufaellige_vorschlagreihenfolge"/></td>
  </tr>
  <tr>
   <td>Levelgleichverteilung</td>
   <td><input type="checkbox" name="levelgleichverteilung"/></td>
  </tr>
  <tr>
   <td>Maximale Punkteanzahl</td>
   <td><input type="text" name="maximale_punkteanzahl"/></td>
  </tr>
  <tr>
   <td>Maximale Frageanzahl</td>
   <td><input type="text" name="maximale_fragenanzahl"/></td>
  </tr>
  <tr>
   <td>Antworten pro Zeile</td>
   <td><input type="text" name="antworten_pro_zeile" placeholder="Pflichtfeld"/></td>
  </tr>
  <tr>
   <td>Start Level</td>
   <td><input type="text" name="start_level"/></td>
  </tr>
  <tr>
   <td>Richtige Fragen bis Levelaufstieg</td>
   <td><input type="text" name="richtige_fragen_bis_levelaufstieg"/></td>
  </tr>
  <tr>
   <td>Falsche Fragen bis Levelabstieg</td>
   <td><input type="text" name="falsche_fragen_bis_levelabstieg"/></td>
  </tr>
  <tr>
   <td></td>
   <td><input type="submit" value="Speichern"/></td>
  </tr>
 </table>
 <input type="hidden" name="save" value="save"/>
</form>
';

//Speichern der Daten
if (isset($_POST['save']) && $_POST['save']=='save')
{
	/*
	 * kurzbz
	 * zeit
	 * antw/zeile
	 */
	
	if(!$rechte->isBerechtigt('basis/testtool', null, 'suid'))
		die('Sie haben keine Berechtigung fuer diese Aktion');

	if (isset($_POST['kurzbz']) && $_POST['kurzbz']!='' && isset($_POST['zeit']) && $_POST['zeit']!='' && isset($_POST['antworten_pro_zeile']) && $_POST['antworten_pro_zeile']!='')
	{
		$gebiet = new gebiet();
		
		$bezeichnung_mehrsprachig=array();
		foreach($sprache->result as $row_sprache)
		{
			if(isset($_POST['bezeichnung_mehrsprachig_'.$row_sprache->sprache]))
				$bezeichnung_mehrsprachig[$row_sprache->sprache]=$_POST['bezeichnung_mehrsprachig_'.$row_sprache->sprache];
		}
		$gebiet->bezeichnung_mehrsprachig = $bezeichnung_mehrsprachig;
		
		$gebiet->kurzbz = $_POST['kurzbz'];
		$gebiet->bezeichnung = $_POST['bezeichnung_mehrsprachig_German'];
		$gebiet->beschreibung = $_POST['beschreibung'];
		$gebiet->zeit = $_POST['zeit'];
		$gebiet->multipleresponse = isset($_POST['multiple_respone']);
		$gebiet->kategorien = isset($_POST['kategorien']);
		$gebiet->maxfragen = $_POST['maximale_fragenanzahl'];
		$gebiet->zufallfrage = isset($_POST['zufaellige_fragereihenfolge']);
		$gebiet->zufallvorschlag = isset($_POST['zufaellige_vorschlagreihenfolge']);
		$gebiet->levelgleichverteilung = isset($_POST['levelgleichverteilung']);
		$gebiet->maxpunkte = $_POST['maximale_punkteanzahl'];
		$gebiet->level_start = $_POST['start_level'];
		$gebiet->level_sprung_auf = $_POST['richtige_fragen_bis_levelaufstieg'];
		$gebiet->level_sprung_ab = $_POST['falsche_fragen_bis_levelabstieg'];
		$gebiet->insertamum = date('Y-m-d H:i:s');
		$gebiet->insertvon = $user;
		$gebiet->antwortenprozeile = $_POST['antworten_pro_zeile'];
	
		if($gebiet->save(true))
		{
			echo 'Daten erfolgreich gespeichert';
		}
		else
		{
			echo '<span class="error">Fehler beim Speichern: '.$gebiet->errormsg.'</span>';
		}
	}
	else
	{
		echo '<span class="error">Bitte f&uuml;llen Sie alle Pflichtfelder aus</span>';
	}
}

echo '</body></html>';
?>
