<?php
session_start();
session_destroy();
header("Location: maindashboard.php");
exit();
