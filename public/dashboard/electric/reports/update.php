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

        h2{
            margin-bottom:10px;
        }

        .topbar{
            margin-bottom:15px;
            display:flex;
            flex-wrap:wrap;
            gap:10px;
            align-items:center;
        }

        select, button{
            padding:8px 12px;
            border-radius:6px;
            border:1px solid #ccc;
            cursor:pointer;
        }

        button:hover{
            opacity:0.9;
        }

        .grid{
            display:grid;
            grid-template-columns:1fr 1fr;
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
            padding:12px;
            border-radius:8px;
            margin-bottom:10px;
        }

        .item button{
            margin-top:10px;
        }

        .status{
            padding:3px 8px;
            border-radius:5px;
            font-size:12px;
            color:white;
            display:inline-block;
            margin:5px 0;
        }

        .active{
            background:#e67e22;
        }

        .under_review{
            background:#3498db;
        }

        .verified{
            background:#9b59b6;
        }

        .resolved{
            background:#2ecc71;
        }

        .rejected{
            background:#e74c3c;
        }

        .empty{
            color:#999;
            text-align:center;
            margin-top:20px;
        }

        .loading{
            color:#555;
            margin-top:10px;
        }

        .control-buttons{
            display:flex;
            flex-wrap:wrap;
            gap:10px;
            margin-top:15px;
        }

        .danger{
            background:#e74c3c;
            color:white;
            border:none;
        }

        .success{
            background:#2ecc71;
            color:white;
            border:none;
        }

        .primary{
            background:#3498db;
            color:white;
            border:none;
        }

        .warning{
            background:#f39c12;
            color:white;
            border:none;
        }

        @media(max-width:900px){
            .grid{
                grid-template-columns:1fr;
            }

            .card{
                height:auto;
            }
        }
    </style>
</head>

<body>

<h2>⚡ Electric Company Outage Dashboard</h2>

<!-- TOPBAR -->
<div class="topbar">

    <label><b>Barangay:</b></label>

    <select id="barangayFilter" onchange="applyFilter()">
        <option value="all">All Barangays</option>
        <option value="Bonuan Gueset">Bonuan Gueset</option>
        <option value="Bonuan Boquig">Bonuan Boquig</option>
        <option value="Bonuan Binloc">Bonuan Binloc</option>
        <option value="Lucao">Lucao</option>
        <option value="Tapuac">Tapuac</option>
        <option value="Tambac">Tambac</option>
        <option value="Pantal">Pantal</option>
        <option value="Herrero-Perez">Herrero-Perez</option>
        <option value="Mayombo">Mayombo</option>
        <option value="Poblacion Oeste">Poblacion Oeste</option>
        <option value="Poblacion Este">Poblacion Este</option>
    </select>

    <!-- BARANGAY BULK -->
    <button class="primary" onclick="updateBarangay('under_review')">
        Set Barangay: Under Review
    </button>

    <button class="warning" onclick="updateBarangay('verified')">
        Verify Barangay
    </button>

    <button class="success" onclick="updateBarangay('resolved')">
        Resolve Barangay
    </button>

    <!-- DAGUPAN -->
    <button class="warning" onclick="updateDagupan('verified')">
        Verify ALL Dagupan
    </button>

    <button class="danger" onclick="updateDagupan('resolved')">
        Resolve ALL Dagupan
    </button>

</div>

<div class="grid">

    <!-- LEFT -->
    <div class="card">

        <h3>Outage Reports</h3>

        <div id="outageList">
            <p class="loading">Loading outages...</p>
        </div>

    </div>

    <!-- RIGHT -->
    <div class="card">

        <h3>Control Panel</h3>

        <div id="detailBox">
            Select an outage report
        </div>

    </div>

</div>

<script>

let outages = [];
let filteredOutages = [];
let selectedId = null;

/* ================= LOAD OUTAGES ================= */
async function loadOutages(){

    try{

        const res = await fetch(
            "http://localhost/crowdsourcedAPI/api/outage_report_electric_com/get.php",
            {
                credentials:"include"
            }
        );

        const result = await res.json();

        if(!result.success){
            alert(result.message || "Failed to load outages");
            return;
        }

        outages = result.data || [];

        applyFilter();

    }catch(err){

        console.error(err);

        document.getElementById("outageList").innerHTML = `
            <p class="empty">Failed to load outage reports.</p>
        `;
    }
}

