<?php
require_once '../db_connect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';

    if (empty($email) || empty($password)) {
        header("Location: login.html?error=missing");
        exit;
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM admin_company WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['user_type']     = 'admin';
        $_SESSION['user_id']       = $admin['company_id'];
        $_SESSION['user_name']     = $admin['company_name'];
        $_SESSION['user_initials'] = strtoupper(substr($admin['company_name'],0,1));
        header("Location: dashboard.php");
        exit;
    } else {
        header("Location: login.html?error=invalid_credentials");
        exit;
    }
}

header("Location: login.html");
exit;
