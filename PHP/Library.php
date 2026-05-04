<?php include 'data.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Library AUchive</title>

<link rel="stylesheet" href="style.css">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <span class="material-icons icon" data-title="Beranda">home</span>
    <span class="material-icons icon active" data-title="Library">menu_book</span>
    <span class="material-icons icon" data-title="Profil">person</span>
    <span class="material-icons icon" data-title="Pengaturan">settings</span>
</div>

<!-- MAIN -->
<div class="main">

    <h1>Library</h1>
    <p class="subtitle">Tempat semua cerita yang kamu simpan.</p>

    <!-- CONTINUE -->
    <h2>Melanjutkan Bacaan</h2>
    <div class="card-container">
        <?php foreach($stories as $s): ?>
            <?php if($s['progress'] > 0 && $s['progress'] < 100): ?>
                <div class="card">
                    <img src="<?= $s['image'] ?>">
                    <h3><?= $s['title'] ?></h3>
                    <small><?= $s['genre'] ?></small>

                    <div class="progress">
                        <div class="progress-bar" style="width:<?= $s['progress'] ?>%"></div>
                    </div>

                    <p class="status"><?= $s['status'] ?></p>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- ALL -->
    <h2>Semua Cerita</h2>
    <div class="card-container">
        <?php foreach($stories as $s): ?>
            <div class="card">
                <?php if($s['favorite']): ?>
                    <span class="material-icons fav">favorite</span>
                <?php endif; ?>

                <img src="<?= $s['image'] ?>">
                <h3><?= $s['title'] ?></h3>
                <small><?= $s['genre'] ?></small>

                <div class="progress">
                    <div class="progress-bar" style="width:<?= $s['progress'] ?>%"></div>
                </div>

                <p class="status"><?= $s['status'] ?></p>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<script src="script.js"></script>
</body>
</html>