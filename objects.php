<?php

class weekObj {
	public $week;
	public $year;
	public $seasonType;
	public $weekID;

	function __construct($weekArray) {
		$this->week = $weekArray['week'];
		$this->year = $weekArray['year'];
		$this->seasonType = $weekArray['seasonType'];
		$this->weekID = $weekArray['id'];
	}
}

class gameObj {
	public $id;
	public $week;
	public $name;
	public $home;
	public $away;
	public $date;
	public $venue;
	public $completed;
	public $homePoints;
	public $awayPoints;
	public $winnerID;
	public $loserID;
	public $favID;
	public $dogID;
	public $spread;
	public $customName;
	public $pick = null;

	function __construct($gameArray, $curWeek, $dbConn) {
		$this->id = $gameArray['id'];
		$this->week = $curWeek;
		$this->name = $gameArray['name'];
		$this->customName = $gameArray['customName'];
		$this->date = $gameArray['startDate'];

		$homeArray = array(
			'id' => $gameArray['homeID'],
			'school' => $gameArray['homeSchool'],
			'mascot' => $gameArray['homeMascot'],
			'abbr' => $gameArray['homeAbbr'],
			'confID' => $gameArray['homeConfID'],
			'confName' => $gameArray['homeConfName'],
			'confShortName' => $gameArray['homeConfShortName'],
			'confAbbr' => $gameArray['homeConfAbbr'],
			'confIsFBS' => $gameArray['homeConfIsFBS'],
			'picked' => $gameArray['homePicks'],
			'rank' => $gameArray['homeRank'],
			'wins' => $gameArray['homeWins'],
			'losses' => $gameArray['homeLosses'],
			'confWins' => $gameArray['homeConfWins'],
			'confLosses' => $gameArray['homeConfLosses'],
			'comedyName' => $gameArray['homeComedyName']
		);

		$awayArray = array(
			'id' => $gameArray['awayID'],
			'school' => $gameArray['awaySchool'],
			'mascot' => $gameArray['awayMascot'],
			'abbr' => $gameArray['awayAbbr'],
			'confID' => $gameArray['awayConfID'],
			'confName' => $gameArray['awayConfName'],
			'confShortName' => $gameArray['awayConfShortName'],
			'confAbbr' => $gameArray['awayConfAbbr'],
			'confIsFBS' => $gameArray['awayConfIsFBS'],
			'picked' => $gameArray['awayPicks'],
			'rank' => $gameArray['awayRank'],
			'wins' => $gameArray['awayWins'],
			'losses' => $gameArray['awayLosses'],
			'confWins' => $gameArray['awayConfWins'],
			'confLosses' => $gameArray['awayConfLosses'],
			'comedyName' => $gameArray['awayComedyName']
		);

		$venueArray = array(
			'id' => $gameArray['venueID'],
			'name' => $gameArray['venueName'],
			'city' => $gameArray['city'],
			'state' => $gameArray['state'],
			'country' => $gameArray['country']
		);

		$this->home = new teamObj(
			$dbConn,
			$homeArray,
			$gameArray
		);

		$this->away = new teamObj(
			$dbConn,
			$awayArray,
			$gameArray
		);
		
		$this->venue = new venueObj(array(
					'id' => $gameArray['venueID'],
					'name' => $gameArray['venueName'],
					'city' => $gameArray['city'],
					'state' => $gameArray['state'],
					'country' => $gameArray['country']
				)
			);
		$this->completed = $gameArray['completed'];
		$this->homePoints = $gameArray['homePoints'];
		$this->awayPoints = $gameArray['awayPoints'];
		$this->winnerID = $gameArray['winnerID'];
		$this->loserID = $gameArray['loserID'];
		$this->favID = $gameArray['favID'];
		$this->dogID = $gameArray['dogID'];
		$this->spread = $gameArray['spread'];
		if(isset($_SESSION['uid'])) {
			$query = 'SELECT * FROM picks WHERE gameID = ? AND userID = ?';
			$queryArray = array($this->id, $_SESSION['uid']);
			$rslt = sqlsrv_query($dbConn, $query, $queryArray);
			if(sqlsrv_has_rows($rslt)) {
				$pickArray = sqlsrv_fetch_array($rslt);
				$this->pick = $pickArray['teamID'];
			} else {
				$this->pick = -1;
			}
		}
	}
}

class teamObj {
	public $id;
	public $school;
	public $mascot;
	public $abbr;
	public $conf;
	public $rank = null;
	public $wins = 0;
	public $losses = 0;
	public $confWins = 0;
	public $confLosses = 0;
	public $lineScores = array();
	public $picked;

	function __construct($dbConn, $teamArray, $gameArray) {
		$this->id = $teamArray['id'];
		$this->school = $teamArray['school'];
		if($teamArray['comedyName'] != null) {
			$this->school = $teamArray['comedyName'];
		}
		$this->mascot = $teamArray['mascot'];
		$this->abbr = $teamArray['abbr'];
		$this->rank = $teamArray['rank'];
		$this->conf = new confObj(array(
			'id' => $teamArray['confID'],
			'name' => $teamArray['confName'],
			'short_name' => $teamArray['confShortName'],
			'abbreviation' => $teamArray['confAbbr'],
			'isFBS' => $teamArray['confIsFBS']
		));
		$this->picked = $teamArray['picked'];
		$this->wins = $teamArray['wins'];
		$this->losses = $teamArray['losses'];
		$this->confWins = $teamArray['confWins'];
		$this->confLosses = $teamArray['confLosses'];

		$query = 'SELECT * FROM gameLineScores WHERE gameID = ? AND teamID = ?';
		$queryArray = array($gameArray['id'], $this->id);
		$rslt = sqlsrv_query($dbConn, $query, $queryArray);
		if(sqlsrv_has_rows($rslt)) {
			while($period = sqlsrv_fetch_array($rslt)) {
				$this->lineScores[$period['period']] = $period['points'];
			}
		}
	}
}

class venueObj {
	public $id;
	public $name;
	public $city;
	public $state;
	public $country;

	function __construct($venueArray) {
		$this->id = $venueArray['id'];
		$this->name = $venueArray['name'];
		$this->city = $venueArray['city'];
		$this->state = $venueArray['state'];
		$this->country = $venueArray['country'];
	}
}

class confObj {
	public $id;
	public $name;
	public $shortName;
	public $abbr;
	public $isFBS;

	function __construct($confArray) {
		$this->id = $confArray['id'];
		$this->name = $confArray['name'];
		$this->shortName = $confArray['short_name'];
		$this->abbr = $confArray['abbreviation'];
		$this->isFBS = $confArray['isFBS'];
	}
}

class userObj {
	public $id;
	public $name;
	public $team;
	public $email;

	function __construct($userArray) {
		$this->id = $userArray['id'];
		$this->name = $userArray['name'];
		$this->team = $userArray['team'];
		$this->email = $userArray['email'];
	}
}

class othersPickObj {
	public $gameID;
	public $pickID;
	public $winnerID;

	function __construct($pickArray) {
		$this->gameID = $pickArray['id'];
		$this->pickID = $pickArray['teamID'];
		if($this->pickID == null) {
			$this->pickID = -1;
		}
		$this->winnerID = $pickArray['winnerID'];
	}
}