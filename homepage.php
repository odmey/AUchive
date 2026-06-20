<?php
session_start();
$isLoggedIn = isset($_SESSION["user_id"]);
$name = $isLoggedIn ? htmlspecialchars($_SESSION["name"] ?? "User") : "";

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

// Static fallback stories in case DB has few elements (to maintain rich visual aesthetics)
$staticFallbackPopular = [
    ['story_id' => null, 'title' => 'Unseen',  'cover' => 'Pic/Unseen.png',  'total_views' => 1500, 'author_username' => 'odmey_'],
    ['story_id' => null, 'title' => 'Karya 2', 'cover' => 'Pic/karya2.jpg', 'total_views' => 920,  'author_username' => 'user_'],
    ['story_id' => null, 'title' => 'Karya 3', 'cover' => 'Pic/karya3.jpg', 'total_views' => 450,  'author_username' => 'user_'],
    ['story_id' => null, 'title' => 'Karya 4', 'cover' => 'Pic/karya4.jpg', 'total_views' => 310,  'author_username' => 'user_'],
];

$staticFallbackNewest = [
    ['story_id' => null, 'title' => 'Karya 5', 'cover' => 'Pic/karya5.jpg', 'total_views' => 200, 'author_username' => 'user_'],
    ['story_id' => null, 'title' => 'Karya 6', 'cover' => 'Pic/karya6.jpg', 'total_views' => 180, 'author_username' => 'user_'],
    ['story_id' => null, 'title' => 'Karya 7', 'cover' => 'Pic/karya7.jpg', 'total_views' => 120, 'author_username' => 'user_'],
    ['story_id' => null, 'title' => 'Karya 8', 'cover' => 'Pic/karya8.jpg', 'total_views' => 90,  'author_username' => 'user_'],
];

// Fetch system warning
$systemWarning = '';
try {
    $warnStmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'system_warning' LIMIT 1");
    $warnRow = $warnStmt ? $warnStmt->fetch() : false;
    if ($warnRow && !empty(trim($warnRow['setting_value'] ?? ''))) {
        $systemWarning = htmlspecialchars(trim($warnRow['setting_value']));
    }
} catch (Exception $e) {
    // Table may not exist yet, ignore
}
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

    <?php if (!empty($systemWarning)): ?>
    <!-- SYSTEM WARNING BANNER -->
    <style>
    .warning-banner-marquee {
        background: linear-gradient(90deg, #d32f2f, #f57c00);
        color: #fff;
        padding: 10px 0;
        font-family: 'Poppins', sans-serif;
        font-size: 14px;
        font-weight: 500;
        position: relative;
        z-index: 9998;
        overflow: hidden;
        display: flex;
        align-items: center;
        border-bottom: 1.5px solid rgba(255, 244, 79, 0.25);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    .marquee-container {
        width: 100%;
        overflow: hidden;
        white-space: nowrap;
        position: relative;
        padding-right: 50px;
    }
    .marquee-content {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        padding-left: 100%;
        animation: marquee-scroll 25s linear infinite;
        cursor: default;
    }
    .marquee-content:hover {
        animation-play-state: paused;
    }
    .marquee-icon {
        font-size: 18px;
        color: #fff44f;
        animation: pulse-warn 1.5s infinite ease-in-out;
        vertical-align: middle;
    }
    .marquee-close-btn {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: #fff;
        font-size: 18px;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        z-index: 9999;
        transition: all 0.3s ease;
    }
    .marquee-close-btn:hover {
        background: #e74c3c;
        border-color: #e74c3c;
        transform: translateY(-50%) scale(1.1);
        box-shadow: 0 0 8px rgba(231, 76, 60, 0.6);
    }
    @keyframes marquee-scroll {
        0% { transform: translate3d(0, 0, 0); }
        100% { transform: translate3d(-100%, 0, 0); }
    }
    @keyframes pulse-warn {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.15); filter: drop-shadow(0 0 4px #fff44f); }
    }
    </style>
    <div id="systemWarningBanner" class="warning-banner-marquee">
        <div class="marquee-container">
            <div class="marquee-content">
                <span class="material-symbols-outlined marquee-icon">warning</span>
                <span><?= $systemWarning ?></span>
            </div>
        </div>
        <button onclick="dismissWarningBanner(this)" class="marquee-close-btn">&times;</button>
    </div>
    <script>
    function dismissWarningBanner(btn) {
        const banner = document.getElementById('systemWarningBanner');
        if (banner) {
            banner.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
            banner.style.opacity = '0';
            banner.style.transform = 'translateY(-100%)';
            setTimeout(() => {
                banner.style.display = 'none';
            }, 400);
        }
    }
    </script>
    <?php endif; ?>

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
                    <img src="Pic/profileicon.jpg" alt="Profile" class="nav-profile" id="profileBtn" title="Profile">
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
    $readUrl = $popularStoryId > 0 ? "Detstory.php?id=" . $popularStoryId : "#";
    ?>
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
                $displayPopular = count($popularStories) >= 1 ? $popularStories : $staticFallbackPopular;
                $rank = 1;
                foreach ($displayPopular as $s): 
                    $coverSrc = !empty($s['cover']) ? htmlspecialchars($s['cover']) : 'Pic/cover-placeholder.png';
                    $link = $s['story_id'] !== null ? "Detstory.php?id=" . $s['story_id'] : "Detstory.php";
                ?>
                    <div class="card-slider">
                        <?php if ($rank <= 3): ?>
                            <div class="rank-badge rank-<?= $rank ?>"><?= $rank ?></div>
                        <?php endif; ?>
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
                    $rank++;
                endforeach; 
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
                $displayNewest = count($newestStories) >= 1 ? $newestStories : $staticFallbackNewest;
                foreach ($displayNewest as $s): 
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
