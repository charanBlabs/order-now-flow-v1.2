<?php

/**
 * BDGS Tools – Stripe Webhook Listener (Optimized & Debugged)
 * Handles Deferred Invoice Creation directly to External API to avoid Loopback Timeouts.
 */
header('Content-Type: application/json');

// --- CONFIGURATION START ---
$perfex_api_url  = 'https://pmp.businesslabs.org/api/payments';
// *** UPDATE: New Auth Token for PMP/Perfex ***
$perfex_token    = '_PLACEHOLDER_FOR_GITHUB_PUSH';
$invoice_api_endpoint = 'https://pmp.businesslabs.org/api/invoices';

$payment_mode_id = 'stripe';
// --- CONFIGURATION END ---

// Enable error logging to file for debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/webhook_errors.log');
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// REPLACE WITH LIVE KEYS WHEN READY (Using User's Provided Test Key)
$stripe_secret  = '_PLACEHOLDER_FOR_GITHUB_PUSH';
$webhook_secret = '_PLACEHOLDER_FOR_GITHUB_PUSH';

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// 1. Verify Signature
if (!empty($webhook_secret) && !empty($sig_header)) {
    $parts = [];
    foreach (explode(',', $sig_header) as $part) {
        list($k, $v) = explode('=', $part, 2);
        $parts[trim($k)] = trim($v);
    }
    if (empty($parts['t']) || empty($parts['v1'])) {
        http_response_code(400);
        exit;
    }

    $signed_payload = $parts['t'] . '.' . $payload;
    $expected_sig = hash_hmac('sha256', $signed_payload, $webhook_secret);
    if (!hash_equals($expected_sig, $parts['v1'])) {
        http_response_code(400);
        exit;
    }
}

$event = json_decode($payload, true);
if (!$event || !isset($event['type'])) {
    http_response_code(400);
    exit;
}

// 2. Extract Data (ROBUST INITIALIZATION)
$type = $event['type'];
$data = $event['data']['object'] ?? [];

$email = $data['customer_email'] ?? $data['customer_details']['email'] ?? '';
$amount = isset($data['amount_total']) ? $data['amount_total'] / 100 : (isset($data['amount']) ? $data['amount'] / 100 : 0);
$currency = strtoupper($data['currency'] ?? 'USD');
$status = $data['status'] ?? $data['payment_status'] ?? 'unknown';

// Transaction ID logic - Force Definition
$stripe_id = '';
if (isset($data['payment_intent']) && is_string($data['payment_intent'])) {
    $stripe_id = $data['payment_intent'];
} elseif (isset($data['id'])) {
    $stripe_id = $data['id'];
} else {
    $stripe_id = 'unknown_' . time();
}

$stripe_object = $data['object'] ?? 'unknown';

// DEBUG LOG: Confirm ID extraction immediately
file_put_contents(__DIR__ . '/webhook_debug.log', date('Y-m-d H:i:s') . " - START: Type=$type, ID=$stripe_id, Status=$status\n", FILE_APPEND);


// --- DB Connection Checks ---
// (Assume global DB connection is active from parent context)

// --- Create Log Table ---
if (function_exists('mysql_query')) {
    mysql_query("
    CREATE TABLE IF NOT EXISTS bdgs_stripe_logs (
      id INT AUTO_INCREMENT PRIMARY KEY,
      event_type VARCHAR(100),
      stripe_object VARCHAR(100),
      stripe_id VARCHAR(255),
      email VARCHAR(255),
      amount DECIMAL(10,2),
      currency VARCHAR(10),
      status VARCHAR(50),
      payload TEXT,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
    ");
}

// --- 3. DUPLICATE CHECK ---
$is_duplicate = false;
if (function_exists('mysql_query')) {
    $check_sql = "SELECT id FROM bdgs_stripe_logs 
                  WHERE stripe_id = '$stripe_id' 
                  AND payload LIKE '%Perfex Update: HTTP 200%' 
                  LIMIT 1";
    $check_res = mysql_query($check_sql);

    if ($check_res && mysql_num_rows($check_res) > 0) {
        $is_duplicate = true;
    }
}

if ($is_duplicate) {
    http_response_code(200);
    echo json_encode(['received' => true, 'status' => 'already_processed']);
    exit;
}

// --- 4. Insert Log (Start of processing) ---
if (function_exists('mysql_query') && function_exists('mysql_real_escape_string')) {
    $payload_safe = mysql_real_escape_string($payload);
    mysql_query("
    INSERT INTO bdgs_stripe_logs (event_type, stripe_object, stripe_id, email, amount, currency, status, payload)
    VALUES ('$type','$stripe_object','$stripe_id','$email','$amount','$currency','$status','$payload_safe')
    ");
} else {
    file_put_contents(__DIR__ . '/webhook_debug.log', date('Y-m-d H:i:s') . " - WARNING: DB connection missing. Skipping DB log.\n", FILE_APPEND);
}


/**
 * HELPER: Direct PMP API Request
 */
if (!function_exists('pmp_api_request')) {
    function pmp_api_request($url, $method = 'GET', $data = [], $token)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 40);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'authtoken: ' . $token,
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'BD-Widget-Agent');

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        file_put_contents(__DIR__ . '/webhook_debug.log', date('Y-m-d H:i:s') . " - API REQ [$method] $url - Code: $http_code - Err: $err\n", FILE_APPEND);

        return ['code' => $http_code, 'body' => $response, 'error' => $err];
    }
}

