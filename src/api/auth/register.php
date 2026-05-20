<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../src/config/connection.php';
require_once __DIR__ . '/../../../src/config/app.php';
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../src/config/env.php';

use Firebase\JWT\JWT;

$conn = getConnection();
$secret_key = $_ENV['JWT_SECRET_KEY'];

/* =========================
   ONLY ALLOW POST
========================= */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . PUBLIC_URL . "/auth/auth.php?page=register");
    exit;
}

/* =========================
   GET FORM DATA
========================= */
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$passwordRaw = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

/* =========================
   VALIDATION
========================= */
if (!$name || !$email || !$passwordRaw || !$confirmPassword) {
    $_SESSION['register_error'] = "All fields are required.";
    header("Location: " . PUBLIC_URL . "/auth/auth.php?page=register");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['register_error'] = "Invalid email format.";
    header("Location: " . PUBLIC_URL . "/auth/auth.php?page=register");
    exit;
}

if ($passwordRaw !== $confirmPassword) {
    $_SESSION['register_error'] = "Passwords do not match.";
    header("Location: " . PUBLIC_URL . "/auth/auth.php?page=register");
    exit;
}

if (strlen($passwordRaw) < 6) {
    $_SESSION['register_error'] = "Password must be at least 6 characters.";
    header("Location: " . PUBLIC_URL . "/auth/auth.php?page=register");
    exit;
}

/* =========================
   CHECK EMAIL EXISTS
========================= */
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);

if ($stmt->fetch()) {
    $_SESSION['register_error'] = "Email already exists.";
    header("Location: " . PUBLIC_URL . "/auth/auth.php?page=register");
    exit;
}

/* =========================
   HASH PASSWORD
========================= */
$password = password_hash($passwordRaw, PASSWORD_BCRYPT);

/* =========================
   INSERT USER (FIXED ROLE SAFE DEFAULT)
========================= */
$stmt = $conn->prepare("
    INSERT INTO users (name, email, password, auth_provider, role, created_at)
    VALUES (?, ?, ?, 'local', 'user', NOW())
");

$success = $stmt->execute([$name, $email, $password]);

if (!$success) {
    $_SESSION['register_error'] = "Registration failed.";
    header("Location: " . PUBLIC_URL . "/auth/auth.php?page=register");
    exit;
}

/* =========================
   GET USER
========================= */
$userId = $conn->lastInsertId();

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['register_error'] = "User retrieval failed.";
    header("Location: " . PUBLIC_URL . "/auth/auth.php?page=register");
    exit;
}

/* =========================
   JWT TOKEN
========================= */
$payload = [
    "id" => $user['id'],
    "email" => $user['email'],
    "role" => $user['role'],
    "auth_provider" => $user['auth_provider'],
    "iat" => time(),
    "exp" => time() + 3600,
    "type" => "access"
];

$jwt = JWT::encode($payload, $secret_key, 'HS256');

/* =========================
   COOKIE
========================= */
setcookie("jwt_token", $jwt, [
    "expires" => time() + 3600,
    "path" => "/",
    "httponly" => true,
    "samesite" => "Lax",
    "secure" => false
]);

/* =========================
   SESSION
========================= */
$_SESSION['user'] = [
    "id" => $user['id'],
    "name" => $user['name'],
    "email" => $user['email'],
    "role" => $user['role'],
    "auth_provider" => $user['auth_provider']
];

/* =========================
   SUCCESS MESSAGE
========================= */
$_SESSION['success'] = "Registration successful.";

/* =========================
   REDIRECT
========================= */
if ($user['role'] === "electric_company") {
    header("Location: " . PUBLIC_URL . "/dashboard/electric/dashboard.php");
} else {
    header("Location: " . PUBLIC_URL . "/dashboard/user/user.php");
}

exit;