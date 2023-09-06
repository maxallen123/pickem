<?php
header('Content-Type: image/png');
require('../functions.php');

if(isset($_GET['teamID'])) {
	$is_dark = 1;
	if(isset($_GET['light'])) {
		if($_GET['light'] == 1) {
			$is_dark = 0;
		}
	}

	$dbConn = sqlConnect();
	$query = 'SELECT img FROM teamLogos WHERE teamID = ? AND is_dark = ?';
	$queryArray = array($_GET['teamID'], $is_dark);
	$b64Img = sqlsrv_fetch_array(sqlsrv_query($dbConn, $query, $queryArray))['img'];
	$imgRaw = base64_decode(substr($b64Img, 22));

	$img = imagecreatefromstring($imgRaw);
	if(isset($_GET['height'])) {
		$oldWidth = imagesx($img);
		$oldHeight = imagesy($img);
		$newWidth = round($oldWidth * ($_GET['height'] / $oldHeight));
		$img = imagescale($img, $newWidth, $_GET['height'], IMG_SINC);
	}
	imagealphablending($img, true);
	imagesavealpha($img, true);
	imagepng($img);
}

?>