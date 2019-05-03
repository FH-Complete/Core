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
 *			Andreas Oesterreicher <andreas.oesterreicher@technikum-wien.at>,
 *			Rudolf Hangl <rudolf.hangl@technikum-wien.at>,
 *			Manfred Kindl <manfred.kindl@technikum-wien.at>
 */

require_once('../../config/cis.config.inc.php');
require_once('../../config/global.config.inc.php');
require_once('../../include/basis_db.class.php');
require_once('../../include/sprache.class.php');
require_once '../../include/phrasen.class.php';
require_once '../../include/studiengang.class.php';

if (!$db = new basis_db())
	die('Fehler beim Oeffnen der Datenbankverbindung');

require_once('../../include/gebiet.class.php');

function getSpracheUser()
{
	if(isset($_SESSION['sprache_user']))
	{
		$sprache_user=$_SESSION['sprache_user'];
	}
	else
	{
		if(isset($_COOKIE['sprache_user']))
		{
			$sprache_user=$_COOKIE['sprache_user'];
		}
		else
		{
			$sprache_user=DEFAULT_LANGUAGE;
		}
		setSpracheUser($sprache_user);
	}
	return $sprache_user;
}

function setSpracheUser($sprache)
{
	$_SESSION['sprache_user']=$sprache;
	setcookie('sprache_user',$sprache,time()+60*60*24*30,'/');
}

if(isset($_GET['sprache_user']))
{
	$sprache_user = new sprache();
	if($sprache_user->load($_GET['sprache_user']))
	{
		setSpracheUser($_GET['sprache_user']);
	}
	else
		setSpracheUser(DEFAULT_LANGUAGE);
}

$sprache_user = getSpracheUser();
$p = new phrasen($sprache_user);
$sprache = getSprache();

session_start();

?><!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link href="../../skin/style.css.php" rel="stylesheet" type="text/css">
</head>

