<?php
// Platform Configuration Constants

// Payment Details (from environment variables with fallback)
define('STANBIC_BANK_NAME', getenv('STANBIC_BANK_NAME') ?: 'Stanbic Bank Uganda');
define('STANBIC_ACCOUNT_NUMBER', getenv('STANBIC_ACCOUNT_NUMBER') ?: '1234567890');
define('STANBIC_ACCOUNT_NAME', getenv('STANBIC_ACCOUNT_NAME') ?: 'IT Hire Uganda');

define('MTN_MOBILE_MONEY_NUMBER', getenv('MTN_MOBILE_MONEY_NUMBER') ?: '0766821496');
define('MTN_ACCOUNT_NAME', getenv('MTN_ACCOUNT_NAME') ?: 'IT Hire Uganda');

// Platform Settings
define('COMMISSION_RATE', floatval(getenv('COMMISSION_RATE') ?: 10.00)); // 10%
define('VERIFICATION_FEE', intval(getenv('VERIFICATION_FEE') ?: 50000)); // UGX
?>
