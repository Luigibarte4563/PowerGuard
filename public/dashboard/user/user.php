<?php

session_start();

require_once __DIR__ . '/../../../src/middleware/requireAuth.php';
require_once __DIR__ . '/../../../src/config/app.php';

$user = requireAuth();

?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<title>PowerGuide Dashboard</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:Arial, sans-serif;
    background:#f4f6f9;
    color:#333;
}

/* ================= NAVBAR ================= */

nav{
    background:#fff;
    padding:15px 30px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 2px 8px rgba(0,0,0,0.08);
    position:sticky;
    top:0;
    z-index:999;
}

nav a{
    text-decoration:none;
    color:#333;
    margin-right:15px;
    font-weight:bold;
}

nav a:hover{
    color:#007bff;
}

/* ================= PROFILE ================= */

.profile{
    display:flex;
    align-items:center;
    gap:20px;
    background:white;
    padding:25px;
    margin:20px;
    border-radius:12px;
    box-shadow:0 2px 8px rgba(0,0,0,0.08);
}

.profile img{
    width:100px;
    height:100px;
    border-radius:50%;
    object-fit:cover;
}

/* ================= DASHBOARD CARDS ================= */

.cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:20px;
    margin:20px;
}

.card{
    padding:25px;
    border-radius:12px;
    color:white;
    box-shadow:0 2px 8px rgba(0,0,0,0.08);
}

.card h2{
    font-size:32px;
    margin-bottom:10px;
}

.card p{
    font-size:16px;
}

.blue{
    background:#007bff;
}

.red{
    background:#dc3545;
}

.orange{
    background:#fd7e14;
}

.green{
    background:#198754;
}

/* ================= BOX ================= */

.box{
    background:white;
    padding:20px;
    margin:20px;
    border-radius:12px;
    box-shadow:0 2px 8px rgba(0,0,0,0.08);
}

input,
textarea,
select{
    width:100%;
    padding:10px;
    margin-top:10px;
    border:1px solid #ccc;
    border-radius:8px;
}

button{
    padding:10px 15px;
    border:none;
    border-radius:8px;
    background:#007bff;
    color:white;
    cursor:pointer;
    margin-top:10px;
}

button:hover{
    opacity:0.9;
}

/* ================= NOTIFICATIONS ================= */

.notif-wrapper{
    position:relative;
}

#notifPanel{
    display:none;
    position:absolute;
    right:0;
    top:45px;
    width:350px;
    max-height:400px;
    overflow-y:auto;
    background:white;
    border-radius:12px;
    box-shadow:0 4px 15px rgba(0,0,0,0.15);
    z-index:999;
}

.notif-item{
    padding:15px;
    border-bottom:1px solid #eee;
    cursor:pointer;
}

.notif-item.unread{
    background:#eef5ff;
}

#notifCount{
    position:absolute;
    top:-5px;
    right:-5px;
    background:red;
    color:white;
    border-radius:50%;
    font-size:12px;
    padding:2px 7px;
    display:none;
}

.quick-actions{
    display:flex;
    gap:15px;
    flex-wrap:wrap;
}

.quick-actions a{
    text-decoration:none;
}

</style>

</head>

<body>

<!-- ================= NAVBAR ================= -->

<nav>

    <div>
        <a href="user.php">Dashboard</a>
        <a href="reports/create_report.php">Create Report</a>
        <a href="reports/update_report.php">My Reports</a>
        <a href="<?= BASE_URL ?>/logout.php">Logout</a>
    </div>

    <div class="notif-wrapper">

        <button onclick="toggleNotifications()">
            🔔 Notifications
        </button>

        <span id="notifCount"></span>

        <div id="notifPanel"></div>

    </div>

</nav>

<!-- ================= PROFILE ================= -->

<?php

$defaultPicture =
"https://scontent.fbag1-2.fna.fbcdn.net/v/t1.15752-9/667329625_832141525960325_566936363299643684_n.jpg";

