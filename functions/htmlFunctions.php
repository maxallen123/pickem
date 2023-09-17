<?php

function pageHeader($dbConn, $pageTitle) {
	if(isset($_SESSION['uid'])) {
		$query = 'SELECT users.id, users.email, users.team, teams.color, teams.alternateColor, teamLogos.href FROM users LEFT JOIN teams ON users.team = teams.id LEFT JOIN teamLogos ON teamLogos.teamId = users.team WHERE users.id = ? AND teamLogos.is_dark = 1';
		$queryArray = array($_SESSION['uid']);
		$rslt = sqlsrv_query($dbConn, $query, $queryArray);
		$user = sqlsrv_fetch_array($rslt);
		$_SESSION['uid'] = $user['id'];
		$_SESSION['email'] = $user['email'];
		$_SESSION['team'] = $user['team'];
		$_SESSION['color'] = $user['color'];
		$_SESSION['alternateColor'] = $user['alternateColor'];
		$_SESSION['logo'] = $user['href'];
	}
	if(!isset($_SESSION['team'])) {
		$_SESSION['team'] = 2633;
	}
	if(isset($_SESSION['color'])) {
		$color = $_SESSION['color'];
		$altColor = $_SESSION['alternateColor'];
	} else {
		$color = $GLOBALS['defaultColor'];
		$altColor = $GLOBALS['defaultAltColor'];
	}
	?>
	<html data-bs-theme="dark">
		<head>
			<title><?= $GLOBALS['name'] ?> Pick 'Em - <?= $pageTitle ?></title>
			<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
			<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css" integrity="sha512-ELV+xyi8IhEApPS/pSj66+Jiw+sOT1Mqkzlh8ExXihe4zfqbWkxPRi8wptXIO9g73FSlhmquFlUOuMSoXz5IRw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
			<link href="css/pickem.css?version=7" rel="stylesheet">
			<link href="css/scoreboard.css?version=7" rel="stylesheet">
			<link href="css/schoolColors.php?version=7" rel="stylesheet">
			<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>
			<script src="https://code.jquery.com/jquery-3.7.0.min.js" integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g=" crossorigin="anonymous"></script>
			<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js" integrity="sha256-xLD7nhI62fcsEZK2/v8LsBcb4lG7dgULkuXoXB/j91c=" crossorigin="anonymous"></script>
			<script src="js/functions.js?version=7"></script>
			<meta name="viewport" content="width=device-width, initial-scale=0.65">
		</head>
		<body>
				<nav class="navbar navbar-expand" style="background-color: #<?= $color ?>; color: #<?= $altColor ?>">
					<div class="container-fluid pageHeader">
						<span class="pickEm">
							<ul class="navbar-nav me-auto my-2 my-lg-0 navbar-nav-scroll">
								<li class="nav-item">
									<img src="images/siteLogo.php?teamID=<?= $_SESSION['team'] ?>&height=30" height="30px">
									&nbsp&nbspPick 'Em
								</li>
								<li class="nav-item dropdown">
									<a class="nav-link dropdown-toggle hamburger" style="background-color: #<?= $color ?>; color: #<?= $altColor ?>" href="#" role="button" data-bs-toggle="dropdown">
									☰
									</a>
									<ul class="dropdown-menu" style="background-color: #<?= $color ?>; color: #<?= $altColor ?>">
										<li><a class="dropdown-item" style="background-color: #<?= $color ?>; color: #<?= $altColor ?>" href="index.php">Main Page</a></li>
										<li><a class="dropdown-item" style="background-color: #<?= $color ?>; color: #<?= $altColor ?>" href="scoreboard.php">User Scoreboard</a></li>
									</ul>
								</li>
							</ul>
						</span>
						<?php
						if(isset($_SESSION['uid'])) {
							?>
							<span class="logOut"><a href="logout.php" class="link-underline link-underline-opacity-0 link-underline-opacity-100-hover" style="color: #<?= $altColor ?>">
								<img src="images/teamLogo.php?teamID=<?= $_SESSION['team'] ?>&height=30" height="30px">
								<br>
								Log Out
							</a></span>
							<?php
						} else {
							?>
							<a href="login.php" class="link-underline link-underline-opacity-0 link-underline-opacity-100-hover" style="color: #<?= $altColor ?>"><h5>Log In</h5></a>
							<?php
						}
						?>
					</div>
				</nav>
				<div class="row d-flex flex-column justify-content-center align-items-center w-100 p-4">
	<?php
}

?>