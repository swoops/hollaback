<?php
include "/opt/hollaback/api.php";
header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
$_SESSION = array();
session_destroy();
