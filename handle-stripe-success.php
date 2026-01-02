<?php
// --- MODIFICATION: Start session to prevent duplicates ---
// This MUST be at the very top of your file, before any output.
session_start();

/**
 * Widget Name: Handle Stripe Success (v2)
 * Description: Handles both one-time and subscription Stripe payments inside BD.
 */

$stripe_secret = '_PLACEHOLDER_FOR_GITHUB_PUSH';

// --- Validate Input ---
//$session_id = $pars[5];


$session_id = $_GET['sid'] ?? '';


if ($session_id == '') {
    echo "<div class='alert alert-danger text-center'>Invalid payment session.</div>";
    return;
}

// --- Fetch Checkout Session ---
$ch = curl_init("https://api.stripe.com/v1/checkout/sessions/" . $session_id);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD => $stripe_secret . ':',
]);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code != 200) {
    echo "<div class='alert alert-warning text-center'>Unable to verify payment with Stripe. Please contact support.</div>";
    return;
}

$session = json_decode($response, true);
if (!is_array($session) || empty($session['id'])) {
    echo "<div class='alert alert-warning text-center'>Invalid session data returned from Stripe.</div>";
    return;
}

// --- Extract Common Data ---
$metadata = $session['metadata'] ?? [];
$tool_id  = mysql_real_escape_string($metadata['tool_id'] ?? '');
$tool_name = mysql_real_escape_string($metadata['tool_name'] ?? 'BDGS Tool');
$payment_unit = mysql_real_escape_string($metadata['payment_unit'] ?? 'fixed');
$startsfrom_type = mysql_real_escape_string($metadata['starts_from_type']);
$imp_type = mysql_real_escape_string($metadata['implementation_type']) ?? '';
$del_time = mysql_real_escape_string($metadata['delivery_time_description']) ?? '';
$war_time = mysql_real_escape_string($metadata['warranty_time_description']) ?? '';

$actual_price = mysql_real_escape_string($metadata['actual_price']) ?? '';
$finaltotal = mysql_real_escape_string($metadata['total']) ?? '';
$finalsubtotal = mysql_real_escape_string($metadata['subtotal']) ?? '';
$finaldiscount = mysql_real_escape_string($metadata['discount_total']) ?? '';
$invoice_id = mysql_real_escape_string($metadata['invoice_id']) ?? '';
$invoice_hash =  mysql_real_escape_string($metadata['invoice_hash']) ?? '';
$post_url = mysql_real_escape_string($metadata['post_url']);
$user_email = mysql_real_escape_string($session['customer_details']['email'] ?? '');
$user_name  = mysql_real_escape_string($session['customer_details']['name'] ?? '');
$user_phone = mysql_real_escape_string($metadata['user_phone']) ?? '';
$amount_paid = isset($session['amount_total']) ? $session['amount_total'] / 100 : 0;
$formatted_amount = '$' . number_format($amount_paid, 2);
$payment_status = mysql_real_escape_string($session['payment_status'] ?? 'unknown');
$transaction_id = mysql_real_escape_string($session['payment_intent'] ?? '');
$subscription_id = '';
$subscription_status = '';
$renewal_interval = '';
$next_payment_date = null;

// --- Disclaimers ---
$DISCLAIMERS = [
    'free' => "Fully free as described. Any requests for <i>extra features, new ideas, or additional custom work</i> will be billed separately.",
    'fixed' => "<strong>Fixed price</strong> covers all listed inclusions.<br> Requests for <strong>extra features, new ideas, or custom work</strong> will be reviewed and quoted separately — <strong>no extra charges without your approval</strong>.",
    'subscription' => "<strong>Subscription</strong> covers listed inclusions. Any requests for <i>extra features, new ideas, or additional custom work</i> will be billed separately",
    'quote' => "<strong>No upfront payment</strong>. We’ll review your needs, meet if required, and share a quote. Work begins only after approval.",
    'starts-from-base' => "<strong>Base price</strong> covers listed inclusions. If no extras are needed, simply reply to our email or contact <strong>support@bdgrowthsuite.com</strong> and we’ll implement. If extras are needed, email or reply — we’ll review, meet if needed, and quote for the additional work.",
    'starts_from_deposit' => "<strong>Most of our tools require full upfront payment.</strong><br> For this service, we only take a <strong>small commitment fee</strong> to secure your spot and begin planning.<br>This covers a <strong>short consulting/brainstorming call</strong> where we understand your ideas, share ours, and finalize the scope.<br> If you move forward, the <strong>full amount is credited to your final invoice.</strong>"
];

