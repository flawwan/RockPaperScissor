<?php

class Api
{

	private $msg = "";
	private $status = false;
	private $players = array();
	private $db = null;
	private $match;
	private $pick;
	private $currentPlayer;

    /**
     * lägger till match id:n och spelarens spelnyckel.
     * @param $db PDO object
     */
	function __construct($db)
	{
		$this->db = $db;
		if (!isset($_GET['id']) || !isset($_SESSION['player'])) {
			die("Miss Param");
		}
		$this->match = intval(isset($_GET['id']) ? $_GET['id'] : 0);
		$this->currentPlayer = $_SESSION["player"];
	}

    /**
     * Denna metod kallas när spelaren har valt ett alternativ i spelet.
     */
	function pick()
	{
		$this->pick = isset($_GET['pick']) ? $_GET['pick'] : null;
        //Sätt ditt val
		$sth = $this->db->prepare("UPDATE `players` SET `pick`=:pick WHERE `player`=:user AND `match_id`=:match");
		$sth->execute(array(':pick' => $this->pick, ':user' => $this->currentPlayer, ':match' => $this->match));

		//Fetch enemy player
		$sth = $this->db->prepare("SELECT `id` FROM `players` WHERE `player`!=:user AND `match_id`=:match");
		$sth->execute(array(':user' => $this->currentPlayer, ':match' => $this->match));

		$turn = $sth->fetch()["id"];
		// set other turn
		$sth = $this->db->prepare("UPDATE `matches` SET `turn`=:turn WHERE `match_id`=:match_id");
		$sth->execute(array(':turn' => $turn, ':match_id' => $this->match));
	}

    /**
     * Metod som kollar om det är din tur samt vilken poäng du och din motståndare har.
     */
	function ajax()
	{
        //Kollar om det är din tur
		$sth = $this->db->prepare("
		SELECT
		`pick`,
		(`turn` = `players`.`id`) as `turn`
		FROM `players`
		LEFT JOIN `matches` ON `matches`.`match_id`=`players`.`match_id`
		WHERE `player`=:user AND `matches`.`match_id`=:match");
		$sth->execute(array(':user' => $this->currentPlayer, ':match' => $this->match));
		$currentPlayerData = $sth->fetch(PDO::FETCH_ASSOC);


		//Kollar om vi har en vinnare.
		$sth = $this->db->prepare("SELECT `pick`,`player`,`name`,`score` FROM `players` WHERE `match_id`=:match");
		$sth->execute(array(':match' => $this->match));
		$players = $sth->fetchAll();

		$playersDone = 0;
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
			$sth->execute(array(':user' => $this->currentPlayer, ':match' => $this->match));

			$this->status = true;
			//Round done, two players
			$this->getWinner($players[0], $players[1]);
		}

        //Returnerar data till klienten.

		header('Content-Type: application/json');
		$returnData = json_encode(array(
			'data' => $currentPlayerData,
			'message' => $this->msg,
			'players' => $this->players
		));
        //Om rundan är klar, rensa och börja på ny runda.
		if ($this->status) {
			$this->resetRound();
		}
		echo $returnData;
	}

    //Algorithm för att bestämma vem som vunnit
	private function getWinner($player1, $player2)
	{
		switch ($player1["pick"]) {
			case "rock":
				if ($player2["pick"] == "paper") {
					//Player 2 won
					$this->winner($player2, $player1, $player2);
				} elseif ($player2["pick"] == "rock") {
					$this->msg = "Tie";
				} else {
					$this->winner($player1, $player1, $player2);
				}
				break;
			case "paper":
				if ($player2["pick"] == "scissor") {
					//Player 2 won
					$this->winner($player2, $player1, $player2);
				} elseif ($player2["pick"] == "paper") {
					$this->msg = "Tie";
				} else {
					$this->winner($player1, $player1, $player2);
				}
				break;
			case "scissor":
				if ($player2["pick"] == "rock") {
					//Player 2 won
					$this->winner($player2, $player1, $player2);
				} elseif ($player2["pick"] == "scissor") {
					$this->msg = "Tie";
				} else {
					$this->winner($player1, $player1, $player2);
				}
				break;
		}
	}


    /**
     * Börja om på en ny runda
     */
	private function resetRound()
	{

		$sth = $this->db->prepare("SELECT * FROM `players` WHERE `match_id`=:match");
		$sth->bindParam(':match', $this->match);
		$sth->execute();

		$allDone = true;

		foreach ($sth->fetchAll() as $player) {
			if (!$player["done"]) {
				$allDone = false;
			}
		}
		if ($allDone) {
			//Clear old and set last winner
			$sth = $this->db->prepare("UPDATE `players` SET `pick`='null',`done`=FALSE WHERE `match_id`=:match AND `done`=TRUE");
			$sth->bindParam(':match', $this->match);
			$sth->execute();
		}
	}

    /**
     * Lägg till en poäng
     * @param $player
     */
	private function addWin($player)
	{
		$sth = $this->db->prepare("UPDATE `players` SET `score`=`score`+1 WHERE `player`=:player AND `match_id`=:node");
		$sth->execute(array(
			':player' => $player,
			':node' => $this->match
		));
	}

    //Räkna ut vem som vann utifrån valen.
	private function winner($player, $player1, $player2)
	{
		$enemyPick = $this->currentPlayer == $player1["player"] ? $player2["pick"] : $player1["pick"];
		$yourPick = $this->currentPlayer == $player1["player"] ? $player1["pick"] : $player2["pick"];
		if ($this->currentPlayer === $player["player"]) {
			$this->msg = "You won(" . $yourPick . "), enemy picked: " . $enemyPick;
			$this->addWin($_SESSION['player']);
		} else {
			$this->msg = "You lost(" . $yourPick . "), enemy picked: " . $enemyPick;
		}
	}
}