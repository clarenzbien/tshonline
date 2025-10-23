<?php
session_start();

// Redirect to login page if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
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

$user_id = $_SESSION['user_id'];

// Fetch user data
$sql = "SELECT name, username, email, profile_pic FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Account</title>
    <link rel="stylesheet" href="./styles/account.css" />
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="back-container">
                <a href="maindashboard.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> 
                </a>
            </div>
    <div class="account-box">
        <div class="head">
            <h2><i class="fas fa-cog"></i> SETTINGS</h2>

             <!-- Empty placeholder to balance flex spacing -->
            <div class="spacer"></div>
        </div>

        <!-- Profile Image -->
        <div class="profile-img">
            <img src="<?php echo $user['profile_pic'] ? htmlspecialchars($user['profile_pic']) : 'default-avatar.png'; ?>" 
                 alt="Profile Image">
        </div>

        <!-- User Details -->
        <div class="user-details">
            <p class="fullname"><?php echo htmlspecialchars($user['name']); ?></p>
            <p class="email"><?php echo htmlspecialchars($user['email']); ?></p>
        </div>

        <!-- Options -->
        <div class="account-links">
            <a href="edit_profile.php"><i class="fas fa-pen"></i> Edit Profile</a>
            <a href="likes.php"><i class="fas fa-star"></i> Starred</a>
            <a href="saves.php"><i class="fas fa-bookmark"></i> Favorites</a>

            <div class="divider"></div>

            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Log Out</a>
        </div>
    </div>
</body>
</html>
