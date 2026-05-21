<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Story Detail Page</title>
    <link rel="stylesheet" href="CSS/detstory.css">
</head>

<body>
    <div class="back-button"> <a href="homepage.php">✕</a></div>
    <section class="story-detail-page">

        <!-- Story Cover -->
        <div class="cover-section">
            <img src="Pic/Unseen.png" alt="Story Cover">
        </div>

        <!-- Story Info -->
        <div class="info-section">

            <h1 class="story-title">Unseen</h1>

            <p class="genre">
                Romance • Campus Life • Slow Burn • Informatics
            </p>

            <p class="status">
                Status: Ongoing
            </p>

            <p class="description">
                A story about two Informatics students who keep crossing
                paths during campus events, coding projects, and late-night
                debugging sessions—without realizing how much they are slowly
                becoming part of each other’s lives.
            </p>

            <!-- Stats -->
            <div class="story-stats">
                <span>👁 12.4K Views</span>
                <span>❤️ 3.2K Likes</span>
                <span>💬 856 Comments</span>
                <span>📚 24 Chapters</span>
            </div>

            <!-- Buttons -->
            <div class="action-buttons">
                <button class="like-btn">Like</button>
                <button class="save-btn">Save</button>
                <button class="follow-btn">Follow</button>
                <a href="Readingpage.php"> <button class="read-btn">Start Reading</button> </a>
            </div>

            <!-- Writer Info -->
            <div class="writer-card">
                <img src="Pic/profileicon.jpg" alt="Writer Profile">

                <div class="writer-info">
                    <h3>@odmey_</h3>
                    <p>Writing romance, chaos, and emotional damage.</p>
                    <small>12 Stories • 8.5K Followers</small>
                </div>
            </div>

        </div>

    </section>

    <script src="story-detail.js"></script>
</body>

</html>