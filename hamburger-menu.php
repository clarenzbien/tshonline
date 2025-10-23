<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentCategory = isset($_GET['category']) ? $_GET['category'] : ''; 

$conn = new mysqli("localhost", "root", "", "tshonline");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hamburger Menu</title>
<link rel="stylesheet" href="./styles/hamburger.css" />
</head>
<body>

<!-- Hamburger -->
<div id="hamburger" aria-label="Open menu" role="button">
  <span class="bar"></span>
  <span class="bar"></span>
  <span class="bar"></span>
</div>

<!-- Overlay -->
<div id="overlay" aria-hidden="true"></div>

<!-- Sidebar -->
<nav id="mySidebar" aria-hidden="true">
  <div class="sidebar-top">
    <a href="maindashboard.php" class="logoH logo-link"></a>
    <button id="closeBtn" aria-label="Close menu">&times;</button>
  </div>

  <!-- Sidebar links -->
  <div class="sidebar-links">
    <?php 
    // Admin / Staff links
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $sql = "SELECT is_admin, role FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if ($row['is_admin'] == 1 && $row['role'] === 'Executive') {
                echo '<a href="./admin/admin-dashboard.php" class="admin-btn">Admin Dashboard</a>';
            } elseif ($row['is_admin'] == 1 && $row['role'] === 'Staff') {
                echo '<a href="./staff/staff_add_article.php" class="admin-btn">Add Article</a>';
            }
        }
        $stmt->close();
    }

    // Dynamic categories in custom order
    $customOrder = ['NEWS','EDITORIAL','OPINION','FEATURE','LITERARY','DEVCOMM','SPORTS','ENTERTAINMENT'];

    $catResult = $conn->query("SELECT name FROM categories");
    $categories = [];
    if ($catResult && $catResult->num_rows > 0) {
        while ($cat = $catResult->fetch_assoc()) {
            $categories[] = $cat['name'];
        }
    }

    // Display categories according to custom order
    foreach ($customOrder as $catName) {
        if (in_array($catName, $categories)) {
            $activeClass = ($currentCategory === $catName) ? 'active' : '';
            echo "<a href='category.php?category=" . urlencode($catName) . "' class='$activeClass'>" . htmlspecialchars($catName) . "</a>";
        }
    }
    ?>
  </div>

  <!-- Account button (bottom) -->
  <a href="account.php" class="account">Account</a>
</nav>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const hamburger = document.getElementById('hamburger');
  const sidebar = document.getElementById('mySidebar');
  const closeBtn = document.getElementById('closeBtn');
  const overlay = document.getElementById('overlay');

  function openSidebar() {
    sidebar.classList.add('open');
    overlay.classList.add('visible');
    hamburger.style.display = 'none';
  }

  function closeSidebar() {
    sidebar.classList.remove('open');
    overlay.classList.remove('visible');
    hamburger.style.display = 'inline-block';
  }

  hamburger.addEventListener('click', openSidebar);
  closeBtn.addEventListener('click', closeSidebar);
  overlay.addEventListener('click', closeSidebar);
});
</script>
</body>
</html>
