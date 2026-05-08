<h3>Admin Registration (DEV ONLY)</h3>

<form action="admin_register.php" method="POST">

    <input type="text" name="name" placeholder="Admin Name" required><br><br>
    <input type="email" name="email" placeholder="Admin Email" required><br><br>

    <input type="password" name="password" placeholder="Password" required><br><br>

    <select name="role">
        <option value="admin">Admin</option>
    </select><br><br>

    <button type="submit">Create Admin</button>
</form>

<?php
session_start();
require_once __DIR__ . '/../../src/config/connection.php';

$conn = getConnection();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request");
}

$name = $_POST['name'] ?? null;
$email = $_POST['email'] ?? null;
$passwordRaw = $_POST['password'] ?? null;
$role = $_POST['role'] ?? 'admin';

if (!$name || !$email || !$passwordRaw) {
    die("Missing required fields");
}

/* =========================
   CHECK IF EMAIL EXISTS
========================= */
$check = $conn->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
$check->execute([":email" => $email]);

if ($check->fetch()) {
    die("Email already exists. Try another one.");
}

/* =========================
   HASH PASSWORD
========================= */
$password = password_hash($passwordRaw, PASSWORD_BCRYPT);

/* =========================
   INSERT ADMIN
========================= */
$stmt = $conn->prepare("
    INSERT INTO users (
        name,
        email,
        password,
        role,
        auth_provider,
        account_status,
        is_verified
    ) VALUES (
        :name,
        :email,
        :password,
        :role,
        'local',
        'active',
        1
    )
");

$stmt->execute([
    ":name" => $name,
    ":email" => $email,
    ":password" => $password,
    ":role" => $role
]);

echo "Admin created successfully!";