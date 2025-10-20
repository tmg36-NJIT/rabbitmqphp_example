#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

//USER AUTH
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
//USER AUTH END


//usr email
function updateUserEmail($username, $email){
	$mysqli = new mysqli("localhost", "authuser", "StrongPassword123!", "testdb");
	 if($mysqli->connect_errno)return ['success' => false, 'message' => 'DB connection failed']; 

	$stmt=$mysqli->prepare("UPDATE users SET email=? WHERE username=?");
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
	     if($mysqli->connect_errno)return ['success' => false, 'has_email' => false, 'message' => 'DB connection failed'];

	$stmt= $mysqli->prepare("SELECT email FROM users WHERE username=?");
	if(!$stmt){ $mysqli->close();
	      return['success' => false, 'has_email' => false, 'message'=> 'Query failed'];}

	$stmt->bind_param("s", $username);
	$stmt->execute();
	$result= $stmt->get_result();
	$email='';
	if($row= $result->fetch_assoc()){$email= $row['email']?? '';}

	$stmt->close();
	$mysqli->close();
	$hasEmail= !empty($email);
	  return['success'=> true, 'has_email'=>$hasEmail, 'email' => $email];
}

//end usr email





//notifications
function getNotifications($username){
	$mysqli= new mysqli("localhost", "authuser", "StrongPassword123!", "testdb");
	 if($mysqli->connect_errno) return['success' => false, 'message'=> 'DB connection failed'];

	$stmt = $mysqli->prepare("SELECT id, message, is_read, created_at FROM notifications WHERE username=? ORDER BY created_at DESC");
	if(!$stmt){ $mysqli->close();
	return ['success' => false, 'message' => 'Query prep failed'];}
	$stmt->bind_param("s", $username);
	$stmt->execute();
	$result = $stmt->get_result();

	$notifications = [];
	while ($row = $result->fetch_assoc()) $notifications[] = $row;

	$stmt->close();
	$mysqli->close();
	return ['success'=> true, 'notifications'=> $notifications];
}



function markNotificationRead($notification_id) {
	$mysqli= new mysqli("localhost", "authuser", "StrongPassword123!", "testdb");
	 if($mysqli->connect_errno) return ['success'=> false, 'message' => 'DB connection failed'];

	$stmt= $mysqli->prepare("UPDATE notifications SET is_read=1 WHERE id=?");
	 if(!$stmt){ $mysqli->close();
	return ['success'=> false, 'message'=> 'Query prep failed'];}

	$stmt->bind_param("i", $notification_id);
	$stmt->execute();
	$affected= $stmt->affected_rows;

	$stmt->close();
	$mysqli->close();
	return ['success'=> $affected > 0, 'message' => $affected > 0 ? 'Notification marked read' : 'Notification not found'];

}

//end notifications




//fetch api data
function getGameDetails($game_id){
	$apiKey= 'e92e94964e714d64aa425f8c11d0996e';
	$url= 'https://api.rawg.io/api/games/' . urlencode($game_id) . '?key=' . $apiKey;

	$response = @file_get_contents($url);
	if($response === false){ return ['success'=> false, 'message' => 'Error retrieving game details'];}

	$data= json_decode($response, true);
	if(!$data){ return ['success'=> false, 'message'=> 'Invalid API response'];}
	return ['success' => true, 'results' => $data];
}



function getGameRecommendations($query = null) {
	$apiKey='e92e94964e714d64aa425f8c11d0996e';
	$baseUrl = 'https://api.rawg.io/api/games?key=' . $apiKey;
	if ($query){ $baseUrl .= '&search=' . urlencode($query);}
	
	$response= @file_get_contents($baseUrl);
	if($response==false){return ['success'  => false, 'message' => 'Unable to reach the rawg api'];}
	$data= json_decode($response, true);
	return ['success' => true, 'results' => $data['results'] ?? []];
}

//end fetch

