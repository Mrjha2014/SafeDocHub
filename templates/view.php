<?php
// templates/view.php
session_start();
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('login.php');
}

// Add your document view content here
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Add head content -->
</head>
<body>
    <!-- Add document view content here -->

    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
</body>
</html>
