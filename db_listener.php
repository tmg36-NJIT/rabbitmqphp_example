#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function createSession($user_id) {
	$mysqli = new mysqli("localhost", "authuser", "StrongPassword123!", "testdb");
	if($mysqli->connect_errno){
	return false;}

	$sessionKey =bin2hex(random_bytes(16));
	$stmt = $mysqli->prepare("INSERT INTO sessions (user_id, session_key, expires_at) VALUES(?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
	if(!$stmt) {
	$mysqli->close();
	return false;}

	$stmt->bind_param("is", $user_id, $sessionKey);
	$stmt->execute();
	$stmt->close();
	$mysqli->close();
	return $sessionKey;
}

function doLoginDB($username, $password) {
	$mysqli = new mysqli("localhost", "authuser", "StrongPassword123!", "testdb");
	if($mysqli->connect_errno) {return['success' => false, 'message' => 'the database connection failed']; }
	$stmt = $mysqli->prepare("SELECT * FROM users WHERE username=? AND password=?");
	if(!$stmt) {
	$mysqli->close();
	return ['success' => false, 'message' => 'query prep failed']; }

	$stmt->bind_param("ss", $username, $password);
	$stmt ->execute();
	$result = $stmt->get_result();
	$row = $result->fetch_assoc();
	$stmt->close();
	$mysqli->close();
	 if($row) { $sessionKey = createSession($row['id']);
		if($sessionKey) {
			return [
				'success' =>true,
				'message' =>"Login successful", 
				'session_key' =>$sessionKey ];
		} else {return ['success' => true, 'message' => "Login successful (no key)"];}
	} else { return ['success' => false, 'message' => "Invalid username and/or password"];}
}

function doRegisterDB($username, $password){
	$mysqli = new mysqli("localhost", "authuser", "StrongPassword123!", "testdb");
	if($mysqli->connect_errno) {return['success' => false, 'message' => 'the database connection failed'];}
	try {
	$stmt = $mysqli->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
	if(!$stmt) {
	$mysqli->close();
	return ['success' => false, 'message' => 'query prep failed']; }


	$stmt->bind_param("ss", $username, $password);
	$stmt->execute();
	$stmt->close();
	$mysqli->close();
	return ['success' => true, 'message' => "User Registered!"];}
	catch(mysqli_sql_exception $e) {
	 if (str_contains($e->getMessage(), 'Duplicate entry')) { return ['success' => false, 'message' => "Username already exists"];}
	return ['success' => false, 'message' => "Registration error: " . $e->getMessage()]; }


}



function updateUserEmail($username, $email){
	$mysqli = new mysqli("localhost", "authuser", "StrongPassword123!", "testdb");
	 if($mysqli->connect_errno)return ['success' => false, 'message' => 'DB connection failed']; 
	
	$stmt=$mysqli->preapre("UPDATE users SET email=? WHERE username=?");
	if(!$stmt){$mysqli->close();
	  return['success' => false, 'message'=> 'Query failed'];}
	
	$stmt->bind_param("ss", $email, $username);
	$stmt->execute();
	$affected=$stmt->affected_rows;
	$stmt->close();
	$mysqli->close();

	if($affected >0){return['success' => true, 'message'=> 'Your email is updated!'];
	}else {return ['success' => false, 'message'=> 'cant find someone with that username'];}
}



function checkUserEmail($username){
	 $mysqli = new mysqli("localhost", "authuser", "StrongPassword123!", "testdb");
	     if($mysqli->connect_errno)return ['success' => false, 'has_email' => false, 'message' => 'DC connection failed'];
	
	$stmt= $mysqli->prepare("SELECT email FROM USERS WHERE username=?");
	if(!$stmt){ $mysqli->close();
	      return['success' => false, 'has_email' => flase, 'message'=> 'Query failed'];
	
	$stmt->bind_param("s", $username);
	$stmt->execute();
	$result= $smtmt->get_result();
	$email='';
	if($row= $result->fetch_assoc()){$email= $row['email']?? '';}

	$stmt->close()
	$mysqli->close();
	$hasemail= !empty($email);
	  return9'success'=> true, 'has_email'=>$hasEmail, 'email' => $email];
}





