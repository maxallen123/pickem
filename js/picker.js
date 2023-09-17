$(document).ready(function() {

	$('.surroundHamburger').popover({
		content: function (team) {
			return setPopover(team);
		}});

	/*const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
	const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));*/	

	$('body').on('click', function (e) {
		$('[data-bs-toggle="popover"]').each(function () {
			//the 'is' for buttons that trigger popups
			//the 'has' for icons within a button that triggers a popup
			if (!$(this).is(e.target) && $(this).has(e.target).length === 0 && $('.popover').has(e.target).length === 0) {
				$(this).popover('hide');
			}
		});
	});

	updatePage();
	setInterval(() => {
		updatePage();
		updateCompare();
	}, 10000);
});

function setPopover(team) {
	var teamID = $(team).val().replace('teamSchedule-', '');
	loadSchedule(teamID);
	var teamTableHTML = '';
	for(var row = 0; row < 15; row++) {
		teamTableHTML += `
			<div id="scheduleRow-${teamID}-${row}" class="row scheduleRow">
				<div id="scheduleHomeAway-${teamID}-${row}" class="col scheduleHomeAway"></div>
				<div id="scheduleLogo-${teamID}-${row}" class="col scheduleLogo"></div>
				<div id="scheduleName-${teamID}-${row}" class="col scheduleName"></div>
				<div id="scheduleWinLoss-${teamID}-${row}" class="col scheduleWinLoss"></div>
				<div id="scheduleScore-${teamID}-${row}" class="col scheduleScore"></div>
			</div>
		`;
	}
	return teamTableHTML;
}

function setPick(boxID) {
	var gameIDHidden = '#box-' + boxID;
	var gameID = $(gameIDHidden).val();
	var selectID = '#pick-' + boxID;
	var selectVal = $(selectID).val();

	$.ajax({
		method: "POST",
		url: './ajax/picker.php',
		data: {
			function: 'setPick',
			gameID: gameID,
			pick: selectVal
		},
		datatype: 'json',
		success:
			function () {
				if(selectVal != -1) {
					$('#logoCell-' + gameID).html('<img src="images/teamLogo.php?teamID=' + selectVal + '&height=30">')
				} else {
					$('#logoCell-' + gameID).html('');
				}
				updatePage();
			}
	});
}

function loadSchedule(teamID) {
	$.ajax({
		method: 'POST',
		url: './ajax/picker.php',
		data: {
			function: 'schedule',
			teamID: teamID
		},
		datatype: 'json',
		success: 
			function(schedule) {
				schedule = schedule['schedule'];
				for(var row = 0; row < 15; row++) {
					if(typeof schedule[row] != 'undefined') {
						$(`#scheduleHomeAway-${teamID}-${row}`).text(schedule[row]['homeAway']);
						$(`#scheduleLogo-${teamID}-${row}`).html(`<img src="images/teamLogo.php?teamID=${schedule[row]['opptID']}&height=15">`);
						$(`#scheduleName-${teamID}-${row}`).text(schedule[row]['opptAbbr']);
						if(schedule[row]['winLoss'] != null) {
							$(`#scheduleWinLoss-${teamID}-${row}`).text(schedule[row]['winLoss']);
							$(`#scheduleWinLoss-${teamID}-${row}`).addClass(`schedule-${schedule[row]['winLoss']}`);
							$(`#scheduleScore-${teamID}-${row}`).text(`${schedule[row]['winnerScore']}-${schedule[row]['loserScore']}`);
							$(`#scheduleScore-${teamID}-${row}`).addClass(`schedule-gameOver`);
						} else {
							$(`#scheduleWinLoss-${teamID}-${row}`).addClass('schedule-none');
							$(`#scheduleScore-${teamID}-${row}`).text(`${schedule[row]['date']}`);
						}
					} else {
						$(`#scheduleRow-${teamID}-${row}`).remove();
					}
				}
			}
	});
}

function updatePage() {
	$.ajax({
		method: 'POST',
		url: './ajax/picker.php',
		data: {
			function: 'updatePage'
		},
		datatype: 'json',
		success:
			function (games) {
				var myScore = parseInt($('#userPreweekScore').val());
				$.each(games, function(boxID, game) {
					updateGame(boxID, game);
				});
			}
	});
}

