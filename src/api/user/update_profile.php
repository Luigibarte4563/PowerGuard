<?php

session_start();

require_once __DIR__ . '/../../../src/config/connection.php';
require_once __DIR__ . '/../../../src/config/app.php';

$conn = getConnection();

/* =========================
   AUTH CHECK
========================= */
if (!isset($_SESSION['user'])) {
    header("Location: " . PUBLIC_URL . "/auth/auth.php?page=login");
    exit();
}

/* =========================
   METHOD CHECK
========================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . PUBLIC_URL . "/dashboard/user/user.php");
    exit();
}

/* =========================
   INPUT
========================= */
$name  = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');

if ($name === '' || $email === '') {
    header("Location: " . PUBLIC_URL . "/dashboard/update_profile.php?error=empty_fields");
    exit();
}

/* =========================
   IMAGE UPLOAD
========================= */
$picturePath = null;

if (isset($_FILES['picture']) && $_FILES['picture']['error'] === 0) {

    $uploadDir = __DIR__ . "/../../../public/uploads/";

    // ensure folder exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = time() . "_" . basename($_FILES["picture"]["name"]);
    $targetFile = $uploadDir . $fileName;

    // safer extension check
    $imageFileType = strtolower(pathinfo($_FILES["picture"]["name"], PATHINFO_EXTENSION));

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($imageFileType, $allowed)) {
        header("Location: " . PUBLIC_URL . "/dashboard/update_profile.php?error=invalid_image");
        exit();
    }

    if (move_uploaded_file($_FILES["picture"]["tmp_name"], $targetFile)) {

        // ✅ FIXED: correct public URL path (NO HARDCODED PowerGuide)
        $picturePath = PUBLIC_URL . "/uploads/" . $fileName;
    }
}

/* =========================
   UPDATE DATABASE
========================= */
$user_id = $_SESSION['user']['id'];

if ($picturePath) {

    $sql = "UPDATE users 
            SET name = ?, email = ?, picture = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$name, $email, $picturePath, $user_id]);

} else {

    $sql = "UPDATE users 
            SET name = ?, email = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$name, $email, $user_id]);
}

/* =========================
   UPDATE SESSION
========================= */
$_SESSION['user']['name'] = $name;
$_SESSION['user']['email'] = $email;

if ($picturePath) {
    $_SESSION['user']['picture'] = $picturePath;
}

/* =========================
   REDIRECT SUCCESS
========================= */
header("Location: " . PUBLIC_URL . "/dashboard/user/user.php?success=1");
exit();