<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

$conn = new mysqli("localhost", "root", "", "web_system");
if ($conn->connect_error)
    die("Connection failed: " . $conn->connect_error);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) $_POST['id'];
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $role = $_POST['role'];

    // Basic validation
    if (empty($fullname) || empty($email) || empty($username) || !in_array($role, ['user', 'staff', 'admin'], true)) {
        header("Location: edit_user.php?id=$id&error=All fields are required");
        exit();
    }

    // Update user
    $stmt = $conn->prepare("UPDATE users SET fullname = ?, email = ?, username = ?, role = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $fullname, $email, $username, $role, $id);

    if ($stmt->execute()) {
        $message = "User updated successfully";
        header("Location: admin_dashboard.php?success=$message");
    } else {
        header("Location: edit_user.php?id=$id&error=Update failed (username/email may already exist)");
    }

    $stmt->close();
}

$conn->close();
exit();
?>