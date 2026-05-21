<?php
require_once '../db_connect.php';
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'client') {
    header("Location: ../login.html"); exit;
}
$clientId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id  = strtoupper(trim($_POST['request_id']  ?? ''));
    $title       = trim($_POST['title']                   ?? '');
    $description = trim($_POST['description']             ?? '');
    $budget      = intval($_POST['budget']                ?? 0);
    $deadline    = trim($_POST['deadline']               ?? '');
    $category    = trim($_POST['category']               ?? '');

    if (empty($request_id)) {
        header("Location: post_request.html?error=missing_id");
        exit;
    }
    if (empty($title)) {
        header("Location: post_request.html?error=missing_title");
        exit;
    }
    if (empty($description)) {
        header("Location: post_request.html?error=missing_desc");
        exit;
    }
    if ($budget <= 0) {
        header("Location: post_request.html?error=invalid_budget");
        exit;
    }
    if (empty($deadline)) {
        header("Location: post_request.html?error=missing_deadline");
        exit;
    }
    if (strtotime($deadline) <= time()) {
        header("Location: post_request.html?error=past_deadline");
        exit;
    }

    $db = getDB();

    // Check if client is verified (either payment_verified flag OR has approved payment)
    $stmt = $db->prepare("SELECT payment_verified FROM service_client WHERE client_id=?");
    $stmt->execute([$clientId]);
    $client = $stmt->fetch();

    // Check if client has approved verification fee payment
    $stmt = $db->prepare("SELECT payment_id FROM payment WHERE client_id = ? AND payment_type = 'verification_fee' AND payment_status IN ('escrowed', 'released')");
    $stmt->execute([$clientId]);
    $verificationPayment = $stmt->fetch();

    // Allow posting if either payment_verified = 1 OR has approved payment
    if (!$client['payment_verified'] && !$verificationPayment) {
        header("Location: post_request.html?error=not_verified");
        exit;
    }

    // Update client verification status to fully_verified if not already
    if ($client['verification_status'] !== 'fully_verified') {
        $stmt = $db->prepare("UPDATE service_client SET verification_status = 'fully_verified', payment_verified = 1 WHERE client_id = ?");
        $stmt->execute([$clientId]);
    }
    
    $chk = $db->prepare("SELECT request_id FROM service_request WHERE request_id=?");
    $chk->execute([$request_id]);
    if ($chk->fetch()) {
        header("Location: post_request.html?error=duplicate");
        exit;
    }

    try {
        $stmt = $db->prepare("INSERT INTO service_request (request_id, title, description, budget, deadline, category, client_id) VALUES(?,?,?,?,?,?,?)");
        $stmt->execute([$request_id, $title, $description, $budget, $deadline, $category, $clientId]);
        header("Location: dashboard.php?posted=success");
        exit;
    } catch (PDOException $e) {
        header("Location: post_request.html?error=db_error");
        exit;
    }
}

header("Location: post_request.html");
exit;
