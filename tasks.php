<?php

require('functions.php');

function loadTeams() {
	$dbConn = sqlConnect();
	$conferences = getConfs($dbConn);
	$context = httpRequestOpts();
	$teams = json_decode(file_get_contents($GLOBALS['teamsURL'], false, $context));

	foreach($teams as $team) {
		loadTeam($dbConn, $team, $conferences);
	}
}

function loadConfs() {
	$dbConn = sqlConnect();
	$context = httpRequestOpts();

	$confs = json_decode(file_get_contents($GLOBALS['confURL'], false, $context));

	foreach($confs as $conf) {
		loadConf($dbConn, $conf);
	}
}

function loadWeeks($year) {
	$dbConn = sqlConnect();
	$context = httpRequestOpts();

	$weeks = json_decode(file_get_contents(str_replace('$year', $year, $GLOBALS['calendarURL']), false, $context));
	
	foreach($weeks as $week) {
		loadWeek($dbConn, $week);
	}
}

function loadGames($year, $seasonType, $week) {
	$dbConn = sqlConnect();
	$context = httpRequestOpts();
	$conferences = getConfs($dbConn);
	$teams = getTeams($dbConn);

	$weekArray = getWeekArray($dbConn, $year, $seasonType, $week);

	if($seasonType == 1) {
		$seasonString = 'regular';
	} else {
		$seasonString = 'postseason';
	}

	$search = array('$year', '$seasonType', '$week');
	$replace = array($year, $seasonString, $week);
	$searchString = str_replace($search, $replace, $GLOBALS['gamesURL']);

	$games = json_decode(file_get_contents($searchString, false, $context));

	foreach($games as $game) {
		loadGame($dbConn, $game, $conferences, $teams, $weekArray);
	}
}

function loadVenues() {
	$dbConn = sqlConnect();
	$context = httpRequestOpts();

	$venues = json_decode(file_get_contents($GLOBALS['venuesURL'], false, $context));

	foreach($venues as $venue) {
		loadVenue($dbConn, $venue);
	}
}

function loadGamesCurWeek2($forceCheck) {
	$dbConn = sqlConnectAll();
	$GLOBALS['graceOffset'] = -6;
	$GLOBALS['graceUnit'] = 'hour';
	$curWeek = getCurWeek($dbConn[0]);
	$confs = getConfsObjs($dbConn[0]);
	$teams = getTeams($dbConn[0]);

	$gameFuture = 2;
	$gamePast   = -6;
	$intervalMinutesIdle = 60;

	$query = 'SELECT id FROM GAMES WHERE startDate >= DATEADD(hour, ' . $gamePast . ', GETDATE()) AND startDate <= DATEADD(hour, ' . $gameFuture . ', GETDATE())';
	// If either a game might be near, going on, or if it's time for an update
	if(sqlsrv_has_rows(sqlsrv_query($dbConn[0], $query)) || (round(time() / 60) % $intervalMinutesIdle) == 0 || $forceCheck) {
		$success = 0;
		$confArray = array(90);
		while($success == 0) {
			foreach($confArray as $conf) {
				$limit = 300;
				$search = array('$year', '$week', '$seasonType', '$limit', '$conf');
				do {
					echo "Pulling data, limit: " . $limit . "\n";
					$replace = array($curWeek->year, $curWeek->week, $curWeek->seasonType + 1, $limit, $conf);
					$searchString = str_replace($search, $replace, $GLOBALS['espnScoreboardURL']);
					$scoreboardStr = @file_get_contents($searchString);
					sleep(5);
					$scoreboardStr = @file_get_contents($searchString);
					$limit++;
				} while(strlen($scoreboardStr) < 1000 && $limit < 375);

				if(strlen($scoreboardStr) > 1000) {
					$success = 1;
					$scoreboard = json_decode($scoreboardStr);
					$games = $scoreboard->events;

					foreach($games as $game) {
						echo "\t" . $game->id . "\n";
						// Check what status we have in DB
						$sqlGameQuery = 'SELECT statusID, predictor FROM games WHERE id = ?';
						$sqlGameArray = array($game->id);
						$sqlGameRslt = sqlsrv_query($dbConn[0], $sqlGameQuery, $sqlGameArray);
						if(sqlsrv_has_rows($sqlGameRslt)) {
							$row = sqlsrv_fetch_array($sqlGameRslt);
							$statusID = $row['statusID'];
							$predictor = $row['predictor'];
						} else {
							$statusID = null;
						}

						// If game is scheduled or we don't have it then load game details
						if(($statusID == 1 || $statusID == null || $predictor == null || in_array($statusID, array(2, 22, 23)) && isset($teams[$game->competitions[0]->competitors[0]->id]) && isset($teams[$game->competitions[0]->competitors[1]->id]))) {
							echo "\t\tPulling game details\n";
							$search = '$gameID';
							$replace = (string)$game->id;
							$searchString = str_replace($search, $replace, $GLOBALS['espnGameURL']);
							do{
								$gameStr = @file_get_contents($searchString);
							} while(strlen($gameStr) < 1000);
							$gameDetails = json_decode($gameStr);
						}

						foreach($dbConn as $instance) {
							loadGameScoreboard($instance, $game, $curWeek);
							if(($statusID == 1 || $statusID == null) && isset($teams[$game->competitions[0]->competitors[0]->id]) && isset($teams[$game->competitions[0]->competitors[1]->id])) {
								updateESPNSpread2($instance, $game->id, $gameDetails);
							}
							if(($predictor == null ||  in_array($statusID, array(2, 22, 23))) && isset($teams[$game->competitions[0]->competitors[0]->id]) && isset($teams[$game->competitions[0]->competitors[1]->id])) {
								updateESPNPredictorProbability($instance, $gameDetails);
							}
						}
					}
				} else {
					$confArray = array();
					foreach($confs as $conf) {
						array_push($confArray, $conf->id);
					}
				}
			}
		}
	} else {
		echo "Not Time to Check: " . (round(time() / 60) % $intervalMinutesIdle) . "\n";
	}
}

