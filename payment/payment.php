<?php
require_once '../config.php';
require_once '../db_connect.php';
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'client') {
    header("Location: ../login.html"); exit;
}
$clientId = $_SESSION['user_id'];
$db = getDB();

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Get client's pending requests that need payment
$stmt = $db->prepare("SELECT request_id, title, budget FROM service_request WHERE client_id = ? AND status = 'open' ORDER BY date_posted DESC");
$stmt->execute([$clientId]);
$pending_requests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Payments — IT Hire</title>
    <link rel="stylesheet" href="../assets/style.css"/>
</head>
<body>

<nav class="navbar">
    <a href="../index.php" class="nav-logo">IT<span>Hire</span></a>
    <div class="nav-links">
        <a href="../index.php">Home</a>
        <a href="../provider/view_requests.php">Browse Requests</a>
        <a href="../login.html">Login</a>
        <a href="../client/dashboard.php" class="active">Dashboard</a>
    </div>
</nav>

<div class="page-md" style="padding-top:2rem">
    <div class="page-header">
        <h1>Payments</h1>
        <p>Record and track your service payments</p>
    </div>

    <!-- Payment Instructions -->
    <div class="card" style="margin-bottom:2rem;background:#f0f7ff;border-left:4px solid #1B3F7A">
        <div style="padding:1rem">
            <h3 style="margin-top:0;margin-bottom:1rem">Payment Details</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                <div>
                    <strong>Mobile Money (MTN):</strong><br/>
                    <span style="font-size:18px;color:#1B3F7A;font-weight:600"><?= MTN_MOBILE_MONEY_NUMBER ?></span><br/>
                    <small>Account Name: <?= MTN_ACCOUNT_NAME ?></small>
                </div>
                <div>
                    <strong>Bank Transfer (Stanbic):</strong><br/>
                    <span style="font-size:18px;color:#1B3F7A;font-weight:600"><?= STANBIC_ACCOUNT_NUMBER ?></span><br/>
                    <small><?= STANBIC_BANK_NAME ?><br/>Account Name: <?= STANBIC_ACCOUNT_NAME ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Verification Fee Payment -->
    <div class="card" style="margin-bottom:2rem">
        <div class="card-header">
            <h2>Pay Verification Fee</h2>
            <p style="font-size:13px;color:#666;margin:0">One-time fee of UGX 50,000 to enable service request posting</p>
        </div>
        <div class="card-body">

            <div id="error-message-verify" style="display:none;" class="alert alert-error"></div>

            <form method="POST" action="payment_process.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"/>
                <input type="hidden" name="payment_type" value="verification_fee"/>
                <input type="hidden" name="amount" value="50000"/>
                <input type="hidden" name="currency" value="UGX"/>
                
                <div class="form-group">
                    <label>Payment Method <span class="req">*</span></label>
                    <select name="method" class="form-control">
                        <option value="">Select method</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="bank_transfer">Bank Transfer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Payment Channel <span class="req">*</span></label>
                    <select name="channel" class="form-control">
                        <option value="">Select channel</option>
                        <option value="MTN">MTN Mobile Money</option>
                        <option value="Stanbic">Stanbic Bank</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Transaction Reference <span class="req">*</span></label>
                    <input type="text" name="reference" class="form-control"
                           placeholder="e.g. MM transaction number or bank reference"/>
                </div>
                <div class="alert alert-info">
                    <span class="alert-icon">ℹ️</span>
                    <div>Amount: <strong>UGX 50,000</strong> (Verification Fee)</div>
                </div>
                <button type="submit" class="btn btn-primary" id="verify-submit-btn">Pay Verification Fee</button>
            </form>
        </div>
    </div>

    <!-- Service Payment -->
    <div class="card">
        <div class="card-header">
            <h2>Pay for Service Request</h2>
            <p style="font-size:13px;color:#666;margin:0">Pay service budget upfront into escrow</p>
        </div>
        <div class="card-body">

            <div id="error-message-service" style="display:none;" class="alert alert-error"></div>

            <form method="POST" action="payment_process.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"/>
                <input type="hidden" name="payment_type" value="service_payment"/>

                <div class="form-group">
                    <label>Select Service Request <span class="req">*</span></label>
                    <select name="request_id" id="request_select" class="form-control" required onchange="updateBudget()">
                        <option value="">Select a request</option>
                        <?php foreach ($pending_requests as $req): ?>
                            <option value="<?= htmlspecialchars($req['request_id']) ?>" data-budget="<?= $req['budget'] ?>">
                                <?= htmlspecialchars($req['request_id']) ?> - <?= htmlspecialchars($req['title']) ?> (UGX <?= number_format($req['budget']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($pending_requests)): ?>
                        <small style="color:#666">No pending requests available for payment. Post a request first.</small>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Budget Amount (UGX) <span class="req">*</span></label>
                    <input type="number" name="budget" id="budget_input" class="form-control"
                           placeholder="Select a request above" min="0" readonly/>
                </div>
                    <div class="form-group">
                        <label>Payment Method <span class="req">*</span></label>
                        <select name="method" class="form-control">
                            <option value="">Select method</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Payment Channel <span class="req">*</span></label>
                    <select name="channel" class="form-control">
                        <option value="">Select channel</option>
                        <option value="MTN">MTN Mobile Money</option>
                        <option value="Stanbic">Stanbic Bank</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Transaction Reference <span class="req">*</span></label>
                    <input type="text" name="reference" class="form-control"
                           placeholder="e.g. MM transaction number or bank reference"/>
                </div>
                <div class="alert alert-info">
                    <span class="alert-icon">ℹ️</span>
                    <div>Total Amount: <strong>UGX [Budget + 50,000]</strong> (Budget + Verification Fee)</div>
                </div>
                <button type="submit" class="btn btn-primary" id="service-submit-btn">Pay Service Budget</button>
            </form>
        </div>
    </div>
</div>
</body></html>

<script>
function updateBudget() {
    const select = document.getElementById('request_select');
    const budgetInput = document.getElementById('budget_input');
    const selectedOption = select.options[select.selectedIndex];

    if (selectedOption && selectedOption.dataset.budget) {
        budgetInput.value = selectedOption.dataset.budget;
    } else {
        budgetInput.value = '';
    }
}

// Double-submit protection
const forms = document.querySelectorAll('form');
forms.forEach(form => {
    form.addEventListener('submit', function(e) {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';
        }
    });
});

