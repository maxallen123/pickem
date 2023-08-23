$(document).ready(function() {
	$('#submit').on('click', function() {
		var success = 1;

		// Check E-mail address
		if(!validateEmail($('#emailAddress').val()) || $('#emailAddress').val() == '') {
			success = 0;
			$('#emailReject').removeClass('d-none');
		} else if(!$('#emailReject').hasClass('d-none')) {
			$('#emailReject').addClass('d-none');
		}

		// Check password isn't blank
		if($('#password1').val() == '') {
			success = 0;
			$('#passwordReject').removeClass('d-none');
		} else if(!$('#passwordRject').hasClass('d-none')) {
			$('#passwordReject').addClass('d-none');
		}

		// Check passwords match
		if($('#password1').val() != $('#password2').val()) {
			success = 0;
			$('#password2Reject').removeClass('d-none');
		} else if (!$('password2Reject').hasClass('d-none')) {
			$('#password2Reject').addClass('d-none');
		}

		// Check name isn't blank
		if($('#name').val() == '') {
			success = 0;
			$('#nameReject').removeClass('d-none');
		} else if(!$('#nameReject').hasClass('d-none')) {
			$('#nameReject').addClass('d-none');
		}

		// Check that a team was picked
		if($('#favTeam').val() == -1) {
			success = 0;
			$('#teamReject').removeClass('d-none');
		} else if(!$('#teamReject').hasClass('d-none')) {
			$('#teamReject').addClass('d-none');
		}

		if($('#favTeam').val() == 238) {
			success = 0;
			$('#teamReject').text('NO. Pick a real team.');
			$('#teamReject').removeClass('d-none');
		} else if(!$('#teamReject').hasClass('d-none')) {
			$('#teamReject').addClass('d-none');
		}

		// If we passed all the tests, send to AJAX
		if(success == 1) {
			$.ajax({
				method: "POST",
				url: "./ajax/newUser.php",
				data: {
					email: $('#emailAddress').val(),
					password: $('#password1').val(),
					name: $('#name').val(),
					team: $('#favTeam').val()
				},
				datatype: 'json',
				success:
					function (result) {
						if(result != 'success') {
							$('#failedServer').text(result);
							$('#failedServer').removeClass('d-none');
						} else {
							window.location.href = "/";
						}
					}
			});
		}
	});
});