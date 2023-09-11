<?php

function loadTeam($dbConn, $team, $conferences) {
	
	if(isset($conferences[$team->conference])) {
		$queries = array();
		$queries = setLoadTeamQueries($queries);

		if(isset($team->color)) {
			$color = substr($team->color, -6);
		} else {
			$color = NULL;
		}

		if(isset($team->alt_color)) {
			$alternateColor = substr($team->alt_color, -6);
		} else {
			$alternateColor = NULL;
		}

		if($team->classification == 'fbs') {
			$isFBS = true;
		} else {
			$isFBS = false;
		}

		$idArray = array($team->id);
		$teamArray = array(
			$team->school,
			$team->mascot,
			$team->abbreviation,
			$conferences[$team->conference]['id'],
			$isFBS,
			$color,
			$alternateColor,
			$team->id
		);

		// Check if already exists
		if(sqlsrv_has_rows(sqlsrv_query($dbConn, $queries['check'], $idArray))) {
			// If exists, do update:
			sqlsrv_query($dbConn, $queries['update'], $teamArray);
		} else {
			// If does not exist, create new row
			sqlsrv_query($dbConn, $queries['new'], $teamArray);
		}

		// Delete logos recreate them
		sqlsrv_query($dbConn, $queries['deleteLogos'], $idArray);

		if(isset($team->logos)) {
			foreach($team->logos as $logo) {
				//Download logo
				$logoImg = 'data:' . get_headers($logo, true)['Content-Type'] . ';base64,' . base64_encode(file_get_contents($logo));
				if(str_contains($logo, 'dark')) {
					$is_dark = true;
				} else {
					$is_dark = false;
				}
				$logoArray = array(
					$team->id,
					$logo,
					$is_dark,
					$logoImg
				);
				sqlsrv_query($dbConn, $queries['addLogo'], $logoArray);
			}
		}
	}
}

function loadConf($dbConn, $conf) {
	$queries = array();
	$queries = setLoadConfQueries();

	if($conf->classification == 'fbs' || $conf->classification == 'fcs') {
		if($conf->classification == 'fbs') {
			$isFBS = true;
		} else {
			$isFBS = false;
		}
		
		$idArray = array($conf->id); 
		$confArray = array(
			$conf->name,
			$conf->short_name,
			$conf->abbreviation,
			$isFBS,
			$conf->id
		);

		if(sqlsrv_has_rows(sqlsrv_query($dbConn, $queries['check'], $idArray))) {
			sqlsrv_query($dbConn, $queries['update'], $confArray);
		} else {
			sqlsrv_query($dbConn, $queries['new'], $confArray);
		}
	}
}

function loadWeek($dbConn, $week) {
	$queries = array();
	$queries = setLoadWeekQueries();

	if($week->seasonType == 'regular') {
		$seasonType = 1;
	} else {
		$seasonType = 2;
	}

	$checkArray = array(
		$week->season,
		$week->week,
		$seasonType
	);

	if(sqlsrv_has_rows(sqlsrv_query($dbConn, $queries['check'], $checkArray))) {
		$updateArray = array(
			$week->firstGameStart,
			$week->lastGameStart,
			$week->season,
			$week->week,
			$seasonType
		);
		sqlsrv_query($dbConn, $queries['update'], $updateArray);
	} else {
		$newArray = array(
			$week->season,
			$week->week,
			$seasonType,
			$week->firstGameStart,
			$week->lastGameStart
		);
		sqlsrv_query($dbConn, $queries['new'], $newArray);
	}
}

function loadGame($dbConn, $game, $conferences, $teams, $week) {
	$queries = array();
	$queries = setLoadGameQueries();

	$checkArray = array($game->id);
	$newUpdtArray = array(
		$game->notes,
		$week['id'],
		$game->home_id,
		$game->away_id,
		$game->start_date,
		$game->venue_id,
		$game->neutral_site,
		$game->conference_game,
		$game->completed,
		$game->id
	);

	if(sqlsrv_has_rows(sqlsrv_query($dbConn, $queries['check'], $checkArray))) {
		sqlsrv_query($dbConn, $queries['update'], $newUpdtArray);
	} else {
		sqlsrv_query($dbConn, $queries['new'], $newUpdtArray);
	}

	updatePointsWinner($dbConn, $game, $queries);
	updateSpread($dbConn, $game, $queries);
	updateLineScores($dbConn, $game, $queries);
}

function updatePointsWinner($dbConn, $game, $queries) {
	$homePoints = $game->home_points;
	$awayPoints = $game->away_points;
	if($game->completed == true) {
		if($homePoints > $awayPoints) {
			$winnerID = $game->home_id;
			$loserID = $game->away_id;
		} else {
			$winnerID = $game->away_id;
			$loserID = $game->home_id;
		}
	} else {
		$winnerID = null;
		$loserID = null;
	}
	$updtWinLoss = array($homePoints, $awayPoints, $winnerID, $loserID, $game->id);
	sqlsrv_query($dbConn, $queries['updtWinLoss'], $updtWinLoss);
}

