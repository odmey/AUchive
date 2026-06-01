<?php
session_start();
require_once 'PHP/database.php';

// Redirect kalau belum login
if (!isset($_SESSION['user_id'])) {
    header('Location: homepage.php?auth=login');
    exit;
}

$user_id = $_SESSION['user_id'];
$pdo = getDB();

// ── ACTION: CLEAR ALL NOTIFICATIONS ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'clear') {
    header('Content-Type: application/json');
    $tableExists = false;
    try {
        $pdo->query("SELECT 1 FROM notifications LIMIT 1");
        $tableExists = true;
    } catch (PDOException $e) {
        $tableExists = false;
    }

    if ($tableExists) {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
        $stmt->execute([$user_id]);
    } else {
        $stmt = $pdo->prepare("SELECT library_id FROM library WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $lib = $stmt->fetch();
        if ($lib) {
            $library_id = $lib['library_id'];
            $stmtUpdate = $pdo->prepare("
                UPDATE library_stories ls
                SET last_read_chapter_id = (
                    SELECT MAX(c.chapter_id)
                    FROM chapters c
                    WHERE c.story_id = ls.story_id AND c.status = 'published'
                )
                WHERE ls.library_id = ?
            ");
            $stmtUpdate->execute([$library_id]);
        }
    }
    echo json_encode(['success' => true]);
    exit;
}

// ── Helper: format waktu relatif ──────────────────────────
function timeAgo($datetime) {
    $now  = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);

    if ($diff->y > 0) return $diff->y . 'y ago';
    if ($diff->m > 0) return $diff->m . 'mo ago';
    if ($diff->d > 0) return $diff->d . 'd ago';
    if ($diff->h > 0) return $diff->h . 'h ago';
    if ($diff->i > 0) return $diff->i . 'm ago';
    if ($diff->s < 5) return 'Just now';
    return $diff->s . 's ago';
}

// ── Helper: format judul notif ────────────────────────────
function formatNotifTitle($n) {
    $actor = htmlspecialchars($n['actor_username'] ?? 'Someone');
    $story = htmlspecialchars($n['story_title'] ?? 'a story');
    $type  = $n['type'] ?? '';
    $title = $n['title'] ?? '';
    $body  = $n['body'] ?? '';

    // Detect comment
    if (stripos($title, 'mengomentari') !== false || stripos($title, 'commented') !== false) {
        return "<strong>$actor</strong> commented in your story";
    }

    // Detect like
    if ($type === 'like' || stripos($title, 'like') !== false || stripos($title, 'menyukai') !== false) {
        $chapter = htmlspecialchars($body ?: 'this chapter');
        return "<strong>$actor</strong> liked your story chapter \"$chapter\"";
    }

    // Detect follow
    if ($type === 'follow' || stripos($title, 'follow') !== false || stripos($title, 'pengikut') !== false || stripos($title, 'mengikuti') !== false) {
        return "<strong>$actor</strong> followed you";
    }

    // Fallbacks
    switch ($type) {
        case 'story':
            return "New chapter: <strong>$story</strong>";
        default:
            return $title ?: "<strong>$actor</strong> sent you a notification";
    }
}

// ── Cek apakah tabel notifications sudah ada ──────────────
$tableExists = false;
try {
    $pdo->query("SELECT 1 FROM notifications LIMIT 1");
    $tableExists = true;
} catch (PDOException $e) {
    $tableExists = false;
}

$notifications = [];

// ════════════════════════════════════════════════════════════
// MODE A: Tabel notifications SUDAH ADA
// ════════════════════════════════════════════════════════════
if ($tableExists) {
    $sql = "
        SELECT
            n.notif_id,
            n.type,
            n.title,
            n.body,
            n.is_read,
            n.created_at,
            n.link_url,
            s.title     AS story_title,
            s.cover     AS story_cover,
            u.username  AS actor_username
        FROM notifications n
        LEFT JOIN stories s ON s.story_id  = n.ref_story_id
        LEFT JOIN users   u ON u.user_id   = n.actor_user_id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT 50
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();

    // Mark as read
    $unreadIds = array_column(
        array_filter($notifications, fn($n) => !$n['is_read']),
        'notif_id'
    );
    if (!empty($unreadIds)) {
        $ph = implode(',', array_fill(0, count($unreadIds), '?'));
        $mStmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE notif_id IN ($ph)");
        $mStmt->execute($unreadIds);
    }

// ════════════════════════════════════════════════════════════
// MODE B: Tabel belum ada → derive dari library
// ════════════════════════════════════════════════════════════
} else {
    $stmt = $pdo->prepare("SELECT library_id FROM library WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $lib = $stmt->fetch();

    if ($lib) {
        $stmt = $pdo->prepare("
            SELECT
                c.chapter_id,
                c.chapter_title,
                c.created_at,
                s.story_id,
                s.title     AS story_title,
                s.cover     AS story_cover
            FROM library_stories ls
            JOIN stories  s ON s.story_id  = ls.story_id
            JOIN chapters c ON c.story_id  = s.story_id
                           AND c.status    = 'published'
            WHERE ls.library_id = ?
              AND c.chapter_id > COALESCE(ls.last_read_chapter_id, 0)
            ORDER BY c.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$lib['library_id']]);
        $newChapters = $stmt->fetchAll();

        foreach ($newChapters as $ch) {
            $notifications[] = [
                'notif_id'       => null,
                'type'           => 'story',
                'title'          => 'Chapter baru: ' . $ch['story_title'],
                'body'           => '"' . $ch['chapter_title'] . '" sudah tersedia.',
                'is_read'        => 0,
                'created_at'     => $ch['created_at'],
                'link_url'       => 'Readingpage.php?story_id=' . $ch['story_id'] . '&chapter_id=' . $ch['chapter_id'],
                'story_title'    => $ch['story_title'],
                'story_cover'    => $ch['story_cover'],
                'actor_username' => null,
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications – AUchive</title>

    <link rel="stylesheet" href="CSS/notif_style.css">
    <link href="https://fonts.googleapis.com/css2?family=Bitter:wght@600&family=Lora&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>

<body>

    <header class="profile-header">
        <div class="left">
            <a href="javascript:window.history.back()" class="back-link">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
        </div>
        <div class="center">
            <h3>Notification</h3>
        </div>
    </header>

    <div class="tabs-container">
        <div class="tabs">
            <div class="tab active" data-type="all" onclick="filterTab(this)">All</div>
            <div class="tab" data-type="story" onclick="filterTab(this)">Story</div>
            <div class="tab" data-type="social" onclick="filterTab(this)">Social</div>
        </div>
        <?php if (!empty($notifications)): ?>
            <button id="clearAllBtn" onclick="clearAllNotifications()" title="Clear All Notifications">
                <span class="material-symbols-outlined">delete</span>
            </button>
        <?php endif; ?>
    </div>

    <div class="container" id="notifContainer">
        <?php if (empty($notifications)): ?>
            <div class="empty" id="emptyState">
                <span class="material-symbols-outlined">notifications_off</span>
                <p>No notifications yet</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $n):
                $isUnread = !$n['is_read'];
                $formattedTitle = formatNotifTitle($n);
                $body = htmlspecialchars($n['body'] ?? '');
                $time = timeAgo($n['created_at']);
                $link = $n['link_url'] ?? '#';
                $type = $n['type'] ?? 'story';
                $cover = $n['story_cover'] ?? '';
                $actor = $n['actor_username'] ?? '';
                $initial = strtoupper(substr($actor ?: 'A', 0, 1));

                $isComment = (stripos($n['title'] ?? '', 'mengomentari') !== false || stripos($n['title'] ?? '', 'commented') !== false);
                $isFollow = ($type === 'follow' || stripos($n['title'] ?? '', 'follow') !== false || stripos($n['title'] ?? '', 'pengikut') !== false || stripos($n['title'] ?? '', 'mengikuti') !== false);
                $isLike = ($type === 'like' || stripos($n['title'] ?? '', 'like') !== false || stripos($n['title'] ?? '', 'menyukai') !== false);
            ?>
                <div class="notification <?= $isUnread ? 'unread' : '' ?>"
                     data-type="<?= htmlspecialchars($type) ?>"
                     onclick="window.location.href='<?= htmlspecialchars($link) ?>'">
                    <div class="avatar" style="overflow:hidden; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <?php if (!empty($cover)): ?>
                            <img src="<?= htmlspecialchars($cover) ?>" style="width:100%; height:100%; border-radius:50%; object-fit:cover;" onerror="this.style.display='none'">
                        <?php else: ?>
                            <div style="width:100%; height:100%; border-radius:50%; display:flex; align-items:center; justify-content:center; color:black; font-weight:bold; font-size:16px;">
                                <?= $initial ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="content">
                        <div class="title"><?= $formattedTitle ?></div>
                        <?php if ($body && ($isComment || (!$isFollow && !$isLike))): ?>
                            <div class="preview"><?= $body ?></div>
                        <?php endif; ?>
                        <div class="time"><?= $time ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="empty" id="emptyFiltered" style="display:none;">
        <span class="material-symbols-outlined">notifications_off</span>
        <p>No notifications in this category</p>
    </div>

    <script>
        // Custom Dialog Confirm
        function customConfirm(message) {
            return new Promise((resolve) => {
                const overlay = document.createElement('div');
                overlay.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100vw;
                    height: 100vh;
                    background: rgba(0, 0, 0, 0.75);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10000;
                    opacity: 0;
                    transition: opacity 0.2s ease;
                `;

                const modal = document.createElement('div');
                modal.style.cssText = `
                    background: #1C1C1C;
                    border: 1px solid rgba(255, 244, 79, 0.25);
                    border-radius: 16px;
                    padding: 24px;
                    width: 90%;
                    max-width: 380px;
                    text-align: center;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
                    transform: scale(0.9);
                    transition: transform 0.2s ease;
                `;

                const text = document.createElement('p');
                text.textContent = message;
                text.style.cssText = `
                    font-size: 15px;
                    color: #fff;
                    margin-bottom: 24px;
                    font-family: 'Poppins', sans-serif;
                    line-height: 1.5;
                `;

                const btnContainer = document.createElement('div');
                btnContainer.style.cssText = `
                    display: flex;
                    gap: 12px;
                    justify-content: center;
                `;

                const cancelBtn = document.createElement('button');
                cancelBtn.textContent = 'Cancel';
                cancelBtn.style.cssText = `
                    padding: 10px 20px;
                    border-radius: 20px;
                    border: 1px solid rgba(255,255,255,0.2);
                    background: transparent;
                    color: #fff;
                    font-family: 'Poppins', sans-serif;
                    font-size: 13px;
                    cursor: pointer;
                    font-weight: 500;
                    transition: all 0.2s;
                    flex: 1;
                `;
                cancelBtn.onmouseenter = () => cancelBtn.style.background = 'rgba(255,255,255,0.05)';
                cancelBtn.onmouseleave = () => cancelBtn.style.background = 'transparent';

                const confirmBtn = document.createElement('button');
                confirmBtn.textContent = 'Clear';
                confirmBtn.style.cssText = `
                    padding: 10px 20px;
                    border-radius: 20px;
                    border: none;
                    background: #FFF44F;
                    color: #000;
                    font-family: 'Poppins', sans-serif;
                    font-size: 13px;
                    cursor: pointer;
                    font-weight: 600;
                    transition: all 0.2s;
                    flex: 1;
                    box-shadow: 0 4px 12px rgba(255, 244, 79, 0.2);
                `;
                confirmBtn.onmouseenter = () => confirmBtn.style.transform = 'translateY(-1px)';
                confirmBtn.onmouseleave = () => confirmBtn.style.transform = 'translateY(0)';

                btnContainer.appendChild(cancelBtn);
                btnContainer.appendChild(confirmBtn);
                modal.appendChild(text);
                modal.appendChild(btnContainer);
                overlay.appendChild(modal);
                document.body.appendChild(overlay);

                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        overlay.style.opacity = '1';
                        modal.style.transform = 'scale(1)';
                    });
                });

                cancelBtn.onclick = () => close(false);
                confirmBtn.onclick = () => close(true);

                function close(val) {
                    overlay.style.opacity = '0';
                    modal.style.transform = 'scale(0.9)';
                    setTimeout(() => {
                        overlay.remove();
                        resolve(val);
                    }, 200);
                }
            });
        }

        // Custom Dialog Alert
        function customAlert(message, type = 'success') {
            return new Promise((resolve) => {
                const overlay = document.createElement('div');
                overlay.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100vw;
                    height: 100vh;
                    background: rgba(0, 0, 0, 0.7);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10000;
                    opacity: 0;
                    transition: opacity 0.2s ease;
                `;

                const modal = document.createElement('div');
                modal.style.cssText = `
                    background: #1C1C1C;
                    border: 1px solid ${type === 'success' ? 'rgba(255, 244, 79, 0.2)' : 'rgba(231, 76, 60, 0.2)'};
                    border-radius: 16px;
                    padding: 24px;
                    width: 90%;
                    max-width: 340px;
                    text-align: center;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
                    transform: scale(0.9);
                    transition: transform 0.2s ease;
                `;

                const text = document.createElement('p');
                text.textContent = message;
                text.style.cssText = `
                    font-size: 15px;
                    color: #fff;
                    margin-bottom: 20px;
                    font-family: 'Poppins', sans-serif;
                    line-height: 1.5;
                `;

                const okBtn = document.createElement('button');
                okBtn.textContent = 'OK';
                okBtn.style.cssText = `
                    padding: 8px 24px;
                    border-radius: 20px;
                    border: none;
                    background: ${type === 'success' ? '#FFF44F' : '#e74c3c'};
                    color: ${type === 'success' ? '#000' : '#fff'};
                    font-family: 'Poppins', sans-serif;
                    font-size: 13px;
                    cursor: pointer;
                    font-weight: 600;
                    transition: all 0.2s;
                `;

                modal.appendChild(text);
                modal.appendChild(okBtn);
                overlay.appendChild(modal);
                document.body.appendChild(overlay);

                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        overlay.style.opacity = '1';
                        modal.style.transform = 'scale(1)';
                    });
                });

                okBtn.onclick = () => {
                    overlay.style.opacity = '0';
                    modal.style.transform = 'scale(0.9)';
                    setTimeout(() => {
                        overlay.remove();
                        resolve();
                    }, 200);
                };
            });
        }

        function filterTab(el) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            el.classList.add('active');

            const type = el.dataset.type;
            const cards = document.querySelectorAll('.notification');
            const emptyFiltered = document.getElementById('emptyFiltered');
            let visibleCount = 0;

            cards.forEach(card => {
                if (type === 'all' || card.dataset.type === type) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (emptyFiltered) {
                emptyFiltered.style.display = visibleCount === 0 ? 'block' : 'none';
            }
        }

        async function clearAllNotifications() {
            const confirmed = await customConfirm("Are you sure you want to clear all notifications?");
            if (!confirmed) return;

            try {
                const response = await fetch('Notification.php?action=clear', {
                    method: 'POST'
                });
                const data = await response.json();
                if (data.success) {
                    const cards = document.querySelectorAll('.notification');
                    cards.forEach(card => card.classList.add('fade-out'));

                    setTimeout(() => {
                        const container = document.getElementById('notifContainer');
                        if (container) {
                            container.innerHTML = `
                                <div class="empty" id="emptyState">
                                    <span class="material-symbols-outlined">notifications_off</span>
                                    <p>No notifications yet</p>
                                </div>
                            `;
                        }
                        const clearBtn = document.getElementById('clearAllBtn');
                        if (clearBtn) {
                            clearBtn.style.display = 'none';
                        }
                        const emptyFiltered = document.getElementById('emptyFiltered');
                        if (emptyFiltered) {
                            emptyFiltered.style.display = 'none';
                        }
                    }, 300);
                } else {
                    await customAlert('Failed to clear notifications.', 'error');
                }
            } catch (error) {
                console.error('Error clearing notifications:', error);
                await customAlert('An error occurred while clearing notifications.', 'error');
            }
        }
    </script>
</body>

</html>