$picture = !empty($user['picture'])
    ? $user['picture']
    : $defaultPicture;

?>

<div class="profile">

    <img src="<?= htmlspecialchars($picture) ?>">

    <div>
        <h1>Welcome <?= htmlspecialchars($user['name']) ?></h1>
        <p><?= htmlspecialchars($user['email']) ?></p>
    </div>

</div>

<!-- ================= DASHBOARD CARDS ================= -->

<div class="cards">

    <div class="card blue">
        <h2 id="totalReports">0</h2>
        <p>📍 My Reports</p>
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

<!-- ================= QUICK ACTIONS ================= -->

<div class="box">

    <h2>Quick Actions</h2>

    <br>

    <div class="quick-actions">

        <a href="reports/create_report.php">
            <button>Create Report</button>
        </a>

        <a href="reports/update_report.php">
            <button>View My Reports</button>
        </a>

        <button onclick="useCurrentLocation()">
            📍 Update My Location
        </button>

    </div>

</div>

<!-- ================= LOCATION ================= -->

<div class="box">

    <h2>📍 My Location</h2>

    <input type="text"
           id="location_name"
           placeholder="Enter location">

    <button onclick="updateLocation()">
        Save Location
    </button>

    <button onclick="useCurrentLocation()">
        Use Current Location
    </button>

    <br><br>

    <p id="current_location">Loading...</p>
    <p id="current_coords"></p>

</div>

<!-- ================= PROFILE UPDATE ================= -->

<div class="box">

    <h2>Edit Profile</h2>

    <form action="<?= BASE_URL ?>/api/user/update_profile.php"
          method="POST"
          enctype="multipart/form-data">

        <input type="text"
               name="name"
               value="<?= htmlspecialchars($user['name']) ?>"
               required>

        <input type="email"
               name="email"
               value="<?= htmlspecialchars($user['email']) ?>"
               required>

        <input type="file" name="picture">

        <button type="submit">
            Update Profile
        </button>

    </form>

</div>

<script>

let notifications = [];

/* =========================================
   LOAD DASHBOARD STATS
========================================= */

async function loadDashboardStats(){

    try{

        /* ===============================
           ELEMENTS
        =============================== */
        const totalReportsEl =
            document.getElementById("totalReports");

        const activeOutagesEl =
            document.getElementById("activeOutages");

        const maintenanceCountEl =
            document.getElementById("maintenanceCount");

        /* ===============================
           LOAD MY REPORTS
        =============================== */
        const myReportsRes = await fetch(
            "http://localhost/crowdsourcedapi/api/outage_report/get_my_report.php",
            {
                credentials:"include"
            }
        );

        const myReportsData =
            await myReportsRes.json();

        if(
            myReportsData.success &&
            totalReportsEl
        ){

            totalReportsEl.innerText =
                myReportsData.count ?? 0;
        }

        /* ===============================
           LOAD ACTIVE OUTAGES
        =============================== */
        const activeRes = await fetch(
            "http://localhost/crowdsourcedapi/api/outage_report/get_active.php",
            {
                credentials:"include"
            }
        );

        const activeData =
            await activeRes.json();

        if(
            activeData.success &&
            activeOutagesEl
        ){

            activeOutagesEl.innerText =
                activeData.total_active_reports ??
                activeData.count ??
                0;
        }

        /* ===============================
           LOAD UPCOMING MAINTENANCE
        =============================== */
        const maintenanceRes = await fetch(
            "http://localhost/crowdsourcedapi/api/maintenance/get_upcoming.php",
            {
                credentials:"include"
            }
        );

        const maintenanceData =
            await maintenanceRes.json();

        if(
            maintenanceData.success &&
            maintenanceCountEl
        ){

            /* FIX:
               uses upcoming_count from API
            */
            maintenanceCountEl.innerText =
                maintenanceData.upcoming_count ??
                maintenanceData.count ??
                0;
        }

    }catch(err){

        console.error(
            "Dashboard Stats Error:",
            err
        );
    }
}

