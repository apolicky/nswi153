<?php

class NIM {
	protected $game_state;
	protected $init_matches;
	protected $remain_matches;
	protected $computer_took;
	protected $seed;
	
	function __construct($init, $matches, $seed) {
		if($init === null){
			$this->game_state = 0;
		}
		else {
			/*start variables*/
			$this->init_matches = intval($init);			
			
			
			if($matches === null) {
				$this->game_state = 1;
				$this->remain_matches = $this->init_matches;
			}
			else{
				
				$this->remain_matches = intval($matches);
				if($this->remain_matches === 0){
				/**/
					$this->game_state = 4;
					$this->computer_took = 0;
				}
				elseif ($this->remain_matches === 1) {
					$this->game_state = 3;
					$this->remain_matches = 0;
					$this->computer_took = 1;
				}
				else {
					$this->game_state = 2;
					/*computers turn*/
				}
			}
		}
	}
	
	function computer_step() {
		if ($this->game_state === 2) {
			$this->computer_took = ($this->remain_matches - 1) % 4;
			if ($this->computer_took === 0) {
				$this->computer_took = rand(1,3);
			}
			$this->remain_matches -= $this->computer_took;			
		}
		else {
			return;
		}
	}	
	
	function show() {
		require("template.php");
	}

	static function start() {
		$nim = new NIM($_GET['initial'] ?? null,
			       $_GET['rem_matches'] ?? null,
			       $_GET['seed'] ?? null);
		$nim->computer_step();
		$nim->show();
	}
}

NIM::start();
