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
 */

 
/*
Vorrückung aller AKTIVEN Studenten ins nächste Semester.
*/

 
		require_once('../../config/vilesci.config.inc.php');
		require_once('../../include/basis_db.class.php');
		if (!$db = new basis_db())
				die('Es konnte keine Verbindung zum Server aufgebaut werden.');
			

require_once('../../include/studiengang.class.php');
require_once('../../include/studiensemester.class.php');
require_once('../../include/functions.inc.php');

function myaddslashes($var)
{
	return ($var!=''?"'".addslashes($var)."'":'null');
}

$ausbildungssemester=0;
$s=new studiengang();
$s->getAll('typ, kurzbz', true);
$studiengang=$s->result;

//Einlesen der studiensemester in einen Array
$ss = new studiensemester();
$ss->getAll();
foreach($ss->studiensemester as $studiensemester)
{
	$ss_arr[] = $studiensemester->studiensemester_kurzbz;
}

$user = get_uid();

//Übergabeparameter
if (isset($_GET['stg_kz']) || isset($_POST['stg_kz']))
	$stg_kz=(isset($_GET['stg_kz'])?$_GET['stg_kz']:$_POST['stg_kz']);
else
	$stg_kz=0;
if (isset($_GET['semester']) || isset($_POST['semester']))
	$semester=(isset($_GET['semester'])?$_GET['semester']:$_POST['semester']);
else
	$semester=100;
if (isset($_GET['studiensemester_kurzbz']) || isset($_POST['studiensemester_kurzbz']))
	$studiensemester_kurzbz=(isset($_GET['studiensemester_kurzbz'])?$_GET['studiensemester_kurzbz']:$_POST['studiensemester_kurzbz']);
else
	$studiensemester_kurzbz=null;
if (is_null($studiensemester_kurzbz))
{
	$studiensemester_kurzbz=$ss->getakt();
}
//$studiensemester_kurzbz_akt=$ss->getakt();			//aktuelles Semester
if (isset($_GET['studiensemester_kurzbz_akt']) || isset($_POST['studiensemester_kurzbz_akt']))
	$studiensemester_kurzbz_akt=(isset($_GET['studiensemester_kurzbz_akt'])?$_GET['studiensemester_kurzbz_akt']:$_POST['studiensemester_kurzbz_akt']);
else
	die("Aktuelles Studiensemester wurde nicht übergeben.");
	
/*$ss->getNextStudiensemester();
$studiensemester_kurzbz_zk=$ss->studiensemester_kurzbz;	//nächstes Semester*/
if (isset($_GET['studiensemester_kurzbz_zk']) || isset($_POST['studiensemester_kurzbz_zk']))
	$studiensemester_kurzbz_zk=(isset($_GET['studiensemester_kurzbz_zk'])?$_GET['studiensemester_kurzbz_zk']:$_POST['studiensemester_kurzbz_zk_']);
else
	die("Nächstes Studiensemester wurde nicht übergeben.");

if(!is_numeric($stg_kz))
	$stg_kz=0;
//semester=100 bedeutet die Auswahl aller Semester
if(!is_numeric($semester))
	$semester=100;

//Einlesen der maximalen, regulären Dauer der Studiengänge in einen Array
$qry_stg="SELECT * FROM public.tbl_studiengang";
if ($result_stg=$db->db_query($qry_stg))
{
	while($row_stg=$db->db_fetch_object($result_stg))
	{
		$max[$row_stg->studiengang_kz]=$row_stg->max_semester;
	}
}	
	
//select für die Anzeige
$sql_query="SELECT tbl_student.*,tbl_person.*, tbl_studentlehrverband.semester as semester_stlv,  tbl_studentlehrverband.verband as verband_stlv, 
			tbl_studentlehrverband.gruppe as gruppe_stlv FROM tbl_studentlehrverband JOIN tbl_student USING (student_uid)
				JOIN tbl_benutzer ON (student_uid=uid)
				JOIN tbl_person USING (person_id)
			WHERE tbl_benutzer.aktiv AND tbl_studentlehrverband.studiengang_kz='$stg_kz' 
			AND studiensemester_kurzbz='$studiensemester_kurzbz' ";
if($semester<100)
{
	$sql_query.="AND tbl_studentlehrverband.semester='$semester' "; //semester = 100 wählt alle aus
}
$sql_query.="ORDER BY semester, nachname";

//echo $sql_query;
if (!$result_std=$db->db_query($sql_query))
	error("Studenten not found!");
$outp='';

