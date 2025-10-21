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
$shouldAskForEmail = true;    //forgot to update variable after renaming, fixed 
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
</header>
<main>

<!-- Deliverable 1: Search feature w/ details/browse -->
<!-- going to try to sort into this chronological format for delivs. (not gonna be perfect) -->

<section id="deliverable-1">
<div style="text-align:center; margin-bottom:20px;">
<div style="display:inline-flex; align-items:center; gap:8px;">

<input
  id="gameSearch"
  type="text"
  placeholder="Search for a game (e.g. Elden Ring)"
style="width:700px; max-width:90%; background:#1a1d24; color:#fff;
             border:1px solid #2a2f38; border-radius:8px; padding:10px;" />

<button id="btnSearch" type="button">Search</button>
</div>
</div>

<!-- Results + Notes + Loading (used stock loading icon found online) -->
<div id="results" class="results-grid"></div>
<div id="note" class="center-note"></div>
<div id="loadingSpinner" style="display:none;text-align:center;margin-top:20px;">
<img src="https://i.imgur.com/llF5iyg.gif" width="60" alt="Loading...">
</div> 


<script> //put this here to prevent blocker 
window._reviewsQueue = window._reviewsQueue || [];
if (typeof window.loadReviews !== "function") {
window.loadReviews = function(name) { window._reviewsQueue.push(name); };
}
</script>

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
        say('Please enter a game name.');
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

function toCards(items) { //used sum resources for formatting this, may look weird 
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
          
   <div class="game-info"> ${g.rating}<br> ${g.released}</div> 
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

//gon add the modal for details later (Update: added)

const html = `
  <div
   id="detailsModal"
   class="modal show"
   onclick="if (event.target.id === 'detailsModal') { document.getElementById('detailsModal').remove(); }" >
    <div class="modal-content">
    <button
        onclick="document.getElementById('detailsModal').remove();"
        style="position:absolute; top:10px; right:14px; background:none; border:none; color:#ccc; font-size:22px; cursor:pointer;"
        aria-label="Close details"
      >×</button>
<h2 style="margin-top:0; font-size:1.25rem; letter-spacing:.2px;">
      ${g.name}  </h2>
       <img
        src="${g.background_image}"
        alt="${g.name}"
        style="width:100%; max-height:400px; object-fit:cover; border-radius:6px; margin:10px 0;">
     <p><strong>Released:</strong> ${g.released || 'N/A'}</p>
     <p><strong>Platforms:</strong> ${platforms}</p>
     <p><strong>System Requirements:</strong> ${requirements}</p>

      <p style="margin-top:10px; line-height:1.5;">
        ${g.description_raw || 'No description available.'}
      </p>
    </div>
  </div>
`;
document.body.insertAdjacentHTML('beforeend', html);
} catch (err) {
  console.error("details error:", err);
  spinnerEl.style.display = 'none';
  say('Error loading details.', true);
}
}
</script>
</section>

<!-- Deliverable 2: Review + Rating System -->
<section id="deliverable-2">
<div id="reviewModal" class="modal">
<div class="modal-content">
 <h2 id="modalGameTitle"> Rate / Review</h2>
       <label for="ratingSelect" style="color:#e9edf1;">Rating</label>
        <select id="ratingSelect">
          <option value="1">1 - Overwhelmingly Negative</option>
          <option value="2">2 - Negative</option>
          <option value="3">3 - Decent</option>
          <option value="4">4 - Great</option>
          <option value="5">5 - Excellent</option>
        </select>
        <textarea id="reviewText" placeholder="Keep it helpful and short." style="width:100%; height:90px; border-radius:6px; background:#151a21; color:#e9edf1; border:1px solid #2b3341; padding:8px;"></textarea>
        <div class= "modal-options" style="margin-top:12px; display:flex; gap:10px; justify-content:flex-end;">
          <button class="btn small" onclick="closeReviewModal()">Cancel</button>
          <button class="btn small" onclick="submitReview()">Submit</button>
        </div>
      </div>
    </div>

 <!-- Success -->
 <div id="successPopup" class="modal">
 <div class="modal-content" style="text-align:center;">
 <h2>Saved</h2>
 <p>Thank you. Your review has been published! </p>
