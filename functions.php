<?php

require('variables.php');
require('functions/loadFunctions.php');
require('functions/fetchFunctions.php');
require('functions/insertUpdateFunctions.php');
require('objects.php');

function sqlConnect() {
	$connectionOptions = array(
		"Database" => $GLOBALS['sqlDB'],
		"UID" => $GLOBALS['sqlUser'],
		"PWD" => $GLOBALS['sqlPwd'],
		"Encrypt" => 1,
		"TrustServerCertificate" => 1,
		"APP" => $GLOBALS['sqlDB']
	);
	$conn = sqlsrv_connect($GLOBALS['sqlAddr'], $connectionOptions);
	if( $conn === false ) {
		echo "Could not connect.\n";
		die( print_r( sqlsrv_errors(), true));
	}

	loadGlobals($conn);
	return $conn;
}

function httpRequestOpts() {
	$opts = [
		"http" => [
			"header" => "Authorization: Bearer ".$GLOBALS['token']
		]
	];
	return stream_context_create($opts);
}

function countryName($code) {
	switch($code) {
		case 'IE':
			return 'Ireland';
		case 'BS':
			return 'Bahamas';
		case 'AU':
			return 'Australia';
	}
}

function getOrdinal($number) {
	$suffix = array('th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th');
	if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
		$ordinal = $number . 'th';
	} else {
		$ordinal = $number . $suffix[$number % 10];
	}

	return $ordinal;
}

function printGame($dbConn, $game, $firstRow, $users) {
	?>
	<tr class="firstRow">
		<th class="header-status" colspan="3" id="header-status-<?= $game->id ?>">
		</th>
		<th class="header-lineScore">
			1
		</th>
		<th class="header-lineScore">
			2
		</th class="header-lineScore">
		<th class="header-lineScore">
			3
		</th>
		<th class="header-lineScore">
			4
		</th>
		<th class="header-lineScore" id="otScore-<?= $game->id ?>">
			<?php
				if(count($game->away->lineScores) > 4) {
					echo 'OT(' . count($game->away->lineScores) - 4 . ')';
				}
			?>
		</th>
		<th class="header-lineScore">
			T
		</th>
		<th class="header-others">
			<?php if($firstRow) { echo "Picked"; } ?>
		</th>
		<th class="header-spread">
			<?php if($firstRow) { echo 'Spread'; } ?>
		</th>
		<th class="header-blank">
		</th>
		<th class="header-pick">
			<?php
			if(isset($_SESSION['uid']) && $firstRow) {
				echo 'Your Pick';
			}
			?>
		</th>
		<th class="header-score">
		<?php
			if(isset($_SESSION['uid']) && $firstRow) {
				echo 'Score';
			}
			?>
		</th>
		<th class="header-compare" colspan="2">
			<?php
			if(isset($_SESSION['uid']) && $firstRow) {
				?>
				Compare to:
				<select id="selectCompare" class="form-select selectCompare text-center" onChange="compare()">
					<option val="-1"></option>
					<?php
					foreach($users as $user) {
						if($user->id != $_SESSION['uid']) {
							?>
							<option val="<?= $user->id ?>"><?= $user->name ?></option>
							<?php
						}
					}
					?>
				</select>
				<?php
			}
			?>
		</th>
	</tr>
	<?php
	printRowTeam($dbConn, $game->away, $game, 'away');
	printRowTeam($dbConn, $game->home, $game, 'home');
	?>
	<tr>
		<td class="gameStatus" id="gameStatus-<?= $game->id ?>" colspan="3">
			<?php
			if(in_array($game->statusID, array(2, 22))) {
				if($game->possession == $game->home->id) {
					$hasBall = 'home';
					$notBall = 'away';
				} else {
					$hasBall = 'away';
					$notBall = 'home';
				}

				$yardLine = $game->yardLine;
				if($yardLine > 50) {
					$yardLine = abs($yardLine - 100);
					$fieldSide = $game->away->abbr;
				} else if($yardLine != 50) {
					$fieldSide = $game->home->abbr;
				} else {
					$fieldSide = 'the';
				}

				if($yardLine - $game->toGo == 0) {
					$toGo = 'Goal';
				} else {
					$toGo = $game->toGo;
				}

				if($game->down > 0) {
					$gameStatus = getOrdinal($game->down) . ' and ' . $toGo . ' at ' . $fieldSide . ' ' . $yardLine;
				} else {
					$gameStatus = 'Kickoff ' + $game->{$hasBall}->abbr;
				}

				echo $gameStatus;
			}
			?>
		</td>
	</tr>
	<tr class="lastRow" id="lastRow-<?= $game->id ?>">
		<td class="gameName" id="gameName-<?= $game->id ?>" colspan="9">
			<?php
			if(($game->name != '' && $game->name != null) || ($game->customName != '' && $game->customName != null)) {
				if($game->name != '' && $game->name != null)  {
					echo $game->name;
				}
				if(($game->name != '' && $game->name != null) && ($game->customName != '' && $game->customName != null)) {
					echo ' - ';
				}
				if($game->customName != '' && $game->customName) {
					echo $game->customName;
				}
			}
			?>
		</td>
		<td class="cell-blank" colspan="4">
		</td>
	</tr>
	<?php
	if(($game->name != '' && $game->name != null) || ($game->customName != '' && $game->customName != null)) {
		?>
		<tr class="lastRow"></tr>
		<?php
	}
}