// ****************************** Vorrücken ******************************
if (isset($_POST['vorr']))
{
//select für die Vorrückung
$sql_query="SELECT tbl_student.*,tbl_person.*, tbl_studentlehrverband.semester as semester_stlv,  tbl_studentlehrverband.verband as verband_stlv, 
			tbl_studentlehrverband.gruppe as gruppe_stlv FROM tbl_studentlehrverband JOIN tbl_student USING (student_uid)
			JOIN tbl_benutzer ON (student_uid=uid)
			JOIN tbl_person USING (person_id)
			WHERE tbl_benutzer.aktiv AND tbl_studentlehrverband.studiengang_kz='$stg_kz' 
			AND studiensemester_kurzbz='$studiensemester_kurzbz_akt'";
	if($semester<100)
	{
		$sql_query.="AND tbl_studentlehrverband.semester='$semester' "; //semester = 100 wählt alle aus
	}
	$sql_query.="ORDER BY semester, nachname";
	
	//echo $sql_query;
	if (!$result_std=$db->db_query($sql_query))
		error("Studenten not found!");
	$next_ss=$studiensemester_kurzbz_zk;
	while($row=$db->db_fetch_object($result_std))
	{
		//aktuelle Rolle laden
		$qry_status="SELECT status_kurzbz,  ausbildungssemester FROM public.tbl_prestudentstatus JOIN public.tbl_prestudent USING(prestudent_id) 
		WHERE person_id=".myaddslashes($row->person_id)." 
		AND studiengang_kz=".$row->studiengang_kz."  
		AND studiensemester_kurzbz=".myaddslashes($studiensemester_kurzbz_akt)." 
		ORDER BY datum desc, tbl_prestudentstatus.insertamum desc, tbl_prestudentstatus.ext_id desc LIMIT 1;";
		if ($result_status=$db->db_query($qry_status))
		{
			if($row_status=$db->db_fetch_object($result_status))
			{
				//Studenten im letzten Semester bleiben dort, wenn aktiv
				if($row->semester_stlv>=$max[$stg_kz] || $row->semester_stlv==0)
				{
					$s=$row->semester_stlv;
				}
				else
				{
					$s=$row->semester_stlv+1;
				}
				if($row_status->ausbildungssemester>=$max[$stg_kz] || $row_status->status_kurzbz=="Unterbrecher")
				{
					$ausbildungssemester=$row_status->ausbildungssemester;
				}
				else 
				{
					$ausbildungssemester=$row_status->ausbildungssemester+1;
				}
				//Lehrverbandgruppe anlegen, wenn noch nicht vorhanden
				$qry_lvb="SELECT * FROM public.tbl_lehrverband 
				WHERE studiengang_kz=".myaddslashes($row->studiengang_kz)." AND semester=".myaddslashes($s)."
				AND verband=".myaddslashes($row->verband_stlv)." AND gruppe=".myaddslashes($row->gruppe_stlv).";";
				if($db->db_num_rows($db->db_query($qry_lvb))<1)
				{
					$lvb_ins="INSERT INTO public.tbl_lehrverband VALUES (".
					myaddslashes($row->studiengang_kz).", ".
					myaddslashes($s).", ".
					myaddslashes($row->verband_stlv).", ".
					myaddslashes($row->gruppe_stlv).", 
					TRUE, NULL, NULL);";
					if (!$r=$db->db_query($lvb_ins))
						die($db->db_last_error());
				}
				//Überprüfen ob Eintrag schon vorhanden
				$qry_chk="SELECT * FROM public.tbl_studentlehrverband 
						WHERE student_uid=".myaddslashes($row->student_uid)." 
						AND studiensemester_kurzbz=".myaddslashes($next_ss)." 
						AND studiengang_kz=".myaddslashes($row->studiengang_kz)."
						AND semester=".$s.";";
				$sql='';
				if($db->db_num_rows($db->db_query($qry_chk))<1)
				{
					//Eintragen der neuen Gruppe
					$sql="INSERT INTO tbl_studentlehrverband
						VALUES ('$row->student_uid','$next_ss','$row->studiengang_kz',
						'$s','$row->verband_stlv','$row->gruppe_stlv',NULL,NULL,now(),'$user',NULL);";
				}
				$qry_chk="SELECT * FROM public.tbl_prestudentstatus
						WHERE prestudent_id=".myaddslashes($row->prestudent_id)." 
						AND studiensemester_kurzbz=".myaddslashes($next_ss).";";
				if($db->db_num_rows($db->db_query($qry_chk))<1)
				{
					//Eintragen des neuen Status
					$sql.="INSERT INTO tbl_prestudentstatus
					VALUES ($row->prestudent_id, '$row_status->status_kurzbz', '$next_ss',
						$ausbildungssemester, now(), now(), '$user',
					NULL, NULL, NULL, NULL);";
				}
				if($sql!='')
				{
					if (!$r=$db->db_query($sql))
					{
						die($db->db_last_error()."<br>".$sql);
					}
				}
			}
		}
	}

}

