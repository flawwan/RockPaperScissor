<?php
session_start();
require 'lib/Database.php';
require 'lib/Pick.php';
header('Content-Type: application/json');

$match = isset($_GET['id']) ? intval($_GET['id']) : 0;
$player = $_SESSION["player"];
$pick = $_GET['pick'];

$sth = $db->prepare("UPDATE `players` SET `pick`=:pick WHERE `player`=:user AND `match_id`=:match");
$sth->execute(array(':pick' => $pick, ':user' => $player, ':match' => $match));