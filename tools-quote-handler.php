<?php
header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit;
}

// Escape helper for mysql_* compatibility
function esc($value)
{
    return mysql_real_escape_string(trim($value));
}

// --- Collect ALL potential POST data ---

// Original Order/Payment fields
$tool_id            = esc($_POST['postid'] ?? $_POST['tool_id'] ?? '');
$tool_name          = esc($_POST['tool_name'] ?? '');
$post_name          = esc($_POST['post_name'] ?? '');
$user_phone         = esc($_POST['user_phone'] ?? '');
$user_email         = esc($_POST['user_email'] ?? '');
$user_name          = esc($_POST['user_name'] ?? '');
$post_url           = esc($_POST['post_url']);
$origin_url         = esc($_POST['origin_url']);

$payment_unit_raw   = $_POST['payment_unit'] ?? '';
$p_unit_check       = strtolower($payment_unit_raw);

if (strpos($p_unit_check, 'quote') !== false) {
    $payment_unit = 'ask_for_quote';
} elseif (strpos($p_unit_check, 'free') !== false) {
    $payment_unit = 'free';
} elseif (strpos($p_unit_check, 'starts') !== false) {
    $payment_unit = 'starts_from'; // Catches "Starts From", "Starts_From", "Starts From (Deposit)"
} elseif (strpos($p_unit_check, 'subscri') !== false) {
    $payment_unit = 'subscription';
} elseif (strpos($p_unit_check, 'fixed') !== false) {
    $payment_unit = 'fixed';
} else {
    $payment_unit = 'unknown'; // Will trigger default fallback
}
 // Handle "starts from" -> "starts_from"

$requirement        = esc($_POST['requirements'] ?? ''); // Used by 'free'
$amount_paid        = esc($_POST['amount_paid'] ?? 0);
$session_id         = esc($_POST['session_id'] ?? '');
$transaction_id     = esc($_POST['transaction_id'] ?? '');
$subscription_id    = esc($_POST['subscription_id'] ?? '');
$subscription_status = esc($_POST['subscription_status'] ?? '');
$renewal_interval   = esc($_POST['renewal_interval'] ?? '');
$next_payment_date  = esc($_POST['next_payment_date'] ?? '');
$payment_status     = esc($_POST['payment_status'] ?? '');
$startsfrom_type    = esc($_POST['starts_from_type'] ?? ''); // 'commitment' or 'full'
$starts_from        = esc($_POST['starts_from'] ?? '');
$specifications     = esc($_POST['specifications'] ?? 'Not Provided');
$actual_price       = esc($_POST['actual_price'] ?? 0); // New Variable Captured Here


// --- Financials (Fix for Order Summary) ---
// UPDATED LOGIC: Prioritize Session Data -> Fallback to Invoice API -> Fallback to Amount Paid

// 1. Default to Amount Paid (Safety Net)
$total    = $amount_paid;
$subtotal = $amount_paid;
$discount = 0;

// 2. Try to get details from Session Data JSON (Stripe Object)
// This expects the full Stripe Session object to be passed in a POST field named 'session_data'
$session_data_raw = $_POST['session_data'] ?? ''; 
$session_parsed   = json_decode($session_data_raw, true);

if (is_array($session_parsed) && isset($session_parsed['amount_total'])) {
    // Stripe sends amounts in cents (e.g., 1000 = 10.00). We divide by 100.
    $total    = floatval($session_parsed['amount_total']) / 100;
    $subtotal = floatval($session_parsed['amount_subtotal'] ?? $session_parsed['amount_total']) / 100;
    $discount = floatval($session_parsed['total_details']['amount_discount'] ?? 0) / 100;
}
// 3. If Session Data is missing, check if Invoice API sent values (Legacy/Fallback)
elseif (isset($_POST['total']) && $_POST['total'] != '' && $_POST['total'] != 0) {
     $total       = esc($_POST['total']);
     $subtotal    = esc($_POST['subtotal'] ?? $_POST['total']);
     $discount    = esc($_POST['discount'] ?? $_POST['discount_total'] ?? 0);
}

// --- NEW: GENERATE STRIPE ADMIN DATA HTML ---
// This creates a technical summary of the transaction for the admin email
$stripe_admin_html = "";
if (!empty($session_parsed) || !empty($transaction_id) || !empty($session_id)) {
    // Extract data from Parsed JSON or fallback to POST variables
    $s_id_disp = $session_parsed['id'] ?? $session_id ?? 'N/A';
    $t_id_disp = $session_parsed['payment_intent'] ?? $transaction_id ?? 'N/A';
    $sub_id_disp = $session_parsed['subscription'] ?? $subscription_id ?? 'N/A';
    $status_disp = $session_parsed['payment_status'] ?? $payment_status ?? 'N/A';
    $cust_email_disp = $session_parsed['customer_details']['email'] ?? $user_email;
    $currency_disp = isset($session_parsed['currency']) ? strtoupper($session_parsed['currency']) : 'USD';
    
    $stripe_admin_html .= "<div style='background-color: #f3f4f6; border: 1px solid #d1d5db; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 12px; color: #1f2937; margin-top: 20px;'>";
    $stripe_admin_html .= "<h4 style='margin-top: 0; margin-bottom: 10px; border-bottom: 1px solid #d1d5db; padding-bottom: 5px; color: #111827;'>Stripe Transaction Data</h4>";
    $stripe_admin_html .= "<p style='margin: 3px 0;'><strong>Session ID:</strong> " . $s_id_disp . "</p>";
    $stripe_admin_html .= "<p style='margin: 3px 0;'><strong>Transaction / PI:</strong> " . $t_id_disp . "</p>";
    
    if ($sub_id_disp !== 'N/A' && !empty($sub_id_disp)) {
        $stripe_admin_html .= "<p style='margin: 3px 0;'><strong>Subscription ID:</strong> " . $sub_id_disp . "</p>";
    }
    
    $stripe_admin_html .= "<p style='margin: 3px 0;'><strong>Amount Total:</strong> " . number_format($total, 2) . " " . $currency_disp . "</p>";
    $stripe_admin_html .= "<p style='margin: 3px 0;'><strong>Payment Status:</strong> " . $status_disp . "</p>";
    $stripe_admin_html .= "<p style='margin: 3px 0;'><strong>Customer Email:</strong> " . $cust_email_disp . "</p>";

    // Add Direct Link to Stripe Dashboard if PI exists
    if ($t_id_disp !== 'N/A' && strpos($t_id_disp, 'pi_') === 0) {
        $stripe_admin_html .= "<p style='margin-top: 10px;'><a href='https://dashboard.stripe.com/payments/" . $t_id_disp . "' target='_blank' style='color: #4f46e5; text-decoration: underline;'>View in Stripe Dashboard</a></p>";
    } elseif ($sub_id_disp !== 'N/A' && strpos($sub_id_disp, 'sub_') === 0) {
          $stripe_admin_html .= "<p style='margin-top: 10px;'><a href='https://dashboard.stripe.com/subscriptions/" . $sub_id_disp . "' target='_blank' style='color: #4f46e5; text-decoration: underline;'>View in Stripe Dashboard</a></p>";
    }
    
    $stripe_admin_html .= "</div>";
} else {
    $stripe_admin_html = "<p style='color: #6b7280; font-size: 12px; font-style: italic;'>No direct Stripe session data captured.</p>";
}
// ---------------------------------------------