// --- Determine Payment Type Key ---
if ($payment_unit == 'starts_from' && $startsfrom_type == 'basic') {
    $payment_type = 'starts-from-base';
} else if ($payment_unit == 'starts_from' && $startsfrom_type == 'deposit') {
    $payment_type = 'starts_from_deposit';
} else if ($payment_unit == 'fixed_price') {
    $payment_type = 'fixed';
} else if ($payment_unit == 'subscription') {
    $payment_type = 'subscription';
} else {
    // Default to the value itself (e.g., 'free', 'quote')
    $payment_type = $payment_unit;
}

$disclaimer_text = $DISCLAIMERS[$payment_type] ?? $DISCLAIMERS['fixed'];

if (strpos($disclaimer_text, '${DEPOSIT_PRICE}') !== false) {
    $disclaimer_text = str_replace('${DEPOSIT_PRICE}', $formatted_amount, $disclaimer_text);
}

// --- Check if it's a Subscription ---
if (!empty($session['subscription'])) {
    $payment_unit = 'subscription'; // This correctly overrides if it's a subscription
    $subscription_id = mysql_real_escape_string($session['subscription']);

    $ch = curl_init("https://api.stripe.com/v1/subscriptions/" . $subscription_id);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => $stripe_secret . ':',
    ]);
    $sub_response = curl_exec($ch);
    $sub_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($sub_code == 200) {
        $sub = json_decode($sub_response, true);
        $subscription_status = mysql_real_escape_string($sub['status']);
        $renewal_interval = mysql_real_escape_string($sub['items']['data'][0]['plan']['interval'] ?? '');
        $next_payment_date = isset($sub['current_period_end'])
            ? date('Y-m-d H:i:s', $sub['current_period_end'])
            : null;
    }
}


// --- MODIFICATION: CALL INTERNAL API & PREVENT DUPLICATES ---
// This is the entire fix for your reload problem.

// 1. Create a unique session flag for this transaction
$processed_flag = 'stripe_processed_' . $session_id;

// 2. Check if this transaction has ALREADY been processed.
// If the flag is not set, we run the code.
if (!isset($_SESSION[$processed_flag])) {

    // 3. Set the API endpoint
    // !! IMPORTANT: You MUST use the full, absolute URL
    $api_url = 'https://bdgrowthsuite.com/api/widget/get/html/tools-quote-handler';

    // 4. Prepare all data for your other widget
    // This array contains everything your old DB/email logic needed
    $postData = [
        // Data from Stripe session
        'tool_id' => $tool_id,
        'tool_name' => $tool_name,
        'user_email' => $user_email,
        'user_name' => $user_name,
        'user_phone' => $user_phone,
        'amount_paid' => $amount_paid,
        'payment_unit' => $payment_type, // Send the final, calculated type
        'stripe_session_id' => $session_id,
        'transaction_id' => $transaction_id,
        'payment_status' => $payment_status,
        'starts_from_type' => $startsfrom_type,
        'total' => $finaltotal,
        'subtotal' => $finalsubtotal,
        'discount' => $finaldiscount,
        'invoice_id' => $invoice_id ?? '',
        'invoice_hash' => $invoice_hash ?? '',
        'post_url' => $post_url,
        // Subscription data (if any)
        'subscription_id' => $subscription_id,
        'subscription_status' => $subscription_status,
        'renewal_interval' => $renewal_interval,
        'next_payment_date' => $next_payment_date,
        'implementation_type' => $imp_type,
        'delivery_time_description' => $del_time,
        'warranty_time_description' => $war_time,

        // Data that MUST be in your Stripe metadata
        'origin_url' => $metadata['origin_url'] ?? '',
        'requirements' => $metadata['requirements'] ?? '',
        'bd_user_id' => $metadata['bd_user_id'] ?? ''
    ];


    // 5. Initialize and execute the cURL request
    $ch_internal = curl_init();
    curl_setopt($ch_internal, CURLOPT_URL, $api_url);
    curl_setopt($ch_internal, CURLOPT_POST, true);
    curl_setopt($ch_internal, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch_internal, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch_internal, CURLOPT_TIMEOUT, 10); // Don't wait too long
    curl_setopt($ch_internal, CURLOPT_HTTPHEADER, [
        'X-Requested-With: XMLHttpRequest' // Mimic AJAX
    ]);

    $api_response = curl_exec($ch_internal);
    $api_http_code = curl_getinfo($ch_internal, CURLINFO_HTTP_CODE);
    curl_close($ch_internal);

    // 6. Set the flag to true AFTER the call
    // This prevents it from ever running again for this session_id
    $_SESSION[$processed_flag] = true;

    // 7. (Optional) Log if the internal API call failed
    if ($api_http_code != 200) {
        error_log("Internal API save failed for Stripe session $session_id. HTTP: $api_http_code. Response: $api_response");
    }
} // --- End duplicate check ---
// --- END MODIFICATION ---


// --- MODIFICATION: Set dynamic thank-you messages based on payment_unit ---
$main_heading = '';
$main_text = '';

