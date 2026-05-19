<?php
session_start();

require_once __DIR__ . '/../../../src/middleware/requireAuth.php';
require_once __DIR__ . '/../../../src/config/app.php';

$user = requireAuth();

if ($user['role'] !== 'electric_company') {
    header("Location: " . BASE_URL . "/dashboard/user/user.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Electric Company Dashboard</title>

<style>

body {
    font-family: Arial;
    background: #eef2f7;
    margin: 0;
}

nav {
    background: #111;
    color: white;
    padding: 15px;
    display: flex;
    justify-content: space-between;
}

nav a {
    color: white;
    margin-right: 15px;
    text-decoration: none;
}

.container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 15px;
    padding: 20px;
}

.card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
}

.card h2 {
    font-size: 36px;
    margin: 10px 0;
}

.blue { background: #007bff; color: white; }
.green { background: #28a745; color: white; }

button {
    padding: 10px;
    border: none;
    background: #007bff;
    color: white;
    border-radius: 6px;
    cursor: pointer;
}

.loading {
    opacity: 0.6;
}

</style>
</head>

<body>

<nav>
    <b>ELECTRIC COMPANY PANEL</b>
    <div>
        <a href="#">Dashboard</a>
        <a href="<?= PUBLIC_URL ?>/dashboard/electric/reports/update.php">Outages</a>
        <a href="<?= PUBLIC_URL ?>/dashboard/electric/maintenance/create_maintenance.php">Maintenance</a>
        <a href="<?= PUBLIC_URL ?>/logout.php">Logout</a>
    </div>
</nav>

<h2 style="padding:20px;">
    Welcome <?= htmlspecialchars($user['name']) ?>
</h2>

<!-- ================= ONLY 2 CARDS ================= -->
<div class="container">

    <div class="card blue loading">
        <h2 id="active">0</h2>
        <p>⚡ Active Outages</p>
    </div>

    <div class="card green loading">
        <h2 id="resolved">0</h2>
        <p>✅ Resolved Outages</p>
    </div>

</div>

<!-- ================= QUICK ACTIONS ================= -->
<h3 style="padding:20px;">Quick Actions</h3>

<div style="padding:20px;">

    <button onclick="location.href='outages.php'">
        View Outages
    </button>

    <button onclick="location.href='maintenance.php'">
        Manage Maintenance
    </button>

</div>

<script>

/* =========================================
   ELECTRIC COMPANY STATS
========================================= */

async function loadCompanyStats() {

    try {

        /* ================= ACTIVE REPORTS ================= */
        const activeRes = await fetch(
            "http://localhost/crowdsourcedAPI/api/outage_report/get_active.php",
            {
                method: "GET",
                credentials: "include",
                headers: { "Accept": "application/json" }
            }
        );

        const activeData = await activeRes.json();

        console.log("Active API:", activeData);

        if (!activeData.success) {
            throw new Error("Active API failed");
        }

        /* ================= RESOLVED REPORTS ================= */
        const resolvedRes = await fetch(
            "http://localhost/crowdsourcedAPI/api/outage_report/get_resolve.php",
            {
                method: "GET",
                credentials: "include",
                headers: { "Accept": "application/json" }
            }
        );

        const resolvedData = await resolvedRes.json();

        console.log("Resolved API:", resolvedData);

        if (!resolvedData.success) {
            throw new Error("Resolved API failed");
        }

        /* ================= UPDATE UI ================= */

        // ACTIVE
        setCard("active", activeData.total_active_reports);

        // RESOLVED
        setCard("resolved", resolvedData.total_resolved);

    } catch (err) {

        console.error("Dashboard error:", err);

        setErrorState();
    }
}

/* ================= UI UPDATE ================= */
function setCard(id, value) {

    const el = document.getElementById(id);

    if (!el) return;

    el.innerText = value ?? 0;

    el.parentElement.classList.remove("loading");
}

/* ================= ERROR STATE ================= */
function setErrorState() {

    ["active", "resolved"].forEach(id => {

        const el = document.getElementById(id);

        if (!el) return;

        el.innerText = "—";

        el.parentElement.classList.remove("loading");
    });
}

/* ================= INIT ================= */
loadCompanyStats();

/* AUTO REFRESH EVERY 10 SECONDS */
setInterval(loadCompanyStats, 10000);

</script>
</body>
</html>