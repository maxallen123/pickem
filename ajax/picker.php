<?php

include('../functions.php');
$dbConn = sqlConnect();
if(session_status() == PHP_SESSION_NONE) session_start();

switch($_POST['function']) {
	case 'setPick':
		setPick($dbConn);
		break;
	case 'updatePicks':
		updatePicks($dbConn);
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
	}
}

function updatePicks($dbConn) {
	$curWeek = getCurWeek($dbConn);
	$ranks = getRankArray($dbConn, $curWeek);
	$weeksGames = getWeeksGames($dbConn, $curWeek, $ranks);
	header('Content-type: application/json');
	echo json_encode($weeksGames);
}
?>