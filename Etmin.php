<?php
session_start();

// Ensure only logged-in administrators can access this dashboard
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
  header("Location: homepage.php");
  exit;
}

$adminName = htmlspecialchars($_SESSION['name'] ?? 'Administrator');
$adminUsername = htmlspecialchars($_SESSION['username'] ?? 'admin');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AUchive Administrative Portal</title>

  <!-- Preconnect and Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Bitter&family=Lora&family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />

  <!-- Admin Portal CSS Styling -->
  <link rel="stylesheet" href="src/Admin/CSS/style_Etmin.css">

  <!-- Chart.js CDN for Analytics rendering -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- Main admin controller -->
  <script src="src/Core/JS/custom_alert.js"></script>
  <script src="src/Admin/JS/admin.js" defer></script>
</head>

<!-- SIDEBAR NAVIGATION -->
<aside class="sidebar">
  <div class="logo" style="gap: 0;">
    <span>AU</span><span>chive</span>
  </div>

  <nav class="menu">
    <a href="#" data-tab="dashboard">
      <span class="material-symbols-outlined">dashboard</span>
      Dashboard
    </a>
    <a href="#" data-tab="users">
      <span class="material-symbols-outlined">group</span>
      Manage Users
    </a>
    <a href="#" data-tab="stories">
      <span class="material-symbols-outlined">auto_stories</span>
      Manage Stories
    </a>
    <a href="#" data-tab="reports">
      <span class="material-symbols-outlined">report</span>
      Reports
    </a>
    <a href="#" data-tab="analytics">
      <span class="material-symbols-outlined">monitoring</span>
      Analytics
    </a>

    <!-- LOGOUT ACTIONS -->
    <a href="#" id="sidebarLogoutBtn" class="menu-logout">
      <span class="material-symbols-outlined">logout</span>
      Logout
    </a>
  </nav>
</aside>

