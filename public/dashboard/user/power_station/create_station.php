<!DOCTYPE html>
<html>
<head>
    <title>Power Stations System</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <style>
        body{
            font-family: Arial;
            padding:20px;
        }

        form{
            display:grid;
            gap:10px;
            max-width:400px;
            margin-bottom:20px;
        }

        input, select, textarea, button{
            padding:10px;
            border:1px solid #ccc;
            border-radius:5px;
        }

        button{
            background:#2ecc71;
            color:#fff;
            cursor:pointer;
        }

        button:hover{
            background:#27ae60;
        }

        #map{
            height:400px;
            margin-top:20px;
        }

        .card{
            border:1px solid #ddd;
            padding:10px;
            margin-top:10px;
            border-radius:8px;
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

<!-- ================= CREATE FORM ================= -->
<form id="stationForm">

    <input type="text" id="station_name" placeholder="Station Name" required>
    <input type="text" id="location_name" placeholder="Location Name" required>

    <select id="station_type">
        <option value="power_station">Power Station</option>
        <option value="solar_station">Solar Station</option>
        <option value="charging_station">Charging Station</option>
        <option value="generator_station">Generator Station</option>
    </select>

    <select id="access_type">
        <option value="free">Free</option>
        <option value="paid">Paid</option>
    </select>

    <select id="availability_status">
        <option value="available">Available</option>
        <option value="busy">Busy</option>
        <option value="offline">Offline</option>
        <option value="maintenance">Maintenance</option>
    </select>

    <input type="text" id="operating_hours" placeholder="Operating Hours">
    <input type="text" id="charging_type" placeholder="Charging Type">
    <textarea id="description" placeholder="Description"></textarea>

    <input type="hidden" id="latitude">
    <input type="hidden" id="longitude">

    <button type="button" onclick="useCurrentLocation()">Use My Location</button>
    <button type="submit">Create Station</button>

</form>

<p id="response"></p>

<!-- ================= MAP ================= -->
<div id="map"></div>

<h3>My Stations</h3>
<div id="list"></div>

<script>

/* ================= MAP INIT ================= */
let map = L.map('map').setView([16.0431, 120.3330], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

const icon = L.icon({
    iconUrl: 'https://cdn-icons-png.flaticon.com/512/252/252025.png',
    iconSize: [35, 35],
    iconAnchor: [17, 35]
});

let marker;
let stationLayer = L.layerGroup().addTo(map);

/* ================= GEOLOCATION ================= */
function useCurrentLocation(){

    navigator.geolocation.getCurrentPosition(async (pos) => {

        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;

        document.getElementById("latitude").value = lat;
        document.getElementById("longitude").value = lng;

        if(marker) map.removeLayer(marker);

        marker = L.marker([lat, lng], { icon })
            .addTo(map)
            .bindPopup("Your Location")
            .openPopup();

        map.setView([lat, lng], 16);

        try {
            const res = await fetch(
                `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`
            );

            const data = await res.json();
            document.getElementById("location_name").value = data.display_name;

        } catch (e) {
            console.log("Geocode error", e);
        }

    });
}

/* ================= MAP CLICK ================= */
map.on('click', async function(e){

    const lat = e.latlng.lat;
    const lng = e.latlng.lng;

    document.getElementById("latitude").value = lat;
    document.getElementById("longitude").value = lng;

    if(marker) map.removeLayer(marker);

    marker = L.marker([lat, lng], { icon })
        .addTo(map)
        .bindPopup("Selected Location")
        .openPopup();

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
});

/* ================= CREATE STATION ================= */
document.getElementById("stationForm").addEventListener("submit", async (e) => {
    e.preventDefault();

    const payload = {
        station_name: document.getElementById("station_name").value,
        location_name: document.getElementById("location_name").value,

        station_type: document.getElementById("station_type").value,
        access_type: document.getElementById("access_type").value,
        availability_status: document.getElementById("availability_status").value,

        operating_hours: document.getElementById("operating_hours").value,
        charging_type: document.getElementById("charging_type").value,
        description: document.getElementById("description").value
    };

    try {

        const res = await fetch(
            "http://localhost/crowdsourcedapi/api/power_station/create.php",
            {
                method: "POST",
                credentials: "include",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            }
        );

        const result = await res.json();

        document.getElementById("response").innerText = result.message;

        if(result.success){
            e.target.reset();
            loadStations();
        }

    } catch (err) {
        console.error(err);
        document.getElementById("response").innerText = "Error creating station";
    }
});

/* ================= LOAD STATIONS ================= */
async function loadStations(){

    try {

        const res = await fetch(
            "http://localhost/crowdsourcedapi/api/power_station/get.php",
            { credentials: "include" }
        );

        const result = await res.json();

        if(!result.success) return;

        stationLayer.clearLayers();

        document.getElementById("list").innerHTML =
            result.data.map(s => `
                <div class="card">
                    <b>${s.station_name}</b><br>
                    ${s.location_name}<br>
                    Type: ${s.station_type}<br>
                    Status: ${s.availability_status}
                </div>
            `).join("");

        result.data.forEach(s => {

            if(s.latitude && s.longitude){

                const m = L.marker([s.latitude, s.longitude], { icon });

                m.bindPopup(`
                    <b>${s.station_name}</b><br>
                    ${s.station_type}<br>
                    ${s.availability_status}<br>
                    ${s.location_name}
                `);

                stationLayer.addLayer(m);
            }

        });

    } catch(err){
        console.error("Load error", err);
    }
}

loadStations();
setInterval(loadStations, 10000);

</script>

</body>
</html>