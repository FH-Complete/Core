<!DOCTYPE html>
<?php
/*
 * Copyright 2014 fhcomplete.org
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 *
 * Authors: Stefan Puraner	<puraner@technikum-wien.at> and
 *          Andreas Moik <moik@technikum-wien.at>.
 */

require_once('../../../../config/cis.config.inc.php');
require_once('../../../../include/functions.inc.php');
require_once('../../../../include/benutzerberechtigung.class.php');
require_once('../../../../include/lehrveranstaltung.class.php');
require_once('../../../../include/konto.class.php');
require_once('../../../../include/studiensemester.class.php');
require_once('../../../../include/student.class.php');
require_once('../../../../include/phrasen.class.php');
require_once('../../../../include/globals.inc.php');
require_once('../../../../include/sprache.class.php');

$sprache = getSprache();
$lang = new sprache();
$lang->load($sprache);
$p = new phrasen($sprache);
//TODO EINE RECHTE?
$uid = get_uid();

$db = new basis_db();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($uid);

$benutzer = new student($uid);

$studiensemester = new studiensemester();
$studiensemester->getAll();

?>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Prüfungsanmeldung</title>
        <script src="../../../../include/js/datecheck.js"></script>
        <script src="../../../../include/js/jquery1.9.min.js"></script>
	<script src="../../../../include/js/jquery.tablesorter.min.js"></script>
        <script src="./pruefung.js.php"></script>
        <link rel="stylesheet" href="../../../../skin/jquery-ui-1.9.2.custom.min.css">
        <link rel="stylesheet" href="../../../../skin/fhcomplete.css">
        <link rel="stylesheet" href="../../../../skin/style.css.php">
        <link rel="stylesheet" href="../../../../skin/tablesort.css">
        <style type="text/css">
            #pruefungen, #prfTermine {
                width: 50%;
            }

            #details {
		width: 50%;
/*                margin-left: 1.5em;*/
            }

	    #lvDetails, #prfDetails {
		min-width: 40%;
		margin-bottom: 1em;
		margin-left: 1.5em;
		float:left;
		/*border: 1px solid black;*/
	    }