<!-- MAIN VIEWPORT -->
<main class="main">

  <!-- TOP STATUS BAR -->
  <div class="topbar">
    <h1 id="pageTitle">Admin Dashboard</h1>
    <div class="admin-box">
      <span class="material-symbols-outlined" style="font-size:16px;">admin_panel_settings</span>
      <span><?= $adminName ?> (@<?= $adminUsername ?>)</span>
    </div>
  </div>

  <!-- EMPTY INITIAL STATE -->
  <section id="empty" class="tab-content active">
    <div class="empty-state" style="height: 60vh;">
      <span class="material-symbols-outlined" style="font-size: 80px; color: rgba(255,255,255,0.05); margin-bottom: 20px;">web_asset</span>
      <h2 style="color: var(--text-muted); font-weight: 500;">Select a menu item</h2>
      <p style="color: #666; margin-top: 8px;">Please choose an option from the sidebar to view data.</p>
    </div>
  </section>

  <!-- ────────────────────────────────────────────────────────
         SECTION 1: DASHBOARD TAB
         ──────────────────────────────────────────────────────── -->
  <section id="dashboard" class="tab-content">
    <!-- Quick Stats Cards -->
    <div class="cards">
      <div class="card">
        <h3>Total Users</h3>
        <h2 id="stat-total-users">-</h2>
        <span class="material-symbols-outlined icon-indicator">group</span>
      </div>
      <div class="card">
        <h3>Total Stories</h3>
        <h2 id="stat-total-stories">-</h2>
        <span class="material-symbols-outlined icon-indicator">auto_stories</span>
      </div>
      <div class="card">
        <h3>Pending Reviews</h3>
        <h2 id="stat-pending-reviews">-</h2>
        <span class="material-symbols-outlined icon-indicator">pending_actions</span>
      </div>
      <div class="card">
        <h3>Active Reports</h3>
        <h2 id="stat-total-reports">-</h2>
        <span class="material-symbols-outlined icon-indicator">warning</span>
      </div>
    </div>

    <!-- Recent Submissions List -->
    <div class="data-box">
      <div class="data-box-header">
        <h2>Recent Story Submissions</h2>
      </div>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Story Title</th>
              <th>Author</th>
              <th>Category</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody id="recent-stories-table-body">
            <!-- Dynamically populated via JS -->
            <tr>
              <td colspan="4" style="text-align:center; padding: 40px 0;">Loading dashboard statistics...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- ────────────────────────────────────────────────────────
         SECTION 2: MANAGE USERS TAB
         ──────────────────────────────────────────────────────── -->
  <section id="users" class="tab-content">
    <div class="data-box">
      <div class="controls-row">
        <div class="search-wrap">
          <span class="material-symbols-outlined">search</span>
          <input type="text" id="user-search-input" class="search-input"
            placeholder="Search users by name, username, or email...">
        </div>
      </div>

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Profile & Info</th>
              <th>Email Address</th>
              <th>Story Count</th>
              <th>Role</th>
              <th style="width: 150px; text-align: right;">Administrative Actions</th>
            </tr>
          </thead>
          <tbody id="users-table-body">
            <!-- Dynamically populated -->
          </tbody>
        </table>
      </div>

      <div class="pagination-row">
        <div class="pagination-info" id="users-pagination-info">Showing 0 of 0 users</div>
        <div class="pagination-btns">
          <button class="btn-page" id="users-prev-btn" disabled>Previous</button>
          <button class="btn-page" id="users-next-btn" disabled>Next</button>
        </div>
      </div>
    </div>

    <!-- Add New Admin Card -->
    <div class="data-box" style="margin-top: 25px;">
      <div class="data-box-header">
        <h2>Register New Admin Account</h2>
      </div>
      <form autocomplete="off" onsubmit="event.preventDefault();" class="settings-group">
        <div class="settings-item">
          <label for="new-admin-username">Username</label>
          <input type="text" id="new-admin-username" placeholder="e.g. admin2" autocomplete="off">
        </div>
        <div class="settings-item">
          <label for="new-admin-name">Full Name</label>
          <input type="text" id="new-admin-name" placeholder="e.g. John Doe" autocomplete="off">
        </div>
        <div class="settings-item">
          <label for="new-admin-email">Email</label>
          <input type="email" id="new-admin-email" placeholder="e.g. admin2@mail.com" autocomplete="off">
        </div>
        <div class="settings-item">
          <label for="new-admin-password">Password (min 8 chars)</label>
          <input type="password" id="new-admin-password" placeholder="••••••••" autocomplete="new-password">
        </div>
        <div style="padding: 15px 20px;">
          <button class="btn-primary" id="create-admin-btn" type="button" style="width: 100%; padding: 14px; border: none; border-radius: 8px; cursor: pointer; font-family: Poppins, sans-serif; font-weight: 600; font-size: 14px;">
            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 6px;">person_add</span>
            Create Admin Account
          </button>
        </div>
      </form>
    </div>
  </section>

  <!-- ────────────────────────────────────────────────────────
         SECTION 3: MANAGE STORIES TAB
         ──────────────────────────────────────────────────────── -->
  <section id="stories" class="tab-content">
    <div class="data-box">
      <div class="controls-row">
        <div class="search-wrap">
          <span class="material-symbols-outlined">search</span>
          <input type="text" id="story-search-input" class="search-input"
            placeholder="Search stories by title or author username...">
        </div>
        <select id="story-status-select" class="filter-select">
          <option value="">All Statuses</option>
          <option value="published">Published</option>
          <option value="draft">Drafts / Ongoing</option>
        </select>
      </div>

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Story & Category</th>
              <th>Author</th>
              <th>Created Date</th>
              <th>Status</th>
              <th>Views</th>
              <th>Likes</th>
              <th style="width: 150px; text-align: right;">Administrative Actions</th>
            </tr>
          </thead>
          <tbody id="stories-table-body">
            <!-- Dynamically populated -->
          </tbody>
        </table>
      </div>

      <div class="pagination-row">
        <div class="pagination-info" id="stories-pagination-info">Showing 0 of 0 stories</div>
        <div class="pagination-btns">
          <button class="btn-page" id="stories-prev-btn" disabled>Previous</button>
          <button class="btn-page" id="stories-next-btn" disabled>Next</button>
        </div>
      </div>
    </div>
  </section>

  <!-- ────────────────────────────────────────────────────────
         SECTION 4: REPORTS TAB
         ──────────────────────────────────────────────────────── -->
  <section id="reports" class="tab-content">
    <div class="data-box">
      <div class="tab-pill-row">
        <button class="tab-pill active" id="pill-pending" data-status="pending">Pending (<span
            id="count-pending">0</span>)</button>
        <button class="tab-pill" id="pill-resolved" data-status="resolved">Resolved (<span
            id="count-resolved">0</span>)</button>
        <button class="tab-pill" id="pill-dismissed" data-status="dismissed">Dismissed (<span
            id="count-dismissed">0</span>)</button>
      </div>

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Reason</th>
              <th>Reporter</th>
              <th>Reported Target</th>
              <th>Submitted Date</th>
              <th style="width: 150px; text-align: right;">Actions</th>
            </tr>
          </thead>
          <tbody id="reports-table-body">
            <!-- Dynamically populated -->
          </tbody>
        </table>
      </div>

      <div class="pagination-row">
        <div class="pagination-info" id="reports-pagination-info">Showing 0 of 0 reports</div>
        <div class="pagination-btns">
          <button class="btn-page" id="reports-prev-btn" disabled>Previous</button>
          <button class="btn-page" id="reports-next-btn" disabled>Next</button>
        </div>
      </div>
    </div>
  </section>

  <!-- ────────────────────────────────────────────────────────
         SECTION 5: ANALYTICS TAB
         ──────────────────────────────────────────────────────── -->
  <section id="analytics" class="tab-content">

    <!-- Statistics Aggregates -->
    <div class="cards" style="margin-bottom: 25px;">
      <div class="card">
        <h3>Total views</h3>
        <h2 id="analytics-views">0</h2>
        <span class="material-symbols-outlined icon-indicator">visibility</span>
      </div>
      <div class="card">
        <h3>Total Likes</h3>
        <h2 id="analytics-likes">0</h2>
        <span class="material-symbols-outlined icon-indicator">favorite</span>
      </div>
      <div class="card">
        <h3>Comments Posted</h3>
        <h2 id="analytics-comments">0</h2>
        <span class="material-symbols-outlined icon-indicator">comment</span>
      </div>
      <div class="card">
        <h3>User Follows</h3>
        <h2 id="analytics-follows">0</h2>
        <span class="material-symbols-outlined icon-indicator">person_add</span>
      </div>
    </div>

    <!-- Charts grid -->
    <div class="analytics-grid">
      <div class="data-box">
        <div class="data-box-header">
          <h2>Stories by Genre</h2>
        </div>
        <div class="chart-container">
          <canvas id="genreChart"></canvas>
        </div>
      </div>

      <div class="data-box">
        <div class="data-box-header">
          <h2>Top Platform Authors</h2>
        </div>
        <div class="top-authors-list" id="top-authors-container">
          <!-- Dynamically populated list -->
        </div>
      </div>
    </div>
  </section>

  <!-- ────────────────────────────────────────────────────────
         SECTION 6: SETTINGS TAB
         ──────────────────────────────────────────────────────── -->


