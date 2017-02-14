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

require_once('../../config/vilesci.config.inc.php');

?>
var StudentRolleStudiensemester_old;
var StudentRolleAusbildungssemester_old;
var StudentRolleStatusgrundDatasource;

// ****
// * Laedt die Rolle
// ****
function StudentRolleInit(prestudent_id, status_kurzbz, studiensemester_kurzbz, ausbildungssemester)
{
	netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect");
	StudentRolleLoadStatusgrund(status_kurzbz)
	if(status_kurzbz!='')
	{
		document.getElementById('student-rolle-grid-row-textbox').hidden=false;
		document.getElementById('student-rolle-grid-row-menulist').hidden=true;

		StudentRolleStudiensemester_old=studiensemester_kurzbz;
		StudentRolleAusbildungssemester_old=ausbildungssemester;

		//Daten holen
		var url = '<?php echo APP_ROOT ?>rdf/prestudentrolle.rdf.php?prestudent_id='+prestudent_id+'&status_kurzbz='+status_kurzbz+'&studiensemester_kurzbz='+studiensemester_kurzbz+'&ausbildungssemester='+ausbildungssemester+'&'+gettimestamp();

		var rdfService = Components.classes["@mozilla.org/rdf/rdf-service;1"].
	                   getService(Components.interfaces.nsIRDFService);

	    var dsource = rdfService.GetDataSourceBlocking(url);

		var subject = rdfService.GetResource("http://www.technikum-wien.at/prestudentrolle/" + prestudent_id+"/"+status_kurzbz+"/"+studiensemester_kurzbz+"/"+ausbildungssemester);

		var predicateNS = "http://www.technikum-wien.at/prestudentrolle/rdf";

		//RDF parsen
		var datum = getTargetHelper(dsource,subject,rdfService.GetResource( predicateNS + "#datum" ));
		var bestaetigt_datum = getTargetHelper(dsource,subject,rdfService.GetResource( predicateNS + "#bestaetigt_am" ));
		var orgform_kurzbz = getTargetHelper(dsource,subject,rdfService.GetResource( predicateNS + "#orgform_kurzbz" ));
		var studienplan_id = getTargetHelper(dsource,subject,rdfService.GetResource( predicateNS + "#studienplan_id" ));
		var anmerkung= getTargetHelper(dsource,subject,rdfService.GetResource( predicateNS + "#anmerkung" ));
		var statusgrund_id = getTargetHelper(dsource,subject,rdfService.GetResource( predicateNS + "#statusgrund_id" ));
		var rt_stufe = getTargetHelper(dsource,subject,rdfService.GetResource( predicateNS + "#rt_stufe" ));
		var neu = false;
	}
	else
	{
		StudentRolleStudiensemester_old='';
		StudentRolleAusbildungssemester_old='';

		document.getElementById('student-rolle-grid-row-textbox').hidden=true;
		document.getElementById('student-rolle-grid-row-menulist').hidden=false;

		//Defaultwerte bei Neuem Datensatz
		var status_kurzbz = 'Interessent';
		var studiensemester_kurzbz=window.opener.getStudiensemester();
		var ausbildungssemester='1';
		var datum = '<?php echo date('d.m.Y') ?>';
		var bestaetigt_datum = '<?php echo date('d.m.Y') ?>';
		var orgform_kurzbz = '';
		var studienplan_id = '';
		var anmerkung = '';
		var statusgrund_id = '';
		var rt_stufe = '';
	}

	document.getElementById('student-rolle-textbox-prestudent_id').value=prestudent_id;
	document.getElementById('student-rolle-textbox-status_kurzbz').value=status_kurzbz;
	document.getElementById('student-rolle-menulist-studiensemester').value=studiensemester_kurzbz;
	document.getElementById('student-rolle-menulist-ausbildungssemester').value=ausbildungssemester;
	document.getElementById('student-rolle-datum-datum').value=datum;
	document.getElementById('student-rolle-datum-bestaetigt_datum').value=bestaetigt_datum;
	document.getElementById('student-rolle-menulist-orgform_kurzbz').value=orgform_kurzbz;
	MenulistSelectItemOnValue('student-rolle-menulist-studienplan', studienplan_id);
	document.getElementById('student-rolle-textbox-anmerkung').value=anmerkung;
	MenulistSelectItemOnValue('student-rolle-menulist-statusgrund', statusgrund_id);
	MenulistSelectItemOnValue('student-rolle-menulist-stufe', rt_stufe);
}

// ****
// * Speichern der Rolle
// * Hierzu wird eine Funktion vom Aufrufenden Fenster gestartet weil
// * es dann nicht zu Problemen mit den Zugriffen auf die anderen Fkt
// * kommt.
// ****
function StudentRolleSpeichern()
{
	if(window.opener.StudentRolleSpeichern(document, StudentRolleStudiensemester_old, StudentRolleAusbildungssemester_old))
		window.close();
}

function StudentRolleLoadStatusgrund(status_kurzbz)
{
	netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect");
	var menulistgrund = document.getElementById('student-rolle-menulist-statusgrund');
	url='<?php echo APP_ROOT;?>rdf/statusgrund.rdf.php?status_kurzbz='+status_kurzbz+'&ts'+gettimestamp();

	try
	{
		StudentRolleStatusgrundDatasource.removeXMLSinkObserver(StudentDetailRolleTreeSinkObserver);
	}
	catch(e)
	{}

	//Alte DS entfernen
	var oldDatasources = menulistgrund.database.GetDataSources();
	while(oldDatasources.hasMoreElements())
	{
		menulistgrund.database.RemoveDataSource(oldDatasources.getNext());
	}
	//Refresh damit die entfernten DS auch wirklich entfernt werden
	menulistgrund.builder.rebuild();

	var rdfService = Components.classes["@mozilla.org/rdf/rdf-service;1"].getService(Components.interfaces.nsIRDFService);
	StudentRolleStatusgrundDatasource = rdfService.GetDataSourceBlocking(url);
	StudentRolleStatusgrundDatasource.QueryInterface(Components.interfaces.nsIRDFRemoteDataSource);
	StudentRolleStatusgrundDatasource.QueryInterface(Components.interfaces.nsIRDFXMLSink);
	menulistgrund.database.AddDataSource(StudentRolleStatusgrundDatasource);
	menulistgrund.builder.rebuild();
}