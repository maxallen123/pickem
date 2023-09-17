<?php
require('functions.php');

if(session_status() == PHP_SESSION_NONE) session_start();
$dbConn = sqlConnect();
$curWeek = getCurWeek($dbConn);
$games = getWeeksGames($dbConn, $curWeek);
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
	if(isset($_SESSION['uid'])) {
		?>
		<div class="col-3 header-compare">
			Compare to:	
		</div>
		<div class="col-3 selectCompareCell">
			<select id="selectCompare" class="form-select selectCompare text-center" onchange="updateCompare()">
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
for($boxID = 0; $boxID <= 150; $boxID++) {
	if(isset($games[$boxID])) {
	?>
	<div class="row">
		<?php
		$games[$boxID]->printGame($boxID, 1);
		if(isset($_SESSION['uid'])) {
			$games[$boxID]->printGamePicker($boxID);
		}
		?>
	</div>
	<?php
	} else {
	}
}
?>
</body>
<script src="js/picker.js"></script>
</html> 