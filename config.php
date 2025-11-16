<?php
// config.php
session_start();

$host   = "localhost";
$dbname = "ecommerce";   // database name
$dbuser = "root";        // your MySQL username
$dbpass = "";            // your MySQL password (empty for XAMPP)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}
