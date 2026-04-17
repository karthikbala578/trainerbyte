<div class="admin-sidebar">
    <ul class="sidebar-menu">
        <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
            <a href="<?php echo ADMIN_URL; ?>index.php">
                <span class="icon">📚</span>
                <span>Library</span>
            </a>
        </li>
        <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'create_game.php' ? 'active' : ''; ?>">
            <a href="<?php echo ADMIN_URL; ?>create_game.php">
                <span class="icon">➕</span>
                <span>Create Game</span>
            </a>
        </li>
        <li>
            <a href="#" onclick="alert('Analytics feature coming soon!'); return false;">
                <span class="icon">📊</span>
                <span>Analytics</span>
            </a>
        </li>
        <li>
            <a href="#" onclick="showAdminSettings(); return false;">
                <span class="icon">⚙️</span>
                <span>Settings</span>
            </a>
        </li>
    </ul>
    
    <!-- Logout at Bottom -->
    <div class="sidebar-logout">
        <ul class="sidebar-menu" style="margin: 0;">
            <li>
                <a href="<?php echo ADMIN_URL; ?>logout.php" style="color: #ef4444;">
                    <span class="icon">🚪</span>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
</div>

<!-- Admin Settings Modal -->
<div id="adminSettingsModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="font-size: 22px;">Admin Settings</h3>
            <span class="modal-close" onclick="closeAdminSettings()">&times;</span>
        </div>
        <div class="modal-body">
            <div class="settings-info">
                <div class="info-row" style="display:flex;justify-content:space-between;padding:15px 0;border-bottom:1px solid var(--border-color);">
                    <label style="font-weight:600;font-size:16px;">Username:</label>
                    <span style="font-size:16px;"><?php echo $_SESSION['admin_username']; ?></span>
                </div>
                <div class="info-row" style="display:flex;justify-content:space-between;padding:15px 0;">
                    <label style="font-weight:600;font-size:16px;">Email:</label>
                    <span style="font-size:16px;"><?php echo $_SESSION['admin_email']; ?></span>
                </div>
            </div>
            <hr style="margin:25px 0;">
            <h4 style="font-size:20px;margin-bottom:20px;">Change Password</h4>
            <form id="changePasswordForm">
                <div class="form-group">
                    <label style="font-size:16px;">Current Password</label>
                    <input type="password" name="current_password" required style="font-size:16px;padding:12px;">
                </div>
                <div class="form-group">
                    <label style="font-size:16px;">New Password</label>
                    <input type="password" name="new_password" required style="font-size:16px;padding:12px;">
                </div>
                <div class="form-group">
                    <label style="font-size:16px;">Confirm Password</label>
                    <input type="password" name="confirm_password" required style="font-size:16px;padding:12px;">
                </div>
                <button type="submit" class="btn btn-primary">Update Password</button>
            </form>
        </div>
    </div>
</div>

<script>
function showAdminSettings() {
    document.getElementById('adminSettingsModal').style.display = 'flex';
}

function closeAdminSettings() {
    document.getElementById('adminSettingsModal').style.display = 'none';
}
</script>