/*	    #prfDetails {
		float:right;
	    }*/

	    #accordion {
		width: 60%;
		clear: left;
		clear: right;
	    }

	    .titel {
		font-weight: bold;
	    }

	    #message {
		position: fixed;
		bottom: 0px;
		width: 100%;
		height: 2em;
		font-size: 1.5em;
		font-weight: bold;
	    }

	    .columnheader1 {
		width: 30%;
	    }
	    .columnheader2 {
		width: 30%;
	    }
	    .columnheader3 {
		width: 30%;
	    }
	    .columnheader4 {
		width: 5%;
	    }

	    #accordion p {
		margin: 0;
		height: 24px;
	    }
        </style>

    </head>
    <body>
        <script>
	    var count = 0;
	    $(document).ajaxSend(function(event, xhr, options){
		count++;
	     });

	     $(document).ajaxComplete(function(event, xhr, settings){
		count--;
		//Wenn alle AJAX-Request fertig sind
		if(count===0)
		{
		    $("#accordion").accordion({
			header: "h2",
			autoHeight: false
		    });
		    $("#accordion").attr("style", "visibility: visible;");
		}
	    });

            $(document).ready(function(){
		loadPruefungen();
		loadPruefungenOfStudiengang();
		loadPruefungenGesamt();
		$("#saveDialog").dialog({
		    modal: true,
		    autoOpen: false,
		    width: "auto"
		});
		$("#dialog").dialog({ autoOpen: false });

		$("#details").dialog({
		    modal: true,
		    autoOpen: false,
		    width: "400px"
		});
		$(document).tooltip({
		    position: {
			    at: "right center",
			    my: "left+15 center"
		    }
		});
            });
	    <?php
	     echo '
		function openAnmeldung(lehrveranstaltung_id, stsem)
		{
			$("#dialog").load("../../profile/studienplan.php?getAnmeldung=true&lehrveranstaltung_id="+lehrveranstaltung_id+"&stsem="+stsem+"&uid='.$db->convert_html_chars($uid).'");
			$("#dialog").dialog("open");
		}'
	    ;
	    ?>
        </script>
        <h1><?php echo $p->t('pruefung/anmeldungFuer'); ?> <?php echo $benutzer->vorname." ".$benutzer->nachname." (".$uid.")"; ?></h1>
	<?php
	    echo '<h3>'.$p->t('pruefung/filter').'</h3>';
	    echo '<p>'.$p->t('global/studiensemester').': ';
	    echo '<select id="filter_studiensemester" onchange="refresh();">';
	    $aktuellesSemester = $studiensemester->getaktorNext();
	    foreach($studiensemester->studiensemester as $sem)
	    {
		if($aktuellesSemester == $sem->studiensemester_kurzbz)
		{
		    echo '<option selected value="'.$sem->studiensemester_kurzbz.'">'.$sem->studiensemester_kurzbz.'</option>';
		}
		else
		{
		    echo '<option value="'.$sem->studiensemester_kurzbz.'">'.$sem->studiensemester_kurzbz.'</option>';
		}
	    }
	    echo '</select></p>';

	?>
	<div id="details" title="<?php echo $p->t('pruefung/details'); ?>">
	    <div id="lvDetails">
		<h1><?php echo $p->t('pruefung/lvDetails'); ?></h1>
                <span class="titel"><?php echo $p->t('global/bezeichnung'); ?>: </span><span id="lvBez"></span><br/>
		<span class="titel"><?php echo $p->t('global/ects'); ?>: </span><span id="lvEcts"></span><br/>
            </div>

            <div id="prfDetails">
		<h1><?php echo $p->t('pruefung/pruefungsDetails'); ?></h1>
                <span class="titel"><?php echo $p->t('pruefung/typ'); ?>: </span><span id="prfTyp"></span><br/>
                <span class="titel"><?php echo $p->t('pruefung/pruefungMethode'); ?>: </span><span id="prfMethode"></span><br/>
                <span class="titel"><?php echo $p->t('global/beschreibung'); ?>: </span><span id="prfBeschreibung"></span><br/>
                <span id="prfEinzeln"></span><br/>
		<span class="titel" style="visibility: hidden;"><?php echo $p->t('pruefung/intervall'); ?>: </span><span id="prfIntervall"></span><br/>
            </div>
        </div>
	<div id="message"></div>
	<div id="accordion" style="visibility: hidden;">
	    <h2><?php echo $p->t('pruefung/besuchteLehrveranstaltungen'); ?></h2>
	    <div>
		<table id="table1" class="tablesorter">
		    <thead>
			<tr>
			    <th class="columnheader1"><?php echo $p->t('global/institut'); ?></th>
			    <th class="columnheader2"><?php echo $p->t('global/lehrveranstaltung'); ?></th>
			    <th class="columnheader3"><?php echo $p->t('pruefung/pruefungTermin'); ?></th>
			    <th class="columnheader4"><?php echo $p->t('pruefung/freiePlaetze'); ?></th>
			</tr>
		    </thead>
		    <tbody id="pruefungen">

		    </tbody>
		</table>
	    </div>
            <?php if(!defined('CIS_PRUEFUNGSANMELDUNG_LEHRVERANSTALTUNGEN_AUS_STUDIENGANG') || CIS_PRUEFUNGSANMELDUNG_LEHRVERANSTALTUNGEN_AUS_STUDIENGANG == true): ?>
	    <h2><?php echo $p->t('pruefung/lvVonStudiengang'); ?></h2>
	    <div>
		<table id="table2" class="tablesorter">
		    <thead>
			<tr>
			    <th class="columnheader1"><?php echo $p->t('global/institut'); ?></th>
			    <th class="columnheader2"><?php echo $p->t('global/lehrveranstaltung'); ?></th>
			    <th class="columnheader3"><?php echo $p->t('pruefung/pruefungTermin'); ?></th>
			    <th class="columnheader4"><?php echo $p->t('pruefung/freiePlaetze'); ?></th>
			</tr>
		    </thead>
		    <tbody id="pruefungenStudiengang">

		    </tbody>
		</table>
	    </div>
            <?php endif; ?>
	    <h2><?php echo $p->t('pruefung/lvAlle'); ?></h2>
	    <div>
		<table id="table3" class="tablesorter">
		    <thead>
			<tr>
			    <th class="columnheader1"><?php echo $p->t('global/institut'); ?></th>
			    <th class="columnheader2"><?php echo $p->t('global/lehrveranstaltung'); ?></th>
			    <th class="columnheader3"><?php echo $p->t('pruefung/pruefungTermin'); ?></th>
			    <th class="columnheader4"><?php echo $p->t('pruefung/freiePlaetze'); ?></th>
			</tr>
		    </thead>
		    <tbody id="pruefungenGesamt">

		    </tbody>
		</table>
	    </div>
	</div>
        <div id="saveDialog" title="<?php echo $p->t('pruefung/anmeldungSpeichern'); ?>">
	    <form id="saveAnmeldungForm">
		<table id="neueAnmeldung">
		    <tr>
			<td>&nbsp;</td>
			<td>
			    <input type="hidden" id="lehrveranstaltungHidden" disabled="true">
			    <input type="hidden" id="terminHidden" disabled="true">
			</td>
		    </tr>
		    <tr>
			<td style="vertical-align: top; font-weight: bold;"><?php echo $p->t('global/lehrveranstaltung'); ?>: </td>
			<td>
			    <span id="lehrveranstaltung"></span>
			</td>
		    </tr>
		    <tr>
			<td style="vertical-align: top; font-weight: bold;"><?php echo $p->t('global/von'); ?>: </td>
			<td>

			    <span id="terminVon"></span>
			</td>
		    </tr>
		    <tr>
			<td style="vertical-align: top; font-weight: bold;"><?php echo $p->t('global/bis'); ?>: </td>
			<td>
			    <span type="text" id="terminBis" disabled="true"></span>
			</td>
		    </tr>
		    <?php if(!defined('CIS_PRUEFUNGSANMELDUNG_ANRECHNUNG') || CIS_PRUEFUNGSANMELDUNG_ANRECHNUNG == true): ?>
            <tr>
			<td style="vertical-align: top; font-weight: bold;"><?php echo $p->t('pruefung/studienverpflichtung'); ?>:* </td>
			<td>
			    <select id="studienverpflichtung"></select>
			</td>
		    </tr>
            <?php endif; ?>
		    <tr>
			<td style="vertical-align: top; font-weight: bold;"><?php echo $p->t('global/anmerkung'); ?>: </td>
			<td>
			    <textarea id="anmeldungBemerkung" rows="10" cols="20"></textarea>
			</td>
		    </tr>
		    <tr>
			<td><input type="button" value="<?php echo $p->t('global/anmelden'); ?>" onclick="saveAnmeldung();"></td>
		    </tr>
		</table>
	    </form>
	</div>
	<div id="dialog">
	</div>
    </body>
</html>