function loadRanksESPN() {
	$dbConn = sqlConnectAll();
	$GLOBALS['graceOffset'] = 0;
	$GLOBALS['graceUnit'] = 'hour';
	$curWeek = getCurWeek($dbConn[0]);
	
	$ranks = json_decode(@file_get_contents($GLOBALS['espnRankingURL']));

	$selectedPoll = 'AP Top 25';
	foreach($ranks->rankings as $poll) {
		if($poll->name == 'Playoff Committee Rankings') {
			$selectedPoll = 'Playoff Committee Rankings';
		}
	}

	foreach($ranks->rankings as $poll) {
		if($poll->name == $selectedPoll) {
			print_r($poll->ranks);
			foreach($dbConn as $instance) {
				loadRankESPN($instance, $poll, $curWeek);
			}
		}
	}
}

function loadGamesYear($year) {
	$dbConn = sqlConnectAll();
	$weeks = getAllYearWeeks($dbConn[0], $year);
	$curWeek = getCurWeek($dbConn[0]);
	$confs = getConfsObjs($dbConn[0]);
	$teams = getTeams($dbConn[0]);

	foreach($weeks as $week) {
		$success = 0;
		$confArray = array(90);
		echo $week->weekID . "\n";
		while($success == 0) {
			foreach($confArray as $conf) {
				$limit = 300 + rand(1, 50);
				$search = array('$year', '$week', '$seasonType', '$limit', '$conf');
				do {
					$replace = array($week->year, $week->week, $week->seasonType + 1, $limit, $conf);
					$searchString = str_replace($search, $replace, $GLOBALS['espnScoreboardURL']);
					echo "Pulling data, " . $searchString . "\n";
					$scoreboardStr = @file_get_contents($searchString);
					$limit++;
				} while(strlen($scoreboardStr) < 1000 && $limit < 375);
				
				if(strlen($scoreboardStr) > 1000) {
					$success = 1;
					$scoreboard = json_decode($scoreboardStr);
					$games = $scoreboard->events;

					foreach($games as $game) {
						echo "\t" . $game->id . "\n";
						// Check what we have in DB
						$sqlGameQuery = 'SELECT statusID, predictor, probability FROM games WHERE id = ?';
						$sqlGameArray = array($game->id);
						$sqlGameRslt = sqlsrv_query($dbConn[0], $sqlGameQuery, $sqlGameArray);
						if(sqlsrv_has_rows($sqlGameRslt)) {
							$row = sqlsrv_fetch_array($sqlGameRslt);
							$statusID = $row['statusID'];
							$predictor = $row['predictor'];
						} else {
							$statusID = null;
						}

						// If game is scheduled or we don't have it then load game details
						if(($statusID == null || $predictor == null) && $week->weekID <= $curWeek->weekID && isset($teams[$game->competitions[0]->competitors[0]->id]) && isset($teams[$game->competitions[0]->competitors[1]->id])) {
							echo "\t\tPulling game details\n";
							$search = '$gameID';
							$replace = (string)$game->id;
							$searchString = str_replace($search, $replace, $GLOBALS['espnGameURL']);
							do{
								$gameStr = @file_get_contents($searchString);
							} while(strlen($gameStr) < 1000);
							$gameDetails = json_decode($gameStr);
						}

						if(isset($teams[$game->competitions[0]->competitors[0]->id]) && isset($teams[$game->competitions[0]->competitors[1]->id])) {
							foreach($dbConn as $instance) {
								loadGameScoreboard($instance, $game, $week);
								if(($statusID == null || $predictor == null) && $week->weekID <= $curWeek->weekID) {
									updateESPNSpread2($instance, $game->id, $gameDetails);
									updateESPNPredictorProbability($instance, $gameDetails);
								}
							}
						}
					}
				} else {
					$confArray = array();
					foreach($confs as $conf) {
						array_push($confArray, $conf->id);
					}
				}
			}
		}
	}
}

if(count($argv) > 1) {
	switch($argv[1]) {
		case 'loadTeams':
			loadTeams();
			break;

		case 'loadConfs':
			loadConfs();
			break;

		case 'loadWeeks':
			if(isset($argv[2])) {
				loadWeeks($argv[2]);
				break;
			} else {
				echo 'Year not set';
				break;
			}

		case 'loadGames':
			if(isset($argv[2]) && isset($argv[3]) && isset($argv[4])) {
				loadGames($argv[2], $argv[3], $argv[4]);
				break;
			} else {
				echo 'Year/SeasonType/Date not set';
				break;
			}

		case 'loadGamesYear':
			if(isset($argv[2])) {
				loadGamesYear($argv[2]);
				break;
			} else {
				echo "Year not set\n";
				break;
			}

		case 'loadVenues':
			loadVenues();
			break;

		case 'loadRanks':
			loadRanksESPN();
			break;
			
		case 'loadGamesCurWeek':
			if(isset($argv[2])) {
				$forceCheck = 1;
			} else {
				$forceCheck = 0;
			}
			loadGamesCurWeek2($forceCheck);
			break;
	}
}

?>