<?php
require('functions.php');

if(session_status() == PHP_SESSION_NONE) session_start();
$dbConn = sqlConnect();
$curWeek = getCurWeek($dbConn);
$users = getUsers($dbConn, $curWeek);

pageHeader($dbConn, 'Live Scoreboard');
foreach($users as $key => $user) {
	$users[$key]->liveScoreRecord($dbConn, $curWeek);
}

usort($users, 'scoreSort');
?>
<table class="table-sm align-middle m-3 scoreboard">
	<tr class="lastRow">
	</tr>
	<tr class="scoreboard-header">
		<th class="scoreboard-header-rank">RK</th>
		<th colspan="2">User</th>
		<th>Rec</th>
		<th>Pts</th>
	</tr>
	<?php
	$rank = 0;
	$row = 0;
	$lastScore = 0;
	foreach($users as $user) {
		$row++;
		if($lastScore != $user->score) {
			$rank = $row;
		}
		$lastScore = $user->score;
		?>
		<tr class="scoreboard-user">
			<td class="scoreboard-rank"><?= $rank ?></td>
			<td class="scoreboard-logo"><img height="35" width="35" src="<?= getLogo($dbConn, $user->team) ?>"></td>
			<td class="scoreboard-name"><?= $user->name ?></td>
			<td class="scoreboard-record"><?= $user->wins ?> - <?= $user->losses ?></td>
			<td class="scoreboard-points"><?= $user->score ?></td>
		</tr>
		<?php
	}