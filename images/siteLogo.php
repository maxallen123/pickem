<?php

header('Content-Type: image/png');
require('../functions.php');

if(isset($_GET['teamID'])) {
	$dbConn = sqlConnect();
	$query = 'SELECT alternateColor FROM teams WHERE id = ?';
	$queryArray = array($_GET['teamID']);
	$color = sqlsrv_fetch_array(sqlsrv_query($dbConn, $query, $queryArray))['alternateColor'];

	list($r, $g, $b) = sscanf($color, '%02x%02x%02x');
	$rgb = array($r, $g, $b);
	$file="../logo.png";
	$rgb = array(255-$rgb[0],255-$rgb[1],255-$rgb[2]);

	$im = imagecreatefrompng($file);

	imagefilter($im, IMG_FILTER_NEGATE); 
	imagefilter($im, IMG_FILTER_COLORIZE, $rgb[0], $rgb[1], $rgb[2]); 
	imagefilter($im, IMG_FILTER_NEGATE); 

	if(isset($_GET['height'])) {
		$oldWidth = imagesx($im);
		$oldHeight = imagesy($im);
		$newWidth = round($oldWidth * ($_GET['height'] / $oldHeight));
		$im = imagescale($im, $newWidth, $_GET['height'], IMG_SINC);
	}

	imagealphablending( $im, true );
	imagesavealpha( $im, true );
	imagepng($im);
	imagedestroy($im);
}