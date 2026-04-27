<?php
require_once "include/session_check.php";
require "include/coreDataconnect.php";

if (!isset($_SESSION['team_id'])) { header("Location: login.php"); exit; }

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
if ($event_id <= 0) die("Invalid Event");

$stmt = $conn->prepare("SELECT * FROM tb_events WHERE event_id = ? AND event_team_pkid = ?");
$stmt->bind_param("ii", $event_id, $_SESSION['team_id']);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
if (!$event) die("Event not found");

$statusMap   = [1 => 'Draft', 2 => 'Published', 3 => 'In Progress', 4 => 'Closed'];
$statusClass = [1 => 'not-started', 2 => 'open', 3 => 'in-progress', 4 => 'completed'];
$ps      = (int)$event['event_playstatus'];

/* Workflow state — identical logic to view_event.php */
$isPublished = $ps >= 2;
$isLive      = $ps === 3;
$isClosed    = $ps !== 3; // closed when NOT live (matches view_event.php)

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

$togglesDisabled = $isExpired;

/* Recompute label/class after possible auto-close (matches view_event.php) */
$statusLabel = $statusMap[$ps] ?? 'Unknown';
$statusCls   = $statusClass[$ps] ?? '';

$eventProgression = max(1, (int)($event['event_progression']));
$eventRelease     = max(1, (int)($event['event_release']));

/* Modules */
$moduleSources = [
    2 => ['table' => 'card_group',        'id' => 'cg_id', 'name' => 'cg_name'],
    5 => ['table' => 'mg5_digisim',        'id' => 'di_id', 'name' => 'di_name'],
    6 => ['table' => 'mg6_riskhop_matrix', 'id' => 'id',    'name' => 'game_name'],
    9 => ['table' => 'mg5_digisim',        'id' => 'di_id', 'name' => 'di_name'],
];
$unions = [];
foreach ($moduleSources as $type => $cfg)
    $unions[] = "SELECT {$cfg['id']} AS module_id, $type AS module_type, {$cfg['name']} AS module_name FROM {$cfg['table']}";
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
$modCount  = count($dbModules);
$unlockedCount = array_sum(array_column($dbModules, 'mod_is_unlocked'));

