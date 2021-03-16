<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<title>NIM</title>
	<link rel="stylesheet" href="style/main.css" type="text/css">
</head>


<body>
<h1>NIM</h1>
<p>Player and computer take 1-3 matches in turns. Whoever takes the last match, looses.</p>


<!-- initial form (start a game) -->

<?php if ($this->game_state === 0) { ?>
	<form action="?" method="GET">
		<table>
			<tr>
				<td><label>Matches:</label></td>
				<td class="center"><input type="number" min="2" max="50" value="20" name="initial"></td>
			</tr>
			<tr>
				<td colspan="2" class="center"><button type="submit">Start</button></td>
			</tr>
		</table>
	</form>


<!-- game in progress -->

<?php } else if ($this->game_state === 1 || $this->game_state === 2) { ?>

<div class="center">
	<?php for($i = 0; $i < min(3, $this->remain_matches); $i++){ ?>
		<a href="?initial=<?= $this->init_matches ?>&rem_matches=<?= $this->remain_matches - ($i + 1) ?>&seed=<?= $this->seed ?>" class="match">
		<img src="style/match.png" class="match">
		</a>
	<?php } ?>

	<?php for($i = 0; $i < $this->remain_matches - 3; $i++) { ?>

	<img src="style/match.png" class="match">

	<?php } ?>

	<?php for($i = 0; $i < $this->computer_took; $i++) { ?>
		<img src="style/match.png" class="match taken">
	<?php } ?>
</div>


<?php } else { /*$game_state === 3 || $game_state === 4*/ ?>

<!-- when the game is over -->

<p>There are no matches left...</p>

<p>Game over. The winner is <strong><?php 
	if($this->game_state === 3) {
		echo "player";
	} else {
		echo "computer";
	}
?></strong>!<br><a href="?">Play Again</a></p>

<?php } ?>


<!-- footer -->

</body>
</html>