/* LOAD */
loadDashboardStats();

/* =========================================
   NOTIFICATIONS
========================================= */

async function loadNotifications(){

    try{

        const res = await fetch(
            "http://localhost/crowdsourcedapi/api/notification/get.php",
            { credentials:"include" }
        );

        const data = await res.json();

        if(!data.success) return;

        notifications = data.data || [];

        renderNotifications();

    }catch(err){
        console.error(err);
    }
}

function renderNotifications(){

    const panel = document.getElementById("notifPanel");
    const badge = document.getElementById("notifCount");

    const unread = notifications.filter(n => n.is_read == 0);

    badge.style.display = unread.length ? "inline-block" : "none";
    badge.innerText = unread.length;

    document.getElementById("notifTotal").innerText = unread.length;

    panel.innerHTML = notifications.length
        ? notifications.map(n => `
            <div class="notif-item ${n.is_read == 0 ? 'unread' : ''}"
                 onclick="markAsRead(${n.id})">

                <b>${n.title}</b><br>
                <small>${n.message}</small>

            </div>
        `).join("")
        : "<div style='padding:15px'>No notifications</div>";
}

function toggleNotifications(){

    const panel = document.getElementById("notifPanel");

    panel.style.display =
        panel.style.display === "block" ? "none" : "block";

    loadNotifications();
}

async function markAsRead(id){

    await fetch(
        "http://localhost/crowdsourcedapi/api/notifications/mark_as_read.php",
        {
            method:"POST",
            credentials:"include",
            headers:{ "Content-Type":"application/json" },
            body:JSON.stringify({id})
        }
    );

    notifications = notifications.map(n =>
        n.id === id ? {...n, is_read:1} : n
    );

    renderNotifications();
}

/* =========================================
   LOAD LOCATION
========================================= */

async function loadLocation(){

    try{

        const res = await fetch(
            "http://localhost/crowdsourcedapi/api/user_location/get.php",
            { credentials:"include" }
        );

        const data = await res.json();

        if(!data.success) return;

        document.getElementById("current_location").innerText =
            "📍 " + (data.data.location_name || "No location");

        document.getElementById("current_coords").innerText =
            `Lat: ${data.data.latitude || "-"} | Lng: ${data.data.longitude || "-"}`;

        document.getElementById("location_name").value =
            data.data.location_name || "";

    }catch(err){
        console.error(err);
    }
}

/* =========================================
   UPDATE LOCATION
========================================= */

async function updateLocation(){

    const location = document.getElementById("location_name").value;

    if(!location){
        alert("Enter location");
        return;
    }

    const res = await fetch(
        "http://localhost/crowdsourcedapi/api/user_location/location.php",
        {
            method:"POST",
            credentials:"include",
            headers:{ "Content-Type":"application/json" },
            body:JSON.stringify({
                location_name: location,
                from_gps:false
            })
        }
    );

    const data = await res.json();

    if(data.success){
        loadLocation();
    }else{
        alert(data.message);
    }
}

/* =========================================
   USE CURRENT LOCATION
========================================= */

function useCurrentLocation(){

    navigator.geolocation.getCurrentPosition(async(pos)=>{

        const latitude  = pos.coords.latitude;
        const longitude = pos.coords.longitude;

        const res = await fetch(
            "http://localhost/crowdsourcedapi/api/user_location/location.php",
            {
                method:"POST",
                credentials:"include",
                headers:{ "Content-Type":"application/json" },
                body:JSON.stringify({
                    location_name:"My Current Location",
                    latitude,
                    longitude,
                    from_gps:true
                })
            }
        );

        const data = await res.json();

        if(data.success){
            alert("Location updated");
            loadLocation();
        }

    }, ()=>{
        alert("Location permission denied");
    });
}

/* =========================================
   INIT
========================================= */

loadDashboardStats();
loadLocation();
loadNotifications();

setInterval(loadNotifications, 15000);

</script>
</body>
</html>