<!DOCTYPE html>
<html>
<head>
    <title>Electric Company - Outage Dashboard</title>

    <style>
        body{
            font-family: Arial;
            background:#f4f6f8;
            margin:0;
            padding:20px;
        }

        h2{ margin-bottom:10px; }

        .grid{
            display:grid;
            grid-template-columns: 1fr 1fr;
            gap:15px;
        }

        .card{
            background:#fff;
            padding:15px;
            border-radius:10px;
            box-shadow:0 2px 8px rgba(0,0,0,0.1);
            height:650px;
            overflow:auto;
        }

        .item{
            border:1px solid #eee;
            padding:10px;
            border-radius:8px;
            margin-bottom:10px;
        }

        .status{
            padding:3px 8px;
            border-radius:5px;
            font-size:12px;
            color:white;
        }

        .active{ background:#e67e22; }
        .under_review{ background:#3498db; }
        .verified{ background:#9b59b6; }
        .resolved{ background:#2ecc71; }
        .rejected{ background:#e74c3c; }

        button{
            margin-top:8px;
            width:100%;
            padding:8px;
            border:none;
            border-radius:6px;
            background:#2c3e50;
            color:white;
            cursor:pointer;
        }

        button:hover{ background:#1a252f; }

        small{
            color:#666;
            display:block;
            margin-top:5px;
        }

        .empty{
            color:#999;
            text-align:center;
            margin-top:20px;
        }
    </style>
</head>

<body>

<h2>⚡ Electric Company Outage Dashboard</h2>

<div class="grid">

    <!-- LIST -->
    <div class="card">
        <h3>Outage Reports</h3>
        <div id="outageList"></div>
    </div>

    <!-- DETAILS -->
    <div class="card">
        <h3>Control Panel</h3>
        <div id="detailBox">Select an outage</div>
    </div>

</div>

<script>

let outages = [];
let selectedId = null;

/* ================= LOAD ================= */
async function loadOutages(){

    try{

        const res = await fetch(
            "http://localhost/crowdsourcedAPI/api/outage_report_electric_com/get.php",
            { credentials:"include" }
        );

        const result = await res.json();

        if(!result.success) throw new Error(result.message);

        outages = result.data || [];

        renderList();

        // 🔥 refresh selected view after reload
        if (selectedId) {
            const fresh = outages.find(o => o.id == selectedId);
            if (fresh) openOutage(selectedId);
        }

    }catch(err){
        console.error(err);
        document.getElementById("outageList").innerHTML =
            "<p class='empty'>Failed to load outages</p>";
    }
}

/* ================= RENDER LIST ================= */
function renderList(){

    const list = document.getElementById("outageList");
    list.innerHTML = "";

    if(outages.length === 0){
        list.innerHTML = "<p class='empty'>No outage reports found</p>";
        return;
    }

    outages.forEach(o => {

        const div = document.createElement("div");
        div.className = "item";

        div.innerHTML = `
            <b>${o.location_name ?? "Unknown Location"}</b><br>

            <span class="status ${o.status}">
                ${o.status}
            </span>

            <small>
                ⚠ Severity: ${o.severity ?? "N/A"} <br>
                👥 Affected: ${o.affected_houses ?? 0} houses <br>
                📍 ${o.location_name ?? "N/A"} <br>
                🕒 ${o.started_at ?? ""} 
            </small>

            <button onclick="openOutage(${o.id})">
                Manage
            </button>
        `;

        list.appendChild(div);
    });
}

/* ================= OPEN ================= */
function openOutage(id){

    selectedId = id;

    const selected = outages.find(o => o.id == id);
    if(!selected) return;

    const isResolved = selected.status === "resolved";

    document.getElementById("detailBox").innerHTML = `
        <h4>${selected.location_name ?? "Outage Report"}</h4>

        <p>
            <b>Status:</b>
            <span class="status ${selected.status}">
                ${selected.status}
            </span>
        </p>

        <p>
            ⚠ Severity: ${selected.severity ?? "N/A"} <br>
            👥 Affected: ${selected.affected_houses ?? 0} houses <br>
            📍 ${selected.location_name ?? "N/A"} <br>
            🕒 ${selected.started_at ?? ""} <br>
        </p>

        ${!isResolved ? `
            <button onclick="resolveOutage(${selected.id})"
                style="background:#27ae60">
                ✔ Mark as RESOLVED
            </button>
        ` : `
            <button onclick="unresolveOutage(${selected.id})"
                style="background:#f39c12">
                ↩ Unresolve (Reopen)
            </button>
        `}
    `;
}

/* ================= RESOLVE ================= */
async function resolveOutage(id){

    if(!confirm("Mark this outage as RESOLVED?")) return;

    try{

        const res = await fetch(
            "http://localhost/crowdsourcedAPI/api/outage_report_electric_com/resolve.php",
            {
                method:"POST",
                headers:{ "Content-Type":"application/json" },
                credentials:"include",
                body: JSON.stringify({ id })
            }
        );

        const result = await res.json();

        if(!result.success) throw new Error(result.message);

        alert("Outage RESOLVED");

        await loadOutages();

    }catch(err){
        alert("Error: " + err.message);
    }
}

/* ================= UNRESOLVE ================= */
async function unresolveOutage(id){

    if(!confirm("Reopen this outage?")) return;

    try{

        const res = await fetch(
            "http://localhost/crowdsourcedAPI/api/outage_report_electric_com/unresolve.php",
            {
                method:"POST",
                headers:{ "Content-Type":"application/json" },
                credentials:"include",
                body: JSON.stringify({ id })
            }
        );

        const result = await res.json();

        if(!result.success) throw new Error(result.message);

        alert("Outage REOPENED");

        await loadOutages();

    }catch(err){
        alert("Error: " + err.message);
    }
}

/* INIT */
loadOutages();
setInterval(loadOutages, 10000);

</script>

</body>
</html>