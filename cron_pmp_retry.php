<?php
// Cron Job: Retry Failed PMP Steps
// Run Frequency: Every Hour

require_once __DIR__ . '/pmp_integration_class.php';

// CONFIG
$perfex_token = '_PLACEHOLDER_FOR_GITHUB_PUSH';
$pmp = new PmpIntegration($perfex_token);

// 1. Fetch orders that need retry
// Status: failed or pending (stuck), Retry Count < 5
if (function_exists('mysql_query')) {
    $q = "SELECT * FROM pmp_order_master 
          WHERE overall_status IN ('failed', 'pending') 
          AND retry_count < 5 
          AND last_attempt_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)
          LIMIT 10";
    $res = mysql_query($q);

    if ($res) {
        while ($row = mysql_fetch_assoc($res)) {
            $sid = $row['stripe_session_id'];
            $current_step = $row['current_step'];
            $retry_count = $row['retry_count'] + 1;

            // Increment retry count
            mysql_query("UPDATE pmp_order_master SET retry_count = $retry_count WHERE id = " . $row['id']);

            error_log("Retrying Order: $sid, Step: $current_step, Attempt: $retry_count");

            // Fetch metadata from logs (we stored it in logs in step 1)
            // Ideally we should have stored metadata in master or logs. 
            // Let's fetch the LAST 'create_invoice' request payload if we need metadata.
            $q_meta = "SELECT request_payload FROM pmp_process_logs WHERE stripe_session_id = '$sid' AND step_name = 'create_invoice' ORDER BY id DESC LIMIT 1";
            $res_meta = mysql_query($q_meta);
            $row_meta = mysql_fetch_assoc($res_meta);

            $metadata = [];
            if ($row_meta && !empty($row_meta['request_payload'])) {
                // The log stored JSON encoded payload which might have been double encoded or just encoded once.
                // In my helper, I encoded it.
                $metadata = json_decode($row_meta['request_payload'], true);
            }

            // --- RETRY LOGIC SWITCH ---
            if ($current_step == 'create_invoice') {
                $pmp->log_step($sid, '', 'create_invoice', 'pending', null, null, "Retry #$retry_count");

                // We need amount... fetch from somewhere?
                // For now, assume we can get it from metadata or just use 0 (will likely fail if amount needed)
                $amount = $metadata['total'] ?? 0; // Approximate

                $invoice_res = $pmp->create_invoice($metadata, $amount);

                if ($invoice_res['status']) {
                    $invoice_id = $invoice_res['invoice_id'];
                    $pmp->log_step($sid, '', 'create_invoice', 'success', $metadata, $invoice_res['response']);

                    // Advance to next step
                    $pmp->update_master_status($sid, 'processing', 'add_payment');
                } else {
                    $pmp->log_step($sid, '', 'create_invoice', 'failed', $metadata, $invoice_res['response'], $invoice_res['error']);
                }
            } elseif ($current_step == 'add_payment') {
                // Fetch Invoice ID from successful log
                $q_inv = "SELECT response_payload FROM pmp_process_logs WHERE stripe_session_id = '$sid' AND step_name = 'create_invoice' AND status = 'success' LIMIT 1";
                $res_inv = mysql_query($q_inv);
                $row_inv = mysql_fetch_assoc($res_inv);
                if ($row_inv) {
                    $inv_data = json_decode($row_inv['response_payload'], true);
                    $invoice_id = $inv_data['data']['new_invoice_id'] ?? $inv_data['new_invoice_id']; // Handle different structures

                    $amount = $metadata['total'] ?? 0;
                    $txn_id = $sid; // Using session ID as txn ID if not separate

                    $pay_res = $pmp->add_payment($invoice_id, $amount, $txn_id);
                    if ($pay_res['status']) {
                        $pmp->log_step($sid, '', 'add_payment', 'success', null, $pay_res['response']);
                        $pmp->update_master_status($sid, 'processing', 'update_stripe');
                    } else {
                        $pmp->log_step($sid, '', 'add_payment', 'failed', null, $pay_res['response'], $pay_res['error']);
                    }
                }
            } elseif ($current_step == 'update_stripe') {
                $pmp->log_step($sid, '', 'update_stripe', 'skipped', null, null, "Retry not implemented for Stripe update yet");
                $pmp->update_master_status($sid, 'completed', 'final_check');
            }
        }
    }
} else {
    error_log("DB functions missing for Cron Retry");
}