function updateGame(boxID, game) {
	var newGame = diffGame(boxID, game);
	updateStatusHeader(boxID, game);
	updateHeaderOT(boxID, game);
	updatePicked(boxID, game);
	updateSpread(boxID, game);
	if(newGame) {
		updateGOTW(boxID, game);
		updateGameID(boxID, game);
		updateTeam(boxID, game, 'away');
		updateTeam(boxID, game, 'home');
		updateRecord(boxID, game, 'away');
		updateRecord(boxID, game, 'home');
		updateSchedule(boxID, game, 'away');
		updateSchedule(boxID, game, 'home');
		updateGameName(boxID, game);
		updateGameInfoBox(boxID, game);
		updatePickers(boxID, game);
	}
	updateLineScores(boxID, game, 'away', newGame);
	updateLineScores(boxID, game, 'home', newGame);
	updateGameStatus(boxID, game);
}

function diffGame(boxID, game) {
	var boxIDInput = '#box-' + boxID;
	if($(boxIDInput).val() == game['id']) {
		return 0;
	} else {
		var oldID = $(boxIDInput).val();
		$(boxIDInput).val(game['id']);
		return oldID;
	}
}

function updateStatusHeader(boxID, game) {
	var statusHeaderText = '';
	var statusInput = '#status-' + boxID;
	
	if($(statusInput).val() != game['statusID']) {
		$(statusInput).val(game['statusID']);
	}

	switch(game['statusID']) {
		case 1:
			statusHeaderText = formattedDateTime(new Date(game['date']['date'] + 'Z'));
			break;
		case 2:
			if(game['curPeriod'] < 5) {
				statusHeaderText += ordinal_suffix_of(game['curPeriod']) + ' Quarter';
				statusHeaderText += ' - ' + Math.floor(game['curTime'] / 60) + ":";
				var seconds = game['curTime'] % 60;
				if(seconds < 10) {
					seconds = '0' + seconds;
				}
				statusHeaderText += seconds;
			} else {
				statusHeaderText = ordinal_suffix_of(game['curPeriod'] - 4) + ' Overtime';
			}
			break;
		case 22:
			if(game['curPeriod'] < 5) {
				statusHeaderText = 'End of ' + ordinal_suffix_of(game['curPeriod']) + ' Quarter';
			} else {
				statusHeaderText = 'End of ' + ordinal_suffix_of(game['curPeriod'] - 4) + ' Overtime';
			}
			break;
		case 23:
			statusHeaderText = 'Halftime';
			break;
		case 3:
			statusHeaderText = 'Final';
			break;
		case 4:
			statusHeaderText = 'Forfeit';
			break;
		case 5:
			statusHeaderText = 'Cancelled';
			break;
		case 6:
			statusHeaderText = 'Postponed';
			break;
		case 7:
			statusHeaderText = 'Delayed';
			break;
	}

	var statusHeader = '#header-status-' + boxID;
	if($(statusHeader).text() != statusHeaderText) {
		$(statusHeader).text(statusHeaderText);
	}
}

function updateHeaderOT(boxID, game) {
	var headerOT = '#header-OT-' + boxID;
	var headerOTText = '';
	if(game['curPeriod'] > 4) {
		headerOTText = 'OT (' + (game['curPeriod'] - 4) + ')';
	}
	if($(headerOT).text() != headerOTText) {
		$(headerOT).text(headerOTText);
	}
}

function updatePicked(boxID, game) {
	var othersAway = '#others-' + boxID + '-away';
	var othersHome = '#others-' + boxID + '-home';
	if($(othersAway).length) {
		picksOthersAway = game['away']['picked'];
		picksOthersHome = game['home']['picked'];
		if($(othersAway).text() != picksOthersAway) {
			$(othersAway).text(picksOthersAway);
		}
		if($(othersHome).text() != picksOthersHome) {
			$(othersHome).text(picksOthersHome);
		}
	}
}

