<?php

    ob_clean(); // VERY IMPORTANT
    header('Content-Type: application/json');

    if(isset($_POST["password"]) && !isset($_COOKIE["userid"])){
		
    $firstname = $_POST["firstname"];
    $lastname = $_POST["lastname"];
    $email1 = $_POST["email"];
    $number = $_POST["phonenumber"];
    $url = $_POST["websiteurl"];
	$postid = $_POST["postid"];
	$actual_link = $_POST["origin_url"];
	$websiteurl = $_POST['websiteurl'];
	$askforquote = $_POST['requirements'] ?? '';
    $time = date('Ymdhis');
    
        $password = $_POST["password"];
        $salt = substr(hash("md5",$w['website_id']."qmzpalvt193764"), -22);
        $password = crypt($password, '$2a$11$'.$salt);
            
        $sql1 = "INSERT INTO `users_data` (`first_name`, `last_name`,`email`, `phone_number`, `password`,`active`,`token`,`ref_code`, `listing_type`,`subscription_id`,`signup_date`,`wallet_balance`) VALUES ('$firstname', '$lastname', '$email1','$number', '$password','2','$time','Self Signup','company','11','$time','100')";
        $sql1result = mysql_query($sql1);
		$lastUserInsert = mysql_insert_id();
		
       $sqluser = "INSERT INTO `users_purchase` (`user_id`,`first_name`, `last_name`,`service_name`, `category`,`transaction_type`,`transaction_amount`,`transaction_history`) VALUES ('$lastUserInsert','$firstname', '$lastname','From BD Growth Suite','Signup Bonus','credit','100','100')";
        $resultuser= mysql_query($sqluser);
		

        $sqlinquiry = "INSERT INTO `users_inquiry` (`user_id`,`name`,`website`, `email`, `phone`,`service_name`, `service_description`, `lead_type`) VALUES ('$lastUserInsert', '".$firstname.' '.$lastname."', '$url', '$email1','$number','$service','Quick Services','1')";
        $result= mysql_query($sqlinquiry);

        $order_date = date('Y-m-d'); 
        $tool_order_items = $_POST['newitems']; // true = convert to associative array
        $order_subtotal = $_POST["sub_total"];
        $order_total = $_POST["total_price"];
        $order_discount_total = $_POST["savings"];
        $clientnote_for_api = $_POST["clientnote"];
        $terms_for_api = $_POST["terms"];
        $sqlorders = "INSERT INTO `tools_invoices` (`id`, `user_id`, `tool_id`, `website_url`, `cust_firstname`, `cust_lastname`, `cust_number`, `cust_email`, `order_date`, `tool_order_items`, `order_subtotal`, `order_total`, `order_discount_total`, `clientnote_for_api`, `terms_for_api`, `pmp_invoiceid`, `last_invoice_number`, `clientid`, `date`, `currency`, `newitems`, `allowed_payment_modes`, `billing_street`, `subtotal`, `total`, `discount_type`, `discount_total`, `clientnote`, `terms`, `invoice_link`, `invoice_status`) 
                        VALUES (NULL, '".$_COOKIE["userid"]."','$postid','$websiteurl' , '$firstname', '$lastname', '$number', '$email1', '$order_date', '$tool_order_items', '$order_subtotal', '$order_total', '$order_discount_total', '$clientnote_for_api', '$terms_for_api', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL);";
        $result_orders= mysql_query($sqlorders);
        //var_dump($result_orders);
        
		// Get the inserted ID
        if ($result_orders) {
            $last_id = mysql_insert_id();
            echo  json_encode([
                'status' => 'success',
                'last_id' => $last_id
            ]);
        } 
		
		//setcookie("servicesOrdered", 2, time() + (86400 * 30), "/");
		//$_SESSION["servicesOrdered"] = "2";
		
		$w[first_name] = $firstname;
		$w[user_email] = $email1;
		$w[phone_number] = $number;
		$w['service_name'] = $service;
		$w['full_name'] = $firstname. ' ' . $lastname;
		$w[url_origin] = $actual_link;
		$w['tool-id'] = $postid;
		

        /** Send Welcome email  --- Check this email template and update the code*/
		
		$email_1 = prepareEmail('registration', $w);  /// Send Welcome email based on account type
		sendEmailTemplate($w['website_email'], $email1, $email_1['subject'], $email_1['html'], $email_1['text'], $email_1['priority'], $w);
		
		/*
		$email_admin1 = prepareEmail('registration-admin', $w);  /// Send Welcome email based on account type
		sendEmailTemplate($w['website_email'], $w['website_email'], $email_admin1['subject'], $email_admin1['html'], $email_admin1['text'], $email_admin1['priority'], $w); 
		*/

	} elseif(isset($_COOKIE["userid"])){
		$email1 = $_POST["email"];
        $firstname = $_POST["firstname"];
        $lastname = $_POST["lastname"];
        $url = $_POST["websiteurl"];
        $number = $_POST["phonenumber"];
        $postid = $_POST["postid"];
		$websiteurl = $_POST['websiteurl'];
		$service = $_POST["service"];
		$time = date('Ymdhis');
		
        /*$sqluser = "INSERT INTO `users_purchase` (`user_id`,`post_id`,`first_name`, `last_name`,`website_url`, `email`, `phone_number`,`service_name`, `category`, `token`) VALUES ('".$_COOKIE["userid"]."','$postid','$firstname', '$lastname', '$url', '$email1','$number','$service','Quick Services','$time')";
        $result= mysql_query($sqluser);*/
		
		/**
		 * Only Work For Old User 
		*/
		
		$sqlinquiry = "INSERT INTO `users_inquiry` (`user_id`,`name`,`website`, `email`, `phone`,`service_name`, `service_description`, `lead_type`) VALUES ('".$_COOKIE["userid"]."', '".$firstname.' '.$lastname."', '$url', '$email1','$number','$service','Quick Services','1')";
         $result= mysql_query($sqlinquiry);

		

        $order_date = date('Y-m-d'); 
        $tool_order_items = $_POST['newitems']; // true = convert to associative array
        $order_subtotal = $_POST["sub_total"];
        $order_total = $_POST["total_price"];
        $order_discount_total = $_POST["savings"];
        $clientnote_for_api = $_POST["clientnote"];
        $terms_for_api = $_POST["terms"];
        $sqlorders = "INSERT INTO `tools_invoices` (`id`, `user_id`, `tool_id`, `website_url`, `cust_firstname`, `cust_lastname`, `cust_number`, `cust_email`, `order_date`, `tool_order_items`, `order_subtotal`, `order_total`, `order_discount_total`, `clientnote_for_api`, `terms_for_api`, `pmp_invoiceid`, `last_invoice_number`, `clientid`, `date`, `currency`, `newitems`, `allowed_payment_modes`, `billing_street`, `subtotal`, `total`, `discount_type`, `discount_total`, `clientnote`, `terms`, `invoice_link`, `invoice_status`) 
                        VALUES (NULL, '".$_COOKIE["userid"]."','$postid','$websiteurl' , '$firstname', '$lastname', '$number', '$email1', '$order_date', '$tool_order_items', '$order_subtotal', '$order_total', '$order_discount_total', '$clientnote_for_api', '$terms_for_api', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL);";
        $result_orders= mysql_query($sqlorders);
        //var_dump($result_orders);

		// Get the inserted ID
        if ($result_orders) {
            $last_id = mysql_insert_id();
            echo  json_encode([
                'status' => 'success',
                'last_id' => $last_id
            ]);
        }
		
		//setcookie("servicesOrdered", 1, time() + (86400 * 30), "/");
		//$_SESSION["servicesOrdered"] = "1";
	}

    ?>