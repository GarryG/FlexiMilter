<?
	global $db;
	$dbname="tmpmail";
	$dbuser="flexi";
	$dbpw="flexi";			// better change at least this ... ;)
	$dbhost="localhost";

	 $db=mysqli_connect($dbhost,$dbuser,$dbpw,$dbname);
?>
