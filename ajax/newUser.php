<?php
require('../functions.php');
session_start();

if(!(isset($_POST['email']) && isset($_POST['password']) && isset($_POST['name']) && isset($_POST['team']))) {
	echo ('Missing Parameters');
	exit;
}
$queries['check'] = 'SELECT * FROM users WHERE email = ?';
$queries['new'] = 'INSERT INTO users (email, password, name, team) VALUES (?, ?, ?, ?)';
$queries['fetch'] = 'SELECT users.id, users.email, teams.color, teams.alternateColor, teamLogos.href FROM users LEFT JOIN teams ON users.team = teams.id LEFT JOIN teamLogos ON teamLogos.teamId = users.team WHERE users.email = ? AND teamLogos.is_dark = 1';

$dbConn = sqlConnect();

if(sqlsrv_has_rows(sqlsrv_query($dbConn, $queries['check'], array($_POST['email'])))) {
	echo "This e-mail address is already in use";
	exit;
}

$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$userArray = array($_POST['email'], $password, $_POST['name'], $_POST['team']);
sqlsrv_query($dbConn, $queries['new'], $userArray);

$rslt = sqlsrv_query($dbConn, $queries['fetch'], array($_POST['email']));
$user = sqlsrv_fetch_array($rslt);
$_SESSION['uid'] = $user['id'];
$_SESSION['email'] = $user['email'];
$_SESSION['color'] = $user['color'];
$_SESSION['alternateColor'] = $user['alternateColor'];
$_SESSION['logo'] = $user['href'];

echo 'success';
?>