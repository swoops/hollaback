<?php
include "/opt/hollaback/api.php";
include "/opt/hollaback/payloads.php";

$ret = array(
    "Success" => True,
    "payloads" => $PAYLOADS
);
echo json_encode($ret);
