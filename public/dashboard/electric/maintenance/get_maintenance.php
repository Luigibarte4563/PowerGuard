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
</style>

<div class="map-card">
    <h2>Upcoming Maintenance Map</h2>
    <div id="maintenanceMap"></div>
</div>

<script>

/* ================= BARANGAY DATA ================= */
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

/* ================= MAP ================= */
let maintenanceMap;
let layers = [];

function initMaintenanceMap(){
    maintenanceMap = L.map('maintenanceMap').setView(
        [16.0431, 120.3330],
        13
    );

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
        attribution:'© OpenStreetMap'
    }).addTo(maintenanceMap);
}

function clearMap(){
    layers.forEach(l => maintenanceMap.removeLayer(l));
    layers = [];
}

/* ================= AUTO STATUS (FIXED) ================= */
function computeStatus(item){

    const now = new Date();

    const start = new Date(`${item.maintenance_date}T${item.start_time}`);
    const end = new Date(`${item.maintenance_date}T${item.end_time}`);

    let status = item.status;

    // If DB status is not reliable, override using time
    if (!status || status === "pending" || status === "ongoing") {

        if (now > end) {
            status = "done";
        } else if (now >= start && now <= end) {
            status = "ongoing";
        } else {
            status = "pending";
        }
    }

    return status;
}

/* ================= LOAD DATA ================= */
async function loadMaintenanceMap(){

    try{

        const res = await fetch(
            "http://localhost/crowdsourcedapi/api/maintenance/get.php",
            { credentials:"include" }
        );

        const result = await res.json();
        if(!result.success) throw new Error(result.message);

        clearMap();

        const items = result.data || [];
        const bounds = [];

        items.forEach(item => {

            const status = computeStatus(item);

            /* 🚨 REMOVE DONE */
            if (status === "done") return;

            const barangays = item.affected_barangays;

            if(!Array.isArray(barangays)) return;

            const radius = Number(item.radius || 2000);

            barangays.forEach(name => {

                const geo = barangayData[name];

                if(!geo){
                    console.warn("Missing barangay:", name);
                    return;
                }

                const latlng = [geo.lat, geo.lng];
                bounds.push(latlng);

                const circle = L.circle(latlng, {
                    radius: radius,
                    color: status === "ongoing" ? "#3498db" : "#e74c3c",
                    fillColor: status === "ongoing" ? "#3498db" : "#e74c3c",
                    fillOpacity: 0.3,
                    weight: 2
                }).addTo(maintenanceMap);

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

                layers.push(circle);
            });

        });

        /* FIT MAP */
        if(bounds.length > 0){
            maintenanceMap.fitBounds(bounds, {
                padding: [50, 50]
            });
        }

        setTimeout(() => maintenanceMap.invalidateSize(), 300);

    } catch(err){
        console.error("Map error:", err);
    }
}

/* ================= INIT ================= */
initMaintenanceMap();
loadMaintenanceMap();
setInterval(loadMaintenanceMap, 30000);

</script>