<?php

class emailStyle {
	public $style;

	function __construct($styleString) {
		$this->style = $styleString;
	}

	function styleTag() {
		return 'style="' . $this->style . '"';
	}
}

function emailClasses() {
	$classes['standingsTable'] = new emailStyle('
		color: white;
		text-align: center;
		margin-top: 30px;
		margin-left: auto;
		margin-right: auto;
		border: 2px solid gray;
		border-collapse: collapse;');
	$classes['rowStyle'] = new emailStyle('
		font-variant: small-caps;
		font-size: 16px;
		color: white;
		text-align: center;
		border: 2px solid gray;
		height: 40px;');
	$classes['rankCell'] = new emailStyle('
		text-align: right;
		font-size: 14px;
		border-top: 2px solid gray;
		border-left: 2px solid gray;
		border-bottom: 2px solid gray;
		width: 40px;
		height: 40px;');
	$classes['logoCell'] = new emailStyle('
		text-align: right;
		width: 50px;
		border-top: 2px solid gray;
		border-bottom: 2px solid gray;
		height: 40px;');
	$classes['nameCell'] = new emailStyle('
		text-align: left;
		width: 300px;
		border-top: 2px solid gray;
		border-bottom: 2px solid gray;
		height: 40px;');
	$classes['recordCell'] = new emailStyle('
		text-align: center;
		width: 50px;
		font-size: 15px;
		border-top: 2px solid gray;
		border-bottom: 2px solid gray;
		height: 40px;');
	$classes['scoreCell'] = new emailStyle('
		text-align: right;
		width: 50px;
		font-size: 15px;
		border-top: 2px solid gray;
		border-bottom: 2px solid gray;
		border-right: 2px solid gray;
		height: 40px;');
	$classes['gamesTable'] = new emailStyle('
		color: white;
		text-align: left;
		margin-top: 30px;
		margin-left: auto;
		margin-right: auto;
		border: 2px solid gray;
		border-collapse: collapse;');
	$classes['gamesRow'] = new emailStyle('
		font-variant: small-caps;
		color: white;
		border: 2px solid gray;
		height: 40px;');
	$classes['gamesRow4x'] = new emailStyle('
		font-variant: small-caps;
		color: white;
		border: 2px solid gray;
		height: 40px;
		background: #140330');
	$classes['gamesAwayLogo'] = new emailStyle('
		height: 40px;
		border-top: 2px solid gray;
		border-left: 2px solid gray;
		border-bottom: 2px solid gray;');
	$classes['gamesRankCell'] = new emailStyle('
		height: 40px;
		font-size: 12px;
		border-top: 2px solid gray;
		border-bottom: 2px solid gray;');
	$classes['gamesNameCell'] = new emailStyle('
		height: 40px;
		font-variant: small-caps;
		width: 150px;
		font-size: 16px;
		border-top: 2px solid gray;
		border-bottom: 2px solid gray;');
	$classes['gamesAtCell'] = new emailStyle('
		font-variant: small-caps;
		font-style: italic;
		font-size: 12px;
		width: 25px;
		height: 40px;
		text-align: center;
		border-top: 2px solid gray;
		border-bottom: 2px solid gray;');
	$classes['gamesHomeLogo'] = new emailStyle('
		height: 40px;
		border-top: 2px solid gray;
		border-bottom: 2px solid gray;');
	$classes['gamesVenueCell'] = new emailStyle('
		height: 40px;
		border-top: 2px solid gray;
		border-bottom: 2px solid gray;
		font-variant: small-caps;
		font-size: 12px;
		width: 125px');
	$classes['gamesSpreadCell'] = new emailStyle('
		height: 40px;
		width: 100px;
		font-size: 14px;
		border-top: 2px solid gray;
		border-bottom: 2px solid gray;');
	$classes['gamesCustomName'] = new emailStyle('
		height: 40px;
		width: 140px;
		font-size: 14px;
		font-variant: small-caps;
		font-weight: bold;
		border-top: 2px solid gray;
		border-bottom: 2px solid gray;
		border-right: 2px solid gray;');
	return $classes;
}

function imageTeam($teamID) {
	return $GLOBALS['baseURL'] . '/images/teamLogo.php?teamID=' . $teamID . '&height=35';
}

function mailBodyStart() {
	$body = '<!DOCTYPE html><html><head><meta name="color-scheme" content="light only"></head><body><style>:root {color-scheme: light; font-family: sans-serif;}</style><table style="background: #212529; width: 100%">';
	$body .= '<tr><td>';
	return $body;
}

function mailHeader($user) {
	$color = $user->color;
	$altColor = $user->altColor;
	ob_start();
	?>
	<table style="background: #<?= $color ?>; color: #<?= $altColor ?>; width: 100%">
	<tr>
		<td><img src="<?= $GLOBALS['baseURL'] ?>/images/siteLogo.php?teamID=<?= $user->team ?>&height=30"></td>
	</tr>
	<tr style="font-variant: small-caps; font-weight: bold; font-size: 24px">
		<td> Pick 'Em</td>
	</tr>
	</table>
	<?php
	$header = ob_get_contents();
	ob_end_clean();
	return $header;
}

function mailScoreboard($dbConn, $users, $curWeek) {
	$cssClasses = emailClasses();
	foreach($users as $key => $user) {
		$users[$key]->liveScoreRecord($dbConn, $curWeek);
	}
	usort($users, 'scoreSort');
	ob_start();
	?>
	<table <?= $cssClasses['standingsTable']->styleTag() ?>>
		<tr <?= $cssClasses['rowStyle']->styleTag() ?>>
			<th>RK</th>
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
		<tr <?= $cssClasses['rowStyle']->styleTag() ?>>
			<td <?= $cssClasses['rankCell']->styleTag() ?>><?= $rank ?></td>
			<td <?= $cssClasses['logoCell']->styleTag() ?>><img src="<?= imageTeam($user->team) ?>"></td>
			<td <?= $cssClasses['nameCell']->styleTag() ?>><?= $user->name ?></td>
			<td <?= $cssClasses['recordCell']->styleTag() ?>><?= $user->wins ?> - <?= $user->losses ?></td>
			<td <?= $cssClasses['scoreCell']->styleTag() ?>><?= $user->score ?></td>
		</tr>
		<?php
	}
	echo '</table>';
	$scoreboard = ob_get_contents();
	ob_end_clean();
	return $scoreboard;
}

function mailWeeksGames($dbConn, $newWeek, $oldWeek) {
	$cssClasses = emailClasses();

	$games = getWeeksGames($dbConn, $newWeek, $oldWeek);
	$ranks = getRankArray($dbConn, $newWeek);
	ob_start();

	?>
	<table <?= $cssClasses['gamesTable']->styleTag() ?>>
	<tr <?= $cssClasses['gamesRow']->styleTag() ?>>
		<th colspan="7">Teams</th>
		<th>Location</th>
		<th>Vegas Spread</th>
		<th></th>
	</tr>
	<?php
	foreach($games as $game) {
		if($game->multiplier == 4) {
			mailGamesRows($dbConn, $game, $ranks);
		}
	}
	foreach($games as $game) {
		if($game->multiplier != 4) {
			mailGamesRows($dbConn, $game, $ranks);
		}
	}
	echo "</table>";

	$gameList = ob_get_contents();
	ob_end_clean();
	return $gameList;
}

function mailGamesRows($dbConn, $game, $ranks) {
	$cssClasses = emailClasses();
	?>
	<tr 
		<?php
		if($game->multiplier == 4) {
			echo $cssClasses['gamesRow4x']->styleTag();
		} else {
			echo $cssClasses['gamesRow']->styleTag();
		}
		?>
		>
		<td <?= $cssClasses['gamesAwayLogo']->styleTag() ?>>
			<img src="<?= imageTeam($game->away->id) ?>">
		</td>
		<?php
		if(isset($ranks[$game->away->id])) {
			?>
			<td <?= $cssClasses['gamesRankCell']->styleTag() ?>><?= $ranks[$game->away->id] ?></td>
			<?php
		} else {
			?>
			<td <?= $cssClasses['gamesRankCell']->styleTag() ?>></td>
			<?php
		}
		?>
		<td <?= $cssClasses['gamesNameCell']->styleTag() ?>> <?= $game->away->school ?></td>
		<td <?= $cssClasses['gamesAtCell']->styleTag() ?>>
		<?php
		if($game->isNeutral) {
			echo 'vs.';
		} else {
			echo 'at';
		}	
		?>
		</td>
		<td <?= $cssClasses['gamesHomeLogo']->styleTag() ?>>
			<img src="<?= imageTeam($game->home->id) ?>"></td>
		<?php
		if(isset($ranks[$game->home->id])) {
			?>
			<td <?= $cssClasses['gamesRankCell']->styleTag() ?>><?= $ranks[$game->home->id] ?></td>
			<?php
		} else {
			?>
			<td <?= $cssClasses['gamesRankCell']->styleTag() ?>></td>
			<?php
		}
		?>
		<td <?= $cssClasses['gamesNameCell']->styleTag() ?>><?= $game->home->school ?></td>
		<td <?= $cssClasses['gamesVenueCell']->styleTag() ?>><?= $game->venue->name ?><br><?= $game->venue->city ?>,<?php if($game->venue->country == 'US') echo $game->venue->state; else echo countryName($game->venue->country); ?></td>
		<td <?= $cssClasses['gamesSpreadCell']->styleTag() ?>>
			<?php
			if($game->spread != 0) {
				if($game->favID == $game->home->id) {
					echo $game->home->abbr;
				} else {
					echo $game->away->abbr;
				}
				echo ' -' . $game->spread;
			} else {
				echo 'EVEN';
			}
			?>
		</td>
		<td <?= $cssClasses['gamesCustomName']->styleTag() ?>>
			<?= $game->gameNameBar() ?>
		</td>
	</tr>
	<?php
}


?>