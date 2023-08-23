<?php
header('Content-Type: image/png');
if(session_status() == PHP_SESSION_NONE) session_start();
include('functions.php');
sqlConnect();

if(isset($_SESSION['uid'])) {
	$color = $_SESSION['alternateColor'];
} else {
	$color = $GLOBALS['defaultAltColor'];
}

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
imagepng($im);
imagedestroy($im);
?>