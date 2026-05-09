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

        button{
            margin-top:5px;
            padding:5px 10px;
            cursor:pointer;
        }

        /* MODAL */
        .modal{
            display:none;
            position:fixed;
            top:0;
            left:0;
            width:100%;
            height:100%;
            background:rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content{
            background:#fff;
            padding:20px;
            width:500px;
            margin:5% auto;
            border-radius:10px;
        }

        .modal input, .modal select, .modal textarea{
            width:100%;
            margin-bottom:10px;
            padding:8px;
        }

        #editMap{
            height:250px;
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

<!-- ================= UPDATE MODAL ================= -->
<div class="modal" id="editModal">

    <div class="modal-content">

        <h3>Edit Station + Pin Location</h3>

        <input type="hidden" id="edit_id">

        <input type="text" id="edit_station_name" placeholder="Station Name">
        <input type="text" id="edit_location_name" placeholder="Location">

        <select id="edit_station_type">
            <option value="power_station">Power Station</option>
            <option value="solar_station">Solar Station</option>
            <option value="charging_station">Charging Station</option>
            <option value="generator_station">Generator Station</option>
        </select>

        <select id="edit_status">
            <option value="available">Available</option>
            <option value="busy">Busy</option>
            <option value="offline">Offline</option>
            <option value="maintenance">Maintenance</option>
        </select>

        <textarea id="edit_description" placeholder="Description"></textarea>

        <!-- 🔥 PIN LOCATION MAP -->
        <div id="editMap"></div>

        <input type="text" id="edit_lat" placeholder="Latitude" readonly>
        <input type="text" id="edit_lng" placeholder="Longitude" readonly>

        <button onclick="updateStation()">Update</button>
        <button onclick="closeModal()">Cancel</button>

    </div>

</div>

<script>

/* ================= MAIN MAP ================= */
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

/* ================= EDIT MAP ================= */
let editMap;
let editMarker;

/* ================= OPEN EDIT MODAL ================= */
function openEdit(s){

    document.getElementById("edit_id").value = s.id;
    document.getElementById("edit_station_name").value = s.station_name;
    document.getElementById("edit_location_name").value = s.location_name;
    document.getElementById("edit_station_type").value = s.station_type;
    document.getElementById("edit_status").value = s.availability_status;
    document.getElementById("edit_description").value = s.description ?? "";

    document.getElementById("edit_lat").value = s.latitude;
    document.getElementById("edit_lng").value = s.longitude;

    document.getElementById("editModal").style.display = "block";

    setTimeout(() => initEditMap(s.latitude, s.longitude), 200);
}

/* ================= INIT EDIT MAP ================= */
function initEditMap(lat, lng){

    if(editMap){
        editMap.remove();
    }

    editMap = L.map('editMap').setView([lat, lng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: "© OpenStreetMap"
    }).addTo(editMap);

    editMarker = L.marker([lat, lng], {
        draggable: true
    }).addTo(editMap);

    editMarker.on('dragend', function(e){
        const pos = editMarker.getLatLng();
        document.getElementById("edit_lat").value = pos.lat;
        document.getElementById("edit_lng").value = pos.lng;
    });

    editMap.on('click', function(e){
        editMarker.setLatLng(e.latlng);

        document.getElementById("edit_lat").value = e.latlng.lat;
        document.getElementById("edit_lng").value = e.latlng.lng;
    });
}

/* CLOSE MODAL */
function closeModal(){
    document.getElementById("editModal").style.display = "none";
}

/* ================= UPDATE STATION ================= */
async function updateStation(){

    const payload = {
        id: document.getElementById("edit_id").value,
        station_name: document.getElementById("edit_station_name").value,
        location_name: document.getElementById("edit_location_name").value,
        station_type: document.getElementById("edit_station_type").value,
        availability_status: document.getElementById("edit_status").value,
        description: document.getElementById("edit_description").value,

        // 🔥 NEW PIN LOCATION
        latitude: document.getElementById("edit_lat").value,
        longitude: document.getElementById("edit_lng").value
    };

    try {

        const res = await fetch(
            "http://localhost/crowdsourcedapi/api/power_station/update.php",
            {
                method: "POST",
                credentials: "include",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            }
        );

        const result = await res.json();

        alert(result.message);

        if(result.success){
            closeModal();
            loadStations();
        }

    } catch(err){
        console.error(err);
    }
}

/* ================= LOAD STATIONS ================= */
async function loadStations(){

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
            `Total Stations: ${result.count}`;

        layerGroup.clearLayers();

        document.getElementById("list").innerHTML = stations.map(s => `

            <div class="card">

                <div class="title">${s.station_name}</div>
                <div>${s.location_name}</div>

                <small>
                    Type: ${s.station_type} <br>
                    Status: ${s.availability_status}
                </small>

                <p>${s.description ?? ""}</p>

                <button onclick='openEdit(${JSON.stringify(s)})'>
                    Edit
                </button>

            </div>

        `).join("");

        let bounds = [];

        stations.forEach(s => {

            const lat = parseFloat(s.latitude);
            const lng = parseFloat(s.longitude);

            if(!isNaN(lat) && !isNaN(lng)){

                const marker = L.marker([lat, lng], { icon });

                marker.bindPopup(`
                    <b>${s.station_name}</b><br>
                    ${s.station_type}<br>
                    ${s.availability_status}<br>
                    ${s.location_name}
                `);

                layerGroup.addLayer(marker);

                bounds.push([lat, lng]);
            }
        });

        if(bounds.length > 0){
            map.fitBounds(bounds, { padding: [50, 50] });
        }

    } catch(err){
        console.error("Load error:", err);
    }
}

/* INIT */
loadStations();
setInterval(loadStations, 10000);

</script>

</body>
</html>