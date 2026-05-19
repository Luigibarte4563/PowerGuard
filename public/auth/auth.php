<?php
session_start();
$page = $_GET['page'] ?? 'login';

require_once __DIR__ . '/../../src/config/env.php';
require_once __DIR__ . '/../../src/config/app.php';

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

            <form action="<?= BASE_URL ?>/src/api/auth/jwt_login.php" method="POST">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Login</button>
            </form>

            <br>
            <a href="auth.php?page=register">No account? Register here</a>

        <?php else: ?>

            <?php if (isset($_SESSION['register_error'])): ?>

                <div style="color:red; margin-bottom:10px;">
                    <?= $_SESSION['register_error']; ?>
                </div>

                <?php unset($_SESSION['register_error']); ?>

            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>

                <div style="color:green; margin-bottom:10px;">
                    <?= $_SESSION['success']; ?>
                </div>

                <?php unset($_SESSION['success']); ?>

            <?php endif; ?>

            <h2>Register</h2>

            <form action="<?= BASE_URL ?>/src/api/auth/register.php" method="POST" id="registerForm">

                <input type="text" name="name" placeholder="Full Name" required>

                <input type="email" name="email" placeholder="Email" required>

                <input type="password" id="password" name="password" placeholder="Password" required>

                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password"
                    required>

                <div id="match-message"></div>

                <button type="submit" id="submitBtn">
                    Register
                </button>

            </form>

            <br>
            <a href="auth.php?page=login">Already have an account? Login</a>

        <?php endif; ?>

    </div>

    <!-- ================= GOOGLE LOGIN ================= -->
    <div id="g_id_onload" data-client_id="<?= $googleClientId ?>" data-auto_prompt="false"
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

            fetch("<?= BASE_URL ?>/src/api/auth/google_auth.php", {
                method: "POST",
                body: formData
            })
                .then(res => res.json())
                .then(res => {

                    if (!res.success) {
                        alert(res.message || "Login failed");
                        return;
                    }

                    if (res.role === "electric_company") {
                        window.location.href = "<?= PUBLIC_URL ?>/dashboard/electric/dashboard.php";
                    }
                    else {
                        window.location.href = "<?= PUBLIC_URL ?>/dashboard/user/user.php";
                    }
                })
                .catch(err => console.error(err));
        }

    </script>

</body>

</html>