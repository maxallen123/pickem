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

function loadYear($year) {
	$dbConn = sqlConnect();
	
	$query = 'SELECT * FROM weeks WHERE year = ?';
	$weekRslt = sqlsrv_query($dbConn, $query, array($year));
	if(sqlsrv_has_rows($weekRslt)) {
		$weeks = array();
		while($weekArray = sqlsrv_fetch_array($weekRslt)) {
			array_push($weeks, new weekObj($weekArray));
		}

		foreach($weeks as $week) {
			print_r($week);
			loadGames($week->year, $week->seasonType, $week->week);
			loadRanks($week->year, $week->seasonType, $week->week);
		}
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

function loadRanks($year, $seasonType, $week) {
	$dbConn = sqlConnectAll();
	$context = httpRequestOpts();
	$teams = getTeams($dbConn[0]);

	$weekArray = getWeekArray($dbConn[0], $year, $seasonType, $week);

	if($seasonType == 1) {
		$seasonString = 'regular';
	} else {
		$seasonString = 'postseason';
	}

	$search = array('$year', '$seasonType', '$week');
	$replace = array($year, $seasonString, $week);
	$searchString = str_replace($search, $replace, $GLOBALS['ranksURL']);

	$ranks = json_decode(file_get_contents($searchString, false, $context));

	$selectedPoll = 'AP Top 25';
	foreach($ranks[0]->polls as $poll) {
		if($poll->poll == 'Playoff Committee Rankings') {
			$selectedPoll = 'Playoff Committee Rankings';
		}
	}
	foreach($ranks[0]->polls as $poll) {
		if($poll->poll == $selectedPoll) {
			foreach($dbConn as $instance) {
				loadRank($instance, $poll, $weekArray, $teams);
			}
		}
	}
}

function loadGamesCurWeek2($forceCheck) {
	$dbConn = sqlConnectAll();
	$GLOBALS['graceOffset'] = -6;
	$GLOBALS['graceUnit'] = 'hour';
	$curWeek = getCurWeek($dbConn[0]);

	$gameFuture = 2;
	$gamePast   = -6;
	$intervalMinutesIdle = 60;

	$query = 'SELECT id FROM GAMES WHERE startDate >= DATEADD(hour, ' . $gamePast . ', GETDATE()) AND startDate <= DATEADD(hour, ' . $gameFuture . ', GETDATE())';
	// If either a game might be near, going on, or if it's time for an update
	if(sqlsrv_has_rows(sqlsrv_query($dbConn[0], $query)) || (round(time() / 60) % $intervalMinutesIdle) == 0 || $forceCheck) {
		
		$limit = 300 + rand(1, 50);
		$search = array('$year', '$week', '$seasonType', '$limit');
		$replace = array($curWeek->year, $curWeek->week, $curWeek->seasonType + 1, $limit);
		$searchString = str_replace($search, $replace, $GLOBALS['espnScoreboardURL']);
		do {
			$scoreboardStr = @file_get_contents($searchString);
			$limit++;
		} while(strlen($scoreboardStr) < 1000);
		$scoreboard = json_decode($scoreboardStr);
		$games = $scoreboard->events;

		// Prepare the status checks because we'll do this each time
		$sqlGameQuery = 'SELECT statusID FROM games WHERE id = ?';
		$sqlGameArray = array('id' => 0);
		$sqlGameRslt = sqlsrv_prepare($dbConn[0], $sqlGameQuery, $sqlGameArray);

		foreach($games as $game) {
			
			// Check what status we have in DB
			$sqlGameArray['id'] = $game->id;
			sqlsrv_execute($sqlGameRslt);
			if(sqlsrv_has_rows($sqlGameRslt)) {
				$statusID = sqlsrv_fetch_array($sqlGameRslt)['statusID'];
			} else {
				$statusID = null;
			}

			// If game is scheduled or we don't have it then load game details
			if($statusID == 1 || $statusID == null) {
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
				if($statusID == 1 || $statusID == null) {
					updateESPNSpread2($instance, $game->id, $gameDetails);
				}
			}
		}
	} else {
		echo "Not Time to Check: " . (round(time() / 60) % $intervalMinutesIdle) . "\n";
	}
}

function loadRanksESPN() {
	$dbConn = sqlConnectAll();
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
			foreach($dbConn as $instance) {
				loadRankESPN($instance, $poll, $curWeek);
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

		case 'loadYear':
			if(isset($argv[2])) {
				loadYear($argv[2]);
				break;
			} else {
				echo 'Year not set';
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