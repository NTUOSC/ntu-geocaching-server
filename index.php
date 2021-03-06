<?php

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

// Set proper Content-Type and charset

$app->response->headers->set('Access-Control-Allow-Origin', '*');
$app->response->headers->set('Content-Type', 'application/json; charset=utf-8');

$app->get('/', function(){

	$endpoints = R::findAll('endpoint');

	$result = array();

	foreach($endpoints as $endpoint){
		$count = R::count('visit', ' eid = ? ', [ $endpoint['id'] ] );
		$result[] = array(
			'id' => $endpoint['id'],
			'name' => $endpoint['name'],
			'count' => $count
		);
	}

	echo json_encode(
		array(
			"status" => "ok",
			"data" => $result
		)
	);

});

$app->get('/endpoint/:name', function($name) use($app) {

	// Show endpoint statistics

	$endpoint = R::findOne('endpoint', ' name = ? ', [$name]);

	if(!empty($endpoint)){

		$count = R::count('visit', ' eid = ? ', [ $endpoint['id'] ]);

		echo json_encode(
			array(
				"result" => "ok",
				"count" => $count
			)
		);

	}else{

		$message = json_encode(
			array(
				"result" => "error",
				"message" => "Not Found"
			)
		);

		$app->halt(404, $message);

	}
});

$app->post('/endpoint/:name', function($name) use($app) {

	$endpoint = R::findOne('endpoint', ' name = ? ', [ $name ]);

	if( !( isset($_POST['auth']) && isset($_POST['cuid']) ) ){

		// Missing parameters

		$message = json_encode(
			array(
				"result" => "error",
				"message" => "Missing Parameters"
			)
		);

		$app->halt(400, $message);

	}else if(empty($endpoint)){

		// Invalid endpoint name

		$message =  json_encode(
			array(
				"result" => "error",
				"message" => "Invalid Endpoint"
			)
		);

		$app->halt(400, $message);

	}else if( sha1($_POST['auth']) != $endpoint['key'] ){

		// Invalid endpoint key

		$message = json_encode(
			array(
				"result" => "error",
				"message" => "Invalid Key"
			)
		);

		$app->halt(403, $message);

	}else{

		$user = R::findOne('user', ' cuid = ? ', [ $_POST['cuid'] ]);
		$uid = 0;
		$is_registered = 0;
		$can_redeem = 0;

		if(empty($user)){

			// Newcoming user for system, first endpoint touched, add to DB

			$user = R::dispense('user');
			$user['cuid'] = $_POST['cuid'];
			$user['ctime'] = R::isoDateTime();
			$uid = R::store($user);

		}else{

			// Existing user

			$uid = $user['id'];

			if($user['data'] != ""){
				// User has data
				$is_registered = 1;
			}

		}

		$visit = R::findOne('visit', ' uid = ? AND eid = ? ', [ $uid, $endpoint['id'] ] );
		$count = R::count('visit', ' uid = ? ', [ $uid ]);

		if(empty($visit)){

			// Newcoming user for endpoint

			$visit = R::dispense('visit');
			$visit['uid'] = $uid;
			$visit['eid'] = $endpoint['id'];
			$visit['ctime'] = R::isoDateTime();
			$vid = R::store($visit);

			$count++;

			if($vid != 0){
				echo json_encode(
					array(
						"result" => "ok",
						"message" => "User successfully visited endpoint!",
						"count" => $count,
						"is_registered" => $is_registered,
						"can_redeem" => $count >= getenv("REDEEM_REQ") ? 1 : 0
					)
				);
			}else{
				$message =  json_encode(
					array(
						"result" => "error",
						"message" => "Database error occured!"
					)
				);
				$app->halt(500, $message);
			}

		}else{

			// Returning user for endpoint

			echo json_encode(
				array(
					"result" => "notice",
					"message" => "User came before!",
					"count" => $count,
					"is_registered" => $is_registered,
					"can_redeem" => $count >= getenv("REDEEM_REQ") ? 1 : 0
				)
			);

		}

	}

});

$app->post('/endpoint', function() use($app) {

	// MASTER_KEY_SHA1 should be a SHA1 string and set in ENV vars
	// It is used to manage endpoints

	if( sha1($_POST['auth']) == getenv("MASTER_KEY_SHA1") ){

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

		$endpoint['key'] = sha1($_POST['key']);

		$id = R::store( $endpoint );

		if($id != 0){
			echo json_encode(
				array(
					"result" => "ok",
					"message" => "Endpoint ".$action."ed sucessfully!"
				)
			);
		}else{
			$message = json_encode(
				array(
					"result" => "error",
					"message" => "Database error occured!"
				)
			);
			$app->halt(500, $message);
		}

	}else{

		// Wrong key

		$message = json_encode(
			array(
				"result" => "error",
				"message" => "Invalid Key"
			)
		);

		$app->halt(403, $message);

	}

});

