<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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

// Fetch all authors by position_title
$query = "
    SELECT id, name, position_title, email, profile_pic 
    FROM users 
    WHERE position_title IN (
        'Editor-in-Chief',
        'Associate Editor',
        'Managing Editor-Print',
        'Managing Editor-Online',
        'Circulations Head',
        'News Editor',
        'Features/ Literary Editor',
        'DevComm Editor',
        'Sports Editor',
        'Social Media Manager',
        'Head Photojournalist',
        'Head Layout Artist',
        'Head Cartoonist',
        'Head Graphics Artist',
        'Publication Staff',
        'Junior Herald'
    )
";
$result = $conn->query($query);

// Define hierarchy order
$executiveOrder = [
    'Managing Editor-Online',
    'Managing Editor-Print',
    'Editor-in-Chief',
    'Associate Editor',
    'Circulations Head'
];

$sectionOrder = [
    'News Editor',
    'Features/ Literary Editor',
    'DevComm Editor',
    'Sports Editor',
    'Social Media Manager',
    'Head Photojournalist',
    'Head Layout Artist',
    'Head Cartoonist',
    'Head Graphics Artist'
];

$staffOrder = [
    'Publication Staff',
    'Junior Herald'
];

// Sort into groups
$executives = [];
$sectionHeads = [];
$staffs = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if (in_array($row['position_title'], $executiveOrder)) {
            $executives[$row['position_title']] = $row;
        } elseif (in_array($row['position_title'], $sectionOrder)) {
            $sectionHeads[$row['position_title']] = $row;
        } elseif (in_array($row['position_title'], $staffOrder)) {
            $staffs[$row['position_title']] = $row;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Editorial Board</title>
<link rel="stylesheet" href="./styles/footer.css">
<link rel="stylesheet" href="./styles/article.css">
<link rel="stylesheet" href="./styles/editorialboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>

<body>

<div class="main-container">
    <div class="left-icon" onclick="openSidebar()">
        <?php include 'hamburger-menu.php'; ?>
    </div>
</div>

<div class="content-wrapper">
    <h1>EDITORIAL BOARD</h1>

    <!-- EXECUTIVES -->
    <?php if (!empty($executives)): ?>
        <h2 class="section-title">EXECUTIVES</h2>
        <div class="authors-container">
            <?php foreach ($executiveOrder as $pos): ?>
                <?php if (!empty($executives[$pos])): ?>
                    <?php $author = $executives[$pos]; ?>
                    <a href="author.php?id=<?php echo $author['id']; ?>" class="author-card-link">
                        <div class="author-card">
                            <img src="<?php echo htmlspecialchars($author['profile_pic']); ?>" alt="<?php echo htmlspecialchars($author['name']); ?>" class="author-pic">
                            <div class="author-name"><?php echo htmlspecialchars($author['name']); ?></div>
                            <div class="author-position"><?php echo htmlspecialchars($author['position_title']); ?></div>
                            <div class="author-email"><?php echo htmlspecialchars($author['email']); ?></div>
                        </div>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- SECTION HEADS -->
    <?php if (!empty($sectionHeads)): ?>
        <h2 class="section-title">SECTION HEADS</h2>
        <div class="authors-container">
            <?php foreach ($sectionOrder as $pos): ?>
                <?php if (!empty($sectionHeads[$pos])): ?>
                    <?php $author = $sectionHeads[$pos]; ?>
                    <a href="author.php?id=<?php echo $author['id']; ?>" class="author-card-link">
                        <div class="author-card">
                            <img src="<?php echo htmlspecialchars($author['profile_pic']); ?>" alt="<?php echo htmlspecialchars($author['name']); ?>" class="author-pic">
                            <div class="author-name"><?php echo htmlspecialchars($author['name']); ?></div>
                            <div class="author-position"><?php echo htmlspecialchars($author['position_title']); ?></div>
                            <div class="author-email"><?php echo htmlspecialchars($author['email']); ?></div>
                        </div>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- STAFF -->
    <?php if (!empty($staffs)): ?>
        <h2 class="section-title">STAFF</h2>
        <div class="authors-container">
            <?php foreach ($staffOrder as $pos): ?>
                <?php if (!empty($staffs[$pos])): ?>
                    <?php $author = $staffs[$pos]; ?>
                    <a href="author.php?id=<?php echo $author['id']; ?>" class="author-card-link">
                        <div class="author-card">
                            <img src="<?php echo htmlspecialchars($author['profile_pic']); ?>" alt="<?php echo htmlspecialchars($author['name']); ?>" class="author-pic">
                            <div class="author-name"><?php echo htmlspecialchars($author['name']); ?></div>
                            <div class="author-position"><?php echo htmlspecialchars($author['position_title']); ?></div>
                            <div class="author-email"><?php echo htmlspecialchars($author['email']); ?></div>
                        </div>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($executives) && empty($sectionHeads) && empty($staffs)): ?>
        <p>No authors found.</p>
    <?php endif; ?>
</div>


<?php include 'footer.php'; ?>

</body>
</html>
