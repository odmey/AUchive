<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AUchive - Guides & Tutorials</title>
    <link href="https://fonts.googleapis.com/css2?family=Bitter&family=Lora&family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link rel="stylesheet" href="CSS/guide.css">
</head>
<body>

<header>
    <a href="Setting.php" class="back-link">
        <span class="material-symbols-outlined">arrow_back</span>
        Back to Settings
    </a>
</header>

<div class="container">
    <h1>Help Center: Guides & Tutorials</h1>
    <p class="subtitle">Learn everything you need to know about navigating and using AUchive.</p>

    <div class="guide-section">
        <h2><span class="material-symbols-outlined">menu_book</span> Reading Stories</h2>
        <p>AUchive is home to a vast collection of interactive stories. Here is how you can start reading:</p>
        <ul class="guide-steps">
            <li>Go to the Homepage to discover trending and recommended stories.</li>
            <li>Click on any story cover to view its details (synopsis, tags, chapters).</li>
            <li>Click "Read First Chapter" or select a specific chapter to start reading.</li>
            <li>Use the navigation buttons at the bottom of the reader to move between chapters.</li>
        </ul>
    </div>

    <div class="guide-section">
        <h2><span class="material-symbols-outlined">edit_square</span> Creating a Story</h2>
        <p>Want to share your own Alternate Universe (AU) story? Follow these steps:</p>
        <ul class="guide-steps">
            <li>Log in to your account.</li>
            <li>Navigate to the <b>Editor</b> page from the main menu.</li>
            <li>Fill in your story title, synopsis, and upload a cover image.</li>
            <li>Use the chapter editor to write your story using our interactive bubble-chat format or standard text blocks.</li>
            <li>Save and publish your chapters for the world to see!</li>
        </ul>
    </div>

    <div class="guide-section">
        <h2><span class="material-symbols-outlined">manage_accounts</span> Managing Your Account</h2>
        <p>Keep your profile up to date and secure:</p>
        <ul class="guide-steps">
            <li>Go to the <b>Settings</b> page.</li>
            <li>Click on <b>Profile</b> to update your Display Name, Bio, and Date of Birth.</li>
            <li>Use the <b>Email</b> or <b>Password & Security</b> menus to change your login credentials. You will need your current password to make these changes.</li>
        </ul>
    </div>

    <div class="guide-section">
        <h2><span class="material-symbols-outlined">bookmarks</span> Library & Bookmarks</h2>
        <p>Never lose track of your favorite stories:</p>
        <ul class="guide-steps">
            <li>When viewing a story, click the <b>Bookmark</b> icon to save it to your Library.</li>
            <li>Access your Library from the top navigation bar to see all your saved stories.</li>
            <li>Stories are automatically sorted by recent updates so you never miss a new chapter.</li>
        </ul>
    </div>
</div>

<footer>
    AUchive Originals | ©2026
</footer>

<script src="JS/guide.js"></script>
</body>
</html>
