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
		require_once('../../../config/vilesci.config.inc.php');
		require_once('../../../include/basis_db.class.php');
		if (!$db = new basis_db())
			die('Es konnte keine Verbindung zum Server aufgebaut werden.');


	$datum=(isset($_REQUEST['datum']) ? $_REQUEST['datum'] :'' );
	$stunde=(isset($_REQUEST['stunde']) ? $_REQUEST['stunde'] :0 );
	$ort_kurzbz=(isset($_REQUEST['ort_kurzbz']) ? $_REQUEST['ort_kurzbz'] :'' );			

	//Stundenplandaten ermitteln welche mehrfach vorkommen
	$sql_query="SELECT * FROM lehre.vw_reservierung WHERE datum='$datum' AND stunde=$stunde AND ort_kurzbz='$ort_kurzbz'";
	//echo $sql_query."<br>";
	$num_rows=0;
	if ($result=$db->db_query($sql_query))
			$num_rows=$db->db_num_rows($result);
	else
		die($db->db_last_error().' <a href="javascript:history.back()">Zur&uuml;ck</a>');			

$cfgBorder=1;
$cfgBgcolorOne='liste0';
$cfgBgcolorTwo='liste1';
?>

<html>
<head>
<title>Reservierung Check Details</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<LINK rel="stylesheet" href="../../../skin/vilesci.css" type="text/css">
</head>
<body>
<H1>Mehrfachbelegungen in Reservierungen Detailansicht</H1>
<table border="<?php echo $cfgBorder;?>">
<tr>
<?php
if ($num_rows>0)
{
	$num_fields=$db->db_num_fields($result);
	$foo = 0;
	for ($i=0;$i<$num_fields; $i++)
	    echo "<th>".$db->db_field_name($result,$i)."</th>";
	for ($j=0; $j<$num_rows;$j++)
	{
		$row=$db->db_fetch_row($result,$j);
		$bgcolor = $cfgBgcolorOne;
		$foo % 2  ? 0: $bgcolor = $cfgBgcolorTwo;
		echo '<tr class="'.$bgcolor.'">';
	    for ($i=0; $i<$num_fields; $i++)
			echo "<td>$row[$i]&nbsp;</td>";
		echo "<td><a href=\"res_check_delete.php?id=$row[0]\">Delete</a></td>";
		echo "<td><a href=\"res_check_mail.php?id=$row[0]\">Mail&Delete</a></td>";

	    echo "</tr>\n";
		$foo++;
	}
}
else
	echo "Kein Eintrag gefunden!";
?>
</table>
</body>
</html>