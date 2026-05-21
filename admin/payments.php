<?php
require_once '../db_connect.php';
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.html"); exit;
}

$db = getDB();

// Handle payment verification (approve/reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_payment'])) {
    $payment_id = $_POST['payment_id'] ?? '';
    $action = $_POST['action'] ?? '';
    $verification_notes = $_POST['verification_notes'] ?? '';

    // Get payment details
    $stmt = $db->prepare("SELECT * FROM payment WHERE payment_id=?");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch();

    if ($action === 'approve') {
        // Update payment status to escrowed
        $stmt = $db->prepare("UPDATE payment SET payment_status='escrowed' WHERE payment_id=?");
        $stmt->execute([$payment_id]);

        // If verification fee, also update client's payment_verified flag and verification_status
        if ($payment && $payment['payment_type'] === 'verification_fee') {
            $stmt = $db->prepare("UPDATE service_client SET payment_verified = 1, verification_status = 'fully_verified' WHERE client_id = ?");
            $stmt->execute([$payment['client_id']]);
        }

        header("Location: payments.php?verified=1");
        exit;
    } elseif ($action === 'reject') {
        // Update payment status to refunded
        $stmt = $db->prepare("UPDATE payment SET payment_status='refunded' WHERE payment_id=?");
        $stmt->execute([$payment_id]);
        header("Location: payments.php?rejected=1");
        exit;
    }
}

// Handle payment release
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['release_payment'])) {
    $payment_id = $_POST['payment_id'] ?? '';
    $release_notes = $_POST['release_notes'] ?? '';
    
    // Get payment details
    $stmt = $db->prepare("SELECT * FROM payment WHERE payment_id=?");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch();
    
    if ($payment && $payment['payment_status'] === 'escrowed') {
        // Update payment status
        $stmt = $db->prepare("UPDATE payment SET payment_status='released' WHERE payment_id=?");
        $stmt->execute([$payment_id]);

        // If verification fee, also update client's verification status
        if ($payment && $payment['payment_type'] === 'verification_fee') {
            $stmt = $db->prepare("UPDATE service_client SET payment_verified = 1, verification_status = 'fully_verified' WHERE client_id = ?");
            $stmt->execute([$payment['client_id']]);
        }

        header("Location: payments.php?released=1");
        exit;
    }
}

