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
	public $pick = null;

	function __construct($gameArray, $curWeek, $dbConn, $ranks) {
		$this->id = $gameArray['id'];
		$this->week = $curWeek;
		$this->name = $gameArray['name'];
		$this->date = $gameArray['startDate'];
		$this->home = new teamObj($dbConn, $this->id, $this->date, $this->week, $ranks, array(
					'id' => $gameArray['homeID'],
					'school' => $gameArray['homeSchool'],
					'mascot' => $gameArray['homeMascot'],
					'abbr' => $gameArray['homeAbbr'],
					'confID' => $gameArray['homeConfID']
				)
			);
		$this->away = new teamObj($dbConn, $this->id, $this->date, $this->week, $ranks, array(
					'id' => $gameArray['awayID'],
					'school' => $gameArray['awaySchool'],
					'mascot' => $gameArray['awayMascot'],
					'abbr' => $gameArray['awayAbbr'],
					'confID' => $gameArray['awayConfID']
				)
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

	function __construct($dbConn, $gameID, $date, $curWeek, $ranks, $teamArray) {-
		$this->id = $teamArray['id'];
		$this->school = $teamArray['school'];
		$this->mascot = $teamArray['mascot'];
		$this->abbr = $teamArray['abbr'];

		// Load rank if exists
		if(isset($ranks[$this->id])) {
			$this->rank = $ranks[$this->id];
		}
		if($dbConn != null) {
			// Load Conference
			$query = 'SELECT * FROM conferences WHERE id = ?';
			$queryArray = array($teamArray['confID']);
			$rslt = sqlsrv_query($dbConn, $query, $queryArray);
			if(sqlsrv_has_rows($rslt)) {
				$this->conf = new confObj(sqlsrv_fetch_array($rslt));
			}

			// Load picks
			$query = 'SELECT * FROM picks WHERE gameID = ? AND teamID = ?';
			$queryArray = array($gameID, $this->id);
			$rslt = sqlsrv_query($dbConn, $query, $queryArray);
			$this->picked = 0;
			if(sqlsrv_has_rows($rslt)) {
				while(sqlsrv_fetch_array($rslt)) {
					$this->picked++;
				}
			}

			// Get line scores for game
			if($gameID != null) {
				$query = 'SELECT * FROM gameLineScores WHERE gameID = ? AND teamID = ?';
				$queryArray = array($gameID, $this->id);
				$rslt = sqlsrv_query($dbConn, $query, $queryArray);
				if(sqlsrv_has_rows($rslt)) {
					while($row = sqlsrv_fetch_array($rslt)) {
						$this->lineScores[$row['period']] = $row['points'];
					}
				}
			}

			// Get win/loss records
			if($date != null) {
				$query = 'SELECT winnerID, loserID, isConference 
							FROM games 
							LEFT JOIN weeks ON weeks.id = games.weekID
							WHERE games.startDate <= ? AND (winnerID = ? OR loserID = ?) AND year = ?';
				$queryArray = array($date, $this->id, $this->id, $curWeek->year);
				$rslt = sqlsrv_query($dbConn, $query, $queryArray);
				if(sqlsrv_has_rows($rslt)) {
					while($row = sqlsrv_fetch_array($rslt)) {
						if($row['winnerID'] == $this->id) {
							$this->wins++;
							if($row['isConference'] == 1) {
								$this->confWins++;
							}
						} elseif($row['loserID'] == $this->id) {
							$this->losses++;
							if($row['isConference'] == 1) {
								$this->confLosses++;
							}
						}
					}
				}
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