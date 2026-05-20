<?php

session_start();

/* =========================
   DEBUGGING (TEMPORARY)
========================= */
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* =========================
   AUTOLOAD + CONFIG
========================= */
require_once __DIR__ . '/../../../vendor/autoload.php';

use Firebase\JWT\JWT;

require_once __DIR__ . '/../../../src/config/connection.php';
require_once __DIR__ . '/../../../src/config/app.php';
require_once __DIR__ . '/../../../src/config/env.php';

/* =========================
   DATABASE
========================= */
$conn = getConnection();

$secret_key = $_ENV['JWT_SECRET_KEY'];

/* =========================
   REQUEST CHECK
========================= */
if ($_SERVER['REQUEST_METHOD'] !== "POST") {
    header("Location: " . PUBLIC_URL . "/auth/auth.php?page=login");
    exit;
}

/* =========================
   INPUT
========================= */
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($email) || empty($password)) {

    header("Location: " . PUBLIC_URL . "/auth/auth.php?page=login&error=Missing credentials");
    exit;
}

try {

    /* =========================
       FIND USER
    ========================= */
    $stmt = $conn->prepare("
        SELECT *
        FROM users
        WHERE email = ?
        AND auth_provider = 'local'
        LIMIT 1
    ");

    $stmt->execute([$email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    /* =========================
       USER NOT FOUND
    ========================= */
    if (!$user) {

        header("Location: " . PUBLIC_URL . "/auth/auth.php?page=login&error=User not found");
        exit;
    }

    /* =========================
       PASSWORD CHECK
    ========================= */
    if (!password_verify($password, $user['password'])) {

        header("Location: " . PUBLIC_URL . "/auth/auth.php?page=login&error=Invalid credentials");
        exit;
    }

    /* =========================
       UPDATE LAST LOGIN
    ========================= */
    $stmt = $conn->prepare("
        UPDATE users
        SET last_login = NOW()
        WHERE id = ?
    ");

    $stmt->execute([$user['id']]);

    /* =========================
       SESSION
    ========================= */
    $_SESSION['user'] = [
        "id" => $user['id'],
        "email" => $user['email'],
        "role" => $user['role']
    ];

    /* =========================
       JWT PAYLOAD
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
        "secure" => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'
    ]);

    /* =========================
       ROLE REDIRECT
    ========================= */
    if ($user['role'] === "admin") {

        header("Location: " . PUBLIC_URL . "/dashboard/admin/dashboard.php");

    } elseif ($user['role'] === "electric_company") {

        header("Location: " . PUBLIC_URL . "/dashboard/electric/dashboard.php");

    } else {

        header("Location: " . PUBLIC_URL . "/dashboard/user/user.php");
    }

    exit;

} catch (Exception $e) {

    header("Location: " . PUBLIC_URL . "/auth/auth.php?page=login&error=" . urlencode($e->getMessage()));
    exit;
}
?>