// **************** Ausgabe vorbereiten ******************************
$s=array();
$outp.="<SELECT name='stg_kz'>";
//Auswahl Studiengang
foreach ($studiengang as $stg)
{
	$outp.="<OPTION onclick=\"window.location.href = '".$_SERVER['PHP_SELF']."?stg_kz=$stg->studiengang_kz&semester=$semester&studiensemester_kurzbz=$studiensemester_kurzbz'\" ".($stg->studiengang_kz==$stg_kz?'selected':'').">$stg->kurzbzlang ($stg->kuerzel) - $stg->bezeichnung</OPTION>";
	//$outp.= '<A href="'.$_SERVER['PHP_SELF'].'?stg_kz='.$stg->studiengang_kz.'&semester='.$semester.'">'.$stg->kuerzel.'</A> - ';
	$s[$stg->studiengang_kz]->max_sem=$stg->max_semester;
	$s[$stg->studiengang_kz]->kurzbz=$stg->kurzbzlang;
}
$outp.='</SELECT>';
//Auswahl Studiensemester
$outp.="<select name='studiensemester_kurzbz'>\n";
foreach ($ss_arr AS $sts)
{
	if ($studiensemester_kurzbz == $sts)
		$sel = " selected ";
	else
		$sel = '';
	$outp.="				<option value='".$sts."' ".$sel."onclick=\"window.location.href = '".$_SERVER['PHP_SELF']."?stg_kz=$stg_kz&semester=$semester&studiensemester_kurzbz=$sts'\">".$sts."</option>";
}
$outp.="		</select>\n";
$outp.="<BR>Vorr&uuml;ckung von ".$studiensemester_kurzbz_akt." / Semester ".($semester<100?$semester:'alle')." -> ".$studiensemester_kurzbz_zk;
$outp.= '<BR> -- ';
for ($i=0;$i<=$s[$stg_kz]->max_sem;$i++)
	$outp.= '<A href="'.$_SERVER['PHP_SELF'].'?stg_kz='.$stg_kz.'&semester='.$i.'&studiensemester_kurzbz='.$studiensemester_kurzbz.'">'.$i.'</A> -- ';
$outp.= '<A href="'.$_SERVER['PHP_SELF'].'?stg_kz='.$stg_kz.'&semester=100&studiensemester_kurzbz='.$studiensemester_kurzbz.'">alle</A> -- ';
//Aufbau Ausgabe
?>
<html>
<head>
<title>Studenten Vorrueckung</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="stylesheet" href="../../skin/vilesci.css" type="text/css">
<link rel="stylesheet" href="../../include/js/tablesort/table.css" type="text/css">
<script src="../../include/js/tablesort/table.js" type="text/javascript"></script>
</head>
<body class="Background_main">
<?php

echo "<H2>Studenten Vorr&uuml;ckung (".$s[$stg_kz]->kurzbz." - ".($semester<100?$semester:'alle')." - ".
	$studiensemester_kurzbz."), DB:".DB_NAME."</H2>";

echo '<form action="" method="POST">';
echo '<table width="70%"><tr><td>';
echo $outp;
echo '</td><td>';
echo '<input type="submit" name="vorr" value="Vorruecken" />';
echo '</td><td>&nbsp;</td></tr></table>';
echo '</form>';

echo "<h3>&Uuml;bersicht</h3>
	<table class='liste table-autosort:2 table-stripeclass:alternate table-autostripe'>
	<thead>
	<tr class='liste'>";

if ($result_std!=0)
{
	$num_rows=pg_num_rows($result_std);
	echo 'Anzahl: '.$num_rows;
	echo "<th class='table-sortable:default'>Nachname</th><th class='table-sortable:default'>Vorname</th><th class='table-sortable:default'>STG</th><th class='table-sortable:default'>Sem</th><th class='table-sortable:default'>Ver</th><th class='table-sortable:default'>Grp</th><th class='table-sortable:default'>Status</th><th class='table-sortable:default'>AusbSem</th>\n";
	echo "</tr></thead>";
	echo "<tbody>";
	for($i=0;$i<$num_rows;$i++)
	{
		$row=pg_fetch_object($result_std,$i);
		$qry_status="SELECT status_kurzbz, ausbildungssemester FROM public.tbl_prestudentstatus 
			JOIN public.tbl_prestudent USING(prestudent_id) WHERE person_id=".myaddslashes($row->person_id)." 
			AND studiengang_kz=".$row->studiengang_kz."  
			AND studiensemester_kurzbz=".myaddslashes($studiensemester_kurzbz)." 
			ORDER BY datum desc, tbl_prestudentstatus.insertamum desc, tbl_prestudentstatus.ext_id desc LIMIT 1;";
		if ($result_status=$db->db_query($qry_status))
		{
			if($row_status=$db->db_fetch_object($result_status))
			{
				echo "<tr>";
				echo "<td>$row->nachname</td><td>$row->vorname</td><td>$row->studiengang_kz</td><td>$row->semester_stlv</td><td>$row->verband_stlv</td><td>$row->gruppe_stlv</td><td>$row_status->status_kurzbz</td><td>$row_status->ausbildungssemester</td>";
				echo "</tr>\n";
			}
			else 
			{
				echo "<tr>";
				echo "<td>$row->nachname</td><td>$row->vorname</td><td>$row->studiengang_kz</td><td>$row->semester_stlv</td><td>$row->verband_stlv</td><td>$row->gruppe_stlv</td><td></td><td></td>";
				echo "</tr>\n";
			}
		}
		else 
		{
			error("Roles not found!");	
		}
	}
}
else
	echo "Kein Eintrag gefunden!";
?>
</tbody>
</table>

<br>
</body>
</html>