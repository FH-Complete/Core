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
	require_once('../../config.inc.php');	
	require_once('../../../include/functions.inc.php');
	require_once('../../../include/news.class.php');
    
    //Connection Herstellen
    if(!$conn = pg_pconnect(CONN_STRING))
       die('Fehler beim oeffnen der Datenbankverbindung');
        
	$user = get_uid();
	
	if(check_lektor($user,$conn))
		$is_lector=true;
    else 
    	$is_lector=false;
		
	if($is_lector)
	{
		if(isset($remove_id) && $remove_id != "")
		{
			$news_obj = new news($conn);
			if($news_obj->delete($remove_id))
			{			
				echo '<script language="JavaScript">';
				echo "	document.location.href = 'pinboard_show.php?course_id=$course_id&term_id=$term_id'";
				echo '</script>';
				exit;
			}
			else 
				echo 'Fehler beim loeschen:'.$news_obj->errormsg;
		}
	}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="../../../skin/cis.css" rel="stylesheet" type="text/css">

<script language="JavaScript">
	
	function deleteEntry(id, course_id, term_id)
	{
		if(confirm("Soll dieser Eintrag wirklich gel�scht werden?") == true)
		{
			document.location.href = 'pinboard_show.php?course_id=' + course_id + '&term_id=' + term_id + '&remove_id=' + id;
		}
	}
	
	function editEntry(id, course_id, term_id)
	{
		parent.news_entry.location.href = 'pinboard_entry.php?course_id=' + course_id + '&term_id=' + term_id + '&news_id=' + id;
	}

</script>
</head>

<body>
<table width="100%"  border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="10">&nbsp;</td>
    <td><table width="100%"  border="0" cellspacing="0" cellpadding="0">
        <tr>
	  	  <?php
		  	if(!$is_lector || !isset($course_id) || !isset($term_id))
				exit;
		  ?>
          <td>
		  	<table width="100%"  border="0" cellspacing="0" cellpadding="0">
			<?php

			$news_obj = new news($conn);
			$news_obj->getnews(MAXNEWSALTER,$course_id, $term_id, true);
			  
			$i=0;
			foreach($news_obj->result as $row)
			{
				$i++;
				echo "<tr>";
				
				if($i % 2 != 0)
					echo '<td class="MarkLine">';
				else
					echo '<td>';
				
				if($row->datum!='')
					$datum = date('d.m.Y',strtotime(strftime($row->datum)));
				else 	
					$datum='';
						
				echo '  <table width="100%"  border="0" cellspacing="0" cellpadding="0">';
				echo '    <tr>';
				echo '      <td nowarp>';
				echo '        <small>'.$datum.'&nbsp;-&nbsp;'.$row->verfasser.'</small>';
				echo '      </td>';
				echo '		<td align="right" nowrap>';
				echo '		  <a onClick="editEntry('.$row->news_id.', '.$row->studiengang_kz.', '.($row->semester==''?0:$row->semester).');">Editieren</a>, <a onClick="deleteEntry('.$row->news_id.', '.$row->studiengang_kz.',' .($row->semester==''?0:$row->semester).');">L&ouml;schen</a>';
				echo '		</td>';
				echo '    </tr>';
				echo '  </table>';
				echo '  <strong>'.$row->betreff.'</strong><br>'.$row->text.'</td>';
				echo "</tr>";
				echo '<tr>';
				echo '  <td>&nbsp;</td>';
				echo '</tr>';
				echo '<tr>';
				echo '  <td>&nbsp;</td>';
				echo '</tr>';
			}

				
					if($i==0)
						echo 'Zur Zeit gibt es keine aktuellen News!';
			  ?>
			</table>
		  </td>
        </tr>
    </table></td>
    <td width="30">&nbsp;</td>
  </tr>
</table>
</body>
</html>