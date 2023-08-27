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
				var ourScore = $('#userPreweekScore').val();
				$.each(picks, function(index, game) {
					var date = new Date(game['date']['date'] + 'Z');
					var curDate = new Date();
					var statusHeader = '#header-status-' + game['id'];
					var selectID = '#pick-' + game['id'];
					var othersAway = '#others-away-' + game['id'];
					var othersHome = '#others-home-' + game['id'];
					var score = '#score-' + game['id'];

					// Update Time/Finished fields
					if(game['completed'] == 1) {
						$(statusHeader).text('Final');
						if(game['pick'] == game['winnerID']) {
							$(score).text(++ourScore);
							$(score).addClass('scoreWinner');
						} else {
							$(score).text(ourScore);
							$(score).removeClass('scoreWinner');
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
					if(pick['winnerID'] != null) {
						if(pick['winnerID'] == pick['pickID']) {
							$(compareScore).text(++curScore);
							$(compareScore).addClass('scoreWinner')
						} else {
							$(compareScore).text(curScore);
							$(compareScore).removeClass('scoreWinner');
						}
					}
				});
			}
	});
}