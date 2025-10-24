<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tshonline";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

$email = $_SESSION['reset_email'];
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $message = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE email=?");
        $stmt->bind_param("ss", $hashed, $email);
        if ($stmt->execute()) {
            unset($_SESSION['reset_email']);
            echo "<script>alert('Password reset successful! You can now log in.'); window.location.href='login.php';</script>";
            exit();
        } else {
            $message = "Error updating password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="./styles/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .login-form h2 {
            text-align: center;
            font-family: Nexa, var(--default-font-family);
            font-weight: 900;
            font-size: 30px;
            margin-bottom: 20px;
            color: #000;
        }

        .login-form input {
            width: 90%;
            padding: 15px;
            font-size: 18px;
            border-radius: 10px;
            border: none;
            background: rgba(135, 135, 135, 0.401);
            color: #000;
            margin-bottom: 15px;
        }

        .submitS {
            width: 100%;
            background: rgb(0, 82, 18);
            color: #fff;
            padding: 15px;
            font-size: 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
        }

        .submitS:hover {
            background: rgba(2, 62, 0, 0.611);
        }

        .error-message {
            color: rgb(135, 0, 0);
            font-size: 16px;
            text-align: center;
            margin-top: 10px;
        }

        .login-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #033a00;
            font-weight: 700;
            text-decoration: none;
            font-size: 18px;
        }

        .login-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="main-container">
        <div class="pic-wrapper"></div>

        <div class="right-container">
            <div class="form-wrapper">
                <form method="post" class="login-form">
                    <h2>Reset Password</h2>

                    <input type="password" name="password" placeholder="Enter new password" required>
                    <input type="password" name="confirm_password" placeholder="Confirm new password" required>

                    <button type="submit" class="submitS">
                        <i class="fa-solid fa-lock"></i> Reset Password
                    </button>

                    <?php if (!empty($message)) : ?>
                        <p class="error-message"><?php echo $message; ?></p>
                    <?php endif; ?>

                    <a href="login.php" class="login-link">Back to Login</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
