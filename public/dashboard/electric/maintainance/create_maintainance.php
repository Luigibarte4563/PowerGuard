<!DOCTYPE html>
<html>
<head>
    <title>Maintenance Scheduler</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <style>
        body {
            font-family: Arial;
            padding: 20px;
        }

        #map {
            height: 450px;
            margin-top: 10px;
            border-radius: 10px;
        }

        form {
            display: grid;
            gap: 10px;
            max-width: 450px;
        }

        input, textarea, select, button {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        button {
            background: #2ecc71;
            color: white;
            cursor: pointer;
        }

        button:hover {
            background: #27ae60;
        }

        select[multiple] {
            height: 140px;
        }

        #status {
            margin-top: 10px;
            font-weight: bold;
        }
    </style>
</head>

<body>

<h2>⚡ Create Maintenance Schedule</h2>

<form id="maintenanceForm">

    <input type="text" id="affected_area" placeholder="Affected Area (map preview only)" required>

    <!-- ================= BARANGAY-BASED APPROACH ================= -->
    <label>Barangays Affected (PRIMARY FILTER)</label>
    <select id="barangays" multiple required>
        <option value="Bonuan Gueset">Bonuan Gueset</option>
        <option value="Bonuan Boquig">Bonuan Boquig</option>
        <option value="Bonuan Binloc">Bonuan Binloc</option>

        <option value="Lucao">Lucao</option>
        <option value="Tapuac">Tapuac</option>
        <option value="Tambac">Tambac</option>
        <option value="Pantal">Pantal</option>

        <option value="Bacayao Norte">Bacayao Norte</option>
        <option value="Bacayao Sur">Bacayao Sur</option>

        <option value="Malued">Malued</option>
        <option value="Mayombo">Mayombo</option>

        <option value="Mangin">Mangin</option>
        <option value="Tebeng">Tebeng</option>

        <option value="Pogo Chico">Pogo Chico</option>
        <option value="Pogo Grande">Pogo Grande</option>

        <option value="Herrero">Herrero</option>
        <option value="Poblacion Centro">Poblacion Centro</option>
        <option value="Poblacion Oeste">Poblacion Oeste</option>
        <option value="Poblacion Este">Poblacion Este</option>
    </select>

    <small>Hold CTRL / tap to select multiple</small>

    <div class="row">
        <div>
            <label>Maintenance Date</label>
            <input type="date" id="maintenance_date" required>
        </div>
    </div>

    <div class="row">
        <input type="time" id="start_time" required>
        <input type="time" id="end_time" required>
    </div>

    <textarea id="description" placeholder="Description"></textarea>

    <!-- map preview only -->
    <input type="hidden" id="latitude">
    <input type="hidden" id="longitude">

    <button type="submit" id="submitBtn">Create Maintenance</button>

</form>

<p id="status"></p>

<div id="map"></div>

<script>

/* ================= MAP ================= */
let map = L.map('map').setView([16.0431, 120.3330], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: "© OpenStreetMap"
}).addTo(map);

let marker;
let barangayLayer = {};

/* ================= BARANGAY DATA ================= */
const barangayData = {
    "Bonuan Gueset": { lat: 16.0585, lng: 120.3345 },
    "Bonuan Boquig": { lat: 16.0600, lng: 120.3200 },
    "Bonuan Binloc": { lat: 16.0620, lng: 120.3100 },

    "Lucao": { lat: 16.0435, lng: 120.3310 },
    "Tapuac": { lat: 16.0460, lng: 120.3450 },
    "Tambac": { lat: 16.0520, lng: 120.3400 },
    "Pantal": { lat: 16.0468, lng: 120.3330 },

    "Bacayao Norte": { lat: 16.0300, lng: 120.3200 },
    "Bacayao Sur": { lat: 16.0250, lng: 120.3250 },

    "Malued": { lat: 16.0400, lng: 120.3200 },
    "Mayombo": { lat: 16.0480, lng: 120.3100 },

    "Mangin": { lat: 16.0550, lng: 120.3500 },
    "Tebeng": { lat: 16.0600, lng: 120.3450 },

    "Pogo Chico": { lat: 16.0510, lng: 120.3600 },
    "Pogo Grande": { lat: 16.0550, lng: 120.3650 },

    "Herrero": { lat: 16.0450, lng: 120.3350 },
    "Poblacion Centro": { lat: 16.0430, lng: 120.3335 },
    "Poblacion Oeste": { lat: 16.0410, lng: 120.3300 },
    "Poblacion Este": { lat: 16.0440, lng: 120.3360 }
};

/* ================= CREATE CIRCLES (HIDDEN INITIALLY) ================= */
Object.keys(barangayData).forEach(name => {

    const b = barangayData[name];

    const circle = L.circle([b.lat, b.lng], {
        radius: 1200,
        color: "#2ecc71",
        fillColor: "#2ecc71",
        fillOpacity: 0.0,   // hidden
        weight: 1
    });

    circle.bindPopup(name);

    barangayLayer[name] = circle;

    circle.on("click", () => toggleBarangay(name));
});

/* ================= TOGGLE BARANGAY ================= */
function toggleBarangay(name) {

    const select = document.getElementById("barangays");
    const option = [...select.options].find(o => o.value === name);

    if (!option) return;

    option.selected = !option.selected;

    syncBarangayState(name);
}

/* ================= SYNC UI STATE ================= */
function syncBarangayState(name) {

    const circle = barangayLayer[name];
    const select = document.getElementById("barangays");

    const isSelected = [...select.options]
        .some(o => o.value === name && o.selected);

    if (isSelected) {

        // SHOW
        if (!map.hasLayer(circle)) {
            circle.addTo(map);
        }

        circle.setStyle({
            color: "#e74c3c",
            fillColor: "#e74c3c",
            fillOpacity: 0.25,
            weight: 2
        });

        map.panTo(circle.getLatLng());

    } else {

        // HIDE
        if (map.hasLayer(circle)) {
            map.removeLayer(circle);
        }
    }
}

/* ================= DROPDOWN → MAP SYNC ================= */
document.getElementById("barangays").addEventListener("change", function () {

    Object.keys(barangayLayer).forEach(name => {
        syncBarangayState(name);
    });
});

/* ================= MAP CLICK (MARK LOCATION ONLY) ================= */
map.on("click", function (e) {

    const lat = e.latlng.lat;
    const lng = e.latlng.lng;

    document.getElementById("latitude").value = lat;
    document.getElementById("longitude").value = lng;

    if (marker) map.removeLayer(marker);

    marker = L.marker([lat, lng]).addTo(map);
});

/* ================= SUBMIT ================= */
document.getElementById("maintenanceForm").addEventListener("submit", async (e) => {

    e.preventDefault();

    const status = document.getElementById("status");
    const btn = document.getElementById("submitBtn");

    const barangays = Array.from(
        document.getElementById("barangays").selectedOptions
    ).map(opt => opt.value);

    if (barangays.length === 0) {
        status.innerText = "❌ Select at least 1 barangay";
        return;
    }

    const payload = {
        affected_area: document.getElementById("affected_area").value,
        maintenance_date: document.getElementById("maintenance_date").value,
        start_time: document.getElementById("start_time").value,
        end_time: document.getElementById("end_time").value,
        description: document.getElementById("description").value,
        latitude: document.getElementById("latitude").value,
        longitude: document.getElementById("longitude").value,
        barangays: barangays
    };

    try {

        btn.disabled = true;
        btn.innerText = "Creating...";

        console.log("PAYLOAD:", payload);

const res = await fetch(
    "http://localhost/crowdsourcedapi/api/maintainance/create.php",
    {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        credentials: "include",
        body: JSON.stringify(payload)
    }
);

const text = await res.text();

console.log("RAW RESPONSE:", text);

const result = JSON.parse(text);


        if (!result.success) throw new Error(result.message);

        status.innerText = "✅ " + result.message;

        alert(`Maintenance created!\nBarangays: ${barangays.length}`);

        document.getElementById("maintenanceForm").reset();

        if (marker) map.removeLayer(marker);

        // reset all circles
        Object.values(barangayLayer).forEach(c => {
            if (map.hasLayer(c)) map.removeLayer(c);
        });

    } catch (err) {
        status.innerText = "❌ " + err.message;
    } finally {
        btn.disabled = false;
        btn.innerText = "Create Maintenance";
    }
});

</script>

</body>
</html>