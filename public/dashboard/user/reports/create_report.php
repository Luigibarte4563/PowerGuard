<!DOCTYPE html>
<html>
<head>
    <title>PowerGuide Outage System</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }

        form {
            max-width: 400px;
            padding: 15px;
            border: 1px solid #ddd;
            margin-bottom: 20px;
        }

        input, select, textarea, button {
            width: 100%;
            margin-top: 8px;
            padding: 8px;
        }

        #map {
            height: 400px;
            margin-top: 20px;
        }

        #response {
            margin-top: 10px;
            font-weight: bold;
        }
    </style>
</head>

<body>

<?php
session_start();
require_once __DIR__ . '/../../../../src/middleware/requireAuth.php';
$user = requireAuth();
?>

<h2>Welcome, <?= htmlspecialchars($user['name']) ?></h2>

<!-- FORM -->
<form id="outageForm">

    <input type="text" id="location_name" placeholder="Location Name" required>

    <select id="category">
        <option value="power_outage">Power Outage</option>
        <option value="low_voltage">Low Voltage</option>
        <option value="power_fluctuation">Power Fluctuation</option>
        <option value="transformer_explosion">Transformer Explosion</option>
        <option value="fallen_power_line">Fallen Power Line</option>
        <option value="electrical_fire">Electrical Fire</option>
        <option value="scheduled_maintenance">Maintenance</option>
        <option value="unknown_issue">Unknown</option>
    </select>

    <select id="severity">
        <option value="minor">Minor</option>
        <option value="moderate" selected>Moderate</option>
        <option value="critical">Critical</option>
    </select>

    <input type="number" id="affected_houses" value="1" min="1">

    <select id="hazard_type">
        <option value="none">None</option>
        <option value="fire">Fire Risk</option>
        <option value="smoke">Smoke</option>
        <option value="sparks">Sparks</option>
        <option value="fallen_wire">Fallen Wire</option>
        <option value="explosion_sound">Explosion Sound</option>
    </select>

    <textarea id="description" placeholder="Description" required></textarea>

    <!-- hidden coords -->
    <input type="hidden" id="latitude">
    <input type="hidden" id="longitude">

    <button type="button" onclick="useCurrentLocation()">Use My Location</button>
    <button type="submit">Submit Report</button>

</form>

<p id="response"></p>

<div id="map"></div>

<script>

/* =========================
   MAP INIT
========================= */
const map = L.map('map').setView([16.0431, 120.3330], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap'
}).addTo(map);

const icon = L.icon({
    iconUrl: 'https://cdn-icons-png.flaticon.com/512/355/355980.png',
    iconSize: [35, 35],
    iconAnchor: [17, 35]
});

let marker;
let layerGroup = L.layerGroup().addTo(map);

/* =========================
   GET LOCATION
========================= */
function useCurrentLocation() {
    navigator.geolocation.getCurrentPosition(async (pos) => {

        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;

        setLocation(lat, lng, "My Location");

    }, () => {
        alert("Location access denied");
    });
}

/* =========================
   MAP CLICK
========================= */
map.on('click', async (e) => {
    setLocation(e.latlng.lat, e.latlng.lng, "Pinned Location");
});

/* =========================
   SET LOCATION (COMMON)
========================= */
async function setLocation(lat, lng, label) {

    document.getElementById("latitude").value = lat;
    document.getElementById("longitude").value = lng;

    if (marker) map.removeLayer(marker);

    marker = L.marker([lat, lng], { icon })
        .addTo(map)
        .bindPopup(label)
        .openPopup();

    map.setView([lat, lng], 16);

    try {
        const res = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`
        );

        const data = await res.json();

        document.getElementById("location_name").value =
            data.display_name || `${lat}, ${lng}`;

    } catch (err) {
        document.getElementById("location_name").value = `${lat}, ${lng}`;
    }
}

/* =========================
   SUBMIT REPORT
========================= */
document.getElementById("outageForm").addEventListener("submit", async (e) => {
    e.preventDefault();

    const payload = {
        location_name: document.getElementById("location_name").value,
        category: document.getElementById("category").value,
        severity: document.getElementById("severity").value,
        description: document.getElementById("description").value,
        affected_houses: parseInt(document.getElementById("affected_houses").value),
        hazard_type: document.getElementById("hazard_type").value,
        started_at: null,
        image_proof: null,
        latitude: document.getElementById("latitude").value || null,
        longitude: document.getElementById("longitude").value || null
    };

    try {
        const res = await fetch(
            "http://localhost/crowdsourcedapi/api/outage_report/create.php",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(payload)
            }
        );

        const result = await res.json();

        document.getElementById("response").innerText = result.message;

        if (result.success) {
            document.getElementById("outageForm").reset();
            if (marker) map.removeLayer(marker);
            loadReports();
        }

    } catch (err) {
        console.error(err);
        document.getElementById("response").innerText = "Submission failed";
    }
});

/* =========================
   LOAD REPORTS
========================= */
async function loadReports() {

    try {
        const res = await fetch(
            "http://localhost/crowdsourcedapi/api/outage_report/get.php"
        );

        const result = await res.json();

        if (!result.success) return;

        layerGroup.clearLayers();

        result.data.forEach(report => {

            if (!report.latitude || !report.longitude) return;

            const m = L.marker([report.latitude, report.longitude], { icon });

            m.bindPopup(`
                <b>${report.location_name}</b><br><br>

                <b>Category:</b> ${report.category}<br>
                <b>Severity:</b> ${report.severity}<br>
                <b>Status:</b> ${report.status}<br>
                <b>Affected:</b> ${report.affected_houses}<br>
                <b>Hazard:</b> ${report.hazard_type}<br><br>

                <b>Description:</b><br>
                ${report.description}
            `);

            layerGroup.addLayer(m);
        });

    } catch (err) {
        console.error("Load error:", err);
    }
}

loadReports();
setInterval(loadReports, 10000);

</script>

</body>
</html>