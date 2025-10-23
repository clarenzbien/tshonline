<?php

// Get total counts from tables
$likesCount = $conn->query("SELECT COUNT(*) AS total FROM article_likes")->fetch_assoc()['total'];
$savesCount = $conn->query("SELECT COUNT(*) AS total FROM article_saves")->fetch_assoc()['total'];
$sharesCount = $conn->query("SELECT COUNT(*) AS total FROM article_shares")->fetch_assoc()['total'];
$commentsCount = $conn->query("SELECT COUNT(*) AS total FROM comments")->fetch_assoc()['total'];

$totalNotifications = $likesCount + $sharesCount + $commentsCount + $savesCount;

// If session not set, initialize
if (!isset($_SESSION['last_seen_notifications'])) {
    $_SESSION['last_seen_notifications'] = $totalNotifications;
}

// Calculate unread notifications
$unreadNotifications = $totalNotifications - $_SESSION['last_seen_notifications'];
if ($unreadNotifications < 0) $unreadNotifications = 0;
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <img src="../assets/images/white@3x.png" alt="Logo" class="sidebar-logo">
    </div>

    <ul class="sidebar-menu">
        <!-- Home is always visible -->
        <li>
            <a href="../maindashboard.php" class="<?= $current_page == 'maindashboard.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-home"></i> Home
            </a>
        </li>

        <?php
// Hide buttons only if user is admin AND role is Staff
if (!(isset($_SESSION['is_admin'], $_SESSION['role']) && $_SESSION['is_admin'] == 1 && $_SESSION['role'] === 'staff')) :
?>
    <li>
        <a href="admin-dashboard.php" class="<?= $current_page == 'admin-dashboard.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-line"></i> Analytics
        </a>
    </li>
    <li class="dropdown">
        <a href="#" class="dropdown-toggle <?= in_array($current_page, ['admin-articles.php', 'admin-articles-published.php', 'admin-articles-draft.php', 'admin-articles-unpublished.php']) ? 'active' : '' ?>">
            <i class="fa-solid fa-newspaper"></i> Articles <i class="fa-solid fa-caret-down"></i>
        </a>
        <ul class="dropdown-menu">
            <li><a href="admin-articles.php" class="<?= $current_page == 'admin-articles.php' ? 'active' : '' ?>">Published</a></li>
            <li><a href="admin-drafts.php" class="<?= $current_page == 'admin-articles-draft.php' ? 'active' : '' ?>">Draft</a></li>
            <li><a href="admin-unpublish.php" class="<?= $current_page == 'admin-articles-unpublished.php' ? 'active' : '' ?>">Unpublished</a></li>
        </ul>
    </li>
     <li>
        <a href="admin-issues.php" class="<?= $current_page == 'admin-issues.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-book"></i> Issues
        </a>
    </li>
    <li>
        <a href="../admin/admin-notifications.php" class="<?= $current_page == 'admin-notifications.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-bell"></i> Notifications
            <?php if ($unreadNotifications > 0): ?>
                <span class="notification-badge"><?= $unreadNotifications ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li>
        <a href="../admin/admin_accounts.php" class="<?= $current_page == 'accounts.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-user"></i> Accounts
        </a>
    </li>
<?php endif; ?>

    </ul>

    <ul class="sidebar-menu logout-menu">
        <li>
            <a href="../account.php" class="sign-out">
                <i class="fa-solid fa-right-from-bracket"></i> Sign Out
            </a>
        </li>
    </ul>
</aside>

<script>
document.querySelectorAll('.dropdown-toggle').forEach(item => {
    item.addEventListener('click', e => {
        e.preventDefault();
        const parent = item.closest('.dropdown');
        parent.classList.toggle('open');
    });
});
</script>




<!-- still shows other buttons kahit admin lang -->