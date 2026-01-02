<?php if(isset($_POST["datapost"])) : ?>
    <?php
    	$datavalue = $_POST["datapost"];

        $sql1 = "select * from `users_data` where `email` = '$datavalue'";
        $sql1result = mysql_query($sql1);
        $numrows2 = mysql_num_rows($sql1result);
        $rows1 = mysql_fetch_assoc($sql1result);
    ?>

        <?php if($numrows2>0) : ?>
		<div class="title text-center"><?php 
            if ( $_POST['tools_pars'] == 'tools-v2') {
                echo "Welcome Back — Log In to Continue";
            } else {
                echo "Order Submission starts here";
            }
        ?></div>
            <form class="bdgs-tool-form" id="login-user">
                <div class="form-group">
                    <label for="email">Email address (you entered in the step #1)</label>
                    <div class="input-group input-email" >
                        <span class="input-group-addon" id="email"><i class="fa fa-envelope" aria-hidden="true"></i></span>
                        <input type="email" class="form-control" id="input-email" value="<?php echo $datavalue?>"
                            aria-describedby="email" disabled>
						<span class="input-group-btn">
                            <a class="btn btn-primary edit-email">Edit</a>
						</span>
                    </div>
					<span style="color: red; display: none; font-size: 14px;" id="error-email" >Please enter valid Email</span>
                </div>
                <div class="form-group">
                    <label for="password">You already have an account with us, please enter your password.</label>
                    <div class="input-group">
                        <span class="input-group-addon" id="password"><i class="fa fa-lock" aria-hidden="true"></i></span>
                        <input type="password" class="form-control" id="input-password" aria-describedby="email" required>
                    </div>
                    <span><span id="login-password" style="font-size: 15px; opacity: 0.7;">Did you forget your password? </span>  <a href="/login/retrieval" style="color: red; font-size: 15px; opacity: 0.7;">Click Here</a></span>
					
                </div>
                <button type="submit" class="btn btn-success" id="login-user-btn">Login</button>
            </form>
        <?php else : ?>
            <div class="title text-center"><?php 
            if ( $_POST['tools_pars'] == 'tools-v2') {
                echo "Create Your Account — It Only Takes a Minute";
            } else {
                echo "Enter your details below to continue the purchase";
            }
        ?></div>
            <form class="bdgs-tool-form" action="" id="signup-user">


                <div class="form-group">
                    <label for="first-name">First Name</label>
                    <div class="input-group">

                        <span class="input-group-addon" id="first-name"><i class="fa fa-user" aria-hidden="true"></i></span>
                        <input type="text" pattern="[A-Za-z]{1,32}" class="form-control" id="input-first-name" name="input-first-name"
                            aria-describedby="first-name" required>
						<input name="origin_url" id="origin_url" type="hidden" value="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>" >
                    </div>
                </div>
                <div class="form-group">
                    <label for="last-name">Last Name</label>
                    <div class="input-group">

                        <span class="input-group-addon" id="last-name"><i class="fa fa-user" aria-hidden="true"></i></span>
                        <input type="text" class="form-control" pattern="[A-Za-z]{1,32}" id="input-last-name" name="input-last-name"
                            aria-describedby="last-name" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="website-url">BD website url (with http:// or https://. Example: https://brilliant.com/)</label>
                    <div class="input-group">

                        <span class="input-group-addon" id="website-url"><i class="fa fa-globe" aria-hidden="true"></i></span>
                        <input type="url" class="form-control" id="input-website-url" name="input-website-url"
                            aria-describedby="website-url" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="email">Email address (you entered in the step #1).</label>
                    <div class="input-group input-email" >

                        <span class="input-group-addon" id="email"><i class="fa fa-envelope" aria-hidden="true"></i></span>
                        <input type="email" class="form-control" id="input-email" value="<?php echo $datavalue?>"
                            aria-describedby="email" disabled>
						<span class="input-group-btn">
                            <a class="btn btn-primary edit-email">Edit</a>
						</span>
                    </div>
					<span style="color: red; display: none; font-size: 14px;" id="error-email" >Please enter valid Email</span>

                </div>

                <div class="form-group">
                    <label for="phone-number">Phone Number (Country code followed by your phone number. Ex: +13025044225)</label>
                    <div class="input-group">

                        <span class="input-group-addon" id="phone-number"><i class="fa fa-phone" aria-hidden="true"></i></span>
                        <input type="price" class="form-control" id="input-phone-number" name="input-phone-number"
                            aria-describedby="phone-number" size="13" >
                    </div>
                    <span style="color: red; display: none; font-size: 14px;" class="error_Msg" >Please enter valid PhoneNumber</span>
                </div>
                
                <div class="form-group">
                    <label for="password">Password (It helps you access your purchases in the future.)</label>
                    <div class="input-group">

                        <span class="input-group-addon" id="password"><i class="fa fa-lock" aria-hidden="true"></i></span>
                        <input type="password" class="form-control" id="input-password" name="input-password"
                            aria-describedby="password" required>
                    </div>
                    <span id="error-password" style="color: red; font-size: 14px;"></span>
                </div>
                <div class="form-group">
                    <label for="password">Confirm Password</label>
                    <div class="input-group">
                        <span class="input-group-addon" id="cnfpassword"><i class="fa fa-lock" aria-hidden="true"></i></span>
                        <input type="password" class="form-control" id="input-cnf-password" name="input-cnf-password"
                            aria-describedby="cnfpassword" required>
                    </div>
                    <span id="error-cnf-password" style="color: red; font-size: 14px;"></span>
                </div>
				
				<!-- Code added for terms and conditions  -->
				<div class="form-group" style="padding: 10px 0;">
					<label style="font-weight: 500 !important;">
						<input type="checkbox" id="license_agree" required>
						I agree to the terms of the <strong>Single Domain-Locked Commercial License (SDCL v1.0).<br></strong>
						I understand this license allows use of the purchased product (theme/layout/service) on one specific domain only, and does not permit redistribution, resale, or use on multiple domains.
						<br>
						<a href="https://bdgrowthsuite.com/license/sdcl-v1" target="_blank">Full license terms</a>
					</label>
				</div>
                <button type="submit" class="btn btn-success" id="signup-user-btn" data-usid="">Save and proceed with the purchase</button>
            </form>
    <?php endif ?>

<?php endif ?>