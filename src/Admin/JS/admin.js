/**
 * ============================================================
 * admin.js – Core Controller for AUchive Admin Portal
 * ============================================================
 */

document.addEventListener("DOMContentLoaded", function () {
  
  // ── Unified Application State ─────────────────────────────
  const state = {
    activeTab: "empty",
    
    // Pagination & Search States
    users: { page: 1, limit: 15, total: 0, search: "" },
    stories: { page: 1, limit: 15, total: 0, search: "", status: "" },
    reports: { page: 1, limit: 15, total: 0, status: "pending" },
    
    // UI References / Chart Instances
    genreChart: null,
    pendingAction: null // For confirmation modal
  };

  // ── Element Registries ──────────────────────────────────────
  const el = {
    // Title
    pageTitle: document.getElementById("pageTitle"),
    
    // Tabs & Sidebar
    menuLinks: document.querySelectorAll(".menu a[data-tab]"),
    tabSections: document.querySelectorAll(".tab-content"),
    sidebarLogoutBtn: document.getElementById("sidebarLogoutBtn"),
    
    // Dashboard Stats
    statTotalUsers: document.getElementById("stat-total-users"),
    statTotalStories: document.getElementById("stat-total-stories"),
    statPendingReviews: document.getElementById("stat-pending-reviews"),
    statTotalReports: document.getElementById("stat-total-reports"),
    recentStoriesTable: document.getElementById("recent-stories-table-body"),
    
    // Users View
    usersTable: document.getElementById("users-table-body"),
    userSearch: document.getElementById("user-search-input"),
    usersPaginationInfo: document.getElementById("users-pagination-info"),
    usersPrevBtn: document.getElementById("users-prev-btn"),
    usersNextBtn: document.getElementById("users-next-btn"),
    
    // Stories View
    storiesTable: document.getElementById("stories-table-body"),
    storySearch: document.getElementById("story-search-input"),
    storyStatus: document.getElementById("story-status-select"),
    storiesPaginationInfo: document.getElementById("stories-pagination-info"),
    storiesPrevBtn: document.getElementById("stories-prev-btn"),
    storiesNextBtn: document.getElementById("stories-next-btn"),
    
    // Reports View
    reportsTable: document.getElementById("reports-table-body"),
    reportsPaginationInfo: document.getElementById("reports-pagination-info"),
    reportsPrevBtn: document.getElementById("reports-prev-btn"),
    reportsNextBtn: document.getElementById("reports-next-btn"),
    pills: document.querySelectorAll(".tab-pill[data-status]"),
    countPending: document.getElementById("count-pending"),
    countResolved: document.getElementById("count-resolved"),
    countDismissed: document.getElementById("count-dismissed"),
    
    // Analytics View
    analyticsViews: document.getElementById("analytics-views"),
    analyticsLikes: document.getElementById("analytics-likes"),
    analyticsComments: document.getElementById("analytics-comments"),
    analyticsFollows: document.getElementById("analytics-follows"),
    topAuthorsContainer: document.getElementById("top-authors-container"),
    
    // Action Confirmation Modal
    actionModal: document.getElementById("action-modal"),
    modalTitle: document.getElementById("modal-title"),
    modalBodyText: document.getElementById("modal-body-text"),
    modalConfirmBtn: document.getElementById("modal-confirm-btn"),
    modalCancelBtn: document.getElementById("modal-cancel-btn"),
    modalCloseBtn: document.getElementById("modal-close-btn"),
    
    // Report Detail Modal
    reportModal: document.getElementById("report-modal"),
    reportCloseBtn: document.getElementById("report-close-btn"),
    reportViewReason: document.getElementById("report-view-reason"),
    reportViewDesc: document.getElementById("report-view-desc"),
    reportViewTarget: document.getElementById("report-view-target"),
    reportDismissBtn: document.getElementById("report-dismiss-btn"),
    reportResolveBtn: document.getElementById("report-resolve-btn")
  };

  // ── Debounce Helper ─────────────────────────────────────────
  function debounce(func, delay = 350) {
    let timer;
    return function (...args) {
      clearTimeout(timer);
      timer = setTimeout(() => func.apply(this, args), delay);
    };
  }

  // ── Floating Toast System ───────────────────────────────────
  function showToast(message, type = "success") {
    const existing = document.getElementById("adminToast");
    if (existing) existing.remove();

    const toast = document.createElement("div");
    toast.id = "adminToast";
    toast.textContent = message;
    toast.style.cssText = `
      position: fixed;
      top: 24px;
      right: 24px;
      z-index: 9999;
      background: ${type === "success" ? "#2ecc71" : "#e74c3c"};
      color: #fff;
      padding: 14px 22px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 500;
      box-shadow: 0 8px 32px rgba(0,0,0,0.3);
      opacity: 0;
      transform: translateY(-10px);
      transition: opacity 0.3s ease, transform 0.3s ease;
    `;
    document.body.appendChild(toast);

    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        toast.style.opacity = "1";
        toast.style.transform = "translateY(0)";
      });
    });

    setTimeout(() => {
      toast.style.opacity = "0";
      toast.style.transform = "translateY(-10px)";
      setTimeout(() => toast.remove(), 350);
    }, 3000);
  }

  // ── Base API Request Handler ──────────────────────────────
  async function apiFetch(url, options = {}) {
    try {
      const response = await fetch(url, options);
      if (!response.ok) {
        if (response.status === 403) {
          showToast("Session expired or Unauthorized access. Redirecting...", "error");
          setTimeout(() => window.location.href = "homepage.php", 1500);
          return null;
        }
        throw new Error(`HTTP Error: ${response.status}`);
      }
      return await response.json();
    } catch (err) {
      showToast(err.message || "Failed to communicate with server", "error");
      return null;
    }
  }

  // ── Dynamic Tab Dispatcher ──────────────────────────────────
  function switchTab(tabId) {
    state.activeTab = tabId;
    
    // Toggle active links
    el.menuLinks.forEach(link => {
      if (link.dataset.tab === tabId) {
        link.classList.add("active");
      } else {
        link.classList.remove("active");
      }
    });

    // Toggle panels
    el.tabSections.forEach(section => {
      if (section.id === tabId) {
        section.classList.add("active");
      } else {
        section.classList.remove("active");
      }
    });

    // Set title and reload data
    const titleMap = {
      empty: "Admin Portal",
      dashboard: "Admin Dashboard",
      users: "Manage Users",
      stories: "Manage Stories",
      reports: "Reports Management",
      analytics: "Platform Analytics"
    };
    el.pageTitle.textContent = titleMap[tabId] || "Admin Portal";

    // Load active tab data
    loadTabData(tabId);
  }

  function loadTabData(tabId) {
    if (tabId === "dashboard") loadDashboard();
    else if (tabId === "users") loadUsers();
    else if (tabId === "stories") loadStories();
    else if (tabId === "reports") loadReports();
    else if (tabId === "analytics") loadAnalytics();
  }

  // ── Tab 1: Dashboard Loader ────────────────────────────────
  async function loadDashboard() {
    el.recentStoriesTable.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:40px 0;">Loading dashboard...</td></tr>`;
    
    const data = await apiFetch("src/Admin/PHP/admin_get_stats.php");
    if (!data || !data.success) return;

    // Populat stats
    el.statTotalUsers.textContent = data.stats.total_users.toLocaleString();
    el.statTotalStories.textContent = data.stats.total_stories.toLocaleString();
    el.statPendingReviews.textContent = data.stats.pending_review.toLocaleString();
    el.statTotalReports.textContent = data.stats.total_reports.toLocaleString();

    // Render Recent stories
    el.recentStoriesTable.innerHTML = "";
    if (data.recent_stories.length === 0) {
      el.recentStoriesTable.innerHTML = `<tr><td colspan="5" style="text-align:center; padding: 30px 0;">No story submissions available</td></tr>`;
      return;
    }

    data.recent_stories.forEach(story => {
      const isDraft = story.status === "draft";
      const statusClass = isDraft ? "pending" : "published";
      const statusLabel = isDraft ? "Draft" : "Published";
      
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td><strong style="color:var(--text-primary);">${escapeHtml(story.title)}</strong></td>
        <td>@${escapeHtml(story.author)}</td>
        <td>${escapeHtml(story.category)}</td>
        <td><span class="status ${statusClass}">${statusLabel}</span></td>
        <td>
          <div class="btn-action-row">
            ${isDraft ? `
              <button class="btn-icon approve" title="Approve & Publish" data-id="${story.story_id}">
                <span class="material-symbols-outlined">check</span>
              </button>
            ` : `
              <button class="btn-icon reject" title="Revert to Draft" data-id="${story.story_id}">
                <span class="material-symbols-outlined">undo</span>
              </button>
            `}
            <button class="btn-icon delete" title="Permanently Delete" data-id="${story.story_id}">
              <span class="material-symbols-outlined">delete</span>
            </button>
          </div>
        </td>
      `;

      // Event bindings
      const approveBtn = tr.querySelector(".approve");
      const rejectBtn = tr.querySelector(".reject");
      const deleteBtn = tr.querySelector(".delete");

      if (approveBtn) {
        approveBtn.addEventListener("click", () => confirmAction("approve_story", story.story_id, `Are you sure you want to approve and publish "${story.title}"?`));
      }
      if (rejectBtn) {
        rejectBtn.addEventListener("click", () => confirmAction("reject_story", story.story_id, `Are you sure you want to revert "${story.title}" to draft mode?`));
      }
      if (deleteBtn) {
        deleteBtn.addEventListener("click", () => confirmAction("delete_story", story.story_id, `WARNING: Are you absolutely sure you want to permanently delete the story "${story.title}"? This cannot be undone.`));
      }

      el.recentStoriesTable.appendChild(tr);
    });
  }

  // ── Tab 2: Users Loader ───────────────────────────────────
  async function loadUsers() {
    el.usersTable.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:40px 0;">Loading users list...</td></tr>`;
    
    const params = new URLSearchParams({
      page: state.users.page,
      search: state.users.search
    });

    const data = await apiFetch(`src/User/PHP/admin_get_users.php?${params}`);
    if (!data || !data.success) return;

    state.users.total = data.total;
    el.usersPaginationInfo.textContent = `Showing ${Math.min(data.total, (state.users.page - 1) * state.users.limit + 1)} - ${Math.min(data.total, state.users.page * state.users.limit)} of ${data.total} users`;
    
    el.usersPrevBtn.disabled = state.users.page <= 1;
    el.usersNextBtn.disabled = (state.users.page * state.users.limit) >= data.total;

    el.usersTable.innerHTML = "";
    if (data.users.length === 0) {
      el.usersTable.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:30px 0;">No matching users found</td></tr>`;
      return;
    }

    data.users.forEach(user => {
      const isBanned = user.role === "banned";
      const statusClass = isBanned ? "banned" : "published";
      const statusLabel = isBanned ? "Banned" : "Active";
      const avatarSrc = user.profile_pic ? user.profile_pic : "Pic/profileicon.jpg";

      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td>
          <div class="user-cell">
            <img class="user-avatar" src="${avatarSrc}" onerror="this.src='Pic/profileicon.jpg'">
            <div class="cell-info">
              <span class="cell-title">${escapeHtml(user.name)}</span>
              <span class="cell-subtitle">@${escapeHtml(user.username)}</span>
            </div>
          </div>
        </td>
        <td>${escapeHtml(user.email)}</td>
        <td>${user.story_count} stories</td>
        <td><span class="status ${statusClass}">${statusLabel}</span></td>
        <td>
          <div class="btn-action-row" style="justify-content: flex-end;">
            ${isBanned ? `
              <button class="btn-icon unban" title="Unban User" data-id="${user.user_id}">
                <span class="material-symbols-outlined">verified_user</span>
              </button>
            ` : `
              <button class="btn-icon ban" title="Ban User" data-id="${user.user_id}">
                <span class="material-symbols-outlined">block</span>
              </button>
            `}
            <button class="btn-icon delete" title="Permanently Delete" data-id="${user.user_id}">
              <span class="material-symbols-outlined">delete</span>
            </button>
          </div>
        </td>
      `;

      // Event bindings
      const banBtn = tr.querySelector(".ban");
      const unbanBtn = tr.querySelector(".unban");
      const deleteBtn = tr.querySelector(".delete");

      if (banBtn) {
        banBtn.addEventListener("click", () => confirmAction("ban_user", user.user_id, `Are you sure you want to BAN user "${user.username}"?`));
      }
      if (unbanBtn) {
        unbanBtn.addEventListener("click", () => confirmAction("unban_user", user.user_id, `Are you sure you want to UNBAN user "${user.username}"?`));
      }
      if (deleteBtn) {
        deleteBtn.addEventListener("click", () => confirmAction("delete_user", user.user_id, `CAUTION: Are you absolutely sure you want to permanently delete the user account "${user.username}"? All their stories, libraries, comments and settings will be wiped. This is irreversible!`));
      }

      el.usersTable.appendChild(tr);
    });
  }

  // ── Tab 3: Stories Loader ─────────────────────────────────
  async function loadStories() {
    el.storiesTable.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:40px 0;">Loading stories list...</td></tr>`;
    
    const params = new URLSearchParams({
      page: state.stories.page,
      search: state.stories.search,
      status: state.stories.status
    });

    const data = await apiFetch(`src/Story/PHP/admin_get_stories.php?${params}`);
    if (!data || !data.success) return;

    state.stories.total = data.total;
    el.storiesPaginationInfo.textContent = `Showing ${Math.min(data.total, (state.stories.page - 1) * state.stories.limit + 1)} - ${Math.min(data.total, state.stories.page * state.stories.limit)} of ${data.total} stories`;
    
    el.storiesPrevBtn.disabled = state.stories.page <= 1;
    el.storiesNextBtn.disabled = (state.stories.page * state.stories.limit) >= data.total;

    el.storiesTable.innerHTML = "";
    if (data.stories.length === 0) {
      el.storiesTable.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:30px 0;">No matching stories found</td></tr>`;
      return;
    }

    data.stories.forEach(story => {
      const isDraft = story.status === "draft";
      const statusClass = isDraft ? "pending" : "published";
      const statusLabel = isDraft ? "Draft / Ongoing" : "Published";
      const coverSrc = story.cover ? story.cover : "Pic/cover-placeholder.png";

      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td>
          <div class="story-cell">
            <img class="story-cover-thumb" src="${coverSrc}" onerror="this.src='Pic/cover-placeholder.png'">
            <div class="cell-info">
              <span class="cell-title">${escapeHtml(story.title)}</span>
              <span class="cell-subtitle">${escapeHtml(story.category)}</span>
            </div>
          </div>
        </td>
        <td>@${escapeHtml(story.author)}</td>
        <td>${story.published_at ? new Date(story.published_at).toLocaleDateString() : '-'}</td>
        <td><span class="status ${statusClass}">${statusLabel}</span></td>
        <td>👁️ ${story.total_views}</td>
        <td>❤️ ${story.total_likes}</td>
        <td>
          <div class="btn-action-row" style="justify-content: flex-end;">
            ${isDraft ? `
              <button class="btn-icon approve" title="Approve & Publish" data-id="${story.story_id}">
                <span class="material-symbols-outlined">check</span>
              </button>
            ` : `
              <button class="btn-icon reject" title="Revert to Draft" data-id="${story.story_id}">
                <span class="material-symbols-outlined">undo</span>
              </button>
            `}
            <button class="btn-icon delete" title="Permanently Delete" data-id="${story.story_id}">
              <span class="material-symbols-outlined">delete</span>
            </button>
          </div>
        </td>
      `;

      // Event bindings
      const approveBtn = tr.querySelector(".approve");
      const rejectBtn = tr.querySelector(".reject");
      const deleteBtn = tr.querySelector(".delete");

      if (approveBtn) {
        approveBtn.addEventListener("click", () => confirmAction("approve_story", story.story_id, `Are you sure you want to approve and publish "${story.title}"?`));
      }
      if (rejectBtn) {
        rejectBtn.addEventListener("click", () => confirmAction("reject_story", story.story_id, `Are you sure you want to revert "${story.title}" to draft?`));
      }
      if (deleteBtn) {
        deleteBtn.addEventListener("click", () => confirmAction("delete_story", story.story_id, `WARNING: Are you absolutely sure you want to permanently delete the story "${story.title}"? All chapters and contents will be deleted. This cannot be undone.`));
      }

      el.storiesTable.appendChild(tr);
    });
  }

  // ── Tab 4: Reports Loader ─────────────────────────────────
  async function loadReports() {
    el.reportsTable.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:40px 0;">Loading violation reports...</td></tr>`;
    
    const params = new URLSearchParams({
      page: state.reports.page,
      status: state.reports.status
    });

    const data = await apiFetch(`src/Report/PHP/admin_get_reports.php?${params}`);
    if (!data || !data.success) return;

    state.reports.total = data.total;
    el.reportsPaginationInfo.textContent = `Showing ${Math.min(data.total, (state.reports.page - 1) * state.reports.limit + 1)} - ${Math.min(data.total, state.reports.page * state.reports.limit)} of ${data.total} reports`;
    
    el.reportsPrevBtn.disabled = state.reports.page <= 1;
    el.reportsNextBtn.disabled = (state.reports.page * state.reports.limit) >= data.total;

    // Update Tab count indicators
    el.countPending.textContent = data.counts.pending || 0;
    el.countResolved.textContent = data.counts.resolved || 0;
    el.countDismissed.textContent = data.counts.dismissed || 0;

    el.reportsTable.innerHTML = "";
    if (data.reports.length === 0) {
      el.reportsTable.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:30px 0;">No ${state.reports.status} reports found</td></tr>`;
      return;
    }

    data.reports.forEach(report => {
      let targetText = "-";
      if (report.reported_user_id) {
        targetText = `👤 User: @${report.reported_user_name}`;
      } else if (report.reported_story_id) {
        targetText = `📖 Story: "${report.reported_story_title}"`;
      }

      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td><strong style="color:var(--text-primary);">${escapeHtml(report.reason)}</strong></td>
        <td>@${escapeHtml(report.reporter_name)}</td>
        <td>${escapeHtml(targetText)}</td>
        <td>${new Date(report.created_at).toLocaleDateString()}</td>
        <td>
          <div class="btn-action-row" style="justify-content: flex-end;">
            <button class="btn-icon view" title="View Violation Details" data-id="${report.report_id}">
              <span class="material-symbols-outlined">visibility</span>
            </button>
          </div>
        </td>
      `;

      // Event bindings
      tr.querySelector(".view").addEventListener("click", () => openReportDetails(report));

      el.reportsTable.appendChild(tr);
    });
  }

  // ── Tab 5: Analytics Loader (Chart.js Integration) ────────
  async function loadAnalytics() {
    const data = await apiFetch("src/Admin/PHP/admin_get_analytics.php");
    if (!data || !data.success) return;

    // Populat stats card
    el.analyticsViews.textContent = data.total_views.toLocaleString();
    el.analyticsLikes.textContent = data.total_likes.toLocaleString();
    el.analyticsComments.textContent = data.total_comments.toLocaleString();
    el.analyticsFollows.textContent = data.total_follows.toLocaleString();

    // Renders top platform authors list
    el.topAuthorsContainer.innerHTML = "";
    if (data.top_authors.length === 0) {
      el.topAuthorsContainer.innerHTML = `<div class="empty-state"><p>No writer activity data registered yet.</p></div>`;
    } else {
      data.top_authors.forEach((author, i) => {
        const avatar = author.profile_pic ? author.profile_pic : "Pic/profileicon.jpg";
        
        const div = document.createElement("div");
        div.className = "author-item";
        div.innerHTML = `
          <div class="author-meta">
            <span class="author-rank">#${i + 1}</span>
            <img class="user-avatar" src="${avatar}" onerror="this.src='Pic/profileicon.jpg'">
            <div class="cell-info">
              <span class="cell-title">${escapeHtml(author.username)}</span>
              <span class="author-stats">📚 <strong>${author.story_count}</strong> stories | 👁️ <strong>${author.total_views}</strong> views</span>
            </div>
          </div>
          <div>
            <span class="status approved">❤️ ${author.total_likes} likes</span>
          </div>
        `;
        el.topAuthorsContainer.appendChild(div);
      });
    }

    // Chart.js: Story distribution by genre
    const genres = data.genre_data.map(g => g.genre_name);
    const storyCounts = data.genre_data.map(g => parseInt(g.count));

    if (state.genreChart) {
      state.genreChart.destroy();
    }

    const canvas = document.getElementById("genreChart");
    if (!canvas) return;

    const ctx = canvas.getContext("2d");
    state.genreChart = new Chart(ctx, {
      type: "doughnut",
      data: {
        labels: genres,
        datasets: [{
          data: storyCounts,
          backgroundColor: [
            "rgba(255, 244, 79, 0.75)",  // Neon Yellow
            "rgba(46, 204, 113, 0.75)",  // Green
            "rgba(52, 152, 219, 0.75)",  // Blue
            "rgba(155, 89, 182, 0.75)",  // Purple
            "rgba(230, 126, 34, 0.75)",  // Orange
            "rgba(231, 76, 60, 0.75)",   // Red
            "rgba(26, 188, 156, 0.75)",  // Teal
            "rgba(149, 165, 166, 0.75)"   // Grey
          ],
          borderColor: "#161616",
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: "right",
            labels: {
              color: "#b3b3b3",
              font: { family: "Poppins", size: 12 }
            }
          }
        }
      }
    });
  }

  // ── Action Dispatcher Overlay Modal ───────────────────────
  function confirmAction(actionName, id, promptText) {
    state.pendingAction = { action: actionName, id: id };
    
    el.modalTitle.textContent = "Confirm System Action";
    el.modalBodyText.textContent = promptText;
    
    // Change coloring of confirm button based on destructive actions
    if (actionName.includes("delete") || actionName.includes("ban_user")) {
      el.modalConfirmBtn.className = "btn-danger";
    } else {
      el.modalConfirmBtn.className = "btn-primary";
    }

    el.actionModal.style.display = "flex";
  }

  async function executeAction() {
    if (!state.pendingAction) return;

    const { action, id } = state.pendingAction;
    
    let payload = { action: action };
    if (action.includes("user")) {
      payload.user_id = id;
    } else if (action.includes("story")) {
      payload.story_id = id;
    } else if (action.includes("report")) {
      payload.report_id = id;
    }

    el.modalConfirmBtn.disabled = true;
    el.modalConfirmBtn.textContent = "Processing...";

    const res = await apiFetch("src/Admin/PHP/admin_action.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });

    el.modalConfirmBtn.disabled = false;
    el.modalConfirmBtn.textContent = "Confirm";
    el.actionModal.style.display = "none";
    state.pendingAction = null;

    if (res && res.success) {
      showToast(res.message || "Action processed successfully!");
      // Reload current active tab
      loadTabData(state.activeTab);
    }
  }

  // ── Violation Reports Detail Modal ─────────────────────────
  function openReportDetails(report) {
    el.reportViewReason.textContent = report.reason;
    el.reportViewDesc.textContent = report.description || "No description provided by the reporter.";
    
    let targetHtml = "-";
    if (report.reported_user_id) {
      targetHtml = `
        <div style="display:flex; align-items:center; gap: 10px; margin-top:8px;">
          <span class="material-symbols-outlined" style="color:var(--accent-color);">person</span>
          <div>
            <strong>@${escapeHtml(report.reported_user_name)}</strong> (User Account)
            <br><span style="font-size:12px; color:var(--text-muted);">ID: ${report.reported_user_id}</span>
          </div>
        </div>
      `;
    } else if (report.reported_story_id) {
      targetHtml = `
        <div style="display:flex; align-items:center; gap: 10px; margin-top:8px;">
          <span class="material-symbols-outlined" style="color:var(--accent-color);">auto_stories</span>
          <div>
            <strong>"${escapeHtml(report.reported_story_title)}"</strong> (Story Publication)
            <br><span style="font-size:12px; color:var(--text-muted);">ID: ${report.reported_story_id}</span>
          </div>
        </div>
      `;
    }
    el.reportViewTarget.innerHTML = targetHtml;

    // Adjust visibility of action buttons based on report status
    if (report.status === "pending") {
      el.reportDismissBtn.style.display = "inline-block";
      el.reportResolveBtn.style.display = "inline-block";
      
      // Bind resolve and dismiss action triggers
      el.reportResolveBtn.onclick = () => {
        el.reportModal.style.display = "none";
        confirmAction("resolve_report", report.report_id, "Mark this report as resolved?");
      };
      
      el.reportDismissBtn.onclick = () => {
        el.reportModal.style.display = "none";
        confirmAction("dismiss_report", report.report_id, "Dismiss this report as invalid or resolved?");
      };
    } else {
      el.reportDismissBtn.style.display = "none";
      el.reportResolveBtn.style.display = "none";
    }

    el.reportModal.style.display = "flex";
  }

  // ── Logout Action Triggers ──────────────────────────────────
  async function performLogout() {
    try {
      await fetch("src/User/PHP/logout.php", {
        method: "POST",
        headers: { "X-Requested-With": "XMLHttpRequest" }
      });
    } finally {
      window.location.href = "homepage.php?loggedout=1";
    }
  }

  // ── Interactive UI Event Bindings ──────────────────────────
  
  // Sidebar tab clicks
  el.menuLinks.forEach(link => {
    link.addEventListener("click", function (e) {
      e.preventDefault();
      const tabId = this.dataset.tab;
      if (tabId) switchTab(tabId);
    });
  });

  // Logout Sidebar action
  if (el.sidebarLogoutBtn) {
    el.sidebarLogoutBtn.addEventListener("click", function (e) {
      e.preventDefault();
      confirmAction("logout", 0, "Are you sure you want to log out of the administrator portal?");
    });
  }

  // Modals actions binding
  el.modalConfirmBtn.addEventListener("click", () => {
    if (state.pendingAction && state.pendingAction.action === "logout") {
      performLogout();
    } else {
      executeAction();
    }
  });

  el.modalCancelBtn.addEventListener("click", () => {
    el.actionModal.style.display = "none";
    state.pendingAction = null;
  });

  el.modalCloseBtn.addEventListener("click", () => {
    el.actionModal.style.display = "none";
    state.pendingAction = null;
  });

  el.reportCloseBtn.addEventListener("click", () => {
    el.reportModal.style.display = "none";
  });

  // Close modals when clicking outside card boundary
  window.addEventListener("click", (e) => {
    if (e.target === el.actionModal) {
      el.actionModal.style.display = "none";
      state.pendingAction = null;
    }
    if (e.target === el.reportModal) {
      el.reportModal.style.display = "none";
    }
  });

  // Search & Filtering listeners (Debounced)
  if (el.userSearch) {
    el.userSearch.addEventListener("input", debounce(function () {
      state.users.search = this.value.trim();
      state.users.page = 1;
      loadUsers();
    }, 400));
  }

  if (el.storySearch) {
    el.storySearch.addEventListener("input", debounce(function () {
      state.stories.search = this.value.trim();
      state.stories.page = 1;
      loadStories();
    }, 400));
  }

  if (el.storyStatus) {
    el.storyStatus.addEventListener("change", function () {
      state.stories.status = this.value;
      state.stories.page = 1;
      loadStories();
    });
  }

  // Users pagination click listeners
  el.usersPrevBtn.addEventListener("click", () => {
    if (state.users.page > 1) {
      state.users.page--;
      loadUsers();
    }
  });

  el.usersNextBtn.addEventListener("click", () => {
    if ((state.users.page * state.users.limit) < state.users.total) {
      state.users.page++;
      loadUsers();
    }
  });

  // Stories pagination click listeners
  el.storiesPrevBtn.addEventListener("click", () => {
    if (state.stories.page > 1) {
      state.stories.page--;
      loadStories();
    }
  });

  el.storiesNextBtn.addEventListener("click", () => {
    if ((state.stories.page * state.stories.limit) < state.stories.total) {
      state.stories.page++;
      loadStories();
    }
  });

  // Reports status pill selectors
  el.pills.forEach(pill => {
    pill.addEventListener("click", function () {
      el.pills.forEach(p => p.classList.remove("active"));
      this.classList.add("active");
      
      state.reports.status = this.dataset.status;
      state.reports.page = 1;
      loadReports();
    });
  });

  // Reports pagination click listeners
  el.reportsPrevBtn.addEventListener("click", () => {
    if (state.reports.page > 1) {
      state.reports.page--;
      loadReports();
    }
  });

  el.reportsNextBtn.addEventListener("click", () => {
    if ((state.reports.page * state.reports.limit) < state.reports.total) {
      state.reports.page++;
      loadReports();
    }
  });

  // ── Utilities Helper ────────────────────────────────────────
  function escapeHtml(str) {
    if (typeof str !== "string") return str;
    return str
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  // ── Create Admin Account Handler ────────────────────────────
  const createAdminBtn = document.getElementById("create-admin-btn");
  if (createAdminBtn) {
    createAdminBtn.addEventListener("click", async function () {
      const username = document.getElementById("new-admin-username").value.trim();
      const name = document.getElementById("new-admin-name").value.trim();
      const email = document.getElementById("new-admin-email").value.trim();
      const password = document.getElementById("new-admin-password").value;

      if (!username || !name || !email || !password) {
        showToast("All fields are required to create an admin account.", "error");
        return;
      }

      createAdminBtn.disabled = true;
      createAdminBtn.textContent = "Creating...";

      const res = await apiFetch("src/Admin/PHP/admin_action.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "create_admin", username, name, email, password })
      });

      createAdminBtn.disabled = false;
      createAdminBtn.innerHTML = `<span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;margin-right:6px;">person_add</span> Create Admin Account`;

      if (res && res.success) {
        showToast(res.message);
        document.getElementById("new-admin-username").value = "";
        document.getElementById("new-admin-name").value = "";
        document.getElementById("new-admin-email").value = "";
        document.getElementById("new-admin-password").value = "";
      }
    });
  }

  // ── System Initializer ──────────────────────────────────────
  // Check if a specific tab parameter is present in the URL query string
  const urlParams = new URLSearchParams(window.location.search);
  const tabParam = urlParams.get("tab");
  const validTabs = ["dashboard", "users", "stories", "reports", "analytics"];
  
  if (tabParam && validTabs.includes(tabParam)) {
    switchTab(tabParam);
  } else {
    switchTab("empty");
  }

});

