// this is going to be the official landing page for the video game recommendation platform (MATS: GameHub)
<?php
session_start();
if(!isset($_SESSION['username'])){
  header("Location: login.php");
  exit;
}
$username=$_SESSION['username'];
$email=$_SESSION['email'] ?? '';
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>MATS: GameHub</title>
</head>
<body>
</body>
</html>

<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="style.css">


?>
