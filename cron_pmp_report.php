<?php
// Cron Job: Report Failed Orders
// Run Frequency: Daily

require_once __DIR__ . '/pmp_integration_class.php';

// CONFIG
$admin_emails = ['charan.businesslabs@gmail.com', 'support@bdgrowthsuite.com'];

if (function_exists('mysql_query')) {
    // Check for orders failed > 24 hours
    $q = "SELECT * FROM pmp_order_master 
          WHERE overall_status = 'failed' 
          AND last_attempt_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $res = mysql_query($q);

    $failed_orders = [];
    if ($res) {
        while ($row = mysql_fetch_assoc($res)) {
            $failed_orders[] = $row;
        }
    }

    if (!empty($failed_orders)) {
        $subject = "ALERT: " . count($failed_orders) . " Failed PMP Orders Detected";
        $body = "The following orders have failed PMP synchronization and have exhausted retries:\n\n";

        foreach ($failed_orders as $order) {
            $body .= "Stripe Session: " . $order['stripe_session_id'] . "\n";
            $body .= "Stuck Step: " . $order['current_step'] . "\n";
            $body .= "Last Attempt: " . $order['last_attempt_at'] . "\n";
            $body .= "-----------------------------------\n";
        }

        $body .= "\nPlease check the 'pmp_process_logs' table for detailed error messages.";

        // Send Email (Assuming mail() works or use a helper if available)
        foreach ($admin_emails as $email) {
            mail($email, $subject, $body);
        }

        echo "Report sent to admins.";
    } else {
        echo "No stale failed orders found.";
    }
}