/* ── CHECK IF ANY PARTICIPANT HAS PLAYED ── */
$stmtPlayers = $conn->prepare("
    SELECT COUNT(DISTINCT user_id) AS player_count
    FROM tb_event_user_score
    WHERE event_id = ?
");
$stmtPlayers->bind_param("i", $event_id);
$stmtPlayers->execute();
$playerCount = (int)($stmtPlayers->get_result()->fetch_assoc()['player_count'] ?? 0);
$hasPlayers  = $playerCount > 0;

$pageTitle = "Moderator Settings – " . htmlspecialchars($event['event_name']);
$pageCSS   = "css_event/moderator_settings.css";
require "layout/header.php";
require "layout/tb_header.php";
?>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">

<!-- ═══════════════════════════════════════════════════════════ -->
<!--  FULL-PAGE MODERATOR SETTINGS                              -->
<!-- ═══════════════════════════════════════════════════════════ -->
<!-- WARNING MODAL (shown when participants have already played) -->
<div class="ms-warn-overlay" id="warnOverlay" style="display:none;" onclick="if(event.target===this)cancelWarn()">
    <div class="ms-warn-box">
        <div class="ms-warn-icon">
            <span class="material-symbols-rounded">warning_amber</span>
        </div>
        <div class="ms-warn-body">
            <h3>Active Participants Detected</h3>
            <p id="warnMsg">Changing this setting while participants are actively playing may disrupt their experience.</p>
            <div class="ms-warn-meta">
                <span class="material-symbols-rounded">group</span>
                <strong><?= $playerCount ?></strong> participant<?= $playerCount !== 1 ? 's have' : ' has' ?> already started playing this event.
            </div>
        </div>
        <div class="ms-warn-actions">
            <button class="ms-btn outline" onclick="cancelWarn()">
                <span class="material-symbols-rounded">close</span>
                Cancel
            </button>
            <button class="ms-btn danger" onclick="confirmWarn()">
                <span class="material-symbols-rounded">check</span>
                Yes, Change Anyway
            </button>
        </div>
    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════ -->
<!--  FULL PAGE — same structure as view_event.php              -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div class="ve-page">

    <!-- ══════ PAGE HEADER — identical to view_event.php ══════ -->
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
                    <!-- <span class="chip"><span class="material-symbols-rounded">key</span><?= htmlspecialchars($event['event_passcode']) ?></span> -->
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
            <a href="view_event.php?event_id=<?= $event_id ?>" class="ve-btn ghost" title="Back to Dashboard">
                <span class="material-symbols-rounded">arrow_back</span>
                <span>Back</span>
            </a>
        </div>
    </div>

    <!-- ══════ ACCESS WORKFLOW STRIP — identical to view_event.php ══════ -->
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

    <!-- ══════ MS-ROOT (sidebar + main) ══════ -->
    <div class="ms-root">

    <!-- ══ LEFT PANEL ══ -->
    <aside class="ms-sidebar">


        <!-- Alert -->
        <div class="ms-alert" id="msAlert" style="display:none;"></div>

        <?php if ($isLive || $isExpired): ?>
        <div class="ms-live-banner">
            <span class="material-symbols-rounded">warning_amber</span>
            <div>
                <strong>Event is <?= $isExpired ? 'EXPIRED' : 'LIVE' ?></strong>
                <span>Progression & Release modes are locked while event is <?= $isExpired ? 'expired' : 'Live' ?>. If needed change the validity in edit event </span>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Divider ── -->
        <div class="ms-divider-label">PROGRESSION MODE</div>

        <div class="ms-option-list">
            <div class="ms-option-item <?= $eventProgression == 1 ? 'active' : '' ?> <?= ($isLive || $isExpired) ? 'locked' : '' ?>"
                 id="opt-seq" onclick="<?= ($isLive || $isExpired) ? '' : 'setProgression(1)' ?>">
                <div class="ms-opt-icon blue">
                    <span class="material-symbols-rounded">linear_scale</span>
                </div>
                <div class="ms-opt-body">
                    <span class="ms-opt-title">Sequence</span>
                    <span class="ms-opt-desc">Modules must be completed in strict order</span>
                </div>
                <div class="ms-opt-check" id="chk-seq">
                    <span class="material-symbols-rounded"><?= $eventProgression == 1 ? 'check_circle' : 'radio_button_unchecked' ?></span>
                </div>
            </div>

            <div class="ms-option-item <?= $eventProgression == 2 ? 'active' : '' ?> <?= ($isLive || $isExpired) ? 'locked' : '' ?>"
                 id="opt-rand" onclick="<?= ($isLive || $isExpired) ? '' : 'setProgression(2)' ?>">
                <div class="ms-opt-icon indigo">
                    <span class="material-symbols-rounded">shuffle</span>
                </div>
                <div class="ms-opt-body">
                    <span class="ms-opt-title">Random</span>
                    <span class="ms-opt-desc">Access any unlocked module freely</span>
                </div>
                <div class="ms-opt-check" id="chk-rand">
                    <span class="material-symbols-rounded"><?= $eventProgression == 2 ? 'check_circle' : 'radio_button_unchecked' ?></span>
                </div>
            </div>
        </div>

        <!-- ── Divider ── -->
        <div class="ms-divider-label">RELEASE MODE</div>

        <div class="ms-option-list">
            <div class="ms-option-item <?= $eventRelease == 1 ? 'active' : '' ?> <?= ($isLive || $isExpired) ? 'locked' : '' ?>"
                 id="opt-auto" onclick="<?= ($isLive || $isExpired) ? '' : 'setRelease(1)' ?>">
                <div class="ms-opt-icon teal">
                    <span class="material-symbols-rounded">auto_mode</span>
                </div>
                <div class="ms-opt-body">
                    <span class="ms-opt-title">Auto</span>
                    <span class="ms-opt-desc">Unlocks based on completion progress</span>
                </div>
                <div class="ms-opt-check" id="chk-auto">
                    <span class="material-symbols-rounded"><?= $eventRelease == 1 ? 'check_circle' : 'radio_button_unchecked' ?></span>
                </div>
            </div>

            <div class="ms-option-item <?= $eventRelease == 2 ? 'active' : '' ?> <?= ($isLive || $isExpired) ? 'locked' : '' ?>"
                 id="opt-manual" onclick="<?= ($isLive || $isExpired) ? '' : 'setRelease(2)' ?>">
                <div class="ms-opt-icon purple">
                    <span class="material-symbols-rounded">admin_panel_settings</span>
                </div>
                <div class="ms-opt-body">
                    <span class="ms-opt-title">Manual</span>
                    <span class="ms-opt-desc">You control each module's access</span>
                </div>
                <div class="ms-opt-check" id="chk-manual">
                    <span class="material-symbols-rounded"><?= $eventRelease == 2 ? 'check_circle' : 'radio_button_unchecked' ?></span>
                </div>
            </div>
        </div>



    </aside>

    <!-- ══ MAIN PANEL ══ -->
    <main class="ms-main">

        <!-- Main header -->
        <div class="ms-main-header">
            <div class="ms-main-title-block">
                <h1>Module Lock Manager</h1>
                <p class="ms-main-sub">
                    <?php if ($eventRelease == 2): ?>
                        <span id="mod-summary"><strong><?= $unlockedCount ?></strong> of <strong><?= $modCount ?></strong> modules unlocked</span>
                    <?php else: ?>
                        <span id="mod-summary">Switch to <strong>Manual</strong> mode to manage individual module access</span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="ms-main-actions" id="mainActions" style="<?= $eventRelease != 2 ? 'opacity:.4;pointer-events:none;' : '' ?>">
                <button class="ms-btn outline" onclick="lockAllModules()">
                    <span class="material-symbols-rounded">lock</span>
                    Lock All
                </button>
                <button class="ms-btn primary" onclick="unlockAllModules()">
                    <span class="material-symbols-rounded">lock_open</span>
                    Unlock All
                </button>
            </div>
        </div>

        <!-- Modules area -->
        <div class="ms-modules-area" id="moduleLockSection">

            <?php if ($eventRelease != 2): ?>
            <!-- Auto mode placeholder -->
            <div class="ms-modules-placeholder" id="autoPlaceholder">
                <div class="ms-placeholder-visual">
                    <span class="material-symbols-rounded">auto_awesome</span>
                </div>
                <h3>Auto Release Active</h3>
                <p>Modules are automatically unlocked as participants progress. Switch to <strong>Manual</strong> release mode to control individual module access.</p>
                <button class="ms-btn primary" onclick="setRelease(2)" <?= ($isLive || $isExpired) ? 'disabled' : '' ?>>
                    <span class="material-symbols-rounded">admin_panel_settings</span>
                    Switch to Manual
                </button>
            </div>
            <?php else: ?>
            <!-- Manual mode: modules list with scroll -->
            <div class="ms-modules-list-wrap" id="moduleListWrap">

                <?php if (empty($dbModules)): ?>
                <div class="ms-modules-placeholder">
                    <div class="ms-placeholder-visual">
                        <span class="material-symbols-rounded">inventory_2</span>
                    </div>
                    <h3>No Modules Yet</h3>
                    <p>No modules have been added to this event. Add modules from the event editor.</p>
                </div>
                <?php else: ?>

                <!-- Stats strip -->
                <div class="ms-stats-strip">
                    <div class="ms-stat">
                        <span class="ms-stat-val" id="stat-total"><?= $modCount ?></span>
                        <span class="ms-stat-lbl">Total Modules</span>
                    </div>
                    <div class="ms-stat-sep"></div>
                    <div class="ms-stat">
                        <span class="ms-stat-val green" id="stat-unlocked"><?= $unlockedCount ?></span>
                        <span class="ms-stat-lbl">Unlocked</span>
                    </div>
                    <div class="ms-stat-sep"></div>
                    <div class="ms-stat">
                        <span class="ms-stat-val red" id="stat-locked"><?= $modCount - $unlockedCount ?></span>
                        <span class="ms-stat-lbl">Locked</span>
                    </div>
                </div>

                <!-- Scrollable module list -->
                <div class="ms-modules-scroll" id="modulesScroll">
                    <?php foreach ($dbModules as $idx => $mod): 
                        $isUnlocked = $mod['mod_is_unlocked'] == 1;
                    ?>
                    <div class="ms-mod-row <?= $isUnlocked ? 'unlocked' : 'locked' ?>" id="module-item-<?= $mod['mod_id'] ?>">
                        <div class="ms-mod-num"><?= $idx + 1 ?></div>

                        <div class="ms-mod-info">
                            <span class="ms-mod-name" title="<?= htmlspecialchars($mod['module_name']) ?>">
                                <?= htmlspecialchars($mod['module_name']) ?>
                            </span>
                            <span class="ms-mod-tag <?= $isUnlocked ? 'tag-open' : 'tag-locked' ?>" id="mod-status-<?= $mod['mod_id'] ?>">
                                <span class="material-symbols-rounded" id="lock-icon-<?= $mod['mod_id'] ?>">
                                    <?= $isUnlocked ? 'lock_open' : 'lock' ?>
                                </span>
                                <?= $isUnlocked ? 'Unlocked' : 'Locked' ?>
                            </span>
                        </div>

                        <div class="ms-mod-progress">
                            <div class="ms-mod-bar <?= $isUnlocked ? 'bar-open' : '' ?>"></div>
                        </div>

                        <label class="ms-toggle" title="<?= $isUnlocked ? 'Click to lock' : 'Click to unlock' ?>">
                            <input type="checkbox"
                                data-modid="<?= $mod['mod_id'] ?>"
                                data-idx="<?= $idx ?>"
                                <?= $isUnlocked ? 'checked' : '' ?>
                                onchange="handleModuleLockChange(event, <?= $idx ?>, <?= $mod['mod_id'] ?>, this)">
                            <span class="ms-toggle-track">
                                <span class="ms-toggle-thumb"></span>
                            </span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

            </div>
            <?php endif; ?>

        </div><!-- /ms-modules-area -->

    </main>

    </div><!-- /ms-root -->

</div><!-- /ve-page -->

<script>
/* ── ALL CONSTANTS (single declaration block) ── */
const EVENT_ID        = <?= $event_id ?>;
const IS_LIVE         = <?= $isLive ? 'true' : 'false' ?>;
const IS_EXPIRED      = <?= $isExpired ? 'true' : 'false' ?>;
const HAS_PLAYERS     = <?= $hasPlayers ? 'true' : 'false' ?>;
let   currentProgression = <?= $eventProgression ?>;
let   currentRelease     = <?= $eventRelease ?>;
let   unlockedCount      = <?= $unlockedCount ?>;
const totalCount         = <?= $modCount ?>;

/* ── WARNING MODAL ── */
let _warnPendingFn = null; // stores the action to run after confirmation

function showWarn(message, onConfirm) {
    document.getElementById('warnMsg').textContent = message;
    _warnPendingFn = onConfirm;
    document.getElementById('warnOverlay').style.display = 'flex';
}
function cancelWarn() {
    document.getElementById('warnOverlay').style.display = 'none';
    _warnPendingFn = null;
}
function confirmWarn() {
    document.getElementById('warnOverlay').style.display = 'none';
    if (typeof _warnPendingFn === 'function') _warnPendingFn();
    _warnPendingFn = null;
}

/* ── ALERT ── */
function showAlert(msg, type = 'error') {
    const box = document.getElementById('msAlert');
    box.className = 'ms-alert ' + type;
    box.innerHTML = `<span class="material-symbols-rounded">${type === 'error' ? 'error' : 'check_circle'}</span><span>${msg}</span>`;
    box.style.display = 'flex';
    clearTimeout(box._t);
    box._t = setTimeout(() => { box.style.display = 'none'; }, 4000);
}

/* ── PROGRESSION ── */
function setProgression(val) {
    if (IS_LIVE) return;
    const doSave = () => {
        saveModSettings('progression', val, () => {
            currentProgression = val;
            ['seq','rand'].forEach(k => {
                const isActive = (k === 'seq' && val === 1) || (k === 'rand' && val === 2);
                document.getElementById('opt-' + k)?.classList.toggle('active', isActive);
                const chk = document.getElementById('chk-' + k);
                if (chk) chk.querySelector('.material-symbols-rounded').textContent = isActive ? 'check_circle' : 'radio_button_unchecked';
            });
            if (val === 1 && currentRelease === 2) lockAllModules(true);
            showAlert('Progression mode updated.', 'success');
        });
    };
    if (HAS_PLAYERS) {
        showWarn(
            'Changing Progression Mode while participants are active may cause confusion — they could lose their current position in the module sequence.',
            doSave
        );
    } else {
        doSave();
    }
}

/* ── RELEASE ── */
function setRelease(val) {
    if (IS_LIVE) return;
    const doSave = () => saveModSettingsAndReload('release', val);
    if (HAS_PLAYERS) {
        showWarn(
            'Changing Release Mode while participants are active may immediately lock or unlock modules they are currently accessing.',
            doSave
        );
    } else {
        doSave();
    }
}

/* ── SAVE AND RELOAD ── */
function saveModSettingsAndReload(type, val) {
    fetch('ajax_moderator_settings.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=update_mode&type=${type}&value=${val}&event_id=${EVENT_ID}`
    }).then(r => r.json()).then(d => {
        if (!d.success) {
            showAlert(d.msg || 'Update failed.', 'error');
        } else {
            // Reload page so server renders the correct UI
            window.location.reload();
        }
    }).catch(() => showAlert('Network error. Please try again.', 'error'));
}