function updateSpread(boxID, game) {
	var spread = game['spread'];
	var spreadBox = '#spreadBox-' + boxID;
	var spreadRowEvenText = 'spreadEven-' + boxID;
	var spreadRowEven = '#' + spreadRowEvenText;
	var spreadRowAwayText = 'spreadRow-' + boxID + '-away';
	var spreadRowAway = '#' + spreadRowAwayText;
	var spreadRowHomeText = 'spreadRow-' + boxID + '-home';
	var spreadRowHome = '#' + spreadRowHomeText;
	if(spread != null) {
		spread = (0 - spread).toFixed(1);
		if(spread < 0) {
			if(game['favID'] == game['away']['id']) {
				var favRow = spreadRowAway;
				var dogRow = spreadRowHome;
			} else {
				var favRow = spreadRowHome;
				var dogRow = spreadRowAway;
			}

			if($(spreadRowEven).length) {
				$(spreadRowEven).remove();
				$(spreadBox).append(`<div id="${spreadRowAwayText}" class="spreadRow"></div>`);
				$(spreadBox).append(`<div id="${spreadRowHomeText}" class="spreadRow"></div>`);
			}

			if($(favRow).text() != spread) {
				$(favRow).text(spread);
				if($(dogRow).text() != '') {
					$(dogRow).text('');
				}
			}
		} else {
			if($(spreadRowAway).length) {
				$(spreadRowAway).remove();
				$(spreadRowHome).remove();
				$(spreadBox).append(`<div id="${spreadRowEvenText}" class="spreadEven"></div>`);
			}

			if($(spreadRowEven).text() != 'EVEN') {
				$(spreadRowEven).text('EVEN');
			}
		}
	} else {
		if($(spreadRowAway).length) {
			$(spreadRowAway).remove();
			$(spreadRowHome).remove();
			$(spreadBox).append(`<div id="${spreadRowEvenText}" class="spreadEven"></div>`);
		}

		if($(spreadRowEven).text() != '') {
			$(spreadRowEven).text('');
		}
	}
}

function updateGameID(boxID, game) {
	inputBoxID = '#box-' + boxID;
	$(inputBoxID).val(game['id']);
}

function updateGOTW(boxID, game) {
	var outerGameWrapper = '#outerGamewrapper-' + boxID;
	if(game['multiplier'] == 4) {
		$(outerGameWrapper).addClass('gotw');
	} else {
		$(outerGameWrapper).removeClass('gotw');
	}
}

function updateTeam(boxID, game, homeAway) {
	var teamRow = '#teamRow-' + boxID + '-' + homeAway;
	var teamLogo = '#logo-' + boxID + '-' + homeAway;
	var teamLink = '#teamLink-' + boxID + '-' + homeAway;

	var winnerClass = '';
	var teamID = game[homeAway]['id'];
	if(game['winnerID'] == teamID) {
		winnerClass = 'winner-' + teamID;
	}
	var teamLinkClassArray = ['link-light', 'link-underline', 'link-underline-opacity-100-hover', 'link-underline-opacity-0', winnerClass];

	$(teamRow).removeClass();
	$(teamRow).addClass('row');
	$(teamRow).addClass('gameRow');
	$(teamRow).addClass(winnerClass);

	$(teamLogo).attr('src', `images/teamLogo.php?teamID=${teamID}&height=30`);

	$(teamLink).removeClass();
	$(teamLink).addClass(teamLinkClassArray);
	$(teamLink).attr('href', `https://www.espn.com/college-football/team/_/id/${teamID}`);
	$(teamLink).text(game[homeAway]['school'])
}

function updateRecord(boxID, game, homeAway) {
	var recordRow = '#teamName-lower-' + boxID + '-' + homeAway;
	var recordRowText = '(' + game[homeAway]['wins'] + '-' + game[homeAway]['losses'];
	if(game[homeAway]['conf']['id'] != 18 && game[homeAway]['conf']['id'] != 32) {
		recordRowText += ', ' + game[homeAway]['confWins'] + '-' + game[homeAway]['confLosses'] + ' ' + game[homeAway]['conf']['abbr'];
	}
	recordRowText += ')';
	$(recordRow).text(recordRowText);
}

function updateSchedule(boxID, game, homeAway) {
	var scheduleButton = '#teamSchedule-' + boxID + '-' + homeAway;
	var teamID = game[homeAway]['id'];

	$(scheduleButton).val('teamSchedule-' + teamID);
}