<button class="btn small" onclick="closePopup()">OK</button>
</div>
</div>
<!-- Error -->
<div id="errorPopup" class="modal">
 <div class="modal-content" style="text-align:center;">
 <h2 style="color:#ff7171;">Error</h2>
 <p id="errorPopupMessage">Something went wrong. Please attempt again!</p>
 <button class="btn small" onclick="closeErrorPopup()">OK</button>
      </div>
    </div>
 <!-- Notice -->
    <div id="reviewNotice" class="notice-box">
    <p id="noticeMessage" style="margin: 0 0 10px 0;"></p>
    <div style="display:flex; justify-content:flex-end;">
    <button id="closeNotice" class="btn small">OK</button>
    </div>
    </div>

<script>
let currentGame = "";

function rateGame(gameName) {
currentGame = gameName;
document.getElementById("modalGameTitle").innerText = `Rate / Review: ${gameName}`;
document.getElementById("reviewModal").classList.add("show");
      }
function closeReviewModal() {
document.getElementById("reviewModal").classList.remove("show");
      }
async function submitReview() {
const rating   = document.getElementById("ratingSelect").value;
const review   = document.getElementById("reviewText").value.trim();
const username = "<?= htmlspecialchars($username) ?>";

if (!rating || !review) {
   showErrorPopup("Please select a rating and enter a review.");
          return;
        }
try {
  const res = await fetch("rabbit_search.php", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({
  type: "submit_review",
  username,
  game_name: currentGame,
  rating,
  comment: review
            })
          });
const data = await res.json();
if (data?.success) {
showPopup();
closeReviewModal();
loadReviews(currentGame);
} else if (String(data?.message || '').includes("already rated")) {
closeReviewModal();
showNotice("Looks like you already rated this game!");
 } else {
   showErrorPopup(data?.message || "Error submitting review.");
          }
  } catch (err) {
console.error("submitReview error:", err);
showErrorPopup("Error submiting review. Please try again.");
        }
      }

async function loadReviews(gameName) {
const el = document.getElementById(`reviews-${gameName.replace(/\s+/g, '_')}`);

if (!el) return;
el.innerHTML = "<em>Loading reviews..Just a moment.</em>";

try {
const res = await fetch("rabbit_search.php", {
method: "POST",
headers: { "Content-Type": "application/json" },
body: JSON.stringify({ type: "get_reviews", game_name: gameName })
});

const data = await res.json();
if (data?.success && Array.isArray(data.results) && data.results.length) {
el.innerHTML = data.results.map(r => {
const mine = r.username === "<?= htmlspecialchars($username) ?>";
return `

<div class="review-card">
<strong>${r.username}</strong> — ${r.rating}/5<br>
"${r.review}"<br>

<small style="color:#8b93a2;">${r.created_at}</small>
${mine ? `<div style="margin-top:6px;"><button class="btn small" data-del="1" data-game="${gameName}">Delete My Review</button></div>` : ""}
</div>
`;

}).join("");
el.querySelectorAll('[data-del="1"]').forEach(btn => {
btn.addEventListener('click', async () => {

const game = btn.dataset.game;
try {
const delRes = await fetch("rabbit_search.php", {
method: "POST",
headers: { "Content-Type": "application/json" },
body: JSON.stringify({ type: "delete_review", username: "<?= htmlspecialchars($username) ?>", game_name: game })
});
const out = await delRes.json();
	
if (out?.success) {
showNotice("Your review has been successfully deleted.");
loadReviews(game);
}
 else {
showNotice(out?.message || "We could not delete review.");
}
} catch (e) {
showNotice("Error deleting review.");
}
});
});
} else {
el.innerHTML = "<em>No reviews yet.</em>";
}
} catch (err) {
console.error("loadReviews error:", err);
el.innerHTML = "<em>Error loading your reviews.</em>";
}
}
(function () {
  if (Array.isArray(window._reviewsQueue) && window._reviewsQueue.length) {
    const q = window._reviewsQueue.slice();
    window._reviewsQueue.length = 0;
    q.forEach(n => loadReviews(n));
  }
})();
function showPopup() {
document.getElementById("successPopup").classList.add("show");
}
function closePopup() {
document.getElementById("successPopup").classList.remove("show"); }
function showErrorPopup(message) {
document.getElementById("errorPopupMessage").innerText = message;
document.getElementById("errorPopup").classList.add("show");
}
function closeErrorPopup() {
document.getElementById("errorPopup").classList.remove("show")  }

