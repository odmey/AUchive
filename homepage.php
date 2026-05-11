<?php
session_start();
$isLoggedIn = isset($_SESSION["user_id"]);
$name = $isLoggedIn ? htmlspecialchars($_SESSION["name"] ?? "User") : "";
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

    <script src="JS/lgsgpopmenu.js" defer></script>
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
                <button class="btn-primary" type="button" onclick="goToLibrary()">Start Reading</button>
                <button class="btn-secondary" type="button" onclick="openSignup()">Join Now</button>
            </div>
        </div>
    </section>

    <!-- POPULAR STORIES -->
    <section class="story-section">
        <h3>Popular Stories</h3>

        <div class="slider-container">
            <button class="arrow-btn" type="button" onclick="scrollSlider(this)">❯</button>

            <div class="slider">
                <div class="card-slider">
                    <a href="Detstory.html"><img src="Pic/Unseen.png" alt="Story1"></a>
                </div>
                <div class="card-slider"><img src="Pic/karya2.jpg" alt="Story 2"></div>
                <div class="card-slider"><img src="Pic/karya3.jpg" alt="Story 3"></div>
                <div class="card-slider"><img src="Pic/karya4.jpg" alt="Story 4"></div>
            </div>
        </div>
    </section>

    <!-- NEW STORIES -->
    <section class="story-section">
        <h3>Newest Stories</h3>

        <div class="slider-container">
            <button class="arrow-btn" type="button" onclick="scrollSlider(this)">❯</button>

            <div class="slider">
                <div class="card-slider"><img src="Pic/karya5.jpg" alt="Story 5"></div>
                <div class="card-slider"><img src="Pic/karya6.jpg" alt="Story 6"></div>
                <div class="card-slider"><img src="Pic/karya7.jpg" alt="Story 7"></div>
                <div class="card-slider"><img src="Pic/karya8.jpg" alt="Story 8"></div>
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