<body scroll="no">
<?php
if (isset($_SESSION['pruefling_id']))
{
	//content_id fuer Einfuehrung auslesen
	$qry = "SELECT content_id FROM testtool.tbl_ablauf_vorgaben WHERE studiengang_kz=".$db->db_add_param($_SESSION['studiengang_kz'])." LIMIT 1";
	$result = $db->db_query($qry);

	echo '<table width="100%" border="0" cellspacing="0" cellpadding="0" style="border-right-width:1px;border-right-color:#BCBCBC; border-collapse: separate;
    border-spacing: 0 3px;">';

// Link zur Startseite
	echo '<tr><td class="ItemTesttool" style="margin-left: 20px;" nowrap>
			<a class="ItemTesttool navButton" href="login.php" target="content">'.$p->t('testtool/startseite').'</a>
		</td></tr>';

// Link zur Einleitung
	if ($content_id = $db->db_fetch_object($result))
    {
		if($content_id->content_id!='')
        {
			echo '
                <tr><td class="ItemTesttool" style="margin-left: 20px;" nowrap>
                    <a class="ItemTesttool navButton" href="../../cms/content.php?content_id='.$content_id->content_id.'&sprache='.$sprache.'" target="content">'.$p->t('testtool/einleitung').'</a>
                </td></tr>
            ';
        }
    }
	echo '<tr><td style="padding-left: 20px;" nowrap>';

	$studiengang_kz = (isset($_SESSION['studiengang_kz'])) ? $_SESSION['studiengang_kz'] : '';
	$stg = new Studiengang($studiengang_kz);

	$sprache_mehrsprachig = new sprache();
	$bezeichnung_mehrsprachig = $sprache_mehrsprachig->getSprachQuery('bezeichnung_mehrsprachig');

	/**
	 * Spaltennamen-Aliase extrahieren um sie im Outer-Select verwenden zu können
     * $bezeichnung_mehrsprachig liefert: bezeichnung_mehrsprachig[1] as bezeichnung_mehrsprachig_1,...
     * $bezeichnung_mehrsprachig_sel liefert: bezeichnung_mehrsprachig_1, bezeichnung_mehrsprachig_2,...
	 */
	$bezeichnung_mehrsprachig_sel = explode(",", $bezeichnung_mehrsprachig);
	foreach ($bezeichnung_mehrsprachig_sel as &$bm)
    {
        $bm = strrchr($bm, ' as ');
    }
	$bezeichnung_mehrsprachig_sel = implode(', ', $bezeichnung_mehrsprachig_sel);

	/**
	 * Reihungstestgebiete der Person ermitteln; Zusammenfassen, falls RT für mehrere Studien
     * 1. Aktuelle Prestudenten zur Person über den Prüfling ermitteln,
     * 2. Einstiegssemester (Erstsemester/Quereinsteiger) und Studienplan pro Prestudent ermitteln,
     * 3. RT-Gebiete falls vorhanden über Studienplan, sonst über STG ermitteln
     * 4. Für Quereinsteiger zusätzlich auch Erstsemestrigen-Gebiete
	 */
	$qry = "
        WITH prestudent_data AS
        (
        SELECT DISTINCT ON (prestudent_id)
	        prestudent_id,
	        studienplan_id,
            studiengang_kz,
            typ,
			tbl_studiengangstyp.bezeichnung AS typ_bz,
	        ausbildungssemester AS semester
        FROM
	        public.tbl_prestudentstatus
        JOIN
	        public.tbl_prestudent USING (prestudent_id)
        JOIN
            public.tbl_studiengang USING (studiengang_kz)
        JOIN
            public.tbl_studiengangstyp USING (typ)
        WHERE
	        tbl_prestudent.person_id = (
		        SELECT
			        person_id
		        FROM
			        public.tbl_prestudent
		        WHERE
			        prestudent_id = ".$db->db_add_param($_SESSION['prestudent_id'])."
	        )

        /* Filter only future studiensemester (incl. actual one) */
        AND
	        studiensemester_kurzbz IN (
		        SELECT
			        studiensemester_kurzbz
		        FROM
			        public.tbl_studiensemester
		        WHERE
			        ende > now()
	        )

        AND
	        status_kurzbz = 'Interessent'";

            /*  If the logged-in prestudents study is a Bachelor-study, filter only Bachelor-studies */
			if ($stg->typ == 'b')
			{
				$qry .= "
				 	AND tbl_studiengang.typ = 'b'";
			}
			/* If the logged-in prestudents study is NOT a Bachelor-study, get only the specific study */
			else
			{
				$qry .= "
				 	AND tbl_studiengang.studiengang_kz = ". $studiengang_kz;
			}

			$qry .= "

        /* Order to get last semester when using distinct on */
        ORDER BY
	        prestudent_id,
	        datum DESC,
	        tbl_prestudentstatus.insertamum DESC,
	        tbl_prestudentstatus.ext_id DESC
        )


        SELECT DISTINCT ON 
            (gebiet_id, semester)
	        semester,
	        gebiet_id,
	        STRING_AGG(studiengang_kz::TEXT, ', ' ORDER BY studiengang_kz) AS studiengang_kz_list,
	        bezeichnung,
	        reihung,
            ". $bezeichnung_mehrsprachig_sel. "
        FROM (
            SELECT
                *
            FROM (    
                (SELECT
                    prestudent_data.semester AS ps_sem,
                    gebiet_id,
                    bezeichnung,
                    tbl_ablauf.studienplan_id,
                    tbl_ablauf.studiengang_kz,
                    tbl_ablauf.semester,
                    tbl_ablauf.reihung,
                    ".$bezeichnung_mehrsprachig. "
                FROM
                    prestudent_data
                JOIN
                    testtool.tbl_ablauf USING (studiengang_kz)
                JOIN
                    testtool.tbl_gebiet USING (gebiet_id)
                WHERE
                    (prestudent_data.semester= 1 AND tbl_ablauf.semester = 1)
                OR
                    (prestudent_data.semester= 3 AND tbl_ablauf.semester IN (1,3))
                )
    
                UNION
    
                (
                SELECT
                    prestudent_data.semester AS ps_sem,
                    gebiet_id,
                    bezeichnung,
                    tbl_ablauf.studienplan_id,
                    tbl_ablauf.studiengang_kz,
                    tbl_ablauf.semester,
                    tbl_ablauf.reihung,
                    ". $bezeichnung_mehrsprachig. "
                FROM
                    prestudent_data
                JOIN
                    testtool.tbl_ablauf USING (studienplan_id)
                JOIN
                    testtool.tbl_gebiet USING (gebiet_id)
                WHERE
                    (prestudent_data.semester= 1 AND tbl_ablauf.semester = 1)
                OR
                    (prestudent_data.semester= 3 AND tbl_ablauf.semester IN (1,3))
                )
            ) temp
        ) temp2
        
        GROUP BY
            semester,
             gebiet_id,
             bezeichnung,
	         reihung,
             bezeichnung_mehrsprachig_1,
             bezeichnung_mehrsprachig_2,
             bezeichnung_mehrsprachig_3,
             bezeichnung_mehrsprachig_4

        ORDER BY
	        semester,
	        gebiet_id,
	        reihung
        ";

	$result = $db->db_query($qry);
	$lastsemester = '';
	$quereinsteiger_stg = '';

	while($row = $db->db_fetch_object($result))
	{
		//Jedes Semester in einer eigenen Tabelle anzeigen
		if($lastsemester!=$row->semester)
		{
			if($lastsemester!='')
			{
				//echo '<tr><td>&nbsp;</td></tr>';
				echo '</table>';
			}
			$lastsemester = $row->semester;

			echo '<table border="0" cellspacing="0" cellpadding="0" id="Gebiet" style="display: visible; border-collapse: separate; border-spacing: 0 3px;">';
			/*echo '<tr><td class="HeaderTesttool">'.$row->semester.'. '.$p->t('testtool/semester').' '.($row->semester!='1'?$p->t('testtool/quereinstieg'):'').'</td></tr>';*/
			echo '<tr><td class="HeaderTesttool">'. ($row->semester == '1' ? strtoupper($p->t('testtool/basic')) : strtoupper($p->t('testtool/quereinsteiger'))).'</td></tr>';
		}

		// Bei Quereinstiegsgebieten nach STG clustern und die STG anzeigen
		if($row->semester != '1')
		{
			if($quereinsteiger_stg != $row->studiengang_kz_list)
			{
			    //echo "<br>"; // Abstand zwischen Erstsemester- und Quereinstiegs-Gebietsblock
				$quereinsteiger_stg = $row->studiengang_kz_list;
				$quereinsteiger_stg_arr = explode(',', $row->studiengang_kz_list);
				$quereinsteiger_stg_string = '';
				$cnt = 0;
				foreach ($quereinsteiger_stg_arr as $qe_stg)
                {
                    $stg = new Studiengang($qe_stg);
					$quereinsteiger_stg_string .= ($cnt > 0) ? ",<br>" : '';
                    $quereinsteiger_stg_string .= $stg->bezeichnung;
                    $cnt++;
                }
                echo '<tr><td class="HeaderTesttoolSTG">'. $quereinsteiger_stg_string. '</td></tr>';
			}
		}

		$gebiet = new gebiet();
		if($gebiet->check_gebiet($row->gebiet_id))
		{
			//Status der Gebiete Pruefen
			$gebiet->load($row->gebiet_id);

			$qry = "SELECT extract('epoch' from '".$gebiet->zeit."'-(now()-min(begintime))) as time
					FROM testtool.tbl_pruefling_frage JOIN testtool.tbl_frage USING(frage_id)
					WHERE gebiet_id=".$db->db_add_param($row->gebiet_id)." AND pruefling_id=".$db->db_add_param($_SESSION['pruefling_id']);
			if($result_time = $db->db_query($qry))
			{
				if($row_time = $db->db_fetch_object($result_time))
				{
					if($row_time->time>0)
					{
						//Gebiet gestartet aber noch nicht zu ende
						$style='';
						$class='ItemTesttool ItemTesttoolAktiv';
					}
					else
					{
						if($row_time->time=='')
						{
							//Gebiet noch nicht gestartet
							$style='';
							$class='ItemTesttool';
						}
						else
						{
							//Gebiet ist zu Ende
							$style='';
							$class='ItemTesttool ItemTesttoolBeendet';
						}
					}
				}
				else
				{
					$style='';
					$class='ItemTesttool';
				}
			}
			else
			{
				$style='';
				$class='ItemTesttool';
			}

			echo '<tr>
					<!--<td width="10" class="ItemTesttoolLeft" nowrap>&nbsp;</td>-->
						<td class="'.$class.'">
							<a class="'.$class.'" href="frage.php?gebiet_id='.$row->gebiet_id.'" onclick="document.location.reload()" target="content" style="'.$style.'">'.$sprache_mehrsprachig->parseSprachResult("bezeichnung_mehrsprachig", $row)[$sprache_user].'</a>
						</td>
					<!--<td width="10" class="ItemTesttoolRight" nowrap>&nbsp;</td>-->
					</tr>';
		}
		else
		{
			echo '<tr>
						<td nowrap>
							<span class="error">&nbsp;'.$row->gebiet_bez.' (invalid)</span>
						</td>
					</tr>';
		}
	}
	echo '</table>';

	// Link zum Logout
	echo '<tr><td class="ItemTesttool" style="margin-left: 20px;" nowrap>
			<a class="ItemTesttool navButton" href="login.php?logout" target="content">Logout</a>
		</td></tr>';

	echo '</td></tr></table>';
}
else
{
	echo '</td></tr></table>';
}
?>
</body>
</html>
