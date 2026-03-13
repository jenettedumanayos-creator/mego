<?php
session_start();

// Security: only admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "web_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Keep role flexible so admin can add staff accounts.
$conn->query("ALTER TABLE users MODIFY COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user'");

// Create products table if it does not exist yet.
$conn->query("CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(150) NOT NULL,
    product_description VARCHAR(255) DEFAULT '',
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_product_name (product_name)
)");

function redirect_with_message(string $type, string $message): void
{
    header("Location: admin_dashboard.php?" . $type . "=" . urlencode($message));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_product') {
        $productName = trim((string) ($_POST['product_name'] ?? ''));
        $productDescription = trim((string) ($_POST['product_description'] ?? ''));
        $priceInput = trim((string) ($_POST['price'] ?? ''));

        if ($productName === '' || $priceInput === '' || !is_numeric($priceInput)) {
            redirect_with_message('error', 'Please enter a valid product name and price.');
        }

        $price = (float) $priceInput;
        if ($price < 0) {
            redirect_with_message('error', 'Price cannot be negative.');
        }

        $stmt = $conn->prepare("INSERT INTO products (product_name, product_description, price, status) VALUES (?, ?, ?, 'active')");
        if (!$stmt) {
            redirect_with_message('error', 'Unable to prepare product insert.');
        }

        $stmt->bind_param("ssd", $productName, $productDescription, $price);
        if ($stmt->execute()) {
            $stmt->close();
            redirect_with_message('success', 'Product added successfully.');
        }

        $stmt->close();
        redirect_with_message('error', 'Product add failed. The product name might already exist.');
    }

    if ($action === 'update_product') {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $productName = trim((string) ($_POST['product_name'] ?? ''));
        $productDescription = trim((string) ($_POST['product_description'] ?? ''));
        $priceInput = trim((string) ($_POST['price'] ?? ''));
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

        if ($productId <= 0 || $productName === '' || $priceInput === '' || !is_numeric($priceInput)) {
            redirect_with_message('error', 'Please provide valid product values to update.');
        }

        $price = (float) $priceInput;
        if ($price < 0) {
            redirect_with_message('error', 'Price cannot be negative.');
        }

        $stmt = $conn->prepare("UPDATE products SET product_name = ?, product_description = ?, price = ?, status = ? WHERE id = ?");
        if (!$stmt) {
            redirect_with_message('error', 'Unable to prepare product update.');
        }

        $stmt->bind_param("ssdsi", $productName, $productDescription, $price, $status, $productId);
        if ($stmt->execute()) {
            $stmt->close();
            redirect_with_message('success', 'Product updated successfully.');
        }

        $stmt->close();
        redirect_with_message('error', 'Product update failed. Product name might already exist.');
    }

    if ($action === 'add_staff') {
        $fullname = trim((string) ($_POST['fullname'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($fullname === '' || $email === '' || $username === '' || $password === '') {
            redirect_with_message('error', 'Please complete all staff fields.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirect_with_message('error', 'Please provide a valid email for staff.');
        }

        if (strlen($password) < 8) {
            redirect_with_message('error', 'Staff password must be at least 8 characters.');
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (fullname, email, username, password, role) VALUES (?, ?, ?, ?, 'staff')");
        if (!$stmt) {
            redirect_with_message('error', 'Unable to prepare staff insert.');
        }

        $stmt->bind_param("ssss", $fullname, $email, $username, $hashed);
        if ($stmt->execute()) {
            $stmt->close();
            redirect_with_message('success', 'Staff account added successfully.');
        }

        $stmt->close();
        redirect_with_message('error', 'Staff add failed. Username or email might already exist.');
    }

    redirect_with_message('error', 'Invalid action request.');
}

$result = $conn->query("SELECT id, fullname, email, username, role, date_registered 
                        FROM users 
                        ORDER BY date_registered DESC");

$staffResult = $conn->query("SELECT id, fullname, email, username, date_registered
                             FROM users
                             WHERE role = 'staff'
                             ORDER BY date_registered DESC");

$productsResult = $conn->query("SELECT id, product_name, product_description, price, status, updated_at
                                FROM products
                                ORDER BY updated_at DESC, id DESC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Admin Dashboard - RJ's Artisan Café</title>
    <style>
        body {
            background-image: url('images/bg.jpg');
            background-size: cover;
            background-attachment: fixed;
            background-repeat: no-repeat;
        }

        .page-header {
            background: linear-gradient(135deg, var(--cafe-brown) 0%, var(--cafe-light) 100%);
            color: var(--white);
            padding: var(--spacing-xl) var(--spacing-md);
            box-shadow: var(--shadow-lg);
            position: relative;
            margin-bottom: var(--spacing-xl);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--spacing-md);
            text-align: center;
        }

        .logo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid var(--gold);
            object-fit: cover;
            margin-bottom: var(--spacing-lg);
            background: var(--white);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .page-header h1 {
            color: var(--white);
            font-size: var(--font-size-3xl);
            margin-bottom: var(--spacing-md);
        }

        .header-subtitle {
            color: var(--gold);
            font-size: var(--font-size-lg);
            margin: 0;
        }

        .btn-logout {
            position: absolute;
            top: var(--spacing-lg);
            right: var(--spacing-lg);
            background: transparent;
            color: var(--white);
            border: 2px solid var(--white);
            border-radius: var(--radius-md);
            padding: var(--spacing-md) var(--spacing-lg);
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-base);
            text-decoration: none;
        }

        .btn-logout:hover {
            background: var(--white);
            color: var(--cafe-brown);
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 var(--spacing-md) var(--spacing-xl);
        }

        .dashboard-title {
            font-size: var(--font-size-2xl);
            color: var(--cafe-brown);
            margin-bottom: var(--spacing-xl);
            padding-bottom: var(--spacing-lg);
            border-bottom: 3px solid var(--gold);
        }

        .alert {
            padding: var(--spacing-md) var(--spacing-lg);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
            border-left: 4px solid;
            animation: slideIn 0.3s ease;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left-color: var(--success);
        }

        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border-left-color: var(--info);
        }

        .alert-error {
            background-color: #fdecea;
            color: #7f1d1d;
            border-left-color: var(--danger);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
        }

        .panel {
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            padding: var(--spacing-lg);
        }

        .panel h3 {
            margin-bottom: var(--spacing-md);
            color: var(--cafe-brown);
            font-size: var(--font-size-xl);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-md);
        }

        .inline-form {
            display: grid;
            grid-template-columns: 1.2fr 1.4fr 0.7fr 0.6fr auto;
            gap: var(--spacing-sm);
            align-items: center;
        }

        .input-mini,
        .select-mini {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-sm);
            font-size: var(--font-size-sm);
        }

        .btn-small {
            padding: 8px 12px;
            background: linear-gradient(135deg, var(--cafe-brown) 0%, var(--cafe-light) 100%);
            color: var(--white);
            border: none;
            border-radius: var(--radius-sm);
            font-size: var(--font-size-sm);
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
        }

        .role-staff {
            background: rgba(243, 156, 18, 0.12);
            color: #b86100;
        }

        .table-responsive {
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }

        .table thead {
            background: linear-gradient(135deg, var(--cafe-brown) 0%, var(--cafe-light) 100%);
            color: var(--white);
        }

        .table th {
            padding: var(--spacing-md);
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: var(--font-size-sm);
            letter-spacing: 0.5px;
        }

        .table td {
            padding: var(--spacing-md);
            border-bottom: 1px solid var(--border-light);
            color: var(--text-dark);
        }

        .table tbody tr {
            transition: background-color var(--transition-base);
        }

        .table tbody tr:hover {
            background-color: rgba(212, 165, 116, 0.05);
        }

        .badge {
            display: inline-block;
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--radius-sm);
            font-size: var(--font-size-sm);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-admin {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success);
        }

        .badge-user {
            background: rgba(52, 152, 219, 0.1);
            color: var(--info);
        }

        .action-buttons {
            display: flex;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
            align-items: center;
        }

        .btn-edit {
            padding: var(--spacing-sm) var(--spacing-md);
            background: linear-gradient(135deg, var(--cafe-brown) 0%, var(--cafe-light) 100%);
            color: var(--white);
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: var(--font-size-sm);
            cursor: pointer;
            transition: all var(--transition-base);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(44, 31, 23, 0.2);
        }

        .btn-reset {
            padding: var(--spacing-sm) var(--spacing-md);
            background: var(--warning);
            color: var(--white);
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: var(--font-size-sm);
            cursor: pointer;
            transition: all var(--transition-base);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(243, 156, 18, 0.2);
            background: #e88608;
        }

        .empty-state {
            text-align: center;
            padding: var(--spacing-xl);
            color: var(--text-light);
        }

        .empty-state h3 {
            color: var(--text-dark);
            font-size: var(--font-size-xl);
            margin-bottom: var(--spacing-md);
        }

        @media (max-width: 768px) {
            .page-header {
                padding: var(--spacing-lg) var(--spacing-md);
            }

            .page-header h1 {
                font-size: var(--font-size-2xl);
            }

            .btn-logout {
                position: static;
                width: 100%;
                margin-top: var(--spacing-lg);
            }

            .header-content {
                text-align: center;
            }

            .dashboard-title {
                font-size: var(--font-size-xl);
            }

            .table {
                font-size: var(--font-size-sm);
            }

            .table th,
            .table td {
                padding: var(--spacing-sm);
            }

            .action-buttons {
                flex-direction: column;
                align-items: stretch;
            }

            .inline-form {
                grid-template-columns: 1fr;
            }

            .btn-edit,
            .btn-reset,
            .btn-small {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>

    <div class="page-header">
        <div class="header-content">
            <img src="images/h.png" alt="RJ's Artisan Café Logo" class="logo">
            <h1>Admin Dashboard</h1>
            <p class="header-subtitle">Welcome, <?= htmlspecialchars($_SESSION['fullname']) ?></p>
        </div>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>

    <div class="dashboard-container">
        <h2 class="dashboard-title">Store Management</h2>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <script>
                setTimeout(function () {
                    var alert = document.querySelector('.alert');
                    if (alert) alert.style.display = 'none';
                }, 2500);
            </script>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <script>
                setTimeout(function () {
                    var alert = document.querySelector('.alert-error');
                    if (alert) alert.style.display = 'none';
                }, 3000);
            </script>
        <?php endif; ?>

        <div class="dashboard-grid">
            <div class="panel">
                <h3>Add Product</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="add_product">
                    <div class="form-row">
                        <input type="text" name="product_name" class="form-control" placeholder="Product name" required>
                        <input type="number" step="0.01" min="0" name="price" class="form-control" placeholder="Price"
                            required>
                    </div>
                    <div class="form-row">
                        <input type="text" name="product_description" class="form-control"
                            placeholder="Description (optional)">
                    </div>
                    <button type="submit" class="btn-edit">Add Product</button>
                </form>
            </div>

            <div class="panel">
                <h3>Add Staff</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="add_staff">
                    <div class="form-row">
                        <input type="text" name="fullname" class="form-control" placeholder="Full name" required>
                        <input type="email" name="email" class="form-control" placeholder="Email" required>
                    </div>
                    <div class="form-row">
                        <input type="text" name="username" class="form-control" placeholder="Username" required>
                        <input type="password" name="password" class="form-control" minlength="8"
                            placeholder="Password (min 8 chars)" required>
                    </div>
                    <button type="submit" class="btn-edit">Add Staff</button>
                </form>
            </div>
        </div>

        <h2 class="dashboard-title">Products</h2>

        <?php if ($productsResult && $productsResult->num_rows > 0): ?>
            <div class="table-responsive" style="margin-bottom: var(--spacing-xl);">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($product = $productsResult->fetch_assoc()): ?>
                            <tr>
                                <td><?= (int) $product['id'] ?></td>
                                <td colspan="5">
                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="action" value="update_product">
                                        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">

                                        <input type="text" name="product_name" class="input-mini"
                                            value="<?= htmlspecialchars($product['product_name']) ?>" required>
                                        <input type="text" name="product_description" class="input-mini"
                                            value="<?= htmlspecialchars($product['product_description']) ?>">
                                        <input type="number" step="0.01" min="0" name="price" class="input-mini"
                                            value="<?= htmlspecialchars(number_format((float) $product['price'], 2, '.', '')) ?>"
                                            required>
                                        <select name="status" class="select-mini">
                                            <option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>>Active
                                            </option>
                                            <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>
                                                Inactive</option>
                                        </select>

                                        <button type="submit" class="btn-small">Save</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="table-responsive" style="margin-bottom: var(--spacing-xl);">
                <div class="empty-state">
                    <h3>No Products Yet</h3>
                    <p>Add your first product and price using the form above.</p>
                </div>
            </div>
        <?php endif; ?>

        <h2 class="dashboard-title">Staff Accounts</h2>

        <?php if ($staffResult && $staffResult->num_rows > 0): ?>
            <div class="table-responsive" style="margin-bottom: var(--spacing-xl);">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($staff = $staffResult->fetch_assoc()): ?>
                            <tr>
                                <td><?= (int) $staff['id'] ?></td>
                                <td><?= htmlspecialchars($staff['fullname']) ?></td>
                                <td><?= htmlspecialchars($staff['email']) ?></td>
                                <td><?= htmlspecialchars($staff['username']) ?></td>
                                <td><?= htmlspecialchars($staff['date_registered']) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit_user.php?id=<?= (int) $staff['id'] ?>" class="btn-edit">Edit</a>
                                        <a href="reset_password.php?id=<?= (int) $staff['id'] ?>" class="btn-reset">Reset</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="table-responsive" style="margin-bottom: var(--spacing-xl);">
                <div class="empty-state">
                    <h3>No Staff Yet</h3>
                    <p>Add staff using the form above to help manage the store.</p>
                </div>
            </div>
        <?php endif; ?>

        <h2 class="dashboard-title">All Accounts</h2>

        <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['fullname']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['username']) ?></td>
                                <td>
                                    <span
                                        class="badge <?= $row['role'] === 'admin' ? 'badge-admin' : ($row['role'] === 'staff' ? 'role-staff' : 'badge-user') ?>">
                                        <?= ucfirst($row['role']) ?>
                                    </span>
                                </td>
                                <td><?= $row['date_registered'] ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit_user.php?id=<?= $row['id'] ?>" class="btn-edit">Edit</a>
                                        <a href="reset_password.php?id=<?= $row['id'] ?>" class="btn-reset">Reset</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <div class="empty-state">
                    <h3>No Users Yet</h3>
                    <p>There are no registered users in the system.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

</body>

</html>

<?php $conn->close();
?>