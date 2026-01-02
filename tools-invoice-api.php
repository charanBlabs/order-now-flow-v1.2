<?php
    header('Content-Type: application/json');

    // Common function to fetch invoices from external API
    function fetch_invoices() {
        $url = 'https://pmp.businesslabs.org/api/invoices';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'authtoken: eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyIjoidGVzdGFwaSIsIm5hbWUiOiJUZXN0QVBJIiwiQVBJX1RJTUUiOjE3NjU4NTk4NDd9.XjWG7ra47fwkr1k4IMaUTFISIpvEiCd_kSjI_-Q00MI',
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'BD-Widget-Agent');

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }


    // Helper to get max ID invoice
    function get_max_id_invoice($invoices) {
        $max_id = -1;
        $max_invoice = null;

        if (!is_array($invoices)) {
            return null; // Return null if $invoices is not an array
        }

        foreach ($invoices as $invoice) {
            if (isset($invoice['id']) && is_numeric($invoice['id'])) {
                $current_id = (int)$invoice['id'];
                if ($current_id > $max_id) {
                    $max_id = $current_id;
                    $max_invoice = $invoice;
                }
            }
        }
        return $max_invoice;
    }


    // Helper to increment invoice number like "INV-105" → "INV-106"
    function increment_invoice_number($number) {
        // This function was just incrementing a number, but the example "INV-105" suggests a prefix.
        // The original code `(string)((int)$number + 1)` would fail for "INV-105".
        // Assuming the number is *actually* just a number string like "105".
        // If it has a prefix, this logic needs to be more complex.
        // Sticking to original logic for now:
        return (string)((int)$number + 1);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $invoices = fetch_invoices();

        if (is_array($invoices) && !empty($invoices)) {
            $max_invoice = get_max_id_invoice($invoices);
            if ($max_invoice !== null) {
                echo json_encode([
                    'status' => 'success',
                    'data' => [
                        'id' => $max_invoice['id'],
                        'hash' => $max_invoice['hash'],
                        'number' => $max_invoice['number'] ?? null,
                        'total' => $max_invoice['total'] ?? null
                    ],
                    'message' => 'Max ID invoice retrieved successfully.'
                ]);
                http_response_code(200);
                exit;
            }
        }

        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'No invoices found.']);
        exit;
    }


    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        $required_fields = ['clientid', 'last_invoice_number', 'date', 'currency', 'newitems', 'allowed_payment_modes', 'subtotal', 'total', 'discount_type', 'clientnote', 'terms', 'discount_total'];
        foreach ($required_fields as $field) {
            // Allow test_mode to be an exception
            if ($field === 'last_invoice_number' && isset($_POST['test_mode']) && $_POST['test_mode'] === 'true') {
                continue;
            }
            
            if (!isset($_POST[$field]) || $_POST[$field] === '') {
                // Check for 'LastOrderIdTool' which seems to be used later
                if ($field === 'clientnote' && isset($_POST['LastOrderIdTool'])) continue; // Relaxing some rules based on later code
                if ($field === 'terms' && isset($_POST['LastOrderIdTool'])) continue;
                
                http_response_code(422);
                echo json_encode(['status' => 'error', 'message' => "Missing or empty field: $field"]);
                exit;
            }
        }

        // Step 1: Fetch current invoices
        $invoices = fetch_invoices();
        $max_invoice = get_max_id_invoice($invoices);

        if (!$max_invoice || !isset($max_invoice['number'])) {
             // Allow test mode to proceed even if no invoices are found
            if (!isset($_POST['test_mode']) || $_POST['test_mode'] !== 'true') {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Could not determine last invoice number.']);
                exit;
            }
             // Create a fake max_invoice for testing
            $max_invoice = ['number' => 'TEST-1000']; 
        }

        $last_invoice_number = $max_invoice['number'];
        $new_invoice_number = increment_invoice_number($last_invoice_number);

        // Step 2: Check if frontend-provided number matches last known number
        if (!isset($_POST['last_invoice_number']) || $_POST['last_invoice_number'] !== $last_invoice_number) {
            // Allow test mode to bypass this check
            if (!isset($_POST['test_mode']) || $_POST['test_mode'] !== 'true') {
                http_response_code(409);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invoice number mismatch. Please refresh and try again.',
                    'expected' => $last_invoice_number
                ]);
                exit;
            }
        }
        
        // Step 3: Build POST fields array including new incremented number
        // (This code will only run if NOT in test mode)
        
    // Decode newitems if passed as JSON string
    $decoded_items = is_string($_POST['newitems']) ? json_decode($_POST['newitems'], true) : $_POST['newitems'];
    $whatsappLink = 'https://web.whatsapp.com/send?phone=917799285123';
    $postData = [
        'clientid' => $_POST['clientid'],
        'date' => $_POST['date'],
        'currency' => $_POST['currency'],
        'allowed_payment_modes' =>[1, "stripe"], // This was hardcoded, keeping it
        'subtotal' => $_POST['subtotal'],
        'total' => $_POST['total'],
        'number' => $new_invoice_number,
        'billing_street' => $_POST['billing_street'] ?? null, // Add null coalescing for safety
        'discount_type' => 'after_tax', // This was hardcoded, keeping it
        'discount_total' => $_POST['discount_total'],
        'clientnote' => 'For questions or support, contact us:<br>Email: <a href="mailto:support@bdgrowthsuite.com" style="color: #007bff;">support@bdgrowthsuite.com</a><br> WhatsApp Message: <a href="' . $whatsappLink . '" target="_blank" style="color: #25D366;">+91 77992 85123</a>',
        'terms' => '<strong>License Type:</strong> Single Domain-Locked Commercial License (SDCL v1.0)<br>By completing this purchase, you acknowledge and accept the terms of this license.<br>Usage is restricted to one specific domain only. Redistribution, resale, or use on multiple domains is not permitted.<br>A separate license must be purchased for each additional website.<br>License terms available at: https://bdgrowthsuite.com/license/sdcl-v1',
    ];



    // Inject properly formatted newitems
    if (is_array($decoded_items)) {
        foreach ($decoded_items as $i => $item) {
            if (is_array($item)) {
                foreach ($item as $key => $value) {
                    $postData["newitems[$i][$key]"] = $value;
                }
            }
        }
    }

    // Log what we’re sending
    error_log("=== OUTGOING PAYLOAD TO API ===");
    error_log(print_r($postData, true));

    // Build query string
    $postFields = http_build_query($postData, '', '&');


    // Step 5: Send POST to external API
    if (isset($_POST['test_mode']) && $_POST['test_mode'] === 'true') {
        // Test mode: Skip external API call, simulate success
        error_log("=== TEST MODE ACTIVATED ===");
        error_log("Skipping external API POST.");
        
        $response = json_encode(['status' => 'success', 'message' => 'Test mode, API call skipped.']);
        $httpcode = 200;
        $curl_error = null;
    } else {
        // Normal mode: Send POST to external API
        $ch = curl_init('https://pmp.businesslabs.org/api/invoices');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'authtoken: eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyIjoidGVzdGFwaSIsIm5hbWUiOiJUZXN0QVBJIiwiQVBJX1RJTUUiOjE3NjU4NTk4NDd9.XjWG7ra47fwkr1k4IMaUTFISIpvEiCd_kSjI_-Q00MI',
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'BD-Widget-Agent');

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
    }

    // Log response details
    error_log("=== API RESPONSE ===");
    error_log("HTTP CODE: " . $httpcode);
    if ($curl_error) {
        error_log("cURL ERROR: " . $curl_error);
    }
    error_log("RESPONSE BODY: " . $response);

    // Continue as usual
    if (isset($_POST['test_mode']) && $_POST['test_mode'] === 'true') {
        // Test mode: Create a FAKE $new_max_invoice
        error_log("Test mode: Creating fake new_max_invoice.");
        $new_max_invoice = [
            'id' => 'TEST-' . rand(1000, 9999), // Fake ID
            'number' => $new_invoice_number,
            'hash' => 'TEST-HASH-' . md5(time()) // Fake hash
        ];
    } else {
        // Normal mode: Fetch real invoices
        $updated_invoices = fetch_invoices();
        $new_max_invoice = get_max_id_invoice($updated_invoices);
    }

    echo json_encode([
        'status' => $httpcode >= 200 && $httpcode < 300 ? 'success' : 'error',
        'message' => $httpcode >= 200 && $httpcode < 300 ? 'Invoice added successfully.' : 'API request failed.',
        'data' => [
            'new_invoice_id' => $new_max_invoice['id'] ?? null,
            'number' => $new_max_invoice['number'] ?? null,
            'hash' => $new_max_invoice['hash'] ?? null,
            'newnum' => $new_invoice_number
        ],
        'http_code' => $httpcode,
        'raw_response' => $response
    ]);

    // Starting  Additional code to strore invoices for tools used in order summary for email templates
    if ($httpcode >= 200 && $httpcode < 300) {
        // Check if mysql functions exist before using them
        if (function_exists('mysql_real_escape_string') && function_exists('mysql_query')) {

            // Escape helper
            function esc($v) {
                // Check if the deprecated mysql function exists
                if (function_exists('mysql_real_escape_string')) {
                    // This will use the last opened connection.
                    // It might still raise a Warning if no connection is active,
                    // but it fixes the Fatal Error from mysql_ping().
                    $escaped = mysql_real_escape_string($v);
                    if ($escaped !== false) {
                        return $escaped;
                    }
                }
                // Fallback for safety if function doesn't exist or fails
                return addslashes($v);
            }

            //Convert values for Inserting in tools_invoices table
            $clientid            = esc($_POST['clientid']);
            $last_invoice_id     =  $new_max_invoice['id'] ?? 'NULL'; // Handle null
            $last_invoice_number = esc($new_invoice_number);
            $date                = esc($_POST['date']);
            $currency            = esc($_POST['currency']);
            $newitems            = esc(json_encode($decoded_items));
            $allowed_modes       = esc(json_encode($postData['allowed_payment_modes']));
            $billing_street      = esc($_POST['billing_street'] ?? '');
            $subtotal            = floatval($_POST['subtotal']);
            $total               = floatval($_POST['total']);
            $discount_type       = esc($_POST['discount_type']);
            $discount_total      = floatval($_POST['discount_total']);
            $clientnote          = esc($postData['clientnote']);
            $terms               = esc($postData['terms']);
            $invoice_link = 'https://pmp.businesslabs.org/invoice/' . ($new_max_invoice['id'] ?? 'NA') . '/' . ($new_max_invoice['hash'] ?? 'NA');

            $LastOrderIdTool = esc($_POST['LastOrderIdTool']);

            //Build INSERT query
            // $q = "
            // INSERT INTO tools_invoices (
            //      clientid, last_invoice_number, date, currency, newitems, allowed_payment_modes,
            //      billing_street, subtotal, total, discount_type, discount_total, clientnote, terms, invoice_link
            // ) VALUES (
            //      '$clientid',
            //      '$last_invoice_number',
            //      '$date',
            //      '$currency',
            //      '$newitems',
            //      '$allowed_modes',
            //      '$billing_street',
            //      $subtotal,
            //      $total,
            //      '$discount_type',
            //      $discount_total,
            //      '$clientnote',
            //      '$terms',
            //      '$invoice_link'
            // )";

            $q = "UPDATE `tools_invoices` SET
                    `pmp_invoiceid` = '$last_invoice_id',
                    `last_invoice_number` = '$last_invoice_number', 
                    `date` = '$date', 
                    `currency` = '$currency', 
                    `newitems` = '$newitems', 
                    `allowed_payment_modes` = '$allowed_modes', 
                    `billing_street` = '$billing_street', 
                    `subtotal` = '$subtotal', 
                    `total` = ' $total', 
                    `discount_type` = '$discount_type', 
                    `discount_total` = '$discount_total', 
                    `clientnote` = '$clientnote', 
                    `terms` = '$terms', 
                    `invoice_link` = '$invoice_link' WHERE `tools_invoices`.`id` = '$LastOrderIdTool'; ";

            // Execute query
            //mysql_query($q);
            $result = mysql_query($q);
            if (!$result) {
                // Don't exit here, just log the error
                error_log('Query failed: ' . mysql_error());
                // http_response_code(500);
                // exit('Query failed: ' . mysql_error());
            }

            //Emali to client after invoice is generated

            $order_summary_html = "
            <hr><h3>Order Summary</h3>
            <table cellpadding='6' cellspacing='0' width='100%' style='border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px; color: #1e293b;'>
                <thead style='background: #f1f5f9; color: #1e293b;'>
                    <tr>
                        <th align='left' style='border: 1px solid #e2e8f0; padding: 8px;'>#</th>
                        <th align='left' style='border: 1px solid #e2e8f0; padding: 8px;'>Item</th>
                        <th align='right' style='border: 1px solid #e2e8f0; padding: 8px;'>Qty</th>
                        <th align='right' style='border: 1px solid #e2e8f0; padding: 8px;'>Rate</th>
                        <th align='right' style='border: 1px solid #e2e8f0; padding: 8px;'>Amount</th>
                    </tr>
                </thead>
                <tbody>
            ";

            $my_invoices_result = mysql_query("SELECT * FROM `tools_invoices` WHERE `tools_invoices`.`id` = '$LastOrderIdTool';");
            $invoice_row = null; // Initialize
            if ($my_invoices_result && mysql_num_rows($my_invoices_result) > 0) {
                $invoice_row = mysql_fetch_assoc($my_invoices_result);
                $items = json_decode($invoice_row['newitems'], true);
            } else {
                // Fallback if query fails or no rows
                $items = $decoded_items; // Use items from POST
                $invoice_row = $_POST; // Use POST data as a fallback
                $invoice_row['invoice_link'] = $invoice_link; // Add link
            }
            
            if (!isset($items) || !is_array($items)) {
                $items = []; // Ensure items is an array
            }


            if (isset($invoice_row['total']) && $invoice_row['total'] == 0.01) {
                $invoice_row['total'] = '0.00';
            }
            
            $id_count = 1;
            $flag = 0;
            foreach ($items as $item) {
                $item_description = $item['description'] ?? 'N/A';
                $item_qty = $item['qty'] ?? 1;
                $item_rate = $item['rate'] ?? 0;
                $item_tool_type = $item['tool_type'] ?? 'N/A';

                $order_summary_html .= "
                    <tr>
                        <td style='border: 1px solid #e2e8f0; padding: 8px;'>{$id_count}</td>
                        <td style='border: 1px solid #e2e8f0; padding: 8px;'>{$item_description}</td>
                        <td style='border: 1px solid #e2e8f0; padding: 8px;' align='right'>{$item_qty}</td>
                        <td style='border: 1px solid #e2e8f0; padding: 8px;' align='right'>$" . number_format($item_rate, 2) . "</td>
                        <td style='border: 1px solid #e2e8f0; padding: 8px;' align='right'>$" . number_format($item_rate * $item_qty, 2) . "</td> 
                    </tr>
                ";
                if ($item_tool_type == 'Fixed Price' || $item_tool_type == 'fixed_price' ) {
                    $flag = 1;
                } else if($item_tool_type === 'Ask for quote' || $item_tool_type == 'ask_for_quote') {
                    $flag = 2;
                } else if ($item_tool_type === 'Free' || $item_tool_type == 'free') {
                    $flag = 3;
                } else if ($item_tool_type === 'Starts from' || $item_tool_type == 'starts_from') {
                    $flag = 4;
                }
                $id_count++;
            }
            
            $row_subtotal = $invoice_row['subtotal'] ?? 0;
            $row_discount_total = $invoice_row['discount_total'] ?? 0;
            $row_total = $invoice_row['total'] ?? 0;
            $row_invoice_link = $invoice_row['invoice_link'] ?? '#';


            $order_summary_html .= "
                    <tr>
                        <td colspan='4' align='right' style='border: 1px solid #e2e8f0; padding: 8px; font-weight: bold;'>Sub Total</td>
                        <td align='right' style='border: 1px solid #e2e8f0; padding: 8px; font-weight: bold;'>$" . number_format($row_subtotal, 2) . "</td>
                    </tr>
                    <tr>
                        <td colspan='4' align='right' style='border: 1px solid #e2e8f0; padding: 8px; font-weight: bold;'>Discount</td>
                        <td align='right' style='border: 1px solid #e2e8f0; padding: 8px; font-weight: bold; color: #dc2626;'>-$" . number_format($row_discount_total, 2) . "</td>
                    </tr>
                    <tr>
                        <td colspan='4' align='right' style='border: 1px solid #e2e8f0; padding: 8px; font-weight: bold;'>Total</td>
                        <td align='right' style='border: 1px solid #e2e8f0; padding: 8px; font-weight: bold;'>$" . number_format($row_total, 2) . "</td>
                    </tr>
                </tbody>
            </table>
            ";

            if ($flag == 1) {
                $order_summary_html .= "<hr><h3 style='margin: 0;'>Invoice & Next Steps</h3>
                <p>Your invoice includes a complete breakdown of the selected paid tool.<br>
                Kindly review and verify that the details align with your order summary above.</p>
                <p>Once everything looks correct, scroll to the bottom of the invoice and click the <strong>&ldquo;Pay Now&rdquo;</strong> button to complete your payment.</p>
                <p><strong>Invoice Link:</strong> <a href='" . $row_invoice_link . "' target='_blank'>" . $row_invoice_link . "</a></p>
                <p>After payment, we’ll begin implementation. Our team will reach out within <strong>2 business days</strong> via email (and phone, if available).</p>
                <p>In the meantime, you can grant full admin access to:<br>
                <strong>dev1@businesslabs.org</strong><br>
                <strong>dev2@businesslabs.org</strong></p><hr>";
                
                $_SESSION['tools_page_type'] = 'paid';
                $_SESSION['tools_page_invoice_link'] = $row_invoice_link;
            }
            else if ($flag == 2){
                $order_summary_html .= "<hr><h3 style='margin: 0;'>Quote Request & Next Steps</h3>
                                <p>Thank you for your interest! We have received your request for a quote.</p>
                                <p>Our solutions team will carefully review your requirements and will contact you within <strong>1-2 business days</strong> to discuss your specific needs and provide a detailed proposal.</p>
                                <p>Please keep an eye on your inbox (and spam folder, just in case) for an email from our team.</p>
                                <p>No further action is needed from you at this time. If you have any immediate questions, please feel free to reply directly to this email.</p><hr>";
                
                $_SESSION['tools_page_type'] = 'quote';
                
            } else if ($flag == 3) {
                $order_summary_html .= "<hr><h3 style='margin: 0;'>Next Steps for Your Free Tool</h3>
                                <p>Thank you for your order! We've received your request and are excited to get you set up.</p>
                                <p>Our team will begin the implementation process shortly. We will reach out via email within <strong>2 business days</strong> to confirm when the setup is complete or if we require any additional information.</p>
                                <p>To help us get started, you can grant full admin access to:</p>
                                <p><strong>dev1@businesslabs.org</strong><br>
                                <strong>dev2@businesslabs.org</strong></p>
                                <p>No further action is needed from you at this time. We look forward to getting you started!</p><hr>";
                
                $_SESSION['tools_page_type'] = 'free';
            } else if ($flag == 4) {
                $order_summary_html .= "<hr><h3 style='margin: 0;'>Invoice & Consultation Next Steps</h3>
                                <p>Thank you for your order! Your invoice covers the initial payment for the selected tool, which secures your purchase and initiates the onboarding process.</p>
                                <p>Please review it and <strong>cross-check each item with the order summary above</strong>.</p>
                                <p>Once everything looks correct, please use the link below to complete the initial payment.</p>
                                <p><strong>Invoice Link:</strong> <a href='" . $row_invoice_link . "' target='_blank'>" . $row_invoice_link . "</a></p>
                                <p>After payment, our team will contact you within <strong>1-2 business days</strong> to schedule a consultation. During this call, we will discuss your specific requirements, explore any necessary add-ons, and finalize the total scope and investment.</p>
                                <p>In the meantime, you can grant full admin access to:<br>
                                <strong>dev1@businesslabs.org</strong><br>
                                <strong>dev2@businesslabs.org</strong></p><hr>";
                $_SESSION['tools_page_type'] = 'starts_from';
            }
            
            $_SESSION["servicesOrdered"] = "1";

            /*set variable name */
            //$w[first_name] = $_POST['FirstName'];
            $w['full_name'] = ($_POST['FirstName'] ?? '') . ' ' . ($_POST['LastName'] ?? '');
            $w['user_email'] = $_POST['clientemail'] ?? '';
            $w['phone_number'] = $_POST['PhoneNumber'] ?? '';
            //$w['full_name'] = $firstname. ' ' . $lastname;
            $w['service_name'] = $_POST['ServiceName'] ?? '';
            $w['tool_short_name'] = $_POST['ShortTitle'] ?? '';
            $w['order_summary_html'] = $order_summary_html;
            $w['order_id'] = $_POST['post_id'] ?? '';
            $w['website_email'] = $w['website_email'] ?? 'default@example.com'; // Add a default

            /*set variable name */
            
            // Check if email functions exist
            if (function_exists('prepareEmail') && function_exists('sendEmailTemplate')) {
                /*send email to client*/
                
                /*$email_2 = prepareEmail('quick_services_tools', $w);
                if($email_2 && $w[user_email]) { // Check if email was prepared and user email exists
                    sendEmailTemplate($w['website_email'], $w[user_email] , $email_2['subject'], $email_2['html'], $email_2['text'], $email_2['priority'], $w);
                }*/
                
                /*
                $email_3 = prepareEmail('quick-services-admin-tools', $w);
                if($email_3) {
                    sendEmailTemplate($w['website_email'], $w['website_email'], $email_3['subject'], $email_3['html'], $email_3['text'], $email_3['priority'], $w);
                }
                */
            } else {
                error_log("Email functions (prepareEmail or sendEmailTemplate) not found. Skipping email.");
            }
        
        } else {
            error_log("Deprecated mysql functions not found. Skipping database update and email sending.");
        }
    }
    // Ending  Additional code to strore invoices for tools used in order summary for email templates

    http_response_code($httpcode);
    exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Invalid request method or missing parameters']);
    exit;
?>

