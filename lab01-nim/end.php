
<!-- when the game is over -->

<?php if($this->game_state === 3) { ?>
<div class="center">
<img src="style/match.png" class="match taken">
</div>
<?php } ?>

<p>There are no matches left...</p>

<p>Game over. The winner is <strong><?php 
	if($this->game_state === 3) {
		echo "player";
	} else {
		echo "computer";
	}
?></strong>!<br><a href="?">Play Again</a></p>


