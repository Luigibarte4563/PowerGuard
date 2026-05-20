<?php

session_start();

require_once __DIR__ . '/../../src/config/connection.php';

$conn = getConnection();
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* =========================================
   HANDLE FORM SUBMIT
========================================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name           = trim($_POST['name'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $passwordRaw    = $_POST['password'] ?? '';

    $company_name   = trim($_POST['company_name'] ?? '');
    $company_email  = trim($_POST['company_email'] ?? null);
    $contact_number = trim($_POST['contact_number'] ?? null);
    $address        = trim($_POST['address'] ?? null);

    /* =========================================
       VALIDATION
    ========================================= */
    if ($name === '' || $email === '' || $passwordRaw === '' || $company_name === '') {
        die("Missing required fields");
    }

    /* =========================================
       CHECK DUPLICATE USER EMAIL
    ========================================= */
    $checkUser = $conn->prepare("
        SELECT id FROM users WHERE email = :email LIMIT 1
    ");

    $checkUser->execute([":email" => $email]);

    if ($checkUser->fetch()) {
        die("User email already exists");
    }

    /* =========================================
       HASH PASSWORD
    ========================================= */
    $password = password_hash($passwordRaw, PASSWORD_BCRYPT);

    try {

        $conn->beginTransaction();

        /* =========================================
           CREATE USER (ELECTRIC COMPANY USER ONLY)
        ========================================= */
        $stmt = $conn->prepare("
            INSERT INTO users (
                name,
                email,
                password,
                role,
                auth_provider
            ) VALUES (
                :name,
                :email,
                :password,
                'electric_company',
                'local'
            )
        ");

        $stmt->execute([
            ":name" => $name,
            ":email" => $email,
            ":password" => $password
        ]);

        $user_id = $conn->lastInsertId();

        /*
         * OPTIONAL:
         * If you still want company info, you MUST store it in users table
         * (because electric_companies table no longer exists)
         */

        $conn->commit();

        echo "Electric company registered successfully!";

    } catch (Throwable $e) {

        $conn->rollBack();

        http_response_code(500);

        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Electric Company Registration</title>
</head>
<body>

<h2>Electric Company Registration</h2>

<form method="POST">

    <h3>User Account</h3>

    <input type="text" name="name" placeholder="Owner / Manager Name" required>
    <br><br>

    <input type="email" name="email" placeholder="Login Email" required>
    <br><br>

    <input type="password" name="password" placeholder="Password" required>
    <br><br>

    <h3>Company Information (stored later or optional)</h3>

    <input type="text" name="company_name" placeholder="Company Name" required>
    <br><br>

    <input type="email" name="company_email" placeholder="Company Email">
    <br><br>

    <input type="text" name="contact_number" placeholder="Contact Number">
    <br><br>

    <textarea name="address" placeholder="Company Address"></textarea>

    <br><br>

    <button type="submit">
        Register Electric Company
    </button>

</form>

</body>
</html>