const urlParams = new URLSearchParams(window.location.search);
const error = urlParams.get('error');
const errorDiv1 = document.getElementById('error-message-verify');
const errorDiv2 = document.getElementById('error-message-service');

if (error) {
    const errorMsg = {
        'invalid_amount': 'Enter a valid payment amount.',
        'missing_method': 'Select a payment method.',
        'missing_channel': 'Select a payment channel.',
        'missing_reference': 'Transaction reference is required.',
        'missing_request_id': 'Service Request ID is required.',
        'invalid_budget': 'Enter a valid budget amount.',
        'invalid_request': 'Invalid Service Request ID. Please check and try again.',
        'unauthorized_request': 'You are not authorized to pay for this request.',
        'db_error': 'Database error occurred. Please try again.',
        'duplicate_payment': 'You already have a verification fee payment pending or approved.',
        'csrf_invalid': 'Invalid security token. Please refresh the page and try again.',
        'rate_limit_exceeded': 'Too many payment attempts. Please wait a minute before trying again.'
    };

    if (errorMsg[error]) {
        errorDiv1.style.display = 'block';
        errorDiv2.style.display = 'block';
        errorDiv1.innerHTML = '<span class="alert-icon">⚠️</span><div>' + errorMsg[error] + '</div>';
        errorDiv2.innerHTML = '<span class="alert-icon">⚠️</span><div>' + errorMsg[error] + '</div>';
    }
}
</script>
