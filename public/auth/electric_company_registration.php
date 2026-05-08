<?php

session_start();

require_once __DIR__ . '/../../src/config/connection.php';

$conn = getConnection();

/* =========================================
   HANDLE FORM SUBMIT
========================================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name            = trim($_POST['name'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $passwordRaw     = $_POST['password'] ?? '';

    $company_name    = trim($_POST['company_name'] ?? '');
    $company_email   = trim($_POST['company_email'] ?? '');
    $contact_number  = trim($_POST['contact_number'] ?? '');
    $address         = trim($_POST['address'] ?? '');

    /* =========================================
       VALIDATION
    ========================================= */
    if (
        !$name ||
        !$email ||
        !$passwordRaw ||
        !$company_name
    ) {
        die("Missing required fields");
    }

    /* =========================================
       CHECK EMAIL EXISTS
    ========================================= */
    $check = $conn->prepare("
        SELECT id
        FROM users
        WHERE email = :email
        LIMIT 1
    ");

    $check->execute([
        ":email" => $email
    ]);

    if ($check->fetch()) {
        die("Email already exists");
    }

    /* =========================================
       HASH PASSWORD
    ========================================= */
    $password = password_hash($passwordRaw, PASSWORD_BCRYPT);

    try {

        $conn->beginTransaction();

        /* =========================================
           CREATE USER
        ========================================= */
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
                'electric_company',
                'local',
                'active',
                1
            )
        ");

        $stmt->execute([
            ":name" => $name,
            ":email" => $email,
            ":password" => $password
        ]);

        $user_id = $conn->lastInsertId();

        /* =========================================
           CREATE ELECTRIC COMPANY
        ========================================= */
        $companyStmt = $conn->prepare("
            INSERT INTO electric_companies (
                user_id,
                company_name,
                company_email,
                contact_number,
                address,
                verification_status
            ) VALUES (
                :user_id,
                :company_name,
                :company_email,
                :contact_number,
                :address,
                'verified'
            )
        ");

        $companyStmt->execute([
            ":user_id" => $user_id,
            ":company_name" => $company_name,
            ":company_email" => $company_email,
            ":contact_number" => $contact_number,
            ":address" => $address
        ]);

        $conn->commit();

        echo "Electric company registered successfully!";

    } catch (PDOException $e) {

        $conn->rollBack();

        die("Database error: " . $e->getMessage());
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

    <input
        type="text"
        name="name"
        placeholder="Owner / Manager Name"
        required
    >
    <br><br>

    <input
        type="email"
        name="email"
        placeholder="Login Email"
        required
    >
    <br><br>

    <input
        type="password"
        name="password"
        placeholder="Password"
        required
    >
    <br><br>

    <h3>Company Information</h3>

    <input
        type="text"
        name="company_name"
        placeholder="Company Name"
        required
    >
    <br><br>

    <input
        type="email"
        name="company_email"
        placeholder="Company Email"
    >
    <br><br>

    <input
        type="text"
        name="contact_number"
        placeholder="Contact Number"
    >
    <br><br>

    <textarea
        name="address"
        placeholder="Company Address"
    ></textarea>

    <br><br>

    <button type="submit">
        Register Electric Company
    </button>

</form>

</body>
</html>