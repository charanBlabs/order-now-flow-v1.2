<?php

/**
 * BDGS Tools - Stripe Payment Failed / Cancelled Handler
 * -------------------------------------------------------
 * Handles cancelled or failed Stripe checkout sessions dynamically inside BD.
 * Works without Stripe SDK (pure cURL).
 */

header('Content-Type: text/html; charset=UTF-8');

$stripe_secret = '_PLACEHOLDER_FOR_GITHUB_PUSH';
$session_id = isset($_GET['sid']) ? trim($_GET['sid']) : '';

if ($session_id == '') {
    // Fallback if Stripe didn’t send session_id (user just clicked “cancel”)
    echo "<h3>Payment Cancelled.</h3><p>You can try again anytime from your dashboard.</p>";
    exit;
}

// --- STEP 1: Retrieve Session Info via Stripe API ---
$ch = curl_init("https://api.stripe.com/v1/checkout/sessions/" . $session_id);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD => $stripe_secret . ':',
]);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code != 200) {
    echo "<h3>Unable to verify payment session. Please try again.</h3>";
    exit;
}

$session = json_decode($response, true);

// --- STEP 2: Extract Metadata / Customer Info ---
$metadata = isset($session['metadata']) ? $session['metadata'] : [];
$tool_name = $metadata['tool_name'] ?? 'BDGS Tool';
$user_email = $session['customer_details']['email'] ?? '';
$user_name = $session['customer_details']['name'] ?? '';
$payment_status = $session['payment_status'] ?? 'failed';
$transaction_id = $session['payment_intent'] ?? '';
$amount_total = isset($session['amount_total']) ? $session['amount_total'] / 100 : 0;
$post_url = !empty($metadata['post_url']) ? $metadata['post_url'] : '/account/home';



// --- STEP 3: Log into BD DB (same pattern as success) ---
$create_table = "
CREATE TABLE IF NOT EXISTS bdgs_failed_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tool_name VARCHAR(255),
  email VARCHAR(255),
  customer_name VARCHAR(255),
  amount DECIMAL(10,2),
  stripe_session VARCHAR(255),
  stripe_txn_id VARCHAR(255),
  payment_status VARCHAR(50),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
mysql_query($create_table);

$insert = sprintf(
    "INSERT INTO bdgs_failed_orders (tool_name, email, customer_name, amount, stripe_session, stripe_txn_id, payment_status)
   VALUES ('%s','%s','%s','%s','%s','%s','%s')",
    mysql_real_escape_string($tool_name),
    mysql_real_escape_string($user_email),
    mysql_real_escape_string($user_name),
    mysql_real_escape_string($amount_total),
    mysql_real_escape_string($session_id),
    mysql_real_escape_string($transaction_id),
    mysql_real_escape_string($payment_status)
);
mysql_query($insert);

/*
// --- STEP 4: Send Failure Notification Email ---
if (function_exists('sendEmailTemplate') && !empty($user_email)) {
    $vars = [
        'tool_name' => $tool_name,
        'amount' => number_format($amount_total, 2),
        'transaction_id' => $transaction_id,
        'payment_status' => ucfirst($payment_status),
    ];
    sendEmailTemplate('stripe_payment_failed', $user_email, $vars);
}
*/


// --- STEP 5: Display Friendly UI to User ---
$page_title = "Payment Failed";
$main_heading = "Payment Cancelled or Failed";

?>



<div class="bdgs-stripe-card">
    <div class="bdgs-stripe-icon-container">
        <!-- Animated 'X' SVG -->
        <svg class="bdgs-stripe-animated-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 88 88">
            <circle class="bdgs-stripe-animated-icon-circle" cx="44" cy="44" r="42" />
            <line class="bdgs-stripe-animated-icon-x-line1" x1="32" y1="32" x2="56" y2="56" />
            <line class="bdgs-stripe-animated-icon-x-line2" x1="56" y1="32" x2="32" y2="56" />
        </svg>
    </div>

    <h1><?= $main_heading ?></h1>
    <p class="bdgs-stripe-subtext">
        You can try again anytime from your dashboard.
    </p>

    <!-- This button matches the 'btn' class from your example -->
    <button class="bdgs-stripe-button" onclick="window.location.href='<?php echo $post_url; ?>'">Click here to try again</button>

    <!-- Removed the paragraph about transaction ID -->


    <div class="bdgs-stripe-redirect-note" style="margin-top:15px;font-size:14px;color:#666;">
        Redirecting you back to the previous page in
        <strong><span id="bdgs-countdown">10</span></strong> seconds so you can try again...
    </div>
</div>

<script>
    (function() {
        var redirectUrl = '<?php echo $post_url; ?>';
        var seconds = 10;
        var counter = document.getElementById('bdgs-countdown');

        var interval = setInterval(function() {
            seconds--;
            counter.textContent = seconds;

            if (seconds <= 0) {
                clearInterval(interval);
                window.location.href = redirectUrl;
            }
        }, 1000);
    })();
</script>