function getGameRecommendations($query = null) {
	$apiKey='e92e94964e714d64aa425f8c11d0996e';
	$baseUrl = 'https://api.rawg.io/api/games?key=' . $apiKey;
	if ($query){ $baseUrl .= '&search=' . urlencode($query);}
	
	$response= @file_get_contents($baseUrl);
	if($response==false){return ['success'  => false, 'message' => 'unable to reach the rawg api'];}
	$data= json_decode($response, true);
	return ['success' => true, 'results' => $data['results'] ?? []];
}

function saveUserSearch($username, $query){
	 $mysqli = new mysqli("localhost", "authuser", "StrongPassword123!", "testdb");
	if($mysqli->connect_errno){return false; };

	$stmt = $mysqli->prepare("INSERT INTO user_searches (username, search_query, searched_at) VALUES (?, ?, NOW())");
		if(!$stmt){$mysqli->close();
		return false;}

	 $stmt->bind_params("is", $username, $query);
	$stmt->execute();
	$stmt->close();
	$mysqli->close();
	return true;}




function submitReview($username, $game_name, $rating, $review){ 
	$mysqli = new mysqli("localhost", "authuser", "StrongPassword123!", "testdb");
	if($mysqli->connect_errno){return['success' =>false, 'message'=> 'database connection failed']; }

	$stmt= $mysqli->prepare("INSERT INTO reviews(username, game_name, rating, review) VALUES(?, ?, ?, ?)");
	if(!$stmt){ $mysqli->close();
	 return['success'=> false, 'message' => 'Query prep failed'];}

	$stmt->bind_param('ssis', $username, $game_name, $rating, $review);
	$stmt->execute();
	$stmt->close();
	$mysqli->close();
	return['success' =>true, 'message'=> 'Your review was submitted successfully!'];
}


function getReviews($game_name){ $mysqli = new mysqli("localhost", "authuser", "StrongPassword123!", "testdb");
	if ($mysqli->connect_errno) {return['success' => false, 'message' => 'connection failed'];}
	 $stmt = $mysqli->prepare("SELECT username, rating, review, created_at from reviews WHERE game_name = ? ORDER BY created_at DESC");
	if(!$stmt){$mysqli->close();
		return['success'=> false,'message' => 'Query prep failed'];}

	$stmt->bind_param("s", $game_name);
	$stmt->execute();
	$result=$stmt->get_result();
	$reviews=[];
	 while($row =$result->fetch_assoc()){$reviews[] = $row;}
	$stmt->close();
	$mysqli->close();
	return['success' => true, 'results' => $reviews]; }





function requestProcessor($request) {
	echo "Received request:";
	 var_dump($request);
        if(!isset($request['type'])){ return['success' => false, 'message' => 'Invalid request'];}


	switch($request['type']){
		case 'login':
		 return doLoginDB($request['username'], $request['password']);
		case 'register':
		 return doRegisterDB($request['username'], $request['password']);
		case'update_email':
		  return updateUserEmail($request['username'], $request['email']);
		case 'check_email':
		   return checkUserEmail($request['username']);

		case 'get_recommendations':
		 $query= $request['query'] ??null;
		$username= $request['username']?? 'Guest';
		if ($query){saveUserSearch($username, $query);}
		 return getGameRecommendations($query);

		case 'submit_review':
		 $username= $request['username']??'Guest';
		 $reviewText=$request['review']?? ($request['comment'] ?? '');
		return submitReview($username,$request['game_name'], $request['rating'], $reviewText);

		case 'get_reviews':
		return getReviews($request['game_name']);
		default:
		 return['success' => false, 'message' => 'invalid request'];}
}




echo "Database Listener starting\n";
$server = new rabbitMQServer("testRabbitMQ.ini", "testServer");
echo "connected to broker: ". $server->BROKER_HOST. "\n";
echo "database listener started\n";

$server->process_requests(function($request) {
	 $response = requestProcessor($request);
	echo "Response: \n";
	var_dump($response);
	return $response; } );


?>
