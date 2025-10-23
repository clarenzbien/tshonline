<?php
$servername = "localhost";
$username = "root";   
$password = "";       
$dbname = "tshonline";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch articles ordered by newest first
$sql = "SELECT * FROM articles WHERE status = 'published' ORDER BY date_posted DESC";
$result = $conn->query($sql);

$articlesByCategory = [];
$recentArticles = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $articlesByCategory[$row['category']][] = $row; // group by category
        $recentArticles[] = $row; // store recents
    }
}
$conn->close();


$conn2 = new mysqli($servername, $username, $password, $dbname);
if ($conn2->connect_error) {
    die("Connection failed: " . $conn2->connect_error);
}

// Fetch most read articles
$mostReadArticles = [];
$allArticlesRes = $conn2->query("SELECT * FROM articles WHERE status = 'published' ORDER BY views DESC");
if ($allArticlesRes->num_rows > 0) {
    while($row = $allArticlesRes->fetch_assoc()) {
        $mostReadArticles[] = $row;
    }
}
$mostReadArticles = array_slice($mostReadArticles, 0, 6); // top 6

$conn2->close();


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

if (isset($_POST['send_feedback'])) {
    $user_email = $_POST['user_email'];
    $user_message = $_POST['user_message'];

    $mail = new PHPMailer(true);

    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'clba.magalong.up@phinmaed.com'; // your Gmail address
        $mail->Password = 'xbge kail ttls brov';   // your Gmail App Password (not normal password)
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Email setup
        $mail->setFrom($user_email, 'Website Feedback');
        $mail->addAddress('clarenzmagalong@gmail.com');
        $mail->Subject = 'New Feedback from TSHonline';
        $mail->Body = "From: $user_email\n\nMessage:\n$user_message";

        $mail->send();
        echo "<script>alert('Thank you for your feedback!');</script>";
    } catch (Exception $e) {
        echo "<script>alert('Sorry, something went wrong: {$mail->ErrorInfo}');</script>";
    }
}


?>




<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>TSH</title>
    <link rel="stylesheet" href="./styles/style.css" />
  </head>
  <body>
    
    <div class="main-container">
        <div class="left-icon" onclick="openSidebar()">
            <?php include 'hamburger-menu.php'; ?>
        </div>
        <div class="search-container">
        <input 
            type="text" 
            id="searchInput" 
            placeholder="Search articles..." 
        />
        <img src="./assets/images/search.svg" alt="Search" class="search-icon" />
    </div>
</div>

    

<div class="content-wrapper" style="margin-top: 80px;">
    
    <p id="noArticlesMsg" style="display:none; text-align:center; margin-top:20px; color:black; font-weight:bold;">
        No articles found.
    </p>

    <div class="recents-wrapper" style="position: relative;">
        <!-- Left Button -->
        <button class="scroll-btn left" onclick="scrollRecents('left')">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="white">
            <path d="M15.41 7.41 14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
        </svg>
        </button>

        <!-- Right Button -->
        <button class="scroll-btn right" onclick="scrollRecents('right')">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="white">
            <path d="M8.59 16.59 13.17 12 8.59 7.41 10 6l6 6-6 6z"/>
        </svg>
        </button>

    

    <div class="recents">
      <?php
      if (!empty($recentArticles)) {
          foreach($recentArticles as $row) {
              ?>
              <div class="recent-article">
                  <div class="article-details">
                      <div class="catbutton">
                        <?php
                            // Map categories to their respective files
                            $categoryPages = [
                                'NEWS' => 'Cnews-categorypage.php',
                                'FEATURE' => 'Cfeature-categorypage.php',
                                'OPINION' => 'Copinion-categorypage.php',
                                'EDITORIAL' => 'Ceditorial-categorypage.php',
                                'LITERARY' => 'Cliterary-categorypage.php',
                                'DEVCOMM' => 'Cdevcomm-categorypage.php',
                                'SPORTS' => 'Csports-categorypage.php',
                                // Add more categories if needed
                            ];

                            $pageLink = isset($categoryPages[$row['category']]) ? $categoryPages[$row['category']] : 'category.php';
                        ?>
                       <a href="category.php?category=<?php echo urlencode($row['category']); ?>" class="cat-button">
                            <?php echo htmlspecialchars($row['category']); ?>
                        </a>

                    </div>

                      <span class="title"><?php echo htmlspecialchars($row['title']); ?></span>
                      <span class="author">By <?php echo htmlspecialchars($row['author']); ?></span>
                      <span class="date"><?php echo date("F d, Y", strtotime($row['date_posted'])); ?></span>
                      <div class="readhere-btn">
                            <a href="article.php?id=<?php echo $row['id']; ?>" class="readhere-btnn">READ HERE</a>
                        </div>

                  </div>
                  <div class="gradient-overlay"></div>
                  <div class="bgpic" style="background-image: url('<?php echo htmlspecialchars($row['image_url']); ?>');"></div>
              </div>
              <?php
          }
      } else {
          echo "<p>No articles found.</p>";
      }
      ?>
      
      </div>
    </div>

