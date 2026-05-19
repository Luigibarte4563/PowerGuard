<?php

require_once __DIR__ . '/../../../src/config/env.php';

/* =========================================
   API ENDPOINT
========================================= */
$url = "http://localhost/crowdsourced-outage-reporting-api/api/outage_report/get.php";

/* =========================================
   CURL REQUEST
========================================= */
$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPGET, true);

$response = curl_exec($ch);

curl_close($ch);

/* =========================================
   OUTPUT RAW API RESPONSE
========================================= */
header("Content-Type: application/json");

echo $response;