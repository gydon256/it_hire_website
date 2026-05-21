<?php
require_once '../db_connect.php';
session_start();
$db = getDB();
$isProvider = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'provider';
$providerNin = $_SESSION['user_id'] ?? null;

// Handle Apply button
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isProvider) {
    $requestId = $_POST['request_id'] ?? '';
    $appId = 'APP' . strtoupper(uniqid());

    // Check not already applied
    $chk = $db->prepare("SELECT application_id FROM application WHERE request_id=? AND provider_nin=?");
    $chk->execute([$requestId, $providerNin]);
    if ($chk->fetch()) {
        $applyMsg = ['type'=>'warning', 'text'=>'You have already applied for this request.'];
    } else {
        $stmt = $db->prepare("INSERT INTO application (application_id,application_date,status,request_id,provider_nin) VALUES(?,NOW(),'pending',?,?)");
        $stmt->execute([$appId, $requestId, $providerNin]);
        $applyMsg = ['type'=>'success', 'text'=>'Application submitted successfully!'];
    }
}

// Search & filter
$search = trim($_GET['search'] ?? '');
$query = "SELECT sr.*, sc.full_name as client_name, sc.physical_address FROM service_request sr LEFT JOIN service_client sc ON sr.client_id=sc.client_id WHERE sr.status='open'";
$params = [];
if ($search) {
    $query .= " AND (sr.title LIKE ? OR sr.description LIKE ? OR sc.full_name LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}
$query .= " ORDER BY sr.date_posted DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Get applied request IDs for this provider
$appliedRequests = [];
if ($isProvider) {
    $aStmt = $db->prepare("SELECT request_id FROM application WHERE provider_nin=?");
    $aStmt->execute([$providerNin]);
    $appliedRequests = array_column($aStmt->fetchAll(), 'request_id');
}

$pageTitle = 'Browse Service Requests';
$activePage = 'requests';
$depth = '../';
require_once '../includes/header.php';
?>

<div class="page">
    <div class="page-header">
        <h1>Browse Service Requests</h1>
        <p>Find your next IT service opportunity — <?= count($requests) ?> requests available</p>
    </div>

    <?php if (isset($applyMsg)): ?>
        <div class="alert alert-<?= $applyMsg['type']==='success'?'success':'info' ?>">
            <span class="alert-icon"><?= $applyMsg['type']==='success'?'✅':'ℹ️' ?></span>
            <span><?= htmlspecialchars($applyMsg['text']) ?></span>
        </div>
    <?php endif; ?>

    <!-- Search bar -->
    <form method="GET" style="margin-bottom:1.5rem;display:flex;gap:10px">
        <input type="text" name="search" class="form-control"
               value="<?= htmlspecialchars($search) ?>"
               placeholder="Search by request title, skills, or client..."
               style="max-width:420px"/>
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if($search): ?>
            <a href="view_requests.php" class="btn btn-ghost">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (empty($requests)): ?>
        <div class="alert alert-info">
            <span class="alert-icon">ℹ️</span>
            <span>No requests found<?= $search ? " matching \"$search\"" : '' ?>.</span>
        </div>
    <?php else: ?>
    <div class="job-grid">
        <?php foreach($requests as $req):
            $isApplied = in_array($req['request_id'], $appliedRequests);
            $deadline = strtotime($req['deadline']);
            $daysLeft = ceil(($deadline - time()) / 86400);
            $isOpen = $daysLeft > 0;
        ?>
        <div class="job-card">
            <div class="job-card-title"><?= htmlspecialchars($req['title']) ?></div>
            <div class="job-card-company">🏢 <?= htmlspecialchars($req['client_name'] ?? 'Unknown') ?></div>
            <?php if ($req['physical_address']): ?>
                <div class="job-card-company" style="margin-top:-6px">📍 <?= htmlspecialchars($req['physical_address']) ?></div>
            <?php endif; ?>
            <div class="job-card-meta">
                <span class="job-meta-item">💰 UGX <?= number_format($req['budget']) ?></span>
                <span class="job-meta-item">📅
                    <?php if (!$isOpen): ?>
                        <span style="color:var(--danger)">Closed</span>
                    <?php elseif ($daysLeft <= 7): ?>
                        <span style="color:var(--warning)"><?= $daysLeft ?> days left</span>
                    <?php else: ?>
                        <?= $daysLeft ?> days left
                    <?php endif; ?>
                </span>
            </div>
            <p style="font-size:13px;color:var(--text-muted);line-height:1.5;margin-bottom:10px">
                <?= htmlspecialchars(substr($req['description'], 0, 120)) ?>...
            </p>
            <div class="job-card-footer">
                <?php if ($isApplied): ?>
                    <span class="badge badge-success">✓ Applied</span>
                    <span style="font-size:12px;color:var(--text-muted)">Application submitted</span>
                <?php elseif (!$isOpen): ?>
                    <span class="badge badge-danger">Closed</span>
                    <span style="font-size:12px;color:var(--text-muted)">Deadline passed</span>
                <?php elseif (!$isProvider): ?>
                    <a href="../login.html" class="btn btn-accent btn-sm">Login to Apply</a>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="request_id" value="<?= htmlspecialchars($req['request_id']) ?>"/>
                        <button type="submit" class="btn btn-accent btn-sm">Apply Now</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
</body></html>