<div class="articles-main-wrapper">
    <div class="section-container most-read-section">
        <div class="section-articles">
            <div class="section-header">
                <div class="sectionM"><span class="text-m">MOST READ ARTICLES</span></div>
             </div>
        </div>

        <div class="most-read-wrapper" style="position: relative;">
            <!-- Left Button -->
            <button class="scroll-btn left" onclick="scrollMostRead('left')">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="white">
                    <path d="M15.41 7.41 14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
                </svg>
            </button>

            <!-- Right Button -->
            <button class="scroll-btn right" onclick="scrollMostRead('right')">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="white">
                    <path d="M8.59 16.59 13.17 12 8.59 7.41 10 6l6 6-6 6z"/>
                </svg>
            </button>

            <div class="article-container most-read">
                <?php foreach($mostReadArticles as $row): ?>
                    <div class="article-img" style="background-image: url('<?php echo htmlspecialchars($row['image_url']); ?>');">
                        <div class="article-text">
                            <a class="title1" href="article.php?id=<?php echo $row['id']; ?>">
                                <?php echo htmlspecialchars($row['title']); ?>
                            </a>
                            <span class="date1"><?php echo date("F d, Y", strtotime($row['date_posted'])); ?></span>
                            <span class="author1">By <?php echo htmlspecialchars($row['author']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>





    <div class="article-wrapper">
        <?php if(!empty($articlesByCategory)): ?>
            <?php foreach($articlesByCategory as $category => $articles): ?>
                <div class="section-container">
                    <!-- Section Header -->
                    <div class="section-articles">
                        <div class="section-header">
                            <div class="sectionH">
                                <span class="text-s"><?php echo htmlspecialchars($category); ?></span>
                            </div>
                            <div class="wrapper">
                                <?php
                                    // Map categories to their respective files
                                    $categoryPages = [
                                        'NEWS' => 'Cnews-categorypage.php',
                                        'FEATURE' => 'Cfeature-categorypage.php',
                                        'OPINION' => 'Copinion-categorypage.php',
                                        'EDITORIAL' => 'Ceditorial-categorypage.php',
                                        'LITERARY' => 'Cliterary-categorypage.php',
                                        'DEVCOMM' => 'Cdevcomm-categorypage.php',
                                        'SPORTS' => 'Csports-categorypage.php',
                                        // Add more categories if needed
                                    ];

                                    $pageLink = isset($categoryPages[$category]) ? $categoryPages[$category] : 'category.php';
                                    ?>
                                    <a href="category.php?category=<?php echo urlencode($category); ?>" class="view-more-btn">VIEW MORE</a>
                            </div>
                        </div>
                    </div>

                    <!-- Articles under this category -->
                    <div class="article-container">
                        <?php 
                        // Take only the first 6 articles for this category
                        $articlesToShow = array_slice($articles, 0, 6); 
                        foreach($articlesToShow as $row): ?>
                            <div class="article-img" style="background-image: url('<?php echo htmlspecialchars($row['image_url']); ?>');">
                                <div class="article-text">
                                    <a class="title1" href="article.php?id=<?php echo $row['id']; ?>">
                                        <?php echo htmlspecialchars($row['title']); ?>
                                    </a>
                                    <span class="date1"><?php echo date("F d, Y", strtotime($row['date_posted'])); ?></span>
                                    <span class="author1">By <?php echo htmlspecialchars($row['author']); ?></span>
                                    
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No articles found.</p>
        <?php endif; ?>
    </div>
</div>






    <?php include 'footer.php'; ?>
</div>

</body>
</html>



<script>
function scrollRecents(direction) {
    const recents = document.querySelector('.recents');
    const article = recents.querySelector('.recent-article');

    if (!article) return; // safety check

    const scrollAmount = article.offsetWidth + parseInt(getComputedStyle(article).marginRight);

    if (direction === 'left') {
        recents.scrollBy({
            left: -scrollAmount,
            behavior: 'smooth'
        });
    } else if (direction === 'right') {
        recents.scrollBy({
            left: scrollAmount,
            behavior: 'smooth'
        });
    }
}

function scrollMostRead(direction) {
    const mostRead = document.querySelector('.most-read');
    const article = mostRead.querySelector('.article-img');

    if (!article) return; // safety check

    const scrollAmount = article.offsetWidth + parseInt(getComputedStyle(article).marginRight);

    if (direction === 'left') {
        mostRead.scrollBy({
            left: -scrollAmount,
            behavior: 'smooth'
        });
    } else if (direction === 'right') {
        mostRead.scrollBy({
            left: scrollAmount,
            behavior: 'smooth'
        });
    }
}

</script>


<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    const allArticles = document.querySelectorAll('.recent-article, .article-img');
    const noArticlesMsg = document.getElementById('noArticlesMsg');

    searchInput.addEventListener('input', () => {
        const query = searchInput.value.toLowerCase().trim();
        let visibleCount = 0;

        allArticles.forEach(article => {
            const titleElem = article.querySelector('.title, .title1');
            const authorElem = article.querySelector('.author, .author1');

            const titleText = titleElem ? titleElem.textContent.toLowerCase() : '';
            const authorText = authorElem ? authorElem.textContent.toLowerCase() : '';

            if (titleText.includes(query) || authorText.includes(query)) {
                article.style.display = '';
                visibleCount++;
            } else {
                article.style.display = 'none';
            }
        });

        noArticlesMsg.style.display = visibleCount === 0 ? 'block' : 'none';
    });
});

</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchContainer = document.querySelector('.search-container');
    const searchInput = document.getElementById('searchInput');

    // Toggle search container when clicked (mobile/tablet)
    searchContainer.addEventListener('click', () => {
        if (window.innerWidth <= 768) {
            searchContainer.classList.toggle('active');
            searchInput.focus(); // automatically focus input
        }
    });

    // Optional: collapse search if user clicks outside
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 768 && !searchContainer.contains(e.target)) {
            searchContainer.classList.remove('active');
        }
    });
});
</script>






<!--timestamp: account na lang