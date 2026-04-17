<?php
session_start();
require "include/coreDataconnect.php";

if (!isset($_SESSION['team_id'])) {
    header("Location: login.php");
    exit;
}

$event_id = intval($_GET['event_id'] ?? 0);
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
$ps          = $event['event_playstatus'];
$statusLabel = $statusMap[$ps] ?? 'Unknown';
$statusCls   = $statusClass[$ps] ?? '';

$pageTitle = htmlspecialchars($event['event_name']);
$pageCSS   = "css_event/view_event.css";
require "layout/header.php";
?>

<!-- Google Material Symbols -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">

<div class="ve-page">

    <!-- ══════ PAGE HEADER (SaaS style) ══════ -->
    <div class="ve-header">
        <div class="ve-header-left">
            <a href="myevent.php" class="ve-back">
                <span class="material-symbols-rounded">arrow_back</span>
            </a>
            <div class="ve-header-info">
                <div class="ve-header-title-row">
                    <h1><?= htmlspecialchars($event['event_name']) ?></h1>
                    <span class="ve-status-badge <?= $statusCls ?>"><?= $statusLabel ?></span>
                </div>
                <div class="ve-header-chips">
                    <span class="chip"><span class="material-symbols-rounded">calendar_today</span><?= date("d M Y", strtotime($event['event_start_date'])) ?></span>
                    <span class="chip"><span class="material-symbols-rounded">hourglass_bottom</span><?= $event['event_validity'] ?> days</span>
                    <span class="chip"><span class="material-symbols-rounded">key</span><?= htmlspecialchars($event['event_passcode']) ?></span>
                </div>
            </div>
        </div>
        <div class="ve-header-actions">
            <div class="ve-copy-pill">
                <span class="ve-copy-url" data-code="<?= htmlspecialchars($event['event_url_code'] ?? '') ?>">Loading...</span>
                <button class="ve-copy-btn-small" onclick="copyEventLink('<?= htmlspecialchars($event['event_url_code'] ?? '') ?>')" title="Copy Link">
                    <span class="material-symbols-rounded">content_copy</span>
                </button>
            </div>
            <!-- <a href="add_modules.php?event_id=<?= $event_id ?>" class="ve-btn primary">
                <span class="material-symbols-rounded">add_circle</span> Manage Modules
            </a> -->
            <a href="create_event.php?event_id=<?= $event_id ?>" class="ve-btn secondary">
                <span class="material-symbols-rounded">edit</span> Edit Event</a>
            <button class="ve-btn ghost" id="exportBtn" onclick="doExport()">
                <span class="material-symbols-rounded">download</span> Export CSV
            </button>
        </div>
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
                <div class="stat-body"><div class="stat-value" id="s-top" style="font-size:14px">—</div><div class="stat-label">Top Performer</div></div>
            </div>
        </div>

        <div class="ve-legend">
            <div class="ve-legend-status">
                <span><span class="dot dot-completed"></span> Completed</span>
                <span><span class="dot dot-inprogress"></span> In Progress</span>
                <span><span class="dot dot-notstarted"></span> Not Started</span>
                <small>Click a participant's name to reveal PIN</small>
            </div>
            <div class="ve-search-box">
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
        </div>

        <div class="ve-legend">
            <div class="ve-legend-status">
                <span><span class="score-chip high">98</span> High ≥80</span>
                <span><span class="score-chip mid">65</span> Mid 50–79</span>
                <span><span class="score-chip low">42</span> Low &lt;50</span>
            </div>
            <div class="ve-search-box">
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

<script>
const EVENT_ID  = <?= $event_id ?>;
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

            thead.innerHTML = '<th>Participant</th>' +
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
    const link = window.location.origin + '/' + code;
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
</script>

<?php // require "../layout/footer.php"; ?>