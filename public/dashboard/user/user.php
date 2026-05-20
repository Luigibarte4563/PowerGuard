<?php

session_start();

require_once __DIR__ . '/../../../src/middleware/requireAuth.php';
require_once __DIR__ . '/../../../src/config/app.php';

$user = requireAuth();

/* =========================
   GOOGLE USER CHECK
========================= */
$isGoogleUser =
    !empty($user['google_id']) ||
    ($user['auth_provider'] ?? '') === 'google';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>PowerGuide Dashboard</title>

    <style>
        /* (UNCHANGED CSS - same as yours) */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial;
            background: #f4f6f9;
            color: #333;
        }

        nav {
            background: #fff;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            position: sticky;
            top: 0;
            z-index: 999;
        }

        nav a {
            text-decoration: none;
            color: #333;
            margin-right: 15px;
            font-weight: bold;
        }

        nav a:hover {
            color: #007bff;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 20px;
            background: white;
            padding: 25px;
            margin: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .profile img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin: 20px;
        }

        .card {
            padding: 25px;
            border-radius: 12px;
            color: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .card h2 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .blue {
            background: #007bff;
        }

        .red {
            background: #dc3545;
        }

        .orange {
            background: #fd7e14;
        }

        .green {
            background: #198754;
        }

        .box {
            background: white;
            padding: 20px;
            margin: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        button {
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            background: #007bff;
            color: white;
            cursor: pointer;
            margin-top: 10px;
        }

        button:hover {
            opacity: 0.9;
        }

        .notif-wrapper {
            position: relative;
        }

        #notifPanel {
            display: none;
            position: absolute;
            right: 0;
            top: 45px;
            width: 350px;
            max-height: 400px;
            overflow-y: auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            z-index: 999;
        }

        .notif-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
        }

        .notif-item.unread {
            background: #eef5ff;
        }

        #notifCount {
            position: absolute;
            top: -5px;
            right: -5px;
            background: red;
            color: white;
            border-radius: 50%;
            font-size: 12px;
            padding: 2px 7px;
            display: none;
        }

        .quick-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .quick-actions a {
            text-decoration: none;
        }
    </style>

</head>