function updateSpread($dbConn, $game, $queries) {
	$checkArray = array($game->id);
	$context = httpRequestOpts();
	$gameSQL = sqlsrv_fetch_array(sqlsrv_query($dbConn, $queries['check'], $checkArray));

	$search = array('$year', '$gameID');
	$replace = array($game->season, $game->id);
	$linesString = str_replace($search, $replace, $GLOBALS['linesURL']);

	$lines = json_decode(file_get_contents($linesString, false, $context));
	if($lines == null) {
		sleep(15);
		$lines = json_decode(file_get_contents($linesString, false, $context));
	}

	$spreadTotal = 0;
	$spreadCount = 0;
	
	foreach($lines[0]->lines as $line) {
		$spreadTotal += $line->spread;
		$spreadCount++;
	}
	if($spreadCount != 0) {
		$spreadAvg = round($spreadTotal * 2 / $spreadCount) / 2;
		if($spreadAvg <= 0) {
			$favID = $game->home_id;
			$dogID = $game->away_id;
		} else {
			$favID = $game->away_id;
			$dogID = $game->home_id;
		}
	} else {
		$spreadAvg = null;
		$favID = null;
		$dogID = null;
	}

	if($spreadAvg != null) {
		$spreadAvg = abs($spreadAvg);
	}

	if($gameSQL['openSpread'] == null) {
		$openSpread = $spreadAvg;
		$openSpreadTime = $gameSQL['openSpreadTime'];
	} else { 
		$openSpread = $gameSQL['openSpread'];
		$openSpreadTime = new DateTime();
	}

	$updateSpread = array($favID, $dogID, $openSpread, $openSpreadTime, $spreadAvg, $game->id);
	sqlsrv_query($dbConn, $queries['updateSpread'], $updateSpread);
}

function updateLineScores($dbConn, $game, $queries) {
	foreach($game->home_line_scores as $quarter => $lineScore) {
		$checkArray = array($game->id, $game->home_id, $quarter + 1);
		$newUpdtArray = array($lineScore, $game->id, $game->home_id, $quarter + 1);
		if(sqlsrv_has_rows(sqlsrv_query($dbConn, $queries['checkLineScore'], $checkArray))) {
			sqlsrv_query($dbConn, $queries['updateLineScore'], $newUpdtArray);
		} else {
			sqlsrv_query($dbConn, $queries['newLineScore'], $newUpdtArray);
		}
	}
	foreach($game->away_line_scores as $quarter => $lineScore) {
		$checkArray = array($game->id, $game->away_id, $quarter + 1);
		$newUpdtArray = array($lineScore, $game->id, $game->away_id, $quarter + 1);
		if(sqlsrv_has_rows(sqlsrv_query($dbConn, $queries['checkLineScore'], $checkArray))) {
			sqlsrv_query($dbConn, $queries['updateLineScore'], $newUpdtArray);
		} else {
			sqlsrv_query($dbConn, $queries['newLineScore'], $newUpdtArray);
		}
	}
}

function loadVenue($dbConn, $venue) {
	$queries = array();
	$queries = setLoadVenueQueries();

	$checkArray = array($venue->id);
	$updtNewArray = array(
		$venue->name,
		$venue->capacity,
		$venue->grass,
		$venue->city,
		$venue->state,
		$venue->country_code,
		$venue->location->y,
		$venue->location->x,
		$venue->elevation * 3.28084,
		$venue->year_constructed,
		$venue->dome,
		$venue->id
	);

	if(sqlsrv_has_rows(sqlsrv_query($dbConn, $queries['check'], $checkArray))) {
		sqlsrv_query($dbConn, $queries['update'], $updtNewArray);
	} else {
		sqlsrv_query($dbConn, $queries['new'], $updtNewArray);
	}
}

