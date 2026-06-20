<?php
// ============================================================
// admin_debug.php  –  System Debugging Control Center
// ============================================================
session_start();

// Ensure only logged-in administrators can access this page
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
  header("Location: homepage.php");
  exit;
}

require_once 'src/Core/PHP/database.php';
$pdo = getDB();

// Auto-create system_settings table
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `system_settings` (
        `setting_key`   varchar(100) NOT NULL,
        `setting_value` text         DEFAULT NULL,
        PRIMARY KEY (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Insert defaults if not present
$defaults = [
    'site_name'      => 'AUchive Fanfiction Platform',
    'system_warning' => '',
    'server_mode'    => 'online',
    'engine_version' => 'v1.4.0-production'
];
foreach ($defaults as $k => $v) {
    $chk = $pdo->prepare("SELECT COUNT(*) FROM system_settings WHERE setting_key = ?");
    $chk->execute([$k]);
    if ((int)$chk->fetchColumn() === 0) {
        $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)")->execute([$k, $v]);
    }
}

// Fetch current settings
$rows = $pdo->query("SELECT setting_key, setting_value FROM system_settings")->fetchAll();
$settings = [];
foreach ($rows as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Gather system stats for monitoring
$totalUsers   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role NOT IN ('admin')")->fetchColumn();
$totalStories = (int)$pdo->query("SELECT COUNT(*) FROM stories")->fetchColumn();
$totalChapters = (int)$pdo->query("SELECT COUNT(*) FROM chapters")->fetchColumn();
$totalComments = (int)$pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();

// Recent activity log (last 10 notifications)
$recentActivity = $pdo->query("SELECT n.title, n.body, n.created_at, u.username FROM notifications n LEFT JOIN users u ON n.user_id = u.user_id ORDER BY n.created_at DESC LIMIT 10")->fetchAll();

// Suspended/banned users
$bannedUsers = $pdo->query("SELECT user_id, username, name, email FROM users WHERE role = 'banned'")->fetchAll();

$adminName = htmlspecialchars($_SESSION['name'] ?? 'Administrator');
$adminUsername = htmlspecialchars($_SESSION['username'] ?? 'admin');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>System Debugging — AUchive Admin</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Bitter&family=Lora&family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />

  <link rel="stylesheet" href="src/Admin/CSS/style_Etmin.css">
  <link rel="stylesheet" href="src/Admin/CSS/style_debug.css">
  <script src="src/Core/JS/custom_alert.js"></script>
</head>

<body>

<!-- SIDEBAR NAVIGATION (matches Etmin.php) -->
<aside class="sidebar">
  <div class="logo" style="gap: 0;">
    <span>AU</span><span>chive</span>
  </div>

  <nav class="menu">
    <a href="Etmin.php">
      <span class="material-symbols-outlined">dashboard</span>
      Dashboard
    </a>
    <a href="Etmin.php">
      <span class="material-symbols-outlined">group</span>
      Manage Users
    </a>
    <a href="Etmin.php">
      <span class="material-symbols-outlined">auto_stories</span>
      Manage Stories
    </a>
    <a href="Etmin.php">
      <span class="material-symbols-outlined">report</span>
      Reports
    </a>
    <a href="Etmin.php">
      <span class="material-symbols-outlined">monitoring</span>
      Analytics
    </a>
    <a href="Etmin.php">
      <span class="material-symbols-outlined">settings</span>
      Settings
    </a>

    <!-- SYSTEM DEBUGGING LINK (ACTIVE) -->
    <a href="admin_debug.php" class="active">
      <span class="material-symbols-outlined">bug_report</span>
      System Debugging
    </a>

    <!-- LOGOUT -->
    <a href="#" id="sidebarLogoutBtn" style="color:#ff7675;">
      <span class="material-symbols-outlined">logout</span>
      Logout
    </a>
  </nav>
</aside>

<!-- MAIN CONTENT -->
<main class="main">

  <!-- TOP BAR -->
  <div class="topbar">
    <h1>System Debugging Control Center</h1>
    <div class="admin-box">
      <span class="material-symbols-outlined" style="font-size:16px;">admin_panel_settings</span>
      <span><?= $adminName ?> (@<?= $adminUsername ?>)</span>
    </div>
  </div>

  <!-- ═══════════════════════════════════════════════════════
       SECTION 1: MONITOR SYSTEM ACTIVITY
       ═══════════════════════════════════════════════════════ -->
  <section class="debug-section">
    <div class="debug-header">
      <span class="material-symbols-outlined">monitor_heart</span>
      <h2>Monitor System Activity</h2>
    </div>

    <!-- System Health Cards -->
    <div class="cards" style="margin-bottom: 25px;">
      <div class="card">
        <h3>Database Status</h3>
        <h2 style="color: var(--success);">Connected</h2>
        <span class="material-symbols-outlined icon-indicator">database</span>
      </div>
      <div class="card">
        <h3>Server Mode</h3>
        <h2 id="health-server-mode" style="color: var(--accent-color);"><?= htmlspecialchars(ucfirst($settings['server_mode'] ?? 'online')) ?></h2>
        <span class="material-symbols-outlined icon-indicator">dns</span>
      </div>
      <div class="card">
        <h3>PHP Memory</h3>
        <h2><?= round(memory_get_usage() / 1024 / 1024, 2) ?> MB</h2>
        <span class="material-symbols-outlined icon-indicator">memory</span>
      </div>
      <div class="card">
        <h3>PHP Version</h3>
        <h2><?= phpversion() ?></h2>
        <span class="material-symbols-outlined icon-indicator">terminal</span>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="cards">
      <div class="card">
        <h3>Total Users</h3>
        <h2><?= number_format($totalUsers) ?></h2>
        <span class="material-symbols-outlined icon-indicator">group</span>
      </div>
      <div class="card">
        <h3>Total Stories</h3>
        <h2><?= number_format($totalStories) ?></h2>
        <span class="material-symbols-outlined icon-indicator">auto_stories</span>
      </div>
      <div class="card">
        <h3>Total Chapters</h3>
        <h2><?= number_format($totalChapters) ?></h2>
        <span class="material-symbols-outlined icon-indicator">format_list_numbered</span>
      </div>
      <div class="card">
        <h3>Total Comments</h3>
        <h2><?= number_format($totalComments) ?></h2>
        <span class="material-symbols-outlined icon-indicator">comment</span>
      </div>
    </div>

    <!-- Activity Log -->
    <div class="data-box" style="margin-top: 25px;">
      <div class="data-box-header">
        <h2>Recent System Activity Log</h2>
      </div>
      <div class="activity-log" id="activity-log">
        <?php if (empty($recentActivity)): ?>
          <div class="log-empty">No recent activity recorded.</div>
        <?php else: ?>
          <?php foreach ($recentActivity as $act): ?>
            <div class="log-entry">
              <div class="log-icon">
                <span class="material-symbols-outlined">notifications_active</span>
              </div>
              <div class="log-content">
                <strong><?= htmlspecialchars($act['title']) ?></strong>
                <p><?= htmlspecialchars($act['body']) ?></p>
                <span class="log-meta">@<?= htmlspecialchars($act['username'] ?? 'system') ?> · <?= date('M d, Y H:i', strtotime($act['created_at'])) ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- ═══════════════════════════════════════════════════════
       SECTION 2: WRITE SYSTEM WARNING
       ═══════════════════════════════════════════════════════ -->
  <section class="debug-section">
    <div class="debug-header">
      <span class="material-symbols-outlined">warning</span>
      <h2>Write System Warning</h2>
    </div>
    <div class="data-box">
      <div class="data-box-header">
        <h2>Global Warning Banner</h2>
        <p style="color:var(--text-muted); font-size:13px; margin-top:4px;">This warning will be displayed at the top of the Homepage and Search Results page for all users.</p>
      </div>
      <div class="settings-group">
        <div class="settings-item">
          <label for="warning-text">Warning Message</label>
          <textarea id="warning-text" rows="3" style="width:100%;box-sizing:border-box;padding:12px;border-radius:8px;border:1px solid var(--border-color);background:var(--surface-color);color:var(--text-primary);font-family:Poppins,sans-serif;font-size:14px;resize:vertical;"
            placeholder="e.g. Scheduled maintenance from 2 PM to 4 PM..."><?= htmlspecialchars($settings['system_warning'] ?? '') ?></textarea>
        </div>
        <div style="padding: 0 20px 20px; display: flex; gap: 12px;">
          <button class="btn-primary" id="publish-warning-btn" style="flex:1; padding:14px; border:none; border-radius:8px; cursor:pointer; font-family:Poppins,sans-serif; font-weight:600; font-size:14px;">
            <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;margin-right:6px;">campaign</span>
            Publish Warning
          </button>
          <button class="btn-secondary-outline" id="clear-warning-btn" style="padding:14px 24px; border-radius:8px; cursor:pointer; font-family:Poppins,sans-serif; font-weight:600; font-size:14px; background:transparent; border:1px solid var(--danger); color:var(--danger);">
            <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;margin-right:6px;">close</span>
            Clear
          </button>
        </div>
      </div>
    </div>
  </section>

  <!-- ═══════════════════════════════════════════════════════
       SECTION 3: MANAGE ACCOUNT SYSTEMS (Suspended Accounts)
       ═══════════════════════════════════════════════════════ -->
  <section class="debug-section">
    <div class="debug-header">
      <span class="material-symbols-outlined">manage_accounts</span>
      <h2>Manage Account Systems</h2>
    </div>
    <div class="data-box">
      <div class="data-box-header">
        <h2>Suspended / Banned Accounts</h2>
      </div>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>User ID</th>
              <th>Username</th>
              <th>Full Name</th>
              <th>Email</th>
              <th style="width:120px;text-align:right;">Actions</th>
            </tr>
          </thead>
          <tbody id="banned-users-table">
            <?php if (empty($bannedUsers)): ?>
              <tr><td colspan="5" style="text-align:center;padding:30px 0;">No suspended accounts found.</td></tr>
            <?php else: ?>
              <?php foreach ($bannedUsers as $bu): ?>
                <tr>
                  <td>#<?= $bu['user_id'] ?></td>
                  <td>@<?= htmlspecialchars($bu['username']) ?></td>
                  <td><?= htmlspecialchars($bu['name']) ?></td>
                  <td><?= htmlspecialchars($bu['email']) ?></td>
                  <td>
                    <div class="btn-action-row" style="justify-content:flex-end;">
                      <button class="btn-icon unban-debug" title="Unban User" data-id="<?= $bu['user_id'] ?>" data-name="<?= htmlspecialchars($bu['username']) ?>">
                        <span class="material-symbols-outlined">verified_user</span>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- ═══════════════════════════════════════════════════════
       SECTION 4: UPDATE SYSTEM SETTINGS
       ═══════════════════════════════════════════════════════ -->
  <section class="debug-section">
    <div class="debug-header">
      <span class="material-symbols-outlined">tune</span>
      <h2>Update System Settings</h2>
    </div>
    <div class="data-box">
      <div class="data-box-header">
        <h2>Platform Configuration</h2>
      </div>
      <div class="settings-group">
        <div class="settings-item">
          <label for="set-site-name">Site Name</label>
          <input type="text" id="set-site-name" value="<?= htmlspecialchars($settings['site_name'] ?? 'AUchive Fanfiction Platform') ?>">
        </div>
        <div class="settings-item">
          <label for="set-engine-version">Engine Version</label>
          <input type="text" id="set-engine-version" value="<?= htmlspecialchars($settings['engine_version'] ?? 'v1.4.0-production') ?>">
        </div>
        <div class="settings-item">
          <label for="set-server-mode">Server Mode</label>
          <select id="set-server-mode" style="width:100%;padding:12px;border-radius:8px;border:1px solid var(--border-color);background:var(--surface-color);color:var(--text-primary);font-family:Poppins,sans-serif;font-size:14px;">
            <option value="online" <?= ($settings['server_mode'] ?? '') === 'online' ? 'selected' : '' ?>>Online (Active)</option>
            <option value="maintenance" <?= ($settings['server_mode'] ?? '') === 'maintenance' ? 'selected' : '' ?>>Maintenance Mode</option>
            <option value="offline" <?= ($settings['server_mode'] ?? '') === 'offline' ? 'selected' : '' ?>>Offline</option>
          </select>
        </div>
        <div style="padding: 15px 20px;">
          <button class="btn-primary" id="save-settings-btn" style="width:100%; padding:14px; border:none; border-radius:8px; cursor:pointer; font-family:Poppins,sans-serif; font-weight:600; font-size:14px;">
            <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;margin-right:6px;">save</span>
            Save Settings
          </button>
        </div>
      </div>
    </div>
  </section>

  <!-- ═══════════════════════════════════════════════════════
       SECTION 5: DAILY ACTIVITY REPORT
       ═══════════════════════════════════════════════════════ -->
  <section class="debug-section">
    <div class="debug-header">
      <span class="material-symbols-outlined">summarize</span>
      <h2>Daily Activity Report</h2>
    </div>
    <div class="data-box">
      <div class="data-box-header">
        <h2>Generate Report</h2>
        <p style="color:var(--text-muted); font-size:13px; margin-top:4px;">Auto-generate a summary of today's platform statistics.</p>
      </div>
      <div style="padding: 20px;">
        <button class="btn-primary" id="generate-report-btn" style="padding:14px 28px; border:none; border-radius:8px; cursor:pointer; font-family:Poppins,sans-serif; font-weight:600; font-size:14px;">
          <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;margin-right:6px;">description</span>
          Generate Report
        </button>
      </div>
      <div id="report-output" style="display:none; margin: 0 20px 20px; padding: 20px; background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: 10px;">
        <pre id="report-content" style="white-space:pre-wrap; font-family:'Courier New',monospace; font-size:13px; color:var(--text-primary); line-height:1.8; margin:0;"></pre>
      </div>
    </div>
  </section>

</main>

<!-- ── Confirmation Modal (reused from Etmin) ─── -->
<div class="modal-overlay" id="action-modal">
  <div class="modal-card">
    <div class="modal-header">
      <h3 id="modal-title">Confirm Action</h3>
      <button class="modal-close-btn" id="modal-close-btn">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>
    <div class="modal-body" id="modal-body-text">
      Are you sure?
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" id="modal-cancel-btn">Cancel</button>
      <button class="btn-primary" id="modal-confirm-btn">Confirm</button>
    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {

  // ── Toast ─────────────────────────────────────────────
  function showToast(message, type = "success") {
    const existing = document.getElementById("debugToast");
    if (existing) existing.remove();
    const toast = document.createElement("div");
    toast.id = "debugToast";
    toast.textContent = message;
    toast.style.cssText = `
      position:fixed;top:24px;right:24px;z-index:9999;
      background:${type === "success" ? "#2ecc71" : "#e74c3c"};
      color:#fff;padding:14px 22px;border-radius:8px;font-size:14px;font-weight:500;
      box-shadow:0 8px 32px rgba(0,0,0,0.3);opacity:0;transform:translateY(-10px);
      transition:opacity 0.3s ease,transform 0.3s ease;
    `;
    document.body.appendChild(toast);
    requestAnimationFrame(() => { requestAnimationFrame(() => { toast.style.opacity="1"; toast.style.transform="translateY(0)"; }); });
    setTimeout(() => { toast.style.opacity="0"; toast.style.transform="translateY(-10px)"; setTimeout(() => toast.remove(), 350); }, 3000);
  }

  // ── API helper ────────────────────────────────────────
  async function apiFetch(url, options = {}) {
    try {
      const response = await fetch(url, options);
      if (!response.ok) {
        if (response.status === 403) { showToast("Unauthorized", "error"); return null; }
        throw new Error("HTTP Error: " + response.status);
      }
      return await response.json();
    } catch (err) {
      showToast(err.message || "Server error", "error");
      return null;
    }
  }

  // ── Publish Warning ───────────────────────────────────
  document.getElementById("publish-warning-btn").addEventListener("click", async function () {
    const warning = document.getElementById("warning-text").value.trim();
    if (!warning) { showToast("Please enter a warning message.", "error"); return; }
    this.disabled = true; this.textContent = "Publishing...";
    const res = await apiFetch("src/Admin/PHP/admin_action.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ action: "update_system_warning", warning: warning })
    });
    this.disabled = false;
    this.innerHTML = `<span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;margin-right:6px;">campaign</span> Publish Warning`;
    if (res && res.success) showToast(res.message);
  });

  // ── Clear Warning ─────────────────────────────────────
  document.getElementById("clear-warning-btn").addEventListener("click", async function () {
    this.disabled = true;
    const res = await apiFetch("src/Admin/PHP/admin_action.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ action: "update_system_warning", warning: "" })
    });
    this.disabled = false;
    if (res && res.success) {
      document.getElementById("warning-text").value = "";
      showToast("Warning cleared.");
    }
  });

  // ── Save Settings ─────────────────────────────────────
  document.getElementById("save-settings-btn").addEventListener("click", async function () {
    const siteName = document.getElementById("set-site-name").value.trim();
    const engineVersion = document.getElementById("set-engine-version").value.trim();
    const serverMode = document.getElementById("set-server-mode").value;
    this.disabled = true; this.textContent = "Saving...";
    const res = await apiFetch("src/Admin/PHP/admin_action.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ action: "update_system_settings", site_name: siteName, engine_version: engineVersion, server_mode: serverMode })
    });
    this.disabled = false;
    this.innerHTML = `<span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;margin-right:6px;">save</span> Save Settings`;
    if (res && res.success) {
      showToast(res.message);
      document.getElementById("health-server-mode").textContent = serverMode.charAt(0).toUpperCase() + serverMode.slice(1);
    }
  });

  // ── Unban buttons ─────────────────────────────────────
  document.querySelectorAll(".unban-debug").forEach(btn => {
    btn.addEventListener("click", async function () {
      const uid = this.dataset.id;
      const uname = this.dataset.name;
      const confirmed = await customConfirm("Unban user @" + uname + "?");
      if (!confirmed) return;
      const res = await apiFetch("src/Admin/PHP/admin_action.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "unban_user", user_id: parseInt(uid) })
      });
      if (res && res.success) {
        showToast(res.message);
        this.closest("tr").remove();
      }
    });
  });

  // ── Generate Report ───────────────────────────────────
  document.getElementById("generate-report-btn").addEventListener("click", async function () {
    this.disabled = true; this.textContent = "Generating...";

    const stats = await apiFetch("src/Admin/PHP/admin_get_stats.php");
    const analytics = await apiFetch("src/Admin/PHP/admin_get_analytics.php");
    const settings = await apiFetch("src/Admin/PHP/get_system_settings.php");

    this.disabled = false;
    this.innerHTML = `<span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;margin-right:6px;">description</span> Generate Report`;

    const now = new Date();
    let report = `════════════════════════════════════════\n`;
    report += `  AUchive Daily Activity Report\n`;
    report += `  Generated: ${now.toLocaleString()}\n`;
    report += `════════════════════════════════════════\n\n`;

    if (settings && settings.success) {
      report += `► Platform Settings\n`;
      report += `  Site Name     : ${settings.settings.site_name || '-'}\n`;
      report += `  Server Mode   : ${settings.settings.server_mode || '-'}\n`;
      report += `  Engine Version: ${settings.settings.engine_version || '-'}\n`;
      report += `  Active Warning: ${settings.settings.system_warning || 'None'}\n\n`;
    }

    if (stats && stats.success) {
      report += `► Platform Statistics\n`;
      report += `  Total Users       : ${stats.stats.total_users}\n`;
      report += `  Total Stories      : ${stats.stats.total_stories}\n`;
      report += `  Pending Reviews    : ${stats.stats.pending_review}\n`;
      report += `  Active Reports     : ${stats.stats.total_reports}\n`;
      report += `  Banned Users       : ${stats.stats.banned_users || 0}\n\n`;
    }

    if (analytics && analytics.success) {
      report += `► Engagement Analytics\n`;
      report += `  Total Views    : ${analytics.total_views}\n`;
      report += `  Total Likes    : ${analytics.total_likes}\n`;
      report += `  Total Comments : ${analytics.total_comments}\n`;
      report += `  Total Follows  : ${analytics.total_follows}\n\n`;

      if (analytics.top_authors && analytics.top_authors.length > 0) {
        report += `► Top Authors\n`;
        analytics.top_authors.forEach((a, i) => {
          report += `  #${i+1} ${a.username} — ${a.story_count} stories, ${a.total_views} views, ${a.total_likes} likes\n`;
        });
        report += `\n`;
      }
    }

    report += `════════════════════════════════════════\n`;
    report += `  End of Report\n`;
    report += `════════════════════════════════════════`;

    document.getElementById("report-content").textContent = report;
    document.getElementById("report-output").style.display = "block";
  });

  // ── Logout ────────────────────────────────────────────
  const logoutBtn = document.getElementById("sidebarLogoutBtn");
  if (logoutBtn) {
    logoutBtn.addEventListener("click", async function (e) {
      e.preventDefault();
      const confirmed = await customConfirm("Logout from admin panel?");
      if (!confirmed) return;
      try {
        await fetch("src/User/PHP/logout.php", { method: "POST", headers: { "X-Requested-With": "XMLHttpRequest" } });
      } finally {
        window.location.href = "homepage.php?loggedout=1";
      }
    });
  }

});
</script>

</body>
</html>


