<?php
/* Current server-side status codes:
   1 = Draft, 2 = Published, 3 = Live, 4 = Closed */
require_once "include/session_check.php";
require "include/coreDataconnect.php";

if (!isset($_SESSION['team_id'])) {
    header("Location: login.php");
    exit;
}

$event_id = isset($_POST['event_id'])
    ? (int)$_POST['event_id']
    : (isset($_SESSION['event_id']) ? (int)$_SESSION['event_id'] : 0);
$_SESSION['event_id'] = $event_id;
if (!$event_id) die("Invalid event");

/* FETCH EVENT */
$stmt = $conn->prepare("
    SELECT * FROM tb_events 
    WHERE event_id = ? AND event_team_pkid = ?
");
$stmt->bind_param("ii", $event_id, $_SESSION['team_id']);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

/* FETCH MODULES */
$res = $conn->query("
    SELECT mod_order, mod_type, mod_game_id
    FROM tb_events_module
    WHERE mod_event_pkid = $event_id AND mod_status = 1
    ORDER BY mod_order ASC
");

/* MODULE NAME */
function getModuleName($conn, $type, $id) {
    if ($type == 2) {
        $q = $conn->query("SELECT cg_name FROM card_group WHERE cg_id = $id");
        if ($q && $r = $q->fetch_assoc()) return $r['cg_name'];
    }
    if ($type == 4) {
        $q = $conn->query("SELECT p_name FROM prioritization WHERE p_id = $id");
        if ($q && $r = $q->fetch_assoc()) return $r['p_name'];
    }
    if ($type == 5) {
        $q = $conn->query("SELECT di_name FROM mg5_digisim WHERE di_id = $id");
        if ($q && $r = $q->fetch_assoc()) return $r['di_name'];
    }
    return "Module";
}

/* STATUS LOGIC */
$ps         = (int)($event['event_playstatus'] ?? 1);
$isPublished = $ps >= 2;
$isLive      = $ps == 3;
$isClosed    = $ps !== 3; // Default to closed if not live
$hasUrl      = !empty($event['event_url_code']);

/* Validity expiry check */
$validityDays = intval($event['event_validity']);
$startDate    = new DateTime($event['event_start_date']);
$endDate      = clone $startDate;
$endDate->modify("+{$validityDays} days");
$isExpired    = (new DateTime()) > $endDate;

/* AUTO-CLOSE: if expired and not already closed, force status=4 */
if ($isExpired && $ps !== 4 && $ps >= 2) {
    $autoClose = $conn->prepare("UPDATE tb_events SET event_playstatus = 4 WHERE event_id = ? AND event_team_pkid = ?");
    $autoClose->bind_param("ii", $event_id, $_SESSION['team_id']);
    $autoClose->execute();
    $ps       = 4;
    $isLive   = false;
    $isClosed = true;
}

/* Toggles are locked permanently once expired */
$togglesDisabled = $isExpired;

$pageTitle = "Final Review";
$pageCSS   = "/css_event/review_modules.css";

require "layout/tb_header.php";

$step = 3;
?>
<style>
  /* ── INPUT GROUP (copy URL) ── */
  :root {
    --primary-color: #4285f4;
    --success-color: #34a853;
    --bg-color: #f8f9fa;
    --border-color: #dadce0;
  }
  .input-group {
    display: flex;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    overflow: hidden;
    transition: border-color 0.2s ease;
  }
  .input-group:focus-within { border-color: var(--primary-color); }
  #urlInput {
    flex: 1; border: none; padding: 8px 5px;
    font-size: 14px; color: #3c4043; outline: none; background: transparent;
  }
  #copyBtn {
    background-color: var(--primary-color); color: white; border: none;
    padding: 0 20px; font-weight: 600; cursor: pointer;
    transition: all 0.2s ease; min-width: 75px;
  }
  #copyBtn:hover  { background-color: #1a73e8; }
  #copyBtn:active { transform: scale(0.96); }
  #copyBtn.copied { background-color: var(--success-color); }

  /* ── NEW LAYOUT ── */
  .review-outer {
    max-width: 1280px;
    margin: 0 auto;
    padding: 32px 20px;
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
    align-items: start;
  }

  /* Left column */
  .review-left {}

  /* ── Top card: poster + overview side by side ── */
  .top-card {
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 8px 24px rgba(0,0,0,.06);
    padding: 24px;
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 24px;
    align-items: start;
    margin-bottom: 20px;
  }
  .top-card .poster-col img {
    width: 100%;
    border-radius: 12px;
    display: block;
    object-fit: cover;
  }
  .top-card .overview-col h3 {
    font-size: 17px;
    font-weight: 700;
    margin: 0 0 16px;
    color: #111827;
  }
  .overview-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px 20px;
  }
  .overview-grid label {
    font-size: 11px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .5px;
    display: block;
    margin-bottom: 3px;
  }
  .overview-grid p {
    font-size: 14px;
    font-weight: 600;
    margin: 0;
    color: #1f2937;
  }
  .overview-grid .full { grid-column: span 2; }

  /* ── Module Sequence card ── */
  .module-card {
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 8px 24px rgba(0,0,0,.06);
    padding: 24px;
  }
  .module-card h3 {
    font-size: 17px;
    font-weight: 700;
    margin: 0 0 16px;
    color: #111827;
  }
  .module-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 14px;
    background: #f8faff;
    border-radius: 12px;
    margin-bottom: 10px;
    transition: .2s;
  }
  .module-item:hover { transform: translateY(-2px); }
  .index {
    background: #e0e7ff; color: #1e3a8a;
    padding: 5px 10px; border-radius: 8px;
    font-size: 13px; font-weight: 700;
  }
  .mi-icon { color: #3b82f6; font-size: 20px; }
  .module-info strong { font-size: 14px; font-weight: 500; color: #1f2937; }

  /* ── Header row ── */
  .review-header-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 22px;
    gap: 16px;
  }
  .review-header-row h1 {
    font-size: 28px;
    font-weight: 700;
    margin: 0 0 4px;
  }
  .review-header-row p {
    color: #6b7280;
    font-size: 14px;
    margin: 0;
  }
  .btn-save-event {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 22px;
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background .2s, transform .15s;
    white-space: nowrap;
    flex-shrink: 0;
  }
  .btn-save-event:hover  { background: #1d4ed8; transform: translateY(-1px); }
  .btn-save-event:active { transform: scale(.97); }

  .save-feedback {
    display: none;
    align-items: center;
    gap: 6px;
    margin-top: 8px;
    font-size: 13px;
    font-weight: 500;
    color: #16a34a;
    animation: fadeSlideIn .3s ease;
  }
  .save-feedback.show { display: flex; }
  @keyframes fadeSlideIn {
    from { opacity: 0; transform: translateY(4px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  /* ── RIGHT SIDEBAR ── */
  .review-right {}

  /* Back to Edit & sidebar actions */
  .sidebar-actions {
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 8px 24px rgba(0,0,0,.06);
    padding: 20px;
    margin-bottom: 20px;
  }
  .btn-back {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 11px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    background: #f3f4f6;
    color: #374151;
    border: none;
    cursor: pointer;
    width: 100%;
    transition: background .2s;
  }
  .btn-back:hover { background: #e5e7eb; }

  .btn-manage {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 11px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #6ee7b7;
    cursor: pointer;
    width: 100%;
    transition: background .2s, border-color .2s;
  }
  .btn-manage:hover { background: #d1fae5; border-color: #34d399; }

  /* ── WORKFLOW SIDEBAR CARD ── */
  .workflow-card {
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 8px 24px rgba(0,0,0,.06);
    padding: 22px;
  }
  .workflow-title {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    color: #6b7280;
    margin: 0 0 18px;
    display: flex;
    align-items: center;
    gap: 7px;
  }
  .workflow-title .material-symbols-outlined {
    font-size: 16px;
    color: #9ca3af;
  }

  /* Publish button */
  .btn-publish {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: transform .15s, box-shadow .15s;
    box-shadow: 0 4px 14px rgba(37,99,235,.3);
  }
  .btn-publish:hover  { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(37,99,235,.4); }
  .btn-publish:active { transform: scale(.97); }
  .btn-publish:disabled {
    background: #c7d2fe; box-shadow: none; cursor: not-allowed;
    color: #818cf8;
  }
  .publish-hint {
    font-size: 12px;
    color: #6b7280;
    margin: 8px 0 0;
    line-height: 1.5;
  }
  .publish-url-row {
    display: none;
    align-items: center;
    gap: 8px;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 10px;
    padding: 9px 12px;
    margin-top: 10px;
  }
  .publish-url-row.show { display: flex; }
  .publish-url-row .pub-url {
    font-size: 12px;
    color: #166534;
    font-weight: 500;
    word-break: break-all;
    flex: 1;
  }
  .pub-copy-btn {
    background: none; border: none; cursor: pointer;
    color: #16a34a; padding: 2px; display: flex;
  }

  /* Divider */
  .wf-divider {
    border: none;
    border-top: 1px solid #f3f4f6;
    margin: 18px 0;
  }

  /* Toggle rows */
  .toggle-row {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 6px;
  }
  .toggle-info { flex: 1; }
  .toggle-label {
    font-size: 14px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 3px;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .toggle-badge {
    font-size: 10px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 20px;
    letter-spacing: .5px;
  }
  .badge-live   { background: #dcfce7; color: #15803d; }
  .badge-closed { background: #fef2f2; color: #b91c1c; }
  .toggle-hint {
    font-size: 12px;
    color: #6b7280;
    line-height: 1.5;
    margin: 4px 0 0;
  }

  /* Toggle switch */
  .toggle-switch {
    position: relative;
    width: 44px;
    height: 24px;
    flex-shrink: 0;
    margin-top: 2px;
  }
  .toggle-switch input { opacity: 0; width: 0; height: 0; }
  .toggle-switch .slider {
    position: absolute;
    inset: 0;
    background: #d1d5db;
    border-radius: 24px;
    cursor: pointer;
    transition: .25s;
  }
  .toggle-switch .slider::before {
    content: '';
    position: absolute;
    width: 18px; height: 18px;
    left: 3px; bottom: 3px;
    background: #fff;
    border-radius: 50%;
    transition: .25s;
    box-shadow: 0 1px 3px rgba(0,0,0,.2);
  }
  .toggle-switch input:checked + .slider             { background: #2563eb; }
  .toggle-switch input:checked + .slider::before     { transform: translateX(20px); }
  .toggle-switch input:disabled + .slider            { opacity: .45; cursor: not-allowed; }

  /* Live toggle: green when active */
  .live-toggle input:checked + .slider               { background: #16a34a; }
  /* Closed toggle: red when active */
  .closed-toggle input:checked + .slider             { background: #dc2626; }

  .wf-status-msg {
    font-size: 12px;
    font-weight: 500;
    padding: 8px 12px;
    border-radius: 8px;
    margin-top: 10px;
    display: none;
  }
  .wf-status-msg.show { display: block; }
  .wf-status-msg.success { background: #f0fdf4; color: #15803d; }
  .wf-status-msg.error   { background: #fef2f2; color: #b91c1c; }

  /* Published checkmark badge */
  .published-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    font-weight: 600;
    color: #0369a1;
    background: #e0f2fe;
    padding: 4px 10px;
    border-radius: 20px;
    margin-top: 10px;
  }
  .published-badge .material-symbols-outlined {
    font-size: 14px;
  }

  /* Expired notice banner */
  .wf-expired-notice {
    display: flex;
    align-items: center;
    gap: 7px;
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
    border-radius: 10px;
    padding: 9px 13px;
    font-size: 12px;
    font-weight: 500;
    line-height: 1.5;
    margin-bottom: 14px;
  }

</style>

<div class="stepper-div">
    <?php include "components/wizard_steps.php"; ?>
</div>

<div class="review-outer">

  <!-- ============ LEFT COLUMN ============ -->
  <div class="review-left">

    <!-- Header row with Save button -->
    <div class="review-header-row">
      <div>
        <h1>Final Review</h1>
        <p>Please verify all details before launching your event.</p>
        <div class="save-feedback" id="saveFeedback">
          <span class="material-symbols-outlined" style="font-size:16px;color:#16a34a">check_circle</span>
          Updates to the event are saved.
        </div>
      </div>
      <button class="btn-save-event" onclick="saveEvent()">
        <span class="material-symbols-outlined" style="font-size:18px">save</span>
        Save
      </button>
    </div>

    <!-- TOP CARD: Poster LEFT + Overview RIGHT -->
    <div class="top-card">
      <div class="poster-col">
        <img src="upload-images/events/<?= $event['event_coverimage'] ?? 'default_event.jpeg' ?>" alt="Event Poster">
      </div>
      <div class="overview-col">
        <h3>Event Overview</h3>
        <div class="overview-grid">
          <div>
            <label>Event Name</label>
            <p><?= htmlspecialchars($event['event_name']) ?></p>
          </div>
          <div>
            <label>Passcode</label>
            <p><?= $event['event_passcode'] ?: '-' ?></p>
          </div>
          <div>
            <label>Start Date</label>
            <p><?= date('d M Y', strtotime($event['event_start_date'])) ?></p>
          </div>
          <div>
            <label>Validity</label>
            <p><?= $event['event_validity'] ?> days</p>
          </div>
          <div class="full">
            <label>Description</label>
            <p><?= $event['event_description'] ?: '-' ?></p>
          </div>
        </div>
      </div>
    </div>

    <!-- MODULE SEQUENCE -->
    <div class="module-card">
      <h3>Module Sequence</h3>
      <div class="module-list">
        <?php
        $i = 1;
        while ($row = $res->fetch_assoc()):
          $name = getModuleName($conn, $row['mod_type'], $row['mod_game_id']);
          $icon = "widgets";
          if ($row['mod_type'] == 2) $icon = "style";
          if ($row['mod_type'] == 4) $icon = "view_list";
          if ($row['mod_type'] == 5) $icon = "memory";
        ?>
        <div class="module-item">
          <span class="index"><?= $i++ ?></span>
          <span class="material-symbols-outlined mi-icon"><?= $icon ?></span>
          <div class="module-info">
            <strong><?= htmlspecialchars($name) ?></strong>
          </div>
        </div>
        <?php endwhile; ?>
      </div>
    </div>

  </div><!-- /review-left -->

  <!-- ============ RIGHT SIDEBAR ============ -->
  <div class="review-right">

    <!-- Sidebar quick nav -->
    <div class="sidebar-actions">
      <form method="POST" action="add_modules.php" style="margin-bottom:10px">
        <input type="hidden" name="event_id" value="<?= $event_id ?>">
        <button class="btn-back" type="submit">
          <span class="material-symbols-outlined" style="font-size:18px">arrow_back</span>
          Back to Edit
        </button>
      </form>
      <form method="POST" action="view_event.php">
        <input type="hidden" name="event_id" value="<?= $event_id ?>">
        <button class="btn-manage" type="submit">
          <span class="material-symbols-outlined" style="font-size:18px">dashboard</span>
          Manage Event
        </button>
      </form>
    </div>

    <!-- WORKFLOW CARD -->
    <div class="workflow-card">
      <div class="workflow-title">
        <span class="material-symbols-outlined">manage_accounts</span>
        EVENT USER ACCESS SETTING
      </div>

      <!-- PUBLISH & GET URL -->
      <button class="btn-publish" id="btnPublish" onclick="publishEvent()"
        <?= ($isPublished) ? 'disabled' : '' ?>>
        <span class="material-symbols-outlined" style="font-size:18px">rocket_launch</span>
        <?= $isPublished ? 'Published ✓' : 'Publish &amp; Get URL' ?>
      </button>

      <?php if ($isPublished): ?>
      <div class="published-badge">
        <span class="material-symbols-outlined">link</span>
        Event URL Generated
      </div>
      <?php endif; ?>

      <p class="publish-hint">Publish to generate a URL for users. Participants can access but cannot start until the event is Live.</p>

      <!-- URL display row -->
      <div class="publish-url-row <?= $hasUrl ? 'show' : '' ?>" id="publishUrlRow">
        <span class="pub-url" id="pubUrlText">
          <?= $hasUrl ? htmlspecialchars('http://localhost/trainerbyte/' . $event['event_url_code']) : '' ?>
        </span>
        <button class="pub-copy-btn" onclick="copyPubUrl()" title="Copy URL">
          <span class="material-symbols-outlined" style="font-size:18px">content_copy</span>
        </button>
      </div>

      <hr class="wf-divider">

      <?php if ($isExpired): ?>
      <!-- EXPIRY NOTICE -->
      <div class="wf-expired-notice">
        <span class="material-symbols-outlined" style="font-size:16px;vertical-align:-3px">timer_off</span>
        <strong>Validity Expired.</strong> This event has been auto-closed and can no longer be changed.
      </div>
      <?php endif; ?>

      <!-- LIVE TOGGLE -->
      <div class="toggle-row">
        <div class="toggle-info">
          <div class="toggle-label">
            LIVE
            <?php if ($isLive): ?>
            <span class="toggle-badge badge-live">ACTIVE</span>
            <?php endif; ?>
          </div>
          <div class="toggle-hint">You can change the status to Live when you're ready for participants to start. In LIVE, no module edits are allowed.</div>
        </div>
        <label class="toggle-switch live-toggle">
          <input type="checkbox" id="toggleLive"
            <?= $isLive ? 'checked' : '' ?>
            <?= ($togglesDisabled || !$isPublished) ? 'disabled' : '' ?>
            onchange="setLive(this.checked)">
          <span class="slider"></span>
        </label>
      </div>

      <hr class="wf-divider">

      <!-- CLOSED TOGGLE -->
      <div class="toggle-row">
        <div class="toggle-info">
          <div class="toggle-label">
            CLOSED
            <?php if ($isClosed): ?>
            <span class="toggle-badge badge-closed">CLOSED</span>
            <?php endif; ?>
          </div>
          <div class="toggle-hint">Set this status when the event access is no more required. This can be set to off only if validity date is more than current date<?= $isExpired ? ' — <strong>Validity expired</strong>' : '' ?>.</div>
        </div>
        <label class="toggle-switch closed-toggle">
          <input type="checkbox" id="toggleClosed"
            <?= $isClosed ? 'checked' : '' ?>
            <?= ($togglesDisabled || !$isPublished) ? 'disabled' : '' ?>
            onchange="setClosed(this.checked)">
          <span class="slider"></span>
        </label>
      </div>

      <!-- Status message area -->
      <div class="wf-status-msg" id="wfMsg"></div>

    </div><!-- /workflow-card -->

  </div><!-- /review-right -->

</div><!-- /review-outer -->

<!-- ── SUCCESS MODAL (after publish) ── -->
<div id="successModal" class="modal-overlay">
  <div class="modal-box">
    <div class="modal-icon">
      <span class="material-symbols-outlined big-icon">check_circle</span>
    </div>
    <h2>Event Created Successfully</h2>
    <p>Your event is now ready to use.</p>
    <div class="input-group">
      <input type="text" value="http://localhost/trainerbyte/<?= htmlspecialchars($event['event_url_code']); ?>" id="urlInput" readonly>
      <button onclick="copyUrl()" id="copyBtn">Copy</button>
    </div>
    <form method="POST" action="view_event.php">
      <input type="hidden" name="event_id" value="<?= $event_id ?>">
      <button class="launch-btn" style="margin-top:16px;width:100%">OK</button>
    </form>
  </div>
</div>

<script>
const EVENT_ID = <?= $event_id ?>;

/* ─── Show success modal on ?success=1 ─── */
const params = new URLSearchParams(window.location.search);
if (params.get("success") == "1") {
  document.getElementById("successModal").classList.add("show");
}

/* ─── Copy URL (modal) ─── */
function copyUrl() {
  const copyText = document.getElementById("urlInput");
  const button   = document.getElementById("copyBtn");
  navigator.clipboard.writeText(copyText.value).then(() => {
    button.innerText = "Copied!";
    button.classList.add("copied");
    setTimeout(() => { button.innerText = "Copy"; button.classList.remove("copied"); }, 2000);
  });
}

/* ─── Copy published URL (sidebar) ─── */
function copyPubUrl() {
  const url = document.getElementById("pubUrlText").innerText.trim();
  navigator.clipboard.writeText(url).then(() => showWfMsg("URL copied to clipboard!", "success"));
}

/* ─── SAVE ─── */
function saveEvent() {
  fetch("ajax_event_status.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `action=save&event_id=${EVENT_ID}`
  })
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      const fb = document.getElementById("saveFeedback");
      fb.classList.add("show");
      setTimeout(() => fb.classList.remove("show"), 3000);
    }
  });
}

/* ─── PUBLISH ─── */
function publishEvent() {
  fetch("ajax_event_status.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `action=publish&event_id=${EVENT_ID}`
  })
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      const btn = document.getElementById("btnPublish");
      btn.disabled = true;
      btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px">rocket_launch</span> Published ✓';

      if (d.url_code) {
        const fullUrl = `http://localhost/trainerbyte/${d.url_code}`;
        document.getElementById("pubUrlText").innerText = fullUrl;
        document.getElementById("publishUrlRow").classList.add("show");
        /* also enable LIVE toggle */
        document.getElementById("toggleLive").disabled = false;
        document.getElementById("toggleClosed").disabled = false;
      }
      showWfMsg("Event published. URL is ready.", "success");
    } else {
      showWfMsg(d.msg, "error");
    }
  });
}

/* ─── LIVE TOGGLE ─── */
function setLive(on) {
  const liveToggle   = document.getElementById("toggleLive");
  const closedToggle = document.getElementById("toggleClosed");
  liveToggle.disabled   = true;
  closedToggle.disabled = true;

  fetch("ajax_event_status.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `action=set_live&event_id=${EVENT_ID}&value=${on ? 1 : 0}`
  })
  .then(r => r.json())
  .then(d => {
    liveToggle.disabled   = false;
    closedToggle.disabled = false;
    if (d.success) {
      /* LIVE ON  → status 3: live=true,  closed=false */
      /* LIVE OFF → status 4: live=false, closed=true  */
      liveToggle.checked   = (d.status === 3);
      closedToggle.checked = (d.status !== 3);
      showWfMsg(d.msg, "success");
    } else {
      liveToggle.checked = !on; // revert
      showWfMsg(d.msg, "error");
    }
  })
  .catch(() => {
    liveToggle.disabled   = false;
    closedToggle.disabled = false;
    liveToggle.checked    = !on;
    showWfMsg("Network error. Please try again.", "error");
  });
}

/* ─── CLOSED TOGGLE ─── */
function setClosed(on) {
  const liveToggle   = document.getElementById("toggleLive");
  const closedToggle = document.getElementById("toggleClosed");
  liveToggle.disabled   = true;
  closedToggle.disabled = true;

  fetch("ajax_event_status.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `action=set_closed&event_id=${EVENT_ID}&value=${on ? 1 : 0}`
  })
  .then(r => r.json())
  .then(d => {
    liveToggle.disabled   = false;
    closedToggle.disabled = <?= $isExpired ? '(d.status === 4)' : 'false' ?>;
    if (d.success) {
      /* CLOSED ON  → status 4: closed=true,  live=false */
      /* CLOSED OFF → status 3: closed=false, live=true  */
      closedToggle.checked = (d.status !== 3);
      liveToggle.checked   = (d.status === 3);
      showWfMsg(d.msg, "success");
    } else {
      closedToggle.checked = !on; // revert
      liveToggle.disabled  = false;
      showWfMsg(d.msg, "error");
    }
  })
  .catch(() => {
    liveToggle.disabled   = false;
    closedToggle.disabled = false;
    closedToggle.checked  = !on;
    showWfMsg("Network error. Please try again.", "error");
  });
}

/* ─── Helper: show status message ─── */
function showWfMsg(msg, type) {
  const el = document.getElementById("wfMsg");
  el.className = `wf-status-msg ${type} show`;
  el.textContent = msg;
  setTimeout(() => el.classList.remove("show"), 4000);
}
</script>

<?php //require "layout/footer.php"; ?>