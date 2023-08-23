function validateEmail(email) {
	var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
	return emailReg.test(email);
}

function formattedDateTime(dateTime) {
	var months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
	var days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];


	var month = months[dateTime.getMonth()];
	var year = dateTime.getFullYear();
	var dow = days[dateTime.getDay()];
	var day = dateTime.getDate();
	var hour = dateTime.getHours();
	var min = dateTime.getMinutes();
	
	if(hour > 12) {
		hour -= 12;
		ampm = 'PM';
	} else {
		ampm = 'AM';
	}

	if(min < 10) {
		min = '0' + min;
	}

	return dow + ', ' + month + ' ' + day + ', ' + year + ' ' + hour + ':' + min + ' ' + ampm;
}