/* ── SAVE ── */
function saveModSettings(type, val, onSuccess) {
    fetch('ajax_moderator_settings.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=update_mode&type=${type}&value=${val}&event_id=${EVENT_ID}`
    }).then(r => r.json()).then(d => {
        if (!d.success) showAlert(d.msg || 'Update failed.', 'error');
        else onSuccess();
    }).catch(() => showAlert('Network error. Please try again.', 'error'));
}

/* ── MODULE LOCK ── */

function vwMsg(txt, type) {
    const el = document.getElementById('vwMsg');
    if (!el) return;
    el.textContent = txt;
    el.className   = 'vw-msg show ' + type;
    setTimeout(() => { el.classList.remove('show'); }, 3000);
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
        closedToggle.disabled = IS_EXPIRED && (d.status === 4);
        if (d.success) {
            liveToggle.checked   = (d.status === 3);
            closedToggle.checked = (d.status !== 3);
            updateStatusBadge(d.status);
            syncSidebarLiveState(d.status === 3);  // ← real-time sidebar update
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
            closedToggle.checked = (d.status !== 3);
            liveToggle.checked   = (d.status === 3);
            updateStatusBadge(d.status);
            syncSidebarLiveState(d.status === 3);  // ← real-time sidebar update
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

/**
 * syncSidebarLiveState(isLive)
 * Called after a successful LIVE/CLOSED toggle — instantly locks or unlocks
 * the Progression Mode and Release Mode option cards in the sidebar,
 * and shows/hides the LIVE warning banner. No page refresh needed.
 */
function syncSidebarLiveState(isLive) {
    const progressionIds = ['opt-seq', 'opt-rand'];
    const releaseIds     = ['opt-auto', 'opt-manual'];
    const allOptionIds   = [...progressionIds, ...releaseIds];

    allOptionIds.forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        if (isLive) {
            el.classList.add('locked');
            el.onclick = null;  // disable clicks
        } else {
            el.classList.remove('locked');
            // Restore click handlers based on which option it is
            if (id === 'opt-seq')    el.onclick = () => setProgression(1);
            if (id === 'opt-rand')   el.onclick = () => setProgression(2);
            if (id === 'opt-auto')   el.onclick = () => setRelease(1);
            if (id === 'opt-manual') el.onclick = () => setRelease(2);
        }
    });

    // Show/hide the live warning banner
    const banner = document.querySelector('.ms-live-banner');
    if (banner) {
        banner.style.display = isLive ? '' : 'none';
    } else if (isLive) {
        // Banner didn't exist (event was not live at page load) — inject it
        const sidebar = document.querySelector('.ms-sidebar');
        const alert   = document.getElementById('msAlert');
        if (sidebar && alert) {
            const div = document.createElement('div');
            div.className = 'ms-live-banner';
            div.id        = 'ms-live-banner-dynamic';
            div.innerHTML = `<span class="material-symbols-rounded">warning_amber</span>
                <div><strong>Event is LIVE</strong>
                <span>Progression &amp; Release modes are locked while event is Live. Module locks can still be toggled.</span></div>`;
            alert.after(div);
        }
    }

    // Also disable/enable the "Switch to Manual" button inside Auto mode placeholder
    const switchBtn = document.querySelector('#autoPlaceholder button');
    if (switchBtn) switchBtn.disabled = isLive;
}


