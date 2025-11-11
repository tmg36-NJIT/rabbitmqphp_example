<?php
require '/var/www/html/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

ini_set('display_errors',1);
error_reporting(E_ALL);
date_default_timezone_set('UTC');

$time=date('Y-m-d H:i:s');
$logFile = '/var/www/html/game_watchlist_cron.log';

//get email usr and pass from .env file
$env = parse_ini_file('/var/www/html/security.env');
$emailUser = $env['EMAIL_USER'] ?? '';
$emailPass = $env['EMAIL_PASS'] ?? '';

$conn=new mysqli("localhost","authuser","StrongPassword123!","testdb");
if($conn->connect_errno){file_put_contents($logFile, "[$time] Connection error: ".$conn->connect_error."\n", FILE_APPEND);
exit;}

//api url+key
$rawgKey= 'e92e94964e714d64aa425f8c11d0996e';
$rawgUrl= 'https://api.rawg.io/api/games?key=' . $rawgKey;
$cheapsharkUrl= 'https://www.cheapshark.com/api/1.0/games?title=';



$query= "SELECT id, username, game_name, last_known_price, last_release_date, last_updated FROM watchlist";
$result= $conn->query($query);

if (!$result){ file_put_contents($logFile, "[$time] Query failed: " . $conn->error . "\n", FILE_APPEND);
	$conn->close();
	exit;}



$userChanges= [];
 while ($row= $result->fetch_assoc()){
	$gameName= urlencode($row['game_name']);
	 $changes= [];
	$newReleaseDetected= false;
	$patchUpdateDetected= false;
	$genreString= "";

	$rawgResponse= @file_get_contents("$rawgUrl&search=$gameName");
	$currentRelease= null;
	$currentUpdated= null;
	$currentVersion= null;
	
	//rawg for game data
	if($rawgResponse!== FALSE){$data= json_decode($rawgResponse, true);
	$game= $data['results'][0] ?? null;

	 if($game){
	$currentRelease= $game['released'] ?? null;
	$currentUpdated= $game['updated'] ?? null;
	$genres= array_map(fn($g) => $g['name'], $game['genres'] ?? []);
	$genreString= implode(', ', $genres);

	//new game
	 if(($row['last_release_date'] === null || $row['last_release_date'] === '') && $currentRelease){ $changes[] = "New game has released! Release date: $currentRelease; Genres: $genreString";
	$newReleaseDetected = true;}
	//patches
	 if($row['last_updated'] != $currentUpdated && $currentUpdated){$patchUpdateDetected= true;
	$changes[]= "New patch is available! Updated on $currentUpdated" . ($currentVersion ? "Version: $currentVersion" : "");
	 }
	 }
	}

	//cheapshark for price data
	$cheapResponse= @file_get_contents($cheapsharkUrl . $gameName);
	$currentPrice= null;

	if($cheapResponse!== FALSE){ $priceData= json_decode($cheapResponse, true);
	  if(!empty($priceData[0]['cheapest'])){ $currentPrice= floatval($priceData[0]['cheapest']);
	  if($row['last_known_price'] !== null && $currentPrice < floatval($row['last_known_price'])){ $changes[]= "Price has dropped from $".$row['last_known_price']." to $$currentPrice!";
	   } elseif($row['last_known_price'] === null){$changes[] = "The current price is $$currentPrice";}
	 }
	}



	//recording changes for user
	if(!empty($changes)){ $username= $row['username'];
	 if(!isset($userChanges[$username])){$userChanges[$username]= [];}
	$userChanges[$username][]= ['game_name' => $row['game_name'], 'release_date'=> $currentRelease, 'genres'=> $genreString, 'price'=> $currentPrice, 
	 'changes'=> $changes, 'new_release'=> $newReleaseDetected, 'patch_update'=> $patchUpdateDetected];


	//adding notifications into the notification table
	 foreach($changes as $c){ if(trim($c)=== '') continue;
	$msg= $row['game_name'] . ' — ' . $c;
	$stmt= $conn->prepare("INSERT IGNORE INTO notifications (username, message, is_read) VALUES (?, ?, 0)");
	$stmt->bind_param("ss", $username, $msg);
	$stmt->execute();
	$stmt->close();}

        //update watchlist
	$conn->query("UPDATE watchlist SET last_release_date = " . ($currentRelease ? "'$currentRelease'" : "NULL") . ",last_updated = " . ($currentUpdated ? "'$currentUpdated'" : "NULL") . 
	 ", last_known_price = " . ($currentPrice ? "'$currentPrice'" : "NULL") . ", last_checked = NOW() WHERE id = {$row['id']}");
	} else{$conn->query("UPDATE watchlist SET last_checked = NOW() WHERE id = {$row['id']}");}
	//avoding api rate limits
	usleep(300000);

}

//send email
foreach($userChanges as $username=> $games){
	//get usr  email
	$emailQuery= "SELECT email FROM users WHERE username='$username' LIMIT 1";
	$emailResult= $conn->query($emailQuery);
	$emailRow = $emailResult ? $emailResult->fetch_assoc() : null;
	$email = $emailRow['email'] ?? null;

	if(!$email){ file_put_contents($logFile, "[$time] No email for $username — notifications saved only\n", FILE_APPEND);
	 continue;}

	$bodyLines= [];
	foreach($games as $g){
	 $bodyLines[] = "Game: {$g['game_name']}";
	$bodyLines[]= "Release Date: " . ($g['release_date'] ?? "Unknown");
	$bodyLines[] = "Genres: " . ($g['genres'] ?: "Unknown");
	$bodyLines[]= "Current Price: " . ($g['price'] !== null ? "$".$g['price'] : "Unknown");
	$bodyLines[]= "Changes Detected:";

	 foreach($g['changes'] as $c){ $bodyLines[] = "- $c";}
	$bodyLines[]= "";}

	$subject = "Updates for Your Watchlist";
	$body= "Hello $username,\n\n" . implode("\n", $bodyLines) . "\n— Game Tracker Bot\n";


	$mail= new PHPMailer(true);
	try{ 
	$mail->isSMTP();
	$mail->Host        = 'smtp.gmail.com';
	$mail->SMTPAuth    = true;
	$mail->Username    = $emailUser;
	$mail->Password    = $emailPass;
	$mail->SMTPSecure  = 'tls';
	$mail->Port        = 587;

	$mail->setFrom($emailUser, 'Game Tracker Bot');
	$mail->addAddress($email, $username);

	$mail->isHTML(false);
	$mail->Subject = $subject;
	$mail->Body    = $body;

        $mail->send();
        file_put_contents($logFile, "[$time] Email has been sent to $email for user $username\n", FILE_APPEND);
    }catch (Exception $e) {file_put_contents($logFile, "[$time] Failed to send email to $email. Error: {$mail->ErrorInfo}\n", FILE_APPEND);}

}


$conn->close();
file_put_contents($logFile, "[$time] Watchlist cron (RAWG + CheapShark) completed with notifications.\n", FILE_APPEND);

?>
