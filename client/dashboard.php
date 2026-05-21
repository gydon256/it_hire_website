<?php
require_once '../db_connect.php';
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'client') {
    header("Location: ../login.html"); exit;
}
$clientId   = $_SESSION['user_id'];
$clientName = $_SESSION['user_name'];
$db = getDB();

$totalRequests = $db->prepare("SELECT COUNT(*) FROM service_request WHERE client_id=?");
$totalRequests->execute([$clientId]);
$totalRequests = $totalRequests->fetchColumn();

$totalApps = $db->prepare("SELECT COUNT(*) FROM application a JOIN service_request sr ON a.request_id=sr.request_id WHERE sr.client_id=?");
$totalApps->execute([$clientId]);
$totalApps = $totalApps->fetchColumn();

$pending = $db->prepare("SELECT COUNT(*) FROM application a JOIN service_request sr ON a.request_id=sr.request_id WHERE sr.client_id=? AND a.status='pending'");
$pending->execute([$clientId]);
$pending = $pending->fetchColumn();

$myRequests = $db->prepare("
    SELECT sr.*, (SELECT COUNT(*) FROM application a WHERE a.request_id=sr.request_id) AS app_count
    FROM service_request sr WHERE sr.client_id=? ORDER BY sr.date_posted DESC
");
$myRequests->execute([$clientId]);
$myRequests = $myRequests->fetchAll();

// Get client verification status
$stmt = $db->prepare("SELECT verification_status, payment_verified FROM service_client WHERE client_id=?");
$stmt->execute([$clientId]);
$clientStatus = $stmt->fetch();

// Get payment history
$stmt = $db->prepare("
    SELECT p.*, sr.title as request_title
    FROM payment p
    LEFT JOIN service_request sr ON p.request_id = sr.request_id
    WHERE p.client_id = ?
    ORDER BY p.payment_date DESC
");
$stmt->execute([$clientId]);
$payments = $stmt->fetchAll();

$pageTitle = 'Service Client Dashboard';
$activePage = 'dashboard';
$depth = '../';
require_once '../includes/header.php';
?>

<div class="page">
    <div class="page-header">
        <h1>Welcome, <?= htmlspecialchars($clientName) ?> 👋</h1>
        <p>Manage your service requests and review applications from here</p>
    </div>

    <!-- Verification Status -->
    <?php if ($clientStatus['verification_status'] !== 'fully_verified' || $clientStatus['payment_verified'] == 0): ?>
    <div class="alert alert-warning">
        <span class="alert-icon">⚠️</span>
        <div>
            <?php if ($clientStatus['verification_status'] !== 'fully_verified'): ?>
                <strong>Verification Required:</strong> Complete email and phone verification to post requests.<br/>
            <?php endif; ?>
            <?php if ($clientStatus['payment_verified'] == 0): ?>
                <strong>Payment Required:</strong> Pay verification fee (UGX 50,000) to post requests. <a href="../payment/payment.php">Pay now →</a>
            <?php else: ?>
                <strong>Payment Status:</strong> <span style="color:#28A745">Verified</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">📋</div>
            <div><div class="stat-num"><?= $totalRequests ?></div><div class="stat-label">Requests Posted</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">👥</div>
            <div><div class="stat-num"><?= $totalApps ?></div><div class="stat-label">Total Applicants</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon amber">⏳</div>
            <div><div class="stat-num"><?= $pending ?></div><div class="stat-label">Pending Review</div></div>
        </div>
    </div>

    <!-- Actions -->
    <div style="display:flex;gap:10px;margin-bottom:1.5rem;flex-wrap:wrap">
        <a href="post_request.html"           class="btn btn-primary">+ Post New Request</a>
        <a href="view_applications.php"  class="btn btn-ghost">View All Applications</a>
        <a href="../payment/payment.php" class="btn btn-ghost">💳 Payments</a>
    </div>

    <!-- My Requests -->
    <div class="card">
        <div class="card-header">
            <h2>My Service Requests</h2>
        </div>
        <?php if (empty($myRequests)): ?>
            <div class="card-body">
                <div class="alert alert-info">
                    <span class="alert-icon">ℹ️</span>
                    <span>You haven't posted any requests yet. <a href="post_request.html">Post your first request →</a></span>
                </div>
            </div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Budget (UGX)</th>
                        <th>Posted</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th>Applicants</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($myRequests as $req): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($req['title']) ?></strong></td>
                        <td><?= number_format($req['budget']) ?></td>
                        <td><?= $req['date_posted'] ?></td>
                        <td>
                            <?php
                            $deadline = strtotime($req['deadline']);
                            $daysLeft = ceil(($deadline - time()) / 86400);
                            ?>
                            <?= $daysLeft > 0 ? $daysLeft.' days left' : 'Closed' ?>
                        </td>
                        <td>
                            <span class="badge badge-info"><?= htmlspecialchars(ucfirst($req['status'])) ?></span>
                        </td>
                        <td>
                            <span class="badge badge-info"><?= $req['app_count'] ?> applied</span>
                        </td>
                        <td>
                            <a href="view_applications.php?request_id=<?= urlencode($req['request_id']) ?>"
                               class="btn btn-ghost btn-sm">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Payment History -->
    <div class="card" style="margin-top:2rem">
        <div class="card-header">
            <h2>Payment History</h2>
        </div>
        <?php if (empty($payments)): ?>
            <div class="card-body">
                <div class="alert alert-info">
                    <span class="alert-icon">ℹ️</span>
                    <span>No payment history yet.</span>
                </div>
            </div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Payment ID</th>
                        <th>Type</th>
                        <th>Request</th>
                        <th>Amount (UGX)</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($payments as $pay): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($pay['payment_id']) ?></code></td>
                        <td>
                            <?php if ($pay['payment_type'] === 'verification_fee'): ?>
                                <span class="badge badge-info">Verification Fee</span>
                            <?php else: ?>
                                <span class="badge badge-info">Service Payment</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($pay['request_title'] ?: '-') ?></td>
                        <td><?= number_format($pay['amount']) ?></td>
                        <td><?= htmlspecialchars($pay['payment_method']) ?> (<?= htmlspecialchars($pay['payment_channel']) ?>)</td>
                        <td>
                            <?php
                            $statusColor = 'gray';
                            if ($pay['payment_status'] === 'pending') $statusColor = 'amber';
                            elseif ($pay['payment_status'] === 'escrowed') $statusColor = 'green';
                            elseif ($pay['payment_status'] === 'released') $statusColor = 'blue';
                            elseif ($pay['payment_status'] === 'refunded') $statusColor = 'red';
                            ?>
                            <span class="badge badge-<?= $statusColor ?>"><?= htmlspecialchars(ucfirst($pay['payment_status'])) ?></span>
                        </td>
                        <td><?= $pay['payment_date'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
</body></html>