function showNotice(message) {

const box = document.getElementById('reviewNotice');
const text = document.getElementById('noticeMessage');

text.textContent = message;
box.style.display = 'block';
}
document.getElementById('closeNotice').onclick = () => {
document.getElementById('reviewNotice').style.display = 'none';
};

</script>
</section>

<!-- Deliverable 3: Recommendation System -->

<!-- not confirmed but may do 2 game rec options: based on filtering + user rating/reviews -->

 <section id="deliverable-3">
    <!-- The only button to start the flow -->
    <div style="text-align:center; margin-top:10px;">
      <button id="startRecBtn" class="cool-btn">Recommend Me a Game</button>
    </div>
</section>

<!-- Surprise Me button -->
<div style="text-align:center; margin-top:10px;">
  <button id="surpriseMeBtn" class="cool-btn">Surprise me. I want a recommendation</button>
</div>
<script>
async function fetchPersonalizedRecommendations() {
resetGrid();
spinnerEl.style.display = 'block';
say('Looking for a personalized game juist for you...');
try {
const res = await fetch('rabbit_search.php', {
method: 'POST',
headers: {'Content-Type':'application/json'},
body: JSON.stringify({
type: 'personalized_recommendations',
username: "<?= htmlspecialchars($username) ?>"
      })
    });

const data = await res.json();
spinnerEl.style.display = 'none';

if (!data || data.success !== true) {
return say(data?.message || 'Looks lke no personalized results found.', true);
}

if (data.note) {
  say(data.note, false);
} else {
  noteEl.textContent = '';
}
const raw   = Array.isArray(data.results) ? data.results.slice(0, 5) : [];
const cards = toCards(raw);
if (!cards.length) return say('No result looks to be found.');
drawCards(cards);
} catch (e) {
console.error(e);
spinnerEl.style.display = 'none';
say('Error fetching personalized recommendations.', true);
}
}
document.getElementById('surpriseMeBtn').addEventListener('click', fetchPersonalizedRecommendations);

</script>
</section>
<!-- Deliverable 4: Watchlist System -->
<section id="deliverable-4">
<div style="text-align:center; margin-top:10px;">
<button id="viewWatchlistBtn" class="cool-btn">View My List</button>
</div>

