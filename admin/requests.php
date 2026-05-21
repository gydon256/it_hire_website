<?php
require_once '../db_connect.php';
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.html"); exit;
}

$db = getDB();

// Get filter
$status_filter = $_GET['status'] ?? '';

// Build query
$query = "SELECT sr.*, sc.full_name as client_name, sp.full_name as provider_name 
          FROM service_request sr 
          LEFT JOIN service_client sc ON sr.client_id = sc.client_id 
          LEFT JOIN service_provider sp ON sr.assigned_provider_nin = sp.provider_nin";
$params = [];

if ($status_filter) {
    $query .= " WHERE sr.status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY sr.date_posted DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Get status counts
$status_counts = [];
$statuses = ['open', 'assigned', 'in_progress', 'pending_verification', 'completed', 'cancelled'];
foreach ($statuses as $status) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM service_request WHERE status = ?");
    $stmt->execute([$status]);
    $status_counts[$status] = $stmt->fetchColumn();
}

$pageTitle = 'Service Requests Management';
$activePage = 'requests';
$depth = '../';
require_once '../includes/header.php';
?>

<div class="page">
    <div class="page-header">
        <h1>Service Requests</h1>
        <p>View and manage all service requests</p>
    </div>

    <!-- Status Filter -->
    <div style="margin-bottom:1.5rem;display:flex;gap:0.5rem;flex-wrap:wrap">
        <a href="requests.php" class="btn <?= $status_filter === '' ? 'btn-primary' : 'btn-ghost' ?>">All (<?= array_sum($status_counts) ?>)</a>
        <?php foreach ($statuses as $status): ?>
            <a href="requests.php?status=<?= $status ?>" class="btn <?= $status_filter === $status ? 'btn-primary' : 'btn-ghost' ?>">
                <?= ucfirst($status) ?> (<?= $status_counts[$status] ?>)
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Requests Table -->
    <div class="card">
        <?php if (empty($requests)): ?>
            <div style="padding:2rem;text-align:center;color:#666">No service requests found</div>
        <?php else: ?>
            <table style="width:100%;border-collapse:collapse">
                <thead>
                    <tr style="background:#f5f5f5">
                        <th style="padding:0.75rem;text-align:left">Request ID</th>
                        <th style="padding:0.75rem;text-align:left">Title</th>
                        <th style="padding:0.75rem;text-align:left">Client</th>
                        <th style="padding:0.75rem;text-align:left">Provider</th>
                        <th style="padding:0.75rem;text-align:right">Budget</th>
                        <th style="padding:0.75rem;text-align:left">Deadline</th>
                        <th style="padding:0.75rem;text-align:left">Status</th>
                        <th style="padding:0.75rem;text-align:left">Posted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $req): ?>
                        <tr style="border-bottom:1px solid #eee">
                            <td style="padding:0.75rem"><?= htmlspecialchars($req['request_id']) ?></td>
                            <td style="padding:0.75rem"><?= htmlspecialchars($req['title']) ?></td>
                            <td style="padding:0.75rem"><?= htmlspecialchars($req['client_name']) ?></td>
                            <td style="padding:0.75rem"><?= htmlspecialchars($req['provider_name'] ?: '-') ?></td>
                            <td style="padding:0.75rem;text-align:right">UGX <?= number_format($req['budget']) ?></td>
                            <td style="padding:0.75rem"><?= htmlspecialchars($req['deadline']) ?></td>
                            <td style="padding:0.75rem">
                                <?php
                                $status_colors = [
                                    'open' => '#28A745',
                                    'assigned' => '#1B3F7A',
                                    'in_progress' => '#FFA500',
                                    'pending_verification' => '#B8860B',
                                    'completed' => '#008000',
                                    'cancelled' => '#DC3545'
                                ];
                                ?>
                                <span style="padding:4px 8px;border-radius:4px;font-size:12px;background:<?= $status_colors[$req['status']] ?>20;color:<?= $status_colors[$req['status']] ?>">
                                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $req['status']))) ?>
                                </span>
                            </td>
                            <td style="padding:0.75rem"><?= htmlspecialchars($req['date_posted']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
</body></html>