//cache user data
function saveUserSearch($username, $query){
	 $mysqli = new mysqli("localhost", "authuser", "StrongPassword123!", "testdb");
	if($mysqli->connect_errno){return false; };

	$stmt = $mysqli->prepare("INSERT INTO user_searches (username, search_query, searched_at) VALUES (?, ?, NOW())");
		if(!$stmt){$mysqli->close();
		return false;}

	 $stmt->bind_param("ss", $username, $query);
	$stmt->execute();
	$stmt->close();
	$mysqli->close();
	return true;}
//end save data


//user reviews
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


function deleteReview($username, $game_name){
	$mysqli = new mysqli("localhost", "authuser", "StrongPassword123!", "testdb");
	      if($mysqli->connect_errno)return ['success' => false, 'message' => 'Database connection failed'];

	$stmt = $mysqli->prepare("DELETE FROM reviews WHERE username=? AND game_name=?");
	if(!$stmt){$mysqli->close();
	 return ['success' => false, 'message' => 'Query prep failed'];}

	 $stmt->bind_param("ss", $username, $game_name);
	$stmt->execute();
	$affected= $stmt->affected_rows;
	  $stmt->close();
	 $mysqli->close();
	if($affected>0) return['success'=> true, 'message' => 'Review is deleted'];
	else return ['success'=> false, 'message' => 'cant find review to delete'];
}
//end user reviews



//watchlist for user
function addToWatchlist($username, $game_id, $game_name){
        $mysqli = new mysqli("localhost", "authuser", "StrongPassword123!", "testdb");
          if($mysqli->connect_errno)return ['success' => false, 'message' => 'Database connection failed'];

	try{$stmt = $mysqli->prepare("INSERT INTO watchlist (username, game_id, game_name, last_updated) VALUES (?, ?, ?, NOW())");
	 if(!$stmt){$mysqli->close();
	 return['success'=> false, 'message'=> 'Query prep failed'];}


	$stmt->bind_param("sis", $username, $game_id, $game_name);
	$stmt->execute();
	$stmt->close();
	$mysqli->close();
	return['success'=> true, 'message'=> 'The game is now in your watchlist'];

	}catch (mysqli_sql_exception $e){
	 if(str_contains($e->getMessage(), 'Duplicate entry')){return ['success'=> false, 'message'=> 'Game is already in your watchlist'];}
	return['success'=> false, 'message'=> 'Error adding to watchlist: ' . $e->getMessage()];}
}


function getWatchlist($username){
	$mysqli = new mysqli("localhost", "authuser", "StrongPassword123!", "testdb");
	 if($mysqli->connect_errno) return ['success'=> false, 'message'=> 'DB connection failed'];

	$stmt= $mysqli->prepare("SELECT game_name, game_id, last_updated FROM watchlist WHERE username=? ORDER BY last_updated DESC");
	 if(!$stmt){$mysqli->close();
        return ['success'=> false, 'message'=> 'Query prep failed'];}

	$stmt->bind_param("s", $username);
	 $stmt->execute();
	$result= $stmt->get_result();
	$games=[];
	while($row = $result->fetch_assoc()){ $games[]= $row;}

	$stmt->close();
	$mysqli->close();
	return ['success'=> true, 'results'=>$games];
}
//watchlist end



//requestProcessor

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

		case'get_notifications':
		 return getNotifications($request['username']);
		case 'mark_notification_read':
		 return markNotificationRead($request['notification_id']);

		case'get_details':
		 return getGameDetails($request['game_id']);

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
		case'delete_review':
		 return deleteReview($request['username'], $request['game_name']);

		case 'add_watchlist':
		 return addToWatchlist($request['username'], $request['game_id'], $request['game_name']);
		case 'get_watchlist':
		 return getWatchlist($request['username']);

		default:
		 return['success' => false, 'message' => 'invalid request'];}
}



//Listener
echo "Database Listener starting\n";
$server = new rabbitMQServer("testRabbitMQ.ini", "testServer");
echo "connected to broker: ". $server->BROKER_HOST. "\n";
echo "database listener started\n";

$server->process_requests(function($request) {
	 $response = requestProcessor($request);
	echo "Response: \n";
	var_dump($response);
	return $response;});


?>
