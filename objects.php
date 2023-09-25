<?php

class weekObj {
	public $week;
	public $year;
	public $seasonType;
	public $weekID;
	public $startDate;
	public $endDate;

	function __construct($weekArray) {
		$this->week = $weekArray['week'];
		$this->year = $weekArray['year'];
		$this->seasonType = $weekArray['seasonType'];
		$this->weekID = $weekArray['id'];
		$this->startDate = $weekArray['startDate'];
		$this->endDate = $weekArray['endDate'];
	}
}

class gameObj {
	public $id;
	public $week;
	public $name;
	public $home;
	public $away;
	public $date;
	public $venue;
	public $isNeutral;
	public $statusID;
	public $curPeriod;
	public $curTime;
	public $down;
	public $toGo;
	public $yardLine;
	public $possession;
	public $completed;
	public $homePoints;
	public $awayPoints;
	public $winnerID;
	public $loserID;
	public $favID;
	public $dogID;
	public $spread;
	public $customName;
	public $pick = null;
	public $multiplier;
	public $jokeGame;
	public $isRivalry;
	public $rivalryName;
	public $rivalryTrophy;

	function __construct($gameArray, $curWeek, $dbConn) {
		$this->id = $gameArray['id'];
		$this->week = $curWeek;
		$this->name = $gameArray['name'];
		$this->customName = $gameArray['customName'];
		$this->date = $gameArray['startDate'];

		$homeArray = array(
			'id' => $gameArray['homeID'],
			'school' => $gameArray['homeSchool'],
			'mascot' => $gameArray['homeMascot'],
			'abbr' => $gameArray['homeAbbr'],
			'confID' => $gameArray['homeConfID'],
			'confName' => $gameArray['homeConfName'],
			'confShortName' => $gameArray['homeConfShortName'],
			'confAbbr' => $gameArray['homeConfAbbr'],
			'confIsFBS' => $gameArray['homeConfIsFBS'],
			'picked' => $gameArray['homePicks'],
			'rank' => $gameArray['homeRank'],
			'wins' => $gameArray['homeWins'],
			'losses' => $gameArray['homeLosses'],
			'confWins' => $gameArray['homeConfWins'],
			'confLosses' => $gameArray['homeConfLosses'],
			'comedyName' => $gameArray['homeComedyName']
		);

		$awayArray = array(
			'id' => $gameArray['awayID'],
			'school' => $gameArray['awaySchool'],
			'mascot' => $gameArray['awayMascot'],
			'abbr' => $gameArray['awayAbbr'],
			'confID' => $gameArray['awayConfID'],
			'confName' => $gameArray['awayConfName'],
			'confShortName' => $gameArray['awayConfShortName'],
			'confAbbr' => $gameArray['awayConfAbbr'],
			'confIsFBS' => $gameArray['awayConfIsFBS'],
			'picked' => $gameArray['awayPicks'],
			'rank' => $gameArray['awayRank'],
			'wins' => $gameArray['awayWins'],
			'losses' => $gameArray['awayLosses'],
			'confWins' => $gameArray['awayConfWins'],
			'confLosses' => $gameArray['awayConfLosses'],
			'comedyName' => $gameArray['awayComedyName']
		);

		$venueArray = array(
			'id' => $gameArray['venueID'],
			'name' => $gameArray['venueName'],
			'city' => $gameArray['city'],
			'state' => $gameArray['state'],
			'country' => $gameArray['country']
		);
		$this->isNeutral = $gameArray['isNeutral'];

		$this->home = new teamObj(
			$dbConn,
			$homeArray,
			$gameArray
		);

		$this->away = new teamObj(
			$dbConn,
			$awayArray,
			$gameArray
		);
		
		$this->venue = new venueObj(array(
					'id' => $gameArray['venueID'],
					'name' => $gameArray['venueName'],
					'city' => $gameArray['city'],
					'state' => $gameArray['state'],
					'country' => $gameArray['country']
				)
			);
		$this->statusID = $gameArray['statusID'];
		$this->curPeriod = $gameArray['curPeriod'];
		$this->curTime = $gameArray['curTime'];
		$this->down = $gameArray['down'];
		$this->toGo = $gameArray['toGo'];
		$this->yardLine = $gameArray['yardLine'];
		$this->possession = $gameArray['possession'];
		$this->completed = $gameArray['completed'];
		$this->homePoints = $gameArray['homePoints'];
		$this->awayPoints = $gameArray['awayPoints'];
		$this->winnerID = $gameArray['winnerID'];
		$this->loserID = $gameArray['loserID'];
		$this->favID = $gameArray['favID'];
		$this->dogID = $gameArray['dogID'];
		$this->spread = $gameArray['spread'];
		if(isset($_SESSION['uid'])) {
			$query = 'SELECT * FROM picks WHERE gameID = ? AND userID = ?';
			$queryArray = array($this->id, $_SESSION['uid']);
			$rslt = sqlsrv_query($dbConn, $query, $queryArray);
			if(sqlsrv_has_rows($rslt)) {
				$pickArray = sqlsrv_fetch_array($rslt);
				$this->pick = $pickArray['teamID'];
			} else {
				$this->pick = -1;
			}
		}
		$this->multiplier = $gameArray['multiplier'];
		$this->jokeGame = $gameArray['jokeGame'];
		if($gameArray['teamAID'] != null) {
			$this->isRivalry = 1;
			$this->rivalryName = $gameArray['rivalryName'];
			$this->rivalryTrophy = $gameArray['trophy'];
		} else {
			$this->isRivalry = 0;
			$this->rivalryName = null;
			$this->rivalryTrophy = null;
		}
	}

