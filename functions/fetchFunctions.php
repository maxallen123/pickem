<?php

// Returns array of all teams, indexed by id and school name
function getTeams($dbConn) {
	$query = 'SELECT * FROM teams ORDER BY school ASC';
	$rslt = sqlsrv_query($dbConn, $query);

	while($team = sqlsrv_fetch_array($rslt)) {
		$teams[$team['id']] = $team;
		$teams[$team['school']] = $team;
	}

	return $teams;
}

// Returns array of specified week
function getWeekArray($dbConn, $year, $seasonType, $week) {
	$query = 'SELECT * FROM weeks WHERE year = ? AND week = ? AND seasonType = ?';
	$weekArray = array($year, $week, $seasonType);
	$rslt = sqlsrv_query($dbConn, $query, $weekArray);
	$week = sqlsrv_fetch_array($rslt);
	return $week;
}

// Returns week object for the current week
function getCurWeek($dbConn) {
	$query = 'SELECT TOP(1) * FROM weeks WHERE endDate > DATEADD(' . $GLOBALS['graceUnit'] . ',' . $GLOBALS['graceOffset'] .', GETDATE()) ORDER BY endDate ASC';
	$weekArray = sqlsrv_fetch_array(sqlsrv_query($dbConn, $query));
	return new weekObj($weekArray);
}

// Returns array of specified week's games 
function getWeeksGames($dbConn, $curWeek, $ranks) {
	$query = 'SELECT 
				games.id, weekID, games.name,
				homeID, home.school AS homeSchool, home.mascot AS homeMascot, home.abbreviation as homeAbbr, home.conferenceID AS homeConfID,
				awayID, away.school AS awaySchool, away.mascot AS awayMascot, away.abbreviation as awayAbbr, away.conferenceID AS awayConfID,
				startDate,
				venueID, venue.name AS venueName, venue.city AS city, venue.state AS state, venue.country AS country,
				completed, homePoints, awayPoints, winnerID, loserID, favID, dogID, closeSpread AS spread, isConference
				FROM games 
				LEFT JOIN teams AS home ON games.homeID = home.id  
				LEFT JOIN teams AS away ON games.awayID = away.id
				LEFT JOIN venues AS venue ON games.venueID = venue.id
				WHERE (weekID = ? AND openSpread <= ?) OR forceInclude = 1 ORDER BY startDate ASC';
	$queryArray = array($curWeek->weekID, $GLOBALS['threshold']);
	$rslt = sqlsrv_query($dbConn, $query, $queryArray);
	if(sqlsrv_has_rows($rslt)) {
		$games = array();
		while($gameArray = sqlsrv_fetch_array($rslt)) {
			array_push($games, new gameObj($gameArray, $curWeek, $dbConn, $ranks));
		}
	}
	return $games;
}

// Get logo for team
function getLogo($dbConn, $teamId) {
	// Query
	$logoQuery = "SELECT 
					href
					FROM teamLogos WHERE
					is_dark = 1 AND teamId = ?";
	
	$logo = sqlsrv_query($dbConn, $logoQuery, array($teamId));
	
	return sqlsrv_fetch_array($logo)['href'];
}

// Get array with AP/CFP ranks of teams for specified week
function getRankArray($dbConn, $curWeek) {
	$ranks = array();

	$query = 'SELECT teamID, rank FROM ranks WHERE weekID = ?';
	$queryArray = array($curWeek->weekID);
	$rslt = sqlsrv_query($dbConn, $query, $queryArray);

	if(sqlsrv_has_rows($rslt)) {
		while($row = sqlsrv_fetch_array($rslt)) {
			$ranks[$row['teamID']] = $row['rank'];
		}
	}
	
	return $ranks;
}

// Fetch array of conferences
function getConfs($dbConn) {
	$query = 'SELECT * FROM conferences';
	$rslt = sqlsrv_query($dbConn, $query);

	while($conference = sqlsrv_fetch_array($rslt)) {
		$conferences[$conference['name']] = $conference;
		$conferences[$conference['id']] = $conference;
	}

	return $conferences;
}

function getUsers($dbConn) {
	$query = 'SELECT * FROM users ORDER BY name ASC';
	$rslt = sqlsrv_query($dbConn, $query);
	$users = array();

	while($userArray = sqlsrv_fetch_array($rslt)) {
		array_push($users, new userObj($userArray));
	}

	return $users;
}
?>