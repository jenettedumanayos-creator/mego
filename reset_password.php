<?php
session_start();

// Only admin can perform reset
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$id = (int)$_GET['id'];
$default = '123456789';
$hashed = password_hash($default, PASSWORD_DEFAULT);

$conn = new mysqli("localhost", "root", "", "web_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->bind_param("si", $hashed, $id);
$stmt->execute();
$stmt->close();
$conn->close();

header("Location: admin_dashboard.php?success=Password+reset+to+default");
exit();
