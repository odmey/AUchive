<?php
session_start();
require_once 'PHP/database.php';
$pdo = getDB();

$chapter_id = isset($_GET['chapter_id']) ? (int)$_GET['chapter_id'] : 0;
$roomchat_id = isset($_GET['roomchat_id']) ? (int)$_GET['roomchat_id'] : 0;
$story_id = isset($_GET['story_id']) ? (int)$_GET['story_id'] : (isset($_SESSION['story_id']) ? (int)$_SESSION['story_id'] : 0);

$roomchat = null;
$bubbles = [];

if ($roomchat_id > 0) {
    // Fetch roomchat info
    $stmt = $pdo->prepare("SELECT * FROM roomchats WHERE roomchat_id = ?");
    $stmt->execute([$roomchat_id]);
    $roomchat = $stmt->fetch();
    
    // Fetch bubbles
    $stmt2 = $pdo->prepare("SELECT * FROM bubbles WHERE roomchat_id = ? ORDER BY sort_order ASC");
    $stmt2->execute([$roomchat_id]);
    $bubbles = $stmt2->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customize bubblechat</title>
     <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link href="https://fonts.googleapis.com/css2?family=Bitter:ital,wght@0,100..900;1,100..900&family=Lora:ital,wght@0,400..700;1,400..700&family=Poppins&display=swap" rel="stylesheet">
     <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
     <link rel="stylesheet" href="CSS/style_bblchat.css">
    </head>
    <body class="theme-wa">
      <div class="topnav">
        <div class="nav-left">
          <a href="Editor.php?story_id=<?= $story_id ?>&chapter_id=<?= $chapter_id ?>" class="back-link">
            <span class="material-symbols-outlined">arrow_back</span>
          </a>
        </div>
        <div class="nav-right">
          <div class="nav-logo"><img src="Pic/TextLogo.png" alt="logo"></div>
          <!-- <span class="nav-title">im</span> -->
        </div>
      </div>
      
      <!-- preview -->
      <div class="layout">
        <div class="preview-panel">
          <div class="chat-header" id="chatHeader">
            <span class="header-back">‹</span>
            <div class="header-avatar" id="previewAvatar"><img src="Pic/PP kosongan.jpg" alt=""></div>
            <div class="header-info">
              <div class="header-name" id="previewName">Contact Name</div>
              <div class="header-status" id="previewStatus">online</div>
            </div>
            <div class="header-icons" id="headerIcons">
              <span class="material-symbols-outlined">video_call</span><span class="material-symbols-outlined">call</span>
            </div>
          </div>
          <div class="chat-area" id="chatArea">
            <div class="date-chip"><span>Today</span></div>
          </div>
          <div class="chat-inputbar" id="chatInputbar">
            <span class="material-symbols-outlined">mood</span>
            <span class="material-symbols-outlined">attach_file </span>
            <input class="ibar-field" id="ibarField" type="text" placeholder="Type a message" disabled>
            <button class="ibar-send" id="ibarSend">
              <svg viewBox="0 0 24 24"><path d="M12 1a4 4 0 014 4v6a4 4 0 01-8 0V5a4 4 0 014-4zm-1 17.93V21h2v-2.07A8 8 0 0020 11h-2a6 6 0 01-12 0H4a8 8 0 007 7.93z"/></svg>
            </button>
          </div>
        </div>
        
        <!-- editor -->
        <div class="editor-panel">
          <div class="editor-header"><h2>Bubble Chat Editor</h2></div>
          
          <div class="editor-body">
            <!-- THEME PICKER -->
            <div class="field-group">
              <label class="field-label">Chat Theme</label>
              <div class="theme-picker">
                <div class="theme-card tc-wa active" onclick="setTheme('wa',this)">
                  <div class="tcp"><div class="tcp-bub in"></div><div class="tcp-bub out"></div></div>
                  <div class="tc-label">Dark</div>
                </div>
                <div class="theme-card tc-im" onclick="setTheme('im',this)">
                  <div class="tcp"><div class="tcp-bub in"></div><div class="tcp-bub out"></div></div>
                  <div class="tc-label">Light</div>
                </div>
              </div>
            </div>
            
            <div class="sep"></div>
            
      <div class="field-group">
        <label class="field-label">Contact Name</label>
        <input type="text" id="contactName" placeholder="Enter contact name…"
        oninput="document.getElementById('previewName').textContent=this.value||'Contact Name'">
      </div>
      
      <div class="field-group">
        <label class="field-label">Profile Photos</label>
        <div class="two-col">
          <div class="field-group">
            <label class="field-label">My Profile</label>
            <input type="file" id="profileUploadme" accept="image/*" onchange="loadAvatar(this,'me')">
          </div>
          <div class="field-group">
            <label class="field-label">Contact Profile</label>
            <input type="file" id="profileUpload" accept="image/*" onchange="loadAvatar(this,'contact')">
          </div>
        </div>
      </div>
      
      <div class="field-group">
        <label class="field-label">Attachments (optional)</label>
        <div class="two-col">
          <div class="field-group">
            <label class="field-label">Picture in Bubble</label>
            <input type="file" id="imageUpload" accept="image/*">
          </div>
          <div class="field-group">
            <label class="field-label">Chat Background</label>
            <input type="file" id="bgupload" accept="image/*" onchange="loadBg(this)">
          </div>
        </div>
      </div>
      
      <div class="sep"></div>
      
      <div class="field-group">
        <label class="field-label">Message Content</label>
        <textarea id="message" placeholder="Type the message here…"></textarea>
      </div>
      
      <div class="two-col">
        <div class="field-group">
          <label class="field-label">Message Time</label>
          <input type="time" id="time" value="10:30">
        </div>
        <div class="field-group">
          <label class="field-label">Bubble Color</label>
          <input type="color" id="bubbleColor" value="#005c4b">
        </div>
      </div>
      
      <div class="field-group">
        <label class="field-label">Position</label>
        <div class="radio-group">
          <label class="radio-option"><input type="radio" name="side" value="left" checked> ← Left</label>
          <label class="radio-option"><input type="radio" name="side" value="right"> Right →</label>
        </div>
      </div>
      
      <div class="sep"></div>
      <div class="btn-row">
        <button class="btn-outline" onclick="addBubble()">+ Add Bubble</button>
        <button class="btn-primary" onclick="clearChat()">🗑 Clear</button>
      </div>
      <button class="btn-save" onclick="saveStory()">SAVE STORY</button>
    </div>
  </div>
</div>

<script>
    const STORY_ID = <?= $story_id ?>;
    const INITIAL_ROOMCHAT = <?= json_encode($roomchat) ?>;
    const INITIAL_BUBBLES = <?= json_encode($bubbles) ?>;
</script>
<script src="JS/bubblechat.js"></script>
</body>
</html>