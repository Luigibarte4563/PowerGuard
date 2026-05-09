<!DOCTYPE html>
<html>
<head>
    <title>Nearby Power Stations Navigation</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>

    <style>
        body { font-family: Arial; padding:20px; }
        #map { height:400px; margin-top:20px; }

        .card {
            border:1px solid #ddd;
            padding:10px;
            margin-top:10px;
            border-radius:8px;
            background:#f9f9f9;
            cursor:pointer;
        }

        .card:hover { background:#eef5ff; }

        .title { font-weight:bold; font-size:16px; }
        #status { font-weight:bold; margin-bottom:10px; }
        .controls { margin-bottom:10px; }

        #nearAlert {
            margin-top:10px;
            font-weight:bold;
            color:green;
        }
    </style>
</head>

<body>

<h2>📍 Navigation to Power Stations</h2>

<div class="controls">
    Radius:
    <select id="radius">
        <option value="1000">1 km</option>
        <option value="3000" selected>3 km</option>
        <option value="5000">5 km</option>
        <option value="10000">10 km</option>
    </select>

    <button onclick="loadStations()">Refresh</button>
</div>

<p id="status">Loading...</p>
<p id="nearAlert"></p>

<div id="map"></div>

<h3>Station List</h3>
<div id="list"></div>

<script>

/* ================= MAP ================= */
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

const pinIcon = L.icon({
    iconUrl: 'https://cdn-icons-png.flaticon.com/512/684/684908.png',
    iconSize: [35, 35],
    iconAnchor: [17, 35]
});

/* LAYERS */
let stationLayer = L.layerGroup().addTo(map);
let userMarker = null;
let pinMarker = null;
let routingControl = null;

/* USER + PIN LOCATION */
let userLat = null;
let userLng = null;
let pinLat = null;
let pinLng = null;

/* ================= DISTANCE ================= */
function getDistance(lat1, lon1, lat2, lon2) {

    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;

    const a =
        Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(lat1*Math.PI/180) *
        Math.cos(lat2*Math.PI/180) *
        Math.sin(dLon/2) * Math.sin(dLon/2);

    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

    return R * c;
}

/* ================= SAFE FETCH ================= */
async function safeFetch(url){

    try {
        const res = await fetch(url, { credentials: "include" });
        const text = await res.text();

        if (text.trim().startsWith("<")) {
            throw new Error("API returned HTML instead of JSON");
        }

        return JSON.parse(text);

    } catch (err) {
        return { success: false, message: err.message };
    }
}

/* ================= USER LOCATION ================= */
async function loadUserLocation(){

    const data = await safeFetch(
        "http://localhost/crowdsourcedapi/api/power_station/get_near_location.php"
    );

    if(!data.success){
        document.getElementById("status").innerText = data.message;
        return;
    }

    userLat = parseFloat(data.data.latitude);
    userLng = parseFloat(data.data.longitude);

    if(isNaN(userLat) || isNaN(userLng)) return;

    if(userMarker) map.removeLayer(userMarker);

    userMarker = L.marker([userLat, userLng], { icon: userIcon })
        .bindPopup("📍 Your Location")
        .addTo(map);

    map.setView([userLat, userLng], 14);
}

/* ================= PIN LOCATION ================= */
map.on("click", function(e){

    pinLat = e.latlng.lat;
    pinLng = e.latlng.lng;

    if(pinMarker) map.removeLayer(pinMarker);

    pinMarker = L.marker([pinLat, pinLng], {
        icon: pinIcon,
        draggable: true
    })
    .addTo(map)
    .bindPopup("📌 Selected Destination")
    .openPopup();

    pinMarker.on("dragend", function(ev){
        pinLat = ev.target.getLatLng().lat;
        pinLng = ev.target.getLatLng().lng;
    });

    document.getElementById("nearAlert").innerText =
        `📌 Pin selected: ${pinLat.toFixed(5)}, ${pinLng.toFixed(5)}`;
});

/* ================= ROUTE ================= */
function drawRoute(destLat, destLng, name){

    const startLat = userLat;
    const startLng = userLng;

    const endLat = destLat;
    const endLng = destLng;

    const km = getDistance(startLat, startLng, endLat, endLng);

    document.getElementById("nearAlert").innerText =
        km < 1
        ? `🚨 VERY NEAR ${name} (${km.toFixed(2)} km)`
        : `Distance to ${name}: ${km.toFixed(2)} km`;

    if(routingControl){
        map.removeControl(routingControl);
    }

    routingControl = L.Routing.control({
        waypoints: [
            L.latLng(startLat, startLng),
            L.latLng(endLat, endLng)
        ],
        lineOptions: { styles: [{ color: 'blue', weight: 5 }] },
        addWaypoints: false,
        draggableWaypoints: false,
        routeWhileDragging: false,
        createMarker: (i, wp) =>
            L.marker(wp.latLng, {
                icon: i === 0 ? userIcon : stationIcon
            })
    }).addTo(map);
}

/* ================= LOAD STATIONS ================= */
async function loadStations(){

    const radius = document.getElementById("radius").value;

    const result = await safeFetch(
        `http://localhost/crowdsourcedapi/api/power_station/get_near_location.php?radius=${radius}`
    );

    if(!result.success){
        document.getElementById("status").innerText = result.message;
        return;
    }

    const stations = result.data || [];

    document.getElementById("status").innerText =
        result.fallback
        ? `⚠ Showing ALL stations (${stations.length})`
        : `Found ${stations.length} stations`;

    stationLayer.clearLayers();

    document.getElementById("list").innerHTML = stations.map(s => {

        const km = userLat
            ? getDistance(userLat, userLng, s.latitude, s.longitude)
            : 0;

        return `
        <div class="card"
             onclick="drawRoute(${s.latitude}, ${s.longitude}, '${s.station_name}')">

            <div class="title">${s.station_name}</div>
            <div>${s.location_name}</div>

            <small>
                Type: ${s.station_type}<br>
                Status: ${s.availability_status}<br>
                Distance: ${km.toFixed(2)} km
            </small>

        </div>`;
    }).join("");

    let bounds = [];

    stations.forEach(s => {

        const lat = parseFloat(s.latitude);
        const lng = parseFloat(s.longitude);

        if(!isNaN(lat) && !isNaN(lng)){

            const marker = L.marker([lat, lng], { icon: stationIcon })
                .bindPopup(`
                    <b>${s.station_name}</b><br>
                    ${s.location_name}<br>
                    <button onclick="drawRoute(${lat},${lng},'${s.station_name}')">
                        Navigate
                    </button>
                `);

            stationLayer.addLayer(marker);
            bounds.push([lat, lng]);
        }
    });

    if(bounds.length) map.fitBounds(bounds);
}

/* ================= INIT ================= */
loadUserLocation();
loadStations();

setInterval(loadStations, 15000);

</script>

</body>
</html>