// We use $payment_type which is now correctly aligned
switch ($payment_type) {
    // FIXED
    case 'fixed':
        $main_heading = 'Payment Received!';
        $main_text = 'Your payment for "<strong>' . $tool_name . '</strong>" has been received successfully.<br><br>
            Within <strong>1–2 business days</strong> (usually the same day), our team will reach out via <strong>email or WhatsApp</strong> to confirm what’s included and check if you’d like any extra enhancements.<br><br>
            <strong>Extras (if any) are quoted separately — nothing moves forward without your approval.</strong>';
        break;

    // SUBSCRIPTION
    case 'subscription':
        $main_heading = 'Payment Received!';
        $main_text = 'Your payment for "<strong>' . $tool_name . '</strong>" has been received, and your subscription is now active.<br><br>
            Within <strong>1-2 business days</strong> (usually the same day), our team will reach out via <strong>email or Whatsapp</strong> with setup and activation details.<br><br>
            Your active subscription includes <strong>all future updates, improvements, and ongoing support</strong> covered under your plan.<br><br>';
        break;

    // STARTS FROM (FULL PAY) keep this line if needed <br>Your project slot is now locked in—no approval or meeting is needed. 
    case 'starts-from-base':
        $main_heading = 'Payment Received!';
        $main_text = 'Your payment for "<strong>' . $tool_name . '</strong>" has been received.
            <br>Our team will reach out within 2 business days (usually the same day) with setup instructions and next steps.';
        break;

    // STARTS FROM (DEPOSIT / COMMITMENT)
    case 'starts_from_deposit':
        $main_heading = 'Payment Received!';
        $main_text = 'Your <strong>' . $formatted_amount . ' commitment payment</strong> for "<strong>' . $tool_name . '</strong>" has been received.<br><br>
            You’ll receive an email soon with a link to schedule a quick call. The right person from our team will join to understand your goals and brainstorm next steps.<br><br>
            <strong>Your ' . $formatted_amount . ' will be fully credited toward the total project cost if you proceed.</strong>';
        break;

    // FREE
    case 'free':
        $main_heading = 'Order Received!';
        $main_text = 'Your order for "<strong>' . $tool_name . '</strong>" has been received. You’ll receive an email within 1–2 business days with simple setup instructions.
            <br>You can follow them yourself, or reply if you’d like our team to handle the setup for you—free of charge.';
        break;

    // ASK FOR QUOTE
    case 'quote':
        $main_heading = 'Order Received!';
        $main_text = 'Your request for "<strong>' . $tool_name . '</strong>" has been received.
            <br>Our senior team will review your requirements and send a personalized quote within 2 business days (often the same day).
            <br>You’ll also receive a link to schedule a discovery call if we need more details.';
        break;

    // DEFAULT
    default:
        $main_heading = 'Order Received!';
        $main_text = 'Your order for "<strong>' . $tool_name . '</strong>" has been received.
            <br>Our team will reach out within 2 business days (usually the same day) to confirm next steps.';
}
// --- END MODIFICATION ---

$page_title = "Payment Successful";
?>

<div class="bdgs-stripe-card">
    <div class="bdgs-stripe-icon-container">
        <svg class="bdgs-stripe-animated-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 88 88">
            <circle class="bdgs-stripe-animated-icon-circle" cx="44" cy="44" r="42" />
            <polyline class="bdgs-stripe-animated-icon-check" points="27 44 40 57 63 34" />
        </svg>
    </div>

    <h1><?= $main_heading ?></h1>
    <p class="bdgs-stripe-subtext">
        <?= $main_text ?>
    </p>


    <?php if ($payment_unit == 'subscription') : ?>

        <div class="bdgs-stripe-separator"></div>

        <div class="alert alert-info text-left" style="margin-top:15px;margin-bottom: 15px;">
            <strong>Subscription Status:</strong> <?= ucfirst($subscription_status) ?><br>
            <strong>Billing Cycle:</strong> <?= ucfirst($renewal_interval) . 'ly' ?><br>
            <?php if ($next_payment_date): ?>
                <strong>Next Payment:</strong> <?= date('M j, Y', strtotime($next_payment_date)) ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="bdgs-stripe-info-box">
        <div class="bdgs-stripe-info-box-flex">
            <div>
                <?= $disclaimer_text ?>
            </div>
        </div>
    </div>

    <div class="bdgs-stripe-separator"></div>

    <h2>Need Help or Have Questions?</h2>
    <p class="bdgs-stripe-help-text">
        Contact us at <a href="mailto:support@businesslabs.org" class="bdgs-contact-link">support@businesslabs.org</a> or
        <a href="https://book.businesslabs.org/" target="_blank" class="bdgs-book-link">book a call</a>.
    </p>

    <button class="bdgs-stripe-button" onclick="window.location.href='<?php echo $post_url; ?>'">Great!</button>
</div>