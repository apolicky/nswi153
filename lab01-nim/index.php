<?php

class NIM {
	protected $game_state;
	protected $game_template;
	protected $init_matches;
	protected $remain_matches;
	protected $computer_took;
	protected $seed;
	
	function __construct($init, $matches, $seed) {
		/*page loaded, initial number of matches not specified*/
		if($init === null){
			$this->game_state = 0;
			$this->game_template = "init.php";
		}
		/*game has already started*/
		else {
			if(!is_numeric($init)) {
				$this->bad_request('\'initial\' should be a number');
			}
			
			$this->init_matches = intval($init);
			if($this->init_matches < 2 || $this->init_matches > 50) {
				$this->bad_request('number of matches should be between 2 and 50');
			}
			
			
			/*no match selected yet, now comes the first move of player*/
			if($matches === null) {
				$this->game_state = 1;
				$this->remain_matches = $this->init_matches;
				$this->game_template = "game.php";
				$this->seed = time();
			}
			/*the game is being played*/
			else {
				if(!is_numeric($matches)) {
					$this->bad_request('\'matches\' should be a number');
				}
				
				$this->remain_matches = intval($matches);
				if($this->remain_matches < 0 || $this->remain_matches > $this->init_matches) {
					$this->bad_request("number of matches should be between 0 and $init");
				}
			
			
				if($this->remain_matches === 0){
				/*player took the last match*/
					$this->game_state = 4;
					$this->computer_took = 0;
					$this->game_template = "end.php";
				}
				elseif ($this->remain_matches === 1) {
				/*the last match must be nimmed by computer*/
					$this->game_state = 3;
					$this->remain_matches = 0;
					$this->computer_took = 1;
					$this->game_template = "end.php";
				}
				else {
				/*computer has to nim some matches*/
					$this->game_state = 2;
					$this->game_template = "game.php";
					$this->computer_step($seed);
					/*computers turn*/
				}
			}
		}
	}
	
	function computer_step($seed) {
		if($seed !== null) {
			if(!is_numeric($seed)) {
				$this->bad_request("non numeric seed: $seed");
			}
			/*use the seed if specified*/
			srand(intval($seed));
		}
		/*generate seed for next move*/
		$this->seed = time();		
	
		$this->computer_took = ($this->remain_matches - 1) % 4;
		if ($this->computer_took === 0) {
			$this->computer_took = rand(1,3);
		}
		$this->remain_matches -= $this->computer_took;
	}
	
	protected function bad_request(string $msg) {
		echo $msg;
		http_response_code(400);
		exit();
	}	
	
	function show() {
		require("template.php");
	}

	static function start() {
		$nim = new NIM($_GET['initial'] ?? null,
			       $_GET['matches'] ?? null,
			       $_GET['seed'] ?? null);
		$nim->show();
	}
}

NIM::start();
