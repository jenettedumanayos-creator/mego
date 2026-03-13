<?php
session_start();
$conn = new mysqli("localhost", "root", "", "web_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if (empty($fullname) || empty($email) || empty($username) || empty($password)) {
        echo "<script>alert('All fields are required!'); window.history.back();</script>";
        exit();
    }
    if ($password !== $confirm) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit();
    }

    $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_stmt->store_result();
    if ($check_stmt->num_rows > 0) {
        echo "<script>alert('Email already exists!'); window.history.back();</script>";
        $check_stmt->close();
        $conn->close();
        exit();
    }
    $check_stmt->close();

    $hashed = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (fullname, email, username, password, role) VALUES (?, ?, ?, ?, 'user')");
    $stmt->bind_param("ssss", $fullname, $email, $username, $hashed);

    if ($stmt->execute()) {
        echo "<script>alert('Registration Successful! You can now login.'); window.location='login.html';</script>";
    } else {
        echo "<script>alert('Error: Username already exists!'); window.history.back();</script>";
    }

    $stmt->close();
}
$conn->close();
?>