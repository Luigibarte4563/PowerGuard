<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<style>
.map-card{
    background:#fff;
    padding:15px;
    border-radius:12px;
    margin-top:20px;
    box-shadow:0 2px 8px rgba(0,0,0,0.1);
}

#maintenanceMap{
    width:100%;
    height:500px;
    border-radius:12px;
}

.map-popup{
    font-size:14px;
    line-height:1.5;
}

.map-popup h4{
    margin:0 0 8px;
}

.loading{
    padding:10px;
    font-size:14px;
    color:#666;
}
</style>

<div class="map-card">
    <h2>Upcoming Maintenance Map</h2>
    <div id="mapLoading" class="loading">Loading maintenance data...</div>
    <div id="maintenanceMap"></div>
</div>

<script>

/* ================= MAP INIT ================= */
let map;
let layersGroup = L.layerGroup();

function initMap() {

    map = L.map('maintenanceMap', {
        zoomControl: true,
        minZoom: 12,
        maxZoom: 15,
        maxBounds: [
            [15.95, 120.25], // SW boundary (Dagupan area)
            [16.15, 120.45]  // NE boundary
        ],
        maxBoundsViscosity: 1.0
    }).setView(
        [16.0431, 120.3330], // Dagupan center
        13
    );

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    layersGroup.addTo(map);
}

/* ================= BARANGAY COORDS ================= */
const barangayData = {
    "Bonuan Gueset": { lat:16.0585, lng:120.3345 },
    "Bonuan Boquig": { lat:16.0600, lng:120.3200 },
    "Bonuan Binloc": { lat:16.0620, lng:120.3100 },
    "Lucao": { lat:16.0435, lng:120.3310 },
    "Tapuac": { lat:16.0460, lng:120.3450 },
    "Tambac": { lat:16.0520, lng:120.3400 },
    "Pantal": { lat:16.0468, lng:120.3330 },
    "Bacayao Norte": { lat:16.0300, lng:120.3200 },
    "Bacayao Sur": { lat:16.0250, lng:120.3250 },
    "Malued": { lat:16.0400, lng:120.3200 },
    "Mayombo": { lat:16.0480, lng:120.3100 },
    "Mangin": { lat:16.0550, lng:120.3500 },
    "Tebeng": { lat:16.0600, lng:120.3450 },
    "Pogo Chico": { lat:16.0510, lng:120.3600 },
    "Pogo Grande": { lat:16.0550, lng:120.3650 },
    "Herrero": { lat:16.0450, lng:120.3350 },
    "Poblacion Centro": { lat:16.0430, lng:120.3335 },
    "Poblacion Oeste": { lat:16.0410, lng:120.3300 },
    "Poblacion Este": { lat:16.0440, lng:120.3360 }
};

/* ================= CLEAN MAP ================= */
function clearMap(){
    layersGroup.clearLayers();
}

/* ================= STATUS CHECK ================= */
function getStatus(item){

    const now = new Date();

    const start = new Date(`${item.maintenance_date}T${item.start_time}`);
    const end   = new Date(`${item.maintenance_date}T${item.end_time}`);

    let status = item.status;

    if (status !== "done") {

        if (now > end) status = "done";
        else if (now >= start) status = "ongoing";
        else status = "pending";
    }

    return status;
}

/* ================= LOAD FROM API ================= */
async function loadMaintenance(){

    try {

        const res = await fetch(
            "http://localhost/crowdsourcedAPI/api/maintenance_map/get.php",
            { credentials: "include" }
        );

        const data = await res.json();

        if (!data.success) {
            throw new Error(data.message);
        }

        clearMap();

        const items = data.data || [];

        let bounds = [];

        items.forEach(item => {

            const status = getStatus(item);

            if (status === "done") return;

            const barangays = item.affected_barangays || [];

            const radius = Number(item.radius || 2000);

            barangays.forEach(name => {

                const geo = barangayData[name];

                if (!geo) return;

                const circle = L.circle([geo.lat, geo.lng], {
                    radius: radius,
                    color: status === "ongoing" ? "#3498db" : "#e74c3c",
                    fillColor: status === "ongoing" ? "#3498db" : "#e74c3c",
                    fillOpacity: 0.25,
                    weight: 2
                });

                circle.bindPopup(`
                    <div class="map-popup">
                        <h4>⚡ Maintenance</h4>
                        <b>Barangay:</b> ${name}<br>
                        <b>Company:</b> ${item.company_name}<br>
                        <b>Status:</b> ${status}<br>
                        <b>Date:</b> ${item.maintenance_date}<br>
                        <b>Time:</b> ${item.start_time} - ${item.end_time}<br>
                        <small>${item.description || "No description"}</small>
                    </div>
                `);

                circle.addTo(layersGroup);

                bounds.push([geo.lat, geo.lng]);
            });
        });


        document.getElementById("mapLoading").style.display = "none";

    } catch (err) {

        console.error("Map error:", err);

        document.getElementById("mapLoading").innerText =
            "Failed to load maintenance data.";
    }
}

/* ================= INIT ================= */
initMap();
loadMaintenance();

/* refresh every 30s */
setInterval(loadMaintenance, 30000);

</script>