// Invoice Details
$invoice_id1        = esc($_POST['invoice_id'] ?? $_POST['invoice_number'] ?? 'test123');
$invoice_hash1      = esc($_POST['invoice_hash'] ?? 'hash-test');
$invoice_id         = 'test123' . $invoice_id1;
$invoice_hash       = 'hash-test' . $invoice_hash1;

// Quote / Lead fields
$fullname           = esc($_POST['lead_name'] ?? '');
$leademail          = esc($_POST['lead_email'] ?? '');
$leadnotes          = esc($_POST['lead_notes'] ?? '');
$spec_link     = esc($_POST['specifications'] ?? '');
$file_list = '';

if (!empty($_FILES['files']['name'])) {
    // Get all file names from the upload
    $file_names = $_FILES['files']['name'];

    // Convert array of names into comma-separated string
    $file_list = implode(',', $file_names);
}

// --- NEW SPEC FIELDS (From DB Variables) ---
$impl_var     = esc($_POST['implementation_type'] ?? 'readytoimplement');
$delivery_var = esc($_POST['delivery_time_description'] ?? '1_week');
$warranty_var = esc($_POST['warranty_time_description'] ?? '1_year');


// --- MAPPING LOGIC: DB Variables -> Readable Text (Case Insensitive Fix) ---

// 1. Implementation Type
switch (strtolower($impl_var)) {
    case 'ready-to-implement': 
    case 'readytoimplement': 
        $impl_label = "Ready-to-Implement"; break;
    case 'quick':              
        $impl_label = "Quick"; break;
    case 'semi-custom':      
    case 'semi_custom':      
        $impl_label = "Semi-Custom"; break;
    case 'fully-custom':     
    case 'fully_custom':     
        $impl_label = "Fully-Custom"; break;
    default:                 
        $impl_label = "Standard"; break; // Fallback
}

// 2. Delivery Time
switch (strtolower($delivery_var)) {
    case '< 3 days':   
    case '3_days':     
        $delivery_label = "< 3 Days"; break;
    case '< 1 week':   
    case '1_week':     
        $delivery_label = "< 1 Week"; break;
    case '1-2 weeks': 
    case '12_weeks':   
        $delivery_label = "1-2 Weeks"; break;
    case '< 1 month':  
    case '1_month':    
        $delivery_label = "< 1 Month"; break;
    default:            
        $delivery_label = "TBD"; break;
}

// 3. Warranty
switch (strtolower($warranty_var)) {
    case 'none':                     
        $warranty_label = "None"; break;
    case '30 days':                  
    case '30_days':                  
        $warranty_label = "30 Days"; break;
    case '1 year':                   
    case '1_year':                   
        $warranty_label = "1 Year"; break;
    case 'until active subscription':
    case 'until_active_subscription':
        $warranty_label = "Active Sub."; break;
    default:                          
        $warranty_label = "Standard"; break;
}


// --- TIMELINE LOGIC ---
if (strtolower($impl_var) === 'ready-to-implement' || strtolower($impl_var) === 'readytoimplement' || strtolower($impl_var) === 'quick') {
    // Fast Track
    $contact_timeline = "1-2 business days (usually under 24hours)";
    $completion_timeline = "2 business days";
} elseif (strtolower($impl_var) === 'semi-custom' || strtolower($impl_var) === 'semi_custom') {
    // Medium Track
    $contact_timeline = "1-2 business days (usually under 24hours)";
    $completion_timeline = "3-5 business days (usually under 24hours)";
} else {
    // Fully Custom / Complex
    $contact_timeline = "1-2 business days";
    $completion_timeline = "defined in your proposal";
}

