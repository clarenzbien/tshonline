<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tshonline";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM articles ORDER BY date_posted DESC";
$result = $conn->query($sql);
?>

<div class="recents">
<?php
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo '
        <div class="recent-article">
            <div class="article-details">
                <div class="newsbutton"><a href="#" class="news-button">'.htmlspecialchars($row["category"]).'</a></div>
                <span class="title">'.htmlspecialchars($row["title"]).'</span>
                <span class="author">By '.htmlspecialchars($row["author"]).'</span>
                <span class="date">'.date("F d, Y", strtotime($row["date_posted"])).'</span>
                <div class="readhere-btn"><a href="'.htmlspecialchars($row["body"]).'" class="readhere-btnn">READ HERE</a></div>
            </div>
            <div class="gradient-overlay"></div>
            <div class="bgpic" style="background-image: url('.htmlspecialchars($row["image_url"]).');"></div>
        </div>';
    }
} else {
    echo "<p>No articles found.</p>";
}
$conn->close();
?>
</div>