/* ================= FILTER ================= */
function applyFilter(){

    const selected = document.getElementById("barangayFilter").value;

    if(selected === "all"){

        filteredOutages = outages;

    }else{

        filteredOutages = outages.filter(o =>
            (o.location_name || "").toLowerCase()
            .includes(selected.toLowerCase())
        );
    }

    renderList();
}

/* ================= RENDER LIST ================= */
function renderList(){

    const list = document.getElementById("outageList");

    list.innerHTML = "";

    if(filteredOutages.length === 0){

        list.innerHTML = `
            <p class="empty">No outage reports found.</p>
        `;

        return;
    }

    filteredOutages.forEach(o => {

        list.innerHTML += `
            <div class="item">

                <b>${o.location_name || 'Unknown Location'}</b><br>

                <span class="status ${o.status}">
                    ${o.status}
                </span>

                <br>

                <small>
                    ⚠ Severity: ${o.severity || "N/A"}<br>
                    👥 Affected Houses: ${o.affected_houses || 0}
                </small>

                <br>

                <button onclick="openOutage(${o.id})">
                    Manage
                </button>

            </div>
        `;
    });
}

/* ================= OPEN DETAIL ================= */
function openOutage(id){

    selectedId = id;

    const selected = filteredOutages.find(o => o.id == id);

    if(!selected) return;

    document.getElementById("detailBox").innerHTML = `

        <h3>${selected.location_name}</h3>

        <p>
            <b>Status:</b>
            <span class="status ${selected.status}">
                ${selected.status}
            </span>
        </p>

        <p>
            <b>Severity:</b> ${selected.severity || "N/A"}
        </p>

        <p>
            <b>Affected Houses:</b>
            ${selected.affected_houses || 0}
        </p>

        <div class="control-buttons">

            <button class="warning"
                onclick="updateSingle(${selected.id}, 'verified')">
                Verify
            </button>

            <button class="success"
                onclick="updateSingle(${selected.id}, 'resolved')">
                Resolve
            </button>

            <button class="primary"
                onclick="updateSingle(${selected.id}, 'under_review')">
                Under Review
            </button>

            <button class="danger"
                onclick="updateSingle(${selected.id}, 'rejected')">
                Reject
            </button>

        </div>
    `;
}

/* ================= SINGLE UPDATE ================= */
async function updateSingle(id, status){

    try{

        const res = await fetch(
            "http://localhost/crowdsourcedAPI/api/outage_report_electric_com/update_single.php",
            {
                method:"POST",
                headers:{
                    "Content-Type":"application/json"
                },
                credentials:"include",
                body: JSON.stringify({
                    id,
                    status
                })
            }
        );

        const result = await res.json();

        alert(result.message);

        loadOutages();

    }catch(err){

        console.error(err);
        alert("Failed to update outage.");
    }
}

/* ================= BARANGAY UPDATE ================= */
async function updateBarangay(status){

    const barangay =
        document.getElementById("barangayFilter").value;

    if(barangay === "all"){

        alert("Please select a specific barangay first.");
        return;
    }

    if(!confirm(
        `Apply "${status}" to all reports in ${barangay}?`
    )) return;

    try{

        const res = await fetch(
            "http://localhost/crowdsourcedAPI/api/outage_report_electric_com/update_barangay.php",
            {
                method:"POST",
                headers:{
                    "Content-Type":"application/json"
                },
                credentials:"include",
                body: JSON.stringify({
                    barangay,
                    status
                })
            }
        );

        const result = await res.json();

        alert(result.message);

        loadOutages();

    }catch(err){

        console.error(err);
        alert("Barangay update failed.");
    }
}

/* ================= DAGUPAN UPDATE ================= */
async function updateDagupan(status){

    if(!confirm(
        `Apply "${status}" to ALL Dagupan reports?`
    )) return;

    try{

        const res = await fetch(
            "http://localhost/crowdsourcedAPI/api/outage_report_electric_com/update_dagupan.php",
            {
                method:"POST",
                headers:{
                    "Content-Type":"application/json"
                },
                credentials:"include",
                body: JSON.stringify({
                    status
                })
            }
        );

        const result = await res.json();

        alert(result.message);

        loadOutages();

    }catch(err){

        console.error(err);
        alert("Dagupan update failed.");
    }
}

/* ================= INIT ================= */
loadOutages();

/* AUTO REFRESH */
setInterval(loadOutages, 10000);

</script>

</body>
</html>