function updateGameName(boxID, game) {
	var gameNameBox = '#gameName-' + boxID;
	var gameStatusBox = '#gameStatus-' + boxID;
	var gameNameString = '';

	if(game['isRivalry']) {
		if(game['rivalryName'] != null) {
			gameNameString += game['rivalryName'];
		} else {
			gameNameString += 'Rivalry Game';
		}

		if(game['rivalryTrophy'] != null) {
			gameNameString += ' - ' + game['rivalryTrophy'];
		}
	}
	
	if(game['name'] != null) {
		if(gameNameString != '') {
			gameNameString += ' - ';
		}
		gameNameString += game['name'];
	}

	if(game['customName'] != null) {
		if(gameNameString != '') {
			gameNameString += ' - ';
		}
		gameNameString += game['customName'];
	}

	console.log(gameNameString);

	if(gameNameString == '') {
		$(gameNameBox).addClass('hidden');
		$(gameNameBox).removeClass('lastRow');
		$(gameStatusBox).addClass('lastRow');
	} else {
		$(gameNameBox).removeClass('hidden');
		$(gameNameBox).addClass('lastRow');
		$(gameStatusBox).removeClass('lastRow');
	}
	$(gameNameBox).text(gameNameString);
}

function updateGameInfoBox(boxID, game) {
	var venueNameBox = '#venueName-' + boxID;
	var cityStateBox = '#cityState-' + boxID;
	var espnLink = '#espnLink-' + boxID;

	$(venueNameBox).text(game['venue']['name']);
	$(cityStateBox).text(cityState(game['venue']));
	$(espnLink).attr('href', `https://www.espn.com/college-football/game/_/gameId/${game['id']}`)
}

function cityState(venue) {
	var city = venue['city'];
	var stateCountry = '';

	if(venue['country'] == 'US') {
		stateCountry = venue['state'];
	} else {
		switch(venue['country']) {
			case 'IE':
				stateCountry = 'Ireland';
				break;
			case 'BS':
				stateCountry = 'Bahamas';
				break;
			case 'AU':
				stateCountry = 'Australia';
				break;
		}
	}
	return city + ', ' + stateCountry;
}

function updatePickers(boxID, game) {
	var logoCellUs = '#selectLogoCellUs-' + boxID;
	var logoCellThem = '#selectLogoCellThem-' + boxID;
	var selectUs = '#pick-' + boxID;
	var selectThem = '#compare-' + boxID;
	var scoreUs = '#score-' + boxID;
	var scoreThem = '#compareScore-' + boxID;
	var selectBlank = '';
	var selectAway = '';
	var selectHome = '';

	$(logoCellUs).find('img').remove();
	$(logoCellThem).find('img').remove();
	$(selectUs).find('option').remove();
	$(selectUs).removeClass();
	$(selectThem).find('option').remove();
	$(selectThem).removeClass();
	$(scoreUs).text('')
	$(scoreUs).removeClass(['scoreWinner', 'scoreLoser']);
	$(scoreThem).text('');
	$(scoreThem).removeClass(['scoreWinner', 'scoreLoser']);

	if(game['pick'] < 0 || game['pick'] == null) {
		selectBlank = 'selected';
	} else {
		$(logoCellUs).append(`<img id="logoPick-${boxID}" class="logoPick" src="images/teamLogo.php?teamID=${game['pick']}&height=30">`);
		if(game['pick'] == game['away']['id']) {
			selectAway = 'selected';
		} else {
			selectHome = 'selected';
		}
	}

	selectClass = '';
	if(game['completed']) {
		var matched = 0;
		for(row = boxID; row >= 0 && matched == 0; row--) {
			if($('#score-' + row).text() != '') {
				score = parseInt($('#score-' + row).text());
				matched = 1;
			}
		}
		if(matched = 0) {
			score = parseInt($('#userPreweekScore').val());
		}

		if(game['pick'] == game['winnerID'] || (game['jokeGame'] && game['pick'] > 0)) {
			selectClass = 'winner-' + game['pick'];
			$(scoreUs).text(score + game['multiplier']);
			$(scoreUs).addClass('scoreWinner');
		} else {
			selectClass = 'loserSelect';
			$(scoreUs).text(score);
			$(scoreUs).addClass('scoreLoser');
		}
	}

	$(selectUs).addClass(['form-select', 'selectPick', selectClass]);
	$(selectThem).addClass(['form-select', 'othersSelectPick']);
	$(selectUs).append(`<option value="-1" ${selectBlank}></option>`);
	$(selectUs).append(`<option value="${game['away']['id']}" ${selectAway}>${game['away']['school']}</option>`);
	$(selectUs).append(`<option value="${game['home']['id']}" ${selectHome}>${game['home']['school']}</option>`);
	$(selectThem).append(`<option value="-1" selected></option>`);
	$(selectThem).append(`<option value="${game['away']['id']}">${game['away']['school']}</option>`);
	$(selectThem).append(`<option value="${game['home']['id']}">${game['home']['school']}</option>`);

}

