<?php
    header('Content-Type: application/json');

    // Constants
    define('PMP_API_URL', 'https://pmp.businesslabs.org/api/');
    define('PMP_AUTH_TOKEN', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyIjoidGVzdGFwaSIsIm5hbWUiOiJUZXN0QVBJIiwiQVBJX1RJTUUiOjE3NjU4NTk4NDd9.XjWG7ra47fwkr1k4IMaUTFISIpvEiCd_kSjI_-Q00MI');

    // Generic API Request
    function api_request($endpoint, $method = 'GET', $data = []) {
        $ch = curl_init(PMP_API_URL . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'authtoken: ' . PMP_AUTH_TOKEN,
                'Content-Type: application/x-www-form-urlencoded'
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'BD-Widget-Agent'
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        $res = curl_exec($ch);
        curl_close($ch);
        return json_decode($res, true);
    }

    // Search Customer
    function search_customer($keyword) {
        $endpoint = 'customers/search/' . urlencode($keyword);
        $response = api_request($endpoint, 'GET');

        if (is_array($response) && isset($response[0]['userid'])) {
            $response = $response[0];
            return [
                'status' => 'found',
                'data' => $response
            ];
        } else {
            return [
                'status' => 'not_found',
                'message' => $response['message'] ?? 'Customer not found.'
            ];
        }
    }

    // Add New Customer
    function add_customer($customerData) {
        if (empty($customerData['company']) || empty($customerData['email'])) {
            return [
                'status' => 'error',
                'message' => 'Company and email are required.'
            ];
        }

        $response = api_request('customers', 'POST', $customerData);

        if (isset($response['status']) && $response['status'] === true) {
            return [
                'status' => 'created',
                'message' => $response['message'] ?? 'Customer added successfully.'
            ];
        } else {
            return [
                'status' => 'error',
                'message' => $response['message'] ?? 'Customer creation failed.'
            ];
        }
    }

    // ==== USAGE STARTS HERE ====

    // Fetch input dynamically from POST or GET
    $firstname = $_REQUEST['firstname'] ?? '';
    $lastname = $_REQUEST['lastname'] ?? '';
    $websiteurl = $_REQUEST['websiteurl'] ?? '';
    $email = $_REQUEST['email'] ?? '';
    $phonenumber = $_REQUEST['phonenumber'] ?? '';
    $last_order_id = $_REQUEST['last_order_id'] ?? '';

    $company = trim($firstname . $lastname);

    // Basic validation
    if (empty($company) || empty($email)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Company and Email are required.'
        ]);
        exit;
    }

    // Step 1: Search by company name
    $search = search_customer($company);
    //echo $company . ' - ' . $email . ' - ' . $phonenumber; // Debugging output
    //echo json_encode($search); // Debugging output

    if ($search['status'] === 'found') {

        //Update tools_invoices table clientid
        $update_sql_orders = "UPDATE `tools_invoices` SET `clientid` = '".$search['data']['userid']."' WHERE `tools_invoices`.`id` =  ".$last_order_id.";";
        $update_result_orders= mysql_query($update_sql_orders);

        echo json_encode([
            'status' => 'found',
            'message' => 'Customer already exists.',
            'customer_id' => $search['data']['userid'],
            'data' => $search['data'],
            //'customer_email' => $search['customer_email'],
            'customer_email' => $search['customer_email'] ?? $email,
            'last_order_id' => $last_order_id,
        ]);
        
        

    } else {
        // Step 2: Add customer if not found
        $customerData = [
            'company' => $company,
            'phonenumber' => $phonenumber,
            'email' => $email
        ];

        $add = add_customer($customerData);

        if ($add['status'] === 'created') {

            // Step 3: Re-search to get new customer ID
            $searchAgain = search_customer($company);
            if ($searchAgain['status'] === 'found') {
                echo json_encode([
                    'status' => 'created',
                    'message' => $add['message'],
                    'customer_id' => $searchAgain['data']['userid'],
                    'data' => $searchAgain['data'],
                    'last_order_id' => $last_order_id,
                ]);

            //Update tools_invoices table clientid
            $update_sql_orders = "UPDATE `tools_invoices` SET `clientid` = '".$searchAgain['data']['userid']."' WHERE `tools_invoices`.`id` =  ".$last_order_id.";";
            $update_result_orders= mysql_query($update_sql_orders);

            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Customer added, but failed to retrieve customer ID.'
                ]);
            }
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => $add['message']
            ]);
        }

    }
?>