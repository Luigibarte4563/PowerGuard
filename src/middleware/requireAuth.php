<?php

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/env.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function requireAuth($debug = false) {

    $conn = getConnection();

    // ✅ FIX: use env instead of hardcoded key
    $secret_key = $_ENV['JWT_SECRET_KEY'] ?? null;

    if (!$secret_key) {
        http_response_code(500);
        exit(json_encode([
            "success" => false,
            "message" => "JWT secret missing in .env"
        ]));
    }

    $user = null;
    $jwtError = null;

    /* =========================
       1. JWT AUTH (PRIMARY)
    ========================= */
    if (isset($_COOKIE['jwt_token'])) {

        try {

            $decoded = JWT::decode(
                $_COOKIE['jwt_token'],
                new Key($secret_key, 'HS256')
            );

            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$decoded->id]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            $jwtError = $e->getMessage();
            $user = null;
        }
    }

    /* =========================
       2. SESSION FALLBACK
    ========================= */
    if (!$user && isset($_SESSION['user']['id'])) {

        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user']['id']]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* =========================
       3. BLOCK IF NO USER
    ========================= */
    if (!$user) {

        http_response_code(401);

        $response = [
            "success" => false,
            "message" => "Unauthorized"
        ];

        // 🔍 DEBUG MODE
        if ($debug) {
            $response["debug"] = [
                "jwt_error" => $jwtError,
                "jwt_cookie_exists" => isset($_COOKIE['jwt_token']),
                "session_exists" => isset($_SESSION['user'])
            ];
        }

        header("Content-Type: application/json");
        echo json_encode($response);
        exit;
    }

    return $user;
}