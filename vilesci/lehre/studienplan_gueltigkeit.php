<?php
/* Copyright (C) 2016 fhcomplete.org
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
 * Authors: Andreas Österreicher <andreas.oesterreicher@technikum-wien.at
 */
require_once('../../config/vilesci.config.inc.php');
require_once('../../include/functions.inc.php');
require_once('../../include/benutzerberechtigung.class.php');
require_once('../../include/studiengang.class.php');
require_once('../../include/studienplan.class.php');
require_once('../../include/studiensemester.class.php');

$user = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);

if(!$rechte->isBerechtigt('assistenz', null, 'suid'))
	die('keine Berechtigung für diese Seite!');

$studiengang_kz = isset($_GET['studiengang_kz'])?$_GET['studiengang_kz']:'';
$db = new basis_db();

echo '<!doctype html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link href="../../skin/vilesci.css" rel="stylesheet" type="text/css">';
include('../../include/meta/jquery.php');
include('../../include/meta/jquery-tablesorter.php');

echo '
	<title>Studienplan Übersicht</title>
	<script type="text/javascript">
			$(document).ready(function()
				{
					$("#t1").tablesorter(
					{
						widgets: ["zebra"]
					});
				});
	</script>
</head>
<body>
<h1>Studienplan Gültigkeit</h1>
<form method="GET" action="studienplan_gueltigkeit.php">
<select name="studiengang_kz">
';
$stg = new studiengang();
$stg->getAll('typ, kurzbz');
foreach($stg->result as $row)
{
	if($row->studiengang_kz == $studiengang_kz)
		$selected = 'selected';
	else
		$selected = '';
	echo '<option value="'.$row->studiengang_kz.'" '.$selected.'>'.$db->convert_html_chars($row->kuerzel.' - '.$row->bezeichnung).'</option>';
}
echo '</select>
<input type="submit" value="Anzeigen">
</form>';

$max_semester=0;
if($studiengang_kz!='')
{
	$studienplan = new studienplan();
	$studienplan->getStudienplaeneFromSem($studiengang_kz);

	foreach($studienplan->result as $row)
	{
		if($max_semester < $row->semester)
			$max_semester = $row->semester;
		$gueltigkeit[$row->studiensemester_kurzbz][$row->semester][]=$row->bezeichnung;
	}
}

$studiensemester = new studiensemester();
$studiensemester->getAll();

echo '<table id="t1" class="tablesorter">
<thead>
<tr>
	<th></th>';

for($i = 1; $i <= $max_semester; $i++)
	echo '<th>'.$i.'. Semester</th>';
echo '</tr>
</thead>
<tbody>';
$start=0;
foreach($studiensemester->studiensemester as $row_stsem)
{

	$row= '<tr>
		<td><b>'.$row_stsem->studiensemester_kurzbz.'</b></td>';

	for($i = 1; $i <= $max_semester; $i++)
	{
		$row .= '<td>';
		if(isset($gueltigkeit[$row_stsem->studiensemester_kurzbz][$i]) && is_array($gueltigkeit[$row_stsem->studiensemester_kurzbz][$i]))
		{
			foreach($gueltigkeit[$row_stsem->studiensemester_kurzbz][$i] as $row_studienplan)
			{
				$start=true;
				$row .= $row_studienplan.'<br>';
			}
		}
		$row .= '</td>';
	}
	$row .= '</tr>';

	if($start)
		echo $row;
}
echo '</tbody></table>';
echo '
</body>
</html>';
