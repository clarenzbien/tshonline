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

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $passwordInput = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email='$email' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($passwordInput, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['profile_pic'] = $user['profile_pic'];

            // Correct admin check with role-based redirects
            if ($user['is_admin'] == 1) {
                $_SESSION['admin_id'] = $user['id']; // optional, needed for admin pages
                $_SESSION['role'] = $user['role'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['profile_pic'] = $user['profile_pic'];

                // Redirect based on role
                if ($user['role'] === 'Executive') {
                    header("Location: admin/admin-dashboard.php");
                } elseif ($user['role'] === 'Staff' || $user['role'] === 'Section Head') {
                    header("Location: staff/staff-articles.php");
                } else {
                    // Fallback for unknown roles
                    header("Location: maindashboard.php");
                }
            } else {
                // Regular user
                header("Location: maindashboard.php");
            }
            exit();



        } else {
            $message = "Invalid password!";
        }
    } else {
        $message = "User not found!";
    }
}




$conn->close();
?>

<!DOCTYPE html>
<html>
<head><title>Login</title>
<link rel="stylesheet" href="./styles/login.css" /></head>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<body>
    <div class="main-container">
        <div class="pic-wrapper"> </div>
            
         <!-- Right-side container -->
            <div class="right-container">
                <div class="TSH-wrapper">
                    <img src="./assets/images/black_3@4x.png" alt="TSH Image" class="TSHpic">
                </div>


                
                <div class="form-wrapper">
                    <form method="post" class="login-form">
                        <span class="wlcb">WELCOME BACK!</span>
                    
                        <span class="emailt">Email</span>
                        <input type="email" name="email" placeholder="Enter your Email" required>
                        <span class="passwordt">Password</span>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                            <i class="fa-solid fa-eye" id="togglePassword"></i>
                        </div> 
                        <?php if (!empty($message)) : ?>
                            <p class="error-message"><?php echo $message; ?></p>
                        <?php endif; ?>

                        <button type="submit">Login</button>
                        <div class="dhc">
                            <p>Don't have an account? 
                                <a href="signup.php" class="signupt">Sign Up</a></p>
                        </div>
                    </form>
                </div>
            </div>
        


        
    </div>
    
</body>


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


</html>
