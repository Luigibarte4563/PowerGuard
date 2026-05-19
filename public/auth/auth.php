<?php
session_start();
$page = $_GET['page'] ?? 'login';

require_once __DIR__ . '/../../src/config/env.php';

$googleClientId = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Auth System</title>

<script src="https://accounts.google.com/gsi/client" async></script>

</head>

<body>

<div class="box">

<?php if ($page === 'login'): ?>

    <h2>Login</h2>

    <form action="../api/auth/jwt_login.php" method="POST">
        <input type="email" name="email" placeholder="Email" required><br><br>
        <input type="password" name="password" placeholder="Password" required><br><br>
        <button type="submit">Login</button>
    </form>

    <br>
    <a href="auth.php?page=register">No account? Register here</a>

<?php else: ?>

    <h2>Register</h2>

    <form action="../../auth/register.php" method="POST" id="registerForm">

        <input type="text" name="name" placeholder="Full Name" required><br><br>
        <input type="email" name="email" placeholder="Email" required><br><br>

        <input type="password" id="password" name="password" placeholder="Password" required><br><br>
        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required><br><br>

        <div id="match-message"></div><br>

        <button type="submit" id="submitBtn">Register</button>
    </form>

    <br>
    <a href="auth.php?page=login">Already have an account? Login</a>

<?php endif; ?>

</div>

<!-- ================= GOOGLE LOGIN ================= -->
<div
    id="g_id_onload"
    data-client_id="<?= $googleClientId ?>"
    data-auto_prompt="false"
    data-callback="handleCredentialResponse">
</div>

<div class="g_id_signin"></div>

<script>

/* ================= PASSWORD MATCH ================= */
const password = document.getElementById("password");
const confirmPassword = document.getElementById("confirm_password");
const matchMessage = document.getElementById("match-message");
const submitBtn = document.getElementById("submitBtn");

function checkMatch() {

    if (!confirmPassword) return;

    if (password.value === confirmPassword.value) {
        matchMessage.innerHTML = "Passwords match";
        matchMessage.style.color = "green";
        submitBtn.disabled = false;
    } else {
        matchMessage.innerHTML = "Passwords do not match";
        matchMessage.style.color = "red";
        submitBtn.disabled = true;
    }
}

if (confirmPassword) {
    confirmPassword.addEventListener("input", checkMatch);
    password.addEventListener("input", checkMatch);
}

/* ================= GOOGLE DECODE ================= */
function decodeJWT(token) {
    const base64Url = token.split(".")[1];
    const base64 = base64Url.replace(/-/g, "+").replace(/_/g, "/");

    return JSON.parse(
        decodeURIComponent(
            atob(base64)
                .split("")
                .map(c => "%" + ("00" + c.charCodeAt(0).toString(16)).slice(-2))
                .join("")
        )
    );
}

/* ================= GOOGLE LOGIN ================= */
function handleCredentialResponse(response) {

    const data = decodeJWT(response.credential);

    const formData = new FormData();
    formData.append("name", data.name);
    formData.append("email", data.email);
    formData.append("picture", data.picture);
    formData.append("sub", data.sub);

    fetch("../api/auth/google_auth.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())   // FIXED (IMPORTANT)
    .then(res => {

        if (!res.success) {
            alert(res.message || "Login failed");
            return;
        }

        // ROLE REDIRECT FIX
        if (res.role === "admin") {
            window.location.href = "../dashboard/admin/dashboard.php";
        }
        else if (res.role === "electric_company") {
            window.location.href = "../dashboard/electric/dashboard.php";
        }
        else {
            window.location.href = "../dashboard/user/user.php";
        }
    })
    .catch(err => console.error(err));
}

</script>

</body>
</html>