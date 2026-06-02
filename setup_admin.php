<?php
// ============================================================
// setup_admin.php  –  One-time Admin Setup & DB Migration
// Run once via browser: http://localhost/Project/AUchive/setup_admin.php
// DELETE this file after use for security!
// ============================================================

require_once 'PHP/database.php';
$pdo = getDB();
$logs = [];
$errors = [];

// ── 1. Fix 'role' column to support 'banned' ────────────────
try {
    // Check current column type
    $colStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    $col = $colStmt->fetch();
    $colType = $col['Type'] ?? '';
    $logs[] = "Current role column type: <code>{$colType}</code>";

    // If ENUM, check if 'banned' is already a valid value
    if (stripos($colType, 'enum') !== false && stripos($colType, 'banned') === false) {
        // Alter column to add 'banned' value
        $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('user','admin','banned') NOT NULL DEFAULT 'user'");
        $logs[] = "✅ Column <code>role</code> updated → ENUM('user','admin','banned')";
    } elseif (stripos($colType, 'varchar') !== false || stripos($colType, 'text') !== false) {
        $logs[] = "ℹ️ Column <code>role</code> is VARCHAR/TEXT — 'banned' value already supported.";
    } elseif (stripos($colType, 'banned') !== false) {
        $logs[] = "ℹ️ Column <code>role</code> already has 'banned' in ENUM — no change needed.";
    } else {
        // Force set to VARCHAR to support any value
        $pdo->exec("ALTER TABLE users MODIFY COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user'");
        $logs[] = "✅ Column <code>role</code> converted to VARCHAR(20) for flexible role support.";
    }
} catch (PDOException $e) {
    $errors[] = "❌ Role column fix failed: " . $e->getMessage();
}

// ── 2. Ensure system_settings table exists ────────────────────
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `system_settings` (
            `setting_key`   varchar(100) NOT NULL,
            `setting_value` text DEFAULT NULL,
            PRIMARY KEY (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    $logs[] = "✅ Table <code>system_settings</code> ready.";
} catch (PDOException $e) {
    $errors[] = "❌ system_settings table: " . $e->getMessage();
}

// ── 3. Show existing admin accounts ──────────────────────────
try {
    $admins = $pdo->query("SELECT user_id, username, name, email FROM users WHERE role = 'admin'")->fetchAll();
    if (count($admins) > 0) {
        $logs[] = "ℹ️ Existing admin accounts (" . count($admins) . "):";
        foreach ($admins as $a) {
            $logs[] = "&nbsp;&nbsp;&nbsp;→ <strong>#{$a['user_id']}</strong> @{$a['username']} ({$a['email']})";
        }
    } else {
        $logs[] = "⚠️ No admin accounts found in database.";
    }
} catch (PDOException $e) {
    $errors[] = "❌ Could not query admins: " . $e->getMessage();
}

// ── 4. Create admin account (from form or defaults) ──────────
$createMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
    $username = trim($_POST['username'] ?? '');
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($name) || empty($email) || empty($password)) {
        $createMsg = '<p class="err">❌ All fields are required.</p>';
    } elseif (strlen($password) < 8) {
        $createMsg = '<p class="err">❌ Password must be at least 8 characters.</p>';
    } else {
        try {
            // Check duplicate
            $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ? OR username = ?");
            $check->execute([$email, $username]);
            if ($check->fetch()) {
                $createMsg = '<p class="err">❌ Email or username already exists. Try upgrading an existing user to admin instead.</p>';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (username, name, email, password, role) VALUES (?, ?, ?, ?, 'admin')");
                $stmt->execute([$username, $name, $email, $hash]);
                $newId = $pdo->lastInsertId();
                $createMsg = "<p class='ok'>✅ Admin account created! ID: <strong>#{$newId}</strong> — username: <strong>@{$username}</strong>. You can now login at <a href='homepage.php'>homepage.php</a>.</p>";
                $logs[] = "✅ New admin created: @{$username} (ID #{$newId})";
            }
        } catch (PDOException $e) {
            $createMsg = '<p class="err">❌ DB Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
    }
}

// ── 5. Upgrade existing user to admin (from form) ─────────────
$upgradeMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upgrade_admin'])) {
    $uid = (int)($_POST['upgrade_user_id'] ?? 0);
    if ($uid > 0) {
        try {
            $pdo->prepare("UPDATE users SET role = 'admin' WHERE user_id = ?")->execute([$uid]);
            $upgradeMsg = "<p class='ok'>✅ User ID #{$uid} has been upgraded to admin!</p>";
        } catch (PDOException $e) {
            $upgradeMsg = '<p class="err">❌ ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
    }
}

// ── 6. Fetch all non-admin users for upgrade dropdown ─────────
$allUsers = [];
try {
    $allUsers = $pdo->query("SELECT user_id, username, name, email, role FROM users WHERE role != 'admin' ORDER BY user_id DESC LIMIT 50")->fetchAll();
} catch (PDOException $e) {}

