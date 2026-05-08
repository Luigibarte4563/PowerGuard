<?php

session_start();

require '../../../vendor/autoload.php';

use Firebase\JWT\JWT;

require_once __DIR__ . '/../../../src/config/connection.php';
require_once __DIR__ . '/../../../src/config/app.php';
require_once __DIR__ . '/../../../src/config/env.php';

$conn = getConnection();
$secret_key = $_ENV['JWT_SECRET_KEY'];

if ($_SERVER['REQUEST_METHOD'] === "POST") {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("
        SELECT * FROM users
        WHERE email = ?
        AND auth_provider = 'local'
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header("Location: " . BASE_URL . "/auth/auth.php?page=login&error=User not found");
        exit;
    }

    if ($user['account_status'] !== 'active') {
        header("Location: " . BASE_URL . "/auth/auth.php?page=login&error=Account disabled");
        exit;
    }

    if (!password_verify($password, $user['password'])) {
        header("Location: " . BASE_URL . "/auth/auth.php?page=login&error=Invalid credentials");
        exit;
    }

    /* =========================
       UPDATE LAST LOGIN
    ========================= */
    $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")
         ->execute([$user['id']]);

    /* =========================
       JWT PAYLOAD (STANDARDIZED)
    ========================= */
    $payload = [
        "id" => $user['id'],
        "email" => $user['email'],
        "role" => $user['role'],
        "auth_provider" => $user['auth_provider'],

        "iat" => time(),
        "exp" => time() + 3600,

        // 🔥 future refresh support
        "type" => "access"
    ];

    $jwt = JWT::encode($payload, $secret_key, 'HS256');

    /* =========================
       SECURE COOKIE SETUP
    ========================= */
    setcookie("jwt_token", $jwt, [
        "expires" => time() + 3600,
        "path" => "/",
        "httponly" => true,
        "samesite" => "Lax",
        "secure" => isset($_SERVER['HTTPS']) // auto HTTPS detection
    ]);

    /* =========================
       SESSION (OPTIONAL BACKUP)
    ========================= */
    $_SESSION['user'] = [
        "id" => $user['id'],
        "role" => $user['role']
    ];

    /* =========================
       ROLE REDIRECT FIXED
    ========================= */
    if ($user['role'] === "admin") {
        header("Location: " . BASE_URL . "/dashboard/admin/dashboard.php");
    } elseif ($user['role'] === "electric_company") {
        header("Location: " . BASE_URL . "/dashboard/electric/dashboard.php");
    } else {
        header("Location: " . BASE_URL . "/dashboard/user/user.php");
    }

    exit;
}