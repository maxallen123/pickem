<?php

function pageHeader($dbConn, $pageTitle) {
	if(isset($_SESSION['uid'])) {
		$query = 'SELECT users.id, users.email, users.team, teams.color, teams.alternateColor, teamLogos.href FROM users LEFT JOIN teams ON users.team = teams.id LEFT JOIN teamLogos ON teamLogos.teamId = users.team WHERE users.id = ? AND teamLogos.is_dark = 1';
		$queryArray = array($_SESSION['uid']);
		$rslt = sqlsrv_query($dbConn, $query, $queryArray);
		$user = sqlsrv_fetch_array($rslt);
		$_SESSION['uid'] = $user['id'];
		$_SESSION['email'] = $user['email'];
		$_SESSION['team'] = $user['team'];
		$_SESSION['color'] = $user['color'];
		$_SESSION['alternateColor'] = $user['alternateColor'];
		$_SESSION['logo'] = $user['href'];
	}
	if(!isset($_SESSION['team'])) {
		$_SESSION['team'] = 2633;
	}
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
			<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css" integrity="sha512-ELV+xyi8IhEApPS/pSj66+Jiw+sOT1Mqkzlh8ExXihe4zfqbWkxPRi8wptXIO9g73FSlhmquFlUOuMSoXz5IRw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
			<link href="css/pickem.css?version=2" rel="stylesheet">
			<link href="css/scoreboard.css?version=3" rel="stylesheet">
			<link href="css/schoolColors.php?version=2" rel="stylesheet">
			<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>
			<script src="https://code.jquery.com/jquery-3.7.0.min.js" integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g=" crossorigin="anonymous"></script>
			<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js" integrity="sha256-xLD7nhI62fcsEZK2/v8LsBcb4lG7dgULkuXoXB/j91c=" crossorigin="anonymous"></script>
			<script src="js/functions.js"></script>
			<meta name="viewport" content="width=device-width, initial-scale=0.65">
		</head>
		<body>
				<nav class="navbar navbar-expand" style="background-color: #<?= $color ?>; color: #<?= $altColor ?>">
					<div class="container-fluid pageHeader">
						<span class="pickEm">
							<ul class="navbar-nav me-auto my-2 my-lg-0 navbar-nav-scroll">
								<li class="nav-item">
									<img src="images/siteLogo.php?teamID=<?= $_SESSION['team'] ?>&height=30" height="30px">
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
								<img src="images/teamLogo.php?teamID=<?= $_SESSION['team'] ?>&height=30" height="30px">
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

function printGame($dbConn, $game, $pickPage) {
	?>
	<div class="col-lg-6">
		<div id="outerGameWrapper-<?= $game->id ?>" <?php if($game->multiplier == 4) echo 'class="gotw"' ?>>
			<div class="fullGameBox">
				<div class="col-4 game">
					<?php
					gameHeaderRow($game, $pickPage);
					printRowTeam($game->away, $game, 'away', $pickPage);
					printRowTeam($game->home, $game, 'home', $pickPage);
					?>
				</div>
				<?php
				printSpreadBox($game);
				printInfoBox($game);
				?>
			</div>
			<?php
			gameStatus($game);
			gameName($game);
			?>
		</div>
	</div>
	<?php
}

function gameHeaderRow($game, $pickPage) {
	?>
	<div class="row header-row">
		<div id="header-status-<?= $game->id ?>" class="header-status col-3">
		</div>
		<div class="header-lineScore">
			1
		</div>
		<div class="header-lineScore">
			2
		</div>
		<div class="header-lineScore">
			3
		</div>
		<div class="header-lineScore">
			4
		</div>
		<div id="header-OT-<?= $game->id ?>" class="header-lineScore">
			<?php
			if(count($game->away->lineScores) > 4) {
				echo 'OT (' . count($game->away->lineScores) - 4 . ')';
			}
			?>
		</div>
		<div class="header-total">
			T
		</div>
		<?php
		if($pickPage) {
			?>
			<div class="header-others">
				Pk'd
			</div>
			<?php
		}
		?>
	</div>
	<?php
}

function printRowTeam($team, $game, $homeAway, $pickPage) {
	if($game->winnerID == $team->id) {
		$winnerClass = 'winner-' . $team->id;
	} else {
		$winnerClass = '';
	}
	?>
	<div id="teamRow-<?= $game->id ?>-<?= $team->id ?>" class="row gameRow <?= $winnerClass ?>">
		<div id="logoCell-<?= $game->id ?>-<?= $team->id ?>" class="logo">
			<img id="logo-<?= $game->id ?>-<?= $team->id ?>" src="images/teamLogo.php?teamID=<?= $team->id ?>&height=30">
		</div>
		<div id="rank-<?= $game->id ?>-<?= $team->id ?>" class="rank">
			<?php
			if($team->rank != null) {
				echo $team->rank;
			}
			?>
		</div>
		<div class="teamName">
			<div id="teamName-<?= $team->id ?>-<?= $game->id ?>" class="upperName">
				<a id="teamLink-<?= $game->id ?>-<?= $team->id ?>" class="link-light link-underline link-underline-opacity-100-hover link-underline-opacity-0
				<?php 
				if($game->winnerID == $team->id) {
					echo ' winner-' . $game->winnerID;
				}
				?>" href="https://www.espn.com/college-football/team/_/id/<?= $team->id ?>"><?= $team->school ?></a>
			</div>
			<div class="lowerRecord">
				<?php
				echo '(' . $team->wins . '-' .  $team->losses;
				if($team->conf->id != 18 && $team->conf->id != 32) {
					echo ', ' . $team->confWins . '-' . $team->confLosses . ' ' . $team->conf->abbr;
				}
				echo ')';
				?>
			</div>
		</div>
		<?php
		$teamTotal = null;
		for($period = 1; $period <= 5; $period++) {
			?>
			<div id="lineScore-<?= $game->id ?>-<?= $team->id ?>-<?= $period ?>" class="lineScore
				<?php
				if($period == 1) {
					echo 'lineScoreFirst';
				}
				?>">
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
			</div>
			<?php
		}
		?>
		<div id="total-<?= $team->id ?>-<?= $game->id ?>" class="total">
			<?= $teamTotal ?>
		</div>
		<?php
		if($pickPage) {
			?>
			<div id="others-<?= $team->id ?>-<?= $game->id ?>" class="othersPicks">
				<?= $team->picked ?>
			</div>
			<?php
		}
		?>
	</div>
	<?php
}

function printSpreadBox($game) {
	?>
	<div>
		<div class="spreadFirstRow">
			Spd
		</div>
		<?php 
		if($game->spread == 0 || $game->spread == null) {
			?>
			<div class="spreadEven">
				<?php if($game->spread != null) echo 'EVEN'; ?>
			</div>
			<?php
		} else {
			?>
			<div class="spreadRow">
				<?php 
				if($game->favID == $game->away->id) {
					echo '-' . number_format($game->spread, 1);
				}?>
			</div>
			<div class="spreadRow">
				<?php
				if($game->favID == $game->home->id) {
					echo '-' . number_format($game->spread, 1);
				}
				?>
			</div>
			<?php
		}
		?>
	</div>
	<?php
}

function printInfoBox($game) {
	?>
	<div class="infoBox">
		<div class="venueName">
			<?= $game->venue->name ?>
		</div>
		<div class="cityState">
			<?= $game->venue->city ?>, <?= $game->venue->stateCountry() ?>
		</div>
		<div class="gameLink">
			<a class="espnLink d-inline-flex focus-ring py-1 px-1 text-decoration-none border rounded-2 link-light" href="https://www.espn.com/college-football/game/_/gameId/<?= $game->id ?>" target="_blank">ESPN Gamecast</a>
		</div>
	</div>
	<?php
}

function gameStatus($game) {
	$class = 'gameStatus';
	if(!in_array($game->statusID, array(2, 22))) {
		$class .= ' hidden';
	}
	if(($game->name == '' || $game->name == null) && ($game->customName == '' || $game->customName == null)) {
		$class .= ' lastRow';
	}
	?>
	<div id="gameStatus-<?= $game->id ?>" class="<?= $class ?>">
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
				$gameStatus = 'Kickoff ' . $game->{$hasBall}->abbr;
			}

			echo $gameStatus;
		}
		?>
	</div>
	<?php
}

