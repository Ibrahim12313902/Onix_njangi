<?php
// Get current page for active menu
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

function isActive($page, $dir = '') {
    global $current_page, $current_dir;
    if (!empty($dir)) {
        return ($current_dir == $dir) ? 'active' : '';
    }
    return ($current_page == $page) ? 'active' : '';
}
?>
<div class="sidebar">
    <div class="sidebar-header">
        <h3><i class="fas fa-bars"></i> ONIX Navigation</h3>
    </div>
    
    <nav class="sidebar-menu">
        <ul>
            <li class="menu-label">MAIN MENU</li>
            <li>
                <a href="<?php echo SITE_URL; ?>dashboard.php" class="<?php echo isActive('dashboard.php'); ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            
            <li>
                <a href="<?php echo SITE_URL; ?>members/index.php" 
                   class="<?php echo isActive('', 'members'); ?>">
                    <i class="fas fa-users"></i> Members
                </a>
            </li>
            
            <li>
                <a href="<?php echo SITE_URL; ?>member_types/index.php" 
                   class="<?php echo isActive('', 'member_types'); ?>">
                    <i class="fas fa-user-tag"></i> Member Type
                </a>
            </li>
            
            <li>
                <a href="<?php echo SITE_URL; ?>hand_types/index.php" 
                   class="<?php echo isActive('', 'hand_types'); ?>">
                    <i class="fas fa-hand-holding"></i> Hand Types
                </a>
            </li>
            
            <li>
                <a href="<?php echo SITE_URL; ?>hands/index.php" 
                   class="<?php echo isActive('', 'hands'); ?>">
                    <i class="fas fa-hands-helping"></i> Hands
                </a>
            </li>
            
            <li>
                <a href="<?php echo SITE_URL; ?>hand_requests/index.php" 
                   class="<?php echo isActive('', 'hand_requests'); ?>">
                    <i class="fas fa-clipboard-list"></i> Hand Requests
                </a>
            </li>
            
            <li>
                <a href="<?php echo SITE_URL; ?>hand_status/index.php" 
                   class="<?php echo isActive('', 'hand_status'); ?>">
                    <i class="fas fa-clipboard-check"></i> Hand Status
                </a>
            </li>
            
            <li>
                <a href="<?php echo SITE_URL; ?>payout_cycles/index.php" 
                   class="<?php echo isActive('', 'payout_cycles'); ?>">
                    <i class="fas fa-calendar-alt"></i> Payout Cycles
                </a>
            </li>
            
            <li>
                <a href="<?php echo SITE_URL; ?>payout_management/index.php" 
                   class="<?php echo isActive('', 'payout_management'); ?>">
                    <i class="fas fa-gift"></i> Payout Management
                </a>
            </li>
            
            <li>
                <a href="<?php echo SITE_URL; ?>payment_proofs/index.php" 
                   class="<?php echo isActive('', 'payment_proofs'); ?>">
                    <i class="fas fa-file-invoice"></i> Payment Proofs
                </a>
            </li>
            
            <li>
                <a href="<?php echo SITE_URL; ?>announcements/index.php" 
                   class="<?php echo isActive('', 'announcements'); ?>">
                    <i class="fas fa-bullhorn"></i> Announcements
                </a>
            </li>
            
            <li>
                <a href="<?php echo SITE_URL; ?>group_chat/index.php" 
                   class="<?php echo isActive('', 'group_chat'); ?>">
                    <i class="fas fa-comments"></i> Group Chat
                </a>
            </li>
            
            <li class="menu-label">MEMBER PORTAL</li>
            <li>
                <a href="<?php echo SITE_URL; ?>member_portal/" target="_blank">
                    <i class="fas fa-external-link-alt"></i> Open Member Portal
                </a>
            </li>
            
            <li class="menu-label">SAVED RECORDS</li>
<li>
    <a href="<?php echo SITE_URL; ?>saved_records/index.php" 
       class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/saved_records/') !== false && !strpos($_SERVER['REQUEST_URI'], 'current_month') && !strpos($_SERVER['REQUEST_URI'], 'last_quarter') && !strpos($_SERVER['REQUEST_URI'], 'achievements') && !strpos($_SERVER['REQUEST_URI'], 'piggie_box')) ? 'active' : ''; ?>">
        <i class="fas fa-save"></i> All Saved Records
    </a>
</li>
<li>
    <a href="<?php echo SITE_URL; ?>saved_records/current_month.php"
       class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'current_month') !== false) ? 'active' : ''; ?>">
        <i class="fas fa-calendar-alt"></i> Current Month
    </a>
</li>
<li>
    <a href="<?php echo SITE_URL; ?>saved_records/last_quarter.php"
       class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'last_quarter') !== false) ? 'active' : ''; ?>">
        <i class="fas fa-chart-line"></i> Last Quarter
    </a>
</li>
<li>
    <a href="<?php echo SITE_URL; ?>saved_records/achievements.php"
       class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'achievements') !== false) ? 'active' : ''; ?>">
        <i class="fas fa-trophy"></i> Achievement
    </a>
</li>
<li>
    <a href="<?php echo SITE_URL; ?>saved_records/piggie_box.php"
       class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'piggie_box') !== false) ? 'active' : ''; ?>">
        <i class="fas fa-piggy-bank"></i> Piggie Box
    </a>
</li>
            
            <li class="menu-label">SYSTEM</li>
            <!-- <li>
                <a href="<?php echo SITE_URL; ?>settings.php" class="<?php echo isActive('settings.php'); ?>">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </li> -->
            <li>
    <a href="<?php echo SITE_URL; ?>settings/index.php" 
       class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/settings/') !== false) ? 'active' : ''; ?>">
        <i class="fas fa-cog"></i> Settings
    </a>
</li>
            <li>
                <a href="<?php echo SITE_URL; ?>logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <p>© <?php echo date('Y'); ?> ONIX</p>
        <p class="version">Version 1.0</p>
    </div>
</div>