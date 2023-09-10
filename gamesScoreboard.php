<?php
require('functions.php');

if(session_status() == PHP_SESSION_NONE) session_start();
$dbConn = sqlConnect();
$curWeek = getCurWeek($dbConn);

if(isset($_GET['confID'])) {
	$confID = $_GET['confID'];
} else {
	$confID = 0;
}
$games = getAllGamesConfWeek($dbConn, $curWeek, $confID);

pageHeader($dbConn, 'Full Game Scoreboard');

$index = 0;
foreach($games as $game) {
	$index++;
	if($index % 2 == 1) {
		?>
		<div class="row">
		<?php
		printGame($dbConn, $game, 0);
	} else {
		printGame($dbConn, $game, 0);
		?>
		</div>
		<?php
	}
}
if($index % 2 == 1) {
	echo "</div>";
}
?>
</body>
<script src="js/picker.js"></script>
</html> 