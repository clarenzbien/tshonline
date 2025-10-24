<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tshonline";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

/* =====================================================
   STEP 1: SEND VERIFICATION CODE TO EMAIL
===================================================== */
if (isset($_POST['send_code'])) {
    $email = trim($_POST['email']);

    $check = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        echo "No account found with that email.";
        exit();
    }

    $code = rand(100000, 999999);
    $conn->query("DELETE FROM email_verifications WHERE email='$email'");

    $stmt = $conn->prepare("INSERT INTO email_verifications (email, verification_code) VALUES (?, ?)");
    $stmt->bind_param("ss", $email, $code);
    $stmt->execute();
    $stmt->close();

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'clba.magalong.up@phinmaed.com';
        $mail->Password = 'xbge kail ttls brov';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('clba.magalong.up@phinmaed.com', 'TSH Online');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Your Password Reset Code';
        $mail->Body = "<p>Your password reset code is <b>$code</b>.</p>";

        $mail->send();
        echo "Verification code sent to $email.";
    } catch (Exception $e) {
        echo "Error sending email: {$mail->ErrorInfo}";
    }
    exit();
}

/* =====================================================
   STEP 2: VERIFY CODE AND REDIRECT TO RESET PAGE
===================================================== */
if (isset($_POST['verify_code'])) {
    $email = trim($_POST['email']);
    $code = trim($_POST['verification_code']);

    $stmt = $conn->prepare("SELECT verification_code FROM email_verifications WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($dbCode);
    $stmt->fetch();
    $stmt->close();

    if ($dbCode && $dbCode === $code) {
        $conn->query("DELETE FROM email_verifications WHERE email='$email'");
        $_SESSION['reset_email'] = $email;
        header("Location: reset_password.php");
        exit();
    } else {
        $message = "Invalid or expired verification code!";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="./styles/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* --- Match login.css layout --- */
        .login-form h2 {
            text-align: center;
            font-family: Nexa, var(--default-font-family);
            font-weight: 900;
            font-size: 30px;
            margin-bottom: 20px;
            color: #000;
        }

        .verify-btn {
            width: 100%;
            background: rgb(0, 82, 18);
            color: #fff;
            padding: 15px;
            font-size: 20px;
            border: none;
            border-radius: 10px;
            margin-top: 15px;
            cursor: pointer;
        }

        .verify-btn:hover {
            background: rgba(2, 62, 0, 0.611);
        }

        .verify-section {
            display: none;
            margin-top: 20px;
            text-align: center;
        }

        .verify-section input {
            width: 90%;
            padding: 15px;
            font-size: 18px;
            border-radius: 10px;
            border: none;
            background: rgba(135, 135, 135, 0.401);
            color: #000;
            margin-bottom: 10px;
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
                <form method="post" id="forgotForm" class="login-form">
                    <h2>Forgot Password</h2>

                    <input type="email" id="email" name="email" placeholder="Enter your email" required>

                    <button type="button" id="sendCodeBtn" class="verify-btn">
                        <i class="fa-solid fa-paper-plane"></i> Send Code
                    </button>

                    <div id="verifySection" class="verify-section">
                        <input type="text" name="verification_code" placeholder="Enter verification code">
                        <button type="submit" name="verify_code" class="submitS">Verify Code</button>
                    </div>

                    <?php if (!empty($message)) : ?>
                        <p class="error-message"><?php echo $message; ?></p>
                    <?php endif; ?>

                    <a href="login.php" class="login-link">Back to Login</a>
                </form>
            </div>
        </div>
    </div>

    <script>
document.getElementById("sendCodeBtn").addEventListener("click", async () => {
    const email = document.getElementById("email").value;
    if (!email) {
        alert("Please enter your email first.");
        return;
    }

    const sendBtn = document.getElementById("sendCodeBtn");
    const messageBox = document.createElement("p");
    messageBox.style.textAlign = "center";
    messageBox.style.fontSize = "16px";
    messageBox.style.marginTop = "10px";
    messageBox.style.color = "#004d00";
    messageBox.id = "loadingMessage";
    messageBox.textContent = "Sending verification code… please wait.";

    // Add message below the button if not already there
    if (!document.getElementById("loadingMessage")) {
        sendBtn.insertAdjacentElement("afterend", messageBox);
    }

    sendBtn.disabled = true;
    sendBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';

    const formData = new FormData();
    formData.append("send_code", "1");
    formData.append("email", email);

    try {
        const response = await fetch("forgot_password.php", {
            method: "POST",
            body: formData
        });

        const result = await response.text();
        messageBox.textContent = result;
        messageBox.style.color = result.includes("sent") ? "#006400" : "#8B0000";

        if (result.includes("sent")) {
            document.getElementById("verifySection").style.display = "block";
        }
    } catch (error) {
        messageBox.textContent = "An error occurred. Please try again.";
        messageBox.style.color = "#8B0000";
    } finally {
        sendBtn.disabled = false;
        sendBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Send Code';
    }
});
</script>

</body>
</html>
