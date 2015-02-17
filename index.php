<?php
session_start();
$match = intval(isset($_GET['match']) ? $_GET['match'] : 0);
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Document</title>
</head>
<body>
<p id="players"></p>

<h3><?= $_SESSION["player"]; ?></h3>
<script src="js/jquery.min.js"></script>
<script>
	var answer = false;
	var interval = setInterval(function () {
			$.get("api.php?id=<?=$match;?>", function (data) {
				$("#players").text(data.players.join(" VS "));
				if (data.message.length > 1) {
					alert(data.message);
				}

				//if (data.data.pick == "null") {
				if (data.data.turn == "1") {
					//Pick something
					var pick = prompt("Rock/Paper/Scissor");
					switch (pick) {
						case "rock":
							console.log("rock");
							answer = true;
							break;
						case "paper":
							console.log("paper");
							answer = true;
							break;
						case "scissor":
							console.log("scissor");
							answer = true;
							break;
						default:
							alert("Wrong answer");
							break;
					}
					;
					if (answer) {
						$.get("pick.php?id=<?=$match;?>&pick=" + pick, function (data) {
							console.log(data);
						});
					}

				}
			});
		},
		3000
	);
</script>
</body>
</html>