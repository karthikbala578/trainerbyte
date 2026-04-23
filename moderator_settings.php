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
$isLive  = $ps === 3;

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

<div class="ms-root">

    <!-- ══ LEFT PANEL ══ -->
    <aside class="ms-sidebar">

        <!-- Sidebar top: event identity -->
        <div class="ms-sidebar-top">
            <a href="view_event.php?event_id=<?= $event_id ?>" class="ms-back-top">
                <span class="material-symbols-rounded">arrow_back_ios_new</span>
                Back to Dashboard
            </a>
            <div class="ms-event-identity">
                <div>
                    <div class="ms-event-label">MODERATOR SETTINGS</div>
                    <div class="ms-event-name"><?= htmlspecialchars($event['event_name']) ?></div>
                </div>
            </div>
            <span class="ms-status-pill <?= $statusClass[$ps] ?? '' ?>">
                <span class="ms-status-dot"></span>
                <?= $statusMap[$ps] ?? 'Unknown' ?>
            </span>
        </div>

        <!-- Alert -->
        <div class="ms-alert" id="msAlert" style="display:none;"></div>

        <?php if ($isLive): ?>
        <div class="ms-live-banner">
            <span class="material-symbols-rounded">warning_amber</span>
            <div>
                <strong>Event is LIVE</strong>
                <span>Progression & Release modes are locked while event is Live. Module locks can still be toggled.</span>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Divider ── -->
        <div class="ms-divider-label">PROGRESSION MODE</div>

        <div class="ms-option-list">
            <div class="ms-option-item <?= $eventProgression == 1 ? 'active' : '' ?> <?= $isLive ? 'locked' : '' ?>"
                 id="opt-seq" onclick="<?= $isLive ? '' : 'setProgression(1)' ?>">
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

            <div class="ms-option-item <?= $eventProgression == 2 ? 'active' : '' ?> <?= $isLive ? 'locked' : '' ?>"
                 id="opt-rand" onclick="<?= $isLive ? '' : 'setProgression(2)' ?>">
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
            <div class="ms-option-item <?= $eventRelease == 1 ? 'active' : '' ?> <?= $isLive ? 'locked' : '' ?>"
                 id="opt-auto" onclick="<?= $isLive ? '' : 'setRelease(1)' ?>">
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

            <div class="ms-option-item <?= $eventRelease == 2 ? 'active' : '' ?> <?= $isLive ? 'locked' : '' ?>"
                 id="opt-manual" onclick="<?= $isLive ? '' : 'setRelease(2)' ?>">
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
                <button class="ms-btn primary" onclick="setRelease(2)" <?= $isLive ? 'disabled' : '' ?>>
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

<script>
const EVENT_ID = <?= $event_id ?>;
const IS_LIVE  = <?= $isLive ? 'true' : 'false' ?>;
const HAS_PLAYERS = <?= $hasPlayers ? 'true' : 'false' ?>;
let currentProgression = <?= $eventProgression ?>;
let currentRelease     = <?= $eventRelease ?>;

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
let unlockedCount = <?= $unlockedCount ?>;
const totalCount  = <?= $modCount ?>;

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
</script>

<?php // require "layout/footer.php"; ?>
