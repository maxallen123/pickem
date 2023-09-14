$(document).ready(function() {

	$('.surroundHamburger').popover({
		content: function (team) {
			return setPopover(team);
		}});

	/*const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
	const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));*/	

	updatePicks();
	setInterval(() => {
		updatePicks();
		compare();
	}, 10000);
});

function setPopover(team) {
	var teamID = $(team).attr('id').replace('teamSchedule-', '');
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

function setPick(gameID) {
	var selectID = '#pick-' + gameID;
	var selectVal = $(selectID).val();
	console.log(selectVal);

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
				updatePicks();
			}
	});
}

function gameLineScores(game, teamID) {
	var totalScore = 0;
	var otScore = 0;
	var ot = 0;
	var gameStarted = 0;
	if(teamID == game['home']['id']) {
		var homeAway = 'home';
	} else {
		var homeAway = 'away';
	}
	$.each(game[homeAway]['lineScores'], function(period, periodScore) {
		gameStarted = 1;
		totalScore += periodScore;
		if(period <= 4) {
			var boxScore = '#lineScore-' + game['id'] + '-' + teamID + '-' + period;
			$(boxScore).text(periodScore);
		} else {
			ot++;
			otScore += periodScore;
		}
	});
	if(ot > 0) {
		$('#header-OT-' + game['id']).text('OT (' + ot + ')');
		$('#lineScore-' + game['id'] + '-' + teamID + '-5').text(otScore);
	}
	if(gameStarted) {
		$('#total-' + teamID + '-' + game['id']).text(totalScore);
	}
}

function winnerCSS(game) {
	$('#teamRow-' + game['id'] + '-' + game['winnerID']).addClass('winner-' + game['winnerID']);
	$('#teamLink-' + game['id'] + '-' + game['winnerID']).addClass('winner-' + game['winnerID']);
}

function updateHeader(game) {
	var date = new Date(game['date']['date'] + 'Z');
	var statusHeader = '#header-status-' + game['id'];
	if(game['completed']) {
		$(statusHeader).text('Final');
	}
	if(game['statusID'] == 1) {
		$(statusHeader).text(formattedDateTime(date));
	}
	if(game['statusID'] == 2) {
		if(game['curPeriod'] < 5) {
			var quarterName = ordinal_suffix_of(game['curPeriod']) + ' Quarter';
			var minutes = Math.floor(game['curTime'] / 60);
			var seconds = game['curTime'] % 60;
			if(seconds < 10) {
				seconds = '0' + seconds;
			}
			$(statusHeader).text(quarterName + ' - ' + minutes + ':' + seconds);
		} else {
			$(statusHeader).text(ordinal_suffix_of(game['curPeriod'] - 4) + ' Overtime');
		}
	}
	if(game['statusID'] == 22) {
		$(statusHeader).text('End of ' + ordinal_suffix_of(game['curPeriod']) + ' Quarter');
	}
	if(game['statusID'] == 23) {
		$(statusHeader).text('Halftime');
	}
	if(game['statusID'] == 7) {
		$(statusHeader).text('Delayed');
	}
}

function updateGameStatus(game) {
	var gameStatusCell = '#gameStatus-' + game['id'];
	if([2, 22].includes(game['statusID'])) {
		if(game['possession'] == game['home']['id']) {
			var hasBall = game['home']['id'];
			var hasBallSide = 'home';
			var notBall = game['away']['id'];
		} else {
			var hasBall = game['away']['id'];
			var hasBallSide = 'away';
			var notBall = game['home']['id'];
		}
		var hasBallNameCell = '#teamName-' + hasBall + '-' + game['id'];
		var notBallNameCell = '#teamName-' + notBall + '-' + game['id'];
		$(hasBallNameCell).addClass('possession');
		$(notBallNameCell).removeClass('possession');
		var yardLine = game['yardLine'];
		if(yardLine > 50) {
			yardLine = Math.abs(yardLine - 100);
			var fieldSide = game['away']['abbr'];
		} else if(yardLine != 50) {
			var fieldSide = game['home']['abbr'];
		} else {
			var fieldSide = 'the'
		}

		if(yardLine - game['toGo'] == 0) {
			toGo = 'Goal';
		} else {
			toGo = game['toGo'];
		}
		
		if(game['down'] > 0) {
			var gameStatus = ordinal_suffix_of(game['down']) + ' and ' + toGo + ' at ' + fieldSide + ' ' + yardLine;
		} else {
			gameStatus = 'Kickoff ' + game[hasBallSide]['abbr'];
		}
		$(gameStatusCell).text(gameStatus);

		if((hasBall == game['home']['id'] && game['yardLine'] >= 80) || (hasBall == game['away']['id'] && game['yardLine'] <= 20)) {
			$(hasBallNameCell).addClass('redzone');
		} else {
			$(hasBallNameCell).removeClass('redzone');
		}
	} else {
		$(gameStatusCell).text('');
		$('#teamName-' + game['home']['id'] + '-' + game['id']).removeClass(['possession', 'redzone']);
		$('#teamName-' + game['away']['id'] + '-' + game['id']).removeClass(['possession', 'redzone']);
	}
}

