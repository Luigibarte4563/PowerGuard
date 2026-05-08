<?php

session_start();

require_once __DIR__ . '/../../../src/config/connection.php';
require '../../../vendor/autoload.php';

use Firebase\JWT\JWT;
require_once __DIR__ . '/../../../src/config/env.php';

$conn = getConnection();
$secret_key = $_ENV['JWT_SECRET_KEY'];

header('Content-Type: application/json');

/* =========================
   INPUT
========================= */
$name = $_POST['name'] ?? null;
$email = $_POST['email'] ?? null;
$picture = $_POST['picture'] ?? null;
$google_id = $_POST['sub'] ?? null;

if (!$email || !$google_id) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid Google data"
    ]);
    exit;
}

/* =========================
   FIND USER
========================= */
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

/* =========================
   CREATE USER IF NOT EXISTS
========================= */
if (!$user) {

    $stmt = $conn->prepare("
        INSERT INTO users (name, email, picture, google_id, auth_provider, account_status)
        VALUES (?, ?, ?, ?, 'google', 'active')
    ");

    $stmt->execute([$name, $email, $picture, $google_id]);

    $user_id = $conn->lastInsertId();

} else {
    $user_id = $user['id'];
}

/* =========================
   RELOAD FULL USER DATA
========================= */
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

/* =========================
   UPDATE LAST LOGIN
========================= */
$conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")
     ->execute([$user_id]);

/* =========================
   SESSION (LIGHTWEIGHT ONLY)
========================= */
$_SESSION['user'] = [
    "id" => $user['id'],
    "role" => $user['role']
];

/* =========================
   JWT PAYLOAD (STANDARDIZED)
========================= */
$payload = [
    "id" => $user['id'],
    "email" => $user['email'],
    "role" => $user['role'],
    "auth_provider" => "google",

    "iat" => time(),
    "exp" => time() + 3600,

    // 🔥 future refresh support
    "type" => "access"
];

$jwt = JWT::encode($payload, $secret_key, 'HS256');

/* =========================
   SECURE COOKIE
========================= */
setcookie("jwt_token", $jwt, [
    "expires" => time() + 3600,
    "path" => "/",
    "httponly" => true,
    "samesite" => "Lax",
    "secure" => isset($_SERVER['HTTPS'])
]);

/* =========================
   RESPONSE
========================= */
echo json_encode([
    "success" => true,
    "role" => $user['role']
]);