// --- UPDATED FUNCTION: Now accepts $actual_price ---
function generateOrderTable($tool_name, $amount_paid, $impl_label, $del_label, $war_label, $subtotal, $discount, $total, $payment_unit, $startsfrom_type, $actual_price = 0) {

    // ---------------------------------------------------------
    // 1. IDENTIFY PRICING TYPE
    // ---------------------------------------------------------
      
    // Type 1: Starts From (Commitment Fee)
    $is_commitment = ($payment_unit === 'starts_from' && ($startsfrom_type === 'commitment' || $startsfrom_type === 'deposit'));

    // Type 2: Starts From (Full Pay / Base Price)
    // Updated to catch various labels for paying the full base price
    $is_starts_full = ($payment_unit === 'starts_from' && (
        $startsfrom_type === 'full' || 
        $startsfrom_type === 'full_pay' || 
        $startsfrom_type === 'base' || 
        $startsfrom_type === 'basic' || 
        $startsfrom_type === 'full_charge'
    ));

    // Type 4: Subscription
    $is_subscription = ($payment_unit === 'subscription');

    // Type 3: Fixed Price (Default fallback)
    $is_fixed = (!$is_commitment && !$is_starts_full && !$is_subscription);


    // ---------------------------------------------------------
    // 2. FORMAT NUMBERS
    // ---------------------------------------------------------
    $f_amount_paid  = number_format((float)$amount_paid, 2);
    $f_subtotal     = number_format((float)$subtotal, 2) ?? $amount_paid; // Used for Base Price fallback
    $f_discount     = number_format((float)$discount, 2);
    $f_total        = number_format((float)$total, 2) ?? $amount_paid;
    
    // NEW: Format Actual Price
    // If actual price is > 0 use it, otherwise fall back to subtotal to avoid showing 0.00
    $f_actual_price = ($actual_price > 0) ? number_format((float)$actual_price, 2) : $f_subtotal;


    // ---------------------------------------------------------
    // 3. GENERATE HTML BASED ON TYPE
    // ---------------------------------------------------------

    // Common styling
    $borderColor = "#e5e7eb"; // Light grey border
    $paddingCSS = "padding: 6px 0;"; // Compact padding

    // === CASE 4: SUBSCRIPTION ===
    if ($is_subscription) {
        $billing_period = (stripos($tool_name, 'year') !== false || stripos($tool_name, 'annual') !== false) ? 'Annually' : 'Monthly';
        
        return "
        <hr style='border: 0; border-top: 1px solid {$borderColor}; margin: 20px 0;'>
        <div style='margin-top: 15px; margin-bottom: 25px; font-family: Calibri, sans-serif; color: #000;'>
            <h3 style='color: #000; margin-bottom: 10px; font-size: 16px;'>Subscription Summary</h3>
            
            <table cellpadding='0' cellspacing='0' width='100%' style='border-collapse: collapse; font-size: 14px; font-family: Calibri, sans-serif;'>
                <thead>
                    <tr style='border-bottom: 1px solid {$borderColor};'>
                        <th align='left' style='padding-bottom: 8px; color: #000;'>Description</th>
                        <th align='right' style='padding-bottom: 8px; color: #000;'>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style='border-bottom: 1px solid {$borderColor};'>
                        <td align='left' style='{$paddingCSS}'><strong>Subscription Plan</strong><br><span style='color: #000; font-size: 12px;'>$tool_name</span></td>
                        <td align='right' style='{$paddingCSS} font-weight: bold;'>" . $f_total . "</td>
                    </tr>
                    <tr style='border-bottom: 1px solid {$borderColor};'>
                        <td align='left' style='{$paddingCSS}'>Billing Period</td>
                        <td align='right' style='{$paddingCSS}'>" . $billing_period . "</td>
                    </tr>
                    <tr>
                        <td align='left' style='{$paddingCSS}'>Status</td>
                        <td align='right' style='{$paddingCSS} color: #16a34a; font-weight: bold;'>Active</td>
                    </tr>
                </tbody>
            </table>
        </div>";
    }

    // === CASE 3: FIXED PRICE ===
    if ($is_fixed) {
        return "
        <hr style='border: 0; border-top: 1px solid {$borderColor}; margin: 20px 0;'>
        <div style='margin-top: 15px; margin-bottom: 25px; font-family: Calibri, sans-serif; color: #000;'>
            <h3 style='color: #000; margin-bottom: 10px; font-size: 16px;'>Order Summary</h3>
            
            <table cellpadding='0' cellspacing='0' width='100%' style='border-collapse: collapse; font-size: 14px; font-family: Calibri, sans-serif;'>
                <thead>
                    <tr style='border-bottom: 1px solid {$borderColor};'>
                        <th align='left' style='padding-bottom: 8px; color: #000;'>Description</th>
                        <th align='right' style='padding-bottom: 8px; color: #000;'>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style='border-bottom: 1px solid {$borderColor};'>
                        <td align='left' style='{$paddingCSS}'>Fixed Price</td>
                        <td align='right' style='{$paddingCSS} font-weight: bold;'>" . $f_total . "</td>
                    </tr>
                    <tr>
                        <td align='left' style='{$paddingCSS}'>Total Paid</td>
                        <td align='right' style='{$paddingCSS} font-weight: bold;'>" . $f_total . "</td>
                    </tr>
                </tbody>
            </table>

            <div style='margin-top: 20px; font-size: 14px; color: #000; line-height: 1.5;'>
                <p style='font-size: 12pt;font-family: Calibri, sans-serif;margin: 0; font-weight: bold;'>Note:</p>
                <p style='font-size: 12pt;font-family: Calibri, sans-serif;margin: 0;'><strong>Fixed price</strong> covers everything shown as included on the landing page.</p>
                <p style='font-size: 12pt;font-family: Calibri, sans-serif;margin: 0; font-style: italic;'>Any extra features, new ideas, or additional custom work will be billed separately.</p>
            </div>
        </div>
        <hr style='border: 0; border-top: 1px solid {$borderColor}; margin: 20px 0;'>";
    }

    // === CASE 2: STARTS FROM - FULL PAY / BASE PRICE ===
    if ($is_starts_full) {
        return "
        <hr style='border: 0; border-top: 1px solid {$borderColor}; margin: 20px 0;'>
        <div style='margin-top: 15px; margin-bottom: 25px; font-family: Calibri, sans-serif; color: #000;'>
            <h3 style='color: #000; margin-bottom: 10px; font-size: 16px;'>Order Summary</h3>
            
            <table cellpadding='0' cellspacing='0' width='100%' style='border-collapse: collapse; font-size: 14px;font-family: Calibri, sans-serif;'>
                <thead>
                    <tr style='border-bottom: 1px solid {$borderColor};'>
                        <th align='left' style='padding-bottom: 8px; color: #000;'>Description</th>
                        <th align='right' style='padding-bottom: 8px; color: #000;'>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style='border-bottom: 1px solid {$borderColor};'>
                        <td align='left' style='{$paddingCSS}'>Base Price</td>
                        <td align='right' style='{$paddingCSS} font-weight: bold;'>" . $f_total . "</td>
                    </tr>
                    <tr>
                        <td align='left' style='{$paddingCSS}'>Total Paid</td>
                        <td align='right' style='{$paddingCSS} font-weight: bold;'>" . $f_total . "</td>
                    </tr>
                </tbody>
            </table>

            <div style='margin-top: 20px; font-size: 14px; color: #000; line-height: 1.5;'>
                <p style='font-size: 12pt;font-family: Calibri, sans-serif; margin: 0; font-weight: 700;'>Note:</p>
                <p style='font-size: 12pt;font-family: Calibri, sans-serif; margin: 0;'>This payment covers everything listed as “included” on the landing page.</p>
                <p style='font-size: 12pt;font-family: Calibri, sans-serif; margin: 0;'>If your final requirements match the standard scope (as they do in most cases), <strong>no additional payment will be required.</strong></p>
                <p style='font-size: 12pt;font-family: Calibri, sans-serif; margin: 0;'>If any customization beyond the listed scope is needed, we will quote only the difference.</p>
            </div>
        </div>";
    }

    // === CASE 1: STARTS FROM - COMMITMENT FEE (New Specific Layout) ===
    // Matches the layout from the latest screenshot
    if ($is_commitment) {
        return "
        <hr style='border: 0; border-top: 1px solid {$borderColor}; margin: 20px 0;'>
        <div style='margin-top: 15px; margin-bottom: 25px; font-family: Calibri, sans-serif; color: #000;'>
            <h3 style='color: #000; margin-bottom: 10px; font-size: 16px;'>Order Summary</h3>
            
            <table cellpadding='0' cellspacing='0' width='100%' style='border-collapse: collapse; font-size: 14px;font-family: Calibri, sans-serif;'>
                <thead>
                    <tr style='border-bottom: 1px solid {$borderColor};'>
                        <th align='left' style='padding-bottom: 8px; color: #000;'>Description</th>
                        <th align='right' style='padding-bottom: 8px; color: #000;'>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style='border-bottom: 1px solid {$borderColor};'>
                        <td align='left' style='{$paddingCSS}'>Base Price</td>
                        <td align='right' style='{$paddingCSS} font-weight: bold;'>" . $f_actual_price . "</td>
                    </tr>
                    <tr style='border-bottom: 1px solid {$borderColor};'>
                        <td align='left' style='{$paddingCSS}'>Commitment Fee</td>
                        <td align='right' style='{$paddingCSS} font-weight: bold;'>" . $f_total . "</td>
                    </tr>
                    <tr>
                        <td align='left' style='{$paddingCSS}'>Total Paid</td>
                        <td align='right' style='{$paddingCSS} font-weight: bold;'>" . $f_total . "</td>
                    </tr>
                </tbody>
            </table>

            <div style='margin-top: 20px; font-size: 14px; color: #000; line-height: 1.5;'>
                <p style='font-size:12pt;font-family: Calibri, sans-serif;margin: 0; font-weight: bold;'>Note:</p>
                <p style='font-size: 12pt;font-family: Calibri, sans-serif;margin: 0;'>If you move forward, the <em>full amount is credited to your final invoice</em> — you only pay the difference (if any).</p>
                <p style='font-size:12pt;font-family: Calibri, sans-serif;margin: 0;'>If not, the clarity and ideas shared during the session are yours to keep.</p>
            </div>
        </div>";
    }
      
    // Fallback return (should technically be unreachable given the logic above, but good practice)
    return "";
}

