<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);

// Redirect ke login jika belum login
if (!$isLoggedIn) {
    header('Location: homepage.php?auth=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AUchive - Library</title>

    <link rel="stylesheet" href="src/Library/CSS/lib_style.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="src/Core/JS/custom_alert.js"></script>
</head>

<body>

    <!-- OVERLAY -->
    <div class="overlay" id="overlay"></div>

    <!-- MAIN -->
    <div class="main">

        <div class="header">

            <div class="header-left">
                <span class="material-icons back-btn" onclick="goBack()">arrow_back</span>
                <img src="Pic/Logo.png" class="logo toggle-btn" id="toggleSidebar">
                <h1>Library</h1>
            </div>


        </div>

        <div class="filter">
            <a href="#" class="filter-btn active" data-filter="all">
                <span class="material-icons">menu_book</span>All Stories
            </a>

            <a href="#" class="filter-btn" data-filter="favorite">
                <span class="material-icons">favorite</span>Favorites
            </a>

            <a href="#" class="filter-btn" data-filter="latest">
                <span class="material-icons">schedule</span>Latest
            </a>
        </div>

        <!-- Lanjutkan Membaca Section (Only shown when in Latest tab) -->
        <div id="continue-section" style="display: none;">
            <h2>Continue Reading</h2>
            <div class="card-container" id="continue-reading"></div>
        </div>

        <h2 id="main-title">All Stories</h2>
        <div class="card-container" id="all-story"></div>

    </div>

    <script src="src/Library/JS/Lib.js"></script>
</body>

</html>

