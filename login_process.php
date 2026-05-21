<?php
/*
=====================================================
 SECTION: LOGIN PROCESS HANDLER
 HANDLED BY: MILAKA ABUBAKER
 CONTRIBUTION: Developed login authentication logic
 for both company and job seeker users (session
 handling, password verification, and redirects).
=====================================================
*/
require_once 'db_connect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $role     = $_POST['role']          ?? '';

    if (empty($email) || empty($password) || empty($role)) {
        header("Location: login.html?error=missing");
        exit;
    }

    $db = getDB();

    if ($role === 'client') {
        $stmt = $db->prepare("SELECT * FROM service_client WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_type']     = 'client';
            $_SESSION['user_id']       = $user['client_id'];
            $_SESSION['user_name']     = $user['full_name'];
            $initials = strtoupper(substr($user['full_name'],0,1));
            $_SESSION['user_initials'] = $initials;
            header("Location: client/dashboard.php");
            exit;
        } else {
            header("Location: login.html?error=invalid_credentials&role=client");
            exit;
        }
    } elseif ($role === 'provider') {
        $stmt = $db->prepare("SELECT * FROM service_provider WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_type']     = 'provider';
            $_SESSION['user_id']       = $user['provider_nin'];
            $_SESSION['user_name']     = $user['full_name'];
            $_SESSION['user_initials'] = strtoupper(substr($user['full_name'],0,1));
            header("Location: provider/dashboard.php");
            exit;
        } else {
            header("Location: login.html?error=invalid_credentials&role=provider");
            exit;
        }
    }
}

header("Location: login.html");
exit;
