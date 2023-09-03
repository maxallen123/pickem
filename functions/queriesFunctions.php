<?php

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
							favID = ?, dogID = ?, openSpread = ?, openSpreadTime = ?, closeSpread = ?
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

function setLoadRanksQueries() {
	$queries['check'] = 'SELECT * FROM ranks WHERE weekID = ? AND teamID = ?';
	$queries['new'] = 'INSERT INTO ranks (weekID, teamID, rank) VALUES (?, ?, ?)';
	return $queries;
}

?>