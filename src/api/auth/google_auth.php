<?php

session_start();

/* =========================
   DEBUGGING (TEMPORARY)
========================= */
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* =========================
   REQUIRED FILES
========================= */
require_once __DIR__ . '/../../../src/config/connection.php';
require_once __DIR__ . '/../../../src/config/app.php';
require_once __DIR__ . '/../../../src/config/env.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use Firebase\JWT\JWT;

/* =========================
   DATABASE CONNECTION
========================= */
$conn = getConnection();

header('Content-Type: application/json');

try {

    /* =========================
       SECRET KEY
    ========================= */
    $secret_key = $_ENV['JWT_SECRET_KEY'];

    /* =========================
       INPUT
    ========================= */
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $picture = trim($_POST['picture'] ?? '');
    $google_id = trim($_POST['sub'] ?? '');

    if (empty($email) || empty($google_id)) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid Google data"
        ]);
        exit;
    }

    /* =========================
       FIND USER
    ========================= */
    $stmt = $conn->prepare("
        SELECT * FROM users 
        WHERE email = ?
        LIMIT 1
    ");

    $stmt->execute([$email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    /* =========================
       CREATE USER IF NOT EXISTS
    ========================= */
    if (!$user) {

        $stmt = $conn->prepare("
            INSERT INTO users (
                name,
                email,
                picture,
                google_id,
                auth_provider,
                role
            )
            VALUES (
                ?, ?, ?, ?, 'google', 'user'
            )
        ");

        $stmt->execute([
            $name,
            $email,
            $picture,
            $google_id
        ]);

        $user_id = $conn->lastInsertId();

    } else {

        $user_id = $user['id'];

        /* =========================
           UPDATE GOOGLE DATA
        ========================= */
        $stmt = $conn->prepare("
            UPDATE users
            SET
                google_id = ?,
                picture = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $google_id,
            $picture,
            $user_id
        ]);
    }

    /* =========================
       RELOAD USER DATA
    ========================= */
    $stmt = $conn->prepare("
        SELECT * FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$user_id]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User loading failed");
    }

    /* =========================
       UPDATE LAST LOGIN
    ========================= */
    $stmt = $conn->prepare("
        UPDATE users
        SET last_login = NOW()
        WHERE id = ?
    ");

    $stmt->execute([$user_id]);

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
       SUCCESS RESPONSE
    ========================= */
    echo json_encode([
        "success" => true,
        "message" => "Login successful",
        "role" => $user['role']
    ]);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>