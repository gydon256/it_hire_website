<!--Section from JoackimMbeko-->
<?php
require_once 'db_connect.php';
$pageTitle  = 'Home';
$activePage = 'home';
$depth      = '';
require_once 'includes/header.php';
?>

<!-- Hero -->
<div class="hero">
    <h1>Find & Hire<br><span>Top IT Service Providers</span></h1>
    <p>Uganda's platform connecting skilled IT service providers with clients who need them. Fast, reliable, and local.</p>
    <div class="hero-btns">
        <a href="client/register.html" class="hero-btn-white">Post a Request</a>
        <a href="provider/view_requests.php" class="hero-btn-outline">Browse Requests</a>
    </div>
</div>

<!-- Stats bar -->
<div style="background:#fff; border-bottom:1px solid var(--border); padding:1.2rem 2rem;">
    <div style="max-width:900px; margin:0 auto; display:flex; justify-content:center; gap:3rem; flex-wrap:wrap; text-align:center;">
        <?php
        try {
            $db   = getDB();
            $reqs = $db->query("SELECT COUNT(*) FROM service_request")->fetchColumn();
            $clnt = $db->query("SELECT COUNT(*) FROM service_client")->fetchColumn();
            $prov = $db->query("SELECT COUNT(*) FROM service_provider")->fetchColumn();
            $apps = $db->query("SELECT COUNT(*) FROM application")->fetchColumn();
        } catch(Exception $e) { $reqs=$clnt=$prov=$apps=0; }
        ?>
        <div>
            <div style="font-family:'Plus Jakarta Sans',sans-serif;font-size:28px;font-weight:800;color:var(--primary)"><?= $reqs ?></div>
            <div style="font-size:13px;color:var(--text-muted)">Open Requests</div>
        </div>
        <div>
            <div style="font-family:'Plus Jakarta Sans',sans-serif;font-size:28px;font-weight:800;color:var(--primary)"><?= $clnt ?></div>
            <div style="font-size:13px;color:var(--text-muted)">Clients</div>
        </div>
        <div>
            <div style="font-family:'Plus Jakarta Sans',sans-serif;font-size:28px;font-weight:800;color:var(--primary)"><?= $prov ?></div>
            <div style="font-size:13px;color:var(--text-muted)">Service Providers</div>
        </div>
        <div>
            <div style="font-family:'Plus Jakarta Sans',sans-serif;font-size:28px;font-weight:800;color:var(--primary)"><?= $apps ?></div>
            <div style="font-size:13px;color:var(--text-muted)">Applications</div>
        </div>
    </div>
</div>

<!-- Features -->
<div class="features">
    <h2>How IT Hire Works</h2>
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">🏢</div>
            <h3>Clients Post Requests</h3>
            <p>Register as a client, describe the IT service you need, set a budget and deadline — done in minutes.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">👤</div>
            <h3>Providers Apply</h3>
            <p>IT service providers create profiles, upload their qualifications, and apply to requests that match their skills.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">✅</div>
            <h3>Review &amp; Select</h3>
            <p>Clients review all applications in one place, compare providers, and select the best person for the service.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">💳</div>
            <h3>Escrow Payments</h3>
            <p>Secure escrow-based payment system with 10% commission. Admin-verified for safety and trust.</p>
        </div>
    </div>
</div>

<!-- Recent Requests -->
<div class="page" style="padding-top:0">
    <div class="page-header">
        <h1>Latest Service Requests</h1>
        <p>Freshly posted service requests from clients across Uganda</p>
    </div>
    <?php
    try {
        $recent = getDB()->query("
            SELECT sr.*, sc.full_name as client_name
            FROM service_request sr
            LEFT JOIN service_client sc ON sr.client_id = sc.client_id
            WHERE sr.status = 'open'
            ORDER BY sr.date_posted DESC
            LIMIT 6
        ")->fetchAll();
    } catch(Exception $e) { $recent = []; }
    ?>
    <?php if (empty($recent)): ?>
        <div class="alert alert-info">
            <span class="alert-icon">ℹ️</span>
            <span>No requests posted yet. <a href="client/register.html">Register as a client</a> and post the first request!</span>
        </div>
    <?php else: ?>
        <div class="job-grid">
        <?php foreach ($recent as $req): ?>
            <div class="job-card">
                <div class="job-card-title"><?= htmlspecialchars($req['title']) ?></div>
                <div class="job-card-company">🏢 <?= htmlspecialchars($req['client_name'] ?? 'Unknown Client') ?></div>
                <div class="job-card-meta">
                    <span class="job-meta-item">💰 UGX <?= number_format($req['budget']) ?></span>
                    <span class="job-meta-item">📅 Deadline: <?= $req['deadline'] ?></span>
                </div>
                <p style="font-size:13px;color:var(--text-muted);line-height:1.5"><?= htmlspecialchars(substr($req['description'], 0, 100)) ?>...</p>
                <div class="job-card-footer">
                    <span class="badge badge-success">Open</span>
                    <a href="provider/view_requests.php" class="btn btn-accent btn-sm">Apply Now</a>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
        <div style="text-align:center;margin-top:1.5rem">
            <a href="provider/view_requests.php" class="btn btn-ghost">View All Requests →</a>
        </div>
    <?php endif; ?>
</div>

<!-- Footer -->
<footer style="background:var(--primary);color:rgba(255,255,255,0.7);text-align:center;padding:1.5rem;font-size:13px;margin-top:3rem;">
    &copy; <?= date('Y') ?> IT Hire Uganda &mdash; Connecting IT Service Providers
</footer>
</body>
</html>
