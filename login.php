<?php

require('functions.php');
session_start();
$dbConn = sqlConnect();

// If we're already logged in, update session variables and redirect back to main page
if(isset($_SESSION['uid'])) {
	$query = 'SELECT users.id, users.email, teams.color, teams.alternateColor, teamLogos.href FROM users LEFT JOIN teams ON users.team = teams.id LEFT JOIN teamLogos ON teamLogos.teamId = users.team WHERE users.id = ? AND teamLogos.is_dark = 1';
	$queryArray = array($_SESSION['uid']);
	$rslt = sqlsrv_query($dbConn, $query, $queryArray);
	$user = sqlsrv_fetch_array($rslt);
	$_SESSION['uid'] = $user['id'];
	$_SESSION['email'] = $user['email'];
	$_SESSION['color'] = $user['color'];
	$_SESSION['alternateColor'] = $user['alternateColor'];
	$_SESSION['logo'] = $user['href'];
	header('Location: ' . $GLOBALS['baseURL']);
	exit;
}

pageHeader('Log In');
?>
<script src="js/login.js"></script>
<div class="row w-75 text-center border-bottom m-3" id="head">
	<h3>Log In</h3>
</div>
<form class="col-sm-3" id="logIn">
	<div class="mb-3">
		<label for="emailAddress" class="form-label">E-mail Address</label>
		<input type="email" class="form-control" id="emailAddress">
		<div id="emailReject" class="form-text text-danger d-none">Invalid e-mail address</div>
	</div>
	<div class="mb-3">
		<label for="password" class="form-label">Password</label>
		<input type="password" class="form-control" id="password1">
		<div id="passwordReject" class="form-text text-danger d-none">Password cannot be blank</div>
	</div>
	<div class="mb-3">
		<button class="btn btn-primary" type="button" id="submit">Log In</button>
	</div>
	<div id="failedServer" class="form-text text-danger d-none"></div>
</form>
</div>
</body>
</html>