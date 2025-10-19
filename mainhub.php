// matshub.php was the prev. file (on git), switching to this file since prev. had some issues.
// got to deliv 3/4 currently.
// going to reformat code structure in this vers. for better readablity (sorted by deliverables)

<?php
//  SESSION SETUP & EMAIL CHECK (Auth + Email presence) - some setp for deliv. 5

session_start();
require_once("rabbitMQLib.inc"); 
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$email = $_SESSION['email'] ?? '';
$showEmailPrompt = false;
if (empty($email)) {
    try {
 $client = new rabbitMQClient("testRabbitMQ.ini", "testServer");
    $response = $client->send_request([
    "type" => "check_email",
    "username" => $username
 ]);
 if (isset($response['has_email']) && $response['has_email'] === false) {
$showEmailPrompt = true;
        } elseif (!empty($response['email'])) {
$_SESSION['email'] = $response['email'];
        }
    } catch (Exception $e) {
        error_log("Email check error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
 <link rel="stylesheet" href="style.css">
  <meta charset="UTF-8" />
  <title>MATS: GameHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
</head>

<body>
//     HEADER: Title, User, Logout, Notification Bell
<header>
  <h1>
<a href="matshub.php" style="text-decoration:none; color:inherit;">  MATS: GameHub  </a>
  </h1>
  <div class="user-tag">Welcome, <?= htmlspecialchars($username) ?>!</div>
  <form action="logout.php" method="post" style="display:inline;">
    <button type="submit" class="logout-btn">Logout</button>
  </form>
<footer>MATS: GameHub © <?= date('Y') ?> | Using  RAWG API</footer>

<main>
 <!-  Deliverable 1: Browse/Search (HTML + JS together) --> //going to try to sort into this chronological format for delivs. (not gonna be perfect)
<section id="deliverable-1">
<!-- UI stuff  -->
<div style="text-align:center; margin-bottom:20px;">
<div style="display:inline-flex; align-items:center; gap:8px;">
<input id="gameSearch" type="text"
placeholder="Search for a game (e.g. Elden Ring)
style="width:700px; max-width:90%; background:#1a1d24; color:#fff
border:1px solid #2a2f38; border-radius:8px; padding:10px;" />
<button id="btnSearch" type="button">Search</button>
</div>
</div>
<!-- Results + Notes + Loading --> //used stock loading icon found online 
<div id="results" class="results-grid"></div>
<div id="note" class="center-note"></div>
<div id="loadingSpinner" style="display:none;text-align:center;margin-top:20px;">
<img src="https://i.imgur.com/llF5iyg.gif" width="60" alt="Loading...">
</div> 

<script>
// === D1: Shared DOM for this section
const resultsEl = document.getElementById('results');
const noteEl = document.getElementById('note');
const spinnerEl = document.getElementById('loadingSpinner');
const placeholder = 'https://via.placeholder.com/250x150?text=No+Image';


