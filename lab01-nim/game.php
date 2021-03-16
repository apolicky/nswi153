<!-- game in progress -->
<div class="center">
	<?php for($i = 0; $i < min(3, $this->remain_matches); $i++){ ?>
		<a href="?initial=<?= $this->init_matches ?>&matches=<?= $this->remain_matches - ($i + 1) ?>&seed=<?= $this->seed ?>" class="match">
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