// --- HELPER: Generate Invoice Link HTML ---
function getInvoiceHtml($id, $hash) {
    if (!empty($id) && !empty($hash)) {
        $link = "https://pmp.businesslabs.org/invoice/{$id}/{$hash}";
        return "<p style='margin-top: 15px;'><strong>Invoice:</strong> <a href='{$link}' target='_blank' style='color: #0056b3; text-decoration: underline;'>View & download invoice</a></p>";
    }
    return "";
}

// --- NEW: Handle 'ask_for_quote' logic first ---
if ($payment_unit == 'ask_for_quote') {

    $token = bin2hex(random_bytes(16));
    $final_file_name = ""; 

    $user_phone_quote = $_POST['input-phone-number'];

    // FTP server credentials
    $ftp_host = brilliantDirectories::getDatabaseConfiguration('ftp_server'); 
    $ftp_user = brilliantDirectories::getDatabaseConfiguration('website_user'); 
    $ftp_pass =  brilliantDirectories::getDatabaseConfiguration('website_pass'); 
    $ftp_dir = "/public_html/images/";      

    // Establish an FTP connection
    $conn_id = ftp_connect($ftp_host);

    if ($conn_id) {
        if (ftp_login($conn_id, $ftp_user, $ftp_pass)) {
            ftp_pasv($conn_id, true);
            if (isset($_FILES['files']) && $_FILES['files']['error'] === UPLOAD_ERR_OK) {
                $tmp_file = $_FILES['files']['tmp_name']; 
                $file_name = $_FILES['files']['name'];
                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $unique_id = mt_rand(10000000, 99999999); 
                $file_base_name = pathinfo($file_name, PATHINFO_FILENAME); 
                $safe_file_name = preg_replace("/[^a-zA-Z0-9-_]/", "-", $file_base_name); 
                $final_file_name = $safe_file_name . "-" . $unique_id . "." . $file_extension; 
                $ftp_target_file = $ftp_dir . basename($final_file_name);
                ftp_put($conn_id, $ftp_target_file, $tmp_file, FTP_BINARY);
            }
            ftp_close($conn_id);
        }
    }

    // ✅ Create orders table if not exists
    mysql_query("
    CREATE TABLE IF NOT EXISTS bdgs_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tool_id VARCHAR(100),
        tool_name VARCHAR(255),
        email VARCHAR(255),
        customer_name VARCHAR(255),
        amount DECIMAL(10,2),
        payment_unit VARCHAR(50),
        stripe_session VARCHAR(255),
        stripe_txn_id VARCHAR(255),
        stripe_subscription VARCHAR(255),
        subscription_status VARCHAR(50),
        renewal_interval VARCHAR(50),
        next_payment_date DATETIME NULL,
        payment_status VARCHAR(50),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ");

    // ✅ Insert into DB 
    $insertquery = "
    INSERT INTO bdgs_orders
    (tool_id, tool_name, specifications, files, email, customer_name, amount, payment_unit, stripe_session, stripe_txn_id, stripe_subscription, subscription_status, renewal_interval, next_payment_date, payment_status)
    VALUES
    ('$tool_id', '$tool_name', '$specifications', '$final_file_name', '$user_email', '$user_name', '$amount_paid', '$payment_unit', '$session_id', '$transaction_id', '$subscription_id', '$subscription_status', '$renewal_interval',
    " . ($next_payment_date ? "'$next_payment_date'" : "NULL") . ", '$payment_status')
    ";
      
    $queryresult = mysql_query($insertquery);
    if($queryresult) {
        $neworderid = mysql_insert_id();
    }

    // --- EMAIL CONTENT FOR QUOTE (#5. Ask For Quote) ---
    $quote_content = "
    <hr style='border: 0; border-top: 1px solid #e5e7eb; margin: 20px 0;'>

    <h3 style='font-size: 14pt;margin: 0 0 10px 0; color: #000; font-family: Calibri, sans-serif;'>Details You Submitted</h3>
      
    <p style='font-size: 12pt;margin: 5px 0; color: #000; font-family: Calibri, sans-serif;'><strong>Name:</strong> {$user_name}</p>
    <p style='font-size: 12pt;margin: 5px 0; color: #000; font-family: Calibri, sans-serif;'><strong>Email:</strong> {$user_email}</p>
    <p style='font-size: 12pt;margin: 5px 0; color: #000; font-family: Calibri, sans-serif;'><strong>Phone:</strong> {$user_phone_quote}</p>
      
    <div style='margin-top: 15px;'>
        <p style='font-size: 12pt;margin: 5px 0; color: #000; font-family: Calibri, sans-serif;'><strong>Requirement Summary:</strong></p>
        <div style='color: #555; font-family: Calibri, sans-serif; line-height: 1.5;'>
            " . nl2br($leadnotes) . "
        </div>
    </div>

    <div style='margin-top: 15px;'>
        <p style='font-size: 12pt;margin: 5px 0; color: #000; font-family: Calibri, sans-serif;'><strong>Specification Link:</strong></p>
        <div style='color: #555; font-family: Calibri, sans-serif;'>
            " . ($spec_link ? $spec_link : 'Not provided') . "
        </div>
    </div>

    <div style='margin-top: 15px;'>
        <p style='font-size: 12pt;margin: 5px 0; color: #000; font-family: Calibri, sans-serif;'><strong>Attached Files:</strong></p>
        <div style='color: #555; font-family: Calibri, sans-serif;'>
            " . ($file_list ? $file_list : 'No files uploaded') . "
        </div>
    </div>

    <hr style='border: 0; border-top: 1px solid #e5e7eb; margin: 25px 0;'>

    <h3 style='font-size: 14pt;margin: 0 0 15px 0; color: #000; font-family: Calibri, sans-serif;'>What Happens Next</h3>
      
    <ol style='color: #000; font-family: Calibri, sans-serif; padding-left: 20px; line-height: 1.6; margin-top: 0;''>
        <li style='font-size: 12pt; padding-left: 5px; font-weight: bold;''>Discovery Session</li>
        <span style='display: inline-block; font-family: Calibri, sans-serif; font-size: 12pt; color: #000; padding-left: 20px;'>
            Please book a quick discovery session so we can go over your requirements and clarify anything needed:
        </span>
        <a href='https://book.businesslabs.org/discovery-session' 
           style='margin-bottom: 5px; font-family: Calibri, sans-serif; padding-left: 20px; color: #0056b3; display: inline-block;'>
           https://book.businesslabs.org/discovery-session
        </a>

        <li style='font-size: 12pt; padding-left: 5px; font-weight: bold;'>Quote Preparation</li>
        <span style='display: inline-block; margin-bottom: 5px; font-family: Calibri, sans-serif; font-size: 12pt; color: #000; padding-left: 20px;'>
            After our discussion, we will prepare a clear and itemized quote based on your needs. Anything beyond the standard scope will be included as optional line items.
        </span>

        <li style='font-size: 12pt; padding-left: 5px; font-weight: bold;''>Begin Work on Approval</li>
        <span style='display: inline-block; margin-bottom: 5px; font-family: Calibri, sans-serif; font-size: 12pt; color: #000; padding-left: 20px;'>
            Once you approve the quote, we’ll begin the project immediately.
        </span>
    </ol>

      
    <hr style='border: 0; border-top: 1px solid #e5e7eb; margin: 20px 0;'>";

    $w['full_name']       = $user_name ?? '';
    $w['user_email']      = $user_email ?? '';
    $w['phone_number']    = $user_phone_quote;
    $w['service_name']    = $tool_name ?? '';
    $w['tool_short_name'] = $tool_name ?? '';
    $w['order_id']        = $neworderid ?? '';
    $w['file_name']       = "https://bdgrowthsuite.com/images/" . $final_file_name;
    $w['order_summary_html'] = $quote_content;
    $w['email_preview_text'] = 'We’ve received your request for ' . $tool_name . ' — let’s discuss your requirements.';
    $w['email_post_url'] = $post_url;
    
    // ✅ PASS STRIPE DATA TO QUOTE EMAIL
    $w['stripe_transaction_data'] = $stripe_admin_html;

    if (function_exists('prepareEmail') && function_exists('sendEmailTemplate')) {
        $email_2 = prepareEmail('ask_for_quote_tools', $w);
        if ($email_2 && $w['user_email']) {
            sendEmailTemplate($w['website_email'], $w['user_email'], $email_2['subject'], $email_2['html'], $email_2['text'], $email_2['priority'], $w);
        }
        
        $email_3 = prepareEmail('quick-services-admin-tools', $w);
        if ($email_3) {
            sendEmailTemplate($w['website_email'], $w['website_email'], $email_3['subject'], $email_3['html'], $email_3['text'], $email_3['priority'], $w);
        }

    }
      
    echo json_encode(["success" => true, "status" => "success", "message" => "Quote request submitted successfully."]);
    exit;
}
// --- END 'ask_for_quote' ---


// --- START PAID/FREE LOGIC ---

if ($tool_name == "" || $user_email == "") {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}

mysql_query("
CREATE TABLE IF NOT EXISTS bdgs_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tool_id VARCHAR(100),
    tool_name VARCHAR(255),
    email VARCHAR(255),
    customer_name VARCHAR(255),
    amount DECIMAL(10,2),
    payment_unit VARCHAR(50),
    stripe_session VARCHAR(255),
    stripe_txn_id VARCHAR(255),
    stripe_subscription VARCHAR(255),
    subscription_status VARCHAR(50),
    renewal_interval VARCHAR(50),
    next_payment_date DATETIME NULL,
    payment_status VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$insertquery = "
INSERT INTO bdgs_orders
(tool_id, tool_name, email, customer_name, amount, payment_unit, stripe_session, stripe_txn_id, stripe_subscription, subscription_status, renewal_interval, next_payment_date, payment_status)
VALUES
('$tool_id', '$tool_name', '$user_email', '$user_name', '$amount_paid', '$payment_unit', '$session_id', '$transaction_id', '$subscription_id', '$subscription_status', '$renewal_interval',
" . ($next_payment_date ? "'$next_payment_date'" : "NULL") . ", '$payment_status')
";

$queryresult = mysql_query($insertquery);
if($queryresult) {
    $neworderid = mysql_insert_id();
}

// ✅ PREPARE EMAIL CONTENT
$email_body_content = "";
$include_table = false;
$whatsappLink = 'https://web.whatsapp.com/send?phone=917799285123';
$invoice_html = getInvoiceHtml($invoice_id, $invoice_hash);
$discoveryMeetingLink = '<a href="https://book.businesslabs.org/discovery-session">https://book.businesslabs.org/discovery-session</a>';
// Common styles
// Common styles
$hr_style = "border: 0; border-top: 1px solid #e5e7eb; margin: 25px 0;";
$h3_style = "margin: 0; color: #000; font-family: Calibri,sans-serif; font-size: 18px;";
$p_style  = "font-size: 12pt;color: #000; font-family: Calibri,sans-serif;";
$green_box_style = "background-color: #f0fdf4; padding: 15px; border-left: 4px solid #22c55e; margin: 20px 0; color: #14532d;";
$orange_box_style = "background-color: #fff7ed; padding: 15px; border-left: 4px solid #f97316; margin: 20px 0; color: #7c2d12;";

switch ($payment_unit) {

    case 'free':
        // #2. Free - Updated based on screenshot
        $email_body_content = "
        <hr style='{$hr_style}'>
        <h3 style='{$h3_style}'>Next Steps</h3>
        
        <ol style='color: #000; font-family: Calibri, sans-serif; padding-left: 20px; margin-top: 15px;'>
            <li style='font-family: Calibri, sans-serif;font-size: 12pt; padding-left: 5px;font-weight: bold;'>
                Provide Admin Access
            </li>
            <span style='display: inline-block;font-family: Calibri, sans-serif;font-size: 12pt;color: #000;padding-left:20px;'>To set this up for you, please grant us <strong>Admin access</strong> to your BD website.<br>
                Add these accounts as Admin:</span><br>
            <a href='mailto:dev1@businesslabs.org' style='font-family: Calibri, sans-serif;padding-left:20px;text-decoration:none; color:#0056b3; display:inline-block; margin-top:5px;'>dev1@businesslabs.org</a><br>
            <a href='mailto:dev2@businesslabs.org' style='font-family: Calibri, sans-serif;padding-left:20px;text-decoration:none; color:#0056b3; display:inline-block; margin-bottom:5px;'>dev2@businesslabs.org</a>
            <li style='font-family: Calibri, sans-serif;font-size: 12pt;padding-left: 5px;font-weight: bold;'>
                We Begin Implementation
            </li>
            <span style='display: inline-block;font-family: Calibri, sans-serif;font-size: 12pt;padding-left:20px;color: #000;'>Once access is received, we will implement everything that is marked as <strong>included</strong> for this free item.</span>
        </ol>

        <p style='font-family: Calibri, sans-serif;color: #000;font-size: 12pt;'>If you need <em>extra features</em>, <em>new ideas</em>, or <em>additional custom work</em>, we can provide a separate quote.</p>
        <hr style='{$hr_style}'>";
        
        $include_table = false;
        break;

    case 'fixed':
    case 'fixed_price':
    case 'subscription':
        // Determine if it is a #1. Plugin (Ready-to-Implement) or #6. Pure Service (Custom/Quick)
        // Default assumption: 'readytoimplement' implies Plugin Logic. Others imply Service logic.
        
        $is_pure_service = (strtolower($impl_var) !== 'ready-to-implement' && strtolower($impl_var) !== 'readytoimplement');

        // SCENARIO 1: ACTUAL SUBSCRIPTION
        if ($payment_unit === 'subscription') {
            $email_body_content = "
            <hr style='{$hr_style}'>
            <h3 style='{$h3_style}'>Next Steps</h3>
            
            <ol style='color: #000; font-family: Calibri, sans-serif; padding-left: 20px; line-height: 1.6; margin-top: 15px;'>
                <li style='padding-left: 5px; font-size: 12pt; font-family: Calibri, sans-serif; font-weight: 700;'>Provide Admin Access</li>
                <span style='line-height: 1.38; display: inline-block; font-family: Calibri, sans-serif; font-size: 12pt; color: #000;padding-left:20px;'>To help you make the most of your subscription, please grant us <strong>Admin access</strong> to your BD website.<br> Add these accounts as Admin:</span><br>
                <a href='mailto:dev1@businesslabs.org' style='font-family: Calibri, sans-serif;padding-left:20px;text-decoration:none; color:#0056b3; display:inline-block; margin-top:5px;'>&ndash; dev1@businesslabs.org</a><br>
                <a href='mailto:dev2@businesslabs.org' style='font-family: Calibri, sans-serif;padding-left:20px;text-decoration:none; color:#0056b3; display:inline-block; margin-top:5px;'>&ndash; dev2@businesslabs.org</a>
                <li style='padding-left: 5px; font-size: 12pt; font-family: Calibri, sans-serif; font-weight: 700;'>We Begin Implementation</li>
                <span style='line-height: 1.38;display: inline-block;font-family: Calibri, sans-serif;font-size: 12pt;color: #000;padding-left:20px;'>Once access is granted, our team will start supporting your setup and ensuring the subscribed features are properly implemented.</span>
            </ol>

            <h3 style='{$h3_style}; margin-top: 10px;'>Updates & Improvements:</h3>
            <p style='{$p_style}; margin: 0; padding: 0; margin-top: 5px;'><span>Enhancements from our internal Ideas List are released periodically based on feasibility, demand, and priority.</span></p>
            <p style='{$p_style}; margin: 0; padding: 0;margin-bottom: 5px;'><span>If you ever need something very specific or urgent, we can provide a custom-built solution and share a separate quote.</span></p>
            {$invoice_html}
            <hr style='{$hr_style}'>";
             $include_table = true; 
        } 
        // SCENARIO 2: FIXED PRICE (Ready-to-Implement / Plugin)
        // Uses similar layout to Subscription, but changes wording to "Purchase" and removes "Updates" section
        elseif (!$is_pure_service) {
             $email_body_content = "
            <hr style='{$hr_style}'>
            <h3 style='{$h3_style}'>Next Steps</h3>
            
            <ol style='color: #000; font-family: Calibri, sans-serif; padding-left: 20px; line-height: 1.6; margin-top: 5px;'>
                <li style='font-size: 12pt; padding-left: 5px;font-weight:700;'>Extras or No Extras</li>
                <span style='line-height: 1.38;display: inline-block;font-family: Calibri, sans-serif;font-size: 12pt;color: #000;padding-left:20px;'>If you need <em>extra features, new ideas, or additional custom work</em>, reply to this email or book the meeting.</span>
                <span style='line-height: 1.38;display: inline-block;font-family: Calibri, sans-serif;font-size: 12pt;color: #000;padding-left:20px;'>If not, we will proceed with everything listed as included on the landing page.</span>
                <li style='font-size: 12pt; padding-left: 5px;font-weight:700;'>Discussion & Confirmation (If Needed)</li>
                <span style='line-height: 1.38;display: inline-block;font-family: Calibri, sans-serif;font-size: 12pt;color: #000;padding-left:20px;'>If a meeting is booked, we'll review details, confirm the timeline, and let you know if any additional quote is required for extras.</span>
                <li style='font-size: 12pt; padding-left: 5px;font-weight:700;'>Implementation Begins</li>
                <span style='line-height: 1.38;display: inline-block;font-family: Calibri, sans-serif;font-size: 12pt;color: #000;padding-left:20px;'>– <strong>Immediately</strong>, if no extras are required <strong>and we have admin access</span>
                <span style='line-height: 1.38;display: inline-block;font-family: Calibri, sans-serif;font-size: 12pt;color: #000;padding-left:20px;'>– <strong>Immediately</strong> after confirming any extra quote, if extras are requested</span>
            </ol>
            <hr style='{$hr_style}'>
            <h3 style='{$h3_style}; margin-top: 25px;'>Access Requirement</h3>
            <p style='line-height: 1.38;margin-top: 5px;margin-bottom: 5px;font-size: 12pt;font-family: Calibri, sans-serif;color:#000;'>To implement your tool/service, please provide <strong>Admin access</strong> to your BD website.<br>
            You may grant access <strong>before, during, or after</strong> the meeting — whichever you prefer.</p>
            
            <p style='margin-top: 5px;margin-bottom: 5px;font-size: 12pt;font-family: Calibri, sans-serif;color:#000;'>Add these accounts as Admin:</p>
            <ul style='margin: 5px;color: #000; padding-left: 20px; line-height: 1.6; list-style-type: disc; font-family: Calibri, sans-serif;'>
                <li style='list-style: none;margin-bottom: 5px;'><a href='mailto:dev1@businesslabs.org' style='text-decoration: none; color:#0056b3;'>dev1@businesslabs.org</a></li>
                <li style='list-style: none;margin-bottom: 5px;'><a href='mailto:dev2@businesslabs.org' style='text-decoration: none; color:#0056b3;'>dev2@businesslabs.org</a></li>
            </ul> 
            
            {$invoice_html}";
            
             // Append table manually to bottom so it appears AFTER the text/invoice link
            if ($amount_paid > 0) {
                 $email_body_content .= generateOrderTable($tool_name, $amount_paid, $impl_label, $delivery_label, $warranty_label, $subtotal, $discount, $total, $payment_unit, $startsfrom_type, $actual_price);
            }
            $include_table = false; // Prevent auto-add at top
        } 
        // SCENARIO 3: FIXED PRICE (Pure Service / Custom)
        else {
            $email_body_content = "
            <hr style='{$hr_style}'>
            <h3 style='{$h3_style}'>Status: Order Confirmed</h3>
            <p style='{$p_style}'>Your payment is confirmed. We are ready to proceed with the implementation immediately (no meeting or approval needed).</p>
            
            <p style='color: #333333; line-height: 1 !important; margin-top: 10px;'><strong>Execution Plan:</strong></p>
            <ul style='color: #333; padding-left: 20px; line-height: 1.6;'>
                <li><strong>Final inputs:</strong> Our team will reach out within <strong>{$contact_timeline}</strong> for final inputs (images, branding, text, design preferences).</li>
                <li><strong>Implementation:</strong> We will share the proposed plan/layout. Once approved, we will implement it end-to-end.</li>
                <li><strong>Completion:</strong> The project will be executed at the earliest availability following approval.</li>
            </ul>
            
            <div style='{$green_box_style}'>
                <strong>Proactive Step: Grant admin access</strong><br>
                If you plan to use our team for installation, please add the following users as Admins now:<br>
                <strong style='color:#000;'>1. dev1@businesslabs.org</strong><br>
                <strong style='color:#000;'>2. dev2@businesslabs.org</strong>
            </div>
            
            {$invoice_html}<hr style='{$hr_style}'>";
             $include_table = true; 
        }
       
        break;

    case 'starts_from':
        if ($startsfrom_type === 'commitment' || $startsfrom_type === 'deposit') {
            // #3. Starts From (Deposit Amount) - UPDATED based on screenshot
            $email_body_content = "
            <hr style='{$hr_style}'>
            <h3 style='{$h3_style}'>Next Steps</h3>
            <p style='margin-top: 5px;margin-bottom: 5px;font-size: 12pt;font-family: Calibri, sans-serif;color:#000;'>We’re ready when you are. Here’s what comes next:</p>
            
            <ol style='color: #000; font-family: Calibri, sans-serif; padding-left: 20px; line-height: 1.6; margin-top: 15px;'>
                <li style='padding-left: 5px; font-size: 12pt; font-weight:700;'>Schedule Your Discovery Session</li>
                <span style='line-height: 1.38;display: inline-block;font-family: Calibri, sans-serif;font-size: 12pt;color: #000;padding-left:20px;'>Book a convenient time here: " . $discoveryMeetingLink . "</span>
                <li style='padding-left: 5px; font-size: 12pt; font-weight: 700;'>Define Scope & Features</li>
                <span style='line-height: 1.38;display: inline-block;font-family: Calibri, sans-serif;font-size: 12pt;color: #000;padding-left:20px;'>In the session, we’ll finalize requirements, review landing-page inclusions, and confirm if any extras are needed.</span>
                <li style='padding-left: 5px;font-size: 12pt; font-weight: 700;'>Review & Begin Implementation</li>
                <span style='line-height: 1.38;display: inline-block;font-family: Calibri, sans-serif;font-size: 12pt;color: #000;padding-left:20px;'>Once the scope and any additional cost (if applicable) are approved, we will begin the project immediately.</span>
            </ol>
            
            {$invoice_html}<hr style='{$hr_style}'>";
        } else {
            // #4. Starts From (Full Charge)
            $email_body_content = "
            <hr style='{$hr_style}'>
            <h3 style='{$h3_style}'>Next Steps</h3>
            <p style='margin-top: 5px;margin-bottom: 5px;font-size: 12pt;font-family: Calibri, sans-serif;color:#000;'>We’re ready when you are. Here’s what comes next:</p>
            
            <ol style='color: #000; font-family: Calibri, sans-serif; padding-left: 20px; line-height: 1.6; margin-top: 15px;'>
                <li style='padding-left: 5px; font-size: 12pt; font-weight: 700;'>Schedule Your Discovery Session</li>
                <span style='line-height: 1.38;display: inline-block;font-family: Calibri, sans-serif;font-size: 12pt;color: #000;padding-left:20px;'>Book a convenient time here:<br>" . $DiscoveryMeetingLink . "</span>
                <li style='padding-left: 5px; font-size: 12pt; font-weight: 700;'>Define Scope & Features</li>
                <span style='line-height: 1.38;display: inline-block;font-family: Calibri, sans-serif;font-size: 12pt;color: #000;padding-left:20px;'>In the session, we’ll finalize requirements, review landing-page inclusions, and confirm if any extras are needed.</span>
                <li style='padding-left: 5px; font-size: 12pt; font-weight: 700;'>Review & Begin Implementation</li>
                <span style='line-height: 1.38;display: inline-block;font-family: Calibri, sans-serif;font-size: 12pt;color: #000;padding-left:20px;'>Once the scope and any additional cost (if applicable) are approved, we will begin the project immediately.</span>
            </ol>

            <h3 style='{$h3_style}; margin-top: 30px;'>Access Requirements</h3>
            <p style='line-height: 1.38;margin-top: 5px;margin-bottom: 5px;font-size: 12pt;font-family: Calibri, sans-serif;color:#000;'>To implement your tool/service, we will need <strong>Admin access</strong> to your BD website.<br>
            You may provide access <strong>before, during, or after</strong> our meeting &mdash; whatever works best for you.</p>
            
            <p style='line-height: 1.38;margin-top: 5px;margin-bottom: 5px;font-size: 12pt;font-family: Calibri, sans-serif;color:#000;'>Please add the following accounts as Admin:</p>
            <ul style='margin: 5px;color: #000; padding-left: 20px; line-height: 1.6; list-style-type: disc; font-family: Calibri, sans-serif;'>
                <li style='list-style: none;margin-bottom: 5px;'><a href='mailto:dev1@businesslabs.org' style='text-decoration: none; color:#0056b3;'>dev1@businesslabs.org</a></li>
                <li style='list-style: none;margin-bottom: 5px;'><a href='mailto:dev2@businesslabs.org' style='text-decoration: none; color:#0056b3;'>dev2@businesslabs.org</a></li>
            </ul> 

            {$invoice_html}<hr style='{$hr_style}'>";
        }
        $include_table = true;
        break;
    
    default:
        // Fallback
        $email_body_content = "
        <hr style='{$hr_style}'>
        <h3 style='{$h3_style}'>Status: Order Received</h3>
        <p style='{$p_style}'>We have successfully received your order details.</p>
        <p style='{$p_style}'>Our team is currently reviewing your request and will be in touch shortly with the next steps.</p>
        {$invoice_html}";
        $include_table = ($amount_paid > 0);
        break;
}
// ✅ CONSTRUCT FINAL HTML
$final_html = "";

if ($include_table && $amount_paid > 0) {
    // UPDATED: Pass payment_unit and startsfrom_type to the table generator
    // Added $actual_price to the end
    $final_html .= generateOrderTable($tool_name, $amount_paid, $impl_label, $delivery_label, $warranty_label, $subtotal, $discount, $total, $payment_unit, $startsfrom_type, $actual_price);
}

$final_html .= $email_body_content;

$w['full_name']       = $user_name ?? '';
$w['user_email']      = $user_email ?? '';
$w['phone_number']    = $_POST['user_phone'] ?? '';
$w['service_name']    = $tool_name ?? '';
$w['tool_short_name'] = $tool_name ?? '';
$w['order_id']        = $neworderid ?? '';
$w['order_summary_html'] = $final_html;
$w['email_preview_text'] = 'Your request for ' . $tool_name . ' has been received. Our team will contact you within 2 business days with next steps.';
$w['email_post_url'] = $post_url;
$w['origin_url'] = $_POST['origin_url'];
$w['tool_url'] = $_POST['post_url'] ?? $post_url;

// ✅ PASS STRIPE DATA TO PAID/FREE EMAIL
$w['stripe_transaction_data'] = $stripe_admin_html;


if (function_exists('prepareEmail') && function_exists('sendEmailTemplate')) {
    
    
    if($payment_unit === 'subscription') {
        $email_2 = prepareEmail('subscription_tools', $w);
    } else if($payment_unit === 'fixed' || $payment_unit === 'fixed_price' ) {
        $email_2 = prepareEmail('fixed_price_tools', $w);
    } else if($payment_unit === 'free') {
        $email_2 = prepareEmail('free_tools', $w);
    } else if ($payment_unit === 'starts_from' && $startsfrom_type === 'commitment' || $startsfrom_type === 'deposit') {
        $email_2 = prepareEmail('starts_from_deposit_tools', $w);
    } else {
        $email_2 = prepareEmail('starts_from_full_tools', $w);
    }
      
    if ($email_2 && $w['user_email']) {
        sendEmailTemplate($w['website_email'], $w['user_email'], $email_2['subject'], $email_2['html'], $email_2['text'], $email_2['priority'], $w);
    }
    
    $email_3 = prepareEmail('quick-services-admin-tools', $w);
    if ($email_3) {
        sendEmailTemplate($w['website_email'], $w['website_email'], $email_3['subject'], $email_3['html'], $email_3['text'], $email_3['priority'], $w);
    }
}

echo json_encode([
    "success" => true,
    "status"  => "success",
    "message" => "Order saved and emails triggered successfully",
    "session_id" => $session_id,
    "payment_unit" => $payment_unit
]);

exit;
?>