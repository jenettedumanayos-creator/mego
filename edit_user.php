<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

$conn = new mysqli("localhost", "root", "", "web_system");
if ($conn->connect_error)
    die("Connection failed: " . $conn->connect_error);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$id = (int) $_GET['id'];

$stmt = $conn->prepare("SELECT fullname, email, username, role FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($fullname, $email, $username, $role);
if (!$stmt->fetch()) {
    header("Location: admin_dashboard.php");
    exit();
}
$stmt->close();

$fullname = $fullname ?? '';
$email = $email ?? '';
$username = $username ?? '';
$role = $role ?? 'user';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Edit User - RJ's Artisan Café</title>
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

        .edit-wrapper {
            width: 100%;
            max-width: 500px;
        }

        .edit-card {
            background: var(--cream);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
        }

        .edit-header {
            background: linear-gradient(135deg, var(--cafe-brown) 0%, var(--cafe-light) 100%);
            padding: var(--spacing-xl) var(--spacing-lg);
            text-align: center;
            color: var(--white);
        }

        .edit-header h1 {
            color: var(--white);
            font-size: var(--font-size-2xl);
            margin-bottom: 0;
            font-family: var(--font-serif);
        }

        .edit-body {
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

        .form-control,
        .form-select {
            width: 100%;
            padding: var(--spacing-md);
            border: 2px solid var(--border-light);
            border-radius: var(--radius-md);
            font-size: var(--font-size-base);
            transition: all var(--transition-base);
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }

        .form-control::placeholder,
        .form-select::placeholder {
            color: var(--text-light);
        }

        .form-control:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212, 165, 116, 0.1);
        }

        .form-select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23d4a574' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right var(--spacing-md) center;
            padding-right: 3rem;
            cursor: pointer;
        }

        .button-group {
            display: flex;
            gap: var(--spacing-md);
            margin-top: var(--spacing-xl);
        }

        .btn-update {
            flex: 1;
            padding: var(--spacing-md) var(--spacing-lg);
            background: linear-gradient(135deg, var(--cafe-brown) 0%, var(--cafe-light) 100%);
            color: var(--white);
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: var(--font-size-base);
            cursor: pointer;
            transition: all var(--transition-base);
            letter-spacing: 0.5px;
        }

        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(44, 31, 23, 0.3);
        }

        .btn-cancel {
            flex: 1;
            padding: var(--spacing-md) var(--spacing-lg);
            background: transparent;
            color: var(--cafe-brown);
            border: 2px solid var(--gold);
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: var(--font-size-base);
            cursor: pointer;
            transition: all var(--transition-base);
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-cancel:hover {
            background: var(--gold);
            color: var(--white);
            border-color: var(--gold);
        }

        @media (max-width: 480px) {
            .edit-header {
                padding: var(--spacing-lg) var(--spacing-md);
            }

            .edit-body {
                padding: var(--spacing-lg);
            }

            .button-group {
                flex-direction: column;
            }

            .edit-header h1 {
                font-size: var(--font-size-xl);
            }
        }
    </style>
</head>

<body>
    <div class="edit-wrapper">
        <div class="edit-card">
            <div class="edit-header">
                <h1>Edit User</h1>
            </div>
            <div class="edit-body">
                <form action="update_user.php" method="POST">
                    <input type="hidden" name="id" value="<?= $id ?>">

                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="fullname"
                            value="<?= htmlspecialchars($fullname) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($email) ?>"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username"
                            value="<?= htmlspecialchars($username) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Account Role</label>
                        <select class="form-select" name="role" required>
                            <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>User</option>
                            <option value="staff" <?= $role === 'staff' ? 'selected' : '' ?>>Staff</option>
                            <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Administrator</option>
                        </select>
                    </div>

                    <div class="button-group">
                        <button type="submit" class="btn-update">Update User</button>
                        <a href="admin_dashboard.php" class="btn-cancel">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>

<?php $conn->close(); ?>