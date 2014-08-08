<?php

header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
header('Access-Control-Allow-Credentials: true');

// Set proper Content-Type and charset
header('Content-Type: application/json; charset=utf-8');

require 'vendor/autoload.php';
require 'rb.php';

// create a connection string from the PG database URL and then use it to connect
// https://gist.github.com/kogcyc/7879293

$url=parse_url(getenv("DATABASE_URL"));

$host = $url["host"];
$port = $url["port"];
$user = $url["user"];
$password = $url["pass"];
$dbname = substr($url["path"],1);

$connect_string = "pgsql:host='" . $host . "' ";
$connect_string = $connect_string . "port=" . $port . " ";
$connect_string = $connect_string . "user='" . $user . "' ";
$connect_string = $connect_string . "password='" . $password . "' ";
$connect_string = $connect_string . "dbname='" . $dbname . "' ";

R::setup($connect_string);

$app = new \Slim\Slim();

$app->get('/', function(){

	echo json_encode(
		array(
			"status" => "ok",
			"message" => "Hello World!"
		)
	);

});

$app->run();

?>
