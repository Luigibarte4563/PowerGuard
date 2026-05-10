<!-- =========================================
     LEAFLET MAP (FIXED + CLEAN)
========================================= -->
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

let maintenanceMap;
let layers = [];

/* =========================================
   INIT MAP
========================================= */
function initMaintenanceMap(){

    maintenanceMap = L.map('maintenanceMap').setView(
        [16.0430, 120.3335],
        11
    );

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(maintenanceMap);
}

/* =========================================
   CLEAR MAP (SAFE)
========================================= */
function clearMap(){

    layers.forEach(layer => {
        if (layer) maintenanceMap.removeLayer(layer);
    });

    layers = [];
}

/* =========================================
   LOAD DATA
========================================= */
async function loadMaintenanceMap(){

    try{

        const response = await fetch(
            "http://localhost/crowdsourcedapi/api/maintenance/get.php",
            { credentials: "include" }
        );

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || "API error");
        }

        clearMap();

        const maintenances = data.data || [];

        maintenances.forEach(item => {

            const locations = item.locations || [];
            const radius = parseInt(item.radius || 2000);

            let validLocations = [];

            locations.forEach(loc => {

                const lat = parseFloat(loc.latitude);
                const lng = parseFloat(loc.longitude);

                if (isNaN(lat) || isNaN(lng)) return;

                validLocations.push({ lat, lng });

                const marker = L.marker([lat, lng]).addTo(maintenanceMap);

                marker.bindPopup(`
                    <div class="map-popup">

                        <h4>⚡ Maintenance</h4>

                        <b>Barangay:</b> ${loc.barangay_name}<br>
                        <b>Company:</b> ${item.company_name}<br>
                        <b>Status:</b> ${item.status}<br><br>

                        <b>Date:</b> ${item.maintenance_date}<br>
                        <b>Time:</b> ${item.start_time} - ${item.end_time}<br>

                        <small>${item.description || ""}</small>

                    </div>
                `);

                layers.push(marker);
            });

            /* =========================================
               DRAW CIRCLE ONLY IF VALID DATA EXISTS
            ========================================= */
            if (validLocations.length > 0) {

                let centerLat = 0;
                let centerLng = 0;

                validLocations.forEach(p => {
                    centerLat += p.lat;
                    centerLng += p.lng;
                });

                const avgLat = centerLat / validLocations.length;
                const avgLng = centerLng / validLocations.length;

                const circle = L.circle([avgLat, avgLng], {

                    radius: radius,
                    color: '#ff3b30',
                    weight: 2,
                    fillColor: '#ff3b30',
                    fillOpacity: 0.25

                }).addTo(maintenanceMap);

                layers.push(circle);
            }

        });

        // refresh rendering safety
        maintenanceMap.invalidateSize();

    } catch(err) {
        console.error("Maintenance Map Error:", err);
    }
}

/* =========================================
   INIT
========================================= */
initMaintenanceMap();
loadMaintenanceMap();

/* AUTO REFRESH */
setInterval(loadMaintenanceMap, 30000);

</script>