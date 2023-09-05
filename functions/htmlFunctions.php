<?php

function printGame($dbConn, $game, $firstRow, $users) {
	?>
	<tr class="firstRow <?php if($game->multiplier == 4) echo 'goty'; ?>">
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
	<tr class="<?php if($game->multiplier == 4) echo 'goty'; ?>">
		<td class="gameStatus" id="gameStatus-<?= $game->id ?>" colspan="17">
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
	<tr class="lastRow <?php if($game->multiplier == 4) echo 'goty'; ?>" id="lastRow-<?= $game->id ?>">
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
		<td class="cell-blank" colspan="8">
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
	<tr class="<?php if($game->multiplier == 4) echo 'goty'; ?>">
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
	<tr class="<?php if($game->multiplier == 4) echo 'goty'; ?>">
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
				<nav class="navbar navbar-expand" style="background-color: #<?= $color ?>; color: #<?= $altColor ?>">
					<div class="container-fluid pageHeader">
						<span class="pickEm">
							<ul class="navbar-nav me-auto my-2 my-lg-0 navbar-nav-scroll">
								<li class="nav-item">
									<img src="logo.php" height="30px">
									&nbsp&nbspPick 'Em
								</li>
								<li class="nav-item dropdown">
									<a class="nav-link dropdown-toggle hamburger" style="background-color: #<?= $color ?>; color: #<?= $altColor ?>" href="#" role="button" data-bs-toggle="dropdown">
									☰
									</a>
									<ul class="dropdown-menu" style="background-color: #<?= $color ?>; color: #<?= $altColor ?>">
										<li><a class="dropdown-item" style="background-color: #<?= $color ?>; color: #<?= $altColor ?>" href="index.php">Main Page</a></li>
										<li><a class="dropdown-item" style="background-color: #<?= $color ?>; color: #<?= $altColor ?>" href="scoreboard.php">User Scoreboard</a></li>
									</ul>
								</li>
							</ul>
						</span>
						<?php
						if(isset($_SESSION['uid'])) {
							?>
							<span class="logOut"><a href="logout.php" class="link-underline link-underline-opacity-0 link-underline-opacity-100-hover" style="color: #<?= $altColor ?>">
								<img src="<?= $_SESSION['logo'] ?>" height="30px">
								<br>
								Log Out
							</a></span>
							<?php
						} else {
							?>
							<a href="login.php" class="link-underline link-underline-opacity-0 link-underline-opacity-100-hover" style="color: #<?= $altColor ?>"><h5>Log In</h5></a>
							<?php
						}
						?>
					</div>
				</nav>
				<div class="row d-flex flex-column justify-content-center align-items-center w-100 p-4">
	<?php
}

function mailBodyStart() {
	$body = '<table style="background: #212529; width: 100%">';
	$body .= '<tr><td>';
	return $body;
}

function mailHeader($user) {
	$color = $user->color;
	$altColor = $user->altColor;
	ob_start();
	?>
	<table style="background: #<?= $color ?>; color: #<?= $altColor ?>; width: 100%">
	<tr>
		<td><img style="height: 30px" src="<?= logoInlineEmail($user) ?>"></td>
	</tr>
	<tr style="font-variant: small-caps; font-weight: bold; font-size: 24px">
		<td> Pick 'Em</td>
	</tr>
	</table>
	<?php
	$header = ob_get_contents();
	ob_end_clean();
	return $header;
}