</main>

<!-- ────────────────────────────────────────────────────────
       ADMIN CONFIRMATION OVERLAYS (MODALS)
       ──────────────────────────────────────────────────────── -->

<!-- Centralised Action Modal -->
<div class="modal-overlay" id="action-modal">
  <div class="modal-card">
    <div class="modal-header">
      <h3 id="modal-title">Administrative Action</h3>
      <button class="modal-close-btn" id="modal-close-btn">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>
    <div class="modal-body" id="modal-body-text">
      Are you sure you want to perform this administrator action?
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" id="modal-cancel-btn">Cancel</button>
      <button class="btn-primary" id="modal-confirm-btn">Confirm</button>
    </div>
  </div>
</div>

<!-- Detailed Report Description Modal -->
<div class="modal-overlay" id="report-modal">
  <div class="modal-card">
    <div class="modal-header">
      <h3>Violation Report Details</h3>
      <button class="modal-close-btn" id="report-close-btn">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>
    <div class="modal-body">
      <div style="margin-bottom: 15px;">
        <strong style="color:var(--accent-color);">Reason:</strong>
        <span id="report-view-reason" style="margin-left: 8px; font-weight:600;">-</span>
      </div>
      <div style="margin-bottom: 15px;">
        <strong style="color:var(--accent-color);">Description/Details:</strong>
        <p id="report-view-desc"
          style="margin-top: 5px; padding: 12px; background: rgba(255,255,255,0.03); border-radius:8px; border:1px solid var(--border-color);">
        </p>
      </div>
      <div>
        <strong style="color:var(--accent-color);">Reported Target:</strong>
        <div id="report-view-target" style="margin-top: 5px;">-</div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" id="report-dismiss-btn"
        style="border-color: var(--danger); color: var(--danger);">Dismiss Report</button>
      <button class="btn-primary" id="report-resolve-btn"
        style="background: var(--success); border-color: var(--success); color: white;">Resolve Report</button>
    </div>
  </div>
</div>

</body>

</html>
