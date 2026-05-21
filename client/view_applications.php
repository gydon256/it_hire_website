<?php
require_once '../db_connect.php';
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'client') {
    header("Location: ../login.html"); exit;
}
$clientId = $_SESSION['user_id'];
$db = getDB();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $stmt = $db->prepare("UPDATE application SET status=? WHERE application_id=?");
    $stmt->execute([$_POST['status'], $_POST['app_id']]);
    header("Location: view_applications.php");
    exit;
}

// Filter by request
$filterRequest = $_GET['request_id'] ?? '';

$requestsStmt = $db->prepare("SELECT request_id, title FROM service_request WHERE client_id=? ORDER BY date_posted DESC");
$requestsStmt->execute([$clientId]);
$requests = $requestsStmt->fetchAll();

$query = "
    SELECT a.*, sp.email, sp.phone_number, sp.experience_years, sr.title
    FROM application a
    JOIN service_request sr ON a.request_id = sr.request_id
    JOIN service_provider sp ON a.provider_nin = sp.provider_nin
    WHERE sr.client_id = ?
";
$params = [$clientId];
if ($filterRequest) { $query .= " AND a.request_id = ?"; $params[] = $filterRequest; }
$query .= " ORDER BY a.application_date DESC";

$appStmt = $db->prepare($query);
$appStmt->execute($params);
$applications = $appStmt->fetchAll();

$pageTitle = 'Applications';
$activePage = 'applications';
$depth = '../';
require_once '../includes/header.php';
?>

<div class="page">
    <div class="page-header">
        <h1>Service Applications</h1>
        <p>Review providers who have applied to your service requests</p>
    </div>

    <!-- Filter -->
    <div class="card" style="margin-bottom:1rem">
        <div class="card-body" style="padding:1rem 1.5rem">
            <form method="GET" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                <label style="font-size:13px;font-weight:600;color:var(--text)">Filter by request:</label>
                <select name="request_id" class="form-control" style="width:auto;min-width:220px">
                    <option value="">All Requests</option>
                    <?php foreach($requests as $r): ?>
                        <option value="<?= htmlspecialchars($r['request_id']) ?>"
                            <?= $filterRequest===$r['request_id']?'selected':'' ?>>
                            <?= htmlspecialchars($r['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-ghost btn-sm">Filter</button>
                <?php if($filterRequest): ?>
                    <a href="view_applications.php" class="btn btn-ghost btn-sm">Clear</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <?php if (empty($applications)): ?>
        <div class="alert alert-info">
            <span class="alert-icon">ℹ️</span>
            <span>No applications found<?= $filterRequest ? ' for this request' : '' ?>.</span>
        </div>
    <?php else: ?>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Provider (NIN)</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Experience</th>
                        <th>Request</th>
                        <th>Applied</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($applications as $app): ?>
                    <tr>
                        <td><strong style="font-family:monospace;font-size:12px"><?= htmlspecialchars($app['provider_nin']) ?></strong></td>
                        <td><?= htmlspecialchars($app['email']) ?></td>
                        <td><?= htmlspecialchars($app['phone_number']) ?></td>
                        <td><?= $app['experience_years'] ?> yr<?= $app['experience_years']!=1?'s':'' ?></td>
                        <td><span style="font-size:12px"><?= htmlspecialchars($app['title']) ?></span></td>
                        <td style="font-size:12px"><?= $app['application_date'] ?></td>
                        <td>
                            <?php
                            $statusMap = ['pending'=>'badge-warning','accepted'=>'badge-success','rejected'=>'badge-danger'];
                            $badgeClass = $statusMap[$app['status']] ?? 'badge-gray';
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= ucfirst($app['status'] ?? 'pending') ?></span>
                        </td>
                        <td>
                            <form method="POST" style="display:flex;gap:4px">
                                <input type="hidden" name="app_id" value="<?= htmlspecialchars($app['application_id']) ?>"/>
                                <select name="status" class="form-control" style="width:auto;font-size:12px;padding:5px 8px">
                                    <option value="pending"  <?= $app['status']==='pending'  ?'selected':'' ?>>Pending</option>
                                    <option value="accepted" <?= $app['status']==='accepted' ?'selected':'' ?>>Accept</option>
                                    <option value="rejected" <?= $app['status']==='rejected' ?'selected':'' ?>>Reject</option>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-accent btn-sm">Save</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
</body></html>
