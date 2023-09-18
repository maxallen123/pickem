<?php

include('../functions.php');
$dbConn = sqlConnect();
header('Content-type: application/json');
if(session_status() == PHP_SESSION_NONE) session_start();
if(isset($argv[1])) {
	$_POST['function'] = $argv[1];
}
if(isset($argv[2])) {
	$_POST['teamID'] = $argv[2];
}


switch($_POST['function']) {
	case 'setPick':
		setPick($dbConn);
		break;
	case 'updatePage':
		updatePage($dbConn);
		break;
	case 'compare':
		compare($dbConn);
		break;
	case 'schedule':
		schedule($dbConn);
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

function updatePage($dbConn) {
	$curWeek = getCurWeek($dbConn);
	$lastWeek = getLastWeek($dbConn);
	$weeksGames = getWeeksGames($dbConn, $curWeek, $lastWeek);
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
		LEFT JOIN rivalries ON (games.homeID = rivalries.teamAID AND games.awayID = rivalries.teamBID) OR (games.homeID = rivalries.teamBID AND games.awayID = rivalries.teamAID)
		WHERE (games.weekID = ? AND openSpread <= ? AND (games.openSpreadTime <= DATEADD(' . $GLOBALS['graceUnit'] . ',' . $GLOBALS['graceOffset'] . ', ?) OR games.openSpreadTime IS NULL)) 
		OR (games.weekID = ? AND forceInclude = 1) OR (games.weekID = ? AND rivalries.teamAID IS NOT NULL) ORDER BY startDate ASC';
	$queryArray = array($userID, $curWeek->weekID, $GLOBALS['threshold'], $curWeek->startDate, $curWeek->weekID, $curWeek->weekID);
	$rslt = sqlsrv_query($dbConn, $query, $queryArray);
	$picks = array();
	if(sqlsrv_has_rows($rslt)) {
		while($pickRow = sqlsrv_fetch_array($rslt)) {
			$picks[$pickRow['id']] = new othersPickObj($pickRow);
		}
	}

	$returnVal['picks'] = $picks;
	$returnVal['score'] = getUserScore($dbConn, $userID, $curWeek);
	
	echo json_encode($returnVal);
}

function schedule($dbConn) {
	$curWeek = getCurWeek($dbConn);
	$schedule = new scheduleObj($dbConn, $curWeek, $_POST['teamID']);
	echo json_encode($schedule);
}
?>