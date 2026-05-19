<?php

session_start();

/* =========================================
   CHECK SESSION
========================================= */
if (!isset($_SESSION['user']['id'])) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

/* =========================================
   API URL
========================================= */
$url = "http://localhost/crowdsourced-outage-reporting-api/api/outage_report/delete.php";

/* =========================================
   INPUT (FROM FRONTEND OR FORM)
========================================= */
$id = $_POST['id'] ?? null;

/* IMPORTANT: use session user id */
$user_id = $_SESSION['user']['id'];

/* =========================================
   VALIDATION
========================================= */
if (!$id) {
    echo json_encode([
        "success" => false,
        "message" => "Report ID is required"
    ]);
    exit;
}

/* =========================================
   BUILD PAYLOAD
========================================= */
$data = [
    "id" => (int)$id,
    "user_id" => (int)$user_id
];

/* =========================================
   CURL REQUEST
========================================= */
$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);

/* pass session */
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);

/* =========================================
   ERROR HANDLING
========================================= */
if (curl_errno($ch)) {
    echo json_encode([
        "success" => false,
        "message" => curl_error($ch)
    ]);
    curl_close($ch);
    exit;
}

curl_close($ch);

/* =========================================
   OUTPUT API RESPONSE
========================================= */
echo $response;