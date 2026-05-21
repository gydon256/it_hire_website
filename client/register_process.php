<?php
require_once '../db_connect.php';
session_start();
if (isset($_SESSION['user_type'])) { header("Location: dashboard.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id               = trim($_POST['client_id']               ?? '');
    $full_name               = trim($_POST['full_name']               ?? '');
    $national_id             = trim($_POST['national_id']             ?? '');
    $organization_name       = trim($_POST['organization_name']       ?? '');
    $email                   = trim($_POST['email']                   ?? '');
    $phone_number            = trim($_POST['phone_number']            ?? '');
    $physical_address        = trim($_POST['physical_address']        ?? '');
    $industry                = trim($_POST['industry']                ?? '');
    $service_category_needed = trim($_POST['service_category_needed'] ?? '');
    $pass                    = $_POST['password']                    ?? '';
    $pass2                   = $_POST['password_confirm']             ?? '';

    if (empty($client_id) || empty($full_name) || empty($national_id) || empty($phone_number) || empty($physical_address) || empty($email) || empty($pass)) {
        header("Location: register.html?error=missing");
        exit;
    }

    if ($pass !== $pass2) {
        header("Location: register.html?error=password_mismatch");
        exit;
    }

    if (strlen($pass) < 6) {
        header("Location: register.html?error=password_short");
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: register.html?error=invalid_email");
        exit;
    }

    $db = getDB();
    $chk = $db->prepare("SELECT client_id FROM service_client WHERE client_id=? OR email=?");
    $chk->execute([$client_id, $email]);
    if ($chk->fetch()) {
        header("Location: register.html?error=exists");
        exit;
    }

    $stmt = $db->prepare("INSERT INTO service_client (client_id, full_name, national_id, organization_name, email, phone_number, physical_address, industry, service_category_needed, password_hash) VALUES(?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$client_id, $full_name, $national_id, $organization_name, $email, $phone_number, $physical_address, $industry, $service_category_needed, password_hash($pass, PASSWORD_DEFAULT)]);
    header("Location: ../login.html?registered=success");
    exit;
}

header("Location: register.html");
exit;
