<?php
session_start();
require_once 'database.php';

// Cek user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../homepage.php?auth=login');
    exit;
}

$user_id     = $_SESSION['user_id'];
$story_id    = (int)($_POST['story_id'] ?? 0);
$title       = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$genre_name  = trim($_POST['genre'] ?? '');
$tags        = trim($_POST['tags'] ?? '');

// Validasi input minimal
if ($story_id <= 0) {
    header('Location: ../Profile.php?error=invalid_story');
    exit;
}
if (empty($title)) {
    header('Location: ../Profile.php?error=title_kosong');
    exit;
}
if (empty($genre_name)) {
    header('Location: ../Profile.php?error=genre_kosong');
    exit;
}

$pdo = getDB();

// Validasi kepemilikan cerita
$stmt = $pdo->prepare("SELECT user_id, cover FROM stories WHERE story_id = ?");
$stmt->execute([$story_id]);
$story = $stmt->fetch();

if (!$story || (int)$story['user_id'] !== (int)$user_id) {
    header('Location: ../Profile.php?error=unauthorized');
    exit;
}

// Upload cover baru ke cloud jika ada
$cover_path = $story['cover']; // default pakai cover lama
if (isset($_FILES['cover']) && $_FILES['cover']['error'] === 0) {
    $fileContent = file_get_contents($_FILES['cover']['tmp_name']);
    if ($fileContent !== false) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $_FILES['cover']['tmp_name']);
        finfo_close($finfo);
        $base64 = 'data:' . $mime . ';base64,' . base64_encode($fileContent);
        $cloudUrl = uploadToCloud($base64);
        if ($cloudUrl) {
            $cover_path = $cloudUrl;
        }
    }
}

// Cari atau insert genre
$stmt = $pdo->prepare("SELECT genre_id FROM genres WHERE genre_name = ?");
$stmt->execute([$genre_name]);
$genre = $stmt->fetch();

if ($genre) {
    $genre_id = $genre['genre_id'];
} else {
    $stmt = $pdo->prepare("INSERT INTO genres (genre_name) VALUES (?)");
    $stmt->execute([$genre_name]);
    $genre_id = $pdo->lastInsertId();
}

// Update detail cerita
try {
    $stmt = $pdo->prepare("
        UPDATE stories 
        SET title = ?, description = ?, genre_id = ?, cover = ? 
        WHERE story_id = ? AND user_id = ?
    ");
    $stmt->execute([$title, $description, $genre_id, $cover_path, $story_id, $user_id]);
} catch (PDOException $e) {
    echo "Error updating story: " . $e->getMessage();
    die();
}

// Update tags (Hapus asosiasi lama, lalu insert baru)
try {
    $stmt = $pdo->prepare("DELETE FROM story_tags WHERE story_id = ?");
    $stmt->execute([$story_id]);

    if (!empty($tags)) {
        $tag_list = array_filter(array_map('trim', explode(' ', $tags)));

        foreach ($tag_list as $tag_name) {
            // Cari atau insert tag
            $stmt = $pdo->prepare("SELECT tag_id FROM tags WHERE tag_name = ?");
            $stmt->execute([$tag_name]);
            $tag = $stmt->fetch();

            if ($tag) {
                $tag_id = $tag['tag_id'];
            } else {
                $stmt = $pdo->prepare("INSERT INTO tags (tag_name) VALUES (?)");
                $stmt->execute([$tag_name]);
                $tag_id = $pdo->lastInsertId();
            }

            // Hubungkan story dengan tag
            $stmt = $pdo->prepare("INSERT IGNORE INTO story_tags (story_id, tag_id) VALUES (?, ?)");
            $stmt->execute([$story_id, $tag_id]);
        }
    }
} catch (PDOException $e) {
    echo "Error updating tags: " . $e->getMessage();
    die();
}

// Redirect kembali ke halaman profil dengan parameter sukses
header('Location: ../Profile.php?success=story_updated');
exit;
?>
