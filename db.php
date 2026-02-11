<?php
$host = "127.0.0.1:3308";
$dbname = "temb2c";
$user = "root";
$pass = "";

// $host = "91.108.107.83:3306";
// $dbname = "u113643104_temb2c";
// $user = "u113643104_temb2c";
// $pass = "Temb2c@123";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_PERSISTENT => true
        ]
    );
} catch(PDOException $e){
    die("Database connection failed");
}
?>
