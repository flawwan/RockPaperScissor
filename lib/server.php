<?php

class Server
{
	private $db = null;

	public function __construct($db)
	{
		$this->db = $db;
	}

    /**
     * Skapar en session för spelare och skickar den vidare.
     */
	public function play()
	{
		$key = !is_array($_GET['key']) ? $_GET['key'] : null;
		if (!isset($key)) {
			die("Invalid data");
		}
		if (isset($key) && !is_array($key)) {
			//Validera spelarens nyckel mot den i databasen.
			$sth = $this->db->prepare("SELECT `id`,`match_id` FROM `players` WHERE `player`=:key", array(':key' => $key));
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
	}

    /**
     * Kommunikationslänk med gamecentral för att kommunicera.
     * @param $serverToken SHA512 server API token
     */
	public function authenticateServer($serverToken)
	{
		if (isset($_POST['token']) && $_POST['token'] === $serverToken && !is_array($serverToken)) {
			//Nu vet vi att servern har skickat förfrågan samt att vi nu måste lägga till spelarna i vår databas.
			$this->db->beginTransaction();


			//Lägg sedan till alla spelare i players vektorn med den matchens id som returnerades ovan.
			$players = isset($_POST['keys']) ? json_decode($_POST['keys']) : array();

			//Börjar med att skapa en match
			$sth = $this->db->prepare("INSERT INTO `matches`(`turn`) VALUES(0)");
			$sth->execute();
			$matchID = $this->db->lastInsertId();

			$this->createPlayers($players, $matchID);
		} else {
			header("HTTP/1.1 403 Unauthorized");
			die("Unauthorized");
		}
	}

    //Lägg till spelare på spelet
	private function createPlayers($players, $matchID)
	{
		$playerIndex = 0;
		$sth = $this->db->prepare("INSERT INTO `players`(`player`,`match_id`,`name`) VALUES(:player,:matchID, :name)");
		foreach ($players as $player) {
			$sth->bindParam(':player', $player[0]);
			$sth->bindParam(':name', $player[1]);
			$sth->bindParam(':matchID', $matchID);
			$sth->execute();

			$playerID = $this->db->lastInsertId();
			if ($playerIndex == 0) {
				//Börjar med att skapa en match
				$sthM = $this->db->prepare("UPDATE `matches` SET `turn`=:turn WHERE `match_id`=:match_id");
				$sthM->bindParam(":turn", $playerID);
				$sthM->bindParam(":match_id", $matchID);
				$sthM->execute();
			}
			$playerIndex++;
		}
		$this->db->commit();
	}
}