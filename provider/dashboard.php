<?php
require_once '../db_connect.php';
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'provider') {
    header("Location: ../login.html"); exit;
}
$providerNin = $_SESSION['user_id'];
$db  = getDB();

$totalApps = $db->prepare("SELECT COUNT(*) FROM application WHERE provider_nin=?");
$totalApps->execute([$providerNin]); $totalApps = $totalApps->fetchColumn();

$accepted  = $db->prepare("SELECT COUNT(*) FROM application WHERE provider_nin=? AND status='accepted'");
$accepted->execute([$providerNin]); $accepted = $accepted->fetchColumn();

$quals     = $db->prepare("SELECT COUNT(*) FROM qualification WHERE provider_nin=?");
$quals->execute([$providerNin]); $quals = $quals->fetchColumn();

$myApps    = $db->prepare("
    SELECT a.*, sr.title, sr.budget, sr.deadline, sc.full_name as client_name
    FROM application a
    JOIN service_request sr ON a.request_id = sr.request_id
    LEFT JOIN service_client sc ON sr.client_id = sc.client_id
    WHERE a.provider_nin = ?
    ORDER BY a.application_date DESC
");
$myApps->execute([$providerNin]);
$myApps = $myApps->fetchAll();

// Get provider verification status
$stmt = $db->prepare("SELECT verification_status FROM service_provider WHERE provider_nin=?");
$stmt->execute([$providerNin]);
$providerStatus = $stmt->fetch();

$pageTitle = 'Service Provider Dashboard';
$activePage = 'dashboard';
$depth = '../';
require_once '../includes/header.php';
?>

<div class="page">
    <div class="page-header">
        <h1>My Dashboard 👋</h1>
        <p>Track your service applications and manage your profile</p>
    </div>

    <!-- Verification Status -->
    <?php if ($providerStatus['verification_status'] !== 'fully_verified'): ?>
    <div class="alert alert-warning">
        <span class="alert-icon">⚠️</span>
        <div><strong>Verification Required:</strong> Complete email and phone verification to apply for service requests.</div>
    </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">📄</div>
            <div><div class="stat-num"><?= $totalApps ?></div><div class="stat-label">Applications Sent</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">✅</div>
            <div><div class="stat-num"><?= $accepted ?></div><div class="stat-label">Accepted</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon amber">🎓</div>
            <div><div class="stat-num"><?= $quals ?></div><div class="stat-label">Qualifications</div></div>
        </div>
    </div>

    <div style="display:flex;gap:10px;margin-bottom:1.5rem;flex-wrap:wrap">
        <a href="view_requests.php"   class="btn btn-primary">Browse Requests</a>
        <a href="add_qualification.html" class="btn btn-ghost">+ Add Qualification</a>
    </div>

    <!-- Qualifications warning -->
    <?php if ($quals == 0): ?>
    <div class="alert alert-info" style="margin-bottom:1.5rem">
        <span class="alert-icon">💡</span>
        <span>Your profile has no qualifications yet. <a href="add_qualification.html">Add your academic background</a> to strengthen your applications.</span>
    </div>
    <?php endif; ?>

    <!-- My Applications -->
    <div class="card">
        <div class="card-header">
            <h2>My Applications</h2>
        </div>
        <?php if (empty($myApps)): ?>
            <div class="card-body">
                <div class="alert alert-info">
                    <span class="alert-icon">ℹ️</span>
                    <span>You haven't applied for any requests yet. <a href="view_requests.php">Browse available requests →</a></span>
                </div>
            </div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Request Title</th>
                        <th>Client</th>
                        <th>Budget (UGX)</th>
                        <th>Applied On</th>
                        <th>Deadline</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($myApps as $app):
                        $statusMap = ['pending'=>'badge-warning','accepted'=>'badge-success','rejected'=>'badge-danger'];
                        $badgeClass = $statusMap[$app['status']] ?? 'badge-gray';
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($app['title']) ?></strong></td>
                        <td><?= htmlspecialchars($app['client_name'] ?? 'N/A') ?></td>
                        <td>UGX <?= number_format($app['budget']) ?></td>
                        <td><?= $app['application_date'] ?></td>
                        <td><?= $app['deadline'] ?></td>
                        <td><span class="badge <?= $badgeClass ?>"><?= ucfirst($app['status'] ?? 'pending') ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
</body></html>
