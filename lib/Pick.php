<?php

class Pick
{

	private $msg = "";
	private $status = false;

	function __construct($db)
	{

		$match = intval(isset($_GET['id']) ? $_GET['id'] : 0);
		$player = $_SESSION["player"];


		$sth = $db->prepare("SELECT `pick` FROM `players` WHERE `player`=:user AND `match_id`=:match");
		$sth->execute(array(':user' => $player, ':match' => $match));
		$data = $sth->fetch(PDO::FETCH_ASSOC);

//Get information about the match
		$sth = $db->prepare("SELECT `pick`,`player` FROM `players` WHERE `match_id`=:match");
		$sth->execute(array(':match' => $match));

		$playersDone = 0;
		$players = $sth->fetchAll();
		$playersCount = count($players);
		foreach ($players as $p) {
			if ($p['pick'] != "null") {
				$playersDone++;
			}
		}

		$this->msg = "";
		if ($playersCount === $playersDone) {
			//Tell db we have seen this
			$sth = $db->prepare("UPDATE `players` SET `done`=TRUE WHERE `player`=:user AND `match_id`=:match");
			$sth->execute(array(':user' => $player, ':match' => $match));

			$this->status = true;
			//Round done, two players
			switch ($players[0]["pick"]) {
				case "rock":
					if ($players[1]["pick"] == "paper") {
						//Player 2 won
						$this->winner($players[1]["player"]);
					} elseif ($players[1]["pick"] == "rock") {
						$this->msg = "Tie";
					} else {
						$this->winner($players[0]["player"]);
					}
					break;
				case "paper":
					if ($players[1]["pick"] == "scissor") {
						//Player 2 won
						$this->winner($players[1]["player"]);
					} elseif ($players[1]["pick"] == "paper") {
						$this->msg = "Tie";
					} else {
						$this->winner($players[0]["player"]);
					}
					break;
				case "scissor":
					if ($players[1]["pick"] == "rock") {
						//Player 2 won
						$this->winner($players[1]["player"]);
					} elseif ($players[1]["pick"] == "scissor") {
						$this->msg = "Tie";
					} else {
						$this->winner($players[0]["player"]);
					}
					break;
			}

		}

		echo json_encode(array(
			'data' => $data,
			'message' => $this->msg
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

	function winner($winner)
	{
		if ($_SESSION['player'] === $winner) {
			$this->msg = "You won";
		} else {
			$this->msg = "You lost";
		}
	}
}