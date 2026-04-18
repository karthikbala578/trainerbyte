<?php
require_once "include/session_check.php";
require "include/coreDataconnect.php";

if (!isset($_SESSION['team_id'])) {
    header("Location: login.php");
    exit;
}

$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
$_SESSION['event_id'] = $event_id;
if ($event_id <= 0) die("Invalid Event");

$stmt = $conn->prepare("
    SELECT * FROM tb_events
    WHERE event_id = ? AND event_team_pkid = ?
");
$stmt->bind_param("ii", $event_id, $_SESSION['team_id']);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

if (!$event) die("Event not found");

$statusMap   = [1 => 'Draft', 2 => 'Published', 3 => 'In Progress', 4 => 'Closed'];
$statusClass = [1 => 'not-started', 2 => 'open', 3 => 'in-progress', 4 => 'completed'];
$ps          = (int)$event['event_playstatus'];

/* Workflow state */
$isPublished = $ps >= 2;
$isLive      = $ps === 3;
$isClosed    = $ps !== 3; // Default to visually closed if not Live
$hasUrl      = !empty($event['event_url_code']);

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

/* Recompute label/class after possible auto-close */
$statusLabel = $statusMap[$ps] ?? 'Unknown';
$statusCls   = $statusClass[$ps] ?? '';

/* Toggles permanently disabled once expired */
/* Toggles permanently disabled once expired */
$togglesDisabled = $isExpired;

/* Moderator Settings configuration */
$eventProgression = (int)($event['event_progression']);
if ($eventProgression <= 0) $eventProgression = 1;

$eventRelease = (int)($event['event_release']);
if ($eventRelease <= 0) $eventRelease = 1;

/* Fetch modules for Manual unlocking */
$moduleSources = [
    2 => ['table' => 'card_group', 'id' => 'cg_id', 'name' => 'cg_name'],
    5 => ['table' => 'mg5_digisim', 'id' => 'di_id', 'name' => 'di_name'],
    6 => ['table' => 'mg6_riskhop_matrix', 'id' => 'id', 'name' => 'game_name'],
    9 => ['table' => 'mg5_digisim', 'id' => 'di_id', 'name' => 'di_name']
];
$unions = [];
foreach ($moduleSources as $type => $cfg) {
    $unions[] = "SELECT {$cfg['id']} AS module_id, $type AS module_type, {$cfg['name']} AS module_name FROM {$cfg['table']}";
}
$unionSql = implode(" UNION ALL ", $unions);

$stmtMod = $conn->prepare("
    SELECT em.mod_id, em.mod_is_unlocked, em.mod_order, u.module_name
    FROM tb_events_module em
    JOIN ($unionSql) u ON em.mod_game_id = u.module_id AND em.mod_type = u.module_type
    WHERE em.mod_event_pkid = ? AND em.mod_status = 1
    ORDER BY em.mod_order ASC
");
$stmtMod->bind_param("i", $event_id);
$stmtMod->execute();
$dbModules = $stmtMod->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = htmlspecialchars($event['event_name']);
$pageCSS   = "css_event/view_event.css";
require "layout/header.php";
require "layout/tb_header.php";
?>

<!-- Google Material Symbols -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">

<div class="ve-page">

    <!-- ══════ PAGE HEADER (SaaS style) ══════ -->
    <div class="ve-header">
        <div class="ve-header-left">
            <div class="ve-header-info">
                <div class="ve-header-title-row">
                    <h1><?= htmlspecialchars($event['event_name']) ?></h1>
                    <span class="ve-status-badge <?= $statusCls ?>"><?= $statusLabel ?></span>
                </div>
                <div class="ve-header-chips">
                    <span class="chip"><span class="material-symbols-rounded">calendar_today</span><?= date("d M Y", strtotime($event['event_start_date'])) ?></span>
                    <span class="chip"><strong>Validity: <?= $event['event_validity'] ?> days</strong></span>
                    <span class="chip"><span class="material-symbols-rounded">key</span><?= htmlspecialchars($event['event_passcode']) ?></span>
                </div>
            </div>
        </div>
        <div class="ve-header-actions">
            <div class="ve-event-link-wrap">
                <span class="ve-event-link-label">Event Link</span>
                <div class="ve-copy-pill" title="Share this event access link with participants">
                    <span class="ve-copy-url" data-code="<?= htmlspecialchars($event['event_url_code'] ?? '') ?>">Loading...</span>
                    <button class="ve-copy-btn-small" onclick="copyEventLink('<?= htmlspecialchars($event['event_url_code'] ?? '') ?>')" title="Copy Link">
                        <span class="material-symbols-rounded">content_copy</span>
                    </button>
                </div>
            </div>
            <form action="create_event.php" method="POST">
                <input type="hidden" name="event_id" value="<?= $event_id ?>">
                <button type="submit" class="ve-btn secondary"> <span class="material-symbols-rounded">edit</span> Edit Event </button>
            </form>
            <button class="ve-btn ghost ve-settings-btn" id="settingsBtn" title="Results &amp; Analysis Setting">
                <span class="material-symbols-rounded">settings</span>
            </button>
        </div>
    </div>

    <!-- ══════ ACCESS WORKFLOW STRIP ══════ -->
    <div class="ve-workflow-strip">
        <span class="vw-label">
            <span class="material-symbols-rounded" style="font-size:15px;vertical-align:-3px">manage_accounts</span>
            EVENT ACCESS
        </span>

        <!-- LIVE toggle -->
        <div class="vw-toggle-group <?= ($togglesDisabled || !$isPublished) ? 'vw-disabled' : '' ?>">
            <span class="vw-toggle-label">
                LIVE
                <?php if ($isLive): ?><span class="vw-badge live">ACTIVE</span><?php endif; ?>
            </span>
            <label class="vw-switch live-toggle">
                <input type="checkbox" id="vw-toggleLive"
                    <?= $isLive ? 'checked' : '' ?>
                    <?= ($togglesDisabled || !$isPublished) ? 'disabled' : '' ?>
                    onchange="vwSetLive(this.checked)">
                <span class="vw-slider"></span>
            </label>
            <span class="vw-hint">Ready for participants to start. In LIVE, no module edits.</span>
        </div>

        <div class="vw-sep"></div>

        <!-- CLOSED toggle -->
        <div class="vw-toggle-group <?= ($togglesDisabled || !$isPublished) ? 'vw-disabled' : '' ?>">
            <span class="vw-toggle-label">
                CLOSED
                <?php if ($isClosed): ?><span class="vw-badge closed">CLOSED</span><?php endif; ?>
            </span>
            <label class="vw-switch closed-toggle">
                <input type="checkbox" id="vw-toggleClosed"
                    <?= $isClosed ? 'checked' : '' ?>
                    <?= ($togglesDisabled || !$isPublished) ? 'disabled' : '' ?>
                    onchange="vwSetClosed(this.checked)">
                <span class="vw-slider"></span>
            </label>
            <span class="vw-hint">No access required. Off only if validity > today<?= $isExpired ? ' <strong style="color:#b91c1c">(Expired)</strong>' : '' ?>.</span>
        </div>

        <!-- inline status message -->
        <div class="vw-msg" id="vwMsg"></div>
    </div>

    <!-- ══════ TABS ══════ -->
    <div class="ve-tabs-bar">
        <button class="ve-tab active" data-tab="matrix" onclick="switchTab('matrix', this)">
            <span class="material-symbols-rounded">grid_on</span> Live Progress Matrix
        </button>
        <button class="ve-tab" data-tab="scores" onclick="switchTab('scores', this)">
            <span class="material-symbols-rounded">leaderboard</span> Scores by Round
        </button>
    </div>

    <!-- ══════ MATRIX SECTION ══════ -->
    <div class="ve-section" id="tab-matrix">
        <div class="ve-stat-row">
            <div class="ve-stat-card">
                <div class="stat-icon blue"><span class="material-symbols-rounded">group</span></div>
                <div class="stat-body"><div class="stat-value" id="s-total">—</div><div class="stat-label">Total Participants</div></div>
            </div>
            <div class="ve-stat-card">
                <div class="stat-icon green"><span class="material-symbols-rounded">percent</span></div>
                <div class="stat-body"><div class="stat-value" id="s-avg">—</div><div class="stat-label">Avg. Completion</div></div>
            </div>
            <div class="ve-stat-card">
                <div class="stat-icon purple"><span class="material-symbols-rounded">inventory_2</span></div>
                <div class="stat-body"><div class="stat-value" id="s-mods">—</div><div class="stat-label">Modules Live</div></div>
            </div>
            <div class="ve-stat-card">
                <div class="stat-icon gold"><span class="material-symbols-rounded">star</span></div>
                <div class="stat-body"><div class="stat-value" id="s-top">—</div><div class="stat-label">Top Performer</div></div>
            </div>
            <div class="ve-search-box" style="flex-shrink:0;margin-left:auto;">
                <span class="material-symbols-rounded">search</span>
                <input type="text" placeholder="Search participant..." onkeyup="doSearch('matrix', this.value)">
            </div>
        </div>

        <div class="ve-table-wrap">
            <table class="ve-table" id="matrix-table">
                <thead><tr id="matrix-thead-row"></tr></thead>
                <tbody id="matrix-tbody"></tbody>
            </table>
            <div class="ve-empty hidden" id="matrix-empty">No participants have joined this event yet.</div>
        </div>
        <div class="ve-pagination" id="matrix-pager"></div>
    </div>

    <!-- ══════ SCORES SECTION ══════ -->
    <div class="ve-section hidden" id="tab-scores">
        <div class="ve-stat-row">
            <div class="ve-stat-card">
                <div class="stat-icon blue"><span class="material-symbols-rounded">casino</span></div>
                <div class="stat-body"><div class="stat-value" id="sc-total">—</div><div class="stat-label">Total Score</div></div>
            </div>
            <div class="ve-stat-card">
                <div class="stat-icon green"><span class="material-symbols-rounded">group</span></div>
                <div class="stat-body"><div class="stat-value" id="sc-avg">—</div><div class="stat-label">Class Average</div></div>
            </div>
            <div class="ve-stat-card">
                <div class="stat-icon gold"><span class="material-symbols-rounded">emoji_events</span></div>
                <div class="stat-body"><div class="stat-value" id="sc-high">—</div><div class="stat-label">Highest Score</div></div>
            </div>
            <div class="ve-stat-card">
                <div class="stat-icon red"><span class="material-symbols-rounded">warning</span></div>
                <div class="stat-body"><div class="stat-value" id="sc-low">—</div><div class="stat-label">Lowest Score</div></div>
            </div>
            <div class="ve-legend-status">
                <span><span class="score-chip high">98</span> High ≥80</span>
                <span><span class="score-chip mid">65</span> Mid 50–79</span>
                <span><span class="score-chip low">42</span> Low &lt;50</span>
            </div>
            <div class="ve-search-box" style="flex-shrink:0;margin-left:auto;">
                <span class="material-symbols-rounded">search</span>
                <input type="text" placeholder="Search participant..." onkeyup="doSearch('scores', this.value)">
            </div>
        </div>

        <div class="ve-table-wrap">
            <table class="ve-table" id="scores-table">
                <thead><tr id="scores-thead-row"></tr></thead>
                <tbody id="scores-tbody"></tbody>
            </table>
            <div class="ve-empty hidden" id="scores-empty">No score data available yet.</div>
        </div>
        <div class="ve-pagination" id="scores-pager"></div>
    </div>

</div><!-- /ve-page -->

<!-- ══════ CLOSED POPUP (shown when participant tries to START a closed event) ══════ -->
<div class="ve-closed-popup" id="veClosedPopup" style="display:none" onclick="if(event.target===this)this.style.display='none'">
    <div class="ve-closed-box">
        <div style="font-size:44px;margin-bottom:12px">🔒</div>
        <h2>Event is Closed</h2>
        <p>This event has been closed by the organizer. Participants can view their progress but cannot start new activities.</p>
        <button onclick="document.getElementById('veClosedPopup').style.display='none'" class="ve-closed-ok">OK</button>
    </div>
</div>

<!-- ══════ WORKFLOW STRIP CSS ══════ -->
<style>
.ve-workflow-strip {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px 20px;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 10px rgba(0,0,0,.05);
    padding: 12px 20px;
    margin: -4px 0 18px;
    font-family: 'Inter', sans-serif;
}
.vw-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.1px;
    text-transform: uppercase;
    color: #9ca3af;
    white-space: nowrap;
    padding-right: 8px;
    border-right: 1px solid #e5e7eb;
}
.vw-toggle-group {
    display: flex;
    align-items: center;
    gap: 8px;
}
.vw-toggle-group.vw-disabled { opacity: .45; pointer-events: none; }
.vw-toggle-label {
    font-size: 12px;
    font-weight: 700;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 5px;
    white-space: nowrap;
}
.vw-badge {
    font-size: 10px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 20px;
    letter-spacing: .4px;
}
.vw-badge.live   { background: #dcfce7; color: #15803d; }
.vw-badge.closed { background: #fef2f2; color: #b91c1c; }
.vw-switch { position:relative; width:40px; height:22px; flex-shrink:0; }
.vw-switch input { opacity:0; width:0; height:0; }
.vw-slider {
    position:absolute; inset:0;
    background:#d1d5db; border-radius:22px;
    cursor:pointer; transition:.25s;
}
.vw-slider::before {
    content:''; position:absolute;
    width:16px; height:16px;
    left:3px; bottom:3px;
    background:#fff; border-radius:50%;
    transition:.25s; box-shadow:0 1px 3px rgba(0,0,0,.2);
}
.vw-switch input:checked + .vw-slider { background:#2563eb; }
.vw-switch input:checked + .vw-slider::before { transform:translateX(18px); }
.live-toggle  input:checked + .vw-slider { background:#16a34a; }
.closed-toggle input:checked + .vw-slider { background:#dc2626; }
.vw-switch input:disabled + .vw-slider { opacity:.45; cursor:not-allowed; }
.vw-sep { width:1px; height:28px; background:#e5e7eb; flex-shrink:0; }
.vw-hint {
    font-size:11px; color:#9ca3af;
    max-width:160px; line-height:1.4;
}
.vw-msg {
    font-size:12px; font-weight:500;
    padding:5px 12px; border-radius:8px;
    display:none;
}
.vw-msg.show { display:block; }
.vw-msg.success { background:#f0fdf4; color:#15803d; }
.vw-msg.error   { background:#fef2f2; color:#b91c1c; }

/* Closed popup */
.ve-closed-popup {
    position:fixed; inset:0;
    background:rgba(0,0,0,.45);
    display:flex; align-items:center; justify-content:center;
    z-index:9999;
}
.ve-closed-box {
    background:#fff;
    border-radius:18px;
    padding:36px 32px;
    max-width:360px; width:90%;
    text-align:center;
    box-shadow:0 12px 40px rgba(0,0,0,.15);
    animation: popIn .25s ease;
}
@keyframes popIn {
    from { transform:scale(.9); opacity:0; }
    to   { transform:scale(1);  opacity:1; }
}
.ve-closed-box h2 { font-size:20px; font-weight:700; margin-bottom:10px; color:#111827; }
.ve-closed-box p  { font-size:14px; color:#6b7280; line-height:1.6; margin-bottom:20px; }
.ve-closed-ok {
    background:#2563eb; color:#fff;
    border:none; border-radius:10px;
    padding:10px 28px; font-size:14px; font-weight:600;
    cursor:pointer; transition:background .2s;
}
.ve-closed-ok:hover { background:#1d4ed8; }
</style>

<!-- ══════ EDIT EVENT MODAL ══════ -->
<div class="ve-modal" id="editModal" onclick="closeEditModal(event)">
    <div class="ve-modal-card edit-card">
        <h3><span class="material-symbols-rounded">edit</span> Edit Event</h3>
        <form id="editEventForm">
            <input type="hidden" name="event_id" value="<?= $event_id ?>">
            <label>Event Name</label>
            <input type="text" name="event_name" value="<?= htmlspecialchars($event['event_name']) ?>">
            <label>Description</label>
            <textarea name="event_description" rows="3"><?= htmlspecialchars($event['event_description']) ?></textarea>
            <label>Start Date</label>
            <input type="date" name="event_start_date" value="<?= date('Y-m-d', strtotime($event['event_start_date'])) ?>">
            <label>Validity (days)</label>
            <input type="number" name="event_validity" value="<?= $event['event_validity'] ?>">
            <label>Status</label>
            <select name="event_playstatus">
                <option value="1" <?= $ps==1?'selected':'' ?>>Draft</option>
                <option value="2" <?= $ps==2?'selected':'' ?>>Published</option>
                <option value="3" <?= $ps==3?'selected':'' ?>>In Progress</option>
                <option value="4" <?= $ps==4?'selected':'' ?>>Closed</option>
            </select>
            <div class="modal-actions">
                <button type="button" class="ve-btn secondary flat"
                        onclick="document.getElementById('editModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="ve-btn primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- ══════ MODERATOR SETTINGS MODAL ══════ -->
<div class="ve-modal" id="modSettingsModal" onclick="closeModSettingsModal(event)">
    <div class="ve-modal-card">
        <h3><span class="material-symbols-rounded">settings</span> Moderator Settings</h3>
        <p style="font-size:14px; color:#4b5563; margin-bottom: 20px;">Configure how game modules are released to participants.</p>
        
        <?php if ($isLive): ?>
            <div style="background: #fef2f2; border-left: 4px solid #ef4444; padding: 12px; margin-bottom: 20px; border-radius: 6px;">
                <p style="margin: 0; color: #991b1b; font-size: 13px; font-weight: 500;">
                    <span class="material-symbols-rounded" style="font-size: 16px; vertical-align: -3px; margin-right: 4px;">warning</span>
                    Event is currently LIVE! You cannot change Progression or Release modes. Please set to CLOSED first. Manual padlocks can still be toggled.
                </p>
            </div>
        <?php endif; ?>
        
        <div id="modAlert" style="display:none; padding:10px; border-radius:6px; margin-bottom:15px; font-size:13px; font-weight:500;"></div>

        <div class="mod-settings-group">
            <label class="mod-label">Progression Mode</label>
            <div class="mod-btn-group">
                <button class="mod-btn <?= $eventProgression == 1 ? 'active' : '' ?>" id="btnSeq" onclick="setProgression(1)" <?= $isLive ? 'disabled' : '' ?>>Sequence</button>
                <button class="mod-btn <?= $eventProgression == 2 ? 'active' : '' ?>" id="btnRand" onclick="setProgression(2)" <?= $isLive ? 'disabled' : '' ?>>Random</button>
            </div>
            <p class="mod-hint">Sequence means users must complete modules in order. Random allows access to any unlocked module.</p>
        </div>

        <div class="mod-settings-group">
            <label class="mod-label">Release Mode</label>
            <div class="mod-btn-group">
                <button class="mod-btn <?= $eventRelease == 1 ? 'active' : '' ?>" id="btnAuto" onclick="setRelease(1)" <?= $isLive ? 'disabled' : '' ?>>Auto</button>
                <button class="mod-btn <?= $eventRelease == 2 ? 'active' : '' ?>" id="btnMan" onclick="setRelease(2)" <?= $isLive ? 'disabled' : '' ?>>Manual</button>
            </div>
            <p class="mod-hint">Auto automatically unlocks modules based on progression. Manual requires Admin to unlock below.</p>
        </div>

        <div id="manualUnlockSection" style="display: <?= $eventRelease == 2 ? 'block' : 'none' ?>; margin-top: 25px;">
            <label class="mod-label" style="display:flex; justify-content:space-between; align-items:center;">
                Module Lock Manager
                <button class="ve-btn ghost mod-unlock-all-btn" onclick="unlockAllModules()">Unlock All</button>
            </label>
            <div class="mod-list">
                <?php foreach($dbModules as $idx => $mod): ?>
                    <div class="mod-list-item">
                        <div class="mod-list-info">
                            <span class="mod-num"><?= $idx + 1 ?></span>
                            <span class="mod-name" title="<?= htmlspecialchars($mod['module_name']) ?>"><?= htmlspecialchars($mod['module_name']) ?></span>
                        </div>
                        <label class="vw-switch">
                            <input type="checkbox" data-modid="<?= $mod['mod_id'] ?>" onclick="return handleModuleLockClick(event, <?= $idx ?>, <?= $mod['mod_id'] ?>, this)" <?= $mod['mod_is_unlocked'] == 1 ? 'checked' : '' ?>>
                            <span class="vw-slider" style="background:#e5e7eb;"></span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="modal-actions" style="margin-top:25px;">
            <button type="button" class="ve-btn primary" onclick="document.getElementById('modSettingsModal').classList.remove('open')">Done</button>
        </div>
    </div>
</div>

<style>
/* Mod settings styles */
#modSettingsModal .ve-modal-card { 
    max-width: 600px; /* Made larger */
    padding: 32px; 
    border-radius: 16px; 
    box-shadow: 0 10px 40px rgba(0,0,0,0.1); 
}
#modSettingsModal h3 {
    margin-top: 0;
    font-size: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #111827;
}
.mod-settings-group { margin-bottom: 24px; }
.mod-label { display: block; font-weight: 600; color: #1f2937; margin-bottom: 10px; font-size: 15px; }
.mod-btn-group { display: flex; border: 1px solid #d1d5db; border-radius: 10px; overflow: hidden; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
.mod-btn { flex: 1; padding: 10px 16px; background: transparent; border: none; font-size: 15px; font-weight: 500; color: #4b5563; cursor: pointer; transition: all 0.2s ease; outline: none; }
.mod-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.mod-btn.active { background: #eff6ff; color: #1d4ed8; font-weight: 600; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02); }
.mod-btn:not(:last-child) { border-right: 1px solid #e5e7eb; }
.mod-hint { font-size: 13px; color: #6b7280; margin-top: 8px; line-height: 1.4; }

.mod-unlock-all-btn { padding: 6px 12px; font-size: 13px; font-weight: 600; display:flex; align-items:center; gap:4px; }
.mod-unlock-all-btn .material-symbols-rounded { font-size: 16px; }

.mod-list { border: 1px solid #e5e7eb; border-radius: 12px; background: #f9fafb; padding: 12px; max-height: 300px; overflow-y: auto; }
.mod-list-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 14px; background: #fff; border-radius: 8px; border: 1px solid #f3f4f6; margin-bottom: 8px; transition: box-shadow 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.02); }
.mod-list-item:hover { box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
.mod-list-item:last-child { margin-bottom: 0; }
.mod-list-info { display: flex; align-items: center; gap: 12px; overflow: hidden; white-space: nowrap; max-width: 350px;}
.mod-num { background: #f3f4f6; color: #374151; font-size: 13px; font-weight: 700; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border-radius: 50%; border: 1px solid #e5e7eb;}
.mod-name { font-size: 15px; font-weight: 500; color: #1f2937; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* Overriding switch for padlock */
.vw-switch input:checked + .vw-slider { background: #22c55e !important; }
</style>

<script>
const EVENT_ID  = <?= $event_id ?>;
const IS_CLOSED = <?= $isClosed ? 'true' : 'false' ?>;
const IS_PUBLISHED = <?= $isPublished ? 'true' : 'false' ?>;
const IS_EXPIRED = <?= $isExpired ? 'true' : 'false' ?>;
let matrixPage  = 1;
let scoresPage  = 1;
let matrixSearch = '';
let scoresSearch = '';
let searchTimeout = null;
let activeTab   = 'matrix';
let pinnedRows  = [];

function doSearch(type, val) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        if (type === 'matrix') {
            matrixSearch = val;
            loadMatrix(1);
        } else {
            scoresSearch = val;
            loadScores(1);
        }
    }, 400);
}

/* ── EXPORT ─────────────────────────────────────────────── */
function doExport() {
    const type = activeTab === 'scores' ? 'scores' : 'progress';
    window.location.href = `export_event_data.php?event_id=${EVENT_ID}&type=${type}`;
}

/* ── TABS ────────────────────────────────────────────────── */
function switchTab(tab, btn) {
    activeTab = tab;
    document.querySelectorAll('.ve-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.ve-section').forEach(s => s.classList.add('hidden'));
    btn.classList.add('active');
    document.getElementById('tab-' + tab).classList.remove('hidden');
}

/* ── PROGRESS MATRIX ─────────────────────────────────────── */
function loadMatrix(page) {
    matrixPage = page;
    fetch(`get_progress_matrix.php?event_id=${EVENT_ID}&page=${page}&per_page=10&search=${encodeURIComponent(matrixSearch)}`)
        .then(r => r.json()).then(data => {
            document.getElementById('s-total').textContent = data.stats.total_participants;
            document.getElementById('s-avg').textContent   = data.stats.avg_completion + '%';
            document.getElementById('s-mods').textContent  = data.stats.modules_count;
            document.getElementById('s-top').textContent   = data.stats.top_performer;

            pinnedRows = data.rows;
            const thead = document.getElementById('matrix-thead-row');
            const tbody = document.getElementById('matrix-tbody');
            const empty = document.getElementById('matrix-empty');
            const pager = document.getElementById('matrix-pager');

            if (!data.rows.length) {
                document.getElementById('matrix-table').classList.add('hidden');
                empty.classList.remove('hidden');
                pager.innerHTML = '';
                return;
            }
            document.getElementById('matrix-table').classList.remove('hidden');
            empty.classList.add('hidden');

            thead.innerHTML = '<th><div>Participant</div><small class="th-pin-hint">Click name to reveal PIN</small></th>' +
                data.modules.map(m => `<th>${m}</th>`).join('');

            tbody.innerHTML = data.rows.map((row, i) => {
                const initials = row.user_name.split(' ').map(w=>w[0]).join('').substring(0,2).toUpperCase();
                const cells = row.statuses.map(s => {
                    const norm = s.trim().toLowerCase();
                    const cls   = norm === 'completed' ? 'st-done' : norm === 'in progress' ? 'st-prog' : 'st-none';
                    const label = norm === 'completed' ? 'COMPLETED' : norm === 'in progress' ? 'IN PROGRESS' : 'NOT STARTED';
                    return `<td><span class="st-badge ${cls}">${label}</span></td>`;
                }).join('');
                return `<tr><td><div class="participant-cell" onclick="openUserPanel(${row.user_id})">
                    <span class="p-avatar">${initials}</span>
                    <span class="p-name">${row.user_name}</span>
                </div></td>${cells}</tr>`;
            }).join('');

            renderPager(pager, data.pagination, loadMatrix);
        });
}

/* ── SORT SCORES TABLE ───────────────────────────────────── */
let sortColIdx  = null;    // current sorted column (number | 'total' | null)
let sortDir     = 'none';  // 'desc' | 'asc' | 'none'
let scoresCache = [];      // last fetched rows
let originalOrder = [];    // preserved original order for reset

function resetSortArrows() {
    document.querySelectorAll('.sort-arrows').forEach(el => {
        el.textContent = '\u2195';
        el.classList.remove('arr-active');
    });
}

function sortScores(colIdx) {
    if (sortColIdx === colIdx) {
        // Cycle: desc → asc → none (reset)
        if (sortDir === 'desc') { sortDir = 'asc'; }
        else if (sortDir === 'asc') { sortDir = 'none'; sortColIdx = null; }
        else { sortDir = 'desc'; }
    } else {
        sortColIdx = colIdx;
        sortDir    = 'desc'; // first click on new column → highest first
    }

    // Update arrow UI
    resetSortArrows();
    if (sortDir !== 'none') {
        const arrowEl = document.getElementById(
            colIdx === 'total' ? 'sort-arr-total' : `sort-arr-${colIdx}`
        );
        if (arrowEl) {
            arrowEl.textContent = sortDir === 'desc' ? '\u2193' : '\u2191';
            arrowEl.classList.add('arr-active');
        }
    }

    // Build sorted list
    const tbody = document.getElementById('scores-tbody');
    const rowsToRender = sortDir === 'none'
        ? [...originalOrder]
        : [...scoresCache].sort((a, b) => {
            const va = sortColIdx === 'total' ? a.total : (a.scores[sortColIdx] ?? -1);
            const vb = sortColIdx === 'total' ? b.total : (b.scores[sortColIdx] ?? -1);
            return sortDir === 'asc' ? va - vb : vb - va;
        });

    tbody.innerHTML = rowsToRender.map(row => {
        const initials = row.user_name.split(' ').map(w=>w[0]).join('').substring(0,2).toUpperCase();
        const cells = row.scores.map((s, ci) => {
            if (s === null) return `<td><span class="sc-chip na">—</span></td>`;
            const cls = s > 0 ? 'sc-high' : 'sc-na';
            const active = (sortColIdx === ci && sortDir !== 'none') ? ' col-sorted' : '';
            return `<td><span class="sc-chip ${cls}${active}">${s}</span></td>`;
        }).join('');
        const totalActive = (sortColIdx === 'total' && sortDir !== 'none') ? ' col-sorted' : '';
        return `<tr><td><div class="participant-cell" onclick="openUserPanel(${row.user_id})">
            <span class="p-avatar">${initials}</span>
            <span class="p-name">${row.user_name}</span>
        </div></td>${cells}<td><strong class="sc-total${totalActive}">${row.total}</strong></td></tr>`;
    }).join('');
}

/* ── SCORES ──────────────────────────────────────────────── */
function loadScores(page) {
    scoresPage    = page;
    sortColIdx    = null;
    sortDir       = 'none';
    resetSortArrows();
    fetch(`get_scores_by_round.php?event_id=${EVENT_ID}&page=${page}&per_page=10&search=${encodeURIComponent(scoresSearch)}`)
        .then(r => r.json()).then(data => {
            document.getElementById('sc-total').textContent = data.stats.total_score;
            document.getElementById('sc-avg').textContent   = data.stats.class_avg;
            document.getElementById('sc-high').textContent  = data.stats.high_score;
            document.getElementById('sc-low').textContent   = data.stats.low_score;

            const thead = document.getElementById('scores-thead-row');
            const tbody = document.getElementById('scores-tbody');
            const empty = document.getElementById('scores-empty');
            const pager = document.getElementById('scores-pager');

            if (!data.rows.length) {
                document.getElementById('scores-table').classList.add('hidden');
                empty.classList.remove('hidden');
                pager.innerHTML = '';
                return;
            }
            document.getElementById('scores-table').classList.remove('hidden');
            empty.classList.add('hidden');

            thead.innerHTML = '<th>Participant</th>' +
                data.modules.map((m, i) => `<th class="sortable-col" onclick="sortScores(${i})">Round ${i+1} <span class="sort-arrows" id="sort-arr-${i}">\u2195</span><br><small>${m}</small></th>`).join('') +
                `<th class="sortable-col" onclick="sortScores('total')">Total <span class="sort-arrows" id="sort-arr-total">\u2195</span></th>`;

            // Cache & preserve original order for reset
            scoresCache   = data.rows;
            originalOrder = [...data.rows];

            tbody.innerHTML = data.rows.map(row => {
                const initials = row.user_name.split(' ').map(w=>w[0]).join('').substring(0,2).toUpperCase();
                const cells = row.scores.map(s => {
                    if (s === null) return `<td><span class="sc-chip na">—</span></td>`;
                    const cls = s > 0 ? 'sc-high' : 'sc-na';
                    return `<td><span class="sc-chip ${cls}">${s}</span></td>`;
                }).join('');
                return `<tr><td><div class="participant-cell" onclick="openUserPanel(${row.user_id})">
                    <span class="p-avatar">${initials}</span>
                    <span class="p-name">${row.user_name}</span>
                </div></td>${cells}<td><strong class="sc-total">${row.total}</strong></td></tr>`;
            }).join('');

            renderPager(pager, data.pagination, loadScores);
        });
}

/* ── PAGINATION ──────────────────────────────────────────── */
function renderPager(container, pg, fn) {
    let html = `<span>Showing ${pg.total} participants</span><div class="pager-btns">`;
    if (pg.pages <= 1) {
        html += `</div>`;
    } else {
        if (pg.page > 1) html += `<button onclick="(${fn.name})(${pg.page-1})">‹ Prev</button>`;
        for (let i = 1; i <= pg.pages; i++) {
            html += `<button class="${i===pg.page?'active':''}" onclick="(${fn.name})(${i})">${i}</button>`;
        }
        if (pg.page < pg.pages) html += `<button onclick="(${fn.name})(${pg.page+1})">Next ›</button>`;
        html += '</div>';
    }
    container.innerHTML = html;
}

/* ── COPY LINK ───────────────────────────────────────────── */
function copyEventLink(code) {
    if (!code) return alert("No link available for this event.");
    const link = window.location.origin + '/trainerbyte/' + code;
    navigator.clipboard.writeText(link).then(() => {
        // Optional: Show a "Copied!" toast or change icon briefly
        const btn = document.querySelector('.ve-copy-btn-small');
        const icon = btn.querySelector('.material-symbols-rounded');
        const oldIcon = icon.textContent;
        icon.textContent = 'check';
        btn.style.color = '#15803d';
        setTimeout(() => {
            icon.textContent = oldIcon;
            btn.style.color = '';
        }, 2000);
        
        console.log("Copied: " + link);
    }).catch(() => alert("Failed to copy link."));
}

/* ── USER PROFILE REDIRECT ───────────────────────────────── */
function openUserPanel(userId) {
    window.location.href = `user_performance.php?event_id=${EVENT_ID}&user_id=${userId}`;
}

/* ── MODERATOR SETTINGS MODAL ────────────────────────────────  */
function openModSettingsModal() { document.getElementById('modSettingsModal').classList.add('open'); }
function closeModSettingsModal(e) {
    if (e.target.id === 'modSettingsModal') document.getElementById('modSettingsModal').classList.remove('open');
}

function showModAlert(msg, type = 'error') {
    const box = document.getElementById('modAlert');
    box.style.display = 'block';
    box.style.backgroundColor = type === 'error' ? '#fef2f2' : '#f0fdf4';
    box.style.color = type === 'error' ? '#991b1b' : '#166534';
    box.style.border = type === 'error' ? '1px solid #f87171' : '1px solid #4ade80';
    box.textContent = msg;
    
    // Auto-hide after 4 seconds
    setTimeout(() => { box.style.display = 'none'; }, 4000);
}

function setProgression(val) {
    saveModSettings('progression', val, function() {
        document.getElementById('btnSeq').classList.remove('active');
        document.getElementById('btnRand').classList.remove('active');
        if (val === 1) {
            document.getElementById('btnSeq').classList.add('active');
            
            // If switching to Sequence, lock all modules to ensure sequence compliance
            if (document.getElementById('btnMan').classList.contains('active')) {
                lockAllModules();
            }
        } else {
            document.getElementById('btnRand').classList.add('active');
        }
    });
}

function setRelease(val) {
    saveModSettings('release', val, function() {
        document.getElementById('btnAuto').classList.remove('active');
        document.getElementById('btnMan').classList.remove('active');
        if (val === 1) document.getElementById('btnAuto').classList.add('active');
        else document.getElementById('btnMan').classList.add('active');
        
        // Toggle manual section visibility
        document.getElementById('manualUnlockSection').style.display = (val === 2) ? 'block' : 'none';
        
        // If switching to Manual and we are in Sequence mode, it's safer to lock out of sync padlocks
        // but locking them all ensures a clean slate
        if (val === 2 && document.getElementById('btnSeq').classList.contains('active')) {
            lockAllModules();
        }
    });
}

function saveModSettings(type, val, onSuccess) {
    fetch('ajax_moderator_settings.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `action=update_mode&type=${type}&value=${val}&event_id=${EVENT_ID}`
    }).then(r=>r.json()).then(d=>{
        if(!d.success) {
            showModAlert(d.msg || 'Update failed', 'error');
        } else {
            onSuccess();
            showModAlert('Settings updated successfully', 'success');
        }
    }).catch(()=>{
        showModAlert('Network error', 'error');
    });
}

function handleModuleLockClick(e, idx, modId, checkboxEl) {
    // In an onclick handler for a checkbox, 'checkboxEl.checked' already reflects the requested NEW state
    const isUnlocked = checkboxEl.checked;
    
    // Enforce Sequence Mode logic
    if (document.getElementById('btnSeq').classList.contains('active')) {
        const checkboxes = document.getElementById('manualUnlockSection').querySelectorAll('input[type="checkbox"]');
        
        if (isUnlocked) {
            // Trying to unlock. Check if all previous modules are unlocked.
            for (let i = 0; i < idx; i++) {
                if (!checkboxes[i].checked) {
                    showModAlert('You must unlock previous modules first in Sequence mode.', 'error');
                    return false; // Block the click natively
                }
            }
        } else {
            // Trying to lock. Check if any subsequent modules are unlocked.
            for (let i = idx + 1; i < checkboxes.length; i++) {
                if (checkboxes[i].checked) {
                    showModAlert('You must lock subsequent modules first in Sequence mode.', 'error');
                    return false; // Block the click natively
                }
            }
        }
    }

    // Sequence valid. Pre-emptively visually update. No icon logic needed anymore.
    
    fetch('ajax_moderator_settings.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `action=update_lock&mod_id=${modId}&value=${isUnlocked?1:0}`
    }).then(r=>r.json()).then(d=>{
        if (d.success) {
            showModAlert('Module lock updated successfully', 'success');
        } else {
            showModAlert(d.msg || 'Lock update failed', 'error');
            // Rollback visually because server failed
            checkboxEl.checked = !isUnlocked; 
        }
    }).catch(()=>{
        showModAlert('Network error', 'error');
        // Rollback visually
        checkboxEl.checked = !isUnlocked; 
    });

    return true; // Allow checkbox to toggle fully
}

function lockAllModules() {
    const checkboxes = document.getElementById('manualUnlockSection').querySelectorAll('input[type="checkbox"]');
    let locksProcessed = false;
    checkboxes.forEach((cb) => {
        if (cb.checked) {
            cb.checked = false;
            locksProcessed = true;
            const modId = cb.getAttribute('data-modid');
            // Fire async lock
            fetch('ajax_moderator_settings.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: `action=update_lock&mod_id=${modId}&value=0`
            });
        }
    });
    
    if (locksProcessed) {
        showModAlert('All modules locked due to Sequence switch.', 'success');
    }
}

function unlockAllModules() {
    // If Sequence mode, we MUST unlock sequentially. But 'unlockAll' simply clicks them in order.
    // Wait, since we now simulate clicks, doing them in order naturally works!
    const checkboxes = document.getElementById('manualUnlockSection').querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach((cb, i) => {
        if (!cb.checked) {
            // Manually simulate the click correctly passing the mock event 
            cb.click();
        }
    });
}

/* ── EDIT MODAL ──────────────────────────────────────────── */
function openEditModal() { document.getElementById('editModal').classList.add('open'); }
function closeEditModal(e) {
    if (e.target.id === 'editModal') document.getElementById('editModal').classList.remove('open');
}
document.getElementById('editEventForm').addEventListener('submit', e => {
    e.preventDefault();
    fetch('update_event.php', { method: 'POST', body: new FormData(e.target) })
        .then(r => r.json())
        .then(data => { if (data.status === 'success') location.reload(); else alert(data.message); });
});

/* ── INIT ────────────────────────────────────────────────── */

window.addEventListener('DOMContentLoaded', () => {
    loadMatrix(1);
    loadScores(1);

    /* ── SETTINGS BUTTON (opens edit modal) ── */
    // Bind Edit button (Edit Event next to Settings)
    // Wait, the Edit Event is a <form> but let's wire up settingsBtn properly
    document.getElementById('settingsBtn')?.addEventListener('click', openModSettingsModal);

    // Initialize Copy Link UI (FULL LINK)
    const linkEl = document.querySelector('.ve-copy-url');
    if (linkEl) {
        const code = linkEl.getAttribute('data-code');
        const baseUrl = window.location.origin + '/trainerbyte/';
        const fullLink = baseUrl + code;

        linkEl.textContent = fullLink;
        linkEl.title = fullLink;
    }
});

/* ══════ WORKFLOW TOGGLES ══════ */
function vwMsg(msg, type) {
    const el = document.getElementById('vwMsg');
    el.className = 'vw-msg ' + type + ' show';
    el.textContent = msg;
    setTimeout(() => el.classList.remove('show'), 4000);
}

function vwSetLive(on) {
    const liveToggle   = document.getElementById('vw-toggleLive');
    const closedToggle = document.getElementById('vw-toggleClosed');
    liveToggle.disabled   = true;
    closedToggle.disabled = true;
    fetch('ajax_event_status.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `action=set_live&event_id=${EVENT_ID}&value=${on?1:0}`
    })
    .then(r=>r.json()).then(d=>{
        liveToggle.disabled   = false;
        closedToggle.disabled = false;
        if (d.success) {
            /* LIVE ON  → status 3 | LIVE OFF → status 4 (Closed) */
            liveToggle.checked   = (d.status === 3);
            closedToggle.checked = (d.status !== 3);
            updateStatusBadge(d.status);
            vwMsg(d.msg, 'success');
        } else {
            liveToggle.checked = !on;
            vwMsg(d.msg, 'error');
        }
    }).catch(() => {
        liveToggle.disabled = false; closedToggle.disabled = false;
        liveToggle.checked  = !on;
        vwMsg('Network error.','error');
    });
}

function vwSetClosed(on) {
    const liveToggle   = document.getElementById('vw-toggleLive');
    const closedToggle = document.getElementById('vw-toggleClosed');
    liveToggle.disabled   = true;
    closedToggle.disabled = true;
    fetch('ajax_event_status.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `action=set_closed&event_id=${EVENT_ID}&value=${on?1:0}`
    })
    .then(r=>r.json()).then(d=>{
        liveToggle.disabled   = false;
        closedToggle.disabled = IS_EXPIRED && (d.status === 4);
        if (d.success) {
            /* CLOSED ON  → status 4 | CLOSED OFF → status 3 (Live) */
            closedToggle.checked = (d.status !== 3);
            liveToggle.checked   = (d.status === 3);
            updateStatusBadge(d.status);
            vwMsg(d.msg, 'success');
        } else {
            closedToggle.checked = !on;
            liveToggle.disabled  = false;
            vwMsg(d.msg, 'error');
        }
    }).catch(() => {
        liveToggle.disabled = false; closedToggle.disabled = false;
        closedToggle.checked = !on;
        vwMsg('Network error.','error');
    });
}

function updateStatusBadge(ps) {
    const map   = {1:'Draft', 2:'Published', 3:'In Progress', 4:'Closed'};
    const cls   = {1:'not-started', 2:'open', 3:'in-progress', 4:'completed'};
    const badge = document.querySelector('.ve-status-badge');
    if (badge) {
        badge.textContent = map[ps] ?? '';
        badge.className   = 've-status-badge ' + (cls[ps] ?? '');
    }
}
</script>

<?php // require "../layout/footer.php"; ?>