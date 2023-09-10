<?php
require('../functions.php');

header('Content-type: text/css');

$dbConn = sqlConnect();
$teams = getTeams($dbConn);

foreach($teams as $team) {
	?>
	.winner-<?= $team['id'] ?> {
		background: #<?= $team['color'] ?> !important;
		color: #<?= $team['alternateColor'] ?> !important;
		border-radius: 0.375rem;
	}

	.ui-select.team-<?= $team['id'] ?> {
		background: url("<?= $GLOBALS['baseURL'] ?>/images/teamLogo.php?teamID=<?= $team['id'] ?>&height=30") 0 0 no-repeat;
	}
	<?php
}
?>