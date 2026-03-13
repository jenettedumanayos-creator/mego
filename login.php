<?php
session_start();

$conn = new mysqli("localhost", "root", "", "web_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter username and password";
    } else {
        $stmt = $conn->prepare("SELECT id, fullname, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $fullname, $hashed, $role);
            $stmt->fetch();

            if (is_string($hashed) && password_verify($password, $hashed)) {
                $_SESSION['user_id']   = $id;
                $_SESSION['fullname']  = $fullname;
                $_SESSION['username']  = $username;
                $_SESSION['role']      = $role;

                // force password change when default password is used
                $defaultPwd = '123456789';
                if (password_verify($defaultPwd, $hashed)) {
                    // redirect to change-password screen
                    header("Location: change_password.php");
                    exit();
                }

                if ($role === 'admin') {
                    header("Location: admin_dashboard.php");
                    exit();
                } else {
                    header("Location: menu.html");
                    exit();
                }
            } else {
                $error = "Invalid username or password";
            }
        } else {
            $error = "Invalid username or password";
        }
    }
}

$conn->close();


if (!empty($error)) {
    echo "<script>alert('" . addslashes($error) . "'); window.location='login.html';</script>";
    exit();
}