function printRowTeam($dbConn, $team, $game, $homeAway) {
	if($game->winnerID == $team->id && $game->completed) {
		$winnerClass = 'winner-' . $team->id;
	} else {
		$winnerClass = '';
	}
	?>
	<tr>
		<td rowspan="2" class="logo <?= $winnerClass ?> rounded-start-4" id="logoCell-<?= $homeAway. '-' . $game->id ?>">
			<img height="35" width="35" src="<?= getLogo($dbConn, $team->id) ?>" id="logo-<?= $homeAway . '-' . $game->id ?>">
		</td>
		<td class="rank <?= $winnerClass ?>" id="rank-<?= $homeAway . '-' . $game->id ?>" rowspan="2">
			<?php
			if($team->rank != null) {
				echo $team->rank;
			}
			?>
		</td>
		<td class="teamName <?= $winnerClass ?><?php
		if(in_array($game->statusID, array(2, 22))) {
			if($team->id == $game->possession) {
				echo ' possession';
				if(($homeAway == 'home' && $game->yardLine >= 80) || ($homeAway == 'away' && $game->yardLine <= 20)) {
					echo ' redzone';
				}
			}
		}
		?>" id="teamName-<?= $homeAway . '-' . $game->id ?>">
			<a class="link-underline link-underline-opacity-0 link-underline-opacity-100-hover link-light <?= $winnerClass ?>" href="https://www.espn.com/college-football/team/_/id/<?= $team->id ?>" target="_blank" id='schoolLink-<?= $homeAway . '-' . $game->id ?>'><?= $team->school ?></a>
		</td>
		<?php
		$teamTotal = null;
		for($period = 1; $period <= 5; $period++) {
			?>
			<td class="lineScore <?= $winnerClass ?>" id="lineScore-<?= $homeAway . '-' . $period . '-' . $game->id ?>" rowspan="2">
				<?php
				if($period != 5) {
					if(isset($team->lineScores[$period])) {
						echo $team->lineScores[$period];
						$teamTotal += $team->lineScores[$period];
					}
				} else {
					if(isset($team->lineScores[$period])) {
						$otScore = 0;
						for($ot = 5; $ot <= count($team->lineScores); $ot++) {
							$otScore += $team->lineScores[$ot];
						}
						echo $otScore;
						$teamTotal += $otScore;
					}
				}
				?>
			</td>
			<?php
		}
		?>
		<td class="total <?= $winnerClass ?>" id="total-<?= $homeAway . '-' . $game->id ?>" rowspan="2">
			<?php
			if(isset($team->lineScores[1])) {
				echo $teamTotal;
			}
			?>
		</td>
		<td class="othersPicks <?= $winnerClass ?> rounded-end-4" id="others-<?= $homeAway . '-' . $game->id ?>" rowspan="2">
			<?= $team->picked ?>
		</td>
		<?php
		if($game->spread != 0) {
			?>
			<td class="spread" id="spread-<?= $homeAway . '-' . $game->id ?>" rowspan="2">
				<?php
				if($team->id == $game->favID) {
					echo '-' . number_format($game->spread, 1);
				}
				?>
			</td>
			<?php
		} else if($homeAway == 'away') {
			?>
			<td class="spread" id="spread-<?= $homeAway . '-' . $game->id ?>" rowspan="4">
				EVEN
			</td>
			<?php
		}
		if($homeAway == 'away') {
			?>
			<td class="venueName" id="venueName-<?= $game->id ?>">
				<?= $game->venue->name ?>
			</td>
			<td class="pick" id="tdpick-<?= $game->id ?>" rowspan="4">
				<?php 
				if(isset($_SESSION['uid'])) {
					?>
						<select class="form-select selectPick <?php
						if($game->completed) {
							if($game->winnerID == $game->pick || ($game->jokeGame && $game->pick != -1)) {
								echo 'winner-' . $game->pick;
							} else {
								echo 'loserSelect';
							}
						}
						?>
						"
						<?php
						if($game->date <= new DateTime()) {
							echo "disabled";
						}
						?> id="pick-<?= $game->id ?>" onChange="setPick(<?= $game->id ?>)">
							<option <?php if($game->pick == -1) echo 'selected'?> value="-1"></option>
							<option <?php if($game->pick == $game->away->id) echo 'selected'?> value="<?= $game->away->id ?>"><?=$game->away->school ?></option>
							<option <?php if($game->pick == $game->home->id) echo 'selected'?> value="<?= $game->home->id ?>"><?=$game->home->school ?></option>
						</select>
					
					<?php
				}
				?>
			</td>
			<td class="score
				<?php
				if(isset($_SESSION['uid']) && $game->completed) {
					if($game->winnerID == $game->pick || ($game->jokeGame && $game->pick != -1)) {
						echo ' scoreWinner';
					}
				}
				?>
				" id="score-<?= $game->id ?>" rowspan="4">
				<?php
				if($game->completed && isset($_SESSION['uid'])) {
					if($game->winnerID == $game->pick || ($game->jokeGame && $game->pick != -1)) {
						echo $GLOBAL['userScore'] += $game->multiplier;
					} else {
						if($GLOBAL['userScore'] == 0) {
							echo "0";
						} else {
							echo $GLOBAL['userScore'];
						}
					}
				}
				?>
			</td>
			<td class="comparePick" rowspan="4">
				<?php
				if(isset($_SESSION['uid'])) {
					?>
					<select class="form-select selectPick" disabled id="compare-<?= $game->id ?>">
						<option selected value="-1"></option>
						<option value="<?= $game->away->id ?>"><?=$game->away->school ?></option>
						<option value="<?= $game->home->id ?>"><?=$game->home->school ?></option>
					</select>
					<?php
				}
				?>
			</td>
			<td class="score" id="compareScore-<?= $game->id ?>" rowspan="4">
			</td>
			<?php
		} else {
			?>
			<td class="gameLink" id="gameLink-<?= $game->id ?>" rowspan="2">
				<a class="espnLink d-inline-flex focus-ring py-1 px-2 text-decoration-none border rounded-2 link-light" href="https://www.espn.com/college-football/game/_/gameId/<?= $game->id ?>" target="_blank">ESPN Gamecast</a>
			</td>
			<?php
		}
		?>
	</tr>
	<tr>
		<td class="record <?= $winnerClass ?>" id="record-<?= $homeAway . '-' . $game->id ?>">
			<?php
				echo '(' . $team->wins . '-' . $team->losses;
				if($team->conf->id != 18 && $team->conf->id != 32) {
					echo ', ' . $team->confWins . '-' . $team->confLosses . ' ' . $team->conf->abbr;
				}
				echo ')';
			?>
		</td>
		<?php
		if($homeAway == 'away') {
			?>
			<td class="cityState" id="cityState-<?= $game->id ?>">
				<?php
				echo $game->venue->city . ', ';
				if($game->venue->country == 'US') {
					echo $game->venue->state;
				} else {
					echo countryName($game->venue->country);
				}
				?>
			</td>
			<?php
		}
		?>
	</tr>
	<?php
}

