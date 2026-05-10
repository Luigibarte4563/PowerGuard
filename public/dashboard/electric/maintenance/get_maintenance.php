<!-- =========================================
     LEAFLET MAP
========================================= -->
<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet/dist/leaflet.css"
/>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<style>

/* =========================================
   MAP CARD
========================================= */
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

/* =========================================
   POPUP
========================================= */
.map-popup{
    font-size:14px;
    line-height:1.5;
}

.map-popup h4{
    margin:0 0 8px;
    color:#333;
}

.map-popup small{
    color:#666;
}

</style>

<!-- =========================================
     MAP CONTAINER
========================================= -->
<div class="map-card">

    <h2>Upcoming Maintenance Map</h2>

    <div id="maintenanceMap"></div>

</div>

<script>

/* =========================================
   GLOBAL MAP
========================================= */
let maintenanceMap;

/* =========================================
   INIT MAP
========================================= */
function initMaintenanceMap(){

    maintenanceMap = L.map('maintenanceMap').setView(
        [16.0430, 120.3335],
        11
    );

    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            attribution:
                '&copy; OpenStreetMap contributors'
        }
    ).addTo(maintenanceMap);
}

/* =========================================
   LOAD MAINTENANCE MAP
========================================= */
async function loadMaintenanceMap(){

    try{

        const response = await fetch(
            "http://localhost/crowdsourcedapi/api/maintenance/get.php",
            {
                credentials:"include"
            }
        );

        const data = await response.json();

        if(!data.success){
            throw new Error(data.message);
        }

        const maintenances = data.data || [];

        /* =====================================
           CLEAR OLD LAYERS
        ===================================== */
        maintenanceMap.eachLayer(layer => {

            if(
                layer instanceof L.Marker ||
                layer instanceof L.Circle
            ){
                maintenanceMap.removeLayer(layer);
            }
        });

        /* =====================================
           LOOP MAINTENANCE
        ===================================== */
        maintenances.forEach(item => {

            const lat = parseFloat(item.latitude);
            const lng = parseFloat(item.longitude);

            if(!lat || !lng) return;

            const radius = parseInt(item.radius || 2000);

            /* =================================
               MARKER
            ================================= */
            const marker = L.marker([lat, lng]).addTo(
                maintenanceMap
            );

            marker.bindPopup(`
                <div class="map-popup">

                    <h4>
                        ⚡ Scheduled Maintenance
                    </h4>

                    <b>Location:</b>
                    ${item.affected_area || "Unknown"}
                    <br>

                    <b>Date:</b>
                    ${item.maintenance_date}
                    <br>

                    <b>Time:</b>
                    ${item.start_time} -
                    ${item.end_time}
                    <br>

                    <b>Radius:</b>
                    ${radius} meters
                    <br><br>

                    <small>
                        ${item.description || ""}
                    </small>

                </div>
            `);

            /* =================================
               AFFECTED RADIUS CIRCLE
            ================================= */
            L.circle([lat, lng], {

                radius: radius,

                color: '#ff3b30',

                fillColor: '#ff3b30',

                fillOpacity: 0.25

            }).addTo(maintenanceMap);

        });

    }catch(err){

        console.error(
            "Maintenance Map Error:",
            err
        );
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