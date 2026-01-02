CREATE TABLE IF NOT EXISTS `pmp_process_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `stripe_session_id` VARCHAR(255) NOT NULL,
  `stripe_txn_id` VARCHAR(255),
  `step_name` VARCHAR(50) NOT NULL, -- 'check_customer', 'create_invoice', 'add_payment', 'check_invoice_status', 'update_stripe'
  `status` VARCHAR(20) NOT NULL, -- 'pending', 'success', 'failed', 'skipped'
  `request_payload` TEXT, -- JSON payload sent to PMP
  `response_payload` TEXT, -- JSON response from PMP
  `error_message` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`stripe_session_id`),
  INDEX (`status`)
);

CREATE TABLE IF NOT EXISTS `pmp_order_master` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `stripe_session_id` VARCHAR(255) UNIQUE NOT NULL,
  `customer_email` VARCHAR(255),
  `overall_status` VARCHAR(20) DEFAULT 'pending', -- 'pending', 'processing', 'completed', 'failed'
  `current_step` VARCHAR(50) DEFAULT 'check_customer',
  `retry_count` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `last_attempt_at` DATETIME
);