function pageHeader($pageTitle) {
	if(isset($_SESSION['color'])) {
		$color = $_SESSION['color'];
		$altColor = $_SESSION['alternateColor'];
	} else {
		$color = $GLOBALS['defaultColor'];
		$altColor = $GLOBALS['defaultAltColor'];
	}
	?>
	<html data-bs-theme="dark">
		<head>
			<title><?= $GLOBALS['name'] ?> Pick 'Em - <?= $pageTitle ?></title>
			<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
			<link href="css/pickem.css" rel="stylesheet">
			<link href="css/scoreboard.css" rel="stylesheet">
			<link href="css/schoolColors.php" rel="stylesheet">
			<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>			<script src="https://code.jquery.com/jquery-3.7.0.min.js" integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g=" crossorigin="anonymous"></script>
			<script src="js/functions.js"></script>
		</head>
		<body>
			<div class="container-fluid h-100">
				<header class="p-2" style="background-color: #<?= $color ?>; color: #<?= $altColor ?>">
						<div class="row align-items-center">
							<div class="col-auto">
								<img src="logo.php" height="30px">
							</div>
							<div class="col w-100 pickEm">
								<h3>Pick 'Em</h3>
							</div>
							<div class="col-auto justify-content-center text-center">
								<?php
								if(isset($_SESSION['uid'])) {
									?>
									<a href="logout.php" class="link-underline link-underline-opacity-0 link-underline-opacity-100-hover" style="color: #<?= $altColor ?>">
										<img src="<?= $_SESSION['logo'] ?>" height="30px">
										<br>
										Log Out
									</a>
									<?php
								} else {
									?>
									<a href="login.php" class="link-underline link-underline-opacity-0 link-underline-opacity-100-hover" style="color: #<?= $altColor ?>"><h5>Log In</h5></a>
									<?php
								}
								?>
							</div>
						</div>
				</header>
				<div class="row d-flex flex-column justify-content-center align-items-center">
	<?php
}
?>