// --- 5. PROCESS PAYMENT ---
// This was line 151 in old file, now strictly logged
file_put_contents(__DIR__ . '/webhook_debug.log', date('Y-m-d H:i:s') . " - Processing Event Logic for ID: $stripe_id\n", FILE_APPEND);

if (($type == 'checkout.session.completed' || $type == 'payment_intent.succeeded') && ($status == 'complete' || $status == 'succeeded' || $status == 'paid')) {

    $metadata = $data['metadata'] ?? [];
    $invoice_id_to_use = $metadata['invoice_id'] ?? '';

    file_put_contents(__DIR__ . '/webhook_debug.log', date('Y-m-d H:i:s') . " - Metadata: " . print_r($metadata, true) . "\n", FILE_APPEND);


    // 5a. DEFERRED INVOICE CREATION HANDLER
    if (empty($invoice_id_to_use) && !empty($metadata['client_first_name'])) {

        require_once __DIR__ . '/pmp_integration_class.php';
        $pmp = new PmpIntegration($perfex_token);

        file_put_contents(__DIR__ . '/webhook_debug.log', date('Y-m-d H:i:s') . " - Entering Deferred Invoice Creation Block (Refactored)...\n", FILE_APPEND);

        // --- Step 1: Create Invoice ---
        $pmp->log_step($stripe_id, '', 'create_invoice', 'pending', $metadata);

        $invoice_res = $pmp->create_invoice($metadata, $amount);

        if ($invoice_res['status']) {
            $invoice_id_to_use = $invoice_res['invoice_id'];
            $invoice_num_custom = $invoice_res['invoice_number'];
            $pmp->log_step($stripe_id, '', 'create_invoice', 'success', $metadata, $invoice_res['response']);

            // --- Step 2: Add Payment ---
            $pmp->log_step($stripe_id, '', 'add_payment', 'pending', ['invoice_id' => $invoice_id_to_use, 'amount' => $amount]);

            $pay_res = $pmp->add_payment($invoice_id_to_use, $amount, $stripe_id);

            if ($pay_res['status']) {
                $pmp->log_step($stripe_id, '', 'add_payment', 'success', null, $pay_res['response']);
            } else {
                $pmp->log_step($stripe_id, '', 'add_payment', 'failed', null, $pay_res['response'], $pay_res['error']);
            }

            // --- Step 3: Update Stripe ---
            $pmp->log_step($stripe_id, '', 'update_stripe', 'pending');
            $msg = "Inv: " . ($invoice_num_custom ?? $invoice_id_to_use);
            $ch_str = curl_init("https://api.stripe.com/v1/payment_intents/$stripe_id");
            curl_setopt($ch_str, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch_str, CURLOPT_USERPWD, $stripe_secret . ':');
            curl_setopt($ch_str, CURLOPT_POST, true);
            curl_setopt($ch_str, CURLOPT_POSTFIELDS, http_build_query([
                'description' => "Order #$stripe_id | $msg",
                'metadata' => ['generated_invoice_id' => $invoice_id_to_use]
            ]));
            $str_r = curl_exec($ch_str);
            curl_close($ch_str);
            $pmp->log_step($stripe_id, '', 'update_stripe', 'success', null, $str_r);
        } else {
            $pmp->log_step($stripe_id, '', 'create_invoice', 'failed', $metadata, $invoice_res['response'], $invoice_res['error']);
        }
    }
}

http_response_code(200);
echo json_encode(['received' => true]);
