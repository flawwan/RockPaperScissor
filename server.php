<?php
require 'lib/Database.php';
DEFINE('GAME_KEY', 'e250d120077889ede5a2a099cf0883438e550ede39b8364d77c063888df01ce2679e02f9978b17cadbf9680f126d7969c6617fbb6a01433e207c60d569aedf50');
//Databasanslutning $db = ....
//Kontrollera om det är servern som skickat förfrågan.
if (isset($_POST['token']) && $_POST['token'] === GAME_KEY) {
	//Nu vet vi att servern har skickat förfrågan samt att vi nu måste lägga till spelarna i vår databas.
	//Börjar med att skapa en match
	$db->beginTransaction();
	$sth = $db->query("INSERT INTO `matches`() VALUES()");
	$matchID = $db->lastInsertId();
	//Lägg sedan till alla spelar i players vektorn med den matchens id som returnerades ovan.
	$players = isset($_POST['keys']) ? json_decode($_POST['keys']) : array();
	foreach ($players as $player) {
		$sth = $db->prepare("INSERT INTO `players`(`player`,`match_id`,`name`) VALUES(:player,:matchID, :name)");
		$sth->bindParam(':player', $player[0]);
		$sth->bindParam(':name', $player[1]);
		$sth->bindParam(':matchID', $matchID);
		$sth->execute();
	}
	$db->commit();
	echo "Success";
} else {
	header("HTTP/1.1 403 Unauthorized");
	die("Unauthorized");
}