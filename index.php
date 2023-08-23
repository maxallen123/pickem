<?php
require('functions.php');

if(session_status() == PHP_SESSION_NONE) session_start();
$dbConn = sqlConnect();
$curWeek = getCurWeek($dbConn);
$ranks = getRankArray($dbConn, $curWeek);
$weeksGames = getWeeksGames($dbConn, $curWeek, $ranks);
pageHeader('Week '. $curWeek->week);

/*header('Content-type: application/json');
echo json_encode($weeksGames); */
?>
		<table class="table-sm align-middle">
			<tr class="lastRow">
			</tr>
<?php
foreach($weeksGames as $game) {
	printGame($dbConn, $game);
}
?>
		</table>
	</body>
	<script src="js/picker.js"></script>
</html> 