<body>

    <nav>
        <div>
            <a href="user.php">Dashboard</a>
            <a href="reports/create_report.php">Create Report</a>
            <a href="reports/update_report.php">My Reports</a>
            <a href="<?= PUBLIC_URL ?>/logout.php">Logout</a>
        </div>

        <div class="notif-wrapper">
            <button onclick="markAllAsRead()" class="mark-all-btn">
                Mark All as Read
            </button>
            <button onclick="toggleNotifications()">🔔 Notifications</button>
            <span id="notifCount"></span>
            <div id="notifPanel"></div>
        </div>
    </nav>

    <?php
    $defaultPicture =
        "https://scontent.fcrk1-3.fna.fbcdn.net/v/t39.30808-6/702502276_945060285185090_8807500919375266217_n.jpg?stp=dst-jpg_s590x590_tt6&_nc_cat=101&ccb=1-7&_nc_sid=127cfc&_nc_eui2=AeGZF7SzhntumxZBovPokXMIypXBMxAwVSPKlcEzEDBVI0T1PkMym8zVYq6nc_eacWsmjGraxKx79YKFJqPoqkAL&_nc_ohc=1KgZabgToVMQ7kNvwHYIMKM&_nc_oc=Adod8ni62_h7Wkdq4rY_xB1t_8F0vnKyTd8DVyXQ5bENUeC2-yLn8FV9pqaoMjfo164&_nc_zt=23&_nc_ht=scontent.fcrk1-3.fna&_nc_gid=6rl-C5KQkk2f5gilvWkmpQ&_nc_ss=7b2a8&oh=00_Af5ouAYsT-Nt_9daKvbxl3pXoqW6PUxBFEjLprI99DAm8A&oe=6A11F917";

    $picture = !empty($user['picture']) ? $user['picture'] : $defaultPicture;
    ?>

    <div class="profile">
        <img src="<?= htmlspecialchars($picture) ?>">
        <div>
            <h1>Welcome <?= htmlspecialchars($user['name']) ?></h1>
            <p><?= htmlspecialchars($user['email']) ?></p>
        </div>
    </div>

    <!-- DASHBOARD -->
    <div class="cards">
        <div class="card blue">
            <h2 id="totalReports">0</h2>
            <p>📍 Available Stations</p>
        </div>
        <div class="card red">
            <h2 id="activeOutages">0</h2>
            <p>⚡ Active Outages</p>
        </div>
        <div class="card orange">
            <h2 id="maintenanceCount">0</h2>
            <p>🛠 Upcoming Maintenance</p>
        </div>
        <div class="card green">
            <h2 id="notifTotal">0</h2>
            <p>🔔 Notifications</p>
        </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="box">
        <h2>Quick Actions</h2>
        <div class="quick-actions">
            <a href="reports/create_report.php"><button>Create Report</button></a>
            <a href="reports/update_report.php"><button>View My Reports</button></a>
            <button onclick="useCurrentLocation()">📍 Update My Location</button>
        </div>
    </div>

    <!-- LOCATION -->
    <div class="box">
        <h2>📍 My Location</h2>

        <input type="text" id="location_name" placeholder="Enter location">

        <button onclick="updateLocation()">Save Location</button>
        <button onclick="useCurrentLocation()">Use Current Location</button>

        <p id="current_location">Loading...</p>
        <p id="current_coords"></p>
    </div>

    <!-- PROFILE UPDATE -->
    <div class="box">
        <h2>Edit Profile</h2>

        <form action="<?= BASE_URL ?>/src/api/user/update_profile.php" method="POST" enctype="multipart/form-data">

            <input type="text" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
            <input type="file" name="picture" accept="image/*">

            <button type="submit">Update Profile</button>
        </form>
    </div>

    <!-- PASSWORD (ONLY NON-GOOGLE USERS) -->
    <?php if (!$isGoogleUser): ?>

        <div class="box">
            <h2>Change Password</h2>

            <form action="<?= BASE_URL ?>/src/api/user/update_password.php" method="POST">

                <input type="password" name="current_password" placeholder="Current Password" required>
                <input type="password" name="new_password" placeholder="New Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm New Password" required>

                <button type="submit">Update Password</button>
            </form>
        </div>

    <?php else: ?>

        <div class="box">
            <h2>Change Password</h2>
            <p style="color:#777;">
                Google accounts cannot use local password change. Manage it in Google Account.
            </p>
        </div>

    <?php endif; ?>

    <script>

        /* FIXED: removed broken selector */
        let notifications = [];

        /* DASHBOARD STATS */
        async function loadDashboardStats() {
            try {

                /* ================= MY REPORTS ================= */
                const myReports = await fetch(
                    "http://localhost/crowdsourcedAPI/api/power_station/get_available.php",
                    {
                        method: "GET",
                        credentials: "include",
                        headers: {
                            "Accept": "application/json"
                        }
                    }
                );

                const myData = await myReports.json();

                document.getElementById("totalReports").innerText =
                    myData.total_available ?? 0;


                /* ================= ACTIVE OUTAGES ================= */
                const active = await fetch(
                    "http://localhost/crowdsourcedapi/api/outage_report/get_active.php",
                    { credentials: "include" }
                );

                const activeData = await active.json();

                console.log("ACTIVE API RESPONSE:", activeData);

                document.getElementById("activeOutages").innerText =
                    activeData.count ??
                    activeData.total ??
                    activeData.total_active_reports ??
                    (Array.isArray(activeData.data) ? activeData.data.length : 0);


                /* ================= MAINTENANCE ================= */
                const maintenance = await fetch(
                    "http://localhost/crowdsourcedapi/api/maintenance/get_upcoming.php",
                    { credentials: "include" }
                );

                const mData = await maintenance.json();

                document.getElementById("maintenanceCount").innerText =
                    mData.count ??
                    mData.upcoming_count ??
                    (Array.isArray(mData.data) ? mData.data.length : 0);

            } catch (e) {
                console.error("Dashboard Stats Error:", e);
            }
        }

        /* NOTIFICATIONS */
        async function loadNotifications() {
            try {
                const res = await fetch("http://localhost/crowdsourcedapi/api/notification/get.php", { credentials: "include" });
                const data = await res.json();
                notifications = data.data || [];
                renderNotifications();
            } catch (e) { console.error(e); }
        }

        function renderNotifications() {
            const panel = document.getElementById("notifPanel");
            const badge = document.getElementById("notifCount");

            const unread = notifications.filter(n => n.is_read == 0);

            badge.style.display = unread.length ? "inline-block" : "none";
            badge.innerText = unread.length;
            document.getElementById("notifTotal").innerText = unread.length;

            panel.innerHTML = notifications.map(n => `
        <div class="notif-item ${n.is_read == 0 ? 'unread' : ''}" onclick="markAsRead(${n.id})">
            <b>${n.title}</b><br><small>${n.message}</small>
        </div>
    `).join("");
        }

        function toggleNotifications() {
            const panel = document.getElementById("notifPanel");
            panel.style.display = panel.style.display === "block" ? "none" : "block";
            loadNotifications();
        }

        window.markAllAsRead = async function () {

            const res = await fetch("http://localhost/crowdsourcedapi/api/notification/mark_all_as_read.php", {
                method: "POST",
                credentials: "include",
                headers: {
                    "Content-Type": "application/json"
                }
            });

            const data = await res.json();

            if (!data.success) {
                console.error(data.message);
                return;
            }

            notifications = notifications.map(n => ({ ...n, is_read: 1 }));
            renderNotifications();
        };

        /* LOCATION */
        async function loadLocation() {
            const res = await fetch("http://localhost/crowdsourcedapi/api/user_location/get.php", { credentials: "include" });
            const data = await res.json();

            document.getElementById("current_location").innerText =
                "📍 " + (data.data.location_name || "No location");

            document.getElementById("current_coords").innerText =
                `Lat: ${data.data.latitude || "-"} | Lng: ${data.data.longitude || "-"}`;
        }

        async function updateLocation() {
            const location = document.getElementById("location_name").value;

            await fetch("http://localhost/crowdsourcedapi/api/user_location/location.php", {
                method: "POST",
                credentials: "include",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ location_name: location })
            });

            loadLocation();
        }

        function useCurrentLocation() {
            navigator.geolocation.getCurrentPosition(async (pos) => {
                await fetch("http://localhost/crowdsourcedapi/api/user_location/location.php", {
                    method: "POST",
                    credentials: "include",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        location_name: "My Location",
                        latitude: pos.coords.latitude,
                        longitude: pos.coords.longitude,
                        from_gps: true
                    })
                });

                loadLocation();
            });
        }

        /* INIT */
        loadDashboardStats();
        loadLocation();
        loadNotifications();
        setInterval(loadNotifications, 15000);

    </script>

</body>

</html>