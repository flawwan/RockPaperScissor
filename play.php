<?php
session_start();
require 'lib/Database.php';

$key = $_GET['key'];
//Clear previous sessions
if (isset($key) && !is_array($key)) {
	//Validate key
	$sth = $db->prepare("SELECT `id`,`match_id` FROM `players` WHERE `player`=:key", array(':key' => $key));
	$sth->bindParam(':key', $key);
	$sth->execute();
	if ($sth->rowCount() == 0) {
		header("HTTP/1.1 403 Unauthorized");
		die("Unauthorized");
	} else {
		$matchID = $sth->fetch()['match_id'];
		unset($_SESSION['player']); //unset previous
		//Valid player ID,create session
		$_SESSION['player'] = $key;
		header("location: index.php?match=" . $matchID);
		exit();
	}
} else {
	header("HTTP/1.1 403 Unauthorized");
	die("Unauthorized");
}