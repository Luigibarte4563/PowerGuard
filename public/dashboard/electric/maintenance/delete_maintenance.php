<div id="maintenanceList"></div>

<script>
const token = localStorage.getItem("token");

/* =========================================
   LOAD MAINTENANCE
========================================= */
async function loadMaintenance() {

    try {

        const res = await fetch(
            "http://localhost/CrowdsourcedAPI/api/maintenance/get.php",
            {
                method: "GET",
                headers: {
                    "Authorization": `Bearer ${token}`
                },
                credentials: "include"
            }
        );

        const result = await res.json();

        const container = document.getElementById("maintenanceList");
        container.innerHTML = "";

        if (!result.success) {
            container.innerHTML = `<p style="color:red;">${result.message}</p>`;
            return;
        }

        if (!result.data || result.data.length === 0) {
            container.innerHTML = `<p>No maintenance found.</p>`;
            return;
        }

        result.data.forEach(item => {

            const div = document.createElement("div");

            div.style.border = "1px solid #ccc";
            div.style.padding = "12px";
            div.style.marginBottom = "10px";
            div.style.borderRadius = "8px";
            div.style.background = "#fff";

            /* SAFE ID CHECK */
            const id = Number(item.id);

            let barangays = Array.isArray(item.affected_barangays)
                ? item.affected_barangays.join(", ")
                : item.affected_barangays;

            div.innerHTML = `
                <h3>⚡ Maintenance #${id}</h3>

                <p><b>Company:</b> ${item.company_name ?? "N/A"}</p>
                <p><b>Date:</b> ${item.maintenance_date}</p>
                <p><b>Time:</b> ${item.start_time} - ${item.end_time}</p>
                <p><b>Radius:</b> ${item.radius} meters</p>
                <p><b>Barangays:</b> ${barangays}</p>
                <p><b>Description:</b> ${item.description ?? "No description"}</p>

                <button
                    style="background:red;color:white;padding:6px 10px;border:none;border-radius:5px;cursor:pointer;margin-top:8px;"
                    onclick="deleteMaintenance(${id}, this)">
                    Delete
                </button>
            `;

            container.appendChild(div);
        });

    } catch (err) {
        console.error(err);
        document.getElementById("maintenanceList").innerHTML =
            `<p style="color:red;">Failed to load data.</p>`;
    }
}

/* =========================================
   DELETE MAINTENANCE
========================================= */
async function deleteMaintenance(id, btn) {

    console.log("Deleting ID:", id); // DEBUG

    if (!id || isNaN(id)) {
        alert("Invalid maintenance ID");
        return;
    }

    if (!confirm("Are you sure?")) return;

    btn.disabled = true;
    btn.innerText = "Deleting...";

    try {

        const res = await fetch(
            "http://localhost/CrowdsourcedAPI/api/maintenance/delete.php",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Authorization": `Bearer ${token}`
                },
                credentials: "include",
                body: JSON.stringify({
                    maintenance_id: parseInt(id)
                })
            }
        );

        const result = await res.json();

        if (!result.success) {
            throw new Error(result.message);
        }

        alert("Deleted successfully");

        btn.parentElement.remove();

    } catch (err) {

        alert("Error: " + err.message);

        btn.disabled = false;
        btn.innerText = "Delete";
    }
}

/* INIT */
loadMaintenance();
</script>