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

// === Step 1: Send verification code ===
if (isset($_POST['send_code'])) {
    $email = trim($_POST['email']);
    $code = rand(100000, 999999); // 6-digit code

    // Remove old codes
    $conn->query("DELETE FROM email_verifications WHERE email = '$email'");

    $stmt = $conn->prepare("INSERT INTO email_verifications (email, verification_code) VALUES (?, ?)");
    $stmt->bind_param("ss", $email, $code);
    $stmt->execute();
    $stmt->close();

    // Send email using PHPMailer
    $mail = new PHPMailer(true);
    try {
        // Gmail SMTP setup
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'clba.magalong.up@phinmaed.com'; // 🟡 replace with your Gmail
        $mail->Password = 'xbge kail ttls brov';   // 🟡 replace with your Google App password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('yourgmail@gmail.com', 'TSH Online');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Your Verification Code';
        $mail->Body = "<p>Your verification code is <b>$code</b>. Enter this to complete your signup.</p>";

        $mail->send();
        echo "Verification code sent to $email!";
    } catch (Exception $e) {
        echo "Error sending email: {$mail->ErrorInfo}";
    }
    exit();
}

// === Step 2: Final signup after code verification ===
$message = "";

if (isset($_POST['username']) && isset($_POST['verification_code'])) {
    $username = trim($_POST['username']);
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $code = trim($_POST['verification_code']);

    $weakPasswords = ["123456","password","qwerty","111111","123123","abc123"];

    // === Password validation ===
    if (strlen($password) < 8) {
        $message = "Password must be at least 8 characters.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $message = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $message = "Password must contain at least one lowercase letter.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $message = "Password must contain at least one number.";
    } elseif (!preg_match('/[\W_]/', $password)) {
        $message = "Password must contain at least one special character.";
    } elseif (in_array(strtolower($password), $weakPasswords)) {
        $message = "Password is too common.";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
    } else {
        // Check verification code
        $stmt = $conn->prepare("SELECT verification_code FROM email_verifications WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($dbCode);
        $stmt->fetch();
        $stmt->close();

        if ($dbCode !== $code) {
            echo "<script>alert('Invalid verification code!'); window.history.back();</script>";
            exit();
        }

        // Check unique username/email
        $checkSql = "SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1";
        $stmt = $conn->prepare($checkSql);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            echo "<script>alert('Username or Email already exists.'); window.history.back();</script>";
            exit();
        }

        // Handle profile picture
        $profilePic = null;
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
            $targetDir = "uploads/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            $fileName = uniqid() . "_" . basename($_FILES['profile_pic']['name']);
            $targetFile = $targetDir . $fileName;

            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFile)) {
                $profilePic = $targetFile;
            } else {
                $message = "Error uploading profile picture.";
            }
        }

        if (empty($message)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, name, email, password, profile_pic) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $username, $name, $email, $hashedPassword, $profilePic);

            if ($stmt->execute()) {
                $conn->query("DELETE FROM email_verifications WHERE email = '$email'");
                echo "<script>alert('Signup successful! You can now log in.'); window.location.href = 'login.php';</script>";
                exit();
            } else {
                $message = "Error: " . $stmt->error;
            }
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
<title>Signup</title>
<link rel="stylesheet" href="./styles/signup.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>
<body>
    <div class="main-container">
        <div class="pic-wrapper"></div>
        <div class="right-container">

            <div class="form-wrapper">
                <form method="post" class="signup-form" enctype="multipart/form-data">
                    <span class="caa">CREATE AN ACCOUNT</span>

                    <div class="profile-pic" id="profilePicDiv">
                        <input type="file" id="profilePic" name="profile_pic" accept="image/*" onchange="previewProfilePic(event)">
                        <label id="uploadLabel" for="profilePic">
                            <i class="fa fa-camera"></i> Upload
                        </label>
                    </div>
                    <div class="name-wrapper">
                        <input type="text" name="username" placeholder="Username" required>
                        <input type="text" name="name" placeholder="Full Name" required>
                    </div>
                    <input type="email" id="email" name="email" placeholder="Email" required>
                    
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Password" required>
                        <i class="fa-solid fa-eye" id="togglePassword"></i>
                    </div>    
                    <ul class="requirements" id="passwordRequirements">
                            <li id="length" class="invalid">Minimum 8 characters</li>
                            <li id="uppercase" class="invalid">At least one uppercase letter</li>
                            <li id="lowercase" class="invalid">At least one lowercase letter</li>
                            <li id="number" class="invalid">At least one number</li>
                            <li id="special" class="invalid">At least one special character</li>
                            <li id="common" class="invalid">Not a common password</li>
                        </ul>
                
                        <input type="password" name="confirm_password" placeholder="Confirm Password" required>

                        <div class="verification-wrapper">
                            <button type="button" id="sendCodeBtn" class="verify-btn">
                                <i class="fa-solid fa-paper-plane"></i> Send Verification Code
                            </button>

                            <div id="verifySection" class="verify-section hidden">
                                <input type="text" name="verification_code" placeholder="Enter Verification Code" class="verify-input">
                            </div>
                        </div>

                    
                    <button class="submitS" type="submit">Sign Up</button>

                    <div class="haa">
                        <p>Have an account? 
                            <a href="login.php" class="logint">Log In</a></p>
                    </div>
                    </form>
                </div>
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

// Live password validation
const passwordInput = document.getElementById("password");
const requirements = {
    length: document.getElementById("length"),
    uppercase: document.getElementById("uppercase"),
    lowercase: document.getElementById("lowercase"),
    number: document.getElementById("number"),
    special: document.getElementById("special"),
    common: document.getElementById("common")
};

const weakPasswords = ["12345689","password","qwerty","111111","123123","abc123"];

passwordInput.addEventListener("input", function() {
    const val = passwordInput.value;

    // Length
    requirements.length.className = val.length >= 8 ? "valid" : "invalid";

    // Uppercase
    requirements.uppercase.className = /[A-Z]/.test(val) ? "valid" : "invalid";

    // Lowercase
    requirements.lowercase.className = /[a-z]/.test(val) ? "valid" : "invalid";

    // Number
    requirements.number.className = /[0-9]/.test(val) ? "valid" : "invalid";

    // Special char
    requirements.special.className = /[\W_]/.test(val) ? "valid" : "invalid";

    // Common password check
    requirements.common.className = weakPasswords.includes(val.toLowerCase()) ? "invalid" : "valid";
});
</script>


<script>
const togglePassword = document.getElementById("togglePassword");
const password = document.getElementById("password");

togglePassword.addEventListener("click", () => {
    // toggle the type attribute
    const type = password.getAttribute("type") === "password" ? "text" : "password";
    password.setAttribute("type", type);

    // toggle eye / eye-slash icon
    togglePassword.classList.toggle("fa-eye");
    togglePassword.classList.toggle("fa-eye-slash");
});
</script>

<script>
document.getElementById("sendCodeBtn").addEventListener("click", async () => {
    const email = document.getElementById("email").value;
    if (!email) {
        alert("Enter your email first!");
        return;
    }

    const formData = new FormData();
    formData.append("send_code", "1");
    formData.append("email", email);

    const response = await fetch("signup.php", {
        method: "POST",
        body: formData
    });

    const result = await response.text();
    alert(result);
    document.getElementById("verifySection").style.display = "block";
});
</script>


</body>
</html>

<!--verification abled-->