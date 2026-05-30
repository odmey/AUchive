<?php
session_start();
$isLoggedIn = isset($_SESSION["user_id"]);
$name = $isLoggedIn ? htmlspecialchars($_SESSION["name"] ?? "User") : "";

// Include database & fetch dynamic stories
require_once 'PHP/database.php';
$pdo = getDB();

// 1. Fetch Popular Stories (ordered by views & likes)
$stmtPopular = $pdo->prepare("
    SELECT story_id, title, cover 
    FROM stories 
    WHERE status = 'published' 
    ORDER BY total_views DESC, total_likes DESC 
    LIMIT 10
");
$stmtPopular->execute();
$popularStories = $stmtPopular->fetchAll();

// 2. Fetch Newest Stories (ordered by published_at DESC)
$stmtNewest = $pdo->prepare("
    SELECT story_id, title, cover 
    FROM stories 
    WHERE status = 'published' 
    ORDER BY published_at DESC 
    LIMIT 10
");
$stmtNewest->execute();
$newestStories = $stmtNewest->fetchAll();

// Static fallback stories in case DB has few elements (to maintain rich visual aesthetics)
$staticFallbackPopular = [
    ['story_id' => null, 'title' => 'Unseen', 'cover' => 'Pic/Unseen.png'],
    ['story_id' => null, 'title' => 'Karya 2', 'cover' => 'Pic/karya2.jpg'],
    ['story_id' => null, 'title' => 'Karya 3', 'cover' => 'Pic/karya3.jpg'],
    ['story_id' => null, 'title' => 'Karya 4', 'cover' => 'Pic/karya4.jpg']
];

$staticFallbackNewest = [
    ['story_id' => null, 'title' => 'Karya 5', 'cover' => 'Pic/karya5.jpg'],
    ['story_id' => null, 'title' => 'Karya 6', 'cover' => 'Pic/karya6.jpg'],
    ['story_id' => null, 'title' => 'Karya 7', 'cover' => 'Pic/karya7.jpg'],
    ['story_id' => null, 'title' => 'Karya 8', 'cover' => 'Pic/karya8.jpg']
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AUchive Landing Page</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bitter&family=Lora&family=Poppins&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link rel="stylesheet" href="CSS/style_homep.css">

    <script src="JS/lgsgpopmenu.js?v=2" defer></script>
</head>

<body>

    <!-- HEADER -->
    <div class="header-wrapper">
        <div class="banner">
            <img src="Pic/TextLogo.png" alt="Logo">
        </div>

        <div class="white-banner">
            <div class="search-container">
                <span class="material-symbols-outlined icon" id="libBtn" title="Library">
                    library_books
                </span>

                <div class="search-bar">
                    <span class="material-symbols-outlined">search</span>
                    <input type="text" id="searchInput" placeholder="Search AU Story...">
                    <div class="search-result" id="searchResult"></div>
                </div>

                <div class="right-icons" id="navArea">
                    <div class="guest-nav" id="guestNav" style="display:<?php echo $isLoggedIn ? 'none' : 'flex'; ?>">
                        <button class="nav-btn" type="button" onclick="openLogin()">Login</button>
                        <button class="nav-btn signup" type="button" onclick="openSignup()">Sign Up</button>
                    </div>

                    <div class="user-nav" id="userNav" style="display:<?php echo $isLoggedIn ? 'flex' : 'none'; ?>">
                        <img src="Pic/profileicon.jpg" alt="Profile" class="nav-profile" id="profileBtn"
                            title="Profile">
                        <div class="settingacc" id="settingBtn" title="Settings">
                            <span class="material-symbols-outlined">settings</span>
                        </div>
                        <div class="notif-acc" id="notifBtn" title="Notifications">
                            <span class="material-symbols-outlined">notifications</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- HERO SECTION -->
    <section class="hero">
        <img src="Pic/cover-utama.jpg" alt="Cover">

        <div class="hero-overlay">
            <h1>Read, Write, and Share Your AU Story</h1>
            <p>
                Discover thousands of Alternate Universe stories
                from your favorite fandoms and start your own journey.
            </p>

            <div class="hero-buttons">
            <?php if (!$isLoggedIn): ?>
                <button class="btn-primary" type="button" onclick="goToLibrary()">Start Reading</button>
                <button class="btn-secondary" type="button" onclick="openSignup()">Join Now</button>
            <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- POPULAR STORIES -->
    <section class="story-section">
        <h3>Popular Stories</h3>

        <div class="slider-container">
            <button class="arrow-btn" type="button" onclick="scrollSlider(this)">❯</button>

            <div class="slider">
                <?php 
                $displayPopular = count($popularStories) >= 1 ? $popularStories : $staticFallbackPopular;
                foreach ($displayPopular as $s): 
                    $coverSrc = !empty($s['cover']) ? htmlspecialchars($s['cover']) : 'Pic/cover-placeholder.png';
                    $link = $s['story_id'] !== null ? "Detstory.php?id=" . $s['story_id'] : "Detstory.php";
                ?>
                    <div class="card-slider">
                        <a href="<?= $link ?>">
                            <img src="<?= $coverSrc ?>" alt="<?= htmlspecialchars($s['title']) ?>" onerror="this.src='Pic/cover-placeholder.png'">
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- NEW STORIES -->
    <section class="story-section">
        <h3>Newest Stories</h3>

        <div class="slider-container">
            <button class="arrow-btn" type="button" onclick="scrollSlider(this)">❯</button>

            <div class="slider">
                <?php 
                $displayNewest = count($newestStories) >= 1 ? $newestStories : $staticFallbackNewest;
                foreach ($displayNewest as $s): 
                    $coverSrc = !empty($s['cover']) ? htmlspecialchars($s['cover']) : 'Pic/cover-placeholder.png';
                    $link = $s['story_id'] !== null ? "Detstory.php?id=" . $s['story_id'] : "Detstory.php";
                ?>
                    <div class="card-slider">
                        <a href="<?= $link ?>">
                            <img src="<?= $coverSrc ?>" alt="<?= htmlspecialchars($s['title']) ?>" onerror="this.src='Pic/cover-placeholder.png'">
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="footer">
        <img src="Pic/TextLogo.png" alt="Logo">
        <p>Create. Share. Express your story.</p>

        <br>

        <p><b>Contact Person</b></p>
        <p>auchivecp@gmail.com</p>
        <p>(021) 09808882</p>

        <br>
        <hr>

        <div class="footer-bottom">
            © 2026 AUchive. All rights reserved.
        </div>
    </footer>

    <!-- LOGIN MODAL -->
    <div id="loginModal" class="modal">
        <div class="auth-card">
            <span class="close" id="closeLoginBtn">&times;</span>

            <div class="auth-inner">
                <h2 class="auth-title">
                    Login
                    <img src="Pic/icon7.png" alt="iconlogo">
                </h2>

                <p class="form-message" id="loginMessage" aria-live="polite"></p>

                <form class="auth-form" id="loginForm" novalidate>
                    <input type="email" name="email" placeholder="Email" autocomplete="email" required>
                    <input type="password" name="password" placeholder="Password" autocomplete="current-password"
                        required>
                    <button type="submit">Login</button>
                </form>
            </div>
        </div>
    </div>

    <!-- SIGN UP MODAL -->
    <div id="signupModal" class="modal">
        <div class="auth-card">
            <span class="close" id="closeSignupBtn">&times;</span>

            <div class="auth-inner">
                <h2 class="auth-title">
                    Sign Up
                    <img src="Pic/icon7.png" alt="iconlogo">
                </h2>

                <p class="form-message" id="signupMessage" aria-live="polite"></p>

                <form class="auth-form" id="signupForm" novalidate>
                    <input type="text" name="username" placeholder="Username" autocomplete="nickname" required>
                    <input type="text" name="name" placeholder="Nama Lengkap" autocomplete="name" required>
                    <input type="email" name="email" placeholder="Email" autocomplete="email" required>
                    <input type="password" name="password" placeholder="Password (min 8 karakter)"
                        autocomplete="new-password" required>
                    <button type="submit">Sign Up</button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>