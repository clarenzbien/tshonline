<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tshonline";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$user_id = $_SESSION['user_id'];

// Fetch existing user data
$sql = "SELECT username, name, email, profile_pic, password, bio FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $bio = trim($_POST['bio']);
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Weak/common passwords
    $weakPasswords = ["123456","password","qwerty","111111","123123","abc123"];

    // If user wants to change password, verify current one first
    if (!empty($newPassword)) {
        if (empty($currentPassword)) {
            $message = "Please enter your current password to set a new one.";
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $message = "Current password is incorrect.";
        } elseif (strlen($newPassword) < 8) {
            $message = "New password must be at least 8 characters.";
        } elseif (!preg_match('/[A-Z]/', $newPassword)) {
            $message = "New password must contain at least one uppercase letter.";
        } elseif (!preg_match('/[a-z]/', $newPassword)) {
            $message = "New password must contain at least one lowercase letter.";
        } elseif (!preg_match('/[0-9]/', $newPassword)) {
            $message = "New password must contain at least one number.";
        } elseif (!preg_match('/[\W_]/', $newPassword)) {
            $message = "New password must contain at least one special character.";
        } elseif (in_array(strtolower($newPassword), $weakPasswords)) {
            $message = "Password is too common.";
        } elseif ($newPassword !== $confirmPassword) {
            $message = "New passwords do not match.";
        }
    }

    // Only continue if there are no error messages yet
    if (empty($message)) {
        // Check unique username/email (exclude current user)
        $checkSql = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ? LIMIT 1";
        $stmt = $conn->prepare($checkSql);
        $stmt->bind_param("ssi", $username, $email, $user_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "Username or Email already exists. Choose another.";
        } else {
            // Handle profile picture
            $profilePic = $user['profile_pic'];
            if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
                $targetDir = "uploads/";
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

                $fileName = uniqid() . "_" . basename($_FILES['profile_pic']['name']);
                $targetFile = $targetDir . $fileName;

                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFile)) {
                    $profilePic = $targetFile;
                } else {
                    $message = "Error uploading profile picture.";
                }
            }

            if (empty($message)) {
                if (!empty($newPassword)) {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $updateSql = "UPDATE users SET username=?, name=?, email=?, password=?, profile_pic=?, bio=? WHERE id=?";
                    $stmt = $conn->prepare($updateSql);
                    $stmt->bind_param("sssssi", $username, $name, $email, $hashedPassword, $profilePic, $user_id);
                } else {
                    $updateSql = "UPDATE users SET username=?, name=?, email=?, profile_pic=?, bio=? WHERE id=?";
                    $stmt = $conn->prepare($updateSql);
                    $stmt->bind_param("sssssi", $username, $name, $email, $profilePic, $bio, $user_id);
                }

               if ($stmt->execute()) {
                if (!empty($newPassword)) {
                    $message = "✅ Password updated successfully!";
                } else {
                    $message = "✅ Profile updated successfully!";
                }
            } else {
                $message = "❌ Error updating profile: " . $stmt->error;
            }

            }
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="./styles/edit_profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="main-container">
    <div class="back-container">
                <a href="account.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> 
                </a>
            </div>
    <div class="form-wrapper">
        <h1><i class="fas fa-pen"></i> Edit Profile</h1>

       <?php if($message): ?>
            <p style="color: <?php echo (strpos($message, '✅') !== false) ? 'green' : 'red'; ?>;">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>


        <form method="post" enctype="multipart/form-data">
            <div class="profile-pic" id="profilePicDiv"
                 style="background-image: url('<?php echo $user['profile_pic'] ? $user['profile_pic'] : 'default.png'; ?>');
                        background-size: cover; background-position: center;">
                <input type="file" id="profilePic" name="profile_pic" accept="image/*" onchange="previewProfilePic(event)">
                <label id="uploadLabel" for="profilePic">
                    <i class="fa fa-camera"></i> Upload
                </label>
            </div>

            <input type="text" name="username" placeholder="Username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            <input type="text" name="name" placeholder="Full Name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            <textarea name="bio" placeholder="Your bio..." rows="4"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
            <hr style="margin: 10px 0;">

            <input type="password" name="current_password" placeholder="Current Password (required if changing)">
            <input type="password" name="password" placeholder="New Password (leave blank to keep)">
            <input type="password" name="confirm_password" placeholder="Confirm New Password">

            <button type="submit">Save Changes</button>
        </form>
    </div>
</div>

<script>
function previewProfilePic(event) {
    const input = event.target;
    const profilePicDiv = input.parentElement;
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            profilePicDiv.style.backgroundImage = `url('${e.target.result}')`;
            profilePicDiv.style.backgroundSize = "cover";
            profilePicDiv.style.backgroundPosition = "center";
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>
