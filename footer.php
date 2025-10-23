<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = new mysqli("localhost", "root", "", "tshonline");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch categories
$customOrder = ['NEWS', 'EDITORIAL', 'OPINION', 'FEATURE', 'LITERARY', 'DEVCOMM', 'SPORTS', 'ENTERTAINMENT'];

$catResult = $conn->query("SELECT name FROM categories");
$categories = [];
if ($catResult && $catResult->num_rows > 0) {
    while ($cat = $catResult->fetch_assoc()) {
        $categories[] = $cat['name'];
    }
}

// Fetch footer publications (latest 3)
$pubResult = $conn->query("SELECT * FROM footer_publications ORDER BY created_at DESC LIMIT 3");
$publications = [];
if ($pubResult && $pubResult->num_rows > 0) {
    while ($pub = $pubResult->fetch_assoc()) {
        $publications[] = $pub;
    }
}

// Reverse array so oldest comes first
$publications = array_reverse($publications);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Footer</title>
  <link rel="stylesheet" href="styles/footer.css" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" />
</head>

<body>
  <!-- Feedback Section -->
<hr class="divider">

<div class="feedback-section">
    <h2>Tell Us What You Think</h2>
    <p>We’d love to hear your thoughts, feedback, or suggestions.</p>

    <form action="" method="POST" class="feedback-form">
        <input 
            type="email" 
            name="user_email" 
            placeholder="Your email" 
            required
        >
        <textarea 
            name="user_message" 
            placeholder="Your message" 
            rows="5" 
            required
        ></textarea>
        <button 
            type="submit" 
            name="send_feedback"
        >
            Send Feedback
        </button>
    </form>
</div>

  <div class="main-containerf">
  <div class="footer">

    <!--<div class="footer-top">-->
     <div class="footer-content">
      <!-- Left column -->
      <div class="footer-left">
       <div class="logo"></div>
       
       <div class="footer-columns">
          <div class="nav-links">
            <a href="maindashboard.php">HOME</a>
            <a href="aboutus.php">COMPANY</a>
            <a href="contactus.php">CONTACT US</a>
            <a href="editorialboard.php">EDITORIAL BOARD</a>
          </div>

          <div class="categories">
            <span>CATEGORIES</span>
            <?php
            foreach ($customOrder as $catName) {
              if (in_array($catName, $categories)) {
                echo "<a href='category.php?category=" . urlencode($catName) . "'>" . htmlspecialchars($catName) . "</a>";
              }
            }
            foreach ($categories as $catName) {
              if (!in_array($catName, $customOrder)) {
                echo "<a href='category.php?category=" . urlencode($catName) . "'>" . htmlspecialchars($catName) . "</a>";
              }
            }
            ?>
          </div>
        </div>
      </div>

      <!-- Right side (Published tabloid/newsletter) -->
      <div class="footer-right">
        <div class="publications">
          <?php foreach ($publications as $pub): ?>
            <a href="<?= htmlspecialchars($pub['link']); ?>" target="_blank" class="pub-item">
              <img src="<?= htmlspecialchars($pub['cover_image']); ?>" alt="<?= htmlspecialchars($pub['title']); ?>">
              <!--<p><?= htmlspecialchars($pub['title']); ?></p>-->
            </a>
          <?php endforeach; ?>
        </div>
        <h3>Recent Published Issues Here</h3>
      </div>
    </div>


    

      
    <!-- Copyright -->
    <div class="footer-bottom">
      &copy; <?= date('Y'); ?> TSH Online. All rights reserved.
    </div>
  </div>
</div>
</body>
</html>