function handleModuleLockChange(e, idx, modId, checkboxEl) {
    const isUnlocked = checkboxEl.checked;
    const allCheckboxes = document.querySelectorAll('#moduleLockSection input[type="checkbox"]');

    if (currentProgression === 1) {
        if (isUnlocked) {
            for (let i = 0; i < idx; i++) {
                if (!allCheckboxes[i].checked) {
                    showAlert('Unlock previous modules first (Sequence mode).', 'error');
                    checkboxEl.checked = false; return;
                }
            }
        } else {
            for (let i = idx + 1; i < allCheckboxes.length; i++) {
                if (allCheckboxes[i].checked) {
                    showAlert('Lock subsequent modules first (Sequence mode).', 'error');
                    checkboxEl.checked = true; return;
                }
            }
        }
    }

    updateModuleVisual(modId, isUnlocked);
    updateStats(isUnlocked ? 1 : -1);

    fetch('ajax_moderator_settings.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=update_lock&mod_id=${modId}&value=${isUnlocked ? 1 : 0}`
    }).then(r => r.json()).then(d => {
        if (d.success) {
            showAlert(`Module ${isUnlocked ? 'unlocked' : 'locked'}.`, 'success');
        } else {
            showAlert(d.msg || 'Lock update failed.', 'error');
            checkboxEl.checked = !isUnlocked;
            updateModuleVisual(modId, !isUnlocked);
            updateStats(isUnlocked ? -1 : 1);
        }
    }).catch(() => {
        showAlert('Network error.', 'error');
        checkboxEl.checked = !isUnlocked;
        updateModuleVisual(modId, !isUnlocked);
        updateStats(isUnlocked ? -1 : 1);
    });
}

