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

.section-title{
    font-weight:bold;
    margin:10px 0;
    padding:6px;
    background:#f4f4f4;
    border-radius:6px;
    font-size:13px;
}

.item{
    border:1px solid #eee;
    padding:10px;
    border-radius:10px;
    margin-bottom:10px;
}

.status{
    display:inline-block;
    margin-top:5px;
    font-size:12px;
    padding:3px 8px;
    border-radius:5px;
    text-transform: uppercase;
}

.status.completed{ background:#2ecc71; color:white; }
.status.upcoming{ background:#f39c12; color:white; }
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
}

#maintenanceMap{
    width:100%;
    height:700px;
    border-radius:12px;
}

/* MODAL */
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
    margin:5px 0 10px;
}

.save{ background:#27ae60; color:white; width:100%; padding:10px; border:none; }
.close{ background:#e74c3c; color:white; width:100%; padding:10px; border:none; margin-top:8px; }
</style>

<div class="wrapper">

    <div class="list-card">
        <h3>📋 Maintenance</h3>

        <div class="section-title">🟡 Active</div>
        <div id="activeList"></div>

        <div class="section-title">🟢 Completed</div>
        <div id="completedList"></div>
    </div>

    <div class="map-card">
        <h3>🗺️ Map View (Active Only)</h3>
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

        <label>Status</label>
        <select id="edit_status">
            <option value="">Auto</option>
            <option value="upcoming">Upcoming</option>
            <option value="ongoing">Ongoing</option>
            <option value="completed">Completed</option>
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
    "Lucao": { lat:16.0435, lng:120.3310 }
};

function initMap(){
    map = L.map('maintenanceMap').setView([16.0430, 120.3335], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
        attribution:'© OpenStreetMap'
    }).addTo(map);
}

function clearMap(){
    layers.forEach(l => map.removeLayer(l));
    layers = [];
}

function normalizeStatus(status){
    if(!status) return "upcoming";
    status = status.toLowerCase();

    if(status === "completed") return "completed";
    if(status === "ongoing") return "ongoing";
    if(status === "upcoming") return "upcoming";

    return "upcoming";
}

function isActive(status){
    return status === "upcoming" || status === "ongoing";
}

function formatDateReadable(dateStr){
    return new Date(dateStr).toLocaleDateString("en-US", {
        year:"numeric",
        month:"long",
        day:"numeric"
    });
}

function formatTime12(timeStr){
    if(!timeStr) return "";
    const [h,m] = timeStr.split(":");
    let hour = parseInt(h);
    const ampm = hour >= 12 ? "PM" : "AM";
    hour = hour % 12 || 12;
    return `${hour}:${m} ${ampm}`;
}

/* OPEN MODAL */
function openEdit(item){

    document.getElementById("edit_id").value = item.id;
    document.getElementById("edit_date").value = item.maintenance_date;
    document.getElementById("edit_start").value = item.start_time;
    document.getElementById("edit_end").value = item.end_time;
    document.getElementById("edit_desc").value = item.description || "";
    document.getElementById("edit_radius").value = item.radius || 2000;
    document.getElementById("edit_status").value = item.status || "";

    document.getElementById("editModal").style.display = "flex";
}

function closeModal(){
    document.getElementById("editModal").style.display = "none";
}

/* UPDATE */
async function submitUpdate(){

    const payload = {
        maintenance_id: document.getElementById("edit_id").value,
        maintenance_date: document.getElementById("edit_date").value,
        start_time: document.getElementById("edit_start").value,
        end_time: document.getElementById("edit_end").value,
        description: document.getElementById("edit_desc").value,
        radius: Number(document.getElementById("edit_radius").value),
        status: document.getElementById("edit_status").value || null
    };

    await fetch("http://localhost/crowdsourcedapi/api/maintenance/update.php",{
        method:"POST",
        headers:{ "Content-Type":"application/json" },
        body: JSON.stringify(payload)
    });

    closeModal();
    loadData();
}

/* LOAD */
async function loadData(){

    const res = await fetch("http://localhost/crowdsourcedapi/api/maintenance/get.php", {
        credentials:"include"
    });

    const result = await res.json();
    const data = result.data || [];

    const activeList = document.getElementById("activeList");
    const completedList = document.getElementById("completedList");

    activeList.innerHTML = "";
    completedList.innerHTML = "";

    clearMap();

    data.forEach(item => {

        const status = normalizeStatus(item.status);

        const container = document.createElement("div");
        container.className = "item";

        container.innerHTML = `
            <h4>${item.company_name}</h4>
            <span class="status ${status}">${status}</span><br>
            <small>
                📅 ${formatDateReadable(item.maintenance_date)}<br>
                🕒 ${formatTime12(item.start_time)} - ${formatTime12(item.end_time)}
            </small>

            ${
                status !== "completed"
                ? `<button class="btn edit">Edit</button>`
                : `<small style="color:gray;">Completed</small>`
            }
        `;

        if(status === "completed"){
            completedList.appendChild(container);
        } else {
            activeList.appendChild(container);
        }

        if(status !== "completed"){
            container.querySelector(".edit").onclick = () => openEdit(item);
        }

        if(!isActive(status)) return;

        let barangays = item.affected_barangays || [];
        if(typeof barangays === "string"){
            try { barangays = JSON.parse(barangays); }
            catch(e){ barangays = []; }
        }

        let points = [];

        barangays.forEach(name => {

            const geo = barangayData[name];
            if(!geo) return;

            points.push(geo);

            const marker = L.marker([geo.lat, geo.lng])
                .addTo(map)
                .bindPopup(`${name}<br>${status}`);

            layers.push(marker);
        });

        if(points.length){

            const center = [
                points.reduce((a,b)=>a+b.lat,0)/points.length,
                points.reduce((a,b)=>a+b.lng,0)/points.length
            ];

            const circle = L.circle(center,{
                radius: item.radius || 2000,
                color: status === "ongoing" ? "#3498db" : "#f39c12",
                fillOpacity:0.2
            }).addTo(map);

            layers.push(circle);
        }

    });

}

initMap();
loadData();
setInterval(loadData, 30000);

</script>