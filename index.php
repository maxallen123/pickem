<?php
require('functions.php');

if(session_status() == PHP_SESSION_NONE) session_start();
$dbConn = sqlConnect();
$curWeek = getCurWeek($dbConn);
$weeksGames = getWeeksGames($dbConn, $curWeek);
$users = getUsers($dbConn);
pageHeader('Week '. $curWeek->week);

/*header('Content-type: application/json');
echo json_encode($weeksGames); */
?>
		<table class="table-sm align-middle">
			<tr class="lastRow">
			</tr>
			<?php
			$firstRow = 1;
			foreach($weeksGames as $game) {
				printGame($dbConn, $game, $firstRow, $users);
				$firstRow = 0;
			}
			?>
		</table>
	</body>
	<script src="js/picker.js"></script>
</html> 