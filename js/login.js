$(document).ready(function() {
	$('#emailAddress').keypress(function(e){
		if(e.keyCode==13)
		$('#submit').click();
	});

	$('#password1').keypress(function(e){
		if(e.keyCode==13)
		$('#submit').click();
	});

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

		if(success == 1) {
			$.ajax({
				method: "POST",
				url: "./ajax/login.php",
				data: {
					email: $('#emailAddress').val(),
					password: $('#password1').val()
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