function updateModuleVisual(modId, isUnlocked) {
    const row  = document.getElementById('module-item-' + modId);
    const tag  = document.getElementById('mod-status-' + modId);
    const icon = document.getElementById('lock-icon-' + modId);
    const bar  = row?.querySelector('.ms-mod-bar');

    if (row) { row.classList.toggle('unlocked', isUnlocked); row.classList.toggle('locked', !isUnlocked); }
    if (tag) {
        tag.className = 'ms-mod-tag ' + (isUnlocked ? 'tag-open' : 'tag-locked');
        tag.innerHTML = `<span class="material-symbols-rounded" id="lock-icon-${modId}">${isUnlocked ? 'lock_open' : 'lock'}</span>${isUnlocked ? 'Unlocked' : 'Locked'}`;
    }
    if (bar) { bar.classList.toggle('bar-open', isUnlocked); }
}

function updateStats(delta) {
    unlockedCount += delta;
    const u = document.getElementById('stat-unlocked');
    const l = document.getElementById('stat-locked');
    const s = document.getElementById('mod-summary');
    if (u) u.textContent = unlockedCount;
    if (l) l.textContent = totalCount - unlockedCount;
    if (s) s.innerHTML = `<strong>${unlockedCount}</strong> of <strong>${totalCount}</strong> modules unlocked`;
}

