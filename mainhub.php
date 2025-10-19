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
 <!-- HEADER: Title, User, Logout, Notification Bell -->
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
// D1: Shared DOM for this section
const resultsEl = document.getElementById('results');
const noteEl = document.getElementById('note');
const spinnerEl = document.getElementById('loadingSpinner');
const placeholder = 'https://via.placeholder.com/250x150?text=No+Image';

 // Helpers (gon use these for other delivs. too (kept global)
function clearUI() { resultsEl.innerHTML = ''; noteEl.textContent = ''; }
function showNote(msg, isErr=false) { noteEl.textContent = msg; noteEl.className = 'center-note' + (isErr ? ' error' : ''); }
document.getElementById('btnSearch').addEventListener('click', fetchGames);

// For D1 (Deliverable 1) - function for search
async function fetchGames() {
const q = document.getElementById('gameSearch').value.trim();
clearUI();
   if (!q) return showNote('Enter a game name.');
  spinnerEl.style.display = 'block';
  showNote('Loading…');
    try {
  const res = await fetch(`rabbit_search.php?genre=${encodeURIComponent(q)}`);
  const data = await res.json();
 console.log("DEBUG:", data);
  renderGames(mapToCards(data.results || []));
      } catch (e) {
    console.error(e);
    showNote('Error fetching data.', true);
     spinnerEl.style.display = 'none';
      }
    }

 // Also D1: fop mappiong api cards
function mapToCards(items) {
if (!Array.isArray(items)) return [];
return items.map(g => ({id: g.id ?? 0, name: g.name ?? 'Unknown',
        rating: g.rating ?? 'N/A',released: g.released ?? 'N/A',
image: g.background_image ?? g.image ?? placeholder
      }));
    }
// D1 too --> for rendring the czards
function renderGames(games) {
resultsEl.innerHTML = '';
noteEl.textContent = '';
resultsEl.classList.remove('show');
void resultsEl.offsetWidth;
resultsEl.classList.add('show');

if (!games.length) return showNote('No results found.');

for (const g of games) {
  const reason = `Because you like ${window.answers?.genre || 'varied genres'} games on ${window.answers?.platform || 'your chosen platform'}.`;
    const card = document.createElement('div');
  card.className = 'game-card';

//add to my list button + view details button
 card.innerHTML = `
 <img src="${g.image}" alt="${g.name}">
 <h3>${g.name}</h3>
<div class="game-info"> ${g.rating}<br> ${g.released}</div>
<p style="font-size:0.8rem;color:#9aa0aa;margin-top:6px;">${reason}</p>
<button style="margin-top:8px;" onclick="viewDetails(${g.id})"> View Details</button>
<button style="margin-top:8px;" onclick="rateGame('${g.name.replace(/'/g,"\\'")}')"> Rate / Review</button>
<button style="margin-top:8px;" onclick="addToMyList(${g.id}, '${g.name.replace(/'/g,"\\'")}')">➕ Add to My List</button>
<div id="reviews-${g.name.replace(/\s+/g, '_')}" style="margin-top:10px;font-size:0.85rem;color:#ccc;"></div>
     `; resultsEl.appendChild(card);  //used specific online presets for formatting
// so far updated structure/formatting/styling + added tweaks like the email setup, etc.
// Going to implement my old functions like fetchReccomendations, etc a lil later since im trynna do a organize it by deliverable


