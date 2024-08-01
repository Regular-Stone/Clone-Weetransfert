<?php
$headers = apache_request_headers();
header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");

$result = file_get_contents('php://input');
$written = file_put_contents('cpu.png', $result, FILE_APPEND);
echo $written;
