<?php
session_start();

require_once __DIR__ . '/../../../src/middleware/requireAuth.php';
require_once __DIR__ . '/../../../src/config/app.php';

$user = requireAuth();

if ($user['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/dashboard/user/user.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>

<style>

body {
    font-family: Arial;
    background: #f4f6f9;
    margin: 0;
}

/* ================= NAV ================= */

nav {
    background: #222;
    color: white;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

nav a {
    color: white;
    margin-right: 15px;
    text-decoration: none;
    font-weight: bold;
}

nav a:hover {
    opacity: 0.8;
}

/* ================= TITLE ================= */

h2 {
    padding: 20px;
}

/* ================= CARDS ================= */

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
    transition: 0.2s;
}

.card:hover {
    transform: translateY(-3px);
}

.card h2 {
    font-size: 34px;
    margin: 0;
}

.card p {
    margin: 10px 0 0;
    font-size: 14px;
}

/* COLORS */

.red { background: #dc3545; color: white; }
.blue { background: #007bff; color: white; }
.green { background: #28a745; color: white; }
.orange { background: #fd7e14; color: white; }

.loading {
    opacity: 0.6;
}

</style>
</head>

<body>

<!-- ================= NAV ================= -->

<nav>
    <div>
        <b>⚡ ADMIN PANEL</b>
    </div>

    <div>
        <a href="admin.php">Dashboard</a>
        <a href="users.php">Users</a>
        <a href="reports.php">Reports</a>
        <a href="<?= BASE_URL ?>/logout.php">Logout</a>
    </div>
</nav>

<!-- ================= HEADER ================= -->

<h2>Welcome Admin, <?= htmlspecialchars($user['name']) ?></h2>

<!-- ================= DASHBOARD CARDS ================= -->

<div class="container">

    <div class="card blue loading">
        <h2 id="users">0</h2>
        <p>Total Users</p>
    </div>

    <div class="card red loading">
        <h2 id="reports">0</h2>
        <p>Total Reports</p>
    </div>

    <div class="card orange loading">
        <h2 id="outages">0</h2>
        <p>Active Outages</p>
    </div>

    <div class="card green loading">
        <h2 id="maintenance">0</h2>
        <p>Maintenance</p>
    </div>

</div>

<!-- ================= SCRIPT ================= -->

<script>

/* =========================================
   ADMIN STATS FETCHER (ROBUST + CLEAN)
========================================= */

async function loadAdminStats() {

    try {
        const res = await fetch(
            "http://localhost/crowdsourcedapi/api/admin/dashboard_stats.php",
            {
                method: "GET",
                credentials: "include",
                headers: {
                    "Accept": "application/json"
                }
            }
        );

        const data = await res.json();

        console.log("Admin Dashboard API:", data);

        if (!data || !data.success) {
            throw new Error("Invalid API response");
        }

        /* ===============================
           UPDATE UI
        =============================== */

        updateCard("users", data.users);
        updateCard("reports", data.reports);
        updateCard("outages", data.active_outages ?? data.outages);
        updateCard("maintenance", data.maintenance);

    } catch (error) {
        console.error("Dashboard load failed:", error);

        setErrorState();
    }
}

/* =========================================
   UPDATE SINGLE CARD
========================================= */

function updateCard(id, value) {
    const el = document.getElementById(id);

    el.innerText = value ?? 0;

    el.parentElement.classList.remove("loading");
}

/* =========================================
   ERROR STATE
========================================= */

function setErrorState() {

    ["users", "reports", "outages", "maintenance"].forEach(id => {
        const el = document.getElementById(id);
        el.innerText = "—";
        el.parentElement.classList.remove("loading");
    });

}

/* =========================================
   AUTO REFRESH (REALTIME FEEL)
========================================= */

loadAdminStats();
setInterval(loadAdminStats, 10000);

</script>

</body>
</html>