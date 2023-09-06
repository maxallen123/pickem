$(document).ready(function() {
	updatePicks();
	setInterval(() => {
		updatePicks();
		compare();
	}, 10000);
});

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
				updatePicks();
			}
	});
}

function gameLineScores(game, homeAway) {
	var totalScore = 0;
	var otScore = 0;
	var ot = 0;
	var gameStarted = 0;
	$.each(game[homeAway]['lineScores'], function(period, periodScore) {
		gameStarted = 1;
		totalScore += periodScore;
		if(period <= 4) {
			var boxScore = '#lineScore-' + homeAway + '-' + period + '-' + game['id'];
			$(boxScore).text(periodScore);
		} else {
			ot++;
			otScore += periodScore;
		}
	});
	if(ot > 0) {
		$('#otScore-' + game['id']).text('OT(' + ot + ')');
		$('#lineScore-' + homeAway + '-5-' + game['id']).text(otScore);
	}
	if(gameStarted) {
		$('#total-' + homeAway + '-' + game['id']).text(totalScore);
	}
}

function winnerCSS(game) {
	if(game['winnerID'] == game['home']['id']) {
		var homeAway = 'home';
	} else {
		var homeAway = 'away';
	}
	$('#logoCell-' + homeAway + '-' + game['id']).addClass('winner-' + game[homeAway]['id']);
	$('#rank-' + homeAway + '-' + game['id']).addClass('winner-' + game[homeAway]['id']);
	$('#teamName-' + homeAway + '-' + game['id']).addClass('winner-' + game[homeAway]['id']);
	$('#schoolLink-' + homeAway + '-' + game['id']).addClass('winner-' + game[homeAway]['id']);
	$('#record-' + homeAway + '-' + game['id']).addClass('winner-' + game[homeAway]['id']);
	$('#lineScore-' + homeAway + '-1-' + game['id']).addClass('winner-' + game[homeAway]['id']);
	$('#lineScore-' + homeAway + '-2-' + game['id']).addClass('winner-' + game[homeAway]['id']);
	$('#lineScore-' + homeAway + '-3-' + game['id']).addClass('winner-' + game[homeAway]['id']);
	$('#lineScore-' + homeAway + '-4-' + game['id']).addClass('winner-' + game[homeAway]['id']);
	$('#lineScore-' + homeAway + '-5-' + game['id']).addClass('winner-' + game[homeAway]['id']);
	$('#total-' + homeAway + '-' + game['id']).addClass('winner-' + game[homeAway]['id']);
	$('#others-' + homeAway + '-' + game['id']).addClass('winner-' + game[homeAway]['id']);
}

function updateHeader(game) {
	var statusHeader = '#header-status-' + game['id'];
	if(game['completed']) {
		$(statusHeader).text('Final');
	}
	if(game['statusID'] == 1) {
		$(statusHeader).text(formattedDateTime(game['date']));
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
}

function updateGameStatus(game) {
	var gameStatusCell = '#gameStatus-' + game['id'];
	if([2, 22].includes(game['statusID'])) {
		if(game['possession'] == game['home']['id']) {
			var hasBall = 'home';
			var notBall = 'away';
		} else {
			var hasBall = 'away';
			var notBall = 'home';
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
			gameStatus = 'Kickoff ' + game[hasBall]['abbr'];
		}
		$(gameStatusCell).text(gameStatus);

		if((hasBall == 'home' && game['yardLine'] >= 80) || (hasBall == 'away' && game['yardLine'] <= 20)) {
			$(hasBallNameCell).addClass('redzone');
		} else {
			$(hasBallNameCell).removeClass('redzone');
		}
	} else {
		$(gameStatusCell).text('');
		$('#teamName-home-' + game['id']).removeClass(['possession', 'redzone']);
		$('#teamName-away-' + game['id']).removeClass(['possession', 'redzone']);
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
					var othersAway = '#others-away-' + game['id'];
					var othersHome = '#others-home-' + game['id'];
					var score = '#score-' + game['id'];

					// Update game linescores
					gameLineScores(game, 'home');
					gameLineScores(game, 'away');

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
						compareID = '#compare-' + pick['gameID'];
						compareScore = '#compareScore-' + pick['gameID'];
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