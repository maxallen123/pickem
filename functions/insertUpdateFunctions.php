<?php

function insertUpdateLinescores($dbConn, $lineScoresArray, $teamID, $gameID) {
	$queryNew = 'INSERT INTO gameLineScores (gameID, teamID, period, points) VALUES (?, ?, ?, ?)';
	$queryDelete = 'DELETE FROM gameLineScores WHERE gameID = ? AND teamID = ? AND period = ?';
	foreach($lineScoresArray as $period => $points) {
		$delArray = array($gameID, $teamID, $period);
		$newArray = array($gameID, $teamID, $period, $points);
		$rslt = sqlsrv_query($dbConn, $queryDelete, $delArray);
		$rslt = sqlsrv_query($dbConn, $queryNew, $newArray);
	}	
}

function insertUpdateGame($dbConn, $gameArray) {
	$queryCheck = 'SELECT id FROM games WHERE id = ?';
	$queryNew = 'INSERT INTO games 
		(name, weekID, 
		homeID, awayID, startDate, 
		venueID, isNeutral, isConference,
		homePoints, awayPoints, winnerID,
		loserID, completed, 
		statusID, curPeriod, curTime,
		id)
		VALUES
		(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
	$queryUpdate = 'UPDATE games SET
		name = ?, weekID = ?,
		homeID = ?, awayID = ?, startDate = ?,
		venueID = ?, isNeutral = ?, isConference = ?,
		homePoints = ?, awayPoints = ?, winnerID = ?,
		loserID = ?, completed = ?,
		statusID = ?, curPeriod = ?, curTime = ?
		WHERE id = ?';
	$newUpdtArray = array(
		$gameArray['name'], $gameArray['weekID'],
		$gameArray['homeID'], $gameArray['awayID'], $gameArray['startDate'],
		$gameArray['venueID'], $gameArray['isNeutral'], $gameArray['isConference'],
		$gameArray['homePoints'], $gameArray['awayPoints'], $gameArray['winnerID'],
		$gameArray['loserID'], $gameArray['completed'], 
		$gameArray['statusID'], $gameArray['curPeriod'], $gameArray['curTime'],
		$gameArray['id']
	);
	if(sqlsrv_has_rows(sqlsrv_query($dbConn, $queryCheck, array($gameArray['id'])))) {
		sqlsrv_query($dbConn, $queryUpdate, $newUpdtArray);
	} else {
		sqlsrv_query($dbConn, $queryNew, $newUpdtArray);
	}
}
?>