#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
//moved code over from db_listener.php!

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


//getPersonalizedRecommendation is the acutal deliverable for recommendations
function getPersonalizedRecommendations($username){
	$mysqli= new mysqli("localhost", "authuser", "StrongPassword123!", "testdb");
	if($mysqli->connect_errno) return['success'=> false,'message'=> 'DB connection failed'];

	$stmt= $mysqli->prepare("SELECT game_name, rating FROM reviews WHERE username =?");
	 if(!$stmt){$mysqli->close();
	return['success'=> false, 'message' =>'Query prep had failed'];}
	$stmt->bind_param("s", $username);
	$stmt->execute();
	$result = $stmt->get_result();

	$liked= [];
	$liked_soft=[];
	$disliked =[];

	while($row = $result->fetch_assoc()){
	$r= (int)$row["rating"];
	if($r >= 4) $liked[] = $row['game_name'];
	else if($r == 3) $liked_soft[] = $row['game_name'];
	else if($r <= 2) $disliked[] = $row['game_name']; }

	$stmt->close();
	$mysqli->close();


	$apiKey = 'e92e94964e714d64aa425f8c11d0996e';

	if(empty($liked) && empty($liked_soft) && empty($disliked)){
	$url = "https://api.rawg.io/api/games?key=$apiKey&ordering=-rating&page_size=5";
	$json= @file_get_contents($url);
	 if(!$json)return ['success' => false, 'message' => "failled to reach api"];
	$data = json_decode($json, true);
	return ['success' => true, 'message' => 'unable find reviews/you possess none. lock in more reviews to get picks', 'results' => ($data['results'] ?? [])];}

	$genreWeights = [];
	$platformWeights = [];
	$dislikedGenres= [];
	$dislikedPlatforms= [];
	$seenGameIds= [];
	$seedNames= array_merge($liked, $liked_soft, $disliked);

//get some games from api based on like seedNames
	foreach($seedNames as $name){
	$q= urlencode($name);
	$searchUrl = "https://api.rawg.io/api/games?key=$apiKey&search=$q&page_size=1";
	$sresp= @file_get_contents($searchUrl);
	 if(!$sresp) continue;
	$sdata = json_decode($sresp, true);
	$hit = $sdata['results'][0] ?? null;
	 if(!$hit || empty($hit['id'])) continue;
	$id = $hit['id'];

	$detailsUrl ="https://api.rawg.io/api/games/$id?key=$apiKey";
	$dresp= @file_get_contents($detailsUrl);
	 if(!$dresp) continue;
	$det= json_decode($dresp, true);

	$genres = is_array($det['genres'] ?? null) ? $det['genres'] : [];
	$plats= is_array($det['platforms'] ?? null) ? $det['platforms'] : [];



	 if(in_array($name, $liked, true)){
	foreach($genres as $g){ $slug = $g['slug'] ?? ''; if ($slug) $genreWeights[$slug] = ($genreWeights[$slug] ?? 0) + 2; }
	foreach($plats as $p) { $ps= $p['platform']['slug'] ?? ''; if($ps) $platformWeights[$ps]= ($platformWeights[$ps] ?? 0) + 2; }
	} else
	 if(in_array($name, $liked_soft, true)) {
	foreach($genres as $g) { $slug = $g['slug'] ?? ''; if ($slug) $genreWeights[$slug]= ($genreWeights[$slug] ?? 0) + 1; }
	foreach($plats as $p){ $ps   = $p['platform']['slug'] ?? ''; if ($ps) $platformWeights[$ps] = ($platformWeights[$ps] ?? 0) + 1; }
	} else{
	foreach($genres as $g){ $slug = $g['slug'] ?? ''; if ($slug) $dislikedGenres[$slug] = true;}
	foreach($plats as $p) { $ps= $p['platform']['slug'] ?? ''; if($ps) $dislikedPlatforms[$ps] = true;}}

//get the games that are positive only
	if(in_array($name,$liked, true) || in_array($name, $liked_soft, true)){
	$suggUrl = "https://api.rawg.io/api/games/$id/suggested?key=$apiKey&page_size=15";
	$sg= @file_get_contents($suggUrl);
	 if($sg){ $sgd= json_decode($sg, true);
	  foreach(($sgd['results']?? []) as $g){ if(!empty($g['id'])) $seenGameIds[$g['id']] = $g;}}}}

//same as above but now its te genre

	arsort($genreWeights);
	arsort($platformWeights);

	if (!empty($genreWeights)){
	$topGenreSlice = array_slice(array_keys($genreWeights), 0, 3);
	$genresParam= implode(',', $topGenreSlice);
	$discUrl= "https://api.rawg.io/api/games?key=$apiKey&genres=$genresParam&ordering=-rating&page_size=40";
	$dj= @file_get_contents($discUrl);
	 if($dj){$dd= json_decode($dj, true);
	  foreach(($dd['results'] ?? []) as $g){
		if(!empty($g['id'])) $seenGameIds[$g['id']] = $g;}  }   }



	$candidates = array_values($seenGameIds);
//filter out the disliked games/genre
	$filtered = [];
	foreach($candidates as $g){ if(empty($g['id'])) continue;
	$gGenres= [];
	 foreach (($g['genres'] ?? []) as $gg) { if (!empty($gg['slug'])) $gGenres[$gg['slug']] = true; }

	$gPlatSlugs = [];
	 foreach(($g['platforms'] ?? [])as $pp){$pls = $pp['platform']['slug']?? ''; 
	  if($pls) $gPlatSlugs[$pls] = true;}

	$avoid =false;
	 foreach($gGenres as $s=> $_){if(isset($dislikedGenres[$s])){$avoid=true; break;}}
	 if($avoid) continue;
	 foreach($gPlatSlugs as $ps=> $_){if(isset($dislikedPlatforms[$ps])){ $avoid= true; break;}}
	 if($avoid) continue;
	$filtered[]= $g;}



//if we dont have enough games, bring in the popular games while avoiding disliked games/genres
	if(count($filtered) < 5){ $fill = "https://api.rawg.io/api/games?key=$apiKey&ordering=-rating&page_size=20";
	$fj = @file_get_contents($fill);
	 if ($fj){
	$fd= json_decode($fj, true);
	  foreach (($fd['results'] ?? []) as $g){
		if (empty($g['id'])) continue;

	$gGenres= [];
	   foreach (($g['genres'] ?? []) as $gg){if (!empty($gg['slug'])) $gGenres[$gg['slug']] = true;}

	 $gPlatSlugs = [];
	  foreach (($g['platforms'] ?? []) as $pp){ $pls = $pp['platform']['slug'] ?? '';
	if($pls) $gPlatSlugs[$pls] = true;}

	$avoid = false;
	  foreach ($gGenres as $s => $_) {if(isset($dislikedGenres[$s])) { $avoid= true; break;}}
	 if($avoid) continue;
	 foreach ($gPlatSlugs as $ps=> $_) {if(isset($dislikedPlatforms[$ps])){ $avoid = true; break;}}
	if($avoid) continue;

	$filtered[] = $g;
	 if (count($filtered) >= 12) break;}}}


	$genreBuckets= [];
	foreach($filtered as $g){
	$firstGenre =$g['genres'][0]['slug']?? 'unknown';
	 if(!isset($genreBuckets[$firstGenre])) $genreBuckets[$firstGenre] = [];
	$genreBuckets[$firstGenre][] =$g;}

	$final = [];
	$round = 0;
	while (count($final) < 5 && $round < 20){
	 foreach($genreBuckets as $slug => $list){
	  if (!count($list)) continue;
	$pick= array_shift($genreBuckets[$slug]);

	$dupe =false;
	 foreach($final as $f){ if(!empty($f['id']) && $f['id'] === $pick['id']){ $dupe= true; break;} }
	 if($dupe) continue;

	$final[] =$pick;
	 if(count($final) >= 5) break;}

	$round++;}


//if its still not  enough, pad it out
	if(count($final) < 5){ foreach($filtered as $g){ $dupe = false;
	  foreach($final as $f){ if(!empty($f['id']) && $g['id'] === $f['id']){ $dupe = true; break;}}
	if($dupe) continue;

	$final[]=$g;
	if(count($final) >= 5) break;}}

	return['success'=> true, 'results' => $final];

}


//end fetch

function requestProcessor($request){
	if (!isset($request['type'])) return ['success'=>false,'message'=>'Invalid request'];

	switch ($request['type']){
	case 'get_details':
		return getGameDetails($request['game_id']);
	case 'get_recommendations':
		return getGameRecommendations($request['query'] ?? null);
	case 'personalized_recommendations':
		return getPersonalizedRecommendations($request['username'] ?? 'Guest');
	default:
		return ['success'=>false,'message'=>'Not handled by DMZ'];
    }
}



echo "DMZ Listener Starting...\n";
$server = new rabbitMQServer("testRabbitMQ.ini", "dmzServer");

echo "Connected to broker: {$server->BROKER_HOST}\n";
echo "Listening on queue: dmz_queue\n";
echo "Waiting for API requests...\n";

$server->process_requests(function ($request) {
	$response = requestProcessor($request);
	echo "Response:\n";
	var_dump($response);
	return $response;});
?>
