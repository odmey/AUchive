<?php
session_start();
$isLoggedIn = isset($_SESSION["user_id"]);
$name = $isLoggedIn ? htmlspecialchars($_SESSION["name"] ?? "User") : "";

require_once 'PHP/database.php';

$keyword = trim($_GET['q'] ?? '');
$pattern = '%' . $keyword . '%';

$pdo = getDB();

// Fetch Users
$stmtUsers = $pdo->prepare("
    SELECT user_id, username, name, profile_pic
    FROM users
    WHERE username LIKE ? OR name LIKE ?
");
$stmtUsers->execute([$pattern, $pattern]);
$users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

// Fetch Stories
$sqlStories = "
    SELECT DISTINCT
        s.story_id,
        s.title,
        s.cover,
        s.status,
        s.description,
        s.total_views,
        s.total_likes,
        u.name AS author_name,
        (SELECT COUNT(*) FROM chapters c WHERE c.story_id = s.story_id AND c.status = 'published') AS chapter_count
    FROM stories s
    LEFT JOIN story_tags st ON st.story_id = s.story_id
    LEFT JOIN tags t ON t.tag_id = st.tag_id
    LEFT JOIN users u ON u.user_id = s.user_id
    WHERE (s.title LIKE ? OR t.tag_name LIKE ?)
      AND s.status = 'published'
";
$stmtStories = $pdo->prepare($sqlStories);
$stmtStories->execute([$pattern, $pattern]);
$stories = $stmtStories->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - AUchive</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bitter&family=Lora&family=Poppins&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link rel="stylesheet" href="CSS/style_homep.css">
    <link rel="stylesheet" href="CSS/style_search.css">

    <script src="JS/lgsgpopmenu.js?v=3" defer></script>
</head>

<body>

    <!-- HEADER (Copied from homepage.php) -->
    <div class="header-wrapper" style="position: sticky; top: 0; z-index: 100;">
        <div class="banner">
            <a href="homepage.php"><img src="Pic/TextLogo.png" alt="Logo"></a>
        </div>

        <div class="white-banner">
            <div class="search-container">
                <span class="material-symbols-outlined icon" id="libBtn" title="Library">
                    library_books
                </span>

                <div class="search-bar">
                    <span class="material-symbols-outlined">search</span>
                    <input type="text" id="searchInput" placeholder="Search AU Story..." value="<?= htmlspecialchars($keyword) ?>">
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

    <div class="search-page-container">
        <h2 class="search-header">Search Results for "<?= htmlspecialchars($keyword) ?>"</h2>

        <!-- TABS -->
        <div class="search-tabs" id="searchTabs">
            <div class="tab-indicator" id="tabIndicator"></div>
            <button class="tab-btn active" data-tab="stories">
                Stories
                <span class="tab-count"><?= count($stories) ?></span>
            </button>
            <button class="tab-btn" data-tab="users">
                Users
                <span class="tab-count"><?= count($users) ?></span>
            </button>
        </div>

        <!-- Stories Section -->
        <div class="search-section" id="tab-stories">
            <?php if (count($stories) > 0): ?>
                <div class="story-grid">
                    <?php foreach ($stories as $story): 
                        $coverSrc = !empty($story['cover']) ? htmlspecialchars($story['cover']) : 'Pic/cover-placeholder.png';
                        
                        $statusLabel = "Draft";
                        $statusClass = "status-draft";
                        if ($story['status'] === 'published') {
                            $statusLabel = "Terbit";
                            $statusClass = "status-published";
                        } elseif ($story['status'] === 'ongoing') {
                            $statusLabel = "Ongoing";
                            $statusClass = "status-ongoing";
                        }
                    ?>
                        <a href="Detstory.php?id=<?= $story['story_id'] ?>" class="story-card">
                            <img class="story-cover" src="<?= $coverSrc ?>" alt="Cover" onerror="this.src='Pic/cover-placeholder.png'">
                            <div class="story-info">
                                <div class="story-title" title="<?= htmlspecialchars($story['title']) ?>"><?= htmlspecialchars($story['title']) ?></div>
                                <div class="story-author">by <?= htmlspecialchars($story['author_name'] ?? 'Unknown') ?></div>
                                <p class="story-desc"><?= htmlspecialchars(mb_strimwidth($story['description'] ?? '', 0, 200, '...')) ?></p>
                                <div class="story-meta">
                                    <span class="story-meta-item">
                                        <span class="material-symbols-outlined" style="font-size:16px; vertical-align:middle; margin-right:3px;">visibility</span><?= number_format($story['total_views'] ?? 0) ?>
                                    </span>
                                    <span class="story-meta-item">
                                        <span class="material-symbols-outlined" style="font-size:16px; vertical-align:middle; margin-right:3px;">favorite</span><?= number_format($story['total_likes'] ?? 0) ?>
                                    </span>
                                    <span class="story-meta-item">
                                        <span class="material-symbols-outlined" style="font-size:16px; vertical-align:middle; margin-right:3px;">format_list_bulleted</span><?= $story['chapter_count'] ?? 0 ?> Ch
                                    </span>
                                    <span class="story-status <?= $statusClass ?>"><?= $statusLabel ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-results">No stories found matching "<?= htmlspecialchars($keyword) ?>"</p>
            <?php endif; ?>
        </div>

        <!-- Users Section -->
        <div class="search-section hidden" id="tab-users">
            <?php if (count($users) > 0): ?>
                <div class="user-grid">
                    <?php foreach ($users as $user): 
                        $avatar = $user['profile_pic'] ?: 'Pic/profileicon.jpg';
                    ?>
                        <a href="profile_person.php?id=<?= $user['user_id'] ?>" class="user-card">
                            <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar" onerror="this.src='Pic/profileicon.jpg'">
                            <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
                            <div class="user-username">@<?= htmlspecialchars($user['username']) ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-results">No users found matching "<?= htmlspecialchars($keyword) ?>"</p>
            <?php endif; ?>
        </div>
    </div>

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

    <!-- MODALS -->
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
                    <input type="password" name="password" placeholder="Password" autocomplete="current-password" required>
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
                    <input type="password" name="password" placeholder="Password (min 8 karakter)" autocomplete="new-password" required>
                    <button type="submit">Sign Up</button>
                </form>
            </div>
        </div>
    </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tabs = document.querySelectorAll('.tab-btn');
            const indicator = document.getElementById('tabIndicator');
            const sections = document.querySelectorAll('.search-section');

            // Move indicator to active tab
            function moveIndicator(btn) {
                indicator.style.width = btn.offsetWidth + 'px';
                indicator.style.left = btn.offsetLeft + 'px';
            }

            // Animate cards entrance
            function animateCards(section) {
                const cards = section.querySelectorAll('.story-card, .user-card');
                cards.forEach((card, i) => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        card.style.transition = `opacity 0.35s ease ${i * 50}ms, transform 0.35s ease ${i * 50}ms`;
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 20);
                });
            }

            // Switch tab
            function switchTab(btn) {
                const tabName = btn.dataset.tab;

                // Active button
                tabs.forEach(t => t.classList.remove('active'));
                btn.classList.add('active');

                // Slide indicator
                moveIndicator(btn);

                // Fade out current, then fade in new
                sections.forEach(s => {
                    if (!s.classList.contains('hidden')) {
                        s.classList.add('tab-fade-out');
                        setTimeout(() => {
                            s.classList.add('hidden');
                            s.classList.remove('tab-fade-out');
                        }, 200);
                    }
                });

                const target = document.getElementById('tab-' + tabName);
                setTimeout(() => {
                    target.classList.remove('hidden');
                    target.classList.add('tab-fade-in');
                    animateCards(target);
                    setTimeout(() => target.classList.remove('tab-fade-in'), 300);
                }, 210);
            }

            // Bind tab clicks
            tabs.forEach(btn => {
                btn.addEventListener('click', () => switchTab(btn));
            });

            // Init: position indicator on first tab
            const activeBtn = document.querySelector('.tab-btn.active');
            if (activeBtn) {
                moveIndicator(activeBtn);
                animateCards(document.getElementById('tab-' + activeBtn.dataset.tab));
            }

            // Re-position indicator on window resize
            window.addEventListener('resize', () => {
                const current = document.querySelector('.tab-btn.active');
                if (current) moveIndicator(current);
            });
        });
    </script>
</body>
</html>
