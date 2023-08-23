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

function setLoadTeamQueries() {
	$queries['check'] = 'SELECT * FROM teams 
							WHERE 
							id = ?';
	$queries['update'] = 'UPDATE teams SET
							school = ?, mascot = ?, abbreviation = ?, conferenceID = ?, isFBS = ?, color = ?, alternateColor = ?
							WHERE
							id = ?';
	$queries['new'] = 'INSERT INTO teams 
							(school, mascot, abbreviation, conferenceID, isFBS, color, alternateColor, id) 
							VALUES 
							(?, ?, ?, ?, ?, ?, ?, ?)';
	$queries['deleteLogos'] = 'DELETE FROM teamLogos 
							WHERE 
							teamId = ?';
	$queries['addLogo']    = 'INSERT INTO teamLogos 
							(teamId, href, is_dark, img) 
							VALUES
							(?, ?, ?, ?)';
	return $queries;
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

function setLoadConfQueries() {
	$queries['check'] = 'SELECT * FROM conferences
							WHERE
							id = ?';
	$queries['update'] = 'UPDATE conferences SET
							name = ?, short_name = ?, abbreviation = ?, isFBS = ?
							WHERE
							id = ?';
	$queries['new'] = 'INSERT INTO conferences
							(name, short_name, abbreviation, isFBS, id)
							VALUES
							(?, ?, ?, ?, ?)';
	return $queries;
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

function setLoadWeekQueries() {
	$queries['check'] = 'SELECT * FROM weeks
							WHERE
							year = ? AND week = ? AND seasonType = ?';
	$queries['update'] = 'UPDATE weeks SET
							startDate = ? AND endDate = ?
							WHERE
							year = ? AND week = ? AND seasonType = ?';
	$queries['new'] = 'INSERT INTO weeks
							(year, week, seasonType, startDate, endDate)
							VALUES
							(?, ?, ?, ?, ?)';
	return $queries;
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
	} else { 
		$openSpread = $gameSQL['openSpread'];
	}

	$updateSpread = array($favID, $dogID, $openSpread, $spreadAvg, $game->id);
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

function setLoadGameQueries() {
	$queries['check'] = 'SELECT * FROM games WHERE id = ?';
	$queries['new'] = 'INSERT INTO games
						(name, weekID, homeID, awayID, startDate, venueID, isNeutral, isConference, completed, id)
						VALUES
						(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
	$queries['update'] = 'UPDATE games SET
							name = ?, weekID = ?, homeID = ?, awayID = ?, startDate = ?, venueID = ?, isNeutral = ?, isConference = ?, completed = ?
							WHERE
							id = ?';
	$queries['updtWinLoss'] = 'UPDATE games SET
							homePoints = ?, awayPoints = ?, winnerID = ?, loserID = ?
							WHERE
							id = ?';
	$queries['updateSpread'] = 'UPDATE games SET
							favID = ?, dogID = ?, openSpread = ?, closeSpread = ?
							WHERE
							id = ?';
	$queries['checkLineScore'] = 'SELECT * FROM gameLineScores
							WHERE
							gameID = ? AND teamID = ? AND period = ?';
	$queries['newLineScore'] = 'INSERT INTO gameLineScores
							(points, gameID, teamID, period)
							VALUES
							(?, ?, ?, ?)';
	$queries['updateLineScore'] = 'UPDATE gameLineScores SET
							points = ?
							WHERE gameID = ? AND teamID = ? AND period = ?';
	return $queries;
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

function setLoadVenueQueries() {
	$queries['check'] = 'SELECT * FROM venues
							WHERE
							id = ?';
	$queries['update'] = 'UPDATE venues SET
							name = ?,
							capacity = ?,
							isGrass = ?,
							city = ?,
							state = ?,
							country = ?,
							latitude = ?,
							longitude = ?,
							elevation = ?,
							yearBuilt = ?,
							isDome = ?
							WHERE id = ?';
	$queries['new'] = 'INSERT INTO venues
							(name,
							capacity,
							isGrass,
							city,
							state,
							country,
							latitude,
							longitude,
							elevation,
							yearBuilt,
							isDome,
							id)
							VALUES
							(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
	return $queries;
}

function loadRank($dbConn, $poll, $week, $teams) {
	$queries = array();
	$queries = setLoadRanksQueries();
	foreach($poll->ranks as $rank) {
		$checkArray = array($week['id'], $teams[$rank->school]['id']);
		if(!sqlsrv_has_rows(sqlsrv_query($dbConn, $queries['check'], $checkArray))) {
			$newArray = array($week['id'], $teams[$rank->school]['id'], $rank->rank);
			sqlsrv_query($dbConn, $queries['new'], $newArray);
		}
	}
}

function setLoadRanksQueries() {
	$queries['check'] = 'SELECT * FROM ranks WHERE weekID = ? AND teamID = ?';
	$queries['new'] = 'INSERT INTO ranks (weekID, teamID, rank) VALUES (?, ?, ?)';
	return $queries;
}
?>