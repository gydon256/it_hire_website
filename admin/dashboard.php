<?php
require_once '../db_connect.php';
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.html"); exit;
}

$db = getDB();

// Get statistics
$stats = [];

// Total clients
$stmt = $db->query("SELECT COUNT(*) FROM service_client");
$stats['total_clients'] = $stmt->fetchColumn();

// Total providers
$stmt = $db->query("SELECT COUNT(*) FROM service_provider");
$stats['total_providers'] = $stmt->fetchColumn();

// Total service requests
$stmt = $db->query("SELECT COUNT(*) FROM service_request");
$stats['total_requests'] = $stmt->fetchColumn();

// Open requests
$stmt = $db->query("SELECT COUNT(*) FROM service_request WHERE status='open'");
$stats['open_requests'] = $stmt->fetchColumn();

// Total payments
$stmt = $db->query("SELECT COUNT(*) FROM payment");
$stats['total_payments'] = $stmt->fetchColumn();

// Escrowed payments
$stmt = $db->query("SELECT COUNT(*) FROM payment WHERE payment_status='escrowed'");
$stats['escrowed_payments'] = $stmt->fetchColumn();

// Released payments
$stmt = $db->query("SELECT COUNT(*) FROM payment WHERE payment_status='released'");
$stats['released_payments'] = $stmt->fetchColumn();

// Total revenue (commission)
$stmt = $db->query("SELECT SUM(commission_amount) FROM payment WHERE payment_status='released'");
$stats['total_revenue'] = $stmt->fetchColumn() ?: 0;

// Pending verifications
$stmt = $db->query("SELECT COUNT(*) FROM service_client WHERE verification_status='unverified'");
$stats['pending_client_verifications'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM service_provider WHERE verification_status='unverified'");
$stats['pending_provider_verifications'] = $stmt->fetchColumn();

// Recent activity
$recent_requests = $db->query("SELECT request_id, title, status, date_posted FROM service_request ORDER BY date_posted DESC LIMIT 5")->fetchAll();
$recent_payments = $db->query("SELECT payment_id, amount, payment_status, payment_date FROM payment ORDER BY payment_date DESC LIMIT 5")->fetchAll();

$pageTitle = 'Admin Dashboard';
$activePage = 'dashboard';
$depth = '../';
require_once '../includes/header.php';
?>

<div class="page">
    <div class="page-header">
        <h1>Admin Dashboard</h1>
        <p>IT Hire Uganda Platform Administration</p>
    </div>

    <!-- Statistics Cards -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1.5rem;margin-bottom:2rem">
        <div class="card" style="padding:1.5rem">
            <div style="font-size:36px;margin-bottom:0.5rem">👥</div>
            <div style="font-size:32px;font-weight:700;color:#1B3F7A"><?= $stats['total_clients'] ?></div>
            <div style="color:#666;font-size:14px">Total Clients</div>
            <div style="margin-top:0.5rem;font-size:12px;color:#999"><?= $stats['pending_client_verifications'] ?> pending verification</div>
        </div>
        <div class="card" style="padding:1.5rem">
            <div style="font-size:36px;margin-bottom:0.5rem">👨‍💻</div>
            <div style="font-size:32px;font-weight:700;color:#1B3F7A"><?= $stats['total_providers'] ?></div>
            <div style="color:#666;font-size:14px">Total Providers</div>
            <div style="margin-top:0.5rem;font-size:12px;color:#999"><?= $stats['pending_provider_verifications'] ?> pending verification</div>
        </div>
        <div class="card" style="padding:1.5rem">
            <div style="font-size:36px;margin-bottom:0.5rem">📋</div>
            <div style="font-size:32px;font-weight:700;color:#1B3F7A"><?= $stats['total_requests'] ?></div>
            <div style="color:#666;font-size:14px">Service Requests</div>
            <div style="margin-top:0.5rem;font-size:12px;color:#999"><?= $stats['open_requests'] ?> open</div>
        </div>
        <div class="card" style="padding:1.5rem">
            <div style="font-size:36px;margin-bottom:0.5rem">💰</div>
            <div style="font-size:32px;font-weight:700;color:#1B3F7A">UGX <?= number_format($stats['total_revenue']) ?></div>
            <div style="color:#666;font-size:14px">Total Revenue</div>
            <div style="margin-top:0.5rem;font-size:12px;color:#999">10% commission</div>
        </div>
    </div>

    <!-- Payment Statistics -->
    <div class="card" style="margin-bottom:2rem">
        <div class="card-header">
            <h2>Payment Overview</h2>
        </div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;padding:1.5rem">
            <div style="text-align:center">
                <div style="font-size:28px;font-weight:700;color:#1B3F7A"><?= $stats['total_payments'] ?></div>
                <div style="color:#666;font-size:14px">Total Payments</div>
            </div>
            <div style="text-align:center">
                <div style="font-size:28px;font-weight:700;color:#FFA500"><?= $stats['escrowed_payments'] ?></div>
                <div style="color:#666;font-size:14px">In Escrow</div>
            </div>
            <div style="text-align:center">
                <div style="font-size:28px;font-weight:700;color:#28A745"><?= $stats['released_payments'] ?></div>
                <div style="color:#666;font-size:14px">Released</div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:2rem">
        <a href="verification.php" class="card" style="padding:1.5rem;text-decoration:none;color:inherit;display:block">
            <div style="font-size:32px;margin-bottom:0.5rem">✅</div>
            <div style="font-weight:600">Manage Verifications</div>
            <div style="color:#666;font-size:13px">Verify clients & providers</div>
        </a>
        <a href="payments.php" class="card" style="padding:1.5rem;text-decoration:none;color:inherit;display:block">
            <div style="font-size:32px;margin-bottom:0.5rem">💵</div>
            <div style="font-weight:600">Release Payments</div>
            <div style="color:#666;font-size:13px">Release escrowed payments</div>
        </a>
        <a href="requests.php" class="card" style="padding:1.5rem;text-decoration:none;color:inherit;display:block">
            <div style="font-size:32px;margin-bottom:0.5rem">📋</div>
            <div style="font-weight:600">Service Requests</div>
            <div style="color:#666;font-size:13px">View all requests</div>
        </a>
    </div>

    <!-- Recent Activity -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
        <div class="card">
            <div class="card-header">
                <h2>Recent Service Requests</h2>
            </div>
            <div style="padding:1rem">
                <?php if (empty($recent_requests)): ?>
                    <div style="color:#666;text-align:center;padding:2rem">No requests yet</div>
                <?php else: ?>
                    <?php foreach ($recent_requests as $req): ?>
                        <div style="padding:0.75rem;border-bottom:1px solid #eee">
                            <div style="font-weight:600"><?= htmlspecialchars($req['title']) ?></div>
                            <div style="font-size:12px;color:#666">
                                ID: <?= htmlspecialchars($req['request_id']) ?> • 
                                Status: <span style="color:#1B3F7A"><?= htmlspecialchars($req['status']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h2>Recent Payments</h2>
            </div>
            <div style="padding:1rem">
                <?php if (empty($recent_payments)): ?>
                    <div style="color:#666;text-align:center;padding:2rem">No payments yet</div>
                <?php else: ?>
                    <?php foreach ($recent_payments as $pay): ?>
                        <div style="padding:0.75rem;border-bottom:1px solid #eee">
                            <div style="font-weight:600">UGX <?= number_format($pay['amount']) ?></div>
                            <div style="font-size:12px;color:#666">
                                ID: <?= htmlspecialchars($pay['payment_id']) ?> • 
                                Status: <span style="color:#1B3F7A"><?= htmlspecialchars($pay['payment_status']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body></html>