function loadGameScoreboard($dbConn, $game, $curWeek) {
	$game = $game->competitions[0];
	$gameArray = array();
	$gameArray['id'] = $game->id;
	if(isset($game->notes[0]->headline)) {
		$gameArray['name'] = $game->notes[0]->headline;
	} else {
		$gameArray['name'] = null;
	}
	$gameArray['startDate'] = str_replace(array('T', 'Z'), ' ', $game->startDate);
	if($game->competitors[0]->homeAway == 'home') {
		$homeIndex = 0;
		$awayIndex = 1;
	} else {
		$homeIndex = 1;
		$awayIndex = 0;
	}
	$gameArray['weekID'] = $curWeek->weekID;
	$gameArray['homeID'] = $game->competitors[$homeIndex]->id;
	$gameArray['awayID'] = $game->competitors[$awayIndex]->id;
	$gameArray['venueID'] = $game->venue->id;
	$gameArray['isNeutral'] = $game->neutralSite;
	$gameArray['isConference'] = $game->conferenceCompetition;
	$gameArray['homePoints'] = $game->competitors[$homeIndex]->score;
	$gameArray['awayPoints'] = $game->competitors[$awayIndex]->score;
	
	// Code to update for in progress
	$gameArray['statusID'] = $game->status->type->id;
	$gameArray['curPeriod'] = $game->status->period;
	$gameArray['curTime'] = $game->status->clock;

	if(in_array($gameArray['statusID'], array(2, 22))) {
		$gameArray['down'] = $game->situation->down;
		if($gameArray['down'] != -1) {
			$gameArray['toGo'] = $game->situation->distance;
			$gameArray['possession'] = $game->situation->possession;
		} else {
			$gameArray['toGo'] = null;
			$gameArray['possession'] = $game->situation->lastPlay->team->id;
		}
		$gameArray['yardLine'] = $game->situation->yardLine;
	} else {
		$gameArray['down'] = null;
		$gameArray['toGo'] = null;
		$gameArray['possession'] = null;
		$gameArray['yardLine'] = null;
	}

	if($gameArray['statusID'] == 3) {
		$gameArray['completed'] = 1;
		if($gameArray['homePoints'] > $gameArray['awayPoints']) {
			$gameArray['winnerID'] = $gameArray['homeID'];
			$gameArray['loserID'] = $gameArray['awayID'];
		} else {
			$gameArray['winnerID'] = $gameArray['awayID'];
			$gameArray['loserID'] = $gameArray['homeID'];
		}
	} else {
		$gameArray['completed'] = 0;
		$gameArray['winnerID'] = null;
		$gameArray['loserID'] = null;
	}
	if(isset($game->competitors[$homeIndex]->linescores)) {
		$homeLinescores = array();
		$awayLinescores = array();
		foreach($game->competitors[$homeIndex]->linescores as $period => $lineScore) {
			$homeLinescores[$period + 1] = $lineScore->value;
		}
		foreach($game->competitors[$awayIndex]->linescores as $period => $lineScore) {
			$awayLinescores[$period + 1] = $lineScore->value;
		}
		insertUpdateLinescores($dbConn, $homeLinescores, $gameArray['homeID'], $gameArray['id']);
		insertUpdateLinescores($dbConn, $awayLinescores, $gameArray['awayID'], $gameArray['id']);
	}
	insertUpdateGame($dbConn, $gameArray);
}

function updateESPNSpread2($dbConn, $gameID, $game) {

	if(isset($game->pickcenter[0])) {
		$lines = 0;
		$spreadSum = 0;

		foreach($game->pickcenter as $line) {
			if(isset($line->spread)) {
				$lines++;
				$spreadSum += $line->spread;
			}
		}

		if($lines != 0) {
			$spread = round(($spreadSum * 2) / $lines) / 2;
			$query = 'SELECT openSpread, openSpreadTime, homeID, awayID FROM games WHERE id = ?';
			$queryArray = array($gameID);
			$rslt = sqlsrv_query($dbConn, $query, $queryArray);
			$row = sqlsrv_fetch_array($rslt);
			$openSpread = $row['openSpread'];
			$openSpreadTime = $row['openSpreadTime'];
			if($openSpread == null) {
				$openSpread = abs($spread);
				$openSpreadTime = new DateTime();
			}
			if($spread <= 0) {
				$favID = $row['homeID'];
				$dogID = $row['awayID'];
			} else {
				$favID = $row['awayID'];
				$dogID = $row['homeID'];
			}
		} else {
			$openSpread = null;
			$openSpreadTime = null;
			$spread = null;
			$favID = null;
			$dogID = null;
		}

		if($spread != null) {
			$spread = abs($spread);
		}

		$query = 'UPDATE games SET openSpread = ?, openSpreadTime = ?, closeSpread = ?, favID = ?, dogID = ? WHERE id = ?';
		$queryArray = array($openSpread, $openSpreadTime, $spread, $favID, $dogID, $gameID);
		sqlsrv_query($dbConn, $query, $queryArray);
	}
}

function loadRankESPN($dbConn, $poll, $curWeek) {
	$queries = array();
	$queries = setLoadRanksQueries();
	foreach($poll->ranks as $rank) {
		$checkArray = array($curWeek->weekID, $rank->team->id);
		if(!sqlsrv_has_rows(sqlsrv_query($dbConn, $queries['check'], $checkArray))) {
			$newArray = array($curWeek->weekID, $rank->team->id, $rank->current);
			sqlsrv_query($dbConn, $queries['new'], $newArray);
		}
	}
}
?>