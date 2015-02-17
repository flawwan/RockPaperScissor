<?php
/*#######################################################################
 * server.php är en fil för att kommunicera med spelservern.
 */######################################################################
require 'lib/Database.php';
//Byt ut mot din privata nyckel.
DEFINE('GAME_KEY', 'e250d120077889ede5a2a099cf0883438e550ede39b8364d77c063888df01ce2679e02f9978b17cadbf9680f126d7969c6617fbb6a01433e207c60d569aedf50');

if (isset($_POST['token']) && $_POST['token'] === GAME_KEY && !is_array($key)) {
	//Nu vet vi att servern har skickat förfrågan samt att vi nu måste lägga till spelarna i vår databas.
	$db->beginTransaction();

	//Börjar med att skapa en match
	$sth = $db->query("INSERT INTO `matches`() VALUES()");
	$matchID = $db->lastInsertId();

	//Lägg sedan till alla spelare i players vektorn med den matchens id som returnerades ovan.
	$players = isset($_POST['keys']) ? json_decode($_POST['keys']) : array();
	foreach ($players as $player) {
		$sth = $db->prepare("INSERT INTO `players`(`player`,`match_id`,`name`) VALUES(:player,:matchID, :name)");
		$sth->bindParam(':player', $player[0]);
		$sth->bindParam(':name', $player[1]);
		$sth->bindParam(':matchID', $matchID);
		$sth->execute();
	}
	$db->commit();
} else {
	header("HTTP/1.1 403 Unauthorized");
	die("Unauthorized");
}