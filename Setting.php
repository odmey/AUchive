<?php
session_start();
require_once 'src/Core/PHP/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: homepage.php");
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare('SELECT name, email, profile_pic FROM users WHERE user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$u = $stmt->fetch();
$name = $u['name'] ?? 'User';
$email = $u['email'] ?? '';
$pic = $u['profile_pic'] ?: 'Pic/PP kosongan.jpg';
// Determine where the back button should point
$backUrl = ($_GET['from'] ?? '') === 'profile' ? 'Profile.php' : 'homepage.php';
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
    <link rel="stylesheet" href="src/User/CSS/style_setting.css">
    <title>Setting</title>
    <script src="src/Core/JS/custom_alert.js"></script>
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
            <a href="<?= $backUrl ?>" class="back-link" aria-label="Back">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
        </div>
    </header>

    <div class="plain">

        <div class="user-view">
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

    <script src="src/User/JS/setting.js"></script>
</body>

</html>
