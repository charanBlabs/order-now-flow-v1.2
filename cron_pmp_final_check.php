<?php
// Cron Job: Final Status Check
// Run Frequency: Every 4 Hours

require_once __DIR__ . '/pmp_integration_class.php';

// CONFIG
$perfex_token = '_PLACEHOLDER_FOR_GITHUB_PUSH';
$pmp = new PmpIntegration($perfex_token);

if (function_exists('mysql_query')) {
    // Check 'completed' orders to verify invoice status in PMP is actually PAID
    $q = "SELECT m.*, l.response_payload 
          FROM pmp_order_master m
          JOIN pmp_process_logs l ON m.stripe_session_id = l.stripe_session_id
          WHERE m.overall_status = 'completed' 
          AND l.step_name = 'create_invoice' AND l.status = 'success'
          AND m.last_attempt_at > DATE_SUB(NOW(), INTERVAL 48 HOUR)
          GROUP BY m.stripe_session_id"; // Simple group by to get one row per order

    $res = mysql_query($q);

    if ($res) {
        while ($row = mysql_fetch_assoc($res)) {
            $sid = $row['stripe_session_id'];
            $inv_data = json_decode($row['response_payload'], true);
            $invoice_id = $inv_data['data']['new_invoice_id'] ?? $inv_data['new_invoice_id'];

            if ($invoice_id) {
                $check = $pmp->check_invoice($invoice_id);
                if (isset($check['status']) && $check['status'] != 2) { // Assuming 2 = Paid
                    error_log("WARNING: Order $sid marked completed but PMP Invoice $invoice_id status is " . $check['status']);
                    // Log this anomaly
                    $pmp->log_step($sid, '', 'final_check', 'warning', null, $check, "Invoice status mismatch");
                } else {
                    // All good
                }
            }
        }
    }
}
