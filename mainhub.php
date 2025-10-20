// matshub.php was the prev. file (on git), switching to this file since prev. had some issues.
// got to deliv 3/4 currently.
// going to reformat code structure in this vers. for better readablity (sorted by deliverables)

<?php
// SESSION SETUP & EMAIL CHECK (Auth + Email presence) - some setp for deliv. 5 

session_start();
require_once("rabbitMQLib.inc"); 
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$email = $_SESSION['email'] ?? '';

$shouldAskForEmail = false; //i changed ths too

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
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>MATS: GameHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="style.css">
</head>
<body>
 <!-- Title, User, Logout, Notification Bell -->
<header>
  <h1>
<a href="mainhub.php" style="text-decoration:none; color:inherit;">  MATS: GameHub  </a>
  </h1>
  <div class="user-tag">Welcome, <?= htmlspecialchars($username) ?>!</div>
  <form action="logout.php" method="post" style="display:inline;">
    <button type="submit" class="logout-btn">Logout</button>
  </form>
<footer>MATS: GameHub © <?= date('Y') ?> | Using  RAWG API</footer>

<main>
 <! -- Deliverable 1: Search feature w/ details/browse  -->  //going to try to sort into this chronological format for delivs. (not gonna be perfect)
<section id="deliverable-1">
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
// D1: Shared DOM for this section
const resultsEl = document.getElementById('results');
const noteEl = document.getElementById('note');
const spinnerEl = document.getElementById('loadingSpinner');
const placeholder = 'https://via.placeholder.com/250x150?text=No+Image';

function resetGrid() {   //i renamed function
        resultsEl.innerHTML = '';
        noteEl.textContent = '';
}
      function say(msg, isErr = false) {
        noteEl.textContent = msg;
        noteEl.className = 'center-note' + (isErr ? ' error' : '');
      }
document.getElementById('btnSearch').addEventListener('click', fetchGames);

async function fetchGames() {
const q = document.getElementById('gameSearch').value.trim();
        resetGrid();
        if (!q) {
        say('Enter a game name.');
        return;
        }
        spinnerEl.style.display = 'block';
        say('Loading...');
   try {
          const res  = await fetch(`rabbit_search.php?genre=${encodeURIComponent(q)}`);
        const data = await res.json();
        const list = Array.isArray(data?.results) ? data.results : [];
          drawCards(toCards(list));
        } catch (err) {
          console.error(err);
          say('Error fetching data.', true);
          spinnerEl.style.display = 'none';
        }
      }

function toCards(items) { //used sum resources for formatting this 
          return items.map(g => ({
          id:        g.id ?? 0,
          name:      g.name ?? 'Unknown',
          rating:    g.rating ?? 'N/A',
          released:  g.released ?? 'N/A',
          image:     g.background_image ?? g.image ?? placeholder
        }));
      } 

function drawCards(games) {
        resultsEl.innerHTML = '';
        noteEl.textContent  = '';
        resultsEl.classList.remove('show');
        void resultsEl.offsetWidth;
        resultsEl.classList.add('show');

        if (!games.length) {
          say('No results found.');
          spinnerEl.style.display = 'none';
          return;
        }
for (const g of games) {
          const card = document.createElement('div');
          card.className = 'game-card';

          const reason = `Suggested based on your picks.`;

          card.innerHTML = `
            <img src="${g.image}" alt="${g.name}">
            <h3>${g.name}</h3>
            <div class="game-info">⭐ ${g.rating}<br>🗓️ ${g.released}</div>
            <p style="font-size:.86rem; color:#9aa0aa; margin-top:6px;">${reason}</p>

            <button class="btn small" style="margin-top:8px;" onclick="viewDetails(${g.id})">View Details</button>
            <button class="btn small" style="margin-top:8px;" onclick="rateGame('${g.name.replace(/'/g,"\\'")}')">Rate / Review</button>
            <button class="btn small" style="margin-top:8px;" onclick="addToMyList(${g.id}, '${g.name.replace(/'/g,"\\'")}')">Add to My List</button>

            <div id="reviews-${g.name.replace(/\s+/g, '_')}" style="margin-top:10px; font-size:.9rem; color:#cfd6df;"></div>
          `;
          resultsEl.appendChild(card);

          // for loading  any reviews already saved for this title
          loadReviews(g.name);
        }
        spinnerEl.style.display = 'none';
      }
async function viewDetails(gameId) {
      try {
      spinnerEl.style.display = 'block';
      say('Loading details...');
          const res = await fetch("rabbit_search.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ type: "get_details", game_id: gameId }) // i first tested w/ game name but it failed, so switching to game_id since it get return api content smoother
          });
          const data = await res.json();
          spinnerEl.style.display = 'none';

       if (!data?.success) {
            say(data?.message || "No details found.", true);
            return;
          }

   const g = data.results;
   const platforms = g.platforms ? g.platforms.map(p => p.platform.name).join(', ') : 'Unknown';
   const requirements = g.platforms && g.platforms[0]?.requirements?.minimum
   ? g.platforms[0].requirements.minimum
   : 'N/A';

//gon add the modal for details later
</script> 
</section>

<! -- Deliverable 2: Review + Rating System  --> 
// same w/ this (from below)





<! -- Deliverable 3: Reccomendation System  -->
//alrdy have base code from matshub.php, gon upload extended ver. here





<!-- Deliverable 4: Watchlist System -->






<! -- Deliverable 5: Notification System






<! -- Deliverable 6: Messsage Board System -->


