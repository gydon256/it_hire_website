<?php
require_once '../db_connect.php';
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.html"); exit;
}

$db = getDB();

// Handle verification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entity_type = $_POST['entity_type'] ?? '';
    $entity_id = $_POST['entity_id'] ?? '';
    $method = $_POST['method'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $status = $_POST['status'] ?? 'verified';
    $notes = $_POST['notes'] ?? '';
    
    if ($entity_type === 'client') {
        $new_status = $status === 'verified' ? ($method === 'email' ? 'email_verified' : 'phone_verified') : 'unverified';
        $stmt = $db->prepare("UPDATE service_client SET verification_status=? WHERE client_id=?");
        $stmt->execute([$new_status, $entity_id]);
    } elseif ($entity_type === 'provider') {
        $new_status = $status === 'verified' ? ($method === 'email' ? 'email_verified' : 'phone_verified') : 'unverified';
        $stmt = $db->prepare("UPDATE service_provider SET verification_status=? WHERE provider_nin=?");
        $stmt->execute([$new_status, $entity_id]);
    }
    
    // Log verification
    $stmt = $db->prepare("INSERT INTO verification_log (entity_type, entity_id, method, contact_attempted, status, notes) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$entity_type, $entity_id, $method, $contact, $status, $notes]);
    
    header("Location: verification.php?success=1");
    exit;
}

// Get pending verifications
$pending_clients = $db->query("SELECT client_id, full_name, email, phone_number, verification_status FROM service_client WHERE verification_status='unverified' OR verification_status='email_verified' OR verification_status='phone_verified'")->fetchAll();
$pending_providers = $db->query("SELECT provider_nin, full_name, email, phone_number, verification_status FROM service_provider WHERE verification_status='unverified' OR verification_status='email_verified' OR verification_status='phone_verified'")->fetchAll();

// Get verification log
$verification_log = $db->query("SELECT * FROM verification_log ORDER BY verification_date DESC LIMIT 20")->fetchAll();

$pageTitle = 'Verification Management';
$activePage = 'verification';
$depth = '../';
require_once '../includes/header.php';
?>

<div class="page">
    <div class="page-header">
        <h1>Verification Management</h1>
        <p>Verify service clients and providers</p>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <span class="alert-icon">✅</span>
            <span>Verification status updated successfully.</span>
        </div>
    <?php endif; ?>

    <!-- Pending Client Verifications -->
    <div class="card" style="margin-bottom:2rem">
        <div class="card-header">
            <h2>Pending Client Verifications</h2>
        </div>
        <?php if (empty($pending_clients)): ?>
            <div style="padding:2rem;text-align:center;color:#666">No pending client verifications</div>
        <?php else: ?>
            <table style="width:100%;border-collapse:collapse">
                <thead>
                    <tr style="background:#f5f5f5">
                        <th style="padding:0.75rem;text-align:left">Client ID</th>
                        <th style="padding:0.75rem;text-align:left">Name</th>
                        <th style="padding:0.75rem;text-align:left">Email</th>
                        <th style="padding:0.75rem;text-align:left">Phone</th>
                        <th style="padding:0.75rem;text-align:left">Status</th>
                        <th style="padding:0.75rem;text-align:center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_clients as $client): ?>
                        <tr style="border-bottom:1px solid #eee">
                            <td style="padding:0.75rem"><?= htmlspecialchars($client['client_id']) ?></td>
                            <td style="padding:0.75rem"><?= htmlspecialchars($client['full_name']) ?></td>
                            <td style="padding:0.75rem"><?= htmlspecialchars($client['email']) ?></td>
                            <td style="padding:0.75rem"><?= htmlspecialchars($client['phone_number']) ?></td>
                            <td style="padding:0.75rem">
                                <span style="padding:4px 8px;border-radius:4px;font-size:12px;background:#FFEFD0;color:#B8860B">
                                    <?= htmlspecialchars($client['verification_status']) ?>
                                </span>
                            </td>
                            <td style="padding:0.75rem;text-align:center">
                                <button onclick="openVerifyModal('client', '<?= htmlspecialchars($client['client_id']) ?>', '<?= htmlspecialchars($client['email']) ?>', '<?= htmlspecialchars($client['phone_number']) ?>')" class="btn btn-sm btn-primary">Verify</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Pending Provider Verifications -->
    <div class="card" style="margin-bottom:2rem">
        <div class="card-header">
            <h2>Pending Provider Verifications</h2>
        </div>
        <?php if (empty($pending_providers)): ?>
            <div style="padding:2rem;text-align:center;color:#666">No pending provider verifications</div>
        <?php else: ?>
            <table style="width:100%;border-collapse:collapse">
                <thead>
                    <tr style="background:#f5f5f5">
                        <th style="padding:0.75rem;text-align:left">Provider NIN</th>
                        <th style="padding:0.75rem;text-align:left">Name</th>
                        <th style="padding:0.75rem;text-align:left">Email</th>
                        <th style="padding:0.75rem;text-align:left">Phone</th>
                        <th style="padding:0.75rem;text-align:left">Status</th>
                        <th style="padding:0.75rem;text-align:center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_providers as $provider): ?>
                        <tr style="border-bottom:1px solid #eee">
                            <td style="padding:0.75rem"><?= htmlspecialchars($provider['provider_nin']) ?></td>
                            <td style="padding:0.75rem"><?= htmlspecialchars($provider['full_name']) ?></td>
                            <td style="padding:0.75rem"><?= htmlspecialchars($provider['email']) ?></td>
                            <td style="padding:0.75rem"><?= htmlspecialchars($provider['phone_number']) ?></td>
                            <td style="padding:0.75rem">
                                <span style="padding:4px 8px;border-radius:4px;font-size:12px;background:#FFEFD0;color:#B8860B">
                                    <?= htmlspecialchars($provider['verification_status']) ?>
                                </span>
                            </td>
                            <td style="padding:0.75rem;text-align:center">
                                <button onclick="openVerifyModal('provider', '<?= htmlspecialchars($provider['provider_nin']) ?>', '<?= htmlspecialchars($provider['email']) ?>', '<?= htmlspecialchars($provider['phone_number']) ?>')" class="btn btn-sm btn-primary">Verify</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Verification Log -->
    <div class="card">
        <div class="card-header">
            <h2>Verification Log</h2>
        </div>
        <?php if (empty($verification_log)): ?>
            <div style="padding:2rem;text-align:center;color:#666">No verification history</div>
        <?php else: ?>
            <table style="width:100%;border-collapse:collapse">
                <thead>
                    <tr style="background:#f5f5f5">
                        <th style="padding:0.75rem;text-align:left">Date</th>
                        <th style="padding:0.75rem;text-align:left">Type</th>
                        <th style="padding:0.75rem;text-align:left">Entity</th>
                        <th style="padding:0.75rem;text-align:left">Method</th>
                        <th style="padding:0.75rem;text-align:left">Status</th>
                        <th style="padding:0.75rem;text-align:left">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($verification_log as $log): ?>
                        <tr style="border-bottom:1px solid #eee">
                            <td style="padding:0.75rem"><?= htmlspecialchars($log['verification_date']) ?></td>
                            <td style="padding:0.75rem"><?= htmlspecialchars($log['entity_type']) ?></td>
                            <td style="padding:0.75rem"><?= htmlspecialchars($log['entity_id']) ?></td>
                            <td style="padding:0.75rem"><?= htmlspecialchars($log['method']) ?></td>
                            <td style="padding:0.75rem">
                                <span style="color:<?= $log['status'] === 'verified' ? '#28A745' : '#DC3545' ?>">
                                    <?= htmlspecialchars($log['status']) ?>
                                </span>
                            </td>
                            <td style="padding:0.75rem"><?= htmlspecialchars($log['notes'] ?: '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Verification Modal -->
<div id="verifyModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
    <div style="background:white;padding:2rem;border-radius:12px;max-width:500px;width:90%">
        <h3 style="margin-top:0">Verify Entity</h3>
        <form method="POST">
            <input type="hidden" name="entity_type" id="modalEntityType"/>
            <input type="hidden" name="entity_id" id="modalEntityId"/>
            
            <div style="margin-bottom:1rem">
                <label style="display:block;margin-bottom:0.5rem">Verification Method</label>
                <select name="method" id="modalMethod" class="form-control" style="width:100%;padding:0.5rem;border:1px solid #ddd;border-radius:4px">
                    <option value="email">Email Verification</option>
                    <option value="phone">Phone Verification</option>
                </select>
            </div>
            
            <div style="margin-bottom:1rem">
                <label style="display:block;margin-bottom:0.5rem">Contact Used</label>
                <input type="text" name="contact" id="modalContact" class="form-control" style="width:100%;padding:0.5rem;border:1px solid #ddd;border-radius:4px" readonly/>
            </div>
            
            <div style="margin-bottom:1rem">
                <label style="display:block;margin-bottom:0.5rem">Status</label>
                <select name="status" class="form-control" style="width:100%;padding:0.5rem;border:1px solid #ddd;border-radius:4px">
                    <option value="verified">Verified</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
            
            <div style="margin-bottom:1rem">
                <label style="display:block;margin-bottom:0.5rem">Notes</label>
                <textarea name="notes" class="form-control" style="width:100%;padding:0.5rem;border:1px solid #ddd;border-radius:4px;min-height:80px" placeholder="Add verification notes..."></textarea>
            </div>
            
            <div style="display:flex;gap:1rem">
                <button type="submit" class="btn btn-primary">Confirm Verification</button>
                <button type="button" onclick="closeVerifyModal()" class="btn btn-ghost">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openVerifyModal(type, id, email, phone) {
    document.getElementById('modalEntityType').value = type;
    document.getElementById('modalEntityId').value = id;
    document.getElementById('modalContact').value = email;
    document.getElementById('verifyModal').style.display = 'flex';
    
    // Update contact based on method
    document.getElementById('modalMethod').onchange = function() {
        document.getElementById('modalContact').value = this.value === 'email' ? email : phone;
    };
}

function closeVerifyModal() {
    document.getElementById('verifyModal').style.display = 'none';
}
</script>
</body></html>
