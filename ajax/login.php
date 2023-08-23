<?php
require('../functions.php');
if(session_status() == PHP_SESSION_NONE) session_start();

if(!(isset($_POST['email']) || isset($_POST['password']))) {
	echo ('Missing Parameters');
	exit;
}

$query = 'SELECT users.id, users.email, users.password, teams.color, teams.alternateColor, teamLogos.href FROM users LEFT JOIN teams ON users.team = teams.id LEFT JOIN teamLogos ON teamLogos.teamId = users.team WHERE users.email = ? AND teamLogos.is_dark = 1';
$dbConn = sqlConnect();

$rslt = sqlsrv_query($dbConn, $query, array($_POST['email']));
if(!sqlsrv_has_rows($rslt)) {
	echo "Invalid e-mail address or password";
	exit;
}

$user = sqlsrv_fetch_array($rslt);
if(!password_verify($_POST['password'], $user['password'])) {
	echo "Invalid e-mail address or password";
	exit;
}

$_SESSION['uid'] = $user['id'];
$_SESSION['email'] = $user['email'];
$_SESSION['color'] = $user['color'];
$_SESSION['alternateColor'] = $user['alternateColor'];
$_SESSION['logo'] = $user['href'];

echo 'success';
?>