<!-- script for adding to list - ip -->
<script> 
     async function addToMyList(gameId, gameName) {
     const username = "<?= htmlspecialchars($username) ?>";
     try {
     const res = await fetch("rabbit_search.php", {
     method: "POST",
     headers: { "Content-Type": "application/json" },
     body: JSON.stringify({
     type: "add_watchlist",
     username,
     game_id: gameId,
     game_name: gameName
            })
          });
     const data = await res.json();
     if (data?.success) {
            showNotice(`Added: ${gameName}`);
          } else {
            showNotice(data?.message || "Could not add game.");
          }
  } catch (err) {
   console.error("addToMyList error:", err);
   showNotice("Server error while adding game.");
        }
      }

  async function fetchWatchlist() {
  const username = "<?= htmlspecialchars($username) ?>";
  try {
  const res = await fetch("rabbit_search.php", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({
 type: "get_watchlist", username })
          });
  const data = await res.json();
  if (!data?.success || !Array.isArray(data.results) || !data.results.length) {
  showNotice("Your list is empty.");
            return;
          }


const html = data.results.map(g => `
<div style="background:#1e222b; padding:10px; margin:10px; border-radius:6px;">
<strong>${g.game_name}</strong><br>
<small>Added on ${g.added_at || g.last_updated || 'N/A'}</small>
</div>
`).join('');



 const modal = `
 <div id="watchlistModal" class="modal show" onclick="if(event.target.id==='watchlistModal'){document.getElementById('watchlistModal').remove();}">
 <div class="modal-content" style="max-width:650px;">
 <button onclick="document.getElementById('watchlistModal').remove();"
 style="position:absolute; top:10px; right:14px; background:none; border:none; color:#ccc; font-size:22px; cursor:pointer;">×</button>
 <h2 style="margin:0 0 8px 0;">Your Game List</h2>
  ${html}
 </div>
</div>
          `;
document.body.insertAdjacentHTML('beforeend', modal);
        } catch (err) {
console.error("fetchWatchlist error:", err);
showNotice("Error loading your list.");
        }
      }
 document.getElementById("viewWatchlistBtn").addEventListener("click", fetchWatchlist);
   
// need to add email prompt modal

<div id = "emailPrompt" class ="modal">
<div class = "modal-content" style = "text-align: center; 

 </script>
  </section>
<section id="email-prompt">
<div id = "emailPrompt" class="modal">
<div class="modal-content" style="text-align:center;">
<button onclick="closeEmailModal()" style="position:absolute; top:10px; right:14px; background:none; border:none; color:#ccc; font-size:22px; cursor:pointer;">×</button>
<h2>Add Your Email</h2>
<p>Do you want to get  updates &  notifiations?</p>
<input id="emailInput" type="email" placeholder="you@example.com"
</div>
</div>
</div>
<script>
function showEmailPrompt() {
document.getElementById("emailPrompt").classList.add("show");
      }
function closeEmailModal() {
document.getElementById("emailPrompt").classList.remove("show");
      }
</script>
</section>

<section id="deliverable-5">
<script> //some setup vars, no funct. yet

const NOTIF_USERNAME = "<?= htmlspecialchars($username) ?>";
const notifBellBtn= document.getElementById('openNotifBtn')
const notifListEl= document.getElementById('notifList');
const notifBadge = document.getElementById('notifBadge');
const notifEmpty= document.getElementById('notifEmpty');
const markAllBtn = document.getElementById('markAllBtn');
const notifPanel = document.getElementById('notifPanel');

let notifCache = [];

notifBellBtn.addEventListener('click', async () => {
const isHidden = notifPanel.style.display === 'none' || !notifPanel.style.display;

if (isHidden) {
await loadNotifs();
notifPanel.style.display = 'block';
 } 
else
 {
notifPanel.style.display = 'none';
 }
      });

async function loadNotifs() {
 try {
const res  = await fetch("rabbit_search.php", {
method: "POST",
headers: {"Content-Type":"application/json"},
body: JSON.stringify({ type: "get_notifications", username: NOTIF_USERNAME })
          });
const data = await res.json();
if (!data?.success) {
drawNotifs([]);
return;
          }
notifCache = Array.isArray(data.notifications) ? data.notifications : [];
drawNotifs(notifCache);
updateBadge(notifCache);
        } 
catch (err) {
console.error("loadNotifications error:", err);
drawNotifs([]);
        }
      }
//gona make some functions to render + update notifs, will add logic* later	

function drawNotifs(list) {
}
function updateBadge(list) {
}


</script>
</section>




<!-- Deliverable 6: Message Board System -->
<script> //looking into existing models atm
</main>
<footer>MATS: GameHub © <?= date('Y') ?> | Using  RAWG API</footer>
</body>
</html>
