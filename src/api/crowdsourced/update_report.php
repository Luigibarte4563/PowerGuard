<?php

header("Content-Type: application/json");

session_start();

/* =========================================
   CHECK SESSION
========================================= */
$user_id = $_SESSION['user']['id'] ?? null;

if (!$user_id) {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);

    exit;
}

/* =========================================
   INPUT JSON
========================================= */
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !is_array($data)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON"
    ]);

    exit;
}

/* =========================================
   REQUIRED
========================================= */
$id = $data["id"] ?? null;

if (!$id) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Report ID is required"
    ]);

    exit;
}

/* =========================================
   BUILD CLEAN PAYLOAD (OPTIMIZED)
========================================= */

$fields = [
    "id",
    "location_name",
    "category",
    "severity",
    "description",
    "affected_houses",
    "status",
    "is_active",
    "hazard_type",
    "started_at",
    "latitude",
    "longitude"
];

$payload = [];

foreach ($fields as $field) {

    if (isset($data[$field])) {

        $value = $data[$field];

        /* skip empty strings */
        if ($value !== "" && $value !== null) {
            $payload[$field] = $value;
        }
    }
}

/* =========================================
   API URL
========================================= */
$url = "http://localhost/crowdsourced-outage-reporting-api/api/outage_report/update.php";

/* =========================================
   CURL REQUEST
========================================= */
$ch = curl_init($url);

curl_setopt_array($ch, [

    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,

    /* pass session */
    CURLOPT_COOKIE         => session_name() . '=' . session_id(),

    CURLOPT_HTTPHEADER     => [
        "Content-Type: application/json"
    ],

    CURLOPT_POSTFIELDS     => json_encode($payload)
]);

/* =========================================
   EXECUTE
========================================= */
$response = curl_exec($ch);

if (curl_errno($ch)) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => curl_error($ch)
    ]);

    curl_close($ch);
    exit;
}

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

/* =========================================
   RETURN RESPONSE
========================================= */
http_response_code($http_code);

echo $response;