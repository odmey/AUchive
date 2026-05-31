<?php
session_start();
require_once 'PHP/database.php';

$name = 'Guest';
$email = '';
$pic = 'Pic/profileicon.jpg';
$isLoggedIn = false;

if (isset($_SESSION['user_id'])) {
    $isLoggedIn = true;
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT name, email, profile_pic FROM users WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch();
    if ($u) {
        $name = $u['name'];
        $email = $u['email'];
        $pic = $u['profile_pic'] ?: $pic;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bitter&family=Lora&family=Poppins&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link rel="stylesheet" href="CSS/style_setting.css">
    <title>Setting</title>
</head>

<body>

    <!-- OVERLAY -->
    <div id="overlay"></div>

    <!-- POPUP -->
    <div class="menu-popup" id="menuPopup" aria-hidden="true">
        <div class="menu-popup-box" role="dialog" aria-modal="true" aria-labelledby="menuPopupTitle">
            <div class="menu-popup-header">
                <h2 class="menu-popup-title" id="menuPopupTitle"></h2>
                <button class="menu-popup-close" id="menuPopupClose" type="button">&times;</button>
            </div>

            <div class="menu-popup-body" id="menuPopupBody"></div>
            <div class="menu-popup-actions" id="menuPopupActions"></div>
        </div>
    </div>

    <header>
        <div class="left">
            <a href="homepage.php" class="back-link" aria-label="Back">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
        </div>
    </header>

    <div class="plain">

        <!-- GUEST VIEW: PHP langsung hide/show — tidak perlu tunggu JS -->
        <div class="guest-view" style="display:<?= $isLoggedIn ? 'none' : 'block' ?>;">
            <div class="profile-card">
                <img src="Pic/profileicon.jpg" alt="Profile icon">
                <div class="profile-desc">
                    <h3>Welcome</h3>
                    <p>Please login first</p>
                </div>
            </div>

            <div class="section-settings">
                <div class="item-set" onclick="goLogin()">
                    <span>Login</span>
                </div>
                <div class="item-set" onclick="goSignup()">
                    <span>Sign Up</span>
                </div>
            </div>

            <div class="section-settings">
                <h4>Help</h4>
                <div class="item-set" data-popup="faq">
                    <span>FAQ</span>
                </div>
            </div>
        </div>

        <!-- USER VIEW: PHP langsung hide/show — tidak perlu tunggu JS -->
        <div class="user-view" style="display:<?= $isLoggedIn ? 'block' : 'none' ?>;">
            <div class="profile-card">
                <img src="<?= htmlspecialchars($pic) ?>" alt="Profile icon">
                <div class="profile-desc">
                    <h3>
                        <?= htmlspecialchars($name) ?>
                    </h3>
                    <p>
                        <?= htmlspecialchars($email) ?>
                    </p>
                </div>
            </div>

            <div class="section-settings">
                <h4>Account</h4>

                <div class="item-set" data-popup="account">
                    <span>Profile</span>
                </div>

                <div class="item-set" data-popup="email">
                    <span>Email</span>
                </div>

                <div class="item-set" data-popup="password">
                    <span>Password & Security</span>
                </div>
            </div>

            <div class="section-settings">
                <h4>Help Center</h4>
                <div class="item-set" data-popup="faq">
                    <span>FAQ</span>
                </div>
            </div>

            <div class="section-settings">
                <div class="item-set" data-popup="logout">
                    <span>Log Out</span>
                </div>
            </div>
        </div>

    </div>

    <footer>
        AUchive Originals | ©2026
    </footer>

    <script src="JS/setting.js"></script>
</body>

</html>