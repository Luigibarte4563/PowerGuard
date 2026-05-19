<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Reports</title>

<style>

body{
    font-family: Arial;
    padding:20px;
    background:#f5f5f5;
}

.card{
    border:1px solid #ddd;
    padding:15px;
    margin-bottom:15px;
    border-radius:10px;
    background:#fff;
    box-shadow:0 2px 5px rgba(0,0,0,0.05);
}

.card h3{
    margin:0 0 8px;
}

.badge{
    display:inline-block;
    padding:4px 10px;
    border-radius:20px;
    font-size:12px;
    color:#fff;
    margin-top:5px;
}

.active{
    background:#ff9800;
}

.under_review{
    background:#2196f3;
}

.verified{
    background:#4caf50;
}

.resolved{
    background:#009688;
}

.rejected{
    background:#f44336;
}

#formOverlay{
    display:none;
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:#000000aa;
    z-index:999;
}

#formBox{
    background:#fff;
    width:450px;
    margin:4% auto;
    padding:20px;
    border-radius:10px;
}

input,
select,
textarea{
    width:100%;
    padding:10px;
    margin-top:10px;
    border:1px solid #ccc;
    border-radius:5px;
    box-sizing:border-box;
}

textarea{
    min-height:100px;
    resize:vertical;
}

button{
    width:100%;
    padding:12px;
    margin-top:12px;
    cursor:pointer;
    border:none;
    border-radius:6px;
}

.update-btn{
    background:#1976d2;
    color:#fff;
}

.close-btn{
    background:#777;
    color:#fff;
}

.edit-btn{
    width:auto;
    background:#4caf50;
    color:#fff;
    padding:8px 15px;
}

.status-box{
    margin-top:10px;
    padding:10px;
    border-radius:6px;
    background:#f1f1f1;
    font-size:14px;
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

<input type="number" id="affected_houses" min="1">

<select id="hazard_type">
    <option value="none">None</option>
    <option value="smoke">Smoke</option>
    <option value="sparks">Sparks</option>
    <option value="fire">Fire</option>
    <option value="fallen_wire">Fallen Wire</option>
    <option value="explosion_sound">Explosion Sound</option>
</select>

<!-- READ ONLY STATUS -->
<div class="status-box">
    <strong>Status:</strong>
    <span id="statusText">active</span>
</div>

<div class="status-box">
    <strong>Activity:</strong>
    <span id="activeText">Active</span>
</div>

<button class="update-btn" onclick="updateReport()">
    Update Report
</button>

<button class="close-btn" onclick="closeForm()">
    Close
</button>

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
                credentials: "include"
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

                <p>
                    <strong>Category:</strong> ${r.category}
                </p>

                <p>
                    <strong>Severity:</strong> ${r.severity}
                </p>

                <span class="badge ${r.status}">
                    ${r.status}
                </span>

                <br><br>

                <button
                    class="edit-btn"
                    onclick='editReport(this)'
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

    document.getElementById("location_name").value =
        r.location_name || "";

    document.getElementById("category").value =
        r.category;

    document.getElementById("severity").value =
        r.severity;

    document.getElementById("description").value =
        r.description || "";

    document.getElementById("affected_houses").value =
        r.affected_houses || 1;

    document.getElementById("hazard_type").value =
        r.hazard_type || "none";

    /* READ ONLY DISPLAY */
    document.getElementById("statusText").innerText =
        r.status;

    document.getElementById("activeText").innerText =
        r.is_active == 1 ? "Active" : "Inactive";

    document.getElementById("formOverlay").style.display =
        "block";
}

/* ================= CLOSE ================= */
function closeForm(){

    document.getElementById("formOverlay").style.display =
        "none";
}

/* ================= UPDATE ================= */
async function updateReport(){

    const payload = {

        id: document.getElementById("id").value,

        location_name:
            document.getElementById("location_name").value,

        category:
            document.getElementById("category").value,

        severity:
            document.getElementById("severity").value,

        description:
            document.getElementById("description").value,

        affected_houses:
            document.getElementById("affected_houses").value,

        hazard_type:
            document.getElementById("hazard_type").value
    };

    try {

        const res = await fetch(
            "http://localhost/crowdsourcedapi/api/outage_report/update.php",
            {
                method: "POST",
                credentials: "include",
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