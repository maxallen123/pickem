<?php

require('variables.php');
require('functions/emailFunctions.php');
require('functions/htmlFunctions.php');
require('functions/loadFunctions.php');
require('functions/fetchFunctions.php');
require('functions/queriesFunctions.php');
require('functions/insertUpdateFunctions.php');
require('objects.php');

function sqlConnect() {
	$connectionOptions = array(
		"Database" => $GLOBALS['sqlDB'],
		"UID" => $GLOBALS['sqlUser'],
		"PWD" => $GLOBALS['sqlPwd'],
		"Encrypt" => 1,
		"TrustServerCertificate" => 1,
		"APP" => $GLOBALS['sqlDB']
	);
	$conn = sqlsrv_connect($GLOBALS['sqlAddr'], $connectionOptions);
	if( $conn === false ) {
		echo "Could not connect.\n";
		die( print_r( sqlsrv_errors(), true));
	}

	loadGlobals($conn);
	return $conn;
}

function httpRequestOpts() {
	$opts = [
		"http" => [
			"header" => "Authorization: Bearer ".$GLOBALS['token']
		]
	];
	return stream_context_create($opts);
}

function countryName($code) {
	switch($code) {
		case 'IE':
			return 'Ireland';
		case 'BS':
			return 'Bahamas';
		case 'AU':
			return 'Australia';
	}
}

function getOrdinal($number) {
	$suffix = array('th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th');
	if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
		$ordinal = $number . 'th';
	} else {
		$ordinal = $number . $suffix[$number % 10];
	}

	return $ordinal;
}

function logoInlineEmail($user) {
	$color = $user->altColor;
	list($r, $g, $b) = sscanf($color, '%02x%02x%02x');

	/* RGB of your inside color */
	$rgb = array($r, $g, $b);
	/* Your file */
	$file="logo.png";

	/* Negative values, don't edit */
	$rgb = array(255-$rgb[0],255-$rgb[1],255-$rgb[2]);

	$im = imagecreatefrompng($file);

	imagefilter($im, IMG_FILTER_NEGATE); 
	imagefilter($im, IMG_FILTER_COLORIZE, $rgb[0], $rgb[1], $rgb[2]); 
	imagefilter($im, IMG_FILTER_NEGATE); 
	
	imagealphablending( $im, false );
	imagesavealpha( $im, true );
	
	ob_start();
	imagepng($im);
	$image = ob_get_contents();
	ob_end_clean();
	$base64 = 'data:image/png;base64,' . base64_encode($image);
	return $base64;
	
}

function base64ImageResize($b64Img, $height) {
	$imgRaw = base64_decode(substr($b64Img, 22));
	$img = imagecreatefromstring($imgRaw);
	$x = imagesx($img);
	$y = imagesy($img);
	$newX = round($x * ($height / $y));
	$img = imagescale($img, $newX, $height, IMG_SINC);
	imagealphablending($img, true);
	imagesavealpha($img, true);
	ob_start();
	imagepng($img);
	$imgRaw = ob_get_contents();
	ob_end_clean();
	$b64Img = 'data:image/png;base64,' . base64_encode($imgRaw);
	return $b64Img;
}
?>