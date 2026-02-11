<?php
session_start();

// Check if admin is logged in
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
} else {
    header("Location: login.php");
    exit;
}
?>
