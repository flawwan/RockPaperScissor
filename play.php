<?php
/*#######################################################################
 * play.php är en fil ...
 */######################################################################
session_start();
require 'lib/Database.php';

$key = $_GET['key'];

if (isset($key) && !is_array($key)) {
	//Validera spelarens nyckel mot den i databasen.
	$sth = $db->prepare("SELECT `id`,`match_id` FROM `players` WHERE `player`=:key", array(':key' => $key));
	$sth->bindParam(':key', $key);
	$sth->execute();
	//Fanns inte i databasen => ogiltig förfrågan.
	if ($sth->rowCount() == 0) {
		header("HTTP/1.1 403 Unauthorized");
		die("Unauthorized");
	}
	$matchID = $sth->fetch()['match_id'];
	unset($_SESSION['player']); //Rensa gammalt spel
	$_SESSION['player'] = $key; //Skapa en session för detta spel
	header("location: index.php?match=" . $matchID);
	exit();
} else {
	header("HTTP/1.1 403 Unauthorized");
	die("Unauthorized");
}