function updatePicks() {
	$.ajax({
		method: 'POST',
		url: './ajax/picker.php',
		data: {
			function: 'updatePicks'
		},
		datatype: 'json',
		success:
			function (picks) {
				var ourScore = parseInt($('#userPreweekScore').val());
				$.each(picks, function(index, game) {
					var date = new Date(game['date']['date'] + 'Z');
					var curDate = new Date();
					var selectID = '#pick-' + game['id'];
					var awayID = game['away']['id'];
					var homeID = game['home']['id'];
					var othersAway = '#others-' + awayID + '-' + game['id'];
					var othersHome = '#others-' + homeID + '-' + game['id'];
					var score = '#score-' + game['id'];

					// Update game linescores
					gameLineScores(game, homeID);
					gameLineScores(game, awayID);

					// Apply CSS if game is over
					if(game['completed']) {
						winnerCSS(game);
					}

					// Update Time/Finished fields
					updateHeader(game);
					updateGameStatus(game);
					if(game['completed']) {
						if(game['pick'] != null) {
							if(game['pick'] == game['winnerID'] || (game['jokeGame'] == 1 && game['pick'] != -1)) {
								$(selectID).addClass('winner-' + game['pick']);
								$(score).text(ourScore += game['multiplier']);
								$(score).addClass('scoreWinner');
							} else {
								$(score).text(ourScore);
								$(score).removeClass('scoreWinner');
								$(selectID).addClass('loserSelect');
							}
						}
					} 
					
					// Disable fields when game starts
					if(date.getTime() < curDate.getTime()) {
						$(selectID).prop('disabled', true);
					}

					// Update selections
					$(selectID).val(game['pick']);
					$(othersAway).text(game['away']['picked']);
					$(othersHome).text(game['home']['picked']);
				});
			}
	});
}

function compare() {
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
					var curScore = returnVal['score'];
					$.each(returnVal['picks'], function(gameID, pick) {
						compareLogo = '#logoCompareCell-' + pick['gameID'];
						compareID = '#compare-' + pick['gameID'];
						compareScore = '#compareScore-' + pick['gameID'];
						if(pick['pickID'] != -1) {
							$(compareLogo).html('<img src="images/teamLogo.php?teamID=' + pick['pickID'] + '&height=30">')
						} else {
							$(compareLogo).html('');
						}
						$(compareID).val(pick['pickID']);
						if(userID != -1) {
							if(pick['winnerID'] != null) {
								if(pick['winnerID'] == pick['pickID'] || (pick['jokeGame'] && pick['pickID'] != -1)) {
									$(compareID).addClass('winner-' + pick['pickID']);
									$(compareScore).text(curScore += pick['multiplier']);
									$(compareScore).addClass('scoreWinner')
								} else {
									$(compareScore).text(curScore);
									$(compareScore).removeClass('scoreWinner');
									$(compareID).removeClass('winner-' + pick['winnerID']);
									$(compareID).removeClass('winner-' + pick['loserID']);
									$(compareID).addClass('loserSelect');
								}
							}
						} else {
							$(compareLogo).html('');
							$(compareScore).text('');
							$(compareScore).removeClass('scoreWinner');
							$(compareID).removeClass('loserSelect');
							$(compareID).removeClass('winner-' + pick['winnerID']);
							$(compareID).removeClass('winner-' + pick['loserID']);
						}
					});
				}
		});
	}
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