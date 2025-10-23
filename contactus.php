<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>About Us</title>
<link rel="stylesheet" href="./styles/style.css" />
<link rel="stylesheet" href="./styles/aboutus.css" />
</head>
<body>
<div class="main-container">
    <div class="left-icon" onclick="openSidebar()">
        <?php include './hamburger-menu.php'; ?>
    </div>
</div>

<main class="aboutus-container">

    <h1 class="page-title">CONTACT THE STUDENTS' HERALD</h1>

    <div class="contact-section">

        <div class="contact-info">
            <div class="info-block">
                <h2>Location</h2>
                <p>28WV+R2R, Arellano St, Downtown District, Dagupan, 2400 Pangasinan</p>
            </div>
            <div class="info-block">
                <h2>Phone</h2>
                <p>(075) 522-5635</p>
            </div>
            <div class="info-block">
                <h2>Links</h2>
                <p>Email: tsh.up@phinmaed.com</p>
                <p>Facebook: <a href="https://www.facebook.com/upangherald/" target="_blank">upangherald</a></p>
                <p>Instagram: thestudentsherald_2526</p>
            </div>
        </div>

        <div class="map-container">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3834.3540076726777!2d120.33993977459967!3d16.04710924003076!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x339167fe6bba4d67%3A0xf54b516c2c5d10b6!2sPHINMA-University%20of%20Pangasinan!5e0!3m2!1sen!2sph!4v1710343365444!5m2!1sen!2sph" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>

    </div>

</main>

<?php include 'footer.php'; ?>
</body>
</html>