// Get pending verification payments
$pending_payments = $db->query("
    SELECT p.*, sr.title, sc.full_name as client_name, sc.email as client_email
    FROM payment p
    LEFT JOIN service_request sr ON p.request_id = sr.request_id
    LEFT JOIN service_client sc ON p.client_id = sc.client_id
    WHERE p.payment_status = 'pending'
    ORDER BY p.payment_date DESC
")->fetchAll();

// Get escrowed payments
$escrowed_payments = $db->query("
    SELECT p.*, sr.title, sp.full_name as provider_name, sc.full_name as client_name
    FROM payment p
    LEFT JOIN service_request sr ON p.request_id = sr.request_id
    LEFT JOIN service_provider sp ON sr.assigned_provider_nin = sp.provider_nin
    LEFT JOIN service_client sc ON p.client_id = sc.client_id
    WHERE p.payment_status = 'escrowed'
    ORDER BY p.payment_date DESC
")->fetchAll();

// Get released payments
$released_payments = $db->query("
    SELECT p.*, sr.title, sp.full_name as provider_name, sc.full_name as client_name
    FROM payment p
    LEFT JOIN service_request sr ON p.request_id = sr.request_id
    LEFT JOIN service_provider sp ON sr.assigned_provider_nin = sp.provider_nin
    LEFT JOIN service_client sc ON p.client_id = sc.client_id
    WHERE p.payment_status = 'released'
    ORDER BY p.payment_date DESC
    LIMIT 10
")->fetchAll();

// Get admin company info for commission rate
$admin = $db->query("SELECT * FROM admin_company")->fetch();
$commission_rate = $admin['commission_rate'];

$pageTitle = 'Payment Management';
$activePage = 'payments';
$depth = '../';
require_once '../includes/header.php';
?>

<div class="page">
    <div class="page-header">
        <h1>Payment Management</h1>
        <p>Verify and release payments</p>
    </div>

    <?php if (isset($_GET['released'])): ?>
        <div class="alert alert-success">
            <span class="alert-icon">✅</span>
            <span>Payment released successfully.</span>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['verified'])): ?>
        <div class="alert alert-success">
            <span class="alert-icon">✅</span>
            <span>Payment verified successfully.</span>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['rejected'])): ?>
        <div class="alert alert-warning">
            <span class="alert-icon">⚠️</span>
            <span>Payment rejected.</span>
        </div>
    <?php endif; ?>

    <!-- Commission Info -->
    <div class="card" style="margin-bottom:2rem;background:#f0f7ff;border-left:4px solid #1B3F7A">
        <div style="padding:1rem">
            <strong>Commission Rate:</strong> <?= $commission_rate ?>%
            (Admin retains <?= $commission_rate ?>%, Provider receives <?= 100 - $commission_rate ?>%)
        </div>
    </div>

    <!-- Pending Verification Payments -->
    <div class="card" style="margin-bottom:2rem">
        <div class="card-header">
            <h2>Pending Verification</h2>
            <p style="font-size:13px;color:#666;margin:0">Check bank/Mobile Money account to verify payments before approving</p>
        </div>
        <?php if (empty($pending_payments)): ?>
            <div style="padding:2rem;text-align:center;color:#666">No payments pending verification</div>
        <?php else: ?>
            <table style="width:100%;border-collapse:collapse">
                <thead>
                    <tr style="background:#f5f5f5">
                        <th style="padding:0.75rem;text-align:left">Payment ID</th>
                        <th style="padding:0.75rem;text-align:left">Type</th>
                        <th style="padding:0.75rem;text-align:left">Client</th>
                        <th style="padding:0.75rem;text-align:left">Contact</th>
                        <th style="padding:0.75rem;text-align:right">Amount</th>
                        <th style="padding:0.75rem;text-align:left">Method</th>
                        <th style="padding:0.75rem;text-align:left">Transaction Ref</th>
                        <th style="padding:0.75rem;text-align:left">Date</th>
                        <th style="padding:0.75rem;text-align:center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_payments as $payment): ?>
                        <tr style="border-bottom:1px solid #eee">
                            <td style="padding:0.75rem"><code><?= htmlspecialchars($payment['payment_id']) ?></code></td>
                            <td style="padding:0.75rem">
                                <?php if ($payment['payment_type'] === 'verification_fee'): ?>
                                    <span class="badge badge-info">Verification Fee</span>
                                <?php else: ?>
                                    <span class="badge badge-info">Service Payment</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:0.75rem"><?= htmlspecialchars($payment['client_name']) ?></td>
                            <td style="padding:0.75rem">
                                <small><?= htmlspecialchars($payment['client_email']) ?></small>
                            </td>
                            <td style="padding:0.75rem;text-align:right;font-weight:600">UGX <?= number_format($payment['amount']) ?></td>
                            <td style="padding:0.75rem"><?= htmlspecialchars($payment['payment_method']) ?> (<?= htmlspecialchars($payment['payment_channel']) ?>)</td>
                            <td style="padding:0.75rem"><code><?= htmlspecialchars($payment['transaction_reference']) ?></code></td>
                            <td style="padding:0.75rem"><?= $payment['payment_date'] ?></td>
                            <td style="padding:0.75rem;text-align:center">
                                <button onclick="openVerifyModal('<?= htmlspecialchars($payment['payment_id']) ?>', '<?= htmlspecialchars($payment['payment_id']) ?>', <?= $payment['amount'] ?>)" class="btn btn-sm btn-primary">Verify</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Escrowed Payments -->
    <div class="card" style="margin-bottom:2rem">
        <div class="card-header">
            <h2>Escrowed Payments (Pending Release)</h2>
        </div>
        <?php if (empty($escrowed_payments)): ?>
            <div style="padding:2rem;text-align:center;color:#666">No escrowed payments pending release</div>
        <?php else: ?>
            <table style="width:100%;border-collapse:collapse">
                <thead>
                    <tr style="background:#f5f5f5">
                        <th style="padding:0.75rem;text-align:left">Payment ID</th>
                        <th style="padding:0.75rem;text-align:left">Service Request</th>
                        <th style="padding:0.75rem;text-align:left">Client</th>
                        <th style="padding:0.75rem;text-align:left">Provider</th>
                        <th style="padding:0.75rem;text-align:right">Gross Amount</th>
                        <th style="padding:0.75rem;text-align:right">Commission</th>
                        <th style="padding:0.75rem;text-align:right">Net to Provider</th>
                        <th style="padding:0.75rem;text-align:center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($escrowed_payments as $payment): ?>
                        <?php
                        $commission = round($payment['amount'] * ($commission_rate / 100));
                        $net_amount = $payment['amount'] - $commission;
                        ?>
                        <tr style="border-bottom:1px solid #eee">
                            <td style="padding:0.75rem"><?= htmlspecialchars($payment['payment_id']) ?></td>
                            <td style="padding:0.75rem"><?= htmlspecialchars($payment['title'] ?: 'Verification Fee') ?></td>
                            <td style="padding:0.75rem"><?= htmlspecialchars($payment['client_name']) ?></td>
                            <td style="padding:0.75rem"><?= htmlspecialchars($payment['provider_name'] ?: '-') ?></td>
                            <td style="padding:0.75rem;text-align:right">UGX <?= number_format($payment['amount']) ?></td>
                            <td style="padding:0.75rem;text-align:right;color:#DC3545">UGX <?= number_format($commission) ?></td>
                            <td style="padding:0.75rem;text-align:right;color:#28A745;font-weight:600">UGX <?= number_format($net_amount) ?></td>
                            <td style="padding:0.75rem;text-align:center">
                                <button onclick="openReleaseModal('<?= htmlspecialchars($payment['payment_id']) ?>', <?= $payment['amount'] ?>, <?= $commission ?>, <?= $net_amount ?>)" class="btn btn-sm btn-primary">Release</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Released Payments -->
    <div class="card">
        <div class="card-header">
            <h2>Recently Released Payments</h2>
        </div>
        <?php if (empty($released_payments)): ?>
            <div style="padding:2rem;text-align:center;color:#666">No released payments yet</div>
        <?php else: ?>
            <table style="width:100%;border-collapse:collapse">
                <thead>
                    <tr style="background:#f5f5f5">
                        <th style="padding:0.75rem;text-align:left">Payment ID</th>
                        <th style="padding:0.75rem;text-align:left">Service Request</th>
                        <th style="padding:0.75rem;text-align:left">Provider</th>
                        <th style="padding:0.75rem;text-align:right">Net Amount</th>
                        <th style="padding:0.75rem;text-align:left">Release Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($released_payments as $payment): ?>
                        <tr style="border-bottom:1px solid #eee">
                            <td style="padding:0.75rem"><?= htmlspecialchars($payment['payment_id']) ?></td>
                            <td style="padding:0.75rem"><?= htmlspecialchars($payment['title'] ?: 'Verification Fee') ?></td>
                            <td style="padding:0.75rem"><?= htmlspecialchars($payment['provider_name'] ?: '-') ?></td>
                            <td style="padding:0.75rem;text-align:right;color:#28A745;font-weight:600">UGX <?= number_format($payment['net_amount_to_provider']) ?></td>
                            <td style="padding:0.75rem"><?= htmlspecialchars($payment['payment_date']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Verification Modal -->
<div id="verifyModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
    <div style="background:white;padding:2rem;border-radius:12px;max-width:600px;width:90%">
        <h3 style="margin-top:0">Verify Payment</h3>
        <div style="background:#f5f5f5;padding:1rem;border-radius:8px;margin-bottom:1rem">
            <div><strong>Payment ID:</strong> <span id="verifyPaymentId"></span></div>
            <div><strong>Amount:</strong> <span id="verifyAmount"></span></div>
            <div style="margin-top:0.5rem;color:#666;font-size:14px">
                Please check your bank/Mobile Money account to verify this payment was received before approving.
            </div>
        </div>
        <form method="POST">
            <input type="hidden" name="payment_id" id="modalVerifyPaymentId"/>
            <input type="hidden" name="verify_payment" value="1"/>

            <div style="margin-bottom:1rem">
                <label style="display:block;margin-bottom:0.5rem">Verification Notes</label>
                <textarea name="verification_notes" class="form-control" style="width:100%;padding:0.5rem;border:1px solid #ddd;border-radius:4px;min-height:80px" placeholder="Add verification notes (e.g., confirmed in bank statement...)"></textarea>
            </div>

            <div style="display:flex;gap:1rem;margin-bottom:1rem">
                <button type="submit" name="action" value="approve" class="btn btn-primary" style="flex:1">✅ Approve Payment</button>
                <button type="submit" name="action" value="reject" class="btn btn-danger" style="flex:1">❌ Reject Payment</button>
            </div>
            <button type="button" onclick="closeVerifyModal()" class="btn btn-ghost" style="width:100%">Cancel</button>
        </form>
    </div>
</div>

<!-- Release Modal -->
<div id="releaseModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
    <div style="background:white;padding:2rem;border-radius:12px;max-width:500px;width:90%">
        <h3 style="margin-top:0">Release Payment</h3>
        <form method="POST">
            <input type="hidden" name="payment_id" id="modalPaymentId"/>
            <input type="hidden" name="release_payment" value="1"/>
            
            <div style="background:#f5f5f5;padding:1rem;border-radius:8px;margin-bottom:1rem">
                <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem">
                    <span>Gross Amount:</span>
                    <span id="modalGross" style="font-weight:600"></span>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;color:#DC3545">
                    <span>Commission (<?= $commission_rate ?>%):</span>
                    <span id="modalCommission"></span>
                </div>
                <div style="display:flex;justify-content:space-between;color:#28A745;font-weight:700;font-size:18px;border-top:1px solid #ddd;padding-top:0.5rem">
                    <span>Net to Provider:</span>
                    <span id="modalNet"></span>
                </div>
            </div>
            
            <div style="margin-bottom:1rem">
                <label style="display:block;margin-bottom:0.5rem">Release Notes</label>
                <textarea name="release_notes" class="form-control" style="width:100%;padding:0.5rem;border:1px solid #ddd;border-radius:4px;min-height:80px" placeholder="Add release notes..."></textarea>
            </div>
            
            <div style="display:flex;gap:1rem">
                <button type="submit" class="btn btn-primary">Confirm Release</button>
                <button type="button" onclick="closeReleaseModal()" class="btn btn-ghost">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openVerifyModal(paymentId, paymentIdDisplay, amount) {
    document.getElementById('modalVerifyPaymentId').value = paymentId;
    document.getElementById('verifyPaymentId').textContent = paymentIdDisplay;
    document.getElementById('verifyAmount').textContent = 'UGX ' + amount.toLocaleString();
    document.getElementById('verifyModal').style.display = 'flex';
}

function closeVerifyModal() {
    document.getElementById('verifyModal').style.display = 'none';
}

function openReleaseModal(paymentId, gross, commission, net) {
    document.getElementById('modalPaymentId').value = paymentId;
    document.getElementById('modalGross').textContent = 'UGX ' + gross.toLocaleString();
    document.getElementById('modalCommission').textContent = 'UGX ' + commission.toLocaleString();
    document.getElementById('modalNet').textContent = 'UGX ' + net.toLocaleString();
    document.getElementById('releaseModal').style.display = 'flex';
}

function closeReleaseModal() {
    document.getElementById('releaseModal').style.display = 'none';
}
</script>
</body></html>
