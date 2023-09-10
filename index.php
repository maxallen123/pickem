<?php
require('functions.php');

if(session_status() == PHP_SESSION_NONE) session_start();
$dbConn = sqlConnect();
$curWeek = getCurWeek($dbConn);
$weeksGames = getWeeksGames($dbConn, $curWeek);
$users = getUsers($dbConn, $curWeek);
pageHeader($dbConn, 'Week '. $curWeek->week);
if(isset($_SESSION['uid'])) {
	$_SESSION['userScore'] = getUserScore($dbConn, $_SESSION['uid'], $curWeek);
	?>
	<input type='hidden' id='userPreweekScore' value='<?= $_SESSION['userScore'] ?>'>
	<?php
}

/*header('Content-type: application/json');
echo json_encode($weeksGames); */
?>
<div class="row col-3 compareCell">
	<?php
	if($_SESSION['uid']) {
		?>
		<div class="col-3 header-compare">
			Compare to:	
		</div>
		<div class="col-3 selectCompareCell">
			<select id="selectCompare" class="form-select selectCompare text-center" onchange="compare()">
				<option value="-1" selected></option>
				<?php
				foreach($users as $user) {
					if($user->id != $_SESSION['uid']) {
						?>
						<option value="<?= $user->id ?>"><?= $user->name ?></option>
						<?php
					}
				}
				?>
			</select>
		</div>
		<?php
	}
	?>
</div>
<?php
foreach($weeksGames as $game) {
	?>
	<div class="row">
		<?php
		printGame($dbConn, $game, 1);
		if(isset($_SESSION['uid'])) {
			printGamePicker($dbConn, $game);
		}
		?>
	</div>
	<?php
}
?>
</body>
<script src="js/picker.js"></script>
</html> 