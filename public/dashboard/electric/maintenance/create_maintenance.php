<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Maintenance Scheduler</title>

<link rel="stylesheet"
href="https://unpkg.com/leaflet/dist/leaflet.css"/>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:Arial, sans-serif;
    background:#f4f6f9;
    padding:20px;
}

.container{
    max-width:1200px;
    margin:auto;

    display:grid;
    grid-template-columns:400px 1fr;
    gap:20px;
}

.card{
    background:#fff;
    border-radius:12px;
    padding:20px;
    box-shadow:0 2px 10px rgba(0,0,0,0.08);
}

h2{
    margin-bottom:15px;
    color:#2c3e50;
}

form{
    display:flex;
    flex-direction:column;
    gap:12px;
}

label{
    font-size:14px;
    font-weight:bold;
    color:#555;
}

input,
textarea,
select{
    width:100%;
    padding:12px;
    border:1px solid #ccc;
    border-radius:8px;
    outline:none;
}

textarea{
    resize:none;
}

select[multiple]{
    height:180px;
}

.row{
    display:flex;
    gap:10px;
}

.row > div{
    flex:1;
}

button{
    padding:12px;
    border:none;
    border-radius:8px;
    background:#27ae60;
    color:white;
    font-size:15px;
    cursor:pointer;
    transition:0.2s;
}

button:hover{
    background:#219150;
}

button:disabled{
    opacity:0.6;
    cursor:not-allowed;
}

#map{
    width:100%;
    height:700px;
    border-radius:12px;
}

#status{
    margin-top:10px;
    font-weight:bold;
}

.success{
    color:#27ae60;
}

.error{
    color:#e74c3c;
}

.badge{
    display:inline-block;
    background:#3498db;
    color:white;
    padding:5px 10px;
    border-radius:20px;
    font-size:12px;
}

.checkbox-row{
    display:flex;
    align-items:center;
    gap:10px;
}

.checkbox-row input{
    width:auto;
}

.info-box{
    background:#ecf0f1;
    padding:10px;
    border-radius:8px;
    font-size:13px;
    color:#555;
}

@media(max-width:900px){

    .container{
        grid-template-columns:1fr;
    }

    #map{
        height:450px;
    }
}

</style>
</head>

<body>

<div class="container">

    <!-- LEFT PANEL -->
    <div class="card">

        <h2>⚡ Create Maintenance</h2>

        <form id="maintenanceForm">

            <div>
                <label>Affected Areas</label>

                <textarea
                    id="affected_area"
                    readonly
                    placeholder="Selected barangays..."
                ></textarea>
            </div>

            <div>
                <label>Select Barangays</label>

                <select id="barangays" multiple></select>

                <small>
                    Hold CTRL to select multiple barangays
                </small>
            </div>

            <div class="checkbox-row">
                <input type="checkbox" id="notify_all">
                <label for="notify_all">
                    Notify ALL Users
                </label>
            </div>

            <div class="info-box">
                If enabled, notifications will be sent to all users.
            </div>

            <div>
                <label>Maintenance Date</label>

                <input
                    type="date"
                    id="maintenance_date"
                    required
                >
            </div>

            <div class="row">

                <div>
                    <label>Start Time</label>

                    <input
                        type="time"
                        id="start_time"
                        required
                    >
                </div>

                <div>
                    <label>End Time</label>

                    <input
                        type="time"
                        id="end_time"
                        required
                    >
                </div>

            </div>

            <div>
                <label>Description</label>

                <textarea
                    id="description"
                    rows="4"
                    placeholder="Enter maintenance details..."
                ></textarea>
            </div>

            <div>
                <label>Notification Radius (meters)</label>

                <input
                    type="number"
                    id="radius"
                    value="2000"
                    min="500"
                >
            </div>

            <button type="submit" id="submitBtn">
                Create Maintenance
            </button>

        </form>

        <div id="status"></div>

    </div>

    <!-- MAP -->
    <div class="card">

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">

            <h2>📍 Maintenance Map</h2>

            <span class="badge">
                Click circles to select
            </span>

        </div>

        <div id="map"></div>

    </div>

</div>

<script>

/* =========================================
   MAP
========================================= */
const map = L.map('map').setView([16.0431, 120.3330], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap'
}).addTo(map);

/* =========================================
   BARANGAY DATA
========================================= */
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

/* =========================================
   ELEMENTS
========================================= */
const barangaySelect = document.getElementById("barangays");
const affectedArea = document.getElementById("affected_area");
const notifyAllCheckbox = document.getElementById("notify_all");
const statusBox = document.getElementById("status");

const layers = {};

/* =========================================
   INIT BARANGAYS
========================================= */
Object.keys(barangayData).forEach(name => {

    const option = document.createElement("option");
    option.value = name;
    option.textContent = name;
    barangaySelect.appendChild(option);

    const circle = L.circle(
        [barangayData[name].lat, barangayData[name].lng],
        {
            radius: 1200,
            color: "#27ae60",
            fillOpacity: 0.2
        }
    ).addTo(map);

    circle.bindPopup(`<b>${name}</b>`);

    circle.on("click", () => {
        option.selected = !option.selected;
        updateSelections();
    });

    layers[name] = circle;
});

/* =========================================
   UPDATE UI
========================================= */
function updateSelections() {

    const selected = [...barangaySelect.selectedOptions]
        .map(o => o.value);

    affectedArea.value = selected.join(", ");

    Object.keys(layers).forEach(name => {

        if (selected.includes(name)) {
            layers[name].setStyle({
                color: "#e74c3c",
                fillOpacity: 0.5
            });
        } else {
            layers[name].setStyle({
                color: "#27ae60",
                fillOpacity: 0.2
            });
        }
    });
}

barangaySelect.addEventListener("change", updateSelections);

/* =========================================
   NOTIFY ALL
========================================= */
notifyAllCheckbox.addEventListener("change", () => {

    if (notifyAllCheckbox.checked) {

        barangaySelect.disabled = true;

        [...barangaySelect.options]
            .forEach(o => o.selected = false);

        affectedArea.value = "ALL AREAS";

    } else {

        barangaySelect.disabled = false;
        affectedArea.value = "";
    }

    updateSelections();
});

/* =========================================
   SUBMIT (UPDATED FOR NEW BACKEND)
========================================= */
document.getElementById("maintenanceForm")
.addEventListener("submit", async (e) => {

    e.preventDefault();

    const btn = document.getElementById("submitBtn");
    statusBox.innerHTML = "";

    const selectedBarangays = [...barangaySelect.selectedOptions]
        .map(o => o.value);

    const payload = {

        maintenance_date: document.getElementById("maintenance_date").value,
        start_time: document.getElementById("start_time").value,
        end_time: document.getElementById("end_time").value,
        description: document.getElementById("description").value,
        radius: document.getElementById("radius").value,

        notify_all: notifyAllCheckbox.checked,

        // ✅ FIX: send actual selected barangays only
        barangays: selectedBarangays
    };

    try {

        btn.disabled = true;
        btn.innerText = "Creating...";

        const response = await fetch(
            "http://localhost/CrowdsourcedAPI/api/maintenance/create.php",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                credentials: "include",
                body: JSON.stringify(payload)
            }
        );

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || "Request failed");
        }

        statusBox.innerHTML = `
            <div class="success">
                ✅ ${result.message}<br>
                📢 Users Notified: ${result.users_notified}
            </div>
        `;

        alert("✅ Maintenance created successfully!");

        /* RESET */
        e.target.reset();
        affectedArea.value = "";
        barangaySelect.disabled = false;

        [...barangaySelect.options]
            .forEach(o => o.selected = false);

        updateSelections();

    } catch (err) {

        console.error(err);

        statusBox.innerHTML = `
            <div class="error">
                ❌ ${err.message}
            </div>
        `;

    } finally {

        btn.disabled = false;
        btn.innerText = "Create Maintenance";
    }
});

</script>
</body>
</html>