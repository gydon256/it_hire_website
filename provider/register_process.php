<?php
require_once '../db_connect.php';
session_start();
if (isset($_SESSION['user_type'])) { header("Location: dashboard.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $provider_nin           = strtoupper(trim($_POST['provider_nin']           ?? ''));
    $full_name              = trim($_POST['full_name']              ?? '');
    $email                  = trim($_POST['email']                  ?? '');
    $phone_number           = trim($_POST['phone_number']           ?? '');
    $physical_address       = trim($_POST['physical_address']       ?? '');
    $specialization         = trim($_POST['specialization']         ?? '');
    $experience_years       = intval($_POST['experience_years']       ?? 0);
    $daily_rate             = intval($_POST['daily_rate']             ?? 0);
    $availability_status    = trim($_POST['availability_status']    ?? 'available');
    $bank_account_number    = trim($_POST['bank_account_number']    ?? '');
    $bank_name              = trim($_POST['bank_name']              ?? '');
    $mobile_money_number    = trim($_POST['mobile_money_number']    ?? '');
    $mobile_money_provider  = trim($_POST['mobile_money_provider']  ?? '');
    $commission_acknowledged = isset($_POST['commission_acknowledged']) ? 1 : 0;
    $pass                   = $_POST['password']                    ?? '';
    $pass2                  = $_POST['password_confirm']            ?? '';

    if (empty($provider_nin) || empty($full_name) || empty($phone_number) || empty($physical_address) || empty($specialization) || empty($email) || empty($pass)) {
        header("Location: register.html?error=missing");
        exit;
    }

    if ($experience_years < 0 || $experience_years > 50) {
        header("Location: register.html?error=invalid_exp");
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

    if (!$commission_acknowledged) {
        header("Location: register.html?error=commission");
        exit;
    }

    $db = getDB();
    $chk = $db->prepare("SELECT provider_nin FROM service_provider WHERE provider_nin=? OR email=?");
    $chk->execute([$provider_nin, $email]);
    if ($chk->fetch()) {
        header("Location: register.html?error=exists");
        exit;
    }

    $stmt = $db->prepare("INSERT INTO service_provider (provider_nin, full_name, email, phone_number, physical_address, specialization, experience_years, daily_rate, availability_status, bank_account_number, bank_name, mobile_money_number, mobile_money_provider, commission_acknowledged, password_hash) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$provider_nin, $full_name, $email, $phone_number, $physical_address, $specialization, $experience_years, $daily_rate, $availability_status, $bank_account_number, $bank_name, $mobile_money_number, $mobile_money_provider, $commission_acknowledged, password_hash($pass, PASSWORD_DEFAULT)]);
    header("Location: ../login.html?registered=success");
    exit;
}

header("Location: register.html");
exit;
