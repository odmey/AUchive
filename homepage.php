<?php
session_start();

// Admin tidak boleh mengakses homepage — redirect ke panel admin
if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin') {
    header("Location: Etmin.php");
    exit;
}

$isLoggedIn = isset($_SESSION["user_id"]);

// Include database & fetch dynamic stories
require_once 'src/Core/PHP/database.php';
$pdo = getDB();

// 1. Fetch Popular Stories (ordered by views & likes)
$stmtPopular = $pdo->prepare("
    SELECT s.story_id, s.title, s.cover, s.description, s.total_views, u.username AS author_username
    FROM stories s
    LEFT JOIN users u ON s.user_id = u.user_id
    WHERE s.status = 'published'
    ORDER BY s.total_views DESC, s.total_likes DESC
    LIMIT 10
");
$stmtPopular->execute();
$popularStories = $stmtPopular->fetchAll();

// 2. Fetch Newest Stories (ordered by published_at DESC)
$stmtNewest = $pdo->prepare("
    SELECT s.story_id, s.title, s.cover, s.total_views, u.username AS author_username
    FROM stories s
    LEFT JOIN users u ON s.user_id = u.user_id
    WHERE s.status = 'published'
    ORDER BY s.published_at DESC
    LIMIT 10
");
$stmtNewest->execute();
$newestStories = $stmtNewest->fetchAll();


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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="src/Core/CSS/style_homep.css">
    <script src="src/Core/JS/custom_alert.js"></script>
    <script src="src/Core/JS/lgsgpopmenu.js?v=2" defer></script>
</head>

<body>



    <!-- NAVBAR -->
    <div class="header-wrapper">
        <nav class="navbar">
            <!-- Left: Logo + Library -->
            <div class="navbar-brand">
                <img src="Pic/TextLogo.png" alt="AUchive" class="nav-logo">
                <span class="navbar-divider"></span>
                <div class="nav-library-link" id="libBtn" title="Library">
                    <span class="material-symbols-outlined nav-lib-icon">library_books</span>
                    <span class="nav-lib-label">Library</span>
                </div>
            </div>

            <!-- Center: Search -->
            <div class="search-bar">
                <span class="material-symbols-outlined">search</span>
                <input type="text" id="searchInput" placeholder="Search AU Story..." autocomplete="off">
                <div class="search-result" id="searchResult"></div>
            </div>

            <!-- Right: Nav Buttons / User Icons -->
            <div class="right-icons" id="navArea">
                <div class="guest-nav" id="guestNav" style="display:<?php echo $isLoggedIn ? 'none' : 'flex'; ?>">
                    <button class="nav-btn" type="button" onclick="openLogin()">Login</button>
                    <button class="nav-btn signup" type="button" onclick="openSignup()">Sign Up</button>
                </div>

                <div class="user-nav" id="userNav" style="display:<?php echo $isLoggedIn ? 'flex' : 'none'; ?>">
                    <div class="notif-acc" id="notifBtn" title="Notifications">
                        <span class="material-symbols-outlined">notifications</span>
                    </div>
                    <div class="settingacc" id="settingBtn" title="Settings">
                        <span class="material-symbols-outlined">settings</span>
                    </div>
                    <img src="Pic/PP kosongan.jpg" alt="Profile" class="nav-profile" id="profileBtn" title="Profile">
                </div>
            </div>
        </nav>
    </div>

    <!-- HERO SECTION -->
    <?php
    $heroCover = 'Pic/cover-utama.jpg';
    $heroTitle = 'Read, Write, and Share Your AU Story';
    $heroDesc = 'Discover thousands of Alternate Universe stories from your favorite fandoms and start your own journey.';
    $popularStoryId = 0;

    if (!empty($popularStories)) {
        $mostPopular = $popularStories[0];
        $popularStoryId = (int)$mostPopular['story_id'];
        if (!empty($mostPopular['cover'])) {
            $heroCover = htmlspecialchars($mostPopular['cover']);
        }
        if (!empty($mostPopular['title'])) {
            $heroTitle = htmlspecialchars($mostPopular['title']);
        }
        if (!empty($mostPopular['description'])) {
            $heroDesc = htmlspecialchars(mb_substr($mostPopular['description'], 0, 180));
            if (mb_strlen($mostPopular['description']) > 180) {
                $heroDesc .= '...';
            }
        }
    }
    // make an url for detail story if id is 0 then set #
    $readUrl = $popularStoryId > 0 ? "Detstory.php?id=" . $popularStoryId : "#";
    ?>
    <!-- default if there is no database -->
    <section class="hero">
        <img src="<?= $heroCover ?>" alt="Cover">

        <div class="hero-overlay">
            <p style="font-size: 16px; text-transform: uppercase; letter-spacing: 2px; color: #FFF44F; margin-bottom: 10px; font-weight: 600;">Most People like this Story</p>
            <h1 style="margin-top: 0; margin-bottom: 30px;"><?= $heroTitle ?></h1>

            <div class="hero-buttons">
                <button class="btn-primary" type="button" onclick="window.location.href='<?= $readUrl ?>'">Start Reading</button>
                <button class="btn-secondary" type="button" id="joinNowBtn" onclick="openSignup()" style="<?= $isLoggedIn ? 'display:none' : '' ?>">Join Now</button>
            </div>
        </div>
    </section>

    <!-- POPULAR STORIES -->
    <section class="story-section">
        <h3>Popular Stories</h3>

        <div class="slider-container">
            <button class="arrow-btn prev" type="button" onclick="scrollSlider(this, 'left')">❮</button>
            <button class="arrow-btn next" type="button" onclick="scrollSlider(this, 'right')">❯</button>

            <div class="slider">
                <?php 
                if (empty($popularStories)):
                    echo "<div style='padding:20px; color:rgba(255,255,255,0.5);'>Belum ada cerita yang dipublikasikan.</div>";
                else:
                    foreach ($popularStories as $s): 
                    $coverSrc = !empty($s['cover']) ? htmlspecialchars($s['cover']) : 'Pic/cover-placeholder.png';
                    $link = $s['story_id'] !== null ? "Detstory.php?id=" . $s['story_id'] : "Detstory.php";
                ?>
                    <div class="card-slider">
                        <a href="<?= $link ?>" class="card-slider-link">
                            <img src="<?= $coverSrc ?>" alt="<?= htmlspecialchars($s['title']) ?>" onerror="this.src='Pic/cover-placeholder.png'">
                            <div class="card-slider-info">
                                <span><span class="material-symbols-outlined">visibility</span> <?= number_format((int)($s['total_views'] ?? 0)) ?></span>
                            </div>
                        </a>
                        <div class="card-slider-footer">
                            <h4><?= htmlspecialchars($s['title']) ?></h4>
                            <span class="card-author">by <?= htmlspecialchars($s['author_username'] ?? 'unknown') ?></span>
                        </div>
                    </div>
                <?php 
                    endforeach; 
                endif;
                ?>
            </div>
        </div>
    </section>

    <!-- NEW STORIES -->
    <section class="story-section">
        <h3>Newest Stories</h3>

        <div class="slider-container">
            <button class="arrow-btn prev" type="button" onclick="scrollSlider(this, 'left')">❮</button>
            <button class="arrow-btn next" type="button" onclick="scrollSlider(this, 'right')">❯</button>

            <div class="slider">
                <?php 
                if (empty($newestStories)):
                    echo "<div style='padding:20px; color:rgba(255,255,255,0.5);'>Belum ada cerita terbaru.</div>";
                else:
                    foreach ($newestStories as $s): 
                    $coverSrc = !empty($s['cover']) ? htmlspecialchars($s['cover']) : 'Pic/cover-placeholder.png';
                    $link = $s['story_id'] !== null ? "Detstory.php?id=" . $s['story_id'] : "Detstory.php";
                ?>
                    <div class="card-slider">
                        <a href="<?= $link ?>" class="card-slider-link">
                            <img src="<?= $coverSrc ?>" alt="<?= htmlspecialchars($s['title']) ?>" onerror="this.src='Pic/cover-placeholder.png'">
                            <div class="card-slider-info">
                                <span><span class="material-symbols-outlined">visibility</span> <?= number_format((int)($s['total_views'] ?? 0)) ?></span>
                            </div>
                        </a>
                        <div class="card-slider-footer">
                            <h4><?= htmlspecialchars($s['title']) ?></h4>
                            <span class="card-author">by <?= htmlspecialchars($s['author_username'] ?? 'unknown') ?></span>
                        </div>
                    </div>
                <?php 
                    endforeach; 
                endif;
                ?>
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
                    <input type="text" name="login_input" placeholder="Username/Email" autocomplete="username" required>
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
                    <input type="text" name="name" placeholder="Full Name" autocomplete="name" required>
                    <input type="email" name="email" placeholder="Email" autocomplete="email" required>
                    <input type="password" name="password" placeholder="Password (min 8 characters)"
                        autocomplete="new-password" required>
                    <button type="submit">Sign Up</button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>
