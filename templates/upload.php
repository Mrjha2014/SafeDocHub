<?php
// templates/upload.php
session_start();
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('login.php');
}

// Add your upload form and content here
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Add head content -->
</head>
<body>
    <!-- Add upload form and content here -->

    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
</body>
</html>
