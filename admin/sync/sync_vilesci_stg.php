<?php
include('../../vilesci/config.inc.php');
include('../../include/studiengang.class.php');

$conn=pg_connect(CONN_STRING);
$conn_vilesci=pg_connect(CONN_STRING_VILESCI);
$adress='pam@technikum-wien.at';

// Encoding fuer VileSci
$qry = "SET CLIENT_ENCODING TO 'UNICODE';";
if(!pg_query($conn_vilesci,$qry))
{
	$this->errormsg	 = "Encoding konnte nicht gesetzt werden";
	return false;
}

// Erhalter anlegen
$result=pg_exec($conn,  "INSERT INTO tbl_erhalter VALUES(5,'TW','Technikum Wien')");

/*************************
 * VileSci-Synchronisation
 */
//Studiengaenge vom VileSci holen
$sql_query='SELECT * FROM tbl_studiengang';
//echo $sql_query.'<br>';
$stg_vilesci=pg_exec($conn_vilesci, $sql_query);

while ($stg=pg_fetch_object($stg_vilesci))
{
	$sql_query="INSERT INTO tbl_studiengang VALUES ($stg->studiengang_kz,'$stg->kurzbz','$stg->kurzbzlang','$stg->bezeichnung',
					'$stg->typ','$stg->farbe','$stg->email',$stg->max_semester,'$stg->max_verband','$stg->max_gruppe',5)";
	if (!$result=@pg_exec($conn, $sql_query))
		echo pg_last_error($conn).'<br>--'.$sql_query.'<br>';

}

?>

<html>
<head>
<title>FAS-Synchro mit VileSci</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<body>
<h3>Studiengaenge werden synchronisiert!</h3>
<?php

?>
</body>
</html>
