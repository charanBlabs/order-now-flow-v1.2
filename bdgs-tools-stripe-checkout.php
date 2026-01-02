<?php

/**
 * BDGS Tools – Stripe Checkout Creator (Final v3)
 * Handles Fixed, Starts-From, and Subscription payments.
 */
header('Content-Type: application/json');

$stripe_secret = '_PLACEHOLDER_FOR_GITHUB_PUSH';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $tool_name  = $data['tool_name']  ?? 'BDGS Tool';
    $tool_id    = $data['tool_id']    ?? '#00';
    $sub_type   = $data['sub_type']    ?? '';
    $tool_price = floatval($data['tool_price'] ?? 0);
    $payment_unit = trim($data['payment_unit'] ?? 'fixed');
    $amount_cents = intval($tool_price * 100);
    if ($payment_unit == 'starts_from') {
        $startsfromtype = $data['startsfromtype'] ?? '';
    }

    $is_subscription = ($payment_unit === 'subscription');

    // --- Build Stripe Payload ---
    $payload = [
        'payment_method_types' => ['card'],
        'customer_email' => $data['user_email'] ?? '',
        'metadata' => [
            'tool_name'    => $tool_name,
            'tool_id'      => $tool_id,
            'user_email'   => $data['user_email'] ?? '',
            'user_name'    => $data['user_name'] ?? '',
            'user_phone'   => $data['user_phone'] ?? '',
            'payment_unit' => $payment_unit,
            'starts_from_type' => $startsfromtype,

            // OPTIONAL: Invoice ID if already created (Legacy Flow compatibility)
            'invoice_id' => $data['generated_invoice_id'] ?? '',
            'invoice_hash' => $data['generated_invoice_hash'] ?? '',
            'customer_id' => $data['customer_id'] ?? '',

            // NEW: Deferred Invoice Parameters (For Webhook Creation)
            'client_first_name' => $data['client_first_name'] ?? '',
            'client_last_name'  => $data['client_last_name'] ?? '',
            'client_website'    => $data['client_website'] ?? '',
            'last_order_id'     => $data['last_order_id'] ?? '',
            'service_name'      => $data['service_name'] ?? '',
            'short_title'       => $data['short_title'] ?? '',
            'post_id'           => $data['post_id'] ?? '',
            'recurring_val'     => $data['recurring_val'] ?? '0',
            'cycles_val'        => $data['cycles_val'] ?? '0',
            'client_note'       => $data['client_note'] ?? '',
            'terms_content'     => $data['terms_content'] ?? '',

            // CRITICAL: Invoice Items Logic (Exact Steps)
            'newitems_json'     => $data['newitems_json'] ?? '',

            // Financials for Invoice
            'implementation_type' => $data['implementation_type'],
            'delivery_time_description' => $data['delivery_time_description'],
            'warranty_time_description' => $data['warranty_time_description'],
            'actual_price' => $data['actual_price'],
            'total' => $data['total'],
            'subtotal' => $data['subtotal'],
            'discount_total' => $data['discount'],
            'post_url' => $data['post_url']
        ],
        'line_items' => [[
            'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                    'name'        => $tool_name,
                    'description' => 'BDGS Tool ID: ' . $tool_id,
                ],
                'unit_amount' => $amount_cents,
            ],
            'quantity' => 1,
        ]],
        'mode' => $is_subscription ? 'subscription' : 'payment',
        'success_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/payment-success?sid={CHECKOUT_SESSION_ID}',
        'cancel_url'  => 'https://' . $_SERVER['HTTP_HOST'] . '/payment-failed?sid={CHECKOUT_SESSION_ID}'
    ];

    if ($is_subscription) {
        $payload['line_items'][0]['price_data']['recurring'] = [
            'interval' => ($sub_type === 'monthly' || $sub_type === 'Monthly') ? 'month' : 'year',
            'interval_count' => 1
        ];
    }

    $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => $stripe_secret . ':',
        CURLOPT_POSTFIELDS     => http_build_query($payload)
    ]);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 200) {
        $session = json_decode($response, true);
        echo json_encode(['url' => $session['url']]);
    } else {
        echo json_encode(['error' => 'Stripe API error: ' . $response]);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
