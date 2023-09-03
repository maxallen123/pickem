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
	}
	<?php
}
?>