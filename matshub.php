// this is going to be the official landing page for the video game recommendation platform (MATS: GameHub)
<?php
session_start();
if(!isset($_SESSION['username'])){
  header("Location: login.php");
  exit;
}
$username=$_SESSION['username'];
$email=$_SESSION['email'] ?? '';?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>MATS: GameHub</title>

<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="style.css">
</head>
<body>
<header>
<h1> <a href="matshub.php"> MATS: GameHub </a> </h1>

<div class="user-tag">Welcome, <?= htmlspecialchars($username) ?>!</div>

<form action="logout.php" method="post" style="display:inline;">
<button type="submit" class="logout-btn">Logout</button>
</form>
</header>
<main></main>
<div>
<input id="gameSearch" type="text" style = "text-align:center;"  placeholder="Search for a game">
  <div style="display:inline-flex;align-items:center;gap:8px;">
<button id="btnSearch" type="button">Search</button>
  </div>
</div>
<div style="text-align:center;">
<button id="startRecBtn" class="cool-btn"> Reccommend Me a Game!!" </button>
</div>

 <div id="recModal" class="modal">
<div class="modal-content">
 <button id="closeModal"></button>
   <h2 id="modalTitle">Let's find your next game!</h2>
 <p id="modalQuestion"></p>
 <div id="modalOptions" class="modal-options"></div>
<button id="nextStep" style="display:none;">Next</button>
</div>
</div>

<footer>MATS: GameHub © | By RAWG API</footer>

<script>

let answers = {};
let step = 0;

const resultsEl=document.getElementById('results');
const noteEl=document.getElementById('note');
const modal=document.getElementById('recModal');
const closeBtn=document.getElementById('closeModal');

//setup for some type of interactive array for reccomendation
const questions=[
{key:'genre',text:'What genre are you into?',optins:['Action','Adventure','RPG']},
{key:'platform',text:'Which platform do yu play on?',options:['PC','PS5','Xbox']},
{key:'year',text:'From what year onward do you prefer games?',input:true}
];

function startInteractiveRec(){
 modal.classList.add('show');
 step=0;
 showQuestion();
}

const modalQ = document.getElementById('modalQuestion');
const modalOpt = document.getElementById('modalOptions');

function showQuestion(){
 const q=questions[step];
 modalQ.textContent=q.text;
 modalOpt.innerHTML='';

if(q.input){
 const select=document.createElement('select');
 for(let y=new Date().getFullYear();y>=1990;y--){
 const o=document.createElement('option');o.value=y;o.textContent=y;
 select.appendChild(o);
 }
 modalOpt.appendChild(select);
 return; 
}
 q.options.forEach(opt=>{
 const b=document.createElement('button');
 b.textContent=opt;
 b.onclick=()=>{answers[q.key]=opt;nextStep();};
modalOpt.appendChild(b);
 });
}


function nextStep(){
 step++;
 if(step<questions.length)showQuestion();
 else{modal.classList.remove('show');fetchRecommendations();}
}

async function fetchGames() {
  const q = document.getElementById('gameSearch').value.trim();
  if (!q) return;

  try {
 const res = await fetch(`rabbit_search.php?genre=${encodeURIComponent(q)}`);
  const data = await res.json();
   console.log('Fetched games:', data); // quick check
  } catch (err) {

 console.error('Error fetching games:', err);

  }
}
async function fetchRecommendations() {
  const params = new URLSearchParams(answers);
  
  try {
 const res = await fetch(`rabbit_search.php?${params.toString()}`);
   const data = await res.json();
console.log('Recommendations:', data);
   // should render the results here
  } catch (err) {
    console.error('Error fetching recommendations:', err);
}
}

//trynna normalize backend responses
function mapToCards(items){
return items.map(g=>({
name:g.name??'Unknown',
 rating:g.rating??'N/A',
released:g.released??'N/A'
 }));
} //and render dyanmic crds fir searches
function renderGames(games){
resultsEl.innerHTML='';
for(const g of games){
const c=document.createElement('div');
c.innerHTML=`<h3>${g.name}</h3>
<p>${g.released}</p>`;
resultsEl.appendChild(c);
 }
}

</script>
</body>
</html>
