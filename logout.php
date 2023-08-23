<?php
require('functions.php');
session_start();
session_destroy();
sqlConnect();
header('Location: ' . $GLOBALS['baseURL']);
?>