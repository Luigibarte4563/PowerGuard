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

        #map{
            height:400px;
            margin-top:20px;
        }

        .card{
            border:1px solid #ddd;
            padding:10px;
            margin-top:10px;
            border-radius:8px;
            background:#f9f9f9;
        }

        .title{
            font-weight:bold;
            font-size:16px;
        }

        #status{
            font-weight:bold;
            margin-bottom:10px;
        }
    </style>
</head>

<body>

<h2>My Power Stations</h2>

<p id="status">Loading stations...</p>

<div id="map"></div>

<h3>Station List</h3>
<div id="list"></div>

<script>

/* ================= MAP INIT ================= */
let map = L.map('map').setView([16.0431, 120.3330], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: "© OpenStreetMap"
}).addTo(map);

/* ICONS */
const stationIcon = L.icon({
    iconUrl: 'https://cdn-icons-png.flaticon.com/512/252/252025.png',
    iconSize: [35, 35],
    iconAnchor: [17, 35]
});

const userIcon = L.icon({
    iconUrl: 'https://cdn-icons-png.flaticon.com/512/64/64113.png',
    iconSize: [35, 35],
    iconAnchor: [17, 35]
});

/* LAYERS */
let stationLayer = L.layerGroup().addTo(map);
let userMarker = null;

/* ================= USER LOCATION (PIN) ================= */
function loadUserLocation(){

    if (!navigator.geolocation) return;

    navigator.geolocation.getCurrentPosition(
        (pos) => {

            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;

            if(userMarker) map.removeLayer(userMarker);

            userMarker = L.marker([lat, lng], { icon: userIcon })
                .addTo(map)
                .bindPopup("📍 Your Location")
                .openPopup();

            map.setView([lat, lng], 14);
        },
        (err) => {
            console.log("Geolocation denied or failed:", err.message);
        }
    );
}

/* ================= LOAD STATIONS ================= */
async function loadStations(){

    try {

        const res = await fetch(
            "http://localhost/crowdsourcedapi/api/power_station/get.php",
            { credentials: "include" }
        );

        const result = await res.json();

        console.log("API RESULT:", result);

        if(!result.success){
            document.getElementById("status").innerText = "Failed to load stations";
            return;
        }

        const stations = result.data || [];

        document.getElementById("status").innerText =
            `Total Stations: ${result.count}`;

        /* CLEAR OLD DATA */
        stationLayer.clearLayers();

        /* LIST */
        document.getElementById("list").innerHTML = stations.map(s => `
            <div class="card">
                <div class="title">${s.station_name}</div>
                <div>${s.location_name}</div>

                <small>
                    Type: ${s.station_type} <br>
                    Status: ${s.availability_status} <br>
                    Access: ${s.access_type}
                </small>

                <p>${s.description ?? ""}</p>
            </div>
        `).join("");

        /* MAP MARKERS */
        let bounds = [];

        stations.forEach(s => {

            const lat = parseFloat(s.latitude);
            const lng = parseFloat(s.longitude);

            if(!isNaN(lat) && !isNaN(lng)){

                const marker = L.marker([lat, lng], { icon: stationIcon });

                marker.bindPopup(`
                    <b>${s.station_name}</b><br>
                    Type: ${s.station_type}<br>
                    Status: ${s.availability_status}<br>
                    Location: ${s.location_name}
                `);

                stationLayer.addLayer(marker);
                bounds.push([lat, lng]);
            }
        });

        if(bounds.length > 0){
            map.fitBounds(bounds, { padding: [50, 50] });
        }

    } catch(err){
        console.error("Load error:", err);
        document.getElementById("status").innerText = "Server error";
    }
}

/* ================= INIT ================= */
loadUserLocation();
loadStations();

/* AUTO REFRESH */
setInterval(loadStations, 10000);

</script>

</body>
</html>