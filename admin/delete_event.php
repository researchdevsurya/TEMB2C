<?php
session_start();
require '../db.php';

if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit;
}

$id = $_GET['id'];

$stmt = $pdo->prepare("DELETE FROM schedule_master WHERE id=?");
$stmt->execute([$id]);

header("Location: events.php");