function updateLineScores(boxID, game, homeAway, newGame) {
	var totalScore = 0;
	var otScore = 0;
	var ot = 0;
	var gameStarted = 0;
	var boxScore = '';

	if(newGame) {
		for(period = 1; period <= 5; period++) {
			boxScore = `#lineScore-${boxID}-${homeAway}-${period}`;
			$(boxScore).text('');
		}
	}

	$.each(game[homeAway]['lineScores'], function(period, lineScore) {
		gameStarted = 1;
		totalScore += lineScore;
		if(period <= 4) {
			boxScore = `#lineScore-${boxID}-${homeAway}-${period}`;
			if($(boxScore).text() != lineScore) {
				$(boxScore).text(lineScore);
			}
		} else {
			ot++;
			otScore += lineScore;
		}
	});
	if(ot > 0) {
		var otCell = `#lineScore-${boxID}-${homeAway}-5`
		if($(otCell).text() != otScore) {
			$(otCell).text(otScore);
		}
	}
	if(gameStarted) {
		var totalCell = `#total-${boxID}-${homeAway}`;
		if($(totalCell).text != totalScore) {
			$(totalCell).text(totalScore);
		}
	}
}

function updateCompare() {
	if($('#selectCompare').length) {
		var userID = $('#selectCompare')[0].selectedOptions[0].attributes[0].value;

		$.ajax({
			method: 'POST',
			url: '/ajax/picker.php',
			data: {
				function: 'compare',
				userID: userID
			},
			datatype: 'json',
			success:
				function (returnVal) {
					var picks = returnVal['picks'];
					var curScore = returnVal['score'];
					$('.boxInput').each(function (index, inputBox) {
						boxID = $(inputBox).attr('id').replace('box-', '');
						gameID = $(inputBox).val();
						selectLogoCell = '#selectLogoCellThem-' + boxID;
						logoImg = '#compareLogo-' + boxID;
						selectCompare = '#compare-' + boxID;
						compareScore = '#compareScore-' + boxID;
						statusInput = '#status-' + boxID;

						if((typeof picks[gameID]) != 'undefined') {
							if(picks[gameID]['pickID'] > 0) {
								logoURL = `images/teamLogo.php?teamID=${picks[gameID]['pickID']}&height=30`;
								if($(logoImg).length) {
									if($(logoImg).attr('src') != logoURL) {
										$(logoImg).attr('src', logoURL);
									}
								} else {
									$(selectLogoCell).append(`<img id="compareLogo-${boxID}" src="${logoURL}">`);
								}
								if($(selectCompare).val() != picks[gameID]['pickID']) {
									$(selectCompare).val(picks[gameID]['pickID']);
								}
								if(picks[gameID]['winnerID'] != null) {
									winner = picks[gameID]['winnerID'];
									if(winner == picks[gameID]['pickID'] || (picks[gameID]['jokeGame'] && picks[gameID]['pickID'] > 0)) {
										if(!$(selectCompare).hasClass('winner-' + winner)) {
											$(selectCompare).removeClass();
											$(selectCompare).addClass(['form-select', 'othersSelectPick', 'winner-' + winner]);
											$(compareScore).removeClass('scoreLoser');
											$(compareScore).addClass('scoreWinner');
										}
										curScore += picks[gameID]['multiplier'];
										if($(compareScore).text() != curScore) {
											$(compareScore).text(curScore);
										}
									} else {
										if(!$(selectCompare).hasClass('loserSelect')) {
											$(selectCompare).removeClass();
											$(selectCompare).addClass(['form-select', 'othersSelectPick', 'loserSelect']);
											$(compareScore).removeClass('scoreWinner');
											$(compareScore).addClass('scoreLoser');
										}
										if($(compareScore).text() != curScore) {
											$(compareScore).text(curScore);
										}
									}
								}
							} 
						}
						if(((typeof picks[gameID]) == 'undefined') || (picks[gameID]['pickID'] <= 0)) {
							if($(logoImg).length) {
								$(logoImg).remove();
							}
							if($(selectCompare).val() != -1) {
								$(selectCompare).val(-1);
							}
							if(userID == -1) {
								if($(selectCompare).attr('class').split(' ').length > 2) {
									$(selectCompare).removeClass();
									$(selectCompare).addClass(['form-select', 'othersSelectPick']);
									$(compareScore).removeClass(['scoreLoser', 'scoreWinner']);
								}
								if($(compareScore).text() != '') {
									$(compareScore).text('');
								}
							}
							if($(statusInput).val() == 3 && userID != -1) {
								if(!$(selectCompare).hasClass('loserSelect')) {
									$(selectCompare).removeClass();
									$(selectCompare).addClass(['form-select', 'othersSelectPick', 'loserSelect']);
									$(compareScore).removeClass('scoreWinner');
									$(compareScore).addClass('scoreLoser');
								}
								if($(compareScore).text() != curScore) {
									$(compareScore).text(curScore);
								}
							}
						}
					});
				}
		});
	}
}

