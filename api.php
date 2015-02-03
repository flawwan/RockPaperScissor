<?php
require 'lib/Database.php';
require 'lib/Pick.php';
session_start();
header('Content-Type: application/json');

$match = $_GET['id'];

$pick = new Pick($db);

if ($pick->status()) {
	$sth = $db->prepare("SELECT * FROM `players` WHERE `match_id`=:match");
	$sth->bindParam(':match', $match);
	$sth->execute();

	$allDone = true;

	foreach ($sth->fetchAll() as $player) {
		if (!$player["done"]) {
			$allDone = false;
		}
	}
	if ($allDone) {
		//Clear old and set last winner
		$sth = $db->prepare("UPDATE `players` SET `pick`='null',`done`=FALSE WHERE `match_id`=:match AND `done`=TRUE");
		$sth->bindParam(':match', $match);
		$sth->execute();
	}
}