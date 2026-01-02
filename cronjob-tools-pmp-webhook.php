<?php
    function fetch_invoices() {
        $url = 'https://pmp.businesslabs.org/api/invoices';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'authtoken: eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyIjoiYmRtcGxlYWRuYXNpcmludGVncmF0aW9uIiwibmFtZSI6Ik5hc2lySW50ZWdyYXRpb24iLCJwYXNzd29yZCI6bnVsbCwiQVBJX1RJTUUiOjE1ODg3NzIwMDF9.ModOOG9bmvsi_HdPtDqz8dPZ0x55vw2HUQBVnCg2Elk',
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'BD-Widget-Agent');

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    // Fetch invoices
    $invoices = fetch_invoices();

    if (is_array($invoices) && !empty($invoices)) {
        $totalInvoices = count($invoices);
        $paidCount = 0;
        $unpaidCount = 0;

        foreach ($invoices as $invoice) {
            if (isset($invoice['status'])) {
                if ($invoice['status'] == "2") {
                    //var_dump($invoice);
                    //echo '<p>'.$invoice['number'].'<p>';

                    $invoice_number =  $invoice['number'];

                    // First, check if invoice number exists with greater condition (example: id > 1000)
                    $qCheck = "SELECT * FROM `tools_invoices` WHERE `last_invoice_number` = '".$invoice_number."' ";
                    $resultCheck = mysql_query($qCheck);

                    if (!$resultCheck) {
                        http_response_code(500);
                        exit("Query failed: " . mysql_error());
                    }

                    // Fetch and loop if data exists
                    if ($row = mysql_fetch_assoc($resultCheck)) {
                        $orderId = $row['id'];

                        $user_Email = $row['cust_email'];
                        $cust_firstname = $row['cust_firstname'];
                        $cust_email = $row['cust_email'];
                        $allowed_payment_modes = $row['allowed_payment_modes'];
                        $total = $row['total'];

                        // Build Update query
                        $qUpdate = "UPDATE `tools_invoices` 
                                    SET `invoice_status` = 2
                                    WHERE `tools_invoices`.`last_invoice_number` = '".$invoice_number."'";

                        $resultUpdate = mysql_query($qUpdate);

                        if (!$resultUpdate) {
                            http_response_code(500);
                            exit("Update failed: " . mysql_error());
                        } else {
                            echo "Invoice Status Updated for invoice number : " . $invoice_number . " and OrderID : " . $orderId;
                            // Email Code After updating the invoice status in tools_invoices Database 
                            // $user_email = $user_Email;

                            // $w['first_name'] = $cust_firstname;
                            // $w['id'] = esc($cs['customer_details']['id']);
                            // $w['email'] = $cust_email;
                            // $w['currency'] = $allowed_payment_modes;
                            // $w['amont'] = $total;
                            // $emailPrepareone = prepareEmail('invoice_payment_received', $w);
                            // $sendemail = sendEmailTemplate($w['website_email'], $user_email, $emailPrepareone[subject], $emailPrepareone[html], $emailPrepareone[text], $emailPrepareone[priority], $w);
                            http_response_code(200);
                        }
                    } 



                    // Build Update query
                    // $q = " UPDATE `tools_invoices` SET `invoice_status` = '2' WHERE `tools_invoices`.`last_invoice_number` = '".$invoice_number."'; ";


                    // // Execute query
                    // $result = mysql_query($q);                
                    // if ($result) {
                    //     echo 'Invoice Status Updated for :'.$invoice_number;
                    //     http_response_code(200);
                    // }
                    // if (!$result) {
                    //     http_response_code(500);
                    //     exit('Query failed: ' . mysql_error());
                    // }

                    // http_response_code(200);
                    // echo $q;
                    // echo 'OK';

                    // Email Code After storing in Database 
                    // $user_email = esc($flds['bdgsaccountemail']);

                    // $w['first_name'] = esc($cs['customer_details']['name']);
                    // $w['id'] = esc($cs['customer_details']['id']);
                    // $w['email'] = esc($cs['customer_details']['name']);
                    // $w['currency'] = esc($cs['customer_details']['currency']);
                    // $w['amont'] = esc($cs['customer_details']['amont']);

                    // $emailPrepareone = prepareEmail('invoice_payment_received', $w);

                    // $sendemail = sendEmailTemplate($w['website_email'], $user_email, $emailPrepareone[subject], $emailPrepareone[html], $emailPrepareone[text], $emailPrepareone[priority], $w);

                    $paidCount++;
                } else {
                    $unpaidCount++;
                }
            }
        }

        // Print in real HTML (not escaped)
        echo "<p>Total Invoices: $totalInvoices</p>";
        echo "<p>Paid Invoices: $paidCount</p>";
        echo "<p>Unpaid/Other Invoices: $unpaidCount</p>";

    } else {
        echo "<p>No invoices found.</p>";
    }
    ?>