function updateGameStatus(boxID, game) {
	var gameStatusCell = '#gameStatus-' + boxID;

	if([2, 22].includes(game['statusID'])) {
		if(game['possession'] == game['home']['id']) {
			var hasBall = game['home']['id'];
			var hasBallSide = 'home';
			var notBall = game['away']['id'];
			var notBallSide = 'away';
		} else {
			var hasBall = game['away']['id'];
			var hasBallSide = 'away';
			var notBall = game['home']['id'];
			var notBallSide = 'home';
		}
		var yardLine = game['yardLine'];
		var fieldSide = 'the';
		if(yardLine > 50) {
			yardLine = 0 - (yardLine - 100);
			fieldSide = game['away']['abbr'];
		} else if(yardLine < 50) {
			fieldSide = game['home']['abbr'];
		}

		if(yardLine == game['toGo']) {
			toGo = 'Goal';
		} else {
			toGo = game['toGo'];
		}

		if(game['down'] > 0) {
			var gameStatus = ordinal_suffix_of(game['down']) + ' and ' + toGo + ' at ' + fieldSide + ' ' + yardLine;
		} else {
			gameStatus = 'Kickoff ' + game[hasBallSide]['abbr'];
		}

		var hasBallNameCell = '#teamName-upper-' + boxID + '-' + hasBallSide;
		var notBallNameCell = '#teamName-upper-' + boxID + '-' + notBallSide;
		if(!$(hasBallNameCell).hasClass('possession')) {
			$(hasBallNameCell).addClass('posession');
		}
		if($(notBallNameCell).hasClass('possession')) {
			$(notBallNameCell).removeClass('possession');
		}
		
		if((hasBall == game['home']['id'] && game['yardLine'] >= 80) || (hasBall == game['away']['id'] && game['yardLine'] <= 20)) {
			if(!$(hasBallNameCell).hasClass('redzone')) {
				$(hasBallNameCell).addClass('redzone');
			}
		} else {
			if($(hasBallNameCell).hasClass('redzone')) {
				$(hasBallNameCell).removeClass('redzone');
			}
		}
		if($(notBallNameCell).hasClass('redzone')) {
			$(notBallNameCell).removeClass('redzone');
		}

		if($(gameStatusCell).text() != gameStatus) {
			$(gameStatusCell).text(gameStatus);
		}
	} else {
		var homeTeamNameCell = `#teamName-upper-${boxID}-home`;
		var awayTeamNameCell = `#teamName-upper-${boxID}-away`;
		if(!$(gameStatusCell).hasClass('hidden')) {
			$(gameStatusCell).addClass('hidden');
		}
		if($(homeTeamNameCell).hasClass('possession')) {
			$(homeTeamNameCell).removeClass('possession');
		}
		if($(homeTeamNameCell).hasClass('redzone')) {
			$(homeTeamNameCell).removeClass('redzone');
		}
		if($(awayTeamNameCell).hasClass('possession')) {
			$(awayTeamNameCell).removeClass('possession');
		}
		if($(awayTeamNameCell).hasClass('redzone')) {
			$(awayTeamNameCell).removeClass('redzone');
		}
	}
}