function lockAllModules(silent = false) {
    document.querySelectorAll('#moduleLockSection input[type="checkbox"]').forEach(cb => {
        const modId = parseInt(cb.getAttribute('data-modid'));
        if (cb.checked) {
            cb.checked = false;
            updateModuleVisual(modId, false);
            updateStats(-1);
            fetch('ajax_moderator_settings.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=update_lock&mod_id=${modId}&value=0`
            });
        }
    });
    if (!silent) showAlert('All modules locked.', 'success');
}

function unlockAllModules() {
    document.querySelectorAll('#moduleLockSection input[type="checkbox"]').forEach(cb => {
        if (!cb.checked) cb.click();
    });
}

/* ── COPY LINK ── */
function copyEventLink(code) {
    if (!code) return alert("No link available for this event.");
    const link = window.location.origin + '/trainerbyte/' + code;
    navigator.clipboard.writeText(link).then(() => {
        const btn = document.querySelector('.ve-copy-btn-small');
        const icon = btn.querySelector('.material-symbols-rounded');
        const oldIcon = icon.textContent;
        icon.textContent = 'check';
        btn.style.color = '#15803d';
        setTimeout(() => {
            icon.textContent = oldIcon;
            btn.style.color = '';
        }, 2000);
    }).catch(() => alert("Failed to copy link."));
}

/* ── INIT ── */
window.addEventListener('DOMContentLoaded', () => {
    // Initialize Copy Link UI — identical to view_event.php
    const linkEl = document.querySelector('.ve-copy-url');
    if (linkEl) {
        const code = linkEl.getAttribute('data-code');
        const baseUrl = window.location.origin + '/trainerbyte/';
        const fullLink = baseUrl + code;
        linkEl.textContent = fullLink;
        linkEl.title = fullLink;
    }
});
</script>

<?php // require "layout/footer.php"; ?>
