<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Reports</title>

<style>
body{
    font-family: Arial;
    padding:20px;
    background:#f5f6fa;
}

.card{
    border:1px solid #ddd;
    padding:15px;
    margin-bottom:12px;
    border-radius:10px;
    background:#fff;
    box-shadow:0 2px 6px rgba(0,0,0,0.05);
}

.status{
    display:inline-block;
    padding:3px 8px;
    border-radius:5px;
    font-size:12px;
    color:#fff;
}

.active{ background:#f39c12; }
.under_review{ background:#3498db; }
.resolved{ background:#2ecc71; }
.rejected{ background:#e74c3c; }

button{
    width:100%;
    padding:10px;
    margin-top:10px;
    cursor:pointer;
    background:#e74c3c;
    color:#fff;
    border:none;
    border-radius:5px;
    font-weight:bold;
}

button:hover{
    background:#c0392b;
}

button:disabled{
    background:#aaa;
    cursor:not-allowed;
}
</style>
</head>

<body>

<h2>My Outage Reports</h2>

<div id="list"></div>

<script>

/* =========================================
   LOAD REPORTS
========================================= */
async function loadReports(){

    const list = document.getElementById("list");
    list.innerHTML = "<p>Loading reports...</p>";

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
            list.innerHTML = "<p>Failed to load reports</p>";
            return;
        }

        if(!result.data || result.data.length === 0){
            list.innerHTML = "<p>No reports found</p>";
            return;
        }

        list.innerHTML = result.data.map(r => {

            const canDelete = r.status === "active" || r.status === "under_review";

            return `
                <div class="card">

                    <h3>${r.location_name}</h3>
                    <p>${r.description ?? "No description"}</p>

                    <p>
                        <span class="status ${r.status}">
                            ${r.status.toUpperCase()}
                        </span>
                    </p>

                    <small>
                        Category: ${r.category} | Severity: ${r.severity}
                    </small>

                    <button 
                        onclick="deleteReport(${r.id})"
                        ${!canDelete ? "disabled" : ""}
                    >
                        ${canDelete ? "Cancel Report" : "Locked (Handled)"}
                    </button>

                </div>
            `;
        }).join("");

    } catch(err){
        console.error(err);
        list.innerHTML = "<p>Server error</p>";
    }
}


/* =========================================
   CANCEL REPORT (SOFT DELETE)
========================================= */
async function deleteReport(id){

    if(!confirm("Cancel this report?")){
        return;
    }

    try {

        const res = await fetch(
            "http://localhost/crowdsourcedapi/api/outage_report/delete.php",
            {
                method: "POST",
                credentials: "include",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ id })
            }
        );

        const result = await res.json();

        alert(result.message || "No response");

        if(result.success){
            loadReports();
        }

    } catch(err){
        console.error(err);
        alert("Failed to cancel report");
    }
}


/* INIT */
loadReports();

</script>

</body>
</html>