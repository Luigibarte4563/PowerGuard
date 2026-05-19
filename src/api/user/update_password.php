<?php

session_start();

require_once __DIR__ . '/../../../src/config/connection.php';
require_once __DIR__ . '/../../../src/config/app.php';

$conn = getConnection();

if (!isset($_SESSION['user'])) {

    header("Location: " . PUBLIC_URL . "/auth/auth.php?page=login");
    exit();
}

$user_id = $_SESSION['user']['id'];

$current = $_POST['current_password'] ?? '';
$new = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if (empty($current) || empty($new) || empty($confirm)) {

    header("Location: " . PUBLIC_URL . "/dashboard/user/user.php?error=empty_fields");
    exit();
}

if ($new !== $confirm) {

    header("Location: " . PUBLIC_URL . "/dashboard/user/user.php?error=password_mismatch");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!password_verify($current, $user['password'])) {

    header("Location: " . PUBLIC_URL . "/dashboard/user/user.php?error=wrong_password");
    exit();
}

$newHashed = password_hash($new, PASSWORD_BCRYPT);

$stmt = $conn->prepare("
    UPDATE users 
    SET password = ?
    WHERE id = ?
");

$stmt->execute([$newHashed, $user_id]);

header("Location: " . PUBLIC_URL . "/dashboard/user/user.php?success=password_updated");
exit();
?>