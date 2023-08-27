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

function printGame($dbConn, $game, $firstRow, $users) {
	?>
	<tr>
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
		<th class="header-lineScore">
			<?php
				if(count($game->away->lineScores) > 4) {
					echo 'OT(' . count($game->away->lineScores) - 4 . ')';
				}
			?>
		</th>
		<th class="header-lineScore">
			T
		</th>
		<th class="header-spread">
			<?php if($firstRow) { echo 'Spread'; } ?>
		</th>
		<th class="header-others">
			<?php if($firstRow) { echo "Picked"; } ?>
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
		<th class="header-compare">
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
	<tr class="lastRow" id="lastRow-<?= $game->id ?>">
		<td class="gameName" id="gameName-<?= $game->id ?>" colspan="9">
			<?= $game->name ?>
		</td>
		<td class="cell-blank" colspan="4">
		</td>
	</tr>
	<?php
	if($game->name != '' && $game->name != null) {
		?>
		<tr class="lastRow"></tr>
		<?php
	}
}

function printRowTeam($dbConn, $team, $game, $homeAway) {
	?>
	<tr>
		<td rowspan="2" class="logo">
			<img height="35" width="35" src=<?= getLogo($dbConn, $team->id) ?> id="logo-<?= $homeAway . '-' . $game->id ?>">
		</td>
		<td class="rank" id="rank-<?= $homeAway . '-' . $game->id ?>" rowspan="2">
			<?php
			if($team->rank != null) {
				echo $team->rank;
			}
			?>
		</td>
		<td class="teamName" id="teamName-<?= $homeAway . '-' . $game->id ?>">
			<a class="link-underline link-underline-opacity-0 link-underline-opacity-100-hover link-light" href="https://www.espn.com/college-football/team/_/id/<?= $team->id ?>" target="_blank"><?= $team->school ?></a>
		</td>
		<?php
		$teamTotal = null;
		for($period = 1; $period <= 5; $period++) {
			?>
			<td class="lineScore" id="lineScore-<?= $homeAway . '-' . $period . '-' . $game->id ?>" rowspan="2">
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
		<td class="total" id="total-<?= $homeAway . '-' . $game->id ?>" rowspan="2">
			<?php
			if($teamTotal != null) {
				echo $teamTotal;
			}
			?>
		</td>
		<td class="spread" id="spread-<?= $homeAway . '-' . $game->id ?>" rowspan="2">
			<?php
			if($team->id == $game->favID) {
				echo '-' . number_format($game->spread, 1);
			}
			?>
		</td>
		<td class="othersPicks" id="others-<?= $homeAway . '-' . $game->id ?>" rowspan="2">
			<?= $team->picked ?>
		</td>
		<?php
		if($homeAway == 'away') {
			?>
			<td class="venueName" id="venueName-<?= $game->id ?>">
				<?= $game->venue->name ?>
			</td>
			<td class="pick" id="tdpick-<?= $game->id ?>" rowspan="4">
				<?php 
				if(isset($_SESSION['uid'])) {
					?>
						<select class="form-select selectPick" <?php
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
		<td class="record" id="record-<?= $homeAway . '-' . $game->id ?>">
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