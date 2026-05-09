<!DOCTYPE html>
<html>
<head>
    <title>My Power Stations</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <style>
        body{
            font-family: Arial;
            padding:20px;
        }

        #map{
            height:400px;
            margin-bottom:20px;
        }

        .card{
            border:1px solid #ddd;
            padding:10px;
            margin-bottom:10px;
            border-radius:8px;
            background:#f9f9f9;
        }

        .title{
            font-weight:bold;
            font-size:16px;
        }

        button{
            padding:6px 10px;
            margin-top:5px;
            cursor:pointer;
        }

        .delete-btn{
            background:#e74c3c;
            color:white;
            border:none;
        }

        .empty{
            padding:10px;
            color:#777;
        }
    </style>
</head>

<body>

<h2>My Power Stations</h2>

<p id="status">Loading...</p>

<div id="map"></div>

<h3>Stations List</h3>
<div id="list"></div>

<script>

/* ================= MAP ================= */
let map = L.map('map').setView([16.0431, 120.3330], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: "© OpenStreetMap"
}).addTo(map);

const icon = L.icon({
    iconUrl: 'https://cdn-icons-png.flaticon.com/512/252/252025.png',
    iconSize: [35, 35],
    iconAnchor: [17, 35]
});

let layerGroup = L.layerGroup().addTo(map);

/* ================= LOAD STATIONS ================= */
async function loadStations(){

    document.getElementById("status").innerText = "Loading...";

    try {

        const res = await fetch(
            "http://localhost/crowdsourcedapi/api/power_station/get_my_posts.php",
            { credentials: "include" }
        );

        const result = await res.json();

        if(!result.success){
            document.getElementById("status").innerText = "Failed to load stations";
            return;
        }

        const stations = result.data || [];

        document.getElementById("status").innerText =
            `Total Stations: ${stations.length}`;

        /* CLEAR MAP */
        layerGroup.clearLayers();

        /* EMPTY STATE */
        if(stations.length === 0){
            document.getElementById("list").innerHTML =
                `<div class="empty">No stations found</div>`;
            return;
        }

        /* LIST UI */
        document.getElementById("list").innerHTML = stations.map(s => `

            <div class="card">

                <div class="title">${s.station_name}</div>

                <div>${s.location_name}</div>

                <small>
                    Type: ${s.station_type} <br>
                    Status: ${s.availability_status}
                </small>

                <p>${s.description || ""}</p>

                <button class="delete-btn" onclick="deleteStation(${s.id})">
                    Delete
                </button>

            </div>

        `).join("");

        /* MAP MARKERS */
        let bounds = [];

        stations.forEach(s => {

            const lat = parseFloat(s.latitude);
            const lng = parseFloat(s.longitude);

            if(!Number.isFinite(lat) || !Number.isFinite(lng)) return;

            const marker = L.marker([lat, lng], { icon });

            marker.bindPopup(`
                <b>${s.station_name}</b><br>
                ${s.station_type}<br>
                ${s.availability_status}<br>
                ${s.location_name}
            `);

            layerGroup.addLayer(marker);
            bounds.push([lat, lng]);
        });

        if(bounds.length > 0){
            map.fitBounds(bounds, { padding: [50, 50] });
        }

    } catch(err){
        console.error("Load error:", err);
        document.getElementById("status").innerText = "Server error";
    }
}

/* ================= DELETE STATION ================= */
async function deleteStation(id){

    if(!id || !confirm("Delete this station?")) return;

    try {

        const res = await fetch(
            "http://localhost/crowdsourcedapi/api/power_station/delete.php",
            {
                method: "POST",
                credentials: "include",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ id })
            }
        );

        const result = await res.json();

        alert(result.message);

        if(result.success){
            loadStations();
        }

    } catch(err){
        console.error("Delete error:", err);
        alert("Delete failed");
    }
}

/* ================= INIT ================= */
loadStations();
setInterval(loadStations, 10000);

</script>

</body>
</html>