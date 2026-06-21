<?php
// flowguide.php — AUchive Interactive Flow Guide (Meisya: Admin & Auth System)
// Access: http://localhost/Project/AUchive/flowguide.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AUchive — Interactive Flow Guide</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined">
<style>
  :root {
    --bg: #080810;
    --surface: #0f0f1a;
    --card: #13131f;
    --border: rgba(130,100,255,0.18);
    --accent: #7c6fff;
    --accent2: #fff44f;
    --green: #2ecc71;
    --red: #e74c3c;
    --orange: #f39c12;
    --blue: #3498db;
    --text: #e8e8f0;
    --muted: #6b6b88;
    --tag-php: rgba(119,123,179,0.2);
    --tag-js: rgba(255,200,50,0.15);
    --tag-css: rgba(41,182,246,0.15);
  }

  * { margin:0; padding:0; box-sizing:border-box; }

  body {
    background: var(--bg);
    color: var(--text);
    font-family: 'Poppins', sans-serif;
    min-height: 100vh;
    overflow-x: hidden;
  }

  /* ─── Animated BG ─── */
  body::before {
    content:'';
    position:fixed; inset:0; z-index:0;
    background:
      radial-gradient(ellipse 80% 50% at 10% 20%, rgba(124,111,255,0.08) 0%, transparent 60%),
      radial-gradient(ellipse 60% 40% at 90% 80%, rgba(255,244,79,0.05) 0%, transparent 60%);
    pointer-events:none;
  }

  .wrapper { position:relative; z-index:1; max-width:1200px; margin:0 auto; padding:40px 24px 80px; }

  /* ─── Header ─── */
  .page-header {
    text-align:center; margin-bottom:60px;
  }
  .page-header .badge {
    display:inline-flex; align-items:center; gap:8px;
    background:rgba(124,111,255,0.12); border:1px solid rgba(124,111,255,0.3);
    color:var(--accent); padding:6px 18px; border-radius:20px;
    font-size:12px; font-weight:600; letter-spacing:1px; margin-bottom:20px;
  }
  .page-header h1 {
    font-size:clamp(28px,5vw,48px); font-weight:800;
    background:linear-gradient(135deg,#ffffff 0%,#b3b0ff 50%,var(--accent2) 100%);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent;
    line-height:1.2; margin-bottom:14px;
  }
  .page-header p {
    color:var(--muted); font-size:15px; max-width:500px; margin:0 auto;
  }

  /* ─── Flow Tabs ─── */
  .flow-tabs {
    display:flex; gap:12px; justify-content:center; flex-wrap:wrap; margin-bottom:50px;
  }
  .flow-tab {
    background:var(--card); border:1px solid var(--border);
    color:var(--muted); padding:12px 28px; border-radius:30px;
    font-size:14px; font-weight:600; cursor:pointer; transition:all 0.3s;
    display:flex; align-items:center; gap:8px;
  }
  .flow-tab:hover { border-color:var(--accent); color:var(--text); }
  .flow-tab.active {
    background:linear-gradient(135deg,rgba(124,111,255,0.25),rgba(124,111,255,0.1));
    border-color:var(--accent); color:var(--accent);
    box-shadow:0 0 20px rgba(124,111,255,0.2);
  }

  /* ─── Flow Section ─── */
  .flow-section { display:none; }
  .flow-section.active { display:block; animation:fadeUp 0.4s ease-out; }
  @keyframes fadeUp {
    from { opacity:0; transform:translateY(20px); }
    to   { opacity:1; transform:translateY(0); }
  }

  .section-title {
    display:flex; align-items:center; gap:14px;
    font-size:22px; font-weight:700; margin-bottom:36px;
    padding-bottom:16px; border-bottom:1px solid var(--border);
  }
  .section-title .icon-circle {
    width:44px; height:44px; border-radius:12px; display:flex;
    align-items:center; justify-content:center; font-size:20px;
    flex-shrink:0;
  }

  /* ─── Step Cards ─── */
  .steps-flow {
    display:flex; flex-direction:column; gap:0;
  }

  .step-item {
    display:flex; gap:0; position:relative;
  }

  /* vertical connector line */
  .step-item:not(:last-child) .step-left::after {
    content:'';
    position:absolute; left:50%; top:72px;
    width:2px; height:calc(100% - 72px + 20px);
    background:linear-gradient(to bottom, var(--accent), transparent);
    transform:translateX(-50%);
    z-index:0;
  }

  .step-left {
    display:flex; flex-direction:column; align-items:center;
    width:80px; flex-shrink:0; position:relative;
    padding-top:20px;
  }

  .step-num {
    width:48px; height:48px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:16px; font-weight:800; position:relative; z-index:1;
    flex-shrink:0; border:2px solid;
    transition:all 0.3s;
  }

  .step-content {
    flex:1; padding:20px 0 30px 10px;
  }

  .step-card {
    background:var(--card); border:1px solid var(--border);
    border-radius:16px; padding:20px 22px;
    cursor:pointer; transition:all 0.3s;
    position:relative; overflow:hidden;
  }

  .step-card::before {
    content:''; position:absolute; left:0; top:0; bottom:0; width:3px;
    border-radius:0 2px 2px 0; transition:all 0.3s;
  }

  .step-card:hover {
    transform:translateX(4px);
    box-shadow:0 8px 32px rgba(0,0,0,0.4);
  }

  .step-card.expanded { border-color:rgba(124,111,255,0.4); }
  .step-card.expanded::before { background:var(--accent); }

  .step-header {
    display:flex; align-items:flex-start; justify-content:space-between; gap:12px;
  }

  .step-title-area { flex:1; }

  .step-algo-label {
    font-size:10px; font-weight:700; letter-spacing:1.5px;
    text-transform:uppercase; margin-bottom:5px; opacity:0.7;
  }

  .step-name {
    font-size:15px; font-weight:700; color:var(--text); margin-bottom:4px;
    display:flex; align-items:center; gap:8px; flex-wrap:wrap;
  }

  .step-desc { font-size:13px; color:var(--muted); line-height:1.6; }

  .expand-icon {
    width:28px; height:28px; border-radius:8px;
    background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08);
    display:flex; align-items:center; justify-content:center;
    font-size:16px; color:var(--muted); transition:all 0.3s; flex-shrink:0;
  }
  .step-card.expanded .expand-icon {
    transform:rotate(180deg); background:rgba(124,111,255,0.15);
    border-color:var(--accent); color:var(--accent);
  }

  /* ─── Step Detail (expanded) ─── */
  .step-detail {
    display:none; margin-top:16px;
    border-top:1px solid rgba(255,255,255,0.06); padding-top:16px;
  }
  .step-card.expanded .step-detail { display:block; }

  .detail-algo {
    background:rgba(0,0,0,0.3); border:1px solid rgba(124,111,255,0.15);
    border-radius:10px; padding:14px 16px; margin-bottom:14px;
  }
  .detail-algo-title {
    font-size:10px; font-weight:700; letter-spacing:1.5px; color:var(--accent);
    text-transform:uppercase; margin-bottom:8px; display:flex; align-items:center; gap:6px;
  }
  .algo-steps { display:flex; flex-direction:column; gap:6px; }
  .algo-step {
    display:flex; gap:10px; align-items:flex-start;
    font-size:12px; color:var(--muted); line-height:1.6;
  }
  .algo-step-num {
    min-width:20px; height:20px; border-radius:50%;
    background:rgba(124,111,255,0.2); color:var(--accent);
    font-size:10px; font-weight:700; display:flex; align-items:center;
    justify-content:center; flex-shrink:0; margin-top:1px;
  }
  .algo-step-text { flex:1; }
  .algo-step-text code {
    font-family:'Fira Code', monospace; font-size:11px;
    background:rgba(124,111,255,0.12); padding:1px 6px; border-radius:4px;
    color:#b3b0ff;
  }

  /* ─── File Tags & Links ─── */
  .file-links {
    display:flex; flex-wrap:wrap; gap:8px; margin-top:10px;
  }

  .file-link {
    display:inline-flex; align-items:center; gap:6px;
    padding:6px 12px; border-radius:8px;
    font-size:12px; font-weight:600; cursor:pointer;
    text-decoration:none; border:1px solid; transition:all 0.25s;
  }
  .file-link:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(0,0,0,0.3); }

  .file-link.php {
    background:rgba(119,123,179,0.15); border-color:rgba(119,123,179,0.35);
    color:#a78bfa;
  }
  .file-link.php:hover { background:rgba(119,123,179,0.3); }

  .file-link.js {
    background:rgba(255,200,50,0.1); border-color:rgba(255,200,50,0.3);
    color:#fbbf24;
  }
  .file-link.js:hover { background:rgba(255,200,50,0.2); }

  .file-link.css {
    background:rgba(41,182,246,0.1); border-color:rgba(41,182,246,0.3);
    color:#38bdf8;
  }
  .file-link.css:hover { background:rgba(41,182,246,0.2); }

  .file-link .material-symbols-outlined { font-size:14px; }

  /* ─── Decision / Branch Nodes ─── */
  .decision-node {
    display:flex; align-items:center; gap:0; margin:0 0 0 80px;
    position:relative;
  }
  .decision-node::before {
    content:'';
    position:absolute; left:-80px; top:50%; width:80px; height:2px;
    background:var(--border);
  }

  .branch-box {
    padding:12px 20px; border-radius:10px;
    font-size:13px; font-weight:600;
    border:2px dashed;
  }
  .branch-yes { border-color:rgba(46,204,113,0.4); color:var(--green); background:rgba(46,204,113,0.06); }
  .branch-no  { border-color:rgba(231,76,60,0.4);  color:var(--red);   background:rgba(231,76,60,0.06);  }

  /* ─── Connector spacer ─── */
  .connector-spacer {
    display:flex; flex-direction:column; align-items:center;
    width:80px; padding:8px 0;
  }
  .connector-line {
    width:2px; height:30px;
    background:linear-gradient(to bottom, rgba(124,111,255,0.5), transparent);
  }

  /* ─── Color classes for step num ─── */
  .c-purple { color:var(--accent); border-color:var(--accent); background:rgba(124,111,255,0.1); }
  .c-green  { color:var(--green);  border-color:var(--green);  background:rgba(46,204,113,0.08); }
  .c-red    { color:var(--red);    border-color:var(--red);    background:rgba(231,76,60,0.08);  }
  .c-orange { color:var(--orange); border-color:var(--orange); background:rgba(243,156,18,0.08); }
  .c-blue   { color:var(--blue);   border-color:var(--blue);   background:rgba(52,152,219,0.08); }
  .c-yellow { color:var(--accent2);border-color:var(--accent2);background:rgba(255,244,79,0.08); }

  .card-accent-purple::before { background:var(--accent) !important; }
  .card-accent-green::before  { background:var(--green) !important; }
  .card-accent-red::before    { background:var(--red) !important; }
  .card-accent-orange::before { background:var(--orange) !important; }
  .card-accent-blue::before   { background:var(--blue) !important; }
  .card-accent-yellow::before { background:var(--accent2) !important; }

  /* ─── Badge tags ─── */
  .step-tag {
    display:inline-flex; align-items:center; gap:4px;
    padding:2px 8px; border-radius:6px; font-size:10px; font-weight:700;
    letter-spacing:0.5px; text-transform:uppercase;
  }
  .tag-auth   { background:rgba(46,204,113,0.12); color:var(--green); }
  .tag-db     { background:rgba(52,152,219,0.12); color:var(--blue); }
  .tag-session{ background:rgba(124,111,255,0.12); color:var(--accent); }
  .tag-api    { background:rgba(243,156,18,0.12); color:var(--orange); }
  .tag-security{background:rgba(231,76,60,0.12);  color:var(--red); }
  .tag-ui     { background:rgba(41,182,246,0.12); color:#38bdf8; }

  /* ─── Legend ─── */
  .legend {
    display:flex; flex-wrap:wrap; gap:12px; align-items:center;
    background:var(--card); border:1px solid var(--border);
    border-radius:12px; padding:14px 20px; margin-bottom:36px;
  }
  .legend-title { font-size:12px; font-weight:600; color:var(--muted); margin-right:8px; }
  .legend-item {
    display:flex; align-items:center; gap:6px;
    font-size:12px; color:var(--muted);
  }
  .legend-dot { width:10px; height:10px; border-radius:50%; }

  /* ─── Hover tooltip ─── */
  .step-card:hover .step-name { color:var(--accent); }

  /* ─── Info box ─── */
  .info-box {
    background:rgba(124,111,255,0.06); border:1px solid rgba(124,111,255,0.2);
    border-radius:10px; padding:12px 16px; margin-top:12px;
    font-size:12px; color:var(--muted); line-height:1.7;
  }
  .info-box strong { color:var(--accent); }

  /* ─── Scrollbar ─── */
  ::-webkit-scrollbar { width:6px; }
  ::-webkit-scrollbar-track { background:var(--bg); }
  ::-webkit-scrollbar-thumb { background:#333; border-radius:4px; }
  ::-webkit-scrollbar-thumb:hover { background:var(--accent); }

  /* ─── Mobile ─── */
  @media (max-width:600px) {
    .step-left { width:50px; }
    .step-num { width:36px; height:36px; font-size:13px; }
  }
</style>
</head>
<body>

<div class="wrapper">

  <!-- ═══ HEADER ═══ -->
  <div class="page-header">
    <div class="badge">
      <span class="material-symbols-outlined" style="font-size:14px;">code_blocks</span>
      MEISYA — SYSTEM & ADMIN MODULE
    </div>
    <h1>Interactive Flow Guide</h1>
    <p>Klik setiap langkah untuk melihat detail algoritma &amp; file kode yang digunakan</p>
  </div>

  <!-- ═══ TABS ═══ -->
  <div class="flow-tabs">
    <div class="flow-tab active" onclick="switchFlow('login')" id="tab-login">
      <span class="material-symbols-outlined" style="font-size:18px;">login</span>
      Alur Login & Sesi
    </div>
    <div class="flow-tab" onclick="switchFlow('register')" id="tab-register">
      <span class="material-symbols-outlined" style="font-size:18px;">person_add</span>
      Alur Register
    </div>
    <div class="flow-tab" onclick="switchFlow('admin')" id="tab-admin">
      <span class="material-symbols-outlined" style="font-size:18px;">admin_panel_settings</span>
      Alur Kerja Admin
    </div>
    <div class="flow-tab" onclick="switchFlow('report')" id="tab-report">
      <span class="material-symbols-outlined" style="font-size:18px;">report</span>
      Alur Moderasi Laporan
    </div>
    <div class="flow-tab" onclick="switchFlow('settings')" id="tab-settings">
      <span class="material-symbols-outlined" style="font-size:18px;">settings</span>
      Alur Pengaturan Akun
    </div>
  </div>

  <!-- ═══════════════════════════════════════════════════════════
       FLOW 1 — LOGIN & SESI
       ═══════════════════════════════════════════════════════════ -->
  <div id="flow-login" class="flow-section active">
    <div class="section-title">
      <div class="icon-circle" style="background:rgba(46,204,113,0.12); color:var(--green);">
        <span class="material-symbols-outlined">key</span>
      </div>
      Alur Login Terpadu + Manajemen Sesi
    </div>

    <div class="legend">
      <span class="legend-title">Keterangan Warna:</span>
      <div class="legend-item"><div class="legend-dot" style="background:var(--green)"></div> Inisiasi & Validasi</div>
      <div class="legend-item"><div class="legend-dot" style="background:var(--blue)"></div> Query Database</div>
      <div class="legend-item"><div class="legend-dot" style="background:var(--red)"></div> Pemeriksaan Keamanan</div>
      <div class="legend-item"><div class="legend-dot" style="background:var(--accent)"></div> Manajemen Sesi</div>
      <div class="legend-item"><div class="legend-dot" style="background:var(--accent2)"></div> Respons & Redirect</div>
    </div>

    <div class="steps-flow">

      <!-- Step 1 -->
      <div class="step-item">
        <div class="step-left">
          <div class="step-num c-green">1</div>
        </div>
        <div class="step-content">
          <div class="step-card card-accent-green" onclick="toggleStep(this)">
            <div class="step-header">
              <div class="step-title-area">
                <div class="step-algo-label" style="color:var(--green)">ALGORITHM STEP 1 — INPUT COLLECTION</div>
                <div class="step-name">
                  <span class="material-symbols-outlined" style="font-size:16px;color:var(--green)">input</span>
                  Terima Input Login dari Browser
                  <span class="step-tag tag-auth">AUTH</span>
                </div>
                <div class="step-desc">User mengisi form login dengan username/email dan password, lalu menekan tombol. JavaScript mengirim data via AJAX POST.</div>
              </div>
              <div class="expand-icon"><span class="material-symbols-outlined" style="font-size:16px">expand_more</span></div>
            </div>
            <div class="step-detail">
              <div class="detail-algo">
                <div class="detail-algo-title"><span class="material-symbols-outlined" style="font-size:12px">functions</span> Algoritma Detail</div>
                <div class="algo-steps">
                  <div class="algo-step"><div class="algo-step-num">a</div><div class="algo-step-text">Ambil nilai <code>login_input</code> dari field (bisa username atau email)</div></div>
                  <div class="algo-step"><div class="algo-step-num">b</div><div class="algo-step-text">Ambil nilai <code>password</code> dari field</div></div>
                  <div class="algo-step"><div class="algo-step-num">c</div><div class="algo-step-text">Kirim via <code>fetch(POST)</code> ke <code>login_action.php</code></div></div>
                  <div class="algo-step"><div class="algo-step-num">d</div><div class="algo-step-text"><strong>Jika</strong> kedua field kosong → Tampilkan pesan error, hentikan proses</div></div>
                </div>
              </div>
              <div class="file-links">
                <a class="file-link php" href="src/User/PHP/login_action.php" target="_blank">
                  <span class="material-symbols-outlined">code</span> login_action.php <span style="opacity:0.5">· baris 19-26</span>
                </a>
                <a class="file-link js" href="src/Core/JS/custom_alert.js" target="_blank">
                  <span class="material-symbols-outlined">javascript</span> custom_alert.js
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Step 2 -->
      <div class="step-item">
        <div class="step-left">
          <div class="step-num c-blue">2</div>
        </div>
        <div class="step-content">
          <div class="step-card card-accent-blue" onclick="toggleStep(this)">
            <div class="step-header">
              <div class="step-title-area">
                <div class="step-algo-label" style="color:var(--blue)">ALGORITHM STEP 2 — DATABASE QUERY</div>
                <div class="step-name">
                  <span class="material-symbols-outlined" style="font-size:16px;color:var(--blue)">database</span>
                  Cari User di Database (OR Query)
                  <span class="step-tag tag-db">DB</span>
                </div>
                <div class="step-desc">PHP mencari data user di tabel <code>users</code> menggunakan kondisi <code>OR</code> — cocokkan dengan email ATAU username sekaligus.</div>
              </div>
              <div class="expand-icon"><span class="material-symbols-outlined" style="font-size:16px">expand_more</span></div>
            </div>
            <div class="step-detail">
              <div class="detail-algo">
                <div class="detail-algo-title"><span class="material-symbols-outlined" style="font-size:12px">functions</span> Algoritma Detail</div>
                <div class="algo-steps">
                  <div class="algo-step"><div class="algo-step-num">a</div><div class="algo-step-text">Panggil <code>getDB()</code> — Singleton PDO connection dari database.php</div></div>
                  <div class="algo-step"><div class="algo-step-num">b</div><div class="algo-step-text">Prepare statement: <code>SELECT ... WHERE email = ? OR username = ?</code></div></div>
                  <div class="algo-step"><div class="algo-step-num">c</div><div class="algo-step-text">Execute dengan binding <code>[$loginInput, $loginInput]</code> (input yang sama dikirim ke kedua kolom)</div></div>
                  <div class="algo-step"><div class="algo-step-num">d</div><div class="algo-step-text">Fetch satu baris hasil → simpan di variabel <code>$user</code></div></div>
                </div>
              </div>
              <div class="file-links">
                <a class="file-link php" href="src/User/PHP/login_action.php" target="_blank">
                  <span class="material-symbols-outlined">code</span> login_action.php <span style="opacity:0.5">· baris 29-32</span>
                </a>
                <a class="file-link php" href="src/Core/PHP/database.php" target="_blank">
                  <span class="material-symbols-outlined">code</span> database.php — <code>getDB()</code> <span style="opacity:0.5">· baris 67-91</span>
                </a>
              </div>
              <div class="info-box"><strong>Kenapa OR?</strong> Sistem mendukung login fleksibel — pengguna bebas memasukkan email atau username, tidak perlu dua field terpisah.</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Step 3 -->
      <div class="step-item">
        <div class="step-left">
          <div class="step-num c-red">3</div>
        </div>
        <div class="step-content">
          <div class="step-card card-accent-red" onclick="toggleStep(this)">
            <div class="step-header">
              <div class="step-title-area">
                <div class="step-algo-label" style="color:var(--red)">ALGORITHM STEP 3 — PASSWORD VERIFICATION</div>
                <div class="step-name">
                  <span class="material-symbols-outlined" style="font-size:16px;color:var(--red)">lock</span>
                  Verifikasi Hash Password (BCrypt)
                  <span class="step-tag tag-security">SECURITY</span>
                </div>
                <div class="step-desc">Password yang diinput dibandingkan dengan hash BCrypt yang tersimpan di database. Proses ini tidak reversible — hash tidak bisa dikembalikan ke teks aslinya.</div>
              </div>
              <div class="expand-icon"><span class="material-symbols-outlined" style="font-size:16px">expand_more</span></div>
            </div>
            <div class="step-detail">
              <div class="detail-algo">
                <div class="detail-algo-title"><span class="material-symbols-outlined" style="font-size:12px">functions</span> Algoritma BCrypt Verification</div>
                <div class="algo-steps">
                  <div class="algo-step"><div class="algo-step-num">a</div><div class="algo-step-text"><strong>IF</strong> <code>$user === false</code> (user tidak ditemukan di DB) → Return error "Incorrect username/email or password"</div></div>
                  <div class="algo-step"><div class="algo-step-num">b</div><div class="algo-step-text">Panggil fungsi bawaan PHP: <code>password_verify($input_password, $stored_hash)</code></div></div>
                  <div class="algo-step"><div class="algo-step-num">c</div><div class="algo-step-text">Fungsi ini secara internal mengekstrak <strong>salt</strong> dari hash tersimpan, lalu menghash ulang password input dengan salt yang sama</div></div>
                  <div class="algo-step"><div class="algo-step-num">d</div><div class="algo-step-text"><strong>IF</strong> hasilnya tidak cocok → Return <code>{success:false}</code> dengan pesan error umum (sengaja samar agar tidak bocor info)</div></div>
                  <div class="algo-step"><div class="algo-step-num">e</div><div class="algo-step-text"><strong>IF</strong> cocok → Lanjut ke pemeriksaan status akun (Step 4)</div></div>
                </div>
              </div>
              <div class="file-links">
                <a class="file-link php" href="src/User/PHP/login_action.php" target="_blank">
                  <span class="material-symbols-outlined">code</span> login_action.php <span style="opacity:0.5">· baris 35-38</span>
                </a>
              </div>
              <div class="info-box"><strong>Kenapa BCrypt?</strong> BCrypt secara otomatis menambahkan <em>salt</em> acak, sehingga dua password yang sama menghasilkan hash yang berbeda. Sangat tahan terhadap serangan <em>rainbow table</em>.</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Step 4 -->
      <div class="step-item">
        <div class="step-left">
          <div class="step-num c-red">4</div>
        </div>
        <div class="step-content">
          <div class="step-card card-accent-red" onclick="toggleStep(this)">
            <div class="step-header">
              <div class="step-title-area">
                <div class="step-algo-label" style="color:var(--red)">ALGORITHM STEP 4 — BAN CHECK</div>
                <div class="step-name">
                  <span class="material-symbols-outlined" style="font-size:16px;color:var(--red)">block</span>
                  Pemeriksaan Status Banned
                  <span class="step-tag tag-security">SECURITY</span>
                </div>
                <div class="step-desc">Meskipun password benar, akun yang diblokir admin tidak boleh masuk. Pemeriksaan kolom <code>role</code> dilakukan sebelum sesi dibuat.</div>
              </div>
              <div class="expand-icon"><span class="material-symbols-outlined" style="font-size:16px">expand_more</span></div>
            </div>
            <div class="step-detail">
              <div class="detail-algo">
                <div class="detail-algo-title"><span class="material-symbols-outlined" style="font-size:12px">functions</span> Algoritma Detail</div>
                <div class="algo-steps">
                  <div class="algo-step"><div class="algo-step-num">a</div><div class="algo-step-text">Periksa nilai <code>$user['role']</code></div></div>
                  <div class="algo-step"><div class="algo-step-num">b</div><div class="algo-step-text"><strong>IF</strong> <code>role === 'banned'</code> → Return JSON error dengan pesan "Account has been deactivated..."</div></div>
                  <div class="algo-step"><div class="algo-step-num">c</div><div class="algo-step-text">Proses <strong>berhenti total</strong> di sini (exit) — sesi tidak pernah dibuat</div></div>
                  <div class="algo-step"><div class="algo-step-num">d</div><div class="algo-step-text"><strong>IF</strong> role != 'banned' → Lanjut ke pembentukan sesi (Step 5)</div></div>
                </div>
              </div>
              <div class="file-links">
                <a class="file-link php" href="src/User/PHP/login_action.php" target="_blank">
                  <span class="material-symbols-outlined">code</span> login_action.php <span style="opacity:0.5">· baris 41-44</span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Step 5 -->
      <div class="step-item">
        <div class="step-left">
          <div class="step-num c-purple">5</div>
        </div>
        <div class="step-content">
          <div class="step-card card-accent-purple" onclick="toggleStep(this)">
            <div class="step-header">
              <div class="step-title-area">
                <div class="step-algo-label" style="color:var(--accent)">ALGORITHM STEP 5 — SESSION CREATION</div>
                <div class="step-name">
                  <span class="material-symbols-outlined" style="font-size:16px;color:var(--accent)">verified_user</span>
                  Buat Sesi PHP yang Aman
                  <span class="step-tag tag-session">SESSION</span>
                </div>
                <div class="step-desc">Sesi baru dibuat dengan ID baru yang diregenerasi untuk mencegah serangan Session Fixation. Data user disimpan ke dalam superglobal <code>$_SESSION</code>.</div>
              </div>
              <div class="expand-icon"><span class="material-symbols-outlined" style="font-size:16px">expand_more</span></div>
            </div>
            <div class="step-detail">
              <div class="detail-algo">
                <div class="detail-algo-title"><span class="material-symbols-outlined" style="font-size:12px">functions</span> Algoritma Session Initialization</div>
                <div class="algo-steps">
                  <div class="algo-step"><div class="algo-step-num">a</div><div class="algo-step-text">Panggil <code>session_regenerate_id(true)</code> — Mengganti ID sesi lama dengan ID baru secara paksa, menghapus file sesi lama</div></div>
                  <div class="algo-step"><div class="algo-step-num">b</div><div class="algo-step-text">Simpan <code>$_SESSION['user_id']</code>, <code>['username']</code>, <code>['name']</code>, <code>['email']</code>, <code>['role']</code></div></div>
                  <div class="algo-step"><div class="algo-step-num">c</div><div class="algo-step-text">Simpan <code>$_SESSION['login_at']</code> = timestamp login saat ini</div></div>
                  <div class="algo-step"><div class="algo-step-num">d</div><div class="algo-step-text">Tentukan <code>$profilePic</code> — jika kosong, gunakan default <code>Pic/profileicon.jpg</code></div></div>
                  <div class="algo-step"><div class="algo-step-num">e</div><div class="algo-step-text">Return JSON <code>{success:true, username, name, profilePic, role}</code> ke browser</div></div>
                </div>
              </div>
              <div class="file-links">
                <a class="file-link php" href="src/User/PHP/login_action.php" target="_blank">
                  <span class="material-symbols-outlined">code</span> login_action.php <span style="opacity:0.5">· baris 48-66</span>
                </a>
              </div>
              <div class="info-box"><strong>Mengapa <code>session_regenerate_id(true)</code>?</strong> Ini melindungi dari serangan <em>Session Fixation Attack</em>, di mana penyerang mencoba menggunakan ID sesi yang sudah diketahuinya sebelum login. Parameter <code>true</code> memastikan sesi lama dihapus dari server.</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Step 6 -->
      <div class="step-item">
        <div class="step-left">
          <div class="step-num c-yellow">6</div>
        </div>
        <div class="step-content">
          <div class="step-card card-accent-yellow" onclick="toggleStep(this)">
            <div class="step-header">
              <div class="step-title-area">
                <div class="step-algo-label" style="color:var(--accent2)">ALGORITHM STEP 6 — SESSION VALIDATION</div>
                <div class="step-name">
                  <span class="material-symbols-outlined" style="font-size:16px;color:var(--accent2)">manage_accounts</span>
                  Validasi Sesi Aktif (Setiap Halaman)
                  <span class="step-tag tag-session">SESSION</span>
                </div>
                <div class="step-desc">Setiap kali halaman dimuat, browser memanggil <code>session_check.php</code> via AJAX untuk memastikan sesi masih valid dan mengambil data terkini.</div>
              </div>
              <div class="expand-icon"><span class="material-symbols-outlined" style="font-size:16px">expand_more</span></div>
            </div>
            <div class="step-detail">
              <div class="detail-algo">
                <div class="detail-algo-title"><span class="material-symbols-outlined" style="font-size:12px">functions</span> Algoritma Validasi Sesi</div>
                <div class="algo-steps">
                  <div class="algo-step"><div class="algo-step-num">a</div><div class="algo-step-text"><strong>IF</strong> <code>$_SESSION['user_id']</code> ada → User masih login</div></div>
                  <div class="algo-step"><div class="algo-step-num">b</div><div class="algo-step-text">Query DB: ambil <code>profile_pic</code> terbaru langsung dari tabel <code>users</code> (bukan dari cache sesi)</div></div>
                  <div class="algo-step"><div class="algo-step-num">c</div><div class="algo-step-text">Return JSON <code>{loggedIn:true, user_id, username, name, email, profilePic, role}</code></div></div>
                  <div class="algo-step"><div class="algo-step-num">d</div><div class="algo-step-text"><strong>ELSE</strong> → Return <code>{loggedIn:false}</code> → Browser tampilkan tombol login</div></div>
                </div>
              </div>
              <div class="file-links">
                <a class="file-link php" href="src/User/PHP/session_check.php" target="_blank">
                  <span class="material-symbols-outlined">code</span> session_check.php
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Step 7 -->
      <div class="step-item">
        <div class="step-left">
          <div class="step-num c-orange">7</div>
        </div>
        <div class="step-content">
          <div class="step-card card-accent-orange" onclick="toggleStep(this)">
            <div class="step-header">
              <div class="step-title-area">
                <div class="step-algo-label" style="color:var(--orange)">ALGORITHM STEP 7 — LOGOUT & CLEANUP</div>
                <div class="step-name">
                  <span class="material-symbols-outlined" style="font-size:16px;color:var(--orange)">logout</span>
                  Logout & Penghancuran Sesi Total
                  <span class="step-tag tag-security">SECURITY</span>
                </div>
                <div class="step-desc">Proses logout bersih: data sesi dihapus dari server, cookie sesi dimatikan dari browser, lalu sesi PHP dihancurkan permanen.</div>
              </div>
              <div class="expand-icon"><span class="material-symbols-outlined" style="font-size:16px">expand_more</span></div>
            </div>
            <div class="step-detail">
              <div class="detail-algo">
                <div class="detail-algo-title"><span class="material-symbols-outlined" style="font-size:12px">functions</span> Algoritma Logout (3 Tahap)</div>
                <div class="algo-steps">
                  <div class="algo-step"><div class="algo-step-num">1</div><div class="algo-step-text"><strong>Kosongkan superglobal:</strong> <code>$_SESSION = []</code> — Menghapus semua data dari array sesi</div></div>
                  <div class="algo-step"><div class="algo-step-num">2</div><div class="algo-step-text"><strong>Hapus Cookie:</strong> Panggil <code>setcookie(session_name(), '', time()-42000, ...)</code> — Menyuruh browser menghapus cookie sesi dengan mengatur waktu kedaluwarsa di masa lalu</div></div>
                  <div class="algo-step"><div class="algo-step-num">3</div><div class="algo-step-text"><strong>Hancurkan Sesi:</strong> <code>session_unset()</code> + <code>session_destroy()</code> — Menghapus file sesi dari server secara permanen</div></div>
                  <div class="algo-step"><div class="algo-step-num">4</div><div class="algo-step-text"><strong>Deteksi AJAX:</strong> Jika header <code>X-Requested-With: XMLHttpRequest</code> ada → Return JSON, jika tidak → Redirect ke homepage</div></div>
                </div>
              </div>
              <div class="file-links">
                <a class="file-link php" href="src/User/PHP/logout.php" target="_blank">
                  <span class="material-symbols-outlined">code</span> logout.php
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div><!-- end steps-flow -->
  </div><!-- end flow-login -->


  <!-- ═══════════════════════════════════════════════════════════
       FLOW 2 — REGISTER
       ═══════════════════════════════════════════════════════════ -->
  <div id="flow-register" class="flow-section">
    <div class="section-title">
      <div class="icon-circle" style="background:rgba(52,152,219,0.12); color:var(--blue);">
        <span class="material-symbols-outlined">person_add</span>
      </div>
      Alur Registrasi Akun Baru
    </div>
    <div class="steps-flow">

      <div class="step-item">
        <div class="step-left"><div class="step-num c-blue">1</div></div>
        <div class="step-content">
          <div class="step-card card-accent-blue" onclick="toggleStep(this)">
            <div class="step-header">
              <div class="step-title-area">
                <div class="step-algo-label" style="color:var(--blue)">STEP 1 — INPUT VALIDATION CHAIN</div>
                <div class="step-name"><span class="material-symbols-outlined" style="font-size:16px;color:var(--blue)">checklist</span> Validasi Berantai 5 Lapis <span class="step-tag tag-security">SECURITY</span></div>
                <div class="step-desc">Register menerapkan validasi berlapis sebelum data menyentuh database.</div>
              </div>
              <div class="expand-icon"><span class="material-symbols-outlined" style="font-size:16px">expand_more</span></div>
            </div>
            <div class="step-detail">
              <div class="detail-algo">
                <div class="detail-algo-title"><span class="material-symbols-outlined" style="font-size:12px">functions</span> Rantai Validasi (Short-Circuit)</div>
                <div class="algo-steps">
                  <div class="algo-step"><div class="algo-step-num">1</div><div class="algo-step-text"><strong>Lapis 1:</strong> Cek semua field tidak kosong (<code>empty()</code>)</div></div>
                  <div class="algo-step"><div class="algo-step-num">2</div><div class="algo-step-text"><strong>Lapis 2:</strong> Username min 3 karakter + Regex <code>/^[a-zA-Z0-9_]+$/</code> — Hanya huruf, angka, underscore</div></div>
                  <div class="algo-step"><div class="algo-step-num">3</div><div class="algo-step-text"><strong>Lapis 3:</strong> Email valid dengan <code>filter_var($email, FILTER_VALIDATE_EMAIL)</code></div></div>
                  <div class="algo-step"><div class="algo-step-num">4</div><div class="algo-step-text"><strong>Lapis 4:</strong> Password minimal 8 karakter</div></div>
                  <div class="algo-step"><div class="algo-step-num">5</div><div class="algo-step-text"><strong>Lapis 5:</strong> Cek duplikasi email & username di DB (dua query terpisah)</div></div>
                </div>
              </div>
              <div class="file-links">
                <a class="file-link php" href="src/User/PHP/register_action.php" target="_blank">
                  <span class="material-symbols-outlined">code</span> register_action.php <span style="opacity:0.5">· baris 23-57</span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="step-item">
        <div class="step-left"><div class="step-num c-red">2</div></div>
        <div class="step-content">
          <div class="step-card card-accent-red" onclick="toggleStep(this)">
            <div class="step-header">
              <div class="step-title-area">
                <div class="step-algo-label" style="color:var(--red)">STEP 2 — PASSWORD HASHING</div>
                <div class="step-name"><span class="material-symbols-outlined" style="font-size:16px;color:var(--red)">enhanced_encryption</span> Enkripsi Password BCrypt <span class="step-tag tag-security">SECURITY</span></div>
                <div class="step-desc">Password TIDAK boleh disimpan dalam bentuk teks biasa. BCrypt menghasilkan hash yang tidak bisa didekripsi balik.</div>
              </div>
              <div class="expand-icon"><span class="material-symbols-outlined" style="font-size:16px">expand_more</span></div>
            </div>
            <div class="step-detail">
              <div class="detail-algo">
                <div class="detail-algo-title"><span class="material-symbols-outlined" style="font-size:12px">functions</span> Proses Hashing BCrypt</div>
                <div class="algo-steps">
                  <div class="algo-step"><div class="algo-step-num">a</div><div class="algo-step-text">Panggil <code>password_hash($password, PASSWORD_BCRYPT)</code></div></div>
                  <div class="algo-step"><div class="algo-step-num">b</div><div class="algo-step-text">PHP secara otomatis menghasilkan <strong>salt acak</strong> dan menggabungkannya dengan password</div></div>
                  <div class="algo-step"><div class="algo-step-num">c</div><div class="algo-step-text">Hasil: string hash 60 karakter, misalnya: <code>$2y$10$abc123...</code></div></div>
                  <div class="algo-step"><div class="algo-step-num">d</div><div class="algo-step-text">Hash ini yang disimpan ke kolom <code>password</code> di tabel <code>users</code></div></div>
                </div>
              </div>
              <div class="file-links">
                <a class="file-link php" href="src/User/PHP/register_action.php" target="_blank">
                  <span class="material-symbols-outlined">code</span> register_action.php <span style="opacity:0.5">· baris 60-63</span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="step-item">
        <div class="step-left"><div class="step-num c-purple">3</div></div>
        <div class="step-content">
          <div class="step-card card-accent-purple" onclick="toggleStep(this)">
            <div class="step-header">
              <div class="step-title-area">
                <div class="step-algo-label" style="color:var(--accent)">STEP 3 — AUTO LOGIN AFTER REGISTER</div>
                <div class="step-name"><span class="material-symbols-outlined" style="font-size:16px;color:var(--accent)">auto_awesome</span> Sesi Otomatis Setelah Register <span class="step-tag tag-session">SESSION</span></div>
                <div class="step-desc">Setelah registrasi berhasil, user langsung diloginkan tanpa harus mengisi form login lagi — UX yang lebih mulus.</div>
              </div>
              <div class="expand-icon"><span class="material-symbols-outlined" style="font-size:16px">expand_more</span></div>
            </div>
            <div class="step-detail">
              <div class="detail-algo">
                <div class="detail-algo-title"><span class="material-symbols-outlined" style="font-size:12px">functions</span> Auto-Login Flow</div>
                <div class="algo-steps">
                  <div class="algo-step"><div class="algo-step-num">a</div><div class="algo-step-text">Setelah INSERT berhasil, ambil ID baru: <code>$pdo->lastInsertId()</code></div></div>
                  <div class="algo-step"><div class="algo-step-num">b</div><div class="algo-step-text">Panggil <code>session_regenerate_id(true)</code> untuk keamanan sesi</div></div>
                  <div class="algo-step"><div class="algo-step-num">c</div><div class="algo-step-text">Tulis ke <code>$_SESSION</code>: <code>user_id</code>, <code>username</code>, <code>name</code>, <code>email</code>, <code>login_at</code></div></div>
                  <div class="algo-step"><div class="algo-step-num">d</div><div class="algo-step-text">Return <code>{success:true, username, name}</code> → Browser lakukan redirect ke homepage</div></div>
                </div>
              </div>
              <div class="file-links">
                <a class="file-link php" href="src/User/PHP/register_action.php" target="_blank">
                  <span class="material-symbols-outlined">code</span> register_action.php <span style="opacity:0.5">· baris 67-79</span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div><!-- end flow-register -->


  <!-- ═══════════════════════════════════════════════════════════
       FLOW 3 — ALUR KERJA ADMIN
       ═══════════════════════════════════════════════════════════ -->
  <div id="flow-admin" class="flow-section">
    <div class="section-title">
      <div class="icon-circle" style="background:rgba(255,244,79,0.1); color:var(--accent2);">
        <span class="material-symbols-outlined">admin_panel_settings</span>
      </div>
      Alur Kerja Lengkap Panel Admin (Etmin.php)
    </div>
    <div class="steps-flow">

      <!-- A1 -->
      <div class="step-item">
        <div class="step-left"><div class="step-num c-red">A</div></div>
        <div class="step-content">
          <div class="step-card card-accent-red" onclick="toggleStep(this)">
            <div class="step-header">
              <div class="step-title-area">
                <div class="step-algo-label" style="color:var(--red)">GATE — ACCESS CONTROL</div>
                <div class="step-name"><span class="material-symbols-outlined" style="font-size:16px;color:var(--red)">security</span> Penjaga Akses Admin (Gate) <span class="step-tag tag-security">SECURITY</span></div>
                <div class="step-desc">Halaman Etmin.php melakukan verifikasi role langsung di baris pertama PHP — sebelum HTML dikirim ke browser.</div>
              </div>
              <div class="expand-icon"><span class="material-symbols-outlined" style="font-size:16px">expand_more</span></div>
            </div>
            <div class="step-detail">
              <div class="detail-algo">
                <div class="detail-algo-title"><span class="material-symbols-outlined" style="font-size:12px">functions</span> Algoritma Server-Side Gate</div>
                <div class="algo-steps">
                  <div class="algo-step"><div class="algo-step-num">a</div><div class="algo-step-text"><code>session_start()</code> → Muat data sesi dari server</div></div>
                  <div class="algo-step"><div class="algo-step-num">b</div><div class="algo-step-text"><strong>IF</strong> <code>!isset($_SESSION['user_id'])</code> → Belum login → <code>header("Location: homepage.php")</code> + <code>exit</code></div></div>
                  <div class="algo-step"><div class="algo-step-num">c</div><div class="algo-step-text"><strong>IF</strong> <code>$_SESSION['role'] !== 'admin'</code> → Bukan admin → Redirect ke homepage</div></div>
                  <div class="algo-step"><div class="algo-step-num">d</div><div class="algo-step-text"><strong>ELSE</strong> → Render halaman admin → Teruskan ke browser</div></div>
                </div>
              </div>
              <div class="file-links">
                <a class="file-link php" href="Etmin.php" target="_blank">
                  <span class="material-symbols-outlined">code</span> Etmin.php <span style="opacity:0.5">· baris 5-11</span>
                </a>
              </div>
              <div class="info-box"><strong>Penting:</strong> Setiap endpoint API admin (<code>admin_action.php</code>, <code>admin_get_stats.php</code>, dll) juga melakukan pengecekan yang sama secara mandiri, tidak hanya mengandalkan penjaga halaman Etmin.php.</div>
            </div>
          </div>
        </div>
      </div>

      <!-- A2 -->
      <div class="step-item">
        <div class="step-left"><div class="step-num c-yellow">B</div></div>
        <div class="step-content">
          <div class="step-card card-accent-yellow" onclick="toggleStep(this)">
            <div class="step-header">
              <div class="step-title-area">
                <div class="step-algo-label" style="color:var(--accent2)">STEP B — SINGLE PAGE APP INIT</div>
                <div class="step-name"><span class="material-symbols-outlined" style="font-size:16px;color:var(--accent2)">pages</span> Inisialisasi SPA + Tab Dispatcher <span class="step-tag tag-ui">UI</span></div>
                <div class="step-desc">admin.js diinisialisasi setelah DOM siap. Seluruh tab (Dashboard, Users, Stories, Reports, Analytics) dikontrol oleh satu fungsi dispatcher tanpa reload halaman.</div>
              </div>
              <div class="expand-icon"><span class="material-symbols-outlined" style="font-size:16px">expand_more</span></div>
            </div>
            <div class="step-detail">
              <div class="detail-algo">
                <div class="detail-algo-title"><span class="material-symbols-outlined" style="font-size:12px">functions</span> Tab Dispatcher Algorithm</div>
                <div class="algo-steps">
                  <div class="algo-step"><div class="algo-step-num">a</div><div class="algo-step-text">Cek URL parameter: <code>URLSearchParams('tab')</code> — Jika ada <code>?tab=dashboard</code>, langsung buka tab itu</div></div>
                  <div class="algo-step"><div class="algo-step-num">b</div><div class="algo-step-text">Fungsi <code>switchTab(tabId)</code>: Toggle class <code>active</code> pada menu link dan panel section</div></div>
                  <div class="algo-step"><div class="algo-step-num">c</div><div class="algo-step-text">Update judul halaman dari <code>titleMap</code> object secara dinamis</div></div>
                  <div class="algo-step"><div class="algo-step-num">d</div><div class="algo-step-text">Panggil <code>loadTabData(tabId)</code> → Dispatch ke fungsi loader yang sesuai</div></div>
                </div>
              </div>
              <div class="file-links">
                <a class="file-link js" href="src/Admin/JS/admin.js" target="_blank">
                  <span class="material-symbols-outlined">javascript</span> admin.js — <code>switchTab()</code> <span style="opacity:0.5">· baris 159-201</span>
                </a>
                <a class="file-link js" href="src/Admin/JS/admin.js" target="_blank">
                  <span class="material-symbols-outlined">javascript</span> admin.js — <code>System Init</code> <span style="opacity:0.5">· baris 879-891</span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- A3 -->
      <div class="step-item">
        <div class="step-left"><div class="step-num c-blue">C</div></div>
        <div class="step-content">
          <div class="step-card card-accent-blue" onclick="toggleStep(this)">
            <div class="step-header">
              <div class="step-title-area">
                <div class="step-algo-label" style="color:var(--blue)">STEP C — DASHBOARD DATA LOADING</div>
                <div class="step-name"><span class="material-symbols-outlined" style="font-size:16px;color:var(--blue)">dashboard</span> Muat Data Dashboard (Fetch Chain) <span class="step-tag tag-api">API</span></div>
                <div class="step-desc">Saat tab Dashboard dibuka, JS memanggil API stats dan merender tabel cerita terbaru beserta tombol aksi. Backend memquery 4 COUNT dari DB.</div>
              </div>
              <div class="expand-icon"><span class="material-symbols-outlined" style="font-size:16px">expand_more</span></div>
            </div>
            <div class="step-detail">
              <div class="detail-algo">
                <div class="detail-algo-title"><span class="material-symbols-outlined" style="font-size:12px">functions</span> Algoritma loadDashboard()</div>
                <div class="algo-steps">
                  <div class="algo-step"><div class="algo-step-num">a</div><div class="algo-step-text">JS: <code>apiFetch('admin_get_stats.php')</code> → GET request ke backend</div></div>
                  <div class="algo-step"><div class="algo-step-num">b</div><div class="algo-step-text">PHP: Jalankan 4 query COUNT secara paralel: <code>total_users</code>, <code>total_stories</code>, <code>pending_review</code>, <code>total_reports</code></div></div>
                  <div class="algo-step"><div class="algo-step-num">c</div><div class="algo-step-text">PHP: Query tambahan untuk 10 cerita terbaru (JOIN dengan <code>users</code> dan <code>genres</code>)</div></div>
                  <div class="algo-step"><div class="algo-step-num">d</div><div class="algo-step-text">JS: Isi angka ke kartu statistik, render baris tabel cerita dengan tombol Approve/Reject/Delete</div></div>
                  <div class="algo-step"><div class="algo-step-num">e</div><div class="algo-step-text">JS: Bind event listener ke setiap tombol → memanggil <code>confirmAction()</code></div></div>
                </div>
              </div>
              <div class="file-links">
                <a class="file-link js" href="src/Admin/JS/admin.js" target="_blank">
                  <span class="material-symbols-outlined">javascript</span> admin.js — <code>loadDashboard()</code> <span style="opacity:0.5">· baris 204-269</span>
                </a>
                <a class="file-link php" href="src/Admin/PHP/admin_get_stats.php" target="_blank">
                  <span class="material-symbols-outlined">code</span> admin_get_stats.php <span style="opacity:0.5">· baris 18-53</span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- A4 -->
      <div class="step-item">
        <div class="step-left"><div class="step-num c-purple">D</div></div>
        <div class="step-content">
          <div class="step-card card-accent-purple" onclick="toggleStep(this)">
            <div class="step-header">
              <div class="step-title-area">
                <div class="step-algo-label" style="color:var(--accent)">STEP D — ACTION CONFIRMATION MODAL</div>
                <div class="step-name"><span class="material-symbols-outlined" style="font-size:16px;color:var(--accent)">warning</span> Modal Konfirmasi Aksi Destruktif <span class="step-tag tag-ui">UI</span></div>
                <div class="step-desc">Sebelum eksekusi aksi berbahaya (ban, delete, dll), sistem menampilkan modal konfirmasi. Aksi disimpan ke state global dan baru dieksekusi setelah tombol Confirm diklik.</div>
              </div>
              <div class="expand-icon"><span class="material-symbols-outlined" style="font-size:16px">expand_more</span></div>
            </div>
            <div class="step-detail">
              <div class="detail-algo">
                <div class="detail-algo-title"><span class="material-symbols-outlined" style="font-size:12px">functions</span> Algoritma confirmAction() + executeAction()</div>
                <div class="algo-steps">
                  <div class="algo-step"><div class="algo-step-num">a</div><div class="algo-step-text"><code>confirmAction(actionName, id, promptText)</code>: Simpan ke <code>state.pendingAction</code>, tampilkan modal</div></div>
                  <div class="algo-step"><div class="algo-step-num">b</div><div class="algo-step-text">Warna tombol Confirm berubah: <strong>Merah</strong> untuk aksi destruktif (delete/ban), <strong>Kuning</strong> untuk approve/resolve</div></div>
                  <div class="algo-step"><div class="algo-step-num">c</div><div class="algo-step-text">User klik Confirm → <code>executeAction()</code> dipanggil</div></div>
                  <div class="algo-step"><div class="algo-step-num">d</div><div class="algo-step-text">Build payload JSON berdasarkan tipe aksi (user_id / story_id / report_id)</div></div>
                  <div class="algo-step"><div class="algo-step-num">e</div><div class="algo-step-text">Kirim POST ke <code>admin_action.php</code> → Tunggu respons → Tutup modal → Reload tab aktif</div></div>
                </div>
              </div>
              <div class="file-links">
                <a class="file-link js" href="src/Admin/JS/admin.js" target="_blank">
                  <span class="material-symbols-outlined">javascript</span> admin.js — <code>confirmAction()</code> <span style="opacity:0.5">· baris 582-596</span>
                </a>
                <a class="file-link js" href="src/Admin/JS/admin.js" target="_blank">
                  <span class="material-symbols-outlined">javascript</span> admin.js — <code>executeAction()</code> <span style="opacity:0.5">· baris 598-631</span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- A5 -->
      <div class="step-item">
        <div class="step-left"><div class="step-num c-orange">E</div></div>
        <div class="step-content">
          <div class="step-card card-accent-orange" onclick="toggleStep(this)">
            <div class="step-header">
              <div class="step-title-area">
                <div class="step-algo-label" style="color:var(--orange)">STEP E — BACKEND SWITCH-CASE DISPATCHER</div>
                <div class="step-name"><span class="material-symbols-outlined" style="font-size:16px;color:var(--orange)">api</span> admin_action.php — Switch Dispatcher <span class="step-tag tag-api">API</span></div>
                <div class="step-desc">Satu endpoint PHP menangani SEMUA aksi admin melalui Switch-Case berdasarkan field "action" dari JSON body. Setiap case terisolasi dan dilindungi try-catch.</div>
              </div>
              <div class="expand-icon"><span class="material-symbols-outlined" style="font-size:16px">expand_more</span></div>
            </div>
            <div class="step-detail">
              <div class="detail-algo">
                <div class="detail-algo-title"><span class="material-symbols-outlined" style="font-size:12px">functions</span> Switch Dispatcher Logic</div>
                <div class="algo-steps">
                  <div class="algo-step"><div class="algo-step-num">a</div><div class="algo-step-text">Decode JSON: <code>json_decode(file_get_contents('php://input'), true)</code></div></div>
                  <div class="algo-step"><div class="algo-step-num">b</div><div class="algo-step-text">Baca field <code>$action</code> → Masuk ke <code>switch($action)</code></div></div>
                  <div class="algo-step"><div class="algo-step-num">c</div><div class="algo-step-text"><code>ban_user</code>: UPDATE role='banned' (exclude admin)</div></div>
                  <div class="algo-step"><div class="algo-step-num">d</div><div class="algo-step-text"><code>delete_user</code>: FK Checks OFF → Hapus relasi → Hapus user → FK Checks ON</div></div>
                  <div class="algo-step"><div class="algo-step-num">e</div><div class="algo-step-text"><code>approve_story</code>: SET status='published', published_at=NOW()</div></div>
                  <div class="algo-step"><div class="algo-step-num">f</div><div class="algo-step-text"><code>delete_story</code>: Hapus berantai — bubbles → roomchats → chapter_blocks → comments → chapters → story</div></div>

                </div>
              </div>
              <div class="file-links">
                <a class="file-link php" href="src/Admin/PHP/admin_action.php" target="_blank">
                  <span class="material-symbols-outlined">code</span> admin_action.php <span style="opacity:0.5">· baris 50-204</span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- A6 -->
      <div class="step-item">
        <div class="step-left"><div class="step-num c-blue">F</div></div>
        <div class="step-content">
          <div class="step-card card-accent-blue" onclick="toggleStep(this)">
            <div class="step-header">
              <div class="step-title-area">
                <div class="step-algo-label" style="color:var(--blue)">STEP F — ANALYTICS & CHART.JS</div>
                <div class="step-name"><span class="material-symbols-outlined" style="font-size:16px;color:var(--blue)">monitoring</span> Render Chart Analitik Genre <span class="step-tag tag-ui">UI</span></div>
                <div class="step-desc">Tab Analytics memanggil API analytics, lalu menggunakan Chart.js untuk merender grafik lingkaran sebaran genre cerita secara dinamis dari data database.</div>
              </div>
              <div class="expand-icon"><span class="material-symbols-outlined" style="font-size:16px">expand_more</span></div>
            </div>
            <div class="step-detail">
              <div class="detail-algo">
                <div class="detail-algo-title"><span class="material-symbols-outlined" style="font-size:12px">functions</span> loadAnalytics() → Chart.js Flow</div>
                <div class="algo-steps">
                  <div class="algo-step"><div class="algo-step-num">a</div><div class="algo-step-text">Fetch <code>admin_get_analytics.php</code> → PHP query: Cerita per genre (GROUP BY genre), Top 5 Author, Total Views/Likes/Comments/Follows</div></div>
                  <div class="algo-step"><div class="algo-step-num">b</div><div class="algo-step-text">Jika chart sebelumnya masih ada → panggil <code>state.genreChart.destroy()</code> untuk menghindari duplikasi canvas</div></div>
                  <div class="algo-step"><div class="algo-step-num">c</div><div class="algo-step-text">Buat instance <code>new Chart(ctx, {type: 'doughnut', data: {...}})</code></div></div>
                  <div class="algo-step"><div class="algo-step-num">d</div><div class="algo-step-text">Data labels = nama genre, data values = jumlah cerita per genre, warna dikodekan manual</div></div>
                  <div class="algo-step"><div class="algo-step-num">e</div><div class="algo-step-text">Render daftar Top Authors secara dinamis dengan DOM appendChild</div></div>
                </div>
              </div>
              <div class="file-links">
                <a class="file-link js" href="src/Admin/JS/admin.js" target="_blank">
                  <span class="material-symbols-outlined">javascript</span> admin.js — <code>loadAnalytics()</code> <span style="opacity:0.5">· baris 496-579</span>
                </a>
                <a class="file-link php" href="src/Admin/PHP/admin_get_analytics.php" target="_blank">
                  <span class="material-symbols-outlined">code</span> admin_get_analytics.php <span style="opacity:0.5">· baris 19-56</span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div><!-- end flow-admin -->


  <!-- ═══════════════════════════════════════════════════════════
       FLOW 4 — MODERASI LAPORAN
       ═══════════════════════════════════════════════════════════ -->
  <div id="flow-report" class="flow-section">
    <div class="section-title">
      <div class="icon-circle" style="background:rgba(231,76,60,0.1); color:var(--red);">
        <span class="material-symbols-outlined">gavel</span>
      </div>
      Alur Pelaporan & Moderasi Laporan Pelanggaran
    </div>
    <div class="steps-flow">

      <div class="step-item">
        <div class="step-left"><div class="step-num c-orange">1</div></div>
        <div class="step-content">
          <div class="step-card card-accent-orange" onclick="toggleStep(this)">
            <div class="step-header">
              <div class="step-title-area">
                <div class="step-algo-label" style="color:var(--orange)">STEP 1 — USER SUBMITS REPORT</div>
                <div class="step-name"><span class="material-symbols-outlined" style="font-size:16px;color:var(--orange)">flag</span> Pengguna Melaporkan Cerita/Akun <span class="step-tag tag-auth">USER</span></div>
                <div class="step-desc">Dari halaman cerita atau profil orang lain, user mengklik tombol report dan mengisi alasan pelanggaran.</div>
              </div>
              <div class="expand-icon"><span class="material-symbols-outlined" style="font-size:16px">expand_more</span></div>
            </div>
            <div class="step-detail">
              <div class="detail-algo">
                <div class="detail-algo-title"><span class="material-symbols-outlined" style="font-size:12px">functions</span> Algoritma Report Submission</div>
                <div class="algo-steps">
                  <div class="algo-step"><div class="algo-step-num">a</div><div class="algo-step-text">Cek login: <code>isset($_SESSION['user_id'])</code> — Jika belum login → 401 Unauthorized</div></div>
                  <div class="algo-step"><div class="algo-step-num">b</div><div class="algo-step-text">Parse input: Mendukung JSON body (fetch AJAX) dan Form POST biasa secara otomatis</div></div>
                  <div class="algo-step"><div class="algo-step-num">c</div><div class="algo-step-text">Validasi: Wajib ada salah satu dari <code>reported_user_id</code> atau <code>reported_story_id</code></div></div>
                  <div class="algo-step"><div class="algo-step-num">d</div><div class="algo-step-text">INSERT ke tabel <code>reports</code>: reporter_id, reported_user/story_id, reason, description, status='pending'</div></div>
                </div>
              </div>
              <div class="file-links">
                <a class="file-link php" href="src/Report/PHP/report_action.php" target="_blank">
                  <span class="material-symbols-outlined">code</span> report_action.php
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="step-item">
        <div class="step-left"><div class="step-num c-blue">2</div></div>
        <div class="step-content">
          <div class="step-card card-accent-blue" onclick="toggleStep(this)">
            <div class="step-header">
              <div class="step-title-area">
                <div class="step-algo-label" style="color:var(--blue)">STEP 2 — ADMIN REVIEWS REPORTS</div>
                <div class="step-name"><span class="material-symbols-outlined" style="font-size:16px;color:var(--blue)">manage_search</span> Admin Melihat & Filter Laporan <span class="step-tag tag-api">API</span></div>
                <div class="step-desc">Admin membuka tab Reports, sistem memfilter laporan berdasarkan status tab pill (pending/resolved/dismissed) dengan paginasi.</div>
              </div>
              <div class="expand-icon"><span class="material-symbols-outlined" style="font-size:16px">expand_more</span></div>
            </div>
            <div class="step-detail">
              <div class="detail-algo">
                <div class="detail-algo-title"><span class="material-symbols-outlined" style="font-size:12px">functions</span> Query JOIN Reports</div>
                <div class="algo-steps">
                  <div class="algo-step"><div class="algo-step-num">a</div><div class="algo-step-text">Terima parameter GET: <code>?status=pending&page=1</code></div></div>
                  <div class="algo-step"><div class="algo-step-num">b</div><div class="algo-step-text">Query dengan 3 LEFT JOIN: <code>reports</code> ← <code>users</code> (reporter), <code>users</code> (reported_user), <code>stories</code> (reported_story)</div></div>
                  <div class="algo-step"><div class="algo-step-num">c</div><div class="algo-step-text">Hitung COUNT untuk setiap status (pending/resolved/dismissed) untuk update badge pill counter</div></div>
                  <div class="algo-step"><div class="algo-step-num">d</div><div class="algo-step-text">Return JSON: array reports + total count + counts per status</div></div>
                </div>
              </div>
              <div class="file-links">
                <a class="file-link php" href="src/Report/PHP/admin_get_reports.php" target="_blank">
                  <span class="material-symbols-outlined">code</span> admin_get_reports.php <span style="opacity:0.5">· baris 43-66</span>
                </a>
                <a class="file-link js" href="src/Admin/JS/admin.js" target="_blank">
                  <span class="material-symbols-outlined">javascript</span> admin.js — <code>loadReports()</code> <span style="opacity:0.5">· baris 437-493</span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="step-item">
        <div class="step-left"><div class="step-num c-purple">3</div></div>
        <div class="step-content">
          <div class="step-card card-accent-purple" onclick="toggleStep(this)">
            <div class="step-header">
              <div class="step-title-area">
                <div class="step-algo-label" style="color:var(--accent)">STEP 3 — ADMIN TAKES ACTION</div>
                <div class="step-name"><span class="material-symbols-outlined" style="font-size:16px;color:var(--accent)">gavel</span> Putusan Admin: Resolve atau Dismiss <span class="step-tag tag-api">API</span></div>
                <div class="step-desc">Admin mengklik View Detail laporan, membaca deskripsi, lalu memutuskan: Resolve (valid) atau Dismiss (tidak valid).</div>
              </div>
              <div class="expand-icon"><span class="material-symbols-outlined" style="font-size:16px">expand_more</span></div>
            </div>
            <div class="step-detail">
              <div class="detail-algo">
                <div class="detail-algo-title"><span class="material-symbols-outlined" style="font-size:12px">functions</span> Algoritma Decision Flow</div>
                <div class="algo-steps">
                  <div class="algo-step"><div class="algo-step-num">a</div><div class="algo-step-text">Klik tombol mata (View) → <code>openReportDetails(report)</code> → Tampilkan modal detail dengan data pelapor & objek yang dilaporkan</div></div>
                  <div class="algo-step"><div class="algo-step-num">b</div><div class="algo-step-text"><strong>IF</strong> <code>report.status !== 'pending'</code> → Sembunyikan tombol aksi (sudah diproses sebelumnya)</div></div>
                  <div class="algo-step"><div class="algo-step-num">c</div><div class="algo-step-text">Klik <strong>Resolve</strong> → <code>confirmAction('resolve_report', report_id, ...)</code> → admin_action.php → <code>UPDATE reports SET status='resolved'</code></div></div>
                  <div class="algo-step"><div class="algo-step-num">d</div><div class="algo-step-text">Klik <strong>Dismiss</strong> → <code>confirmAction('dismiss_report', report_id, ...)</code> → admin_action.php → <code>UPDATE reports SET status='dismissed'</code></div></div>
                  <div class="algo-step"><div class="algo-step-num">e</div><div class="algo-step-text">Setelah aksi → Reload tab reports untuk merefleksikan perubahan counter</div></div>
                </div>
              </div>
              <div class="file-links">
                <a class="file-link js" href="src/Admin/JS/admin.js" target="_blank">
                  <span class="material-symbols-outlined">javascript</span> admin.js — <code>openReportDetails()</code> <span style="opacity:0.5">· baris 634-683</span>
                </a>
                <a class="file-link php" href="src/Admin/PHP/admin_action.php" target="_blank">
                  <span class="material-symbols-outlined">code</span> admin_action.php <span style="opacity:0.5">· baris 170-182</span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div><!-- end flow-report -->


  <!-- ═══════════════════════════════════════════════════════════
       FLOW 5 — SETTINGS
       ═══════════════════════════════════════════════════════════ -->
  <div id="flow-settings" class="flow-section">
    <div class="section-title">
      <div class="icon-circle" style="background:rgba(52,152,219,0.1); color:var(--blue);">
        <span class="material-symbols-outlined">settings</span>
      </div>
      Alur Pengaturan Akun & Keamanan Pengguna
    </div>
    <div class="steps-flow">

      <div class="step-item">
        <div class="step-left"><div class="step-num c-blue">1</div></div>
        <div class="step-content">
          <div class="step-card card-accent-blue" onclick="toggleStep(this)">
            <div class="step-header">
              <div class="step-title-area">
                <div class="step-algo-label" style="color:var(--blue)">STEP 1 — DYNAMIC POPUP SYSTEM</div>
                <div class="step-name"><span class="material-symbols-outlined" style="font-size:16px;color:var(--blue)">open_in_new</span> Popup Modal Dinamis (setting.js) <span class="step-tag tag-ui">UI</span></div>
                <div class="step-desc">setting.js mengelola satu elemen popup yang kontennya diganti secara dinamis tergantung menu yang diklik — tanpa membuat multiple modal terpisah.</div>
              </div>
              <div class="expand-icon"><span class="material-symbols-outlined" style="font-size:16px">expand_more</span></div>
            </div>
            <div class="step-detail">
              <div class="detail-algo">
                <div class="detail-algo-title"><span class="material-symbols-outlined" style="font-size:12px">functions</span> Dynamic Popup Algorithm</div>
                <div class="algo-steps">
                  <div class="algo-step"><div class="algo-step-num">a</div><div class="algo-step-text">Setiap <code>.item-set</code> memiliki atribut <code>data-popup="account|email|password|logout|faq"</code></div></div>
                  <div class="algo-step"><div class="algo-step-num">b</div><div class="algo-step-text">Klik → <code>openMenuPopup(type)</code> dipanggil → Tampilkan overlay + popup</div></div>
                  <div class="algo-step"><div class="algo-step-num">c</div><div class="algo-step-text">Untuk tipe "account" dan "email": Fetch <code>settings_get_profile.php</code> dulu untuk mengisi nilai awal form</div></div>
                  <div class="algo-step"><div class="algo-step-num">d</div><div class="algo-step-text">Tulis HTML form ke <code>popupBody.innerHTML</code> berdasarkan tipe yang dipilih</div></div>
                  <div class="algo-step"><div class="algo-step-num">e</div><div class="algo-step-text">Bind event listener ke tombol Save yang baru dibuat</div></div>
                </div>
              </div>
              <div class="file-links">
                <a class="file-link js" href="src/User/JS/setting.js" target="_blank">
                  <span class="material-symbols-outlined">javascript</span> setting.js — <code>openMenuPopup()</code> <span style="opacity:0.5">· baris 25-225</span>
                </a>
                <a class="file-link php" href="Setting.php" target="_blank">
                  <span class="material-symbols-outlined">code</span> Setting.php
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="step-item">
        <div class="step-left"><div class="step-num c-orange">2</div></div>
        <div class="step-content">
          <div class="step-card card-accent-orange" onclick="toggleStep(this)">
            <div class="step-header">
              <div class="step-title-area">
                <div class="step-algo-label" style="color:var(--orange)">STEP 2 — EMAIL UPDATE (WITH AUTH)</div>
                <div class="step-name"><span class="material-symbols-outlined" style="font-size:16px;color:var(--orange)">alternate_email</span> Ganti Email + Verifikasi Password <span class="step-tag tag-security">SECURITY</span></div>
                <div class="step-desc">Untuk mengubah email, pengguna wajib memasukkan password saat ini sebagai konfirmasi identitas. Sistem juga memeriksa keunikan email baru.</div>
              </div>
              <div class="expand-icon"><span class="material-symbols-outlined" style="font-size:16px">expand_more</span></div>
            </div>
            <div class="step-detail">
              <div class="detail-algo">
                <div class="detail-algo-title"><span class="material-symbols-outlined" style="font-size:12px">functions</span> Email Update Algorithm</div>
                <div class="algo-steps">
                  <div class="algo-step"><div class="algo-step-num">a</div><div class="algo-step-text">Validasi format email baru dengan <code>filter_var($new_email, FILTER_VALIDATE_EMAIL)</code></div></div>
                  <div class="algo-step"><div class="algo-step-num">b</div><div class="algo-step-text">Ambil hash password user dari DB, verifikasi dengan <code>password_verify($password, $stored_hash)</code></div></div>
                  <div class="algo-step"><div class="algo-step-num">c</div><div class="algo-step-text">Cek uniqueness: <code>SELECT ... WHERE email = ? AND user_id != ?</code> — Email baru tidak boleh milik akun lain</div></div>
                  <div class="algo-step"><div class="algo-step-num">d</div><div class="algo-step-text">Jika lolos semua → <code>UPDATE users SET email = ?</code></div></div>
                </div>
              </div>
              <div class="file-links">
                <a class="file-link php" href="src/User/PHP/settings_update_email.php" target="_blank">
                  <span class="material-symbols-outlined">code</span> settings_update_email.php
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="step-item">
        <div class="step-left"><div class="step-num c-red">3</div></div>
        <div class="step-content">
          <div class="step-card card-accent-red" onclick="toggleStep(this)">
            <div class="step-header">
              <div class="step-title-area">
                <div class="step-algo-label" style="color:var(--red)">STEP 3 — SELF-DELETE ACCOUNT</div>
                <div class="step-name"><span class="material-symbols-outlined" style="font-size:16px;color:var(--red)">delete_forever</span> Hapus Akun Mandiri (Self-Deletion) <span class="step-tag tag-security">DANGER</span></div>
                <div class="step-desc">Penghapusan akun diri sendiri memerlukan konfirmasi password. Setelah dihapus, sesi dihancurkan dan user diarahkan ke homepage.</div>
              </div>
              <div class="expand-icon"><span class="material-symbols-outlined" style="font-size:16px">expand_more</span></div>
            </div>
            <div class="step-detail">
              <div class="detail-algo">
                <div class="detail-algo-title"><span class="material-symbols-outlined" style="font-size:12px">functions</span> Self-Deletion Algorithm</div>
                <div class="algo-steps">
                  <div class="algo-step"><div class="algo-step-num">a</div><div class="algo-step-text">JS: Tampilkan <code>customConfirm("Are you sure...")</code> dulu (dari custom_alert.js)</div></div>
                  <div class="algo-step"><div class="algo-step-num">b</div><div class="algo-step-text">Jika dikonfirmasi → Kirim <code>POST settings_delete_account.php</code> dengan password</div></div>
                  <div class="algo-step"><div class="algo-step-num">c</div><div class="algo-step-text">PHP: Verifikasi password lagi (<code>password_verify</code>), jika salah → Error</div></div>
                  <div class="algo-step"><div class="algo-step-num">d</div><div class="algo-step-text">PHP: <code>DELETE FROM users WHERE user_id = ?</code></div></div>
                  <div class="algo-step"><div class="algo-step-num">e</div><div class="algo-step-text">PHP: <code>session_destroy()</code> → Return <code>{success:true}</code> → JS redirect ke homepage</div></div>
                </div>
              </div>
              <div class="file-links">
                <a class="file-link js" href="src/User/JS/setting.js" target="_blank">
                  <span class="material-symbols-outlined">javascript</span> setting.js <span style="opacity:0.5">· baris 81-97</span>
                </a>
                <a class="file-link php" href="src/User/PHP/settings_delete_account.php" target="_blank">
                  <span class="material-symbols-outlined">code</span> settings_delete_account.php
                </a>
                <a class="file-link js" href="src/Core/JS/custom_alert.js" target="_blank">
                  <span class="material-symbols-outlined">javascript</span> custom_alert.js — <code>customConfirm()</code>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div><!-- end flow-settings -->

</div><!-- end wrapper -->

<script>
  function switchFlow(name) {
    document.querySelectorAll('.flow-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.flow-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('flow-' + name).classList.add('active');
    document.getElementById('tab-' + name).classList.add('active');
  }

  function toggleStep(card) {
    const isExpanded = card.classList.contains('expanded');
    // Collapse all others in the same flow section
    const section = card.closest('.flow-section');
    section.querySelectorAll('.step-card.expanded').forEach(c => {
      if (c !== card) c.classList.remove('expanded');
    });
    card.classList.toggle('expanded', !isExpanded);
  }
</script>
</body>
</html>