function gameName($game) {
	$gameName = $game->gameNameBar();
	if($gameName != '') {
		?>
		<div class="gameName lastRow">
			<?= $gameName ?>
		</div>
		<?php
	}
}

function printGamePicker($dbConn, $game) {
	?>
	<div class="col-lg-6">
		<div class="row pickRow">
			<div class="userSelectCell">
				<div class="selectLogoCell" id="logoCell-<?= $game->id ?>">
					<?php
					if($game->pick > 0) {
						?>
						<img id="logoPick-<?= $game->id ?>" src="<?= $GLOBALS['baseURL'] ?>/images/teamLogo.php?teamID=<?= $game->pick ?>&height=30">
						<?php
					}
					?>
				</div>
				<div class="selectCell">
					<select 
						id="pick-<?= $game->id ?>" 
						class="form-select selectPick
						<?php 
						if($game->completed) {
							if(($game->winnerID == $game->pick) || ($game->jokeGame && $game->pick > 0)) {
								echo ' winner-' . $game->pick;
							} else {
								echo ' loserSelect';
							}
						}
						?>" 
						onchange="setPick(<?= $game->id ?>)" 
						<?php if($game->date <= new DateTime()) echo 'disabled'; ?>>
						<option value="-1" <?php if(!($game->pick > 0)) echo 'selected'; ?>></option>
						<option value="<?= $game->away->id ?>" <?php if($game->pick == $game->away->id) echo 'selected'; ?>><?= $game->away->school ?></option>
						<option value="<?= $game->home->id ?>" <?php if($game->pick == $game->home->id) echo 'selected'; ?>><?= $game->home->school ?></option>
					</select>
				</div>
				<div id="score-<?= $game->id ?>" class="score
				<?php 
				if($game->completed) {
					if(($game->winnerID == $game->pick) || ($game->jokeGame && $game->pick > 0)) {
						echo ' scoreWinner';
					}
				} 
				?>">
					<?php
					if($game->completed && isset($_SESSION['uid'])) {
						if($game->winnerID == $game->pick || ($game->jokeGame && $game->pick != -1)) {
							echo $_SESSION['userScore'] += $game->multiplier;
						} else {
							if($_SESSION['userScore'] == 0) {
								echo "0";
							} else {
								echo $_SESSION['userScore'];
							}
						}
					}
					?>
				</div>
			</div>
			
			<div class="userSelectCell">
				<div class="selectLogoCell" id="logoCompareCell-<?= $game->id ?>">
				</div>
				<div class="selectCell">
					<select id="compare-<?= $game->id ?>" class="form-select othersSelectPick" disabled>
						<option value="-1" selected></option>
						<option value="<?= $game->away->id ?>"><?= $game->away->school ?></option>
						<option value="<?= $game->home->id ?>"><?= $game->home->school ?></option>
					</select>
				</div>
				<div id="compareScore-<?= $game->id ?>" class="score">
				</div>
			</div>
		</div>
	</div>
	<?php
}
?>