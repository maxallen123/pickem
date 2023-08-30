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
					var statusHeader = '#header-status-' + game['id'];
					var selectID = '#pick-' + game['id'];
					var othersAway = '#others-away-' + game['id'];
					var othersHome = '#others-home-' + game['id'];
					var score = '#score-' + game['id'];

					// Update game linescores
					var totalScore = 0;
					var otScore = 0;
					var ot = 0;
					var gameStarted = 0;
					$.each(game['home']['lineScores'], function(period, periodScore) {
						gameStarted = 1;
						totalScore += periodScore;
						if(period <= 4) {
							var boxScore = '#lineScore-home-' + period + '-' + game['id'];
							$(boxScore).text(periodScore);
						} else {
							ot++;
							otScore += periodScore;
						}
					});
					if(ot > 0) {
						$('#otScore-' + game['id']).text('OT(' + ot + ')');
						$('#lineScore-home-5-' + game['id']).text(otScore);
					}
					if(gameStarted) {
						$('#total-home-' + game['id']).text(totalScore);
					}

					var totalScore = 0;
					var otScore = 0;
					var ot = 0;
					$.each(game['away']['lineScores'], function(period, periodScore) {
						totalScore += periodScore;
						if(period <= 4) {
							var boxScore = '#lineScore-away-' + period + '-' + game['id'];
							$(boxScore).text(periodScore);
						} else {
							ot++;
							otScore += periodScore;
						}
					});
					if(ot > 0) {
						$('#lineScore-away-5-' + game['id']).text(otScore);
					}
					if(gameStarted) {
						$('#total-away-' + game['id']).text(totalScore);
					}
					
					// Apply CSS if game is over
					if(game['completed']) {
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
						$('#spread-' + homeAway + '-' + game['id']).addClass('winner-' + game[homeAway]['id']);
						$('#others-' + homeAway + '-' + game['id']).addClass('winner-' + game[homeAway]['id']);
					}

					// Update Time/Finished fields
					if(game['completed']) {
						$(statusHeader).text('Final');
						if(game['pick'] != null) {
							if(game['pick'] == game['winnerID'] || (game['jokeGame'] == 1 && game['pick'] != -1)) {
								$(score).text(ourScore += game['multiplier']);
								$(score).addClass('scoreWinner');
							} else {
								$(score).text(ourScore);
								$(score).removeClass('scoreWinner');
							}
						}
					} else {
						$(statusHeader).text(formattedDateTime(date));
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
									$(compareScore).text(curScore += pick['multiplier']);
									$(compareScore).addClass('scoreWinner')
								} else {
									$(compareScore).text(curScore);
									$(compareScore).removeClass('scoreWinner');
								}
							}
						} else {
							$(compareScore).text('');
							$(compareScore).removeClass('scoreWinner');
						}
					});
				}
		});
	}
}