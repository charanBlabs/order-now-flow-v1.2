<?php

class PmpIntegration
{
    private $api_url = 'https://pmp.businesslabs.org/api';
    private $token;
    private $db_link;

    public function __construct($token)
    {
        $this->token = $token;
        // Assume global connection or init new one if needed, but for now rely on existing global or pass it in.
        // In legacy php apps, mysql_query uses the last open link.
        // We will try to use mysql_query compatible logic or upgrade to mysqli if detected.
    }

    // --- LOGGING HELPER ---
    public function log_step($stripe_session_id, $stripe_txn_id, $step_name, $status, $request_payload = null, $response_payload = null, $error_msg = null)
    {
        $req = $request_payload ? json_encode($request_payload) : null;
        $res = $response_payload ? json_encode($response_payload) : null;

        // Sanitize for SQL
        $s_sid = $this->esc($stripe_session_id);
        $s_tid = $this->esc($stripe_txn_id);
        $s_step = $this->esc($step_name);
        $s_status = $this->esc($status);
        $s_req = $this->esc($req);
        $s_res = $this->esc($res);
        $s_err = $this->esc($error_msg);

        $sql = "INSERT INTO pmp_process_logs (stripe_session_id, stripe_txn_id, step_name, status, request_payload, response_payload, error_message, created_at)
                VALUES ('$s_sid', '$s_tid', '$s_step', '$s_status', '$s_req', '$s_res', '$s_err', NOW())";

        // Try executing
        if (function_exists('mysql_query')) {
            mysql_query($sql);
        } elseif (function_exists('mysqli_query')) {
            // Need a link for mysqli
            // Assuming global $mysqli or similar if this was modern. 
            // Fallback: Just log to file if DB fails
            // error_log("DB Insert Failed: No mysql function");
        }

        // Also update Master Table
        if ($status == 'failed' || $status == 'success') {
            $overall = $status == 'success' ? 'processing' : 'failed'; // If a step succeeds, we are still processing unless it's the LAST step.
            if ($step_name == 'final_check' && $status == 'success') {
                $overall = 'completed';
            }
            $this->update_master_status($stripe_session_id, $overall, $step_name);
        }
    }

    public function update_master_status($stripe_session_id, $status, $current_step)
    {
        $s_sid = $this->esc($stripe_session_id);
        $s_status = $this->esc($status);
        $s_step = $this->esc($current_step);

        // Upsert logic
        $sql = "INSERT INTO pmp_order_master (stripe_session_id, overall_status, current_step, last_attempt_at)
                VALUES ('$s_sid', '$s_status', '$s_step', NOW())
                ON DUPLICATE KEY UPDATE overall_status = '$s_status', current_step = '$s_step', last_attempt_at = NOW()";

        if (function_exists('mysql_query')) {
            mysql_query($sql);
        }
    }

    // --- API HELPER ---
    private function request($endpoint, $method = 'GET', $data = [])
    {
        $url = $this->api_url . $endpoint;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 40);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'authtoken: ' . $this->token,
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

        return ['code' => $http_code, 'body' => $response, 'error' => $err];
    }

    // --- CORE STEPS ---

    // Step 1: Check Customer (Not explicitly in requirements but good practice, skipping for now to match flow)

    // Step 2: Create Invoice
    public function create_invoice($metadata, $amount)
    {
        // Logic to get max ID, increment, and post
        // This requires 2 API calls (Get All, then Post)
        // Implemented similar to webhook logic

        // 1. Get All Invoices
        $res_get = $this->request('/invoices', 'GET');
        if ($res_get['code'] < 200 || $res_get['code'] >= 300) {
            return ['status' => false, 'error' => 'Failed to fetch invoices: ' . $res_get['code']];
        }

        $all_invoices = json_decode($res_get['body'], true);
        $max_id = -1;
        $max_invoice_obj = null;
        if (is_array($all_invoices)) {
            foreach ($all_invoices as $inv) {
                if (isset($inv['id']) && is_numeric($inv['id'])) {
                    $current_id = (int)$inv['id'];
                    if ($current_id > $max_id) {
                        $max_id = $current_id;
                        $max_invoice_obj = $inv;
                    }
                }
            }
        }

        if (!$max_invoice_obj) {
            return ['status' => false, 'error' => 'Could not determine max invoice number'];
        }

        $last_num_str = $max_invoice_obj['number'];
        $next_valid_number = (string)((int)$last_num_str + 1);

        // Prepare Data
        $new_items = [];
        if (!empty($metadata['newitems_json'])) {
            $new_items = json_decode($metadata['newitems_json'], true);
        }
        if (empty($new_items)) {
            $item_desc = $metadata['service_name'] ?? 'Service';
            $new_items = [[
                'description' => $item_desc,
                'qty' => 1,
                'rate' => $metadata['tool_price'] ?? 0
            ]];
        }

        $invoice_post_data = [
            'clientid'      => $metadata['customer_id'],
            'number'        => $next_valid_number,
            'date'          => date('Y-m-d'),
            'currency'      => 1,
            'newitems'      => $new_items,
            'allowed_payment_modes' => ["stripe"],
            'billing_street' => $metadata['client_website'] ?? 'Not Provided',
            'subtotal'      => $metadata['subtotal'],
            'total'         => $metadata['total'],
            'discount_type' => "after_tax",
            'discount_total' => $metadata['discount_total'],
            'clientnote'    => $metadata['client_note'] ?? '',
            'terms'         => $metadata['terms_content'] ?? '',
            'recurring'     => $metadata['recurring_val'],
            'cycles'        => $metadata['cycles_val']
        ];

        // 2. Create Invoice
        $res_post = $this->request('/invoices', 'POST', $invoice_post_data);

        $post_resp_data = json_decode($res_post['body'], true);

        // Check success
        if (isset($post_resp_data['data']['new_invoice_id'])) {
            return [
                'status' => true,
                'invoice_id' => $post_resp_data['data']['new_invoice_id'],
                'invoice_number' => $post_resp_data['data']['number'] ?? $next_valid_number,
                'payload' => $invoice_post_data,
                'response' => $post_resp_data
            ];
        }

        return ['status' => false, 'error' => 'Invoice API failed: ' . $res_post['body'], 'payload' => $invoice_post_data, 'response' => $post_resp_data];
    }

    // Step 3: Add Payment
    public function add_payment($invoice_id, $amount, $txn_id)
    {
        $post_fields = [
            'invoiceid'     => $invoice_id,
            'amount'        => $amount,
            'paymentmode'   => 'stripe',
            'transactionid' => $txn_id,
            'note'          => 'Payment via Stripe. Txn: ' . $txn_id
        ];

        $res = $this->request('/payments', 'POST', $post_fields);

        if ($res['code'] >= 200 && $res['code'] < 300) {
            return ['status' => true, 'response' => json_decode($res['body'], true)];
        }

        return ['status' => false, 'error' => 'Payment API failed: ' . $res['body'], 'response' => $res['body']];
    }

    // Step 4: Verify Invoice Status (Optional final check)
    public function check_invoice($invoice_id)
    {
        $res = $this->request('/invoices/' . $invoice_id, 'GET');
        return json_decode($res['body'], true);
    }

    private function esc($str)
    {
        if (function_exists('mysql_real_escape_string')) {
            return mysql_real_escape_string($str);
        }
        return addslashes($str);
    }
}
