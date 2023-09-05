<?php
include('Mail.php');
require('functions.php');

$dbConn = sqlConnect();
$oldWeek = getCurWeek($dbConn);
$GLOBALS['graceOffset'] = 1;
$newWeek = getCurWeek($dbConn);
$users = getUsers($dbConn, $oldWeek);

$params = array();
$params['host'] = $GLOBALS['smtpHost'];
$params['port'] = $GLOBALS['smtpPort'];
$params['auth'] = true;
$params['username'] = $GLOBALS['smtpUser'];
$params['password'] = $GLOBALS['smtpPass'];
$mail = Mail::factory('smtp', $params);

$recipients = 'maxallen1234@gmail.com';
$headers['From'] = $GLOBALS['smtpFrom'];
$headers['Subject'] = 'Weekly Update for Week ' . $oldWeek->week;
$headers['MIME-Version'] = '1.0';
$headers['Content-Type'] = 'text/html; charset=UTF-8';

$body  = '<p style="color: white; text-align: center">Week ' . $oldWeek->week . ' is now in the books! The current standings:</p>';
$body .= mailScoreboard($dbConn, $users, $oldWeek);
$body .= '<br><p style="color: white; text-align: center">Games for Week ' . $newWeek->week . ':</p>';
$body .= mailWeeksGames($dbConn, $newWeek, $oldWeek);
$body .= '</td></tr></table>';

foreach($users as $user) {
	$bodyUser = mailHeader($user) . $body;
	$bodyUser = mailBodyStart() . $bodyUser;
	$recipients = $user->email;
	$headers['To'] = $user->name . ' <' . $user->email . '>';
	if($user->email == 'brad.allen@amanomcgann.com') {
		//$mail->send($recipients, $headers, $body);
	}
}

echo $bodyUser;