function mailScoreboard($dbConn, $users, $curWeek) {
	foreach($users as $key => $user) {
		$users[$key]->liveScoreRecord($dbConn, $curWeek);
	}
	usort($users, 'scoreSort');
	ob_start();
	?>
	<table style="color: white; text-align=center; width: 514px; margin-top: 30px; margin-left: auto; margin-right: auto; border: 1px solid gray; border-collapse: collapse;">
		<tr style="font-variant: small-caps; font-size: 18px; text-align: center;">
			<th style="width: 40px">RK</th>
			<th colspan="2">User</th>
			<th>Rec</th>
			<th>Pts</th>
		</tr>
	<?php
	$rank = 0;
	$row = 0;
	$lastScore = 0;
	foreach($users as $user) {
		$row++;
		if($lastScore != $user->score) {
			$rank = $row;
		}
		$lastScore = $user->score;
		?>
		<tr style="font-size: 16px; border: 1px solid gray;">
			<td style="text-align: right;"><?= $rank ?></td>
			<td style="text-align: right; width: 50px;"><img style="height: 35px; width: 35px" src="<?= getLogo($dbConn, $user->team) ?>"></td>
			<td style="width: 300px; font-variant: small-caps;"><?= $user->name ?></td>
			<td style="width: 50px; text-align: center;"><?= $user->wins ?> - <?= $user->losses ?></td>
			<td style="width: 50px; text-align: right;"><?= $user->score ?></td>
		</tr>
		<?php
	}
	echo '</table>';
	$scoreboard = ob_get_contents();
	ob_end_clean();
	return $scoreboard;
}

function mailWeeksGames($dbConn, $newWeek, $oldWeek) {
	$games = getWeeksGames($dbConn, $newWeek);
	$ranks = getRankArray($dbConn, $oldWeek);
	ob_start();

	?>
	<table style="color: white; text-align=center; width: 650px; margin-top: 30px; margin-left: auto; margin-right: auto; border: 1px solid gray; border-collapse: collapse;">
	<tr>
		<th colspan="7">Teams</th>
		<th>Location</th>
		<th>Vegas Spread</th>
	</tr>
	<?php
	foreach($games as $game) {
		if($game->multiplier == 4) {
			mailGamesRows($dbConn, $game, $ranks);
		}
	}
	foreach($games as $game) {
		if($game->multiplier != 4) {
			mailGamesRows($dbConn, $game, $ranks);
		}
	}
	echo "</table>";

	$gameList = ob_get_contents();
	ob_end_clean();
	return $gameList;
}

function mailGamesRows($dbConn, $game, $ranks) {
	?>
	<tr style="vertical-align: middle; border: 1px solid gray;
		<?php
		if($game->multiplier == 4) {
			echo 'background: #140330;';
		}
		?>
		">
		<td><img style="height: 35px; width: 35px" src="<?= getLogo($dbConn, $game->away->id) ?>"></td>
		<?php
			if(isset($ranks[$game->away->id])) 
				echo '<td style="font-size: 10px">' . $ranks[$game->away->id] . '</td>'; 
			else 
				echo '<td></td>';
			echo '<td style="font-variant: small-caps; font-size: 16px">' . $game->away->school . '</td>';
			echo '<td style="font-variant: small-caps; font-size: 12px; width: 25px; text-align: center;">';
			if($game->isNeutral) echo 'vs.'; else echo 'at';	
			echo '</td>';
		?>
		<td><img style="height: 35px; width: 35px" src="<?= getLogo($dbConn, $game->home->id) ?>"></td>
		<?php
			if(isset($ranks[$game->home->id])) 
				echo '<td style="font-size: 10px">' . $ranks[$game->home->id] . '</td>'; 
			else 
				echo '<td></td>';
			echo '<td style="font-variant: small-caps; font-size: 16px">' . $game->home->school . '</td>';
		?>
		<td style="font-variant: small-caps; font-size: 14px;"><?= $game->venue->name ?><br><?= $game->venue->city ?>,<?php if($game->venue->country == 'US') echo $game->venue->state; else echo countryName($game->venue->country); ?></td>
		<td style="font-size: 14px; width: 100px">
			<?php
			if($game->favID == $game->home->id) {
				echo $game->home->abbr;
			} else {
				echo $game->away->abbr;
			}
			echo ' -' . $game->spread;
			?>
		</td>
		<?php
		if($game->customName != null) {
			?>
			<td style="font-size: 14px; font-variant: small-caps; font-weight: bold"><?= $game->customName ?></td>
			<?php
		}
		?>
	</tr>
	<?php
}
?>