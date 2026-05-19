<?php

header("Content-Type: application/json");

session_start();

/* =========================================
   CHECK SESSION
========================================= */
if (
    !isset($_SESSION['user']['id']) ||
    empty($_SESSION['user']['id'])
) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

/* =========================================
   API URL
========================================= */
$url = "http://localhost/crowdsourced-outage-reporting-api/api/outage_report/get_my_reports.php";

/* =========================================
   INIT CURL
========================================= */
$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json"
    ],

    /* IMPORTANT: keep session alive */
    CURLOPT_COOKIE => session_name() . "=" . session_id()
]);

$response = curl_exec($ch);

/* =========================================
   ERROR HANDLING
========================================= */
if (curl_errno($ch)) {

    echo json_encode([
        "success" => false,
        "message" => "CURL Error: " . curl_error($ch)
    ]);

    exit;
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

/* =========================================
   OUTPUT
========================================= */
http_response_code($httpCode);
echo $response;