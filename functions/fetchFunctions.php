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

function getTeamsWithLogo($dbConn) {
	$query = 'SELECT id, school, mascot, abbreviation, conferenceID, isFBS, color, alternateColor, lightLogos.href AS lightLogo, darkLogos.href AS darkLogo FROM teams LEFT JOIN teamLogos AS lightLogos ON teams.id = lightLogos.teamId AND lightLogos.is_dark = 0 LEFT JOIN teamLogos AS darkLogos ON teams.id = darkLogos.teamId AND darkLogos.is_dark = 1 ORDER BY school ASC';
	$rslt = sqlsrv_query($dbConn, $query);

	while($team = sqlsrv_fetch_array($rslt)) {
		$teams[$team['id']] = $team;
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

function getLastWeek($dbConn) {
	$query = 'SELECT TOP(1) * FROM weeks WHERE endDate < DATEADD(' . $GLOBALS['graceUnit'] . ',' . $GLOBALS['graceOffset'] .', GETDATE()) ORDER BY endDate DESC';
	$weekArray = sqlsrv_fetch_array(sqlsrv_query($dbConn, $query));
	return new weekObj($weekArray);
}

function getWeekNumber($dbConn, $year, $seasonType, $weekNum) {
	$query = 'SELECT * FROM weeks WHERE year = ? AND seasonType = ? AND week = ?';
	$queryArray = array($year, $seasonType, $weekNum);
	$weekArray = sqlsrv_fetch_array(sqlsrv_query($dbConn, $query, $queryArray));
	return new weekObj($weekArray);
}

function getAllYearWeeks($dbConn, $year) {
	$query = 'SELECT * FROM weeks WHERE year = ?';
	$queryArray = array($year);
	$rslt = sqlsrv_query($dbConn, $query, $queryArray);
	$weeks = array();
	if(sqlsrv_has_rows($rslt)) {
		while($week = sqlsrv_fetch_array($rslt)) {
			array_push($weeks, new weekObj($week));
		}
	}
	return $weeks;
}

// Returns array of specified week's games 
function getWeeksGames($dbConn, $curWeek, $lastWeek) {
	$query = 'SELECT 
				games.id, games.weekID, games.name, games.customName, games.multiplier, games.jokeGame, games.isNeutral,
				homeID, home.school AS homeSchool, home.mascot AS homeMascot, home.abbreviation as homeAbbr, home.conferenceID AS homeConfID, home.comedyName AS homeComedyName,
				homeConference.name AS homeConfName, homeConference.short_name AS homeConfShortName, homeConference.abbreviation AS homeConfAbbr, homeConference.isFBS AS homeConfIsFBS,
				awayID, away.school AS awaySchool, away.mascot AS awayMascot, away.abbreviation as awayAbbr, away.conferenceID AS awayConfID, away.comedyName AS awayComedyName,
				awayConference.name AS awayConfName, awayConference.short_name AS awayConfShortName, awayConference.abbreviation AS awayConfAbbr, awayConference.isFBS AS awayConfIsFBS,
				games.startDate,
				venueID, venue.name AS venueName, venue.city AS city, venue.state AS state, venue.country AS country,
				statusID, curPeriod, curTime,
				down, toGo, yardLine, possession,
				completed, homePoints, awayPoints, winnerID, loserID, favID, dogID, closeSpread AS spread, isConference,
				(SELECT COUNT(gameID) FROM picks LEFT JOIN users ON users.id = picks.userID WHERE picks.gameID = games.id AND picks.teamID = games.homeID AND users.name <> \'Vegas\') AS homePicks,
				(SELECT COUNT(gameID) FROM picks LEFT JOIN users ON users.id = picks.userID WHERE picks.gameID = games.id AND picks.teamID = games.awayID AND users.name <> \'Vegas\') AS awayPicks,
				homeRanks.rank AS homeRank, awayRanks.rank AS awayRank,
				(SELECT COUNT(id) FROM games AS homeWinGames WHERE homeWinGames.weekID IN (SELECT id FROM weeks WHERE year = weekPresent.year) AND homeWinGames.winnerID = games.homeID AND homeWinGames.startDate < games.startDate) AS homeWins,
				(SELECT COUNT(id) FROM games AS homeLossGames WHERE homeLossGames.weekID IN (SELECT id FROM weeks WHERE year = weekPresent.year) AND homeLossGames.loserID = games.homeID AND homeLossGames.startDate < games.startDate) AS homeLosses,
				(SELECT COUNT(id) FROM games AS homeConfWinGames WHERE homeConfWinGames.weekID IN (SELECT id FROM weeks WHERE year = weekPresent.year) AND homeConfWinGames.winnerID = games.homeID AND homeConfWinGames.startDate < games.startDate AND homeConfWinGames.isConference = 1) AS homeConfWins,
				(SELECT COUNT(id) FROM games AS homeConfLossGames WHERE homeConfLossGames.weekID IN (SELECT id FROM weeks WHERE year = weekPresent.year) AND homeConfLossGames.loserID = games.homeID AND homeConfLossGames.startDate < games.startDate AND homeConfLossGames.isConference = 1) AS homeConfLosses,
				(SELECT COUNT(id) FROM games AS awayWinGames WHERE awayWinGames.weekID IN (SELECT id FROM weeks WHERE year = weekPresent.year) AND awayWinGames.winnerID = games.awayID AND awayWinGames.startDate < games.startDate) AS awayWins,
				(SELECT COUNT(id) FROM games AS awayLossGames WHERE awayLossGames.weekID IN (SELECT id FROM weeks WHERE year = weekPresent.year) AND awayLossGames.loserID = games.awayID AND awayLossGames.startDate < games.startDate) AS awayLosses,
				(SELECT COUNT(id) FROM games AS awayConfWinGames WHERE awayConfWinGames.weekID IN (SELECT id FROM weeks WHERE year = weekPresent.year) AND awayConfWinGames.winnerID = games.awayID AND awayConfWinGames.startDate < games.startDate AND awayConfWinGames.isConference = 1) AS awayConfWins,
				(SELECT COUNT(id) FROM games AS awayConfLossGames WHERE awayConfLossGames.weekID IN (SELECT id FROM weeks WHERE year = weekPresent.year) AND awayConfLossGames.loserID = games.awayID AND awayConfLossGames.startDate < games.startDate AND awayConfLossGames.isConference = 1) AS awayConfLosses,
				rivalries.teamAID, rivalries.name AS rivalryName, rivalries.trophy
				FROM games 
				LEFT JOIN teams AS home ON games.homeID = home.id
				LEFT JOIN conferences AS homeConference ON home.conferenceID = homeConference.id
				LEFT JOIN ranks AS homeRanks ON homeRanks.weekID = games.weekID AND homeRanks.teamID = games.homeID
				LEFT JOIN teams AS away ON games.awayID = away.id
				LEFT JOIN conferences AS awayConference ON away.conferenceID = awayConference.id
				LEFT JOIN ranks AS awayRanks ON awayRanks.weekID = games.weekID AND awayRanks.teamID = games.awayID
				LEFT JOIN venues AS venue ON games.venueID = venue.id
				LEFT JOIN weeks AS weekPresent ON games.weekID = weekPresent.id
				LEFT JOIN rivalries ON (games.homeID = rivalries.teamAID AND games.awayID = rivalries.teamBID) OR (games.homeID = rivalries.teamBID AND games.awayID = rivalries.teamAID)
				WHERE 
				((games.weekID = ? AND openSpread <= ? AND (games.openSpreadTime <= DATEADD(day, 1, ?) OR games.openSpreadTime IS NULL)) 
				AND (home.isFBS = 1 OR away.isFBS = 1))
				OR (games.weekID = ? AND forceInclude = 1)
				OR (rivalries.teamAID IS NOT NULL AND games.weekID = ?)
				ORDER BY startDate ASC, games.id ASC';
	$queryArray = array($curWeek->weekID, $GLOBALS['threshold'], $lastWeek->endDate, $curWeek->weekID, $curWeek->weekID);
	$rslt = sqlsrv_query($dbConn, $query, $queryArray);
	print_r(sqlsrv_errors());
	if(sqlsrv_has_rows($rslt)) {
		$games = array();
		while($gameArray = sqlsrv_fetch_array($rslt)) {
			array_push($games, new gameObj($gameArray, $curWeek, $dbConn));
		}
	}
	return $games;
}

// Get logo for team
function getLogo($dbConn, $teamId) {
	// Query
	$logoQuery = "SELECT 
					img
					FROM teamLogos WHERE
					is_dark = 1 AND teamId = ?";
	
	$logo = sqlsrv_query($dbConn, $logoQuery, array($teamId));
	
	return sqlsrv_fetch_array($logo)['img'];
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

function getConfsObjs($dbConn) {
	$query = 'SELECT id, name, short_name, abbreviation, isFBS FROM conferences';
	$rslt = sqlsrv_query($dbConn, $query);
	$confArray = array();
	if(sqlsrv_has_rows($rslt)) {
		while($confRow = sqlsrv_fetch_array($rslt)) {
			array_push($confArray, new confObj($confRow));
		}
	}
	return $confArray;
}

// Fetch array of users
function getUsers($dbConn, $curWeek) {
	$query = 'SELECT users.id, users.name, users.email, users.team, teams.color, teams.alternateColor FROM users LEFT JOIN teams ON teams.id = users.team WHERE users.id IN (SELECT DISTINCT(userID) FROM picks LEFT JOIN games ON games.id = picks.gameID LEFT JOIN weeks ON games.weekID = weeks.id WHERE weeks.year = ?) ORDER BY name ASC';
	$queryArray = array($curWeek->year);
	$rslt = sqlsrv_query($dbConn, $query, $queryArray);
	$users = array();

	while($userArray = sqlsrv_fetch_array($rslt)) {
		array_push($users, new userObj($userArray));
	}

	return $users;
}

// Get Array of game IDs. $all == 1 means all games, 0 means just pick games
function getWeeksGameIDs($dbConn, $curWeek, $all) {
	$gameIDArray = array();
	$query = 'SELECT id FROM games 
				LEFT JOIN rivalries ON (games.homeID = rivalries.teamAID AND games.awayID = rivalries.teamBID) OR (games.homeID = rivalries.teamBID AND games.awayID = rivalries.teamAID) 
				WHERE ';
	if($all) {
		$query .= 'weekID = ?';
		$queryArray = array($curWeek->weekID);
	} else {
		$query .= '(weekID = ? AND openSpread <= ?) OR (forceInclude = 1 AND weekID = ?) OR (rivalries.teamAID IS NOT NULL AND weekID = ?)';
		$queryArray = array($curWeek->weekID, $GLOBALS['threshold'], $curWeek->weekID, $curWeek->weekID);
	}
	$rslt = sqlsrv_query($dbConn, $query, $queryArray);
	if(sqlsrv_has_rows($rslt)) {
		while($row = sqlsrv_fetch_array($rslt)) {
			array_push($gameIDArray, $row['id']);
		}
	}
	return $gameIDArray;
}

function getUserScore($dbConn, $userID, $curWeek) {
	$query = 'SELECT SUM(multiplier) AS score 
		FROM picks 
			LEFT JOIN games ON picks.gameID = games.id 
			LEFT JOIN weeks ON weeks.id = games.weekID
		WHERE (games.winnerID = picks.teamID 
			AND weeks.endDate < DATEADD(' . $GLOBALS['graceUnit'] . ',' . $GLOBALS['graceOffset'] .', GETDATE()) 
			AND picks.userID = ?
			AND weeks.year = ?)
		OR (games.jokeGame = 1
			AND picks.userID = ?
			AND weeks.year = ?
			AND weeks.endDate < DATEADD(' . $GLOBALS['graceUnit'] . ',' . $GLOBALS['graceOffset'] .', GETDATE()))';
	$queryArray = array($userID, $curWeek->year, $userID, $curWeek->year);
	$score = sqlsrv_fetch_array(sqlsrv_query($dbConn, $query, $queryArray))['score'];
	if($score == null) {
		$score = 0;
	}
	return $score;
}

function getLiveUserScore($dbConn, $userID, $curWeek) {
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
	$queryArray = array($userID, $curWeek->year, $userID, $curWeek->year);
	$score = sqlsrv_fetch_array(sqlsrv_query($dbConn, $query, $queryArray))['score'];
	if($score == null) {
		$score = 0;
	}
	return $score;
}

function scoreSort($a, $b) {
	if($a->score < $b->score) {
		return true;
	} else {
		return false;
	}
}

function getAllGamesConfWeek($dbConn, $curWeek, $confID) {
	$query = 'SELECT 
		games.id, games.weekID, games.name, games.customName, games.multiplier, games.jokeGame, games.isNeutral,
		homeID, home.school AS homeSchool, home.mascot AS homeMascot, home.abbreviation as homeAbbr, home.conferenceID AS homeConfID, home.comedyName AS homeComedyName,
		homeConference.name AS homeConfName, homeConference.short_name AS homeConfShortName, homeConference.abbreviation AS homeConfAbbr, homeConference.isFBS AS homeConfIsFBS,
		awayID, away.school AS awaySchool, away.mascot AS awayMascot, away.abbreviation as awayAbbr, away.conferenceID AS awayConfID, away.comedyName AS awayComedyName,
		awayConference.name AS awayConfName, awayConference.short_name AS awayConfShortName, awayConference.abbreviation AS awayConfAbbr, awayConference.isFBS AS awayConfIsFBS,
		games.startDate,
		venueID, venue.name AS venueName, venue.city AS city, venue.state AS state, venue.country AS country,
		statusID, curPeriod, curTime,
		down, toGo, yardLine, possession,
		completed, homePoints, awayPoints, winnerID, loserID, favID, dogID, closeSpread AS spread, isConference,
		(SELECT COUNT(gameID) FROM picks WHERE gameID = games.id AND teamID = games.homeID) AS homePicks,
		(SELECT COUNT(gameID) FROM picks WHERE gameID = games.id AND teamID = games.awayID) AS awayPicks,
		homeRanks.rank AS homeRank, awayRanks.rank AS awayRank,
		(SELECT COUNT(id) FROM games AS homeWinGames WHERE homeWinGames.weekID IN (SELECT id FROM weeks WHERE year = weekPresent.year) AND homeWinGames.winnerID = games.homeID AND homeWinGames.startDate < games.startDate) AS homeWins,
		(SELECT COUNT(id) FROM games AS homeLossGames WHERE homeLossGames.weekID IN (SELECT id FROM weeks WHERE year = weekPresent.year) AND homeLossGames.loserID = games.homeID AND homeLossGames.startDate < games.startDate) AS homeLosses,
		(SELECT COUNT(id) FROM games AS homeConfWinGames WHERE homeConfWinGames.weekID IN (SELECT id FROM weeks WHERE year = weekPresent.year) AND homeConfWinGames.winnerID = games.homeID AND homeConfWinGames.startDate < games.startDate AND homeConfWinGames.isConference = 1) AS homeConfWins,
		(SELECT COUNT(id) FROM games AS homeConfLossGames WHERE homeConfLossGames.weekID IN (SELECT id FROM weeks WHERE year = weekPresent.year) AND homeConfLossGames.loserID = games.homeID AND homeConfLossGames.startDate < games.startDate AND homeConfLossGames.isConference = 1) AS homeConfLosses,
		(SELECT COUNT(id) FROM games AS awayWinGames WHERE awayWinGames.weekID IN (SELECT id FROM weeks WHERE year = weekPresent.year) AND awayWinGames.winnerID = games.awayID AND awayWinGames.startDate < games.startDate) AS awayWins,
		(SELECT COUNT(id) FROM games AS awayLossGames WHERE awayLossGames.weekID IN (SELECT id FROM weeks WHERE year = weekPresent.year) AND awayLossGames.loserID = games.awayID AND awayLossGames.startDate < games.startDate) AS awayLosses,
		(SELECT COUNT(id) FROM games AS awayConfWinGames WHERE awayConfWinGames.weekID IN (SELECT id FROM weeks WHERE year = weekPresent.year) AND awayConfWinGames.winnerID = games.awayID AND awayConfWinGames.startDate < games.startDate AND awayConfWinGames.isConference = 1) AS awayConfWins,
		(SELECT COUNT(id) FROM games AS awayConfLossGames WHERE awayConfLossGames.weekID IN (SELECT id FROM weeks WHERE year = weekPresent.year) AND awayConfLossGames.loserID = games.awayID AND awayConfLossGames.startDate < games.startDate AND awayConfLossGames.isConference = 1) AS awayConfLosses,
		rivalries.teamAID, rivalries.name AS rivalryName, rivalries.trophy
		FROM games 
		LEFT JOIN teams AS home ON games.homeID = home.id
		LEFT JOIN conferences AS homeConference ON home.conferenceID = homeConference.id
		LEFT JOIN ranks AS homeRanks ON homeRanks.weekID = games.weekID AND homeRanks.teamID = games.homeID
		LEFT JOIN teams AS away ON games.awayID = away.id
		LEFT JOIN conferences AS awayConference ON away.conferenceID = awayConference.id
		LEFT JOIN ranks AS awayRanks ON awayRanks.weekID = games.weekID AND awayRanks.teamID = games.awayID
		LEFT JOIN venues AS venue ON games.venueID = venue.id
		LEFT JOIN weeks AS weekPresent ON games.weekID = weekPresent.id
		LEFT JOIN rivalries ON (games.homeID = rivalries.teamAID AND games.awayID = rivalries.teamBID) OR (games.homeID = rivalries.teamBID AND games.awayID = rivalries.teamAID)
		WHERE
		games.weekID = ?';
	$queryArray = array($curWeek->weekID);
	if($confID == -1) {
		$query .= ' AND (home.isFBS = 1 OR away.isFBS = 1)';
	}
	if($confID > 0) {
		$query .= ' AND (home.conferenceID = ? OR away.conferenceID = ?)';
		array_push($queryArray, $confID, $confID);
	}
	$query .= ' ORDER BY games.statusID ASC';
	$rslt = sqlsrv_query($dbConn, $query, $queryArray);
	print_r(sqlsrv_errors());
	if(sqlsrv_has_rows($rslt)) {
		$games = array();
		while($gameArray = sqlsrv_fetch_array($rslt)) {
			array_push($games, new gameObj($gameArray, $curWeek, $dbConn));
		}
	}
	return $games;
}
?>