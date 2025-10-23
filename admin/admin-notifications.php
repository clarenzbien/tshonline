<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tshonline";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Reset notification count when viewing the page
$likesCount = $conn->query("SELECT COUNT(*) AS total FROM article_likes")->fetch_assoc()['total'];
$savesCount = $conn->query("SELECT COUNT(*) AS total FROM article_saves")->fetch_assoc()['total'];
$sharesCount = $conn->query("SELECT COUNT(*) AS total FROM article_shares")->fetch_assoc()['total'];
$commentsCount = $conn->query("SELECT COUNT(*) AS total FROM comments")->fetch_assoc()['total'];

$totalNotifications = $likesCount + $sharesCount + $commentsCount + $savesCount;
$_SESSION['last_seen_notifications'] = $totalNotifications;


// Fetch likes, saves, shares, comments as unified notifications
$sql = "
    SELECT l.id, u.name, u.profile_pic, a.id AS article_id, a.title, 'starred' AS action, l.created_at AS action_date, NULL AS comment_text
    FROM article_likes l
    JOIN users u ON l.user_id = u.id
    JOIN articles a ON l.article_id = a.id

    UNION ALL

    SELECT s.id, u.name, u.profile_pic, a.id AS article_id, a.title, 'saved' AS action, s.saved_at AS action_date, NULL AS comment_text
    FROM article_saves s
    JOIN users u ON s.user_id = u.id
    JOIN articles a ON s.article_id = a.id

    UNION ALL

    SELECT sh.id, u.name, u.profile_pic, a.id AS article_id, a.title, 'shared' AS action, sh.created_at AS action_date, NULL AS comment_text
    FROM article_shares sh
    JOIN users u ON sh.user_id = u.id
    JOIN articles a ON sh.article_id = a.id

    UNION ALL

    SELECT c.id, u.name, u.profile_pic, a.id AS article_id, a.title, 'commented' AS action, c.created_at AS action_date, c.comment_text
    FROM comments c
    JOIN users u ON c.user_id = u.id
    JOIN articles a ON c.article_id = a.id

    ORDER BY action_date DESC
";


$notificationsResult = $conn->query($sql);

// Combine similar actions (same user + article)
$combined = [];

if ($notificationsResult->num_rows > 0) {
    while ($row = $notificationsResult->fetch_assoc()) {
        $key = $row['name'] . '|' . $row['title']; // group by user + article

        if (!isset($combined[$key])) {
            $combined[$key] = [
                'name' => $row['name'],
                'profile_pic' => $row['profile_pic'],
                'title' => $row['title'],
                'article_id' => $row['article_id'], // <-- add this
                'actions' => [],
                'comment_text' => null,
                'latest_date' => $row['action_date']
            ];
        }

        // Add the action (liked, saved, shared, commented)
        $combined[$key]['actions'][] = $row['action'];

        // If commented, store comment text
        if ($row['action'] === 'commented' && !empty($row['comment_text'])) {
            $combined[$key]['comment_text'] = $row['comment_text'];
        }

        // Track latest activity time
        if (strtotime($row['action_date']) > strtotime($combined[$key]['latest_date'])) {
            $combined[$key]['latest_date'] = $row['action_date'];
        }
    }
}

?>

<?php
function str_replace_last($search, $replace, $subject) {
    $pos = strrpos($subject, $search);
    if ($pos !== false) {
        $subject = substr_replace($subject, $replace, $pos, strlen($search));
    }
    return $subject;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Notifications</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../adminstyles/admin-style.css">
<link rel="stylesheet" href="../adminstyles/admin-articles.css">
<link rel="stylesheet" href="../adminstyles/admin-notification.css">
</head>
<body>
    <button id="hamburgerBtn" class="hamburger-btn">
        <i class="fa-solid fa-bars"></i>
    </button>
<div class="dashboard-container">
    <?php include 'sidebar.php'; ?>

    
    <main class="main-content">
        <div class="articles-header">
            <h1>Notifications</h1>
        </div>

        <div>
            <?php if (!empty($combined)): ?>
                <?php foreach ($combined as $notif): ?>
                    <?php
                        $actions = $notif['actions'];
                        $actionsText = implode(', ', array_unique($actions)); // avoid duplicates
                        $actionsText = str_replace_last(',', ' and', $actionsText); // clean last comma
                    ?>
                    <div class="notification-item">
                        <img src="../<?php echo htmlspecialchars($notif['profile_pic']); ?>" alt="User">
                        <div class="notification-text">
                            <span class="name"><?php echo htmlspecialchars($notif['name']); ?></span>
                            <span class="action"><?php echo htmlspecialchars($actionsText); ?></span>
                            <span> the article </span>
                            <span class="title">
                                <a href="../article.php?id=<?php echo $notif['article_id']; ?>">
                                    <?php echo htmlspecialchars($notif['title']); ?>
                                </a>
                            </span>


                            <?php if(in_array('commented', $actions) && $notif['comment_text']): ?>
                                <span class="comment">"<?php echo htmlspecialchars(substr($notif['comment_text'], 0, 50)); ?><?php echo strlen($notif['comment_text']) > 50 ? '...' : ''; ?>"</span>
                            <?php endif; ?>
                            <span class="date"><?php echo date('M d, Y h:i A', strtotime($notif['latest_date'])); ?></span>
                        </div>
                        </div>

                <?php endforeach; ?>
            <?php else: ?>
                <p>No notifications found.</p>
            <?php endif; ?>

        </div>
    </main>
</div>
</body>

<script>
const hamburgerBtn = document.getElementById('hamburgerBtn');
const sidebar = document.querySelector('.sidebar');

hamburgerBtn.addEventListener('click', () => {
    sidebar.classList.toggle('show');
});

// Optional: close sidebar when clicking outside
document.addEventListener('click', e => {
    if (!sidebar.contains(e.target) && !hamburgerBtn.contains(e.target)) {
        sidebar.classList.remove('show');
    }
});
</script>
</html>


<!-- timestamppp okay na ang notifications numberr -->