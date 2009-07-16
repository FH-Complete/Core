<?php
/* Copyright (C) 2008 Technikum-Wien
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

// ---------------- CIS Include Dateien einbinden
	require_once('../../../config/cis.config.inc.php');
// ---------------- Diverse Funktionen und UID des Benutzers ermitteln
	require_once('../../../include/functions.inc.php');
	if (!$user=get_uid())
		die('Sie sind nicht angemeldet. Es wurde keine Benutzer UID gefunden !');
	require_once('../../../include/datum.class.php');
// ---------------- Classen Datenbankabfragen und Funktionen 
	include_once('../../../include/person.class.php');
	include_once('../../../include/benutzer.class.php');
	include_once('../../../include/benutzerberechtigung.class.php');
	require_once('../../../include/studiengang.class.php');	
// ---------------- News Classe und Allg.Funktionen	
	require_once('../../../include/news.class.php');
	// Init	
	$error='';

	$fachbereich_kurzbz=(isset($_REQUEST['fachbereich_kurzbz']) && !empty($_REQUEST['fachbereich_kurzbz'])?$_REQUEST['fachbereich_kurzbz']:null);
	$studiengang_kz=(isset($_REQUEST['course_id'])?$_REQUEST['course_id']:(isset($_REQUEST['studiengang_kz'])?$_REQUEST['studiengang_kz']:null));
	$semester=(isset($_REQUEST['term_id'])?$_REQUEST['term_id']:(isset($_REQUEST['semester'])?$_REQUEST['semester']:null));

	// Parameter sind nicht korr.
	if(is_null($fachbereich_kurzbz) && (is_null($studiengang_kz) || is_null($semester)) )
		die('Fehlerhafte Parameteruebergabe !');
	
	$rechte = new benutzerberechtigung();
 	$rechte->getBerechtigungen($user);
	if(check_lektor($user))
       	$is_lector=true;
	else
		die('Sie haben keine Berechtigung f&uuml;r diese Seite. !  <a href="javascript:history.back()">Zur&uuml;ck</a>');
		
	
	// Wird nur in den Tools - News fuer Fachbereich verwendet	
	if (!is_null($fachbereich_kurzbz))
	{	
		if($rechte->isBerechtigt('admin') 
		|| $rechte->isBerechtigt('assistenz') 
		|| $rechte->isBerechtigt('lehre') 
		|| $rechte->isBerechtigt('news'))
			$berechtigt=true;
		else
			$berechtigt=false;
		if(!$berechtigt)
			die('Sie haben keine Berechtigung f&uuml;r diese Seite. !  <a href="javascript:history.back()">Zur&uuml;ck</a>');
	}		
	
	
	if (!$stg_obj = new studiengang($studiengang_kz))
		die('Fehler beim Studiengang '.(!empty($studiengang_kz)?$studiengang_kz.' ':'').'lesen ! '.$stg_obj->errormsg);
	else 
		$row_stg_short=(isset($stg_obj->kuerzel)?$stg_obj->kuerzel:'');

	// Open der NEWs-Classe
	$news = new news();
	
	// Parameter einlesen		
	$news_id=trim((isset($_REQUEST['news_id']) ? $_REQUEST['news_id']:''));
	$btnSend=trim((isset($_REQUEST['btnSend']) ? $_REQUEST['btnSend']:''));
	$btnDel=trim((isset($_REQUEST['btnDel']) ? $_REQUEST['btnDel']:''));
	$btnRead=trim((isset($_REQUEST['btnRead']) ? $_REQUEST['btnRead']:''));
	
	// Verarbeiten der Daten
	if (!empty($btnSend))
	{
		if(isset($news_id) && $news_id != "")
			$news->new=false;
		else
			$news->new=true;			

		$news->news_id = $news_id;
		$news->betreff = trim(isset($_REQUEST['betreff']) ? $_REQUEST['betreff']:'');
		$news->verfasser =trim(isset($_REQUEST['verfasser']) ? $_REQUEST['verfasser']:$user);
		$news->text = trim(isset($_REQUEST['text']) ? $_REQUEST['text']:'');

		$news->studiengang_kz=(isset($_REQUEST['course_id'])?$_REQUEST['course_id']:(isset($_REQUEST['studiengang_kz'])?$_REQUEST['studiengang_kz']:0));
		$news->semester=(isset($_REQUEST['term_id'])?$_REQUEST['term_id']:(isset($_REQUEST['semester'])?$_REQUEST['semester']:null));

		$news->fachbereich_kurzbz=(isset($_REQUEST['fachbereich_kurzbz']) && !empty($_REQUEST['fachbereich_kurzbz'])?$_REQUEST['fachbereich_kurzbz']:null);
		
		$chksenat=(isset($_REQUEST['chksenat']) ?true :false);

		// Nur in den Tools - Fachbereich diesen Teil belegen
		if(is_null($studiengang_kz) && is_null($semester) )
		{
			if(isset($chksenat))
				$news->fachbereich_kurzbz = 'Senat';
			else
				$news->fachbereich_kurzbz = '';
		}	
			
		
		$datum_obj = new datum();
		if(isset($_POST['datum']) && !$datum_obj->checkDatum($_POST['datum']))
			$error.=(!empty($error)?'<br>':'').$_POST['datum'].' Datum ist falsch ';
		if(isset($_POST['datum_bis']) && !$datum_obj->checkDatum($_POST['datum_bis']))
			$error.=(!empty($error)?'<br>':'').$_POST['datum_bis'].' Datum Bis ist falsch ';


			
			
		$news->datum = trim((isset($_REQUEST['datum']) ? $_REQUEST['datum']:date('d.m.Y')));
		$news->datum_bis = trim((isset($_REQUEST['datum_bis']) ? $_REQUEST['datum_bis']:null));

		

		$news->uid=$user;
		$news->updatevon=$user;
		$news->updateamum=date('Y-m-d H:i:s');	
		
		$news->insertvon=$user;
		$news->insertamum=date('Y-m-d H:i:s');	
				
		if($news->save())
		{
			if(isset($news_id) && $news_id != "")
				$error.=(!empty($error)?'<br>':'').'Die Nachricht wurde erfolgreich ge&auml;ndert!';		
			else
				$error.=(!empty($error)?'<br>':'').'Die Nachricht wurde erfolgreich eingetragen!';						
		}
		else
		{
			$error.=(!empty($error)?'<br>':'').$news->errormsg;
		}
				
	}

	// Verarbeiten der Daten
	if (!empty($btnDel))
	{
		if(isset($news_id) && $news_id != "")
		{
			if($news->delete($news_id))
			{
				writeCISlog('DELETE NEWS','');
				$error.=(!empty($error)?'<br>':'').'Die Nachricht wurde erfolgreich gel&ouml;scht!';						
				$news_id='';
			}
			else
				$error.=(!empty($error)?'<br>':'').'Fehler beim l&ouml;schen des Eintrages! '.$news->errormsg;
		}	
		
	}
	// Einlesen News
	if(isset($news_id) && $news_id != "")
	{
		if (!$news->load($news_id))
			$error.=(!empty($error)?'<br>':'').$news->errormsg;	
	}	
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link href="../../../skin/style.css.php" rel="stylesheet" type="text/css">
<script language="JavaScript" type="text/javascript">



	function focusFirstElement()
	{
		if(document.NewsEntry.verfasser != null)
		{
			document.NewsEntry.verfasser.focus();
		}
	}

	function plausibElement()
	{
		document.getElementById('error').innerHTML='';
		if(document.NewsEntry.verfasser.value == '')
		{
			document.NewsEntry.verfasser.focus();
			document.getElementById('error').innerHTML='Eingabe Verfasser fehlt!';
			return false;
		}
		var checkDate=''
		if(document.NewsEntry.datum.value == '')
		{
			document.NewsEntry.datum.focus();
			document.getElementById('error').innerHTML='Eingabe Sichtbar ab fehlt!';
			return false;
		}
		
		if(document.NewsEntry.datum.value != '')
		{
			checkDate=checkdatum(document.NewsEntry.datum);
			if (checkDate)
			{
				document.NewsEntry.datum.focus();
				document.getElementById('error').innerHTML=checkDate;
				return false;		
			}
		}

		if (document.NewsEntry.datum_bis.value != '')
		{
			checkDate=checkdatum(document.NewsEntry.datum_bis);
			if (checkDate)
			{
				document.NewsEntry.datum_bis.focus();
				document.getElementById('error').innerHTML=checkDate;
				return false;		
			}	
		}

		
		if(document.NewsEntry.betreff.value == '')
		{
			document.NewsEntry.betreff.focus();
			document.getElementById('error').innerHTML='Eingabe Titel fehlt!';
			return false;
		}
		if(document.NewsEntry.text.value == '')
		{
			document.NewsEntry.text.focus();
			document.getElementById('error').innerHTML='Eingabe der Nachricht fehlt!';
			return false;
		}
		return true;
	}

	function checkdatum(datum)
	{
		var Datum=datum;
		if(Datum.value.length<10)
		{
			return 'Datum ' + Datum.value + ' ist ungültig. Bitte beachten Sie das führende nullen angegeben werden müssen (Beispiel: <?php echo date('d.m.Y');?>)';
		}
	      	var Tag, Monat,Jahr,Date; 
		Date=Datum.value;
	      	Tag=Date.substring(0,2); 
	      	Monat=Date.substring(3,5); 
	      	Jahr=Date.substring(6,10); 
	
	  	if (parseInt(Tag,10)<1 || parseInt(Tag,10)>31)
		{	
			return ' Tag '+ Tag + ' ist nicht richtig im Datum '+ Datum.value;
		}
	  	if (parseInt(Monat,10)<1 || parseInt(Monat,10)>12)
		{	
			return ' Monat '+ Monat + ' ist nicht richtig im Datum '+ Datum.value;
		}
	  	if (parseInt(Jahr,10)<2000 || parseInt(Jahr,10)>3000)
		{	
			return ' Jahr '+ Jahr + ' ist nicht richtig im Datum '+ Datum.value;
		}

  	return false;
	}		
	
	
	function deleteEntry(id)
	{
		if(confirm("Soll dieser Eintrag wirklich gelöscht werden?") == true)
		{
			document.location.href = '<?php echo $_SERVER['PHP_SELF'];?>?btnDel=y&news_id=' + id + '&fachbereich_kurzbz=<?php echo (is_null($fachbereich_kurzbz)?'':$fachbereich_kurzbz);?>' + '&studiengang_kz=<?php echo (is_null($studiengang_kz)?'':$studiengang_kz);?>'+ '&semester=<?php echo  (is_null($semester)?'':$semester);?>';
		}
	}
	$fachbereich_kurzbz=trim((isset($_REQUEST['fachbereich_kurzbz']) ? $_REQUEST['fachbereich_kurzbz']:null));
	$studiengang_kz=(isset($_REQUEST['course_id'])?$_REQUEST['course_id']:'');
	$semester=(isset($_REQUEST['term_id'])?$_REQUEST['term_id']:'');
	function editEntry(id)
	{
		document.location.href = '<?php echo $_SERVER['PHP_SELF'];?>?btnRead=y&news_id=' + id + '&fachbereich_kurzbz=<?php echo (is_null($fachbereich_kurzbz)?'':$fachbereich_kurzbz);?>' + '&studiengang_kz=<?php echo (is_null($studiengang_kz)?'':$studiengang_kz);?>'+ '&semester=<?php echo  (is_null($semester)?'':$semester);?>';
	}
	
</script>
</head>

<body onLoad="focusFirstElement();">
<form onsubmit="if (!plausibElement()) return false;" name="NewsEntry" target="_self" action="<?php echo $_SERVER['PHP_SELF'];?>"  method="post" enctype="multipart/form-data">
<table class="tabcontent" id="inhalt">
  <tr>
	    <td class="tdwidth10">&nbsp;<a name="top" >&nbsp;</a></td>
	    <td>
		   <table class="tabcontent">

		      <tr><td class="ContentHeader"><font class="ContentHeader">&nbsp;Lektorenbereich - Pinboardverwaltung
		  <?php

			if(is_null($fachbereich_kurzbz) && isset($studiengang_kz) && isset($semester))
			{
				if (is_null($studiengang_kz))
					$studiengang_kz=0;
				if($studiengang_kz == 0 && $semester == 0)
				{
					echo 'Alle Studieng&auml;nge, Alle Semester';
				}
				else if($studiengang_kz == 0)
				{
					echo 'Alle Studieng&auml;nge, '.$semester.'. Semester';
				}
				else if($semester == 0)
				{
					echo $stg_obj->kuerzel.', Alle Semester';
				}
				else
				{
					echo $stg_obj->kuerzel.', '.$semester.'. Semester';
				}
			}
		  ?></font></td></tr>
		      <tr><td class="ContentHeader2">&nbsp;<?php echo (isset($news_id) && $news_id != ''?'Eintrag &auml;ndern':'Neuen Eintrag erstellen'); ?></td></tr>
			  <tr>
			    <td>
				  <table class="tabcontent">
				    <tr>
					  <td width="65">Verfasser:</td>
					  <td><input class="TextBox" style="color:black;background-color:#FFFCF2;border : 1px solid Black;" type="text" name="verfasser" size="30"<?php if(isset($news_id) && $news_id != "") echo ' value="'.$news->verfasser.'"'; ?>></td>
					 
					<?php
						// Verwendung in der Lehre
					 	if(!is_null($studiengang_kz) || !is_null($semester) )
						{
						?>				 
					  <td width="81">Studiengang: </td>
					  <td width="130">
					  	<select onchange="window.document.NewsEntry.submit();" name="course_id" class="TextBox">
							<option value="0" <?php echo (is_null($studiengang_kz) || $studiengang_kz=='0'?' selected="selected" ':''); ?> >Alle Studieng&auml;nge</option>
						<?php
			  				$studiengaenge = new studiengang();
					  		$studiengaenge->getAll('typ, kurzbz');
							foreach($studiengaenge->result AS $row_course)
							{
								echo '<option '.($studiengang_kz!='0' && $studiengang_kz==$row_course->studiengang_kz?' selected="selected" ':'') .' value="'.$row_course->studiengang_kz.'">'.$row_course->kuerzel.' ('.$row_course->kurzbzlang.')</option>';
							}
						?>
					  	</select>
					  </td>
				 	<?php } ?>
					
					  <td>Sichtbar ab:</td>
					  <td><input class="TextBox" style="color:black;background-color:#FFFCF2;border : 1px solid Black;" type="text" name="datum" size="10" value="<?php if(isset($news_id) && $news_id != "") echo date('d.m.Y',strtotime(strftime($news->datum))); else echo date('d.m.Y'); ?>"></td>
				    </tr>
					<tr>
					  <td>Titel:</td>
					  <td><input class="TextBox" style="color:black;background-color:#FFFCF2;border : 1px solid Black;" type="text" name="betreff" size="30"<?php if(isset($news_id) && $news_id != "") echo ' value="'.$news->betreff.'"'; ?>></td>
					<?php
						// Verwendung in der Lehre
					 	if(!is_null($studiengang_kz) || !is_null($semester) )
						{
						?>				 
					  <td>Semester: </td>
					  <td width="130">
					  	<select name="term_id"  onchange="window.document.NewsEntry.submit();" class="TextBox">
						<?php
							echo '<option value="0" '.(is_null($semester) || $semester==0?' selected="selected" ':'').'>Alle Semester</option>';
							echo '<option value="1" '.($semester==1?' selected="selected" ':'').'>1. Semester</option>';
							echo '<option value="2" '.($semester==2?' selected="selected" ':'').'>2. Semester</option>';
							echo '<option value="3" '.($semester==3?' selected="selected" ':'').'>3. Semester</option>';
							echo '<option value="4" '.($semester==4?' selected="selected" ':'').'>4. Semester</option>';
							echo '<option value="5" '.($semester==5?' selected="selected" ':'').'>5. Semester</option>';
							echo '<option value="6" '.($semester==6?' selected="selected" ':'').'>6. Semester</option>';
							echo '<option value="7" '.($semester==7?' selected="selected" ':'').'>7. Semester</option>';
							echo '<option value="8" '.($semester==8?' selected="selected" ':'').'>8. Semester</option>';
						?>
					  	</select>
					  </td>
					  <?php } ?>
					  <td>Sichtbar bis (optional):</td>
					  <td><input type="text" class="TextBox" name="datum_bis" size="10" value="<?php if(isset($news_id) && $news_id != "" && $news->datum_bis!='') echo date('d.m.Y',strtotime(strftime($news->datum_bis))); else echo ''; ?>"></td>
					</tr>
					<tr>
					<td colspan="2">Bitte geben Sie hier Ihre Nachricht ein:</td>
					<?php
					  if(!is_null($fachbereich_kurzbz) && $fachbereich_kurzbz!='' && ($rechte->isBerechtigt('admin',0) || $rechte->isBerechtigt('assistenz',0)) )
					  {
					?>
						  <td>Senat:</td>
						  <td><input type="checkbox" name="chksenat" <?php if(isset($news_id) && $news_id!="" && $news->fachbereich_kurzbz=='Senat') echo ' checked'?>></td>
					<?php
					  }
					?>
				    </tr>
				</table>
				</td>
			  </tr>
			  <tr>
			  	<td><textarea class="TextBox" style="color:black;background-color:#FFFCF2;border : 1px solid Black;width: 99%; heigth: 166px;"   name="text" rows="10" cols="100" maxlength="1999"><?php if(isset($news_id) && $news_id != "") echo mb_eregi_replace("<br>", "\r\n", $news->text); ?></textarea></td>
			  </tr>
			  <tr>
			  	<td id="error" class="error">&nbsp;<?php echo $error; ?></td>
			  </tr>
			  <tr>
			  	<td nowrap>
					<table>
						<tr>
							<td>
						        <input type="submit" name="btnSend" value="Abschicken">&nbsp;
								<input type="reset" name="btnCancel" value="<?php echo (isset($news_id) && $news_id !=''?'Abbrechen':'Zur&uuml;cksetzen'); ?>" onClick="document.location.href='<?php echo $_SERVER['PHP_SELF']; ?>';">&nbsp;
						  		<input type="hidden" name="news_id" value="<?php echo $news_id;?>">	
							</td>
							<td style="color:black;background-color:#FFFCF2;border : 1px solid Black;">&nbsp;&nbsp;&nbsp;</td><td>Pflichtfelder</td>
							
						</tr>
					</table>
				</td>					
			  </tr>
		    </table>
		</td>
		<td class="tdwidth30">&nbsp;</td>
	  </tr>
	</table>
</form>
<?php

	// Einlesen News
	$all=true;

	$maxnews=(defined('MAXNEWS')?MAXNEWS:5);
	$maxalter=(defined('MAXNEWSALTER')?MAXNEWSALTER:30);		

	$fachbereich_kurzbz=(isset($_REQUEST['fachbereich_kurzbz']) && !empty($_REQUEST['fachbereich_kurzbz']) ? $_REQUEST['fachbereich_kurzbz']:null);
	$studiengang_kz=(isset($_REQUEST['course_id'])?$_REQUEST['course_id']:(isset($_REQUEST['studiengang_kz'])?$_REQUEST['studiengang_kz']:0));
    $semester=(isset($_REQUEST['term_id'])?$_REQUEST['term_id']:(isset($_REQUEST['semester'])?$_REQUEST['semester']:null));
#org.	$news_obj->getnews(MAXNEWSALTER,$studiengang_kz, $semester, true, null, MAXNEWS);
	if (!$news->getnews($maxalter, $studiengang_kz, $semester, $all, $fachbereich_kurzbz, $maxnews))
		die($news->errormsg);	
		
	// Datenlesen OK - in Tabellenform anzeigen
	if(count($news->result)<1)
		exit('Zur Zeit gibt es keine aktuellen News!');
?>

<table class="tabcontent" id="inhalt">
  <tr>
    <td class="tdwidth10">&nbsp;</td>
    <td><table class="tabcontent">
        <tr>
	         <td>
		  	<table class="tabcontent">
			  <?php
				$i=0;
				foreach($news->result as $row)
				{
					if((is_null($fachbereich_kurzbz) || $fachbereich_kurzbz=='') && $row->studiengang_kz==0 && $row->semester==0)
						continue;

					$datum = date('d.m.Y',strtotime(strftime($row->datum)));
					echo '<tr>';
					$i++; // Zeilenwechsel - Counter
					if($i % 2 != 0)
						echo '<td class="MarkLine">';
					else
						echo '<td>';
					echo '  <table class="tabcontent">';
					echo '    <tr>';
					echo '      <td nowarp title="Studiengang_kz:'.$row->studiengang_kz ,', Semester:'. $row->semester .', Fachbereich_kurzbz:'.$row->fachbereich_kurzbz.'">';
					echo $datum.'&nbsp;'.$row->verfasser;
					echo '      </td>';
					echo '		<td align="right" nowrap>';
					echo '		  <a onClick="editEntry('.$row->news_id.');">Editieren</a>, <a onClick="deleteEntry('.$row->news_id.');">L&ouml;schen</a>, <a href="#top" >Top</a>';
					echo '		</td>';
					echo '    </tr>';
					echo '	  <tr>';
					echo '		<td>&nbsp;</td>';
					echo '	  </tr>';
					echo '  </table>';
					echo '  <strong>'.$row->betreff.'</strong><br>'.$row->text.'</td>';
					echo '</tr>';
					
					echo '<tr>';
					echo '  <td>&nbsp;</td>';
					echo '</tr>';
				}
			  ?>
			</table>
		  </td>
        </tr>
    </table></td>
    <td class="tdwidth30">&nbsp;</td>
  </tr>
</table>
<a href="#top" >&nbsp;Top</a>

</body>
</html>