	public function isOT() {
		$otPeriods = count($this->away->lineScores) - 4;
		if($otPeriods > 0) {
			return $otPeriods;
		} else {
			return 0;
		}
	}

	public function printGame($boxID, $pickPage) {
		?>
		<div id="gameBox-<?= $boxID ?>" class="col-lg-6 gameBox">
			<input type="hidden" id="box-<?= $boxID ?>" class="boxInput" value="<?= $this->id ?>">
			<input type="hidden" id="status-<?= $boxID ?>" class="statusInput" value="<?= $this->statusID ?>">
			<div id="outerGameWrapper-<?= $boxID ?>" <?php if($this->multiplier > 1) echo 'class="gotw"' ?>>
				<div class="fullGameBox">
					<div class="col-4 game">
						<?php
							$this->gameHeaderRow($boxID, $pickPage);
							$this->away->printRowTeam('away', $pickPage, $boxID, $this->winnerID == $this->away->id);
							$this->home->printRowTeam('home', $pickPage, $boxID, $this->winnerID == $this->home->id);
						?>
					</div>
					<?php
					$this->printSpreadBox($boxID);
					$this->printInfoBox($boxID);
					?>
				</div>
				<?php
				$this->gameStatus($boxID);
				$this->gameNameRow($boxID);
				?>
			</div>
		</div>
		<?php
	}

