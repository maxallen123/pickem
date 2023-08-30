<?php

include('../functions.php');
$dbConn = sqlConnect();
header('Content-type: application/json');
if(session_status() == PHP_SESSION_NONE) session_start();

switch($_POST['function']) {
	case 'setPick':
		setPick($dbConn);
		break;
	case 'updatePicks':
		updatePicks($dbConn);
		break;
	case 'compare':
		compare($dbConn);
		break;
}

function setPick($dbConn) {
	$queries['check'] = 'SELECT * FROM picks WHERE gameID = ? AND userID = ?';
	$queries['new'] = 'INSERT INTO picks (teamID, gameID, userID) VALUES (?, ?, ?)';
	$queries['update'] = 'UPDATE picks SET teamID = ? WHERE gameID = ? AND userID = ?';
	$queries['delete'] = 'DELETE FROM picks WHERE gameID = ? AND userID = ?';
	$queries['checkTime'] = 'SELECT startDate FROM games WHERE id = ?';

	$pick = $_POST['pick'];
	$gameID = $_POST['gameID'];
	$userID = $_SESSION['uid'];

	$chkDelArray = array($gameID, $userID);
	$newUpdtArray = array($pick, $gameID, $userID);
	$chkTimeArray = array($gameID);

	$time = sqlsrv_fetch_array(sqlsrv_query($dbConn, $queries['checkTime'], $chkTimeArray));
	$time = $time['startDate'];
	if($time > new DateTime()) {
		$pickExists = sqlsrv_has_rows(sqlsrv_query($dbConn, $queries['check'], $chkDelArray));
		if($pickExists && $pick == -1) {
			sqlsrv_query($dbConn, $queries['delete'], $chkDelArray);
		}
		if($pickExists && $pick != -1) {
			sqlsrv_query($dbConn, $queries['update'], $newUpdtArray);
		}
		if(!($pickExists) && $pick != -1) {
			sqlsrv_query($dbConn, $queries['new'], $newUpdtArray);
		}
		echo json_encode('success');
	}
}

function updatePicks($dbConn) {
	$curWeek = getCurWeek($dbConn);
	$ranks = getRankArray($dbConn, $curWeek);
	$weeksGames = getWeeksGames($dbConn, $curWeek, $ranks);
	echo json_encode($weeksGames);
}

function compare($dbConn) {
	$userID = $_POST['userID'];
	$curWeek = getCurWeek($dbConn);
	$weeksGames = getWeeksGameIDs($dbConn, $curWeek, 0);
	foreach($weeksGames as $game) {
		$picks[$game] = -1;
	}

	$query = 'SELECT games.id, picks.teamID, games.winnerID, games.multiplier, games.jokeGame
		FROM games 
		LEFT JOIN picks ON picks.gameID = games.id AND picks.userID = ?
		WHERE (weekID = ? AND openSpread <= ?) OR forceInclude = 1 ORDER by startDate ASC';
	$queryArray = array($userID, $curWeek->weekID, $GLOBALS['threshold']);
	$rslt = sqlsrv_query($dbConn, $query, $queryArray);
	$picks = array();
	if(sqlsrv_has_rows($rslt)) {
		while($pickRow = sqlsrv_fetch_array($rslt)) {
			array_push($picks, new othersPickObj($pickRow));
		}
	}

	$returnVal['picks'] = $picks;
	$returnVal['score'] = getUserScore($dbConn, $userID, $curWeek);
	
	echo json_encode($returnVal);
}
?>