// ── 7. Show current banned users ─────────────────────────────
$bannedUsers = [];
try {
    $bannedUsers = $pdo->query("SELECT user_id, username, name, email FROM users WHERE role = 'banned'")->fetchAll();
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AUchive Admin Setup</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #0d0d0d; color: #e0e0e0; margin: 0; padding: 30px; }
        h1 { color: #FFF44F; font-size: 26px; margin-bottom: 4px; }
        h2 { color: #FFF44F; font-size: 18px; border-bottom: 1px solid rgba(255,244,79,0.2); padding-bottom: 8px; margin-top: 30px; }
        .card { background: #1a1a1a; border: 1px solid rgba(255,244,79,0.15); border-radius: 12px; padding: 24px; margin-bottom: 24px; }
        .log-item { padding: 5px 0; font-size: 14px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .ok { color: #2ecc71; }
        .err { color: #e74c3c; }
        input, select { width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.12); background: #252525; color: #fff; font-size: 14px; margin-bottom: 12px; outline: none; }
        input:focus, select:focus { border-color: #FFF44F; }
        label { font-size: 13px; color: rgba(255,255,255,0.55); margin-bottom: 4px; display: block; }
        .btn { padding: 12px 24px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; width: 100%; margin-top: 4px; }
        .btn-primary { background: #FFF44F; color: #111; }
        .btn-primary:hover { background: #ffe800; }
        .btn-danger { background: #e74c3c; color: #fff; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { color: rgba(255,255,255,0.45); font-weight: 500; text-align: left; padding: 8px 12px; border-bottom: 1px solid rgba(255,255,255,0.08); }
        td { padding: 8px 12px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .badge { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-admin { background: rgba(255,244,79,0.15); color: #FFF44F; }
        .badge-banned { background: rgba(231,76,60,0.15); color: #e74c3c; }
        .badge-user { background: rgba(46,204,113,0.12); color: #2ecc71; }
        .warn-box { background: rgba(231,76,60,0.08); border: 1px solid rgba(231,76,60,0.3); border-radius: 8px; padding: 14px 18px; font-size: 13px; color: #e74c3c; margin-top: 16px; }
    </style>
</head>
<body>

<h1>🛡️ AUchive — Admin Setup & Database Migration</h1>
<p style="color:rgba(255,255,255,0.45); font-size:13px; margin-bottom:24px;">
    Run database fixes and create admin accounts. <strong style="color:#e74c3c;">Delete this file after use!</strong>
</p>

<!-- Migration Logs -->
<div class="card">
    <h2>📋 Migration Logs</h2>
    <?php foreach ($logs as $log): ?>
        <div class="log-item"><?= $log ?></div>
    <?php endforeach; ?>
    <?php foreach ($errors as $err): ?>
        <div class="log-item err"><?= $err ?></div>
    <?php endforeach; ?>
</div>

<div class="row">

    <!-- Create New Admin -->
    <div class="card">
        <h2>➕ Create New Admin Account</h2>
        <?= $createMsg ?>
        <form method="POST">
            <label>Username</label>
            <input type="text" name="username" placeholder="e.g. admin_auchive" required>
            <label>Full Name</label>
            <input type="text" name="name" placeholder="e.g. AUchive Admin" required>
            <label>Email</label>
            <input type="email" name="email" placeholder="admin@auchive.com" required>
            <label>Password (min. 8 chars)</label>
            <input type="password" name="password" placeholder="••••••••" required>
            <button type="submit" name="create_admin" class="btn btn-primary">Create Admin Account</button>
        </form>
    </div>

    <!-- Upgrade Existing User -->
    <div class="card">
        <h2>⬆️ Upgrade Existing User to Admin</h2>
        <?= $upgradeMsg ?>
        <?php if (count($allUsers) > 0): ?>
        <form method="POST">
            <label>Select User to Upgrade</label>
            <select name="upgrade_user_id">
                <?php foreach ($allUsers as $u): ?>
                    <option value="<?= $u['user_id'] ?>">
                        #<?= $u['user_id'] ?> @<?= htmlspecialchars($u['username']) ?> — <?= htmlspecialchars($u['email']) ?> [<?= $u['role'] ?>]
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="upgrade_admin" class="btn btn-primary" style="background:#3498db;">Upgrade to Admin</button>
        </form>
        <?php else: ?>
            <p style="color:rgba(255,255,255,0.4); font-size:13px;">No non-admin users found in database.</p>
        <?php endif; ?>
    </div>

</div>

<!-- Current Banned Users -->
<div class="card">
    <h2>🚫 Currently Banned Users (<?= count($bannedUsers) ?>)</h2>
    <?php if (count($bannedUsers) > 0): ?>
    <table>
        <thead>
            <tr><th>ID</th><th>Username</th><th>Name</th><th>Email</th></tr>
        </thead>
        <tbody>
            <?php foreach ($bannedUsers as $b): ?>
            <tr>
                <td>#<?= $b['user_id'] ?></td>
                <td>@<?= htmlspecialchars($b['username']) ?></td>
                <td><?= htmlspecialchars($b['name']) ?></td>
                <td><?= htmlspecialchars($b['email']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p style="color:rgba(255,255,255,0.4); font-size:13px;">No banned users currently.</p>
    <?php endif; ?>
</div>

<!-- All Users Table -->
<div class="card">
    <h2>👥 All Users (max 50)</h2>
    <table>
        <thead>
            <tr><th>ID</th><th>Username</th><th>Name</th><th>Email</th><th>Role</th></tr>
        </thead>
        <tbody>
            <?php
            $allUsersAll = [];
            try {
                $allUsersAll = $pdo->query("SELECT user_id, username, name, email, role FROM users ORDER BY user_id DESC LIMIT 50")->fetchAll();
            } catch (PDOException $e) {}
            foreach ($allUsersAll as $u): ?>
            <tr>
                <td>#<?= $u['user_id'] ?></td>
                <td>@<?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['name']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="badge badge-<?= $u['role'] ?>"><?= strtoupper($u['role']) ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="warn-box">
    ⚠️ <strong>SECURITY WARNING:</strong> Delete or rename this file after setup!<br>
    <code>c:\xampp\htdocs\Project\AUchive\setup_admin.php</code>
</div>

</body>
</html>