	private function gameHeaderRow($boxID, $pickPage) {
		?>
		<div class="row header-row">
			<div id="header-status-<?= $boxID ?>" class="header-status col-3">
			</div>
			<?php
			for($period = 1; $period <= 4; $period++) {
				?>
				<div id="header-lineScore-<?= $boxID ?>-<?= $period ?>" class="header-lineScore">
					<?= $period ?>
				</div>
				<?php
			}
			?>
			<div id="header-OT-<?= $boxID ?>" class="header-lineScore">
				<?php
				if($this->isOT()) {
					?>
					OT (<?= $this->isOT()?>)
					<?php
				}
				?>
			</div>
			<div id="header-total-<?= $boxID ?>" class="header-total">
				T
			</div>
			<?php
			if($pickPage) {
				?>
				<div id="header-others-<?= $boxID ?>" class="header-others">
					Picked
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}

	private function printSpreadBox($boxID) {
		?>
		<div id="spreadBox-<?= $boxID ?>" class="spreadBox">
			<div id="spreadFirstRow-<?= $boxID ?>" class="spreadFirstRow">
				Spread
			</div>
			<?php
			if($this->spread == 0 || $this->spread == null) {
				?>
				<div id="spreadEven-<?= $boxID ?>" class="spreadEven">
					<?php if($this->spread != null) echo 'EVEN'; ?>
				</div>
				<?php
			} else {
				?>
				<div id="spreadRow-<?= $boxID ?>-away" class="spreadRow">
					<?php
					if($this->favID == $this->away->id) {
						echo '-' . number_format($this->spread, 1);
					}
					?>
				</div>
				<div id="spreadRow-<?= $boxID ?>-home" class="spreadRow">
					<?php
					if($this->favID == $this->home->id) {
						echo '-' . number_format($this->spread, 1);
					}
					?>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}

	private function printInfoBox($boxID) {
		?>
		<div id="infoBox-<?= $boxID ?>" class="infoBox">
			<div id="venueName-<?= $boxID ?>" class="venueName">
				<?= $this->venue->name ?>
			</div>
			<div id="cityState-<?= $boxID ?>" class="cityState">
				<?= $this->venue->cityState(); ?>
			</div>
			<div id="gameLink-<?= $boxID ?>" class="gameLink">
			<a id="espnLink-<?= $boxID ?>"
				class="espnLink d-inline-flex focus-ring py-1 px-1 text-decoration-none border rounded-2 link-light" 
				href="https://www.espn.com/college-football/game/_/gameId/<?= $this->id ?>"
				target="_blank">ESPN Gamecast</a>
			</div>
		</div>
		<?php
	}

	private function gameStatus($boxID) {
		$class = 'gameStatus';
		if(!in_array($this->statusID, array(2, 22))) {
			$class .= ' hidden';
		}
		if($this->gameName() == '') {
			$class .= ' lastRow';
		}
		?>
		<div id="gameStatus-<?= $this->id ?>" class="<?= $class ?>">
			<?php
			if(in_array($this->statusID, array(2, 22))) {
				if($this->possession == $this->home->id) {
					$hasBall = 'home';
					$notBall = 'away';
				} else {
					$hasBall = 'away';
					$notBall = 'home';
				}

				$yardLine = $this->yardLine;
				if($yardLine > 50) {
					$yardLine = abs($yardLine - 100);
					$fieldSide = $this->away->abbr;
				} else if($yardLine != 50) {
					$fieldSide = $game->home->abbr;
				} else {
					$fieldSide = 'the';
				}

				if($yardLine - $this->toGo == 0) {
					$toGo = 'Goal';
				} else {
					$toGo = $this->toGo;
				}

				if($game->down > 0) {
					$gameStatus = getOrdinal($this->down) . ' and ' . $toGo . ' at ' . $fieldSide . ' ' . $yardLine;
				} else {
					$gameStatus = 'Kickoff ' . $this->{$hasBall}->abbr;
				}

				echo $gameStatus;
			}
			?>
		</div>
		<?php
	}

	private function gameName() {
		$gameString = '';
		if($this->isRivalry) {
			if($this->rivalryName != null) {
				$gameString .= $this->rivalryName;
			} else {
				$gameString .= 'Rivalry Game';
			}
			if($this->rivalryTrophy != null) {
				$gameString .= ' - ' . $this->rivalryTrophy;
			}
		}
		if($this->name != null) {
			if($gameString != '') {
				$gameString .= ' - ';
			}
			$gameString .= $this->name;
		}
		if($this->customName != null) {
			if($gameString != '') {
				$gameString .= ' - ';
			}
			$gameString .= $this->customName;
		}
		return $gameString;
	}

	private function gameNameRow($boxID) {
		$gameString = $this->gameName();
		
		$hidden = '';
		if($gameString == '') {
			$hidden = 'hidden';
		}
		?>
		
		<div id="gameName-<?= $boxID ?>" class="gameName lastRow <?= $hidden ?>">
			<?= $gameString ?>
		</div>
		<?php
	}

	public function printGamePicker($boxID) {
		?>
		<div id="pickerContainer-<?= $boxID ?>" class="pickerContainer col-lg-6">
			<div id="pickRow-<?= $boxID ?>" class="pickRow row">
				<div id="userSelectCellUs-<?= $boxID ?>" class="userSelectCell">
					<div id="selectLogoCellUs-<?= $boxID ?>" class="selectLogoCell">
						<?php
						if($this->pick > 0) {
							?>
							<img id="logoPick-<?= $boxID ?>"
								class="logoPick"
								src="<?= $GLOBALS['baseURL'] ?>/images/teamLogo.php?teamID=<?= $this->pick ?>&height=30">
							<?php
						}
						?>
					</div>
					<?php 
					$this->selectPickerUs($boxID); 
					$this->scoreCell($boxID);
					?>
				</div>
				<?php
				$this->selectPickerThem($boxID);
				?>
			</div>
		</div>
		<?php
	}

	private function selectPickerUs($boxID) {
		$class = 'form-select selectPick';
		if($this->completed) {
			if(($this->winnerID == $this->pick) || ($this->jokeGame && $this->pick > 0)) {
				$class .= ' winner-' . $this->pick;
			} else {
				$class .= ' loserSelect';
			}
		}

		if($this->date <= new DateTime()) {
			$disabled = 'disabled';
		} else {
			$disabled = '';
		}

		if($this->pick < 0) {
			$nullSelect = 'selected';
			$awaySelect = '';
			$homeSelect = '';
		} else if($this->pick == $this->away->id) {
			$nullSelect = '';
			$awaySelect = 'selected';
			$homeSelect = '';
		} else {
			$nullSelect = '';
			$awaySelect = '';
			$homeSelect = 'selected';
		}
		?>
		<div id="selectCellUs-<?= $boxID ?>" class="selectCell">
			<select id="pick-<?= $boxID ?>" class="<?= $class ?>" onchange="setPick(<?= $boxID ?>)" <?= $disabled ?>>
				<option value="-1" <?= $nullSelect ?>></option>
				<option value="<?= $this->away->id ?>" <?= $awaySelect ?>><?= $this->away->school ?></option>
				<option value="<?= $this->home->id ?>" <?= $homeSelect ?>><?= $this->home->school ?></option>
			</select>
		</div>
		<?php
	}

	private function scoreCell($boxID) {
		$class = 'score';
		if($this->completed) {
			if(($this->winnerID == $this->pick) || ($this->jokeGame && $this->pick > 0)) {
				$class .= ' scoreWinner';
				$_SESSION['userScore'] += $this->multiplier;
			} else {
				$class .= ' scoreLoser';
				if($_SESSION['userScore'] == 0) {
					$_SESSION['userScore'] = '0';
				}
			}
		}
		?>
		<div id="score-<?= $boxID ?>" class="<?= $class ?>">
			<?php 
			if($this->completed) {
				echo $_SESSION['userScore'];
			}
			?>
		</div>
		<?php
	}

	private function selectPickerThem($boxID) {
		?>
		<div id="userSelectCellThem-<?= $boxID ?>" class="userSelectCell">
			<div id="selectLogoCellThem-<?= $boxID ?>" class="selectLogoCell">
			</div>
			<div id="selectCellThem-<?= $boxID ?>" class="selectCell">
				<select id="compare-<?= $boxID ?>" class="form-select othersSelectPick" disabled>
					<option value="-1" selected></option>
					<option value="<?= $this->away->id ?>"><?= $this->away->school ?></option>
					<option value="<?= $this->home->id ?>"><?= $this->home->school ?></option>
				</select>
			</div>
			<div id="compareScore-<?= $boxID ?>" class="score">
			</div>
		</div>
		<?php
	}
}

class teamObj {
	public $id;
	public $school;
	public $mascot;
	public $abbr;
	public $conf;
	public $rank = null;
	public $wins = 0;
	public $losses = 0;
	public $confWins = 0;
	public $confLosses = 0;
	public $lineScores = array();
	public $picked;

	function __construct($dbConn, $teamArray, $gameArray) {
		$this->id = $teamArray['id'];
		$this->school = $teamArray['school'];
		if($teamArray['comedyName'] != null) {
			$this->school = $teamArray['comedyName'];
		}
		$this->mascot = $teamArray['mascot'];
		$this->abbr = $teamArray['abbr'];
		$this->rank = $teamArray['rank'];
		$this->conf = new confObj(array(
			'id' => $teamArray['confID'],
			'name' => $teamArray['confName'],
			'short_name' => $teamArray['confShortName'],
			'abbreviation' => $teamArray['confAbbr'],
			'isFBS' => $teamArray['confIsFBS']
		));
		$this->picked = $teamArray['picked'];
		$this->wins = $teamArray['wins'];
		$this->losses = $teamArray['losses'];
		$this->confWins = $teamArray['confWins'];
		$this->confLosses = $teamArray['confLosses'];

		$query = 'SELECT * FROM gameLineScores WHERE gameID = ? AND teamID = ?';
		$queryArray = array($gameArray['id'], $this->id);
		$rslt = sqlsrv_query($dbConn, $query, $queryArray);
		if(sqlsrv_has_rows($rslt)) {
			while($period = sqlsrv_fetch_array($rslt)) {
				$this->lineScores[$period['period']] = $period['points'];
			}
		}
	}

	public function printRowTeam($homeAway, $pickPage, $boxID, $isWinner) {
		if($isWinner) {
			$winnerClass = 'winner-' . $this->id;
		} else {
			$winnerClass = '';
		}

		?>
		<div id="teamRow-<?= $boxID ?>-<?= $homeAway ?>" class="row gameRow <?= $winnerClass ?>">
			<div id="logoCell-<?= $boxID ?>-<?= $homeAway ?>" class="logo">
				<img id="logo-<?= $boxID ?>-<?= $homeAway ?>" src="images/teamLogo.php?teamID=<?= $this->id ?>&height=30">
			</div>
			<div id="rank-<?= $boxID ?>-<?= $homeAway ?>" class="rank">
				<?= $this->rank ?>
			</div>
			<div id="teamName-<?= $boxID ?>-<? $homeAway ?>" class="teamName">
				<div id="teamName-upper-<?= $boxID ?>-<?= $homeAway ?>" class="upperName">
					<a id="teamLink-<?= $boxID ?>-<?= $homeAway ?>" 
						class="link-light link-underline link-underline-opacity-100-hover link-underline-opacity-0 <?= $winnerClass ?>"
						href="https://www.espn.com/college-football/team/_/id/<?= $this->id ?>" target="_blank">
						<?= $this->school ?>
					</a>
				</div>
				<div id="teamName-lower-<?= $boxID ?>-<?= $homeAway ?>" class="lowerRecord">
					<?php
					echo '(' . $this->wins . '-' . $this->losses;
					if($this->conf->id != 18 && $this->conf->id != 32) {
						echo ', ' . $this->confWins . '-' . $this->confLosses . ' ' . $this->conf->abbr;
					}
					echo ')';
					?>
				</div>
			</div>
			<div id="scheduleCell-<?= $boxID ?>" class="scheduleCell">
				<button id="teamSchedule-<?= $boxID ?>-<?= $homeAway ?>" class="surroundHamburger" data-bs-toggle="popover" data-bs-html="true" data-bs-custom-class="scheduleBox" value="teamSchedule-<?= $this->id ?>">☰▸</button>
			</div>
			<?php
			$this->printLineScores($boxID, $homeAway);
			if($pickPage) {
				?>
				<div id="others-<?= $boxID ?>-<?= $homeAway ?>" class="othersPicks">
					<?= $this->picked ?>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}

	private function printLineScores($boxID, $homeAway) {
		$teamTotal = null;
		$line = 'lineScoreFirst';
		$periods = count($this->lineScores);
		for($period = 1; $period <= 5; $period++) {
			?>
			<div id="lineScore-<?= $boxID ?>-<?= $homeAway ?>-<?= $period ?>" class="lineScore <?= $line ?>">
			<?php
			if($period != 5) {
				if(isset($this->lineScores[$period])) {
					echo $this->lineScores[$period];
					$teamTotal += $this->lineScores[$period];
				}
			} else {
				if($periods > 4) {
					$otScore = 0;
					for($ot = 5; $ot <= $periods; $ot++) {
						$otScore += $this->lineScores[$ot];
					}
					echo $otScore;
					$teamTotal += $otScore;
				}
			}
			$line = '';
			?>
			</div>
			<?php
		}
		?>
		<div id="total-<?= $boxID ?>-<?= $homeAway ?>" class="total">
			<?= $teamTotal ?>
		</div>
		<?php
	}
}

class scheduleObj {
	public $schedule = array();

	function __construct($dbConn, $curWeek, $teamID) {
		$query = 'SELECT games.id, games.winnerID, games.loserID, games.homeID, games.isNeutral,
					games.awayID, games.completed, games.statusID, games.startDate,
					games.homePoints, games.awayPoints, homeTeam.abbreviation AS homeAbbr, awayTeam.abbreviation AS awayAbbr
					FROM games 
					LEFT JOIN teams AS homeTeam ON homeTeam.id = homeID
					LEFT JOIN teams AS awayTeam ON awayTeam.id = awayID
					WHERE 
					games.weekID IN 
						(SELECT id FROM weeks WHERE year = ?)
					AND
					(games.homeID = ? OR games.awayID = ?)
					ORDER BY startDate';
		$queryArray = array($curWeek->year, $teamID, $teamID);
		$rslt = sqlsrv_query($dbConn, $query, $queryArray);
		while($game = sqlsrv_fetch_array($rslt)) {
			array_push($this->schedule, new scheduleGameObj($game, $teamID));
		}
	}

	function printSchedule() {
		ob_start();
		?>
		<table>
			<?php
			foreach($this->schedule as $game) {
				echo $game->scheduleRow();
			}
			?>
		</table>
		<?php
		$returnVal = ob_get_contents();
		ob_end_clean();
		return $returnVal;
	}
}

class venueObj {
	public $id;
	public $name;
	public $city;
	public $state;
	public $country;

	function __construct($venueArray) {
		$this->id = $venueArray['id'];
		$this->name = $venueArray['name'];
		$this->city = $venueArray['city'];
		$this->state = $venueArray['state'];
		$this->country = $venueArray['country'];
	}

	function cityState() {
		return $this->city . ', ' . $this->stateCountry();
	}

	function stateCountry() {
		if($this->country == 'US') {
			return $this->state;
		} else {
			switch($this->country) {
				case 'IE':
					return 'Ireland';
				case 'BS':
					return 'Bahamas';
				case 'AU':
					return 'Australia';
			}
		}
	}
}

class confObj {
	public $id;
	public $name;
	public $shortName;
	public $abbr;
	public $isFBS;

	function __construct($confArray) {
		$this->id = $confArray['id'];
		$this->name = $confArray['name'];
		$this->shortName = $confArray['short_name'];
		$this->abbr = $confArray['abbreviation'];
		$this->isFBS = $confArray['isFBS'];
	}
}

class userObj {
	public $id;
	public $name;
	public $team;
	public $email;
	public $color;
	public $altColor;
	public $score;
	public $wins;
	public $losses;

	function __construct($userArray) {
		$this->id = $userArray['id'];
		$this->name = $userArray['name'];
		$this->team = $userArray['team'];
		$this->email = $userArray['email'];
		$this->color = $userArray['color'];
		$this->altColor = $userArray['alternateColor'];
	}

	function liveScoreRecord($dbConn, $curWeek) {
		$query = 'SELECT SUM(multiplier) AS score 
			FROM picks 
				LEFT JOIN games ON picks.gameID = games.id 
				LEFT JOIN weeks ON weeks.id = games.weekID
			WHERE (games.winnerID = picks.teamID  
				AND picks.userID = ?
				AND weeks.year = ?)
			OR (games.jokeGame = 1
				AND picks.userID = ?
				AND weeks.year = ?)';
		$queryArray = array($this->id, $curWeek->year, $this->id, $curWeek->year);
		$this->score = sqlsrv_fetch_array(sqlsrv_query($dbConn, $query, $queryArray))['score'];
		if($this->score == null) {
			$this->score = 0;
		}

		$query = 'SELECT COUNT(multiplier) AS wins
			FROM picks 
					LEFT JOIN games ON picks.gameID = games.id 
					LEFT JOIN weeks ON weeks.id = games.weekID
				WHERE (games.winnerID = picks.teamID  
					AND picks.userID = ?
					AND weeks.year = ?)
				OR (games.jokeGame = 1
					AND picks.userID = ?
					AND weeks.year = ?)';
		$this->wins = sqlsrv_fetch_array(sqlsrv_query($dbConn, $query, $queryArray))['wins'];
		$query = 'SELECT COUNT(multiplier) AS losses
			FROM picks 
				LEFT JOIN games ON picks.gameID = games.id 
				LEFT JOIN weeks ON weeks.id = games.weekID
			WHERE (games.winnerID <> picks.teamID  
				AND picks.userID = ?
				AND games.jokeGame IS NULL
				AND weeks.year = ?)';
		$queryArray = array($this->id, $curWeek->year);
		$this->losses = sqlsrv_fetch_array(sqlsrv_query($dbConn, $query, $queryArray))['losses'];
	}
}

class othersPickObj {
	public $gameID;
	public $pickID;
	public $winnerID;
	public $multiplier;
	public $jokeGame;

	function __construct($pickArray) {
		$this->gameID = $pickArray['id'];
		$this->pickID = $pickArray['teamID'];
		if($this->pickID == null) {
			$this->pickID = -1;
		}
		$this->winnerID = $pickArray['winnerID'];
		$this->multiplier = $pickArray['multiplier'];
		$this->jokeGame = $pickArray['jokeGame'];
	}
}

class scheduleGameObj {
	public $gameID;
	public $winLoss = null;
	public $opptID;
	public $opptAbbr;
	public $homeAway;
	public $date;
	public $winnerScore;
	public $loserScore;

	function __construct($game, $teamID) {
		$this->id = $game['id'];
		
		if($game['completed']) {
			if($game['winnerID'] == $teamID) {
				$this->winLoss = 'W';
			} else {
				$this->winLoss = 'L';
			}
		}

		if($game['homeID'] == $teamID) {
			$this->opptID = $game['awayID'];
			$this->opptAbbr = $game['awayAbbr'];
			$this->homeAway = 'vs';
			if($this->winLoss != null) {
				if($this->winLoss == 'W') {
					$this->winnerScore = $game['homePoints'];
					$this->loserScore = $game['awayPoints'];
				} else {
					$this->winnerScore = $game['awayPoints'];
					$this->loserScore = $game['homePoints'];
				}
			}
		} else {
			$this->opptID = $game['homeID'];
			$this->opptAbbr = $game['homeAbbr'];
			if(!$game['isNeutral']) {
				$this->homeAway = '@';
			} else {
				$this->homeAway = 'vs';
			}
			if($this->winLoss != null) {
				if($this->winLoss == 'W') {
					$this->winnerScore = $game['awayPoints'];
					$this->loserScore = $game['homePoints'];
				} else {
					$this->winnerScore = $game['homePoints'];
					$this->loserScore = $game['awayPoints'];
				}
			}
		}

		$game['startDate']->setTimezone(new DateTimeZone('Pacific/Honolulu'));
		$this->date = $game['startDate']->format('n/j');
	}

	function scheduleRow() {
		ob_start();
		?>
		<tr>
			<td>
				<?= $this->winLoss ?>
			</td>
			<td>
				<?= $this->homeAway ?>
			</td>
			<td>
				<?= $this->opptAbbr ?>
			</td>
			<td>
				<?php
				if($this->winLoss != null) {
					echo $this->winnerScore . '-' . $this->loserScore;
				} else {
					$this->date->setTimezone(new DateTimeZone('Pacific/Honolulu'));
					echo $this->date->format('n/j');
				}
				?>
			</td>
		</tr>
		<?php
		$returnVal = ob_get_contents();
		ob_end_clean();
		return $returnVal;
	}
}