$app->post('/user', function(){

	if( isset($_POST['cuid']) && isset($_POST['auth']) && isset($_POST['data']) ){

		$auth_success = false;
		$auth_identidy = "";

		if( sha1($_POST['auth']) == getenv("MASTER_KEY_SHA1") ){
			$auth_success = true;
			$auth_identity = 0;
		}else if( isset($_POST['name']) ){
			$endpoint = R::findOne('endpoint', ' key = ? AND name = ? ', [ $_POST['auth'], $_POST['name'] ]);
			if(!empty($endpoint)){
				$auth_success = true;
				$auth_identity = $endpoint['id'];
			}
		}

		if($auth_success === true){

			$user = R::findOne('user', ' cuid = ? ', [ $_POST['cuid'] ]);

			if(!empty($user)){

				// User is in db
				$user = R::load('user', $user['id']);

			}else{

				// User is not in db
				$user = R::dispense('user');
				$user['cuid'] = $_POST['cuid'];
				$user['ctime'] = R::isoDateTime();

			}

			$user['mtime'] = R::isoDateTime();
			$user['data'] = $_POST['data'];
			$user['data_by'] = $auth_identity;

			R::store($user);

			echo json_encode(
				array(
					"result" => "ok",
					"message" => "Data added successfully!"
				)
			);

		}else{

			$message = json_encode(
				array(
					"result" => "error",
					"message" => "Invalid Key"
				)
			);

			$app->halt(403, $message);

		}

	}else{

		$message = json_encode(
			array(
				"result" => "error",
				"message" => "Missing Parameters"
			)
		);

		$app->halt(400, $message);

	}

});

$app->get('/user', function(){

	$users = R::findAll('user');
	$users_data = array();

	foreach($users as $user){
		$users_data[] = array(
			"id" => $user['id'],
			"cuid" => $user['cuid']
		);
	}

	echo json_encode(
		array(
			"result" => "ok",
			"data" => $users_data
		)
	);

});

$app->get('/user/:cuid', function($cuid){

	$user = R::findOne('user', ' cuid = ? ', [ $cuid ]);

	if(empty($user)){

		$message = json_encode(
			array(
				"result" => "error",
				"message" => "User not found!"
			)
		);

		$app->halt(404, $message);

	}else{

		$visits = R::findAll('visit', ' uid = ? ', [ $user['id'] ] );
		$visited_endpoints = array();

		foreach($visits as $visit){
			$endpoint = R::findOne('endpoint', ' id = ? ', [ $visit['eid'] ] );
			$visited_endpoints[] = array(
				"name" => $endpoint['name'],
				"ctime" => $visit['ctime']
			);
		}

		echo json_encode(
			array(
				"result" => "ok",
				"count" => sizeof($visits),
				"is_registered" => $user['data'] == '' ? 0 : 1,
				"can_redeem" => sizeof($visits) >= getenv("REDEEM_REQ") ? 1 : 0,
				"visited" => $visited_endpoints
			)
		);

	}

});

$app->post('/redeem', function() use($app) {
	if( isset($_POST['cuid']) && isset($_POST['auth']) ){

		$auth_success = false;
		$auth_identidy = "";

		if( sha1($_POST['auth']) == getenv("MASTER_KEY_SHA1") ){
			$auth_success = true;
			$auth_identity = 0;
		}else if( isset($_POST['name']) ){
			$endpoint = R::findOne('endpoint', ' key = ? AND name = ? ', [ sha1($_POST['auth']), $_POST['name'] ]);
			if(!empty($endpoint)){
				$auth_success = true;
				$auth_identity = $endpoint['id'];
			}
		}

		if($auth_success === true){

			$user = R::findOne('user', ' cuid = ? ', [ $_POST['cuid'] ]);

			if(empty($user)){

				$message = json_encode(
					array(
						"result" => "error",
						"message" => "User does not exist"
					)
				);
				$app->halt(400, $message);

			}else if($user['redeem'] == 1){

				// Already redeemed

				$message = json_encode(
					array(
						"result" => "error",
						"message" => "Already redeemed"
					)
				);
				$app->halt(400, $message);

			}else if($user['data'] == ''){

				$message = json_encode(
					array(
						"result" => "error",
						"message" => "Data not provided yet"
					)
				);
				$app->halt(400, $message);

			}else if(	getenv("REDEEM_REQ") > R::count('visit', ' uid = ? ', [ $user['id'] ] )){

				$message = json_encode(
					array(
						"result" => "error",
						"message" => "User did not reach goal yet"
					)
				);
				$app->halt(400, $message);

			}else{

				// User is in db
				$user = R::load('user', $user['id']);

			}

			$user['rtime'] = R::isoDateTime();
			$user['redeem'] = 1;
			$user['redeem_by'] = $auth_identity;

			R::store($user);

			echo json_encode(
				array(
					"result" => "ok",
					"message" => "Redeemed successfully!"
				)
			);

		}else{

			$message = json_encode(
				array(
					"result" => "error",
					"message" => "Invalid Key"
				)
			);

			$app->halt(403, $message);

		}

	}else{

		$message = json_encode(
			array(
				"result" => "error",
				"message" => "Missing Parameters"
			)
		);

		$app->halt(400, $message);

	}
});

$app->run();

?>
