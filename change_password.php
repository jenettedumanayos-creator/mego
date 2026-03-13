<?php
session_start();

// make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$conn = new mysqli("localhost", "root", "", "web_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($new) || empty($confirm)) {
        $error = "All fields are required";
    } elseif ($new !== $confirm) {
        $error = "Passwords do not match";
    } elseif (strlen($new) < 6) {
        $error = "Password must be at least 6 characters";
    } else {
        // update password
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $_SESSION['user_id']);
        if ($stmt->execute()) {
            $success = "Password changed successfully";
            // after changing password, log user out and redirect to login
            session_unset();
            session_destroy();
            header("Location: login.html?success=" . urlencode($success));
            exit();
        } else {
            $error = "Failed to update password";
        }
    }
}

$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Change Password - RJ's Artisan Café</title>
    <style>
        body {
            background-image: url('images/bg.jpg');
            background-size: cover;
            background-attachment: fixed;
            background-repeat: no-repeat;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-md);
        }

        .password-wrapper {
            width: 100%;
            max-width: 450px;
        }

        .password-card {
            background: var(--cream);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
        }

        .password-header {
            background: linear-gradient(135deg, var(--cafe-brown) 0%, var(--cafe-light) 100%);
            padding: var(--spacing-xl) var(--spacing-lg);
            text-align: center;
            color: var(--white);
        }

        .password-header h1 {
            color: var(--white);
            font-size: var(--font-size-2xl);
            margin-bottom: var(--spacing-sm);
            font-family: var(--font-serif);
        }

        .password-header p {
            color: var(--gold);
            font-size: var(--font-size-base);
            margin: 0;
            letter-spacing: 0.5px;
        }

        .password-body {
            padding: var(--spacing-xl);
        }

        .form-group {
            margin-bottom: var(--spacing-lg);
        }

        .form-label {
            display: block;
            margin-bottom: var(--spacing-sm);
            color: var(--text-dark);
            font-weight: 600;
            font-size: var(--font-size-sm);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            width: 100%;
            padding: var(--spacing-md);
            border: 2px solid var(--border-light);
            border-radius: var(--radius-md);
            font-size: var(--font-size-base);
            transition: all var(--transition-base);
        }

        .form-control::placeholder {
            color: var(--text-light);
        }

        .form-control:disabled {
            background-color: #f0f0f0;
            color: #999;
            cursor: not-allowed;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212, 165, 116, 0.1);
        }

        .btn-change {
            width: 100%;
            padding: var(--spacing-md) var(--spacing-lg);
            background: linear-gradient(135deg, var(--cafe-brown) 0%, var(--cafe-light) 100%);
            color: var(--white);
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: var(--font-size-lg);
            cursor: pointer;
            transition: all var(--transition-base);
            letter-spacing: 1px;
            margin-top: var(--spacing-md);
        }

        .btn-change:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(44, 31, 23, 0.3);
        }

        .alert {
            padding: var(--spacing-md) var(--spacing-lg);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
            border-left: 4px solid;
            animation: slideIn 0.3s ease;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left-color: #e74c3c;
        }

        @media (max-width: 480px) {
            .password-header {
                padding: var(--spacing-lg) var(--spacing-md);
            }

            .password-body {
                padding: var(--spacing-lg);
            }

            .password-header h1 {
                font-size: var(--font-size-xl);
            }
        }
    </style>
</head>
<body>
    <div class="password-wrapper">
        <div class="password-card">
            <div class="password-header">
                <h1>Change Password</h1>
                <p>Update your account security</p>
            </div>
            <div class="password-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <!-- disabled field for display, plus hidden field for submission -->
                        <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['username']) ?>" readonly>
                        <input type="hidden" name="username" value="<?= htmlspecialchars($_SESSION['username']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" placeholder="Enter new password" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" name="confirm_password" placeholder="Confirm new password" required>
                    </div>
                    <button type="submit" class="btn-change">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
