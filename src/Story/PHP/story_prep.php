<?php
session_start();
require_once __DIR__ . '/../../Core/PHP/database.php';
header('Content-Type: application/json');

// Cek user sudah login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'redirect_url' => 'homepage.php?auth=login']);
    exit;
}

$user_id     = $_SESSION['user_id'];
$title       = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$genre_name  = trim($_POST['genre'] ?? '');
$tags        = trim($_POST['tags'] ?? '');

// Validasi
if (empty($title)) {
    echo json_encode(['success' => false, 'message' => 'Title cannot be empty']);
    exit;
}
if (empty($genre_name)) {
    echo json_encode(['success' => false, 'message' => 'Genre cannot be empty']);
    exit;
}

$pdo = getDB();

// Upload cover ke cloud (ImgBB)
$cover_path = null;
if (isset($_FILES['cover']) && $_FILES['cover']['error'] === 0) {
    $fileContent = file_get_contents($_FILES['cover']['tmp_name']);
    if ($fileContent !== false) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $_FILES['cover']['tmp_name']);
        finfo_close($finfo);
        $base64 = 'data:' . $mime . ';base64,' . base64_encode($fileContent);
        $cover_path = uploadToCloud($base64);
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

// Insert story
try {
    $stmt = $pdo->prepare("
        INSERT INTO stories (user_id, title, description, genre_id, cover)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $title, $description, $genre_id, $cover_path]);
    $story_id = $pdo->lastInsertId();
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
    exit;
}

// Insert tags
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

// Simpan story_id ke session lalu return JSON dengan URL redirect
$_SESSION['story_id'] = $story_id;
echo json_encode([
    'success'      => true,
    'redirect_url' => 'Editor.php?story_id=' . $story_id . '&new=1'
]);
exit;
?>
