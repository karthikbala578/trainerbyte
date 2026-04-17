<?php
require_once "include/session_check.php";
require "include/coreDataconnect.php";

if (!isset($_SESSION['team_id'])) {
    header("Location: login.php");
    exit;
}

$pageTitle = "Content Libraries";
$pageCSS   = "assets/styles/library.css";
require "layout/tb_header.php";
?>

<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">

<?php
/* ─── Fetch all libraries for this team with their classification ─── */
$stmt = $conn->prepare("SELECT cg_id, cg_name, cg_status, createddate FROM card_group WHERE byteguess_pkid =?
    ORDER BY cg_id DESC
");
$stmt->bind_param("i", $_SESSION['team_id']);
$stmt->execute();
$games = $stmt->get_result();

$all_games = [];
while ($g = $games->fetch_assoc()) {
    $all_games[] = $g;
}

/* ─── Fetch DigiSIM Libraries for this team ─── */
$stmt2 = $conn->prepare("SELECT di_id, di_name, di_status
    FROM mg5_digisim WHERE di_digisim_category_pkid = ?
    ORDER BY di_id DESC
");
$stmt2->bind_param("i", $_SESSION['team_id']);
$stmt2->execute();
$digisim_result = $stmt2->get_result();

$all_digisim = [];
while ($d = $digisim_result->fetch_assoc()) {
    $all_digisim[] = $d;
}
/* ─── Fetch MultiStage Libraries for this team ─── */
$stmt_ms = $conn->prepare("SELECT ms_id, ms_name, ms_status
    FROM mg5_ms_digisim_master
    WHERE ms_team_pkid = ?
    ORDER BY ms_id DESC
");
$stmt_ms->bind_param("i", $_SESSION['team_id']);
$stmt_ms->execute();
$ms_result = $stmt_ms->get_result();

$all_multistage = [];
while ($ms = $ms_result->fetch_assoc()) {
    $all_multistage[] = $ms;
}

$stmt6 = $conn->prepare("SELECT *
        FROM mg6_riskhop_matrix      
        ORDER BY id DESC
    ");
//$stmt6->bind_param("i", $_SESSION['team_id']);
$stmt6->execute();
$riskhop_result = $stmt6->get_result();

$all_riskhop = [];
while ($r = $riskhop_result->fetch_assoc()) {
    $all_riskhop[] = $r;
}

/* ─── Fetch Dynamic Game Counts ─── */
$counts = [
    'ByteGuess'  => count($all_games),
    'PixelQuest' => 0,
    'DigiSim'    => count($all_digisim),
    'RiskHOP'    => count($all_riskhop),
    'TrustTrap'  => 0,
    'BountyBid'  => 0,
    'DigiHunt'   => 0,
    'BitBargain' => 0,
    'MultiStage' => count($all_multistage),
];


?>

<div class="lib-page">

    <!-- ── TOP HEADER ── -->
    <div class="lib-header">
        <div class="lib-header-left" style="margin-left:100px;">
            <h1>Content Libraries</h1>
            <p>Manage and organize your training modules and assets.</p>
        </div>
        <a href="create_library.php" class="btn-create">
            <span class="material-symbols-rounded plus-icon">add_circle</span> Create New Library
        </a>
    </div>

    <!-- ── MAIN SECTION ── -->
    <div class="lib-section">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 12px;">
            <h3 style="margin: 0; font-size: 14px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Overview</h3>
            <button id="viewAllBtn" style="background: none; border: none; color: var(--primary-blue); font-weight: 700; cursor: pointer; font-size: 13px;">View All Classes ↓</button>
        </div>

    <!-- ── CLASSIFICATION STATS ── -->
    <div class="lib-stats" id="libStats">
        <div class="stat-card" onclick="filterByCategory('ByteGuess', this)" style="cursor: pointer;">
            <span class="stat-icon bg-teal">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
            </span>
            <div class="stat-body">
                <div class="stat-count"><?= $counts['ByteGuess'] ?></div>
                <span class="stat-label">BYTEGUESS</span>
            </div>
        </div>

        <div class="stat-card" onclick="filterByCategory('BountyBid', this)" style="cursor: pointer;">
            <span class="stat-icon bg-blue">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            </span>
            <div class="stat-body">
                <div class="stat-count"><?= $counts['BountyBid'] ?></div>
                <span class="stat-label">BOUNTBID</span>
            </div>
        </div>

        <div class="stat-card" onclick="filterByCategory('TrustTrap', this)" style="cursor: pointer;">
            <span class="stat-icon bg-orange">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </span>
            <div class="stat-body">
                <div class="stat-count"><?= $counts['TrustTrap'] ?></div>
                <span class="stat-label">TRUSTTRAP</span>
            </div>
        </div>

        <!-- ── HIDDEN CARDS ── -->
        <div class="stat-card extra-card" onclick="filterByCategory('PixelQuest', this)" style="display: none; cursor: pointer;">
            <span class="stat-icon bg-purple">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2" ry="2"/><path d="M6 12h4"/><path d="M8 10v4"/><line x1="15" y1="13" x2="15.01" y2="13"/><line x1="18" y1="11" x2="18.01" y2="11"/></svg>
            </span>
            <div class="stat-body">
                <div class="stat-count"><?= $counts['PixelQuest'] ?></div>
                <span class="stat-label">PIXEL QUEST</span>
            </div>
        </div>

        <div class="stat-card extra-card" onclick="filterByCategory('DigiHunt', this)" style="display: none; cursor: pointer;">
            <span class="stat-icon bg-yellow">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </span>
            <div class="stat-body">
                <div class="stat-count"><?= $counts['DigiHunt'] ?></div>
                <span class="stat-label">DIGIHUNT</span>
            </div>
        </div>

        <div class="stat-card extra-card" onclick="filterByCategory('BitBargain', this)" style="display: none; cursor: pointer;">
            <span class="stat-icon bg-red">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
            </span>
            <div class="stat-body">
                <div class="stat-count"><?= $counts['BitBargain'] ?></div>
                <span class="stat-label">BIT BARGAIN</span>
            </div>
        </div>

        <div class="stat-card extra-card" onclick="filterByCategory('RiskHOP', this)" style="display: none; cursor: pointer;">
            <span class="stat-icon bg-indigo">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            </span>
            <div class="stat-body">
                <div class="stat-count"><?= $counts['RiskHOP'] ?></div>
                <span class="stat-label">RISKHOP</span>
            </div>
        </div>

        <div class="stat-card extra-card" onclick="filterByCategory('DigiSim', this)" style="display: none; cursor: pointer;">
            <span class="stat-icon bg-slate">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2" ry="2"/><rect x="9" y="9" width="6" height="6"/><line x1="9" y1="1" x2="9" y2="4"/><line x1="15" y1="1" x2="15" y2="4"/><line x1="9" y1="20" x2="9" y2="23"/><line x1="15" y1="20" x2="15" y2="23"/><line x1="20" y1="9" x2="23" y2="9"/><line x1="20" y1="14" x2="23" y2="14"/><line x1="1" y1="9" x2="4" y2="9"/><line x1="1" y1="14" x2="4" y2="14"/></svg>
            </span>
            <div class="stat-body">
                <div class="stat-count"><?= $counts['DigiSim'] ?></div>
                <span class="stat-label">DIGISIM</span>
            </div>
        </div>
        <div class="stat-card extra-card" onclick="filterByCategory('MultiStage', this)" style="display: none; cursor: pointer;">
            <span class="stat-icon" style="color:#be185d; background:#fce7f3;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h6v6H3zM15 3h6v6h-6zM3 15h6v6H3zM15 15h6v6h-6zM9 6h6M6 9v6M18 9v6M9 18h6"/></svg>
            </span>
            <div class="stat-body">
                <div class="stat-count"><?= $counts['MultiStage'] ?></div>
                <span class="stat-label">MULTISTAGE</span>
            </div>
        </div>
    </div>

    <!-- ── LIBRARY TABLE ── -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
        <h3 id="libTableTitle" style="margin: 0; font-size: 16px; font-weight: 700; color: var(--text-main);">All Libraries</h3>
        <div class="lib-search-box">
            <span class="material-symbols-rounded">search</span>
            <input type="text" id="libSearchInput" placeholder="Search by name..." onkeyup="filterLibraryTable()">
        </div>
    </div>

    <div class="lib-table-wrap">
        <table class="lib-table">
            <thead>
                <tr>
                    <th>Library Name</th>
                    <th id="classificationHeader">Classification</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody id="libTableBody">
                <tr id="noResultsRow" style="display: none;">
                    <td colspan="3" style="text-align: center; padding: 48px; color: var(--text-muted); background: #fff;">
                        <span class="material-symbols-rounded" style="font-size: 32px; display: block; margin-bottom: 8px; opacity: 0.5;">search_off</span>
                        No libraries found for this category.
                    </td>
                </tr>
            <?php if (count($all_games) > 0): ?>
                <?php foreach ($all_games as $g): ?>
                    <tr data-category="ByteGuess">
                        <td><div class="col-name"><?= htmlspecialchars($g['cg_name']) ?></div></td>
                        <td>
                            <div class="col-class">
                                <span class="class-badge badge-teal">ByteGuess</span>
                            </div>
                        </td>
                        <td>
                            <div class="col-actions">
                                <a href="library/view_game.php?cg_id=<?= $g['cg_id'] ?>" class="act-link">View</a>
                                <label class="toggle-switch">
                                    <input type="checkbox" onchange="toggleStatus(<?= $g['cg_id'] ?>, this.checked)" <?= $g['cg_status'] == 1 ? 'checked' : '' ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if (count($all_digisim) > 0): ?>
                <?php foreach ($all_digisim as $d): ?>
                    <tr data-category="DigiSim">
                        <td><div class="col-name"><?= htmlspecialchars($d['di_name']) ?></div></td>
                        <td>
                            <div class="col-class">
                                <span class="class-badge badge-slate">DigiSim</span>
                            </div>
                        </td>
                        <td>
                            <div class="col-actions">
                                <a href="digisim/admin/manual/manual_page_container.php?digisim_id=<?=$d['di_id']?>" class="act-link" >View</a>
                                <label class="toggle-switch">
                                    <input type="checkbox" disabled <?= $d['di_status'] == 1 ? 'checked' : '' ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (count($all_riskhop) > 0): ?>
                <?php foreach ($all_riskhop as $r): ?>
                    <tr data-category="RiskHOP">
                        <td><div class="col-name"><?= htmlspecialchars($r['game_name']) ?></div></td>
                        <td>
                            <div class="col-class">
                                <span class="class-badge badge-slate">RiskHOP</span>
                            </div>
                        </td>
                        <td>
                            <div class="col-actions">
                                <a href="#" class="act-link" >View</a>
                                <label class="toggle-switch">
                                    <input type="checkbox" disabled <?= $r['status'] == 'published' ? 'checked' : '' ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (count($all_multistage) > 0): ?>
                <?php foreach ($all_multistage as $ms): ?>
                    <tr data-category="MultiStage">
                        <td><div class="col-name"><?= htmlspecialchars($ms['ms_name']) ?></div></td>
                        <td>
                            <div class="col-class">
                                <span class="class-badge badge-multistage">MultiStage</span>
                            </div>
                        </td>
                        <td>
                            <div class="col-actions">
                                <a href="digisim/admin/multistage/multistage_success.php?ms_id=<?= $ms['ms_id'] ?>" class="act-link">View</a>
                                <label class="toggle-switch">
                                    <input type="checkbox" disabled <?= $ms['ms_status'] == 1 ? 'checked' : '' ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (count($all_games) === 0 && count($all_digisim) === 0 && count($all_riskhop) === 0 && count($all_multistage) === 0): ?>
                <tr>
                    <td colspan="3">
                        <div class="lib-empty">
                            No libraries created yet. Click <a href="/trainergenie/create_library.php">Create New Library</a> to get started.
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    </div> <!-- /lib-section -->
</div> <!-- /lib-page -->

<script>
document.getElementById('viewAllBtn').addEventListener('click', function() {
    const extraCards = document.querySelectorAll('.extra-card');
    const isHidden = (extraCards[0].style.display === 'none' || extraCards[0].style.display === '');
    
    extraCards.forEach(card => {
        card.style.display = isHidden ? 'flex' : 'none';
    });
    
    this.innerText = isHidden ? 'View Less ↑' : 'View All Classes ↓';
    
    // Also reset all filters when clicking this toggle
    filterByCategory('All', null);
});

function toggleStatus(cgId, isChecked) {
    const status = isChecked ? 1 : 0;
    
    fetch('api/update_library_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `cg_id=${cgId}&status=${status}`
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert('Failed to update status. Please try again.');
            // Revert state if failed
            const checkbox = document.querySelector(`input[onchange="toggleStatus(${cgId}, this.checked)"]`);
            if (checkbox) checkbox.checked = !isChecked;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the status.');
    });
}

let currentCat = 'All';
let currentSearch = '';

function filterLibraryTable() {
    const rows = document.querySelectorAll('#libTableBody tr:not(#noResultsRow)');
    let visibleCount = 0;
    
    currentSearch = document.getElementById('libSearchInput').value.toLowerCase();

    rows.forEach(row => {
        // Skip placeholders
        if (row.querySelector('.lib-empty')) return;
        
        const rowCat = row.getAttribute('data-category');
        const rowName = row.querySelector('.col-name').innerText.toLowerCase();
        
        const catMatch = (currentCat === 'All' || rowCat === currentCat);
        const searchMatch = rowName.includes(currentSearch);
        
        if (catMatch && searchMatch) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    // Show/hide no results row
    const noResultsRow = document.getElementById('noResultsRow');
    if (noResultsRow) {
        if (visibleCount === 0) {
            const msg = currentSearch ? 'No libraries match your search.' : `No libraries found for this category.`;
            noResultsRow.querySelector('td').innerHTML = `
                <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <span class="material-symbols-rounded" style="font-size: 32px; opacity: 0.5;">search_off</span>
                    <span>${msg}</span>
                </div>
            `;
            noResultsRow.style.display = '';
        } else {
            noResultsRow.style.display = 'none';
        }
    }
}

function filterByCategory(cat, element) {
    const cards = document.querySelectorAll('.stat-card');
    const tableTitle = document.getElementById('libTableTitle');
    const tableWrap  = document.querySelector('.lib-table-wrap');

    // Toggle logic: if clicking already active card, reset to 'All'
    if (element && element.classList.contains('active')) {
        currentCat = 'All';
    } else {
        currentCat = cat;
    }

    // Update active class on cards
    cards.forEach(c => c.classList.remove('active'));
    if (currentCat !== 'All' && element) {
        element.classList.add('active');
    }

    // Update title
    if (tableTitle) {
        tableTitle.innerText = currentCat === 'All' ? 'All Libraries' : `${currentCat} Libraries`;
    }

    // Show/hide Classification column — redundant when a single category is selected
    if (tableWrap) {
        if (currentCat === 'All') {
            tableWrap.classList.remove('hide-classification');
        } else {
            tableWrap.classList.add('hide-classification');
        }
    }

    // Run the combined filter
    filterLibraryTable();
}
</script>

<?php //require "layout/footer.php"; ?>