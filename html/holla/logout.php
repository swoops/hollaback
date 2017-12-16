<?php
header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
session_start();
$_SESSION = array();
session_destroy();
