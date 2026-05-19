<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<style>
.wrapper{
    display:grid;
    grid-template-columns:350px 1fr;
    gap:15px;
    margin-top:20px;
    font-family:Arial;
}

.list-card{
    background:#fff;
    padding:15px;
    border-radius:12px;
    box-shadow:0 2px 8px rgba(0,0,0,0.1);
    height:700px;
    overflow:auto;
}

.item{
    border:1px solid #eee;
    padding:10px;
    border-radius:10px;
    margin-bottom:10px;
    transition:0.3s;
}

.item.done{
    opacity:0.5;
    background:#f5f5f5;
}

.item h4{ margin:0; }

.item small{
    display:block;
    color:#777;
    margin-top:5px;
}

.status{
    display:inline-block;
    margin-top:5px;
    font-size:12px;
    padding:3px 8px;
    border-radius:5px;
}

.status.done{ background:#2ecc71; color:white; }
.status.pending{ background:#f39c12; color:white; }
.status.ongoing{ background:#3498db; color:white; }

.btn{
    margin-top:8px;
    padding:6px 10px;
    border:none;
    border-radius:6px;
    cursor:pointer;
    font-size:12px;
}

.edit{ background:#3498db; color:white; }

.map-card{
    background:#fff;
    padding:15px;
    border-radius:12px;
    box-shadow:0 2px 8px rgba(0,0,0,0.1);
}

#maintenanceMap{
    width:100%;
    height:700px;
    border-radius:12px;
}

#editModal{
    display:none;
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.5);
    justify-content:center;
    align-items:center;
    z-index:99999;
}

.modal-box{
    background:#fff;
    width:400px;
    padding:20px;
    border-radius:12px;
}

.modal-box input,
.modal-box textarea,
.modal-box select{
    width:100%;
    padding:8px;
    margin-top:5px;
    margin-bottom:10px;
}

.modal-box button{
    width:100%;
    padding:10px;
    border:none;
    border-radius:8px;
    cursor:pointer;
}

.save{ background:#27ae60; color:white; }
.close{ background:#e74c3c; color:white; margin-top:8px; }
</style>

<div class="wrapper">

    <div class="list-card">
        <h3>📋 Maintenance List</h3>
        <div id="maintenanceList"></div>
    </div>

    <div class="map-card">
        <h3>🗺️ Map View</h3>
        <div id="maintenanceMap"></div>
    </div>

</div>

<!-- MODAL -->
<div id="editModal">
    <div class="modal-box">

        <h3>Edit Maintenance</h3>

        <input type="hidden" id="edit_id">

        <label>Date</label>
        <input type="date" id="edit_date">

        <label>Start</label>
        <input type="time" id="edit_start">

        <label>End</label>
        <input type="time" id="edit_end">

        <label>Description</label>
        <textarea id="edit_desc"></textarea>

        <label>Radius</label>
        <input type="number" id="edit_radius">

        <!-- STATUS (MANUAL ONLY) -->
        <label>Status (Optional Override)</label>
        <select id="edit_status">
            <option value="">Auto (based on time)</option>
            <option value="pending">Pending</option>
            <option value="ongoing">Ongoing</option>
            <option value="done">Done</option>
        </select>

        <button class="save" onclick="submitUpdate()">Save Update</button>
        <button class="close" onclick="closeModal()">Close</button>

    </div>
</div>

<script>

let map;
let layers = [];

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

function initMap(){
    map = L.map('maintenanceMap').setView([16.0430, 120.3335], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
        attribution:'© OpenStreetMap'
    }).addTo(map);

    setTimeout(() => map.invalidateSize(), 500);
}

function clearMap(){
    layers.forEach(l => map.removeLayer(l));
    layers = [];
}

/* ================= AUTO STATUS ================= */
function getStatus(item){

    const now = new Date();
    const start = new Date(`${item.maintenance_date}T${item.start_time}`);
    const end = new Date(`${item.maintenance_date}T${item.end_time}`);

    if(now > end) return "done";
    if(now >= start && now <= end) return "ongoing";
    return "pending";
}

/* ================= LOAD DATA ================= */
async function loadData(){

    try{

        const res = await fetch(
            "http://localhost/crowdsourcedapi/api/maintenance/get.php",
            { credentials:"include" }
        );

        const result = await res.json();
        if(!result.success) throw new Error(result.message);

        const list = document.getElementById("maintenanceList");
        list.innerHTML = "";

        clearMap();

        const items = result.data || [];

        items.forEach(item => {

            const status = item.status || getStatus(item);

            const div = document.createElement("div");
            div.className = "item " + status;

            div.innerHTML = `
                <h4>⚡ ${item.company_name}</h4>

                <span class="status ${status}">
                    ${status.toUpperCase()}
                </span>

                <small>
                    📅 ${item.maintenance_date}<br>
                    🕒 ${item.start_time} - ${item.end_time}<br>
                    📍 ${item.affected_barangays?.join(", ") || ""}
                </small>

                <button class="btn edit">Edit</button>
            `;

            div.querySelector(".edit").onclick = () => openEdit(item);

            list.appendChild(div);

            /* MAP */
            const barangays = item.affected_barangays || [];
            let valid = [];

            barangays.forEach(name => {

                const geo = barangayData[name];
                if(!geo) return;

                valid.push(geo);

                const marker = L.marker([geo.lat, geo.lng]).addTo(map);
                marker.bindPopup(`<b>${name}</b><br>${item.company_name}`);

                layers.push(marker);
            });

            if(valid.length > 0){

                let latSum = 0;
                let lngSum = 0;

                valid.forEach(v => {
                    latSum += v.lat;
                    lngSum += v.lng;
                });

                const circle = L.circle(
                    [latSum / valid.length, lngSum / valid.length],
                    {
                        radius: item.radius || 2000,
                        color:"#e74c3c",
                        fillOpacity:0.2
                    }
                ).addTo(map);

                layers.push(circle);
            }

        });

    }catch(err){
        console.error(err);
    }
}

/* ================= OPEN MODAL ================= */
function openEdit(item){

    document.getElementById("edit_id").value = item.id;
    document.getElementById("edit_date").value = item.maintenance_date;
    document.getElementById("edit_start").value = item.start_time;
    document.getElementById("edit_end").value = item.end_time;
    document.getElementById("edit_desc").value = item.description || "";
    document.getElementById("edit_radius").value = item.radius;

    // optional manual override
    document.getElementById("edit_status").value = "";

    document.getElementById("editModal").style.display = "flex";
}

function closeModal(){
    document.getElementById("editModal").style.display = "none";
}

/* ================= UPDATE ================= */
async function submitUpdate(){

    const manualStatus = document.getElementById("edit_status").value;

    let status = null;

    // only send status if user selected manually
    if(manualStatus !== ""){
        status = manualStatus;
    }

    const payload = {
        maintenance_id: document.getElementById("edit_id").value,
        maintenance_date: document.getElementById("edit_date").value,
        start_time: document.getElementById("edit_start").value,
        end_time: document.getElementById("edit_end").value,
        description: document.getElementById("edit_desc").value,
        radius: Number(document.getElementById("edit_radius").value),
        status: status
    };

    try{

        const res = await fetch(
            "http://localhost/crowdsourcedapi/api/maintenance/update.php",
            {
                method:"POST",
                headers:{ "Content-Type":"application/json" },
                credentials:"include",
                body: JSON.stringify(payload)
            }
        );

        const result = await res.json();

        if(!result.success) throw new Error(result.message);

        alert("Updated successfully!");

        closeModal();
        loadData();

    }catch(err){
        alert("Error: " + err.message);
    }
}

/* INIT */
initMap();
loadData();
setInterval(loadData, 30000);

</script>