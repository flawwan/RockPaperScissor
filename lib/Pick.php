<?php

class Pick
{

	private $msg = "";
	private $status = false;
	private $players = array();
	private $db = null;
	private $match;

	function __construct($db)
	{
		$this->db = $db;
		$this->match = intval(isset($_GET['id']) ? $_GET['id'] : 0);
		$player = $_SESSION["player"];


		$sth = $this->db->prepare("SELECT `pick` FROM `players` WHERE `player`=:user AND `match_id`=:match");
		$sth->execute(array(':user' => $player, ':match' => $this->match));
		$data = $sth->fetch(PDO::FETCH_ASSOC);

//Get information about the match
		$sth = $this->db->prepare("SELECT `pick`,`player`,`name`,`score` FROM `players` WHERE `match_id`=:match");
		$sth->execute(array(':match' => $this->match));

		$playersDone = 0;
		$players = $sth->fetchAll();
		$playersCount = count($players);
		foreach ($players as $p) {
			array_push($this->players, $p["name"] . " (Score:" . $p["score"] . ")");
			if ($p['pick'] != "null") {
				$playersDone++;
			}
		}

		$this->msg = "";
		if ($playersCount === $playersDone) {
			//Tell db we have seen this
			$sth = $this->db->prepare("UPDATE `players` SET `done`=TRUE WHERE `player`=:user AND `match_id`=:match");
			$sth->execute(array(':user' => $player, ':match' => $this->match));

			$this->status = true;
			//Round done, two players
			switch ($players[0]["pick"]) {
				case "rock":
					if ($players[1]["pick"] == "paper") {
						//Player 2 won
						$this->winner($players[1], $players[0], $players[1]);
					} elseif ($players[1]["pick"] == "rock") {
						$this->msg = "Tie";
					} else {
						$this->winner($players[0], $players[0], $players[1]);
					}
					break;
				case "paper":
					if ($players[1]["pick"] == "scissor") {
						//Player 2 won
						$this->winner($players[1], $players[0], $players[1]);
					} elseif ($players[1]["pick"] == "paper") {
						$this->msg = "Tie";
					} else {
						$this->winner($players[0], $players[0], $players[1]);
					}
					break;
				case "scissor":
					if ($players[1]["pick"] == "rock") {
						//Player 2 won
						$this->winner($players[1], $players[0], $players[1]);
					} elseif ($players[1]["pick"] == "scissor") {
						$this->msg = "Tie";
					} else {
						$this->winner($players[0], $players[0], $players[1]);
					}
					break;
			}

		}

		echo json_encode(array(
			'data' => $data,
			'message' => $this->msg,
			'players' => $this->players
		));
	}

	function status()
	{
		return $this->status;
	}

	function msg()
	{
		return $this->msg;
	}

	function addWin($player)
	{
		$sth = $this->db->prepare("UPDATE `players` SET `score`=`score`+1 WHERE `player`=:player AND `match_id`=:node");
		$sth->execute(array(
			':player' => $player,
			':node' => $this->match
		));
	}

	function winner($player, $player1, $player2)
	{
		$enemyPick = $_SESSION['player'] == $player1["player"] ? $player2["pick"] : $player1["pick"];
		$yourPick = $_SESSION['player'] == $player1["player"] ? $player1["pick"] : $player2["pick"];
		if ($_SESSION['player'] === $player["player"]) {

			$this->msg = "You won(" . $yourPick . "), enemy picked: " . $enemyPick;
			$this->addWin($_SESSION['player']);
		} else {
			$this->msg = "You lost(" . $yourPick . "), enemy picked: " . $enemyPick;
		}
	}
}