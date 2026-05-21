<?php
require_once '../db_connect.php';
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'client') {
    header("Location: ../login.html"); exit;
}
$clientId = $_SESSION['user_id'];
$db = getDB();

// Error logging function
function logPaymentError($message, $clientId, $context = []) {
    $logFile = __DIR__ . '/../logs/payment_errors.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
    $logEntry = "[$timestamp] $message | Client ID: $clientId" . $contextStr . "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Rate limiting: Max 5 payment submissions per minute per client
$rateLimitKey = 'payment_rate_limit_' . $clientId;
$currentTime = time();
if (!isset($_SESSION[$rateLimitKey])) {
    $_SESSION[$rateLimitKey] = [];
}
$_SESSION[$rateLimitKey] = array_filter($_SESSION[$rateLimitKey], function($timestamp) use ($currentTime) {
    return $timestamp > $currentTime - 60; // Keep only submissions from last 60 seconds
});

if (count($_SESSION[$rateLimitKey]) >= 5) {
    logPaymentError('Rate limit exceeded', $clientId, ['attempts' => count($_SESSION[$rateLimitKey])]);
    header("Location: payment.php?error=rate_limit_exceeded", true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || $csrfToken !== $_SESSION['csrf_token']) {
        logPaymentError('CSRF token validation failed', $clientId);
        header("Location: payment.php?error=csrf_invalid", true, 303);
        exit;
    }

    // Regenerate CSRF token after validation
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    $paymentType = trim($_POST['payment_type'] ?? '');
    $method      = trim($_POST['method'] ?? '');
    $channel     = trim($_POST['channel'] ?? '');
    $reference   = trim($_POST['reference'] ?? '');

    if (empty($paymentType) || empty($method) || empty($channel) || empty($reference)) {
        header("Location: payment.php?error=missing_method", true, 303);
        exit;
    }

    try {
        if ($paymentType === 'verification_fee') {
            // Check if client already has a pending or approved verification fee payment
            $stmt = $db->prepare("SELECT payment_id, payment_status FROM payment WHERE client_id = ? AND payment_type = 'verification_fee' AND payment_status IN ('pending', 'escrowed', 'released')");
            $stmt->execute([$clientId]);
            $existingPayment = $stmt->fetch();

            if ($existingPayment) {
                header("Location: payment.php?error=duplicate_payment", true, 303);
                exit;
            }

            // Insert verification fee payment record with pending status
            $txnId = 'TXN' . strtoupper(uniqid());
            $stmt = $db->prepare("INSERT INTO payment (payment_id, client_id, request_id, amount, currency, payment_method, payment_channel, transaction_reference, payment_status, payment_type, payment_date) VALUES(?,?,?,?,?,?,?,?,?,?,NOW())");
            $stmt->execute([$txnId, $clientId, null, 50000, 'UGX', $method, $channel, $reference, 'pending', $paymentType]);

            // Track submission for rate limiting
            $_SESSION[$rateLimitKey][] = time();

            header("Location: ../client/dashboard.php?payment=submitted", true, 303);
            exit;

        } elseif ($paymentType === 'service_payment') {
            $requestId = trim($_POST['request_id'] ?? '');
            $budget     = intval($_POST['budget'] ?? 0);

            if (empty($requestId) || $budget <= 0) {
                header("Location: payment.php?error=missing_request_id", true, 303);
                exit;
            }

            // Validate request exists and belongs to client
            $stmt = $db->prepare("SELECT request_id, client_id FROM service_request WHERE request_id = ?");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();

            if (!$request) {
                header("Location: payment.php?error=invalid_request", true, 303);
                exit;
            }

            if ($request['client_id'] !== $clientId) {
                header("Location: payment.php?error=unauthorized_request", true, 303);
                exit;
            }

            // Check if client already has approved verification fee payment
            $stmt = $db->prepare("SELECT payment_id FROM payment WHERE client_id = ? AND payment_type = 'verification_fee' AND payment_status IN ('escrowed', 'released')");
            $stmt->execute([$clientId]);
            $verificationPayment = $stmt->fetch();

            // Calculate total amount (only add verification fee if not already paid)
            if ($verificationPayment) {
                $totalAmount = $budget;
            } else {
                $totalAmount = $budget + 50000;
            }

            // Calculate commission (10% of budget only, not verification fee)
            $commissionAmount = round($budget * 0.10);
            $netToProvider = $budget - $commissionAmount;

            $txnId = 'TXN' . strtoupper(uniqid());

            // Insert payment record with pending status and commission details
            $stmt = $db->prepare("INSERT INTO payment (payment_id, client_id, request_id, amount, currency, payment_method, payment_channel, transaction_reference, payment_status, payment_type, payment_date, commission_amount, net_amount_to_provider) VALUES(?,?,?,?,?,?,?,?,?,?,NOW(),?,?)");
            $stmt->execute([$txnId, $clientId, $requestId, $totalAmount, 'UGX', $method, $channel, $reference, 'pending', $paymentType, $commissionAmount, $netToProvider]);

            // Track submission for rate limiting
            $_SESSION[$rateLimitKey][] = time();

            header("Location: ../client/dashboard.php?payment=escrowed", true, 303);
            exit;
        }
    } catch (PDOException $e) {
        logPaymentError('Database error: ' . $e->getMessage(), $clientId, ['payment_type' => $paymentType]);
        header("Location: payment.php?error=db_error", true, 303);
        exit;
    }
}

header("Location: payment.php", true, 303);
exit;
