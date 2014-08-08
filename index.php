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

$app->post('/endpoint', function(){

	// MASTER_KEY_SHA1 should be a SHA1 string and set in ENV vars
	// It is used to manage endpoints

	if( sha1($_POST['auth']) === getenv("MASTER_KEY_SHA1") ){

		$endpoint = R::findOne('endpoint', ' name = ? ', [ $_POST['name'] ]);

		$action = '';

		if(empty($endpoint)){

			// Adding new endpoint

			$action = 'add';
			$endpoint = R::dispense('endpoint');
			$endpoint['name'] = $_POST['name'];

		}else{

			// Updating existing endpoint

			$action = 'updat';
			$endpoint = R::load('endpoint', $endpoint['id']);

		}

		$endpoint['key'] = $_POST['key'];

		R::store( $endpoint );

		echo json_encode(
			array(
				"result" => "ok",
				"message" => "Endpoint ".$action."ed sucessfully!"
			)
		);

	}else{

		// Wrong key

		header("HTTP/1.1 403 Forbidden");

	}

});

$app->run();

?>
