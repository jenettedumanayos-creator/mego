<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: menu.html#order');
    exit();
}

$customerName = trim((string) ($_POST['customer_name'] ?? ''));
$contactNumber = trim((string) ($_POST['contact_number'] ?? ''));
$productName = trim((string) ($_POST['product_name'] ?? ''));
$size = trim((string) ($_POST['size'] ?? ''));
$quantity = (int) ($_POST['quantity'] ?? 0);
$orderNotes = trim((string) ($_POST['order_notes'] ?? ''));

if (
    $customerName === '' ||
    $contactNumber === '' ||
    $productName === '' ||
    $size === '' ||
    $quantity < 1
) {
    header('Location: menu.html#order');
    exit();
}

$orderFilePath = __DIR__ . DIRECTORY_SEPARATOR . 'orders.txt';
$orderTime = date('Y-m-d H:i:s');
$orderLine = implode("\t", [
    $orderTime,
    str_replace(["\t", "\n", "\r"], ' ', $customerName),
    str_replace(["\t", "\n", "\r"], ' ', $contactNumber),
    str_replace(["\t", "\n", "\r"], ' ', $productName),
    str_replace(["\t", "\n", "\r"], ' ', $size),
    (string) $quantity,
    str_replace(["\t", "\n", "\r"], ' ', $orderNotes)
]) . PHP_EOL;

file_put_contents($orderFilePath, $orderLine, FILE_APPEND | LOCK_EX);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: Arial, sans-serif;
            background: #f5ede0;
            color: #2c1f17;
        }

        .card {
            width: min(92%, 520px);
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(44, 31, 23, 0.15);
            padding: 26px 22px;
        }

        h1 {
            margin-top: 0;
            margin-bottom: 8px;
            font-size: 1.8rem;
        }

        p {
            margin: 8px 0;
            line-height: 1.5;
        }

        a {
            display: inline-block;
            margin-top: 14px;
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 8px;
            color: #fff;
            background: #2c1f17;
            font-weight: 700;
        }
    </style>
</head>

<body>
    <div class="card">
        <h1>Order Received</h1>
        <p>Thank you, <?php echo htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8'); ?>.</p>
        <p>Your order for <?php echo (int) $quantity; ?>
            <?php echo htmlspecialchars($productName, ENT_QUOTES, 'UTF-8'); ?>
            (<?php echo htmlspecialchars($size, ENT_QUOTES, 'UTF-8'); ?>) has been submitted.
        </p>
        <p>We will contact you at <?php echo htmlspecialchars($contactNumber, ENT_QUOTES, 'UTF-8'); ?>.</p>
        <a href="menu.html#menu">Back to Menu</a>
    </div>
</body>

</html>