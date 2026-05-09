<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Reports</title>

<style>
body{
    font-family: Arial;
    padding:20px;
}

.card{
    border:1px solid #ccc;
    padding:12px;
    margin-bottom:10px;
    border-radius:8px;
    background:#fff;
}

#formOverlay{
    display:none;
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:#000000aa;
}

#formBox{
    background:#fff;
    width:450px;
    margin:5% auto;
    padding:20px;
    border-radius:10px;
}

input, select, textarea{
    width:100%;
    padding:8px;
    margin-top:8px;
}

button{
    width:100%;
    padding:10px;
    margin-top:10px;
    cursor:pointer;
}
</style>
</head>

<body>

<h2>My Outage Reports</h2>

<div id="list"></div>

<!-- MODAL -->
<div id="formOverlay">
<div id="formBox">

<h3>Edit Report</h3>

<input type="hidden" id="id">

<input type="text" id="location_name" placeholder="Location Name">

<select id="category">
    <option value="power_outage">Power Outage</option>
    <option value="low_voltage">Low Voltage</option>
    <option value="power_fluctuation">Power Fluctuation</option>
    <option value="transformer_explosion">Transformer Explosion</option>
    <option value="fallen_power_line">Fallen Power Line</option>
    <option value="electrical_fire">Electrical Fire</option>
    <option value="scheduled_maintenance">Maintenance</option>
    <option value="unknown_issue">Unknown</option>
</select>

<select id="severity">
    <option value="minor">Minor</option>
    <option value="moderate">Moderate</option>
    <option value="critical">Critical</option>
</select>

<textarea id="description" placeholder="Description"></textarea>

<input type="number" id="affected_houses">

<select id="status">
    <option value="active">Active</option>
    <option value="under_review">Under Review</option>
    <option value="verified">Verified</option>
    <option value="resolved">Resolved</option>
    <option value="rejected">Rejected</option>
</select>

<select id="is_active">
    <option value="1">Active</option>
    <option value="0">Inactive</option>
</select>

<select id="hazard_type">
    <option value="none">None</option>
    <option value="smoke">Smoke</option>
    <option value="sparks">Sparks</option>
    <option value="fire">Fire</option>
    <option value="fallen_wire">Fallen Wire</option>
    <option value="explosion_sound">Explosion Sound</option>
</select>

<button onclick="updateReport()">Update Report</button>
<button onclick="closeForm()">Close</button>

</div>
</div>

<script>

let currentReport = null;

/* ================= LOAD ================= */
async function loadReports(){

    const list = document.getElementById("list");
    list.innerHTML = "Loading...";

    try {

        const res = await fetch(
            "http://localhost/crowdsourcedapi/api/outage_report/get_my_report.php",
            {
                method: "GET",
                credentials: "include" // JWT COOKIE
            }
        );

        const result = await res.json();

        if(!result.success){
            list.innerHTML = "Failed to load";
            return;
        }

        if(!result.data.length){
            list.innerHTML = "No reports found";
            return;
        }

        list.innerHTML = result.data.map(r => `
            <div class="card">
                <h3>${r.location_name}</h3>
                <p>${r.description || ""}</p>
                <small>
                    ${r.category} | ${r.severity} | ${r.status}
                </small>
                <br><br>

                <!-- SAFE: store JSON in data attribute -->
                <button onclick='editReport(this)'
                    data-report='${JSON.stringify(r).replace(/'/g, "&apos;")}'
                >
                    Edit
                </button>
            </div>
        `).join("");

    } catch(err){
        console.error(err);
        list.innerHTML = "Server error";
    }
}


/* ================= EDIT ================= */
function editReport(btn){

    const r = JSON.parse(btn.getAttribute("data-report"));

    currentReport = r;

    document.getElementById("id").value = r.id;
    document.getElementById("location_name").value = r.location_name || "";
    document.getElementById("category").value = r.category;
    document.getElementById("severity").value = r.severity;
    document.getElementById("description").value = r.description || "";
    document.getElementById("affected_houses").value = r.affected_houses || 1;
    document.getElementById("status").value = r.status;
    document.getElementById("is_active").value = r.is_active ? "1" : "0";
    document.getElementById("hazard_type").value = r.hazard_type || "none";

    document.getElementById("formOverlay").style.display = "block";
}


/* ================= CLOSE ================= */
function closeForm(){
    document.getElementById("formOverlay").style.display = "none";
}


/* ================= UPDATE (JWT READY) ================= */
async function updateReport(){

    const payload = {
        id: document.getElementById("id").value,
        location_name: document.getElementById("location_name").value,
        category: document.getElementById("category").value,
        severity: document.getElementById("severity").value,
        description: document.getElementById("description").value,
        affected_houses: document.getElementById("affected_houses").value,
        status: document.getElementById("status").value,
        is_active: document.getElementById("is_active").value,
        hazard_type: document.getElementById("hazard_type").value
    };

    try {

        const res = await fetch(
            "http://localhost/crowdsourcedapi/api/outage_report/update.php",
            {
                method: "POST",
                credentials: "include", // JWT COOKIE SENT HERE
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(payload)
            }
        );

        const result = await res.json();

        alert(result.message);

        if(result.success){
            closeForm();
            loadReports();
        }

    } catch(err){
        console.error(err);
        alert("Update failed");
    }
}

/* INIT */
loadReports();

</script>

</body>
</html>