<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";   
$password = "";       
$dbname = "tshonline";

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


<div class="about-us-page">
  <header class="company-header">
    <h1 class="company-title"><span class="our">OUR COMPANY</h1>
  </header>

  <section class="company-description">
    <p>
      The Students’ Herald, having been founded in 1988, is a duly recognized institution
      in PHINMA-University of Pangasinan and carries the advocacy line “Truth Above All.”
      The organization serves as a channel for information dissemination, transparency,
      and freedom of expression, and acts as an autonomous institution performing duties
      to uphold, defend, and protect the rights of students.
    </p>
  </section>

  <section class="mission-vision">
    <div class="mission">
      <h2>MISSION</h2>
      <p>
        The Students' Herald shall carry the advocacy line “Truth Above All” and will be
        committed to journalistic integrity, preserving its right to inform students,
        regardless of school administration and student government constraints, while
        upholding moral and legal obligations.
      </p>
    </div>

    <div class="vision">
      <h2>VISION</h2>
      <p>
        The Students' Herald, as an autonomous institution, shall ensure transparency and
        publish reliable reports to preserve students’ rights to fair information and act
        as a channel for student reactions to school policies, grievances, and complaints
        that require immediate attention.
      </p>
    </div>
  </section>

  <section class="objectives-section">
    <h2>OBJECTIVES</h2>
    <p>The Students’ Herald shall at all times:</p>
    <ul class="objectives-list">
      <li>Uphold, protect, and defend the rights of students, such as the right to information transparency and freedom of expression.</li>
      <li>Enhance and enrich the journalistic skills of all members through training, seminars, workshops, and orientation programs.</li>
      <li>Serve as the official channel, aside from the student government, through which students can air their reactions to school policies, grievances, and complaints.</li>
      <li>Inform the students about school activities, as well as local, national, and international issues requiring attention.</li>
      <li>Act as an autonomous institution, without violating moral and ethical standards or laws, in performing its duties and responsibilities.</li>
    </ul>
  </section>
</div>


<?php include 'footer.php'; ?>
</div>
</body>
</html>
