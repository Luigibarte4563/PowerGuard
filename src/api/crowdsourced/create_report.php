<?php

session_start();
require_once __DIR__ . '/../../../src/config/env.php';

/* =========================================
   CHECK SESSION (OK)
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
$url = "http://localhost/crowdsourced-outage-reporting-api/api/outage_report/create.php";

/* =========================================
   INPUT
========================================= */
$location_name = $_POST['location_name'] ?? null;
$category      = $_POST['category'] ?? "power_outage";
$severity      = $_POST['severity'] ?? "moderate";
$image_proof     = $data["image_proof"] ?? null;
$description   = $_POST['description'] ?? null;

$affected_houses = $_POST['affected_houses'] ?? 1;
$is_active       = $_POST['is_active'] ?? "yes";
$hazard_type     = $_POST['hazard_type'] ?? "none";
$started_at      = $_POST['started_at'] ?? null;

/* =========================================
   VALIDATION
========================================= */
if (!$location_name || !$description) {
    echo json_encode([
        "success" => false,
        "message" => "location_name and description are required"
    ]);
    exit;
}

/* =========================================
   BUILD DATA (NO USER_ID!)
========================================= */
$data = [
    "location_name" => $location_name,
    "category" => $category,
    "severity" => $severity,
    "image_proof" => $image_proof,
    "description" => $description,
    "affected_houses" => (int)$affected_houses,
    "is_active" => $is_active,
    "hazard_type" => $hazard_type,
    "started_at" => $started_at,
    "image_proof" => null
];

/* =========================================
   CURL REQUEST (SESSION PASSED)
========================================= */
$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);

/* IMPORTANT: pass session cookie */
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);

curl_close($ch);

echo $response;