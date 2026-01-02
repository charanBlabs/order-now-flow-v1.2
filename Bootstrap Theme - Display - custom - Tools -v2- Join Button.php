<?php
$post_id = $group["group_id"];
$query = "SELECT file FROM `users_portfolio` WHERE users_portfolio.order = 1 AND users_portfolio.group_id = $post_id";
$result = mysql_query($query);
$numrows = mysql_num_rows($result);

?>

<?php if (!isset($_SESSION["toolsOrdered"])): ?>

    <style>
        /* CSS for Subscription Tabs */
        .bdgs-subscription-tabs {
            display: flex;
            gap: 10px;
            background: #f1f3f5;
            padding: 5px;
            border-radius: 10px;
            border: 1px solid #e9ecef;
        }

        .bdgs-sub-tab {
            flex: 1;
            padding: 12px 10px;
            text-align: center;
            cursor: pointer;
            border-radius: 8px;
            border: 1px solid transparent;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: transparent;
            user-select: none;
        }

        .bdgs-sub-tab:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        .bdgs-sub-tab.active {
            background: #fff;
            border-color: #dee2e6;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .bdgs-sub-tab .bdgs-tab-title {
            font-size: 15px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 4px;
        }

        .bdgs-sub-tab.active .bdgs-tab-title {
            color: #007bff;
            /* Bootstrap Primary Color or your theme color */
        }

        .bdgs-sub-tab .bdgs-tab-price {
            font-size: 13px;
            color: #868e96;
        }

        .bdgs-sub-tab.active .bdgs-tab-price {
            color: #343a40;
            font-weight: 500;
        }

        .bdgs-save-badge {
            background: #d4edda;
            color: #155724;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 12px;
            margin-top: 6px;
            font-weight: bold;
            display: inline-block;
        }
    </style>

    <!-- This button just triggers the modal. All other logic is removed from it. -->
    <button class="btn btn-primary btn-lg vmargin" id="tool-order-now"
        data-postid="<?php print_r($group["group_id"]); ?>"
        data-service="<?php print_r($group["group_name"]); ?>"
        data-link="<?php echo $pars[1]; ?>"
        data-post_link="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>"
        data-usid="<?php echo $_COOKIE["userid"]; ?>"
        data-tool-subtitle="Boost your website's rankings and visibility."
        data-tool-name="<?php echo htmlspecialchars($group['group_name'] ?? ''); ?>"
        data-tool-price="<?php echo htmlspecialchars($group['property_price'] ?? ''); ?>"
        data-tool-id="<?php print_r("#" . $group["group_id"]); ?>"
        data-title="<?php echo htmlspecialchars($group['group_name'] ?? ''); ?>"
        data-price="<?php echo htmlspecialchars($group['property_price'] ?? ''); ?>"
        data-price-type="<?php echo htmlspecialchars($group['price_unit'] ?? ''); ?>"
        data-short-title="<?php echo htmlspecialchars($group['short_title'] ?? ''); ?>"
        data-subscription-type="<?php echo htmlspecialchars($group['tool_subscription_type'] ?? ''); ?>"

        data-annual-price="<?php echo htmlspecialchars($group['annual_price'] ?? '0.00'); ?>"

        data-commitment-price="<?php echo htmlspecialchars($group['commitment_price'] ?? '50.00'); ?>"
        data-starts-from-type="<?php echo htmlspecialchars($group['starts_from_type'] ?? 'deposit'); ?>"

        data-implementation-type="<?php echo htmlspecialchars($group['implementation_type'] ?? ''); ?>"
        data-delivery-time="<?php echo htmlspecialchars($group['delivery_time_description'] ?? ''); ?>"
        data-warranty-time="<?php echo htmlspecialchars($group['warranty_time_description'] ?? ''); ?>"
        data-target="#bdgsCheckoutModal"
        data-toggle="modal"
        style="width: 100%; font-weight: 600;">Order Now
    </button>
    <!-- End of Order Now Button -->

    <!-- Loader Modal -->
    <div id="loaderModal" class="modal" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius: 16px; border: none;">
                <div id="loadermodal-body" class="modal-body text-center p-4">
                    <!-- New loader content from newloader.html -->
                    <div class="bdw-loader-container">
                        <svg class="bdw-invoice-icon" viewBox="0 0 100 100">
                            <path class="bdw-doc-outline" d="M20,10 L80,10 Q90,10 90,20 L90,80 Q90,90 80,90 L20,90 Q10,90 10,80 L10,20 Q10,10 20,10 Z" fill="none" />
                            <g class="bdw-doc-lines-group">
                                <line class="bdw-doc-lines" x1="30" y1="35" x2="70" y2="35" />
                                <line class="bdw-doc-lines" x1="30" y1="50" x2="70" y2="50" />
                                <line class="bdw-doc-lines" x1="30" y1="65" x2="50" y2="65" />
                            </g>
                            <path class="bdw-checkmark" d="M35 50 L45 60 L65 40" fill="none" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>

                        <h3 id="loader-headline">Generating Your Invoice...</h3>
                        <p id="loader-subtext">Please wait a moment.</p>

                        <div class="bdw-progress-bar-container mt-3">
                            <div id="loader-bar" class="bdw-progress-bar-fill"></div>
                        </div>
                    </div>

                    <!-- Keep the existing error button -->
                    <button class="btn btn-lg btn-warning mt-3 btn-close-failed" style="display: none;">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Loader Modal -->

    <!-- Checkout Modal -->
    <div id="bdgsCheckoutModal" class="modal fade" role="dialog" tabindex="-1" aria-labelledby="bdgsModalLabel" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header bdgs-modal-header">
                    <div class="bdgs-header-icon"><i class="fa fa-magic"></i></div>
                    <div class="bdgs-header-text">
                        <h4 class="modal-title" id="bdgsModalHeaderTitle">
                            Review & Complete Your Purchase
                        </h4>
                    </div>
                    <button
                        type="button"
                        class="close"
                        data-dismiss="modal"
                        aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">

                    <!-- Initial Modal Content (visible on load) -->
                    <div id="tool-order-now-content" class="tool-order-now" style="display: block;">

                        <?php if (!isset($_COOKIE["userid"])) { ?>
                            <!-- 1. First Step (For logged-out users) -->
                            <!-- This form is rendered instantly, which is correct. -->
                            <!-- Title removed and moved to modal header via JS -->
                            <form class="bdgs-tool-form" action="" id="next">
                                <div class="form-group">
                                    <label for="email">Enter your email</label>
                                    <div class="input-group">
                                        <span class="input-group-addon" id="email"><i class="fa fa-envelope" aria-hidden="true"></i></span>
                                        <input type="email" class="form-control" id="input-email" placeholder="jhon@gmail.com"
                                            aria-describedby="email" required>
                                    </div>
                                    <span style="color: red; display: none; font-size: 14px;" id="error-email">Please enter valid Email</span>
                                </div>
                                <button type="submit" class="btn btn-success" id="next-btn">Next</button>
                            </form>
                            <!-- End First Step -->
                        <?php } ?>

                        <?php if (isset($_COOKIE["userid"])) { ?>
                            <!-- 2. Second Step (For logged-in users) -->
                            <!-- We now render a simple placeholder. -->
                            <!-- The AJAX call is moved to the main <script> block. -->
                            <div class="text-center" id="form-loading-placeholder">
                                <p>Loading your order form...</p>
                                <!-- You can add a spinner icon here -->
                            </div>
                        <?php } ?>

                    </div>
                    <!-- End Initial Modal Content -->

                    <!-- Main Modal Content (hidden by default) -->
                    <div id="mainmodal-content" class="row" style="display: none;">
                        <!-- === Left Column === -->
                        <div class="col-md-7">
                            <div class="bdgs-left-col-wrapper">
                                <!-- --- Main Product Card --- -->
                                <div class="bdgs-card bdgs-product-card">
                                    <div class="row bdgs-product-row">
                                        <!-- Product Image -->
                                        <div class="col-sm-4">
                                            <div class="bdgs-product-image-wrapper">
                                                <?php if ($numrows > 0) {
                                                    $rows = mysql_fetch_assoc($result);
                                                    $file = $rows["file"];
                                                } ?>
                                                <img
                                                    src="/photos/main/<?php echo $file; ?>"
                                                    alt="Tool Preview"
                                                    class="img-responsive" />
                                            </div>
                                        </div>
                                        <!-- Product Info -->
                                        <div class="col-sm-8 bdgs-product-info">
                                            <h3>
                                                <span class="bdgs-title-group">
                                                    <span id="bdgsToolName">Super SEO Booster</span>
                                                    <a
                                                        href=""
                                                        target="_blank"
                                                        title="View Tool Details"
                                                        class="bdgs-view-details-link">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                </span>
                                                <span class="bdgs-tool-id" id="bdgsToolId">#02</span>
                                            </h3>
                                            <p
                                                class="bdgs-product-description"
                                                id="bdgsToolDescription">
                                                This tool seamlessly integrates with your Brilliant
                                                Directories website to boost your SEO.
                                            </p>
                                            <!-- Product Footer: Badges & Price -->
                                            <div class="bdgs-product-footer">
                                                <div class="bdgs-footer-badges">
                                                    <span class="bdgs-footer-badge">
                                                        <div class="bdgs-badge-header">
                                                            <i class="fa fa-cogs"></i>
                                                            <span>Implementation</span>
                                                        </div>
                                                        <span id="bdgs-implementation-type" class="bdgs-badge-label">Full Service</span>
                                                    </span>
                                                    <span class="bdgs-footer-badge">
                                                        <div class="bdgs-badge-header">
                                                            <i class="fa fa-clock-o"></i>
                                                            <span>Delivery Time</span>
                                                        </div>
                                                        <span id="bdgs-delivery-time" class="bdgs-badge-label">5-7 Days</span>
                                                    </span>
                                                    <span class="bdgs-footer-badge">
                                                        <div class="bdgs-badge-header">
                                                            <i class="fa fa-shield"></i>
                                                            <span>Warranty</span>
                                                        </div>
                                                        <span id="bdgs-warranty-time" class="bdgs-badge-label">Lifetime</span>
                                                    </span>
                                                </div>
                                                <div class="bdgs-footer-price" id="bdgsMainToolPrice">
                                                    <span id="bdgs-footer-price-label"></span>
                                                    <span class="bdgs-footer-price-value">$45.00</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- === MOVED: Subscription Options (hidden by default) === -->
                                    <!-- This div is now empty, the dropdown was moved to the right column -->
                                    <!-- === END MOVED === -->

                                    <!-- Product Terms -->
                                    <span class="bdgs-product-terms">
                                        <!-- Disclaimer text injected by JS -->
                                    </span>
                                </div>

                                <!-- --- Support Info Card: Guarantee --- -->
                                <div class="bdgs-card bdgs-support-block">
                                    <i class="fa fa-shield"></i>
                                    <div>
                                        <strong>Your purchase is governed by our licensing terms</strong>
                                        <span>Single Domain-Locked Commercial License (SDCL v1.0)
                                            This product is licensed for use on one specific domain only. Redistribution, resale, or use on multiple domains is not permitted.
                                            A separate license must be purchased for each additional website.
                                            <a target="_blank" href="https://bdgrowthsuite.com/license/sdcl-v1">Full License terms<i class="fa fa-external-link"></i></a></span>
                                    </div>
                                </div>

                                <!-- --- Support Info Card: Help --- -->
                                <div class="bdgs-card bdgs-support-block">
                                    <i class="fa fa-question-circle"></i>
                                    <div>
                                        <strong>Need Help or Have Questions?</strong>
                                        <span class="bdgs-support-text">Contact our team at
                                            <a href="mailto:support@businesslabs.org">support@businesslabs.org</a>
                                            <!-- This span will be shown/hidden by JS -->
                                            <span id="bdgsBookCallWrapper" style="display: none;"> or <a href="https://book.businesslabs.org/" target="_blank" class="bdgs-book-link">book a call</a> with one of our specialists.</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- === Right Column === -->
                        <div class="col-md-5">
                            <div class="bdgs-right-col-wrapper">
                                <!-- --- Order Summary Card --- -->
                                <div class="bdgs-card bdgs-order-summary">
                                    <h3 class="bdgs-order-summary-title">Order Summary</h3>

                                    <!-- === NEW: Subscription Options (Moved here) === -->
                                    <!-- Updated to use Tabs instead of Dropdown -->
                                    <div id="bdgsSubscriptionOptions" class="form-group" style="display: none; padding: 0 0 15px 0; margin-bottom: 15px; border-bottom: 1px dashed #eee;">
                                        <label style="font-weight: bold; font-size: 15px; display: block; margin-bottom: 10px;">Subscription Plan:</label>
                                        <div id="subscriptionTypeTabs" class="bdgs-subscription-tabs">
                                            <!-- Tabs will be added by JS -->
                                        </div>
                                    </div>
                                    <!-- === END NEW === -->

                                    <!-- Order Item List -->
                                    <div id="bdgsOrderItemsList">
                                        <!-- Items added via JS -->
                                    </div>

                                    <!-- Order Totals -->
                                    <div class="bdgs-order-totals">
                                        <div class="bdgs-price-row">
                                            <span>Sub Total</span>
                                            <span id="bdgsSummarySubtotal">$0.00</span>
                                        </div>
                                        <div class="bdgs-price-row bdgs-price-row-discount" style="display: none;">
                                            <span>Discount</span>
                                            <span id="bdgsSummaryDiscount">$0.00</span>
                                        </div>
                                        <div class="bdgs-price-row bdgs-price-row-total">
                                            <span>Total</span>
                                            <span id="bdgsSummaryTotal">$0.00</span>
                                        </div>
                                    </div>

                                    <!-- Checkout Button -->
                                    <button
                                        class="btn btn-block btn-lg bdgs-checkout-btn"
                                        id="bdgsProceedBtn">
                                        Proceed to Checkout
                                    </button>

                                    <!-- Trust Badges -->
                                    <p class="bdgs-secure-text">
                                        <i class="fa fa-lock"></i>Secure payment • Encrypted checkout
                                    </p>
                                    <div class="bdgs-trust-badges">
                                        <i class="fa fa-cc-visa" title="Visa"></i>
                                        <i class="fa fa-cc-mastercard" title="MasterCard"></i>
                                        <i class="fa fa-cc-stripe" title="Stripe"></i>
                                        <img
                                            src="https://businesslabs.org/bootcamps/tech-training/assets/norton.png"
                                            alt="Norton Secured"
                                            title="Norton Secured" />
                                        <img
                                            src="https://businesslabs.org/bootcamps/tech-training/assets/mac-safe.png"
                                            alt="McAfee Secure"
                                            title="McAfee Secure" />
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <!-- End Main Modal Content -->

                    <!-- Thank You Section (hidden by default) -->
                    <!-- NOTE: This section is now used for 'free' or 'Ask for quote' tools. -->
                    <div class="bdw-thank-you" id="bdwThankYou" style="display: none;">
                        <div class="bdw-thank-you-img-holder">

                            <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                                <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none" />
                                <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8" />
                            </svg>

                        </div>
                        <h2 class="bdw-thank-you-title" id="bdwThankYouTitle">Thank You!</h2>
                        <div class="bdw-thank-you-message" id="bdwThankYouMessage">
                            <!-- Dynamic content will be injected here -->
                        </div>
                        <div class="bdw-thank-you-actions" id="bdwThankYouActions">
                            <!-- Thank you actions will be inserted here by JavaScript -->
                        </div>
                    </div>
                    <!-- End Thank You Section -->

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Main Checkout Modal -->

    <script>
        // REFACTOR: This function shows an error state in the loader modal
        function showLoaderError(headline, subtext) {
            // $('#loader-icon').text("❌"); // Icon is SVG based now
            $('.bdw-checkmark').css('display', 'none'); // Hide checkmark
            $('.bdw-doc-lines-group').css('display', 'none'); // Hide lines

            $('#loader-headline').text(headline || "An Error Occurred");
            $('#loader-subtext').css('opacity', '0');
            setTimeout(() => {
                $('#loader-subtext').html("<small>" + (subtext || "Please try again.") + "</small>");
                $('#loader-subtext').css('opacity', '1');
            }, 300);
            $('.bdw-progress-bar-fill').css('background-color', '#fa537c');
            updateProgressBar(100); // Fill bar to show error

            // Show a close button
            $('#loaderModal .btn-close-failed').show().on('click', function() {
                $('#loaderModal').modal('hide');
                // Reset button for next time
                $(this).off('click').hide();
                // Reset progress bar color and state
                $('.bdw-progress-bar-fill').css('background-color', ''); // Resets to CSS default
                $('.bdw-checkmark').css('display', 'none');
                $('.bdw-doc-lines-group').css('display', 'block');
                updateProgressBar(0); // Reset progress
            });
        }

        // RE-ADDED: This function shows a step in the loader modal
        function showLoaderStep(headline, subtext, progress) {
            // Reset icon state for a new step
            $('.bdw-checkmark').css('display', 'none');
            $('.bdw-doc-lines-group').css('display', 'block');

            $('#loader-headline').text(headline);
            $('#loader-subtext').css('opacity', '0');
            setTimeout(() => {
                $('#loader-subtext').html("<small>" + subtext + "</small>");
                $('#loader-subtext').css('opacity', '1');
            }, 300);
            if (progress) {
                updateProgressBar(progress);
            }
        }

        /*
         REMOVED: const steps = [...]
         This array was for the old multi-step invoice loader, which is no longer used.
        */

        /*
         REMOVED: function hideLoader()
         This function is no longer necessary.
        */

        let currentProgress = 0;

        function updateProgressBar(newProgress) {
            if (newProgress < currentProgress) { // Resetting progress
                $('.bdw-progress-bar-fill').css('transition', 'none');
                $('.bdw-progress-bar-fill').css('width', '0%');
                currentProgress = 0;
                // Force reflow
                $('.bdw-progress-bar-fill')[0].offsetHeight;
                $('.bdw-progress-bar-fill').css('transition', 'width 0.5s ease-in-out');
            }

            if (newProgress > currentProgress) {
                currentProgress = newProgress;
                $('#loader-bar').css('width', currentProgress + '%');

                // Show checkmark when complete
                if (newProgress >= 100) {
                    // Don't show checkmark automatically unless it's a success
                    // showLoaderError handles its own state
                }
            }
        }
    </script>

    <script>
        $(document).ready(function() {

            // === NEW HELPER FUNCTION ===
            function updateModalHeader(title) {
                $('#bdgsModalHeaderTitle').text(title);
            }

            function updateModalHeaderIcon(newIconClassName) {
                // Find the <i> tag within the specified container
                const $iconElement = $('.bdgs-header-icon i');

                if ($iconElement.length > 0) {
                    // Set the 'class' attribute directly.
                    // This replaces all previous classes with the new ones.
                    // We always include "fa" as the base class for Font Awesome 4.7.
                    $iconElement.attr('class', 'fa ' + newIconClassName);
                } else {
                    console.warn('Could not find element .bdgs-header-icon i to update.');
                }
            }
            // === END NEW HELPER FUNCTION ===

            // --- Modal State Variables ---
            var mainToolInfo = {};
            let selectedNewItems = [];
            var basePrice = 0;
            var currentTotal = 0;
            var isProcessing = false;
            /*
             REMOVED: let selectedNewItems = [];
             This was for the old PMP invoice.
            */
            var discount = 0.0; // Placeholder for future discount logic
            let rawPrice = $('#tool-order-now').data('price'); // "$950.00"
            let numericPrice = parseFloat(
                String(rawPrice).replace(/[^0-9.]/g, '') // remove $, commas, spaces
            );
            numericPrice = numericPrice.toFixed(2);
            const currentDate = new Date().toISOString().split("T")[0];
            var subtotal = 0;
            const tools_pars_url = '<?php echo $pars[0]; ?>';

            /*
             REMOVED: const toolData = { ... }
             This object was primarily used by the old showThankYou function
             and is now redundant. mainToolInfo is used instead.
            */

            $("#sidebar_ordernow").click(function(e) {
                // Prevent any default action the button might have
                e.preventDefault();

                // Find the main "Order Now" button and programmatically click it.
                // This will trigger the modal and pass all the correct data-attributes
                // that your existing JavaScript already knows how to handle.
                $("#tool-order-now").click();
            });

            var modalObserver;
            var $modal = $('#bdgsCheckoutModal');
            var $modalBody = $modal.find('.modal-body');

            // --- 1. Create a function to check and set padding ---
            // We will call this function repeatedly.
            function checkModalPadding() {

                // Find the element *every time* in case it was just added
                var $toolContent = $modalBody.find('#tool-order-now-content');
                var $formmargin = $modalBody.find('.bdgs-tool-form');

                // --- This is your logic ---
                // Check if content exists AND is visible (display: 'block')
                if ($toolContent.length > 0 && $toolContent.css('display') === 'block') {

                    // --- Condition Met: (tool-order-now-content is visible) ---
                    // Set padding to 0
                    $modalBody[0].style.setProperty('padding', '0', 'important');
                    $formmargin.css('margin-block', '0', 'important');
                } else {

                    // --- Condition Fails: (mainmodal-content is visible) ---
                    // Set padding to 30px
                    $modalBody[0].style.setProperty('padding', '30px');
                    $formmargin.css('margin-block', '20px');
                }
            }

            // --- 2. Create the Observer ---
            // This observer will call checkModalPadding() on ANY change
            modalObserver = new MutationObserver(function(mutations) {
                // A mutation (change) was detected! Re-run the check.
                checkModalPadding();
            });

            // --- 3. Start Observer when Modal Opens ---
            $modal.on('shown.bs.modal', function(e) {
                // A) Run the check immediately for the initial state
                checkModalPadding();

                // B) Start the observer to watch for dynamic changes
                // We watch for:
                // - attributes: Catches style="display: block"
                // - childList: Catches content being loaded via AJAX
                // - subtree: Ensures we catch changes on all child elements
                modalObserver.observe($modalBody[0], {
                    attributes: true,
                    childList: true,
                    subtree: true
                });
            });

            // --- 4. Stop Observer when Modal Closes (Very Important!) ---
            $modal.on('hidden.bs.modal', function(e) {
                // A) Stop watching to prevent memory leaks
                if (modalObserver) {
                    modalObserver.disconnect();
                }

                // B) Always reset padding for a clean state next time
                $modalBody[0].style.setProperty('padding', '30px');
            });

            // --- NEW: Disclaimer Text Content ---
            // (Adapted from bdgs-tools-checkout-all.html)
            const DISCLAIMERS = {
                free: "💡 <strong>Fully free as described</strong>. Any requests for <i>extra features, new ideas, or additional custom work</i> will be billed separately.",
                fixed_price: "💡 <strong>Fixed price</strong> covers the described service. Any requests for <i>extra features, new ideas, or additional custom work</i> will be billed separately.",
                subscription: "💡 <strong>Subscription</strong> covers listed inclusions. Any requests for <i>extra features, new ideas, or additional custom work</i> will be billed separately.",
                ask_for_quote: "💡 <strong>No upfront payment</strong>. Work starts only after you approve the quote and make the payment — 100% risk-free (full refund if we can’t deliver).",
                starts_from: "💡 <strong>Base price</strong> covers listed inclusions. If no extras are needed, simply reply to our email or contact <strong>support@bdgrowthsuite.com</strong> and we’ll implement. If extras are needed, email or reply — we’ll review, meet if needed, and quote for the additional work.",
                // --- MODIFICATION: Added deposit disclaimer ---
                starts_from_deposit: "💡 In many BDGS tools, the full base price is charged before work begins. But due to the nature of this service, we only charge ${DEPOSIT_PRICE} upfront. This small commitment covers a short consulting or brainstorming call to discuss your ideas, share ours, and finalize what will be done.<br>The ${DEPOSIT_PRICE} is fully credited toward your final project total if you proceed."
            };

            let toolprice_type = $('#tool-order-now').data('price-type');


            if (toolprice_type == "free") {
                $('#bdgs-footer-price-label').text("Free");
            } else if (toolprice_type == "fixed_price") {
                $('#bdgs-footer-price-label').text('Fixed Price:');
            } else if (toolprice_type == "starts_from") {
                $('#bdgs-footer-price-label').text('Starts From:');
            } else if (toolprice_type == "subscription") {
                $('#bdgs-footer-price-label').text('Subscription:');
            } else if (toolprice_type == "ask_for_quote") {
                $('#bdgs-footer-price-label').text('Price:');
            }

            /**
             * Formats a number as USD currency.
             * @param {number} amount - The number to format.
             * @returns {string} - The formatted currency string (e.g., "$45.00").
             */
            function formatCurrency(amount) {
                if (typeof amount !== "number" || isNaN(amount)) {
                    amount = 0;
                }
                return "$" + amount.toFixed(2);
            }


            /**
             * Funtion to Update Disclamier in the footer terms and conditions
             */
            // --- MODIFICATION: Replaced this entire function ---
            function updateDisclamier(disclaimerKey) {
                let disclaimerText = DISCLAIMERS[disclaimerKey] || DISCLAIMERS.fixed_price;

                // Special handling for deposit price replacement
                if (disclaimerKey === 'starts_from_deposit' && mainToolInfo && mainToolInfo.commitmentPrice > 0) {
                    var depositPriceFormatted = '<strong>' + formatCurrency(mainToolInfo.commitmentPrice) + '</strong>';
                    disclaimerText = disclaimerText.split('${DEPOSIT_PRICE}').join(depositPriceFormatted);
                }
                mainToolInfo.disclaimerText = disclaimerText;

                console.log('disclaimerKey:', disclaimerKey);
                console.log('mainToolInfo:', mainToolInfo);
                console.log(DISCLAIMERS.starts_from_deposit.includes('${DEPOSIT_PRICE}'));
                console.log('disclaimerText:', depositPriceFormatted);

                $('.bdgs-product-terms').html(disclaimerText);
                $('#bdgs-checkout-disclaimer').html(disclaimerText);
            }
            // --- END MODIFICATION ---


            /**
             * Creates the HTML for a single item in the order summary.
             * @param {string} id - The unique ID of the item.
             * @param {string} name - The display name of the item.
             * @param {number} price - The price of the item.
             * @param {boolean} [isMainItem=false] - Whether this is the main, non-removable item.
             * @returns {string} - The HTML string for the order item row.
             */

            /*cuurently ignore below function until add cart works*/
            function createOrderItemHtml(id, name, price, isMainItem = false) {
                const rowClass = isMainItem ?
                    "bdgs-order-item bdgs-main-item" :
                    "bdgs-order-item";
                const dataAttribute = `data-id="${id}"`;
                // UPDATE: Handle 'Free' display
                const priceDisplay = (price === 0 && (mainToolInfo.paymentUnit === 'free' || mainToolInfo.paymentUnit === 'ask_for_quote')) ? 'Free' : formatCurrency(price);

                if (mainToolInfo.paymentUnit === 'starts_from' && mainToolInfo.startsFromType === 'deposit') {
                    return `
                        <div class="${rowClass}" ${dataAttribute}>
                            <div class="bdgs-item-details">
                                <span class="bdgs-item-name">Commitment Fee</span>
                                <span class="bdgs-item-value">${name}</span>
                            </div>
                            <span class="bdgs-item-price">${priceDisplay}</span>
                            <button class="bdgs-remove-item-btn" aria-label="Remove item">&times;</button>
                        </div>`;
                } else {

                    return `
                        <div class="${rowClass}" ${dataAttribute}>
                            <div class="bdgs-item-details">
                                <span class="bdgs-item-name">${name}</span>
                            </div>
                            <span class="bdgs-item-price">${priceDisplay}</span>
                            <button class="bdgs-remove-item-btn" aria-label="Remove item">&times;</button>
                        </div>`;
                }
            }

            function showThankYou(toolInfo) {
                // This function now uses the new toolInfo object and populates the existing #bdwThankYou div

                showSection('#bdwThankYou'); // This function already exists and hides other sections

                // === NEW CODE: Update modal header for thank you step ===
                updateModalHeader("Order Confirmed — You’re All Set!");
                // === END NEW CODE ===

                var title = 'Order Received!';
                var mainText = '';

                // --- UNIFIED CONTENT ---
                // This help HTML is now consistent with showDynamicThankYou
                var helpHtml = `
                    <div class="bdgs-swal-help">
                        <strong>Need Help? <a href="mailto:support@businesslabs.org">support@businesslabs.org</a></strong>
                    </div>
                `;

                // This switch block is copied from showDynamicThankYou to unify logic
                switch (toolInfo.paymentUnit) {

                    // FIXED
                    case 'fixed':
                        title = 'Payment Successful!';
                        mainText = `
                            Your payment for "<strong>${toolInfo.name}</strong>" was successful.
                            You’ll receive a confirmation email within 1–2 business days (usually within a few hours) that includes setup instructions.
                            You can complete the setup yourself, or simply reply if you’d like our team to handle installation at no extra cost.
                        `;
                        break;

                        // SUBSCRIPTION
                    case 'subscription':
                        title = 'Payment Successful!';
                        mainText = `
                            Your payment for "<strong>${toolInfo.name}</strong>" was successful, and your subscription is now active.
                            Our onboarding team will contact you within 2 business days (typically within hours) with setup and activation details.
                            You’ll continue receiving updates, improvements, and ongoing support while your subscription remains active.
                        `;
                        break;

                        // STARTS FROM (FULL PAY)
                    case 'starts-from-base':
                        title = 'Payment Successful!';
                        mainText = `
                            Your payment for "<strong>${toolInfo.name}</strong>" has been received.
                            Your project slot is now locked in—no approval or meeting is needed.
                            Our team will reach out within 2 business days (usually within hours) with setup instructions and next steps.
                        `;
                        break;

                        // STARTS FROM (DEPOSIT / COMMITMENT)
                    case 'starts-from-deposit':
                        title = 'Payment Received!';
                        mainText = `
                            Your $50 commitment payment for "<strong>${toolInfo.name}</strong>" was successful.
                            You’ll receive an email shortly with a link to schedule your discovery call with our senior design team.
                            During that session, we’ll discuss your goals and finalize your quote—your $50 fee will be fully credited toward your total if you proceed.
                        `;
                        break;

                        // FREE
                    case 'free':
                        title = 'Order Received!';
                        mainText = `
                            Your order for "<strong>${toolInfo.name}</strong>" has been received.<br>You’ll receive an email within <strong>2 business days</strong> (usually under 24 hours) with an easy-to-follow setup guide.<br>You can follow the guide to install it yourself, or simply reply to that email if you'd like our team to handle the installation for you at no extra cost.
                        `;
                        break;

                        // ASK FOR QUOTE
                    case 'quote': // Keep this case if 'quote' is still used
                    case 'ask_for_quote':
                        title = 'Order Received!';
                        mainText = `
                            Your request for "<strong>${toolInfo.name}</strong>" has been received.<br>
                            Our senior team will review your requirements and send a personalized quote within 2 business days (often the same day).<br>
                            You’ll also receive a link to schedule a discovery call if we need more details.
                        `;
                        break;

                        // DEFAULT
                    default:
                        title = 'Order Received!';
                        mainText = `
                            Your order for "<strong>${toolInfo.name}</strong>" has been received.
                            Our team will reach out within 2 business days (usually within hours) to confirm next steps.
                        `;
                }

                // This HTML structure is now mirrored in showThankYou
                var swalHtml = `
                    <div class="bdgs-swal-container">
                        <p>${mainText}</p>
                        
                        <div class="bdgs-swal-disclaimer">
                            ${toolInfo.disclaimerText}
                        </div>
                        
                        <hr style="border-top: 1px dashed #ccc; margin: 25px 0;">
                        
                        ${helpHtml}
                    </div>
                `;
                // --- END UNIFIED CONTENT ---

                Swal.fire({
                    title: title,
                    html: swalHtml,
                    allowOutsideClick: false,
                    icon: 'success',
                    confirmButtonText: 'Great',
                    confirmButtonColor: '#E74D56',
                    width: '700px',
                    showCloseButton: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#bdgsCheckoutModal').modal('hide');
                        window.location.reload();

                    }
                });
            }

            function showDynamicThankYou(toolInfo) {
                var title = 'Order Received!';
                var mainText = '';

                // --- UNIFIED CONTENT ---
                // This help HTML is now consistent with showThankYou
                var helpHtml = `
                    <div class="bdgs-swal-help">
                        <strong>Need Help? <a href="mailto:support@businesslabs.org">support@businesslabs.org</a></strong>
                    </div>
                `;

                // This switch block is the source of truth for unified logic
                switch (toolInfo.paymentUnit) {

                    // FIXED
                    case 'fixed':
                        title = 'Payment Successful!';
                        mainText = `
                            Your payment for "<strong>${toolInfo.name}</strong>" was successful.
                            You’ll receive a confirmation email within 1–2 business days (usually within a few hours) that includes setup instructions.
                            You can complete the setup yourself, or simply reply if you’d like our team to handle installation at no extra cost.
                        `;
                        break;

                        // SUBSCRIPTION
                    case 'subscription':
                        title = 'Payment Successful!';
                        mainText = `
                            Your payment for "<strong>${toolInfo.name}</strong>" was successful, and your subscription is now active.
                            Our onboarding team will contact you within 2 business days (typically within hours) with setup and activation details.
                            You’ll continue receiving updates, improvements, and ongoing support while your subscription remains active.
                        `;
                        break;

                        // STARTS FROM (FULL PAY)
                    case 'starts-from-base':
                        title = 'Payment Successful!';
                        mainText = `
                            Your payment for "<strong>${toolInfo.name}</strong>" has been received.
                            Your project slot is now locked in—no approval or meeting is needed.
                            Our team will reach out within 2 business days (usually within hours) with setup instructions and next steps.
                        `;
                        break;

                        // STARTS FROM (DEPOSIT / COMMITMENT)
                    case 'starts-from-deposit':
                        title = 'Payment Received!';
                        mainText = `
                            Your $50 commitment payment for "<strong>${toolInfo.name}</strong>" was successful.
                            You’ll receive an email shortly with a link to schedule your discovery call with our senior design team.
                            During that session, we’ll discuss your goals and finalize your quote—your $50 fee will be fully credited toward your total if you proceed.
                        `;
                        break;

                        // FREE
                    case 'free':
                        title = 'Order Received!';
                        mainText = `
                            Your order for "<strong>${toolInfo.name}</strong>" has been received.<br>You’ll receive an email within <strong>2 business days</strong> (usually under 24 hours) with an easy-to-follow setup guide.<br>You can follow the guide to install it yourself, or simply reply to that email if you'd like our team to handle the installation for you at no extra cost.
                        `;
                        break;

                        // ASK FOR QUOTE
                    case 'quote': // Keep this case if 'quote' is still used
                    case 'ask_for_quote':
                        title = 'Order Received!';
                        mainText = `
                            Your request for "<strong>${toolInfo.name}</strong>" has been received.<br>
                            Our senior team will review your requirements and send a personalized quote within 2 business days (often the same day).<br>
                            You’ll also receive a link to schedule a discovery call if we need more details.
                        `;
                        break;

                        // DEFAULT
                    default:
                        title = 'Order Received!';
                        mainText = `
                            Your order for "<strong>${toolInfo.name}</strong>" has been received.
                            Our team will reach out within 2 business days (usually within hours) to confirm next steps.
                        `;
                }

                // This HTML structure is now mirrored in showThankYou
                var swalHtml = `
                    <div class="bdgs-swal-container">
                        <p>${mainText}</p>
                        
                        <div class="bdgs-swal-disclaimer">
                            ${toolInfo.disclaimerText}
                        </div>
                        
                        <hr style="border-top: 1px dashed #ccc; margin: 25px 0;">
                        
                        ${helpHtml}
                    </div>
                `;
                // --- END UNIFIED CONTENT ---

                Swal.fire({
                    title: title,
                    html: swalHtml,
                    allowOutsideClick: false,
                    icon: 'success',
                    confirmButtonText: 'Great',
                    confirmButtonColor: '#E74D56',
                    width: '700px',
                    showCloseButton: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#bdgsCheckoutModal').modal('hide');
                        window.location.reload();

                    }
                });
            }

            function showSection(sectionId) {
                // Hide all sections first — add the hidden class and also set inline display:none
                // so we reliably hide them even when elements have inline styles.
                var $sections = $('#tool-order-now-content, #mainmodal-content, #bdwThankYou');
                $sections.addClass('hidden').css('display', 'none');

                // Reset checkmark animation
                $('svg.checkmark').removeClass('active');

                // Show the requested section — remove the class and set inline display:block
                // after a short defer so other handlers can run first.
                setTimeout(function() {
                    $(sectionId).removeClass('hidden').css('display', 'block');
                }, 50);

            }

            /**
             * Recalculates all prices in the order summary and updates the DOM.
             */
            function updateSummary() {

                // Reset subtotal
                subtotal = 0;

                // Loop over each item in the list to calculate subtotal
                $("#bdgsOrderItemsList .bdgs-order-item").each(function() {
                    var priceText = $(this).find(".bdgs-item-price").text();
                    // Handle "Free" text
                    if (priceText.toLowerCase() === 'free') {
                        priceText = '$0';
                    }
                    var price = parseFloat(priceText.replace(/[^0-9.]/g, ""));
                    if (!isNaN(price)) {
                        if (mainToolInfo.paymentUnit === 'subscription') {
                            if (mainToolInfo.selectedSubscriptionType === 'annually' && mainToolInfo.subscriptionType === 'Monthly,Annually') {
                                subtotal += mainToolInfo.price * 12;
                            } else if (mainToolInfo.selectedSubscriptionType === 'annually' && mainToolInfo.subscriptionType === 'Annually') {
                                subtotal += mainToolInfo.annualPrice;
                            } else if (mainToolInfo.selectedSubscriptionType === 'monthly') {
                                subtotal += mainToolInfo.price;
                            }
                        } else {
                            subtotal += price;
                        }
                    }
                });


                currentTotal = subtotal - discount;

                // Update DOM elements
                $("#bdgsSummarySubtotal").text(formatCurrency(subtotal));
                $("#bdgsSummaryDiscount").text((discount > 0 ? "-" : "") + formatCurrency(discount));

                // === START CHANGE ===
                var totalText = formatCurrency(currentTotal);
                if (mainToolInfo.paymentUnit === 'subscription') {
                    // Use a span with smaller font size and lighter color
                    var suffixStyle = 'font-size: 13px; color: #999; font-weight: normal; margin-left: 5px;';

                    if (mainToolInfo.selectedSubscriptionType === 'annually') {
                        totalText = totalText + ' <span style="' + suffixStyle + '">/ billed Annually</span>';
                    } else {
                        totalText = totalText + ' <span style="' + suffixStyle + '">/ billed Monthly</span>';
                    }
                }
                // Use .html() to render the span tags
                $("#bdgsSummaryTotal").html(totalText);
                // === END CHANGE ===

                // Show/hide discount row if discount is applied
                // === FIX: Use .toggle(discount > 0) ===
                $(".bdgs-order-totals .bdgs-price-row-discount").toggle(discount >= 0);
            }

            /**
             * Event handler for when the modal is about to be shown.
             * Resets the modal to its default state and populates it with data
             * from the button that triggered it.
             */
            $("#bdgsCheckoutModal").on("show.bs.modal", function(event) {
                var button = $(event.relatedTarget); // Button that triggered the modal

                /*
                 REMOVED: selectedNewItems = [];
                 REMOVED: selectedNewItems.push(...);
                 This logic was for the old PMP invoice.
                */

                // Get numeric price
                let rawPrice = button.data('price');
                let numericPrice = parseFloat(String(rawPrice).replace(/[^0-9.]/g, '')) || 0;
                let priceType = button.data("price-type") || 'fixed_price';

                /*
                 REMOVED: selectedNewItems.push(...)
                 This logic was for the old PMP invoice.
                */

                // Get tool info from data-* attributes
                // --- MODIFICATION: Added commitmentPrice, startsFromType, and priceToCharge ---
                mainToolInfo = {
                    post_name: button.data("tool-name") || "",
                    name: button.data("short-title") || "This Tool",
                    subtitle: button.data("tool-subtitle") ||
                        "Enhance your Brilliant Directories website.",
                    id: button.data("tool-id") || "#??",
                    price: numericPrice, // Use the cleaned float (This is MONTHLY price for subscriptions)
                    warranty: button.data("warranty-time") || "6 months",
                    delivery: button.data("delivery-time") || "24 hours",
                    implementation: button.data("implementation-type") || "Instant",
                    paymentUnit: priceType,
                    commitmentPrice: parseFloat(button.data("commitment-price")) || 0,
                    startsFromType: button.data("starts-from-type") || 'base',
                    origin_url: button.data("post_link"),
                    post_url: button.data("post_link"),
                    // === NEW: Read subscription data ===
                    subscriptionType: (button.data("subscription-type") || '').trim(), // 'Monthly', 'Annually', 'Monthly,Annually'
                    // === REMOVED monthlyPrice, mainToolInfo.price is now the monthly price ===
                    annualPrice: parseFloat(button.data("annual-price")) || 0,
                    selectedSubscriptionType: '', // Will be set to 'monthly' or 'annually'
                    // === END NEW ===

                    priceToCharge: numericPrice, // Default priceToCharge to full price
                    disclaimerText: '',
                    post_id: button.data("postid"),
                    subtotal: subtotal,
                    discount: discount,
                    total: currentTotal,
                };

                // === MODIFIED: Populate selectedNewItems based on pricing type (excluding free/quote) ===
                if (priceType !== 'free' && priceType !== 'ask_for_quote') {

                    // Default values
                    let itemDescription = button.data('title') || 'Untitled Tool';
                    let itemLongDescription = "This professional tool provides advanced functionality to enhance your business operations.";
                    let itemRate = numericPrice; // Default rate

                    if (priceType === 'starts_from' && mainToolInfo.startsFromType === 'deposit' && mainToolInfo.commitmentPrice > 0) {
                        // --- STARTS FROM (DEPOSIT) LOGIC ---
                        itemRate = mainToolInfo.commitmentPrice;
                        itemDescription = "Consulting Fee to Discuss Tool/Service: " + (mainToolInfo.name || '');

                        let startsFromPrice = formatCurrency(numericPrice);
                        let toolUrl = button.data("post_link");
                        let toolName = mainToolInfo.name || 'this tool';

                        itemLongDescription =
                            "Starts From: " + startsFromPrice + "\n" +
                            "Learn more: " + toolUrl + "\n\n" +
                            "This consulting fee covers our time for reviewing your requirements, understanding your goals, and discussing the full scope of " + toolName + ".\n\n" +
                            "If you choose to move forward after receiving our quote, the full amount of this consulting fee will be credited to your final invoice — you only pay the remaining difference (if any).\n" +
                            "If you decide not to proceed, all clarity, ideas, and strategic direction shared during the session are yours to keep.";

                    } else if (priceType === 'subscription') {
                        // --- SUBSCRIPTION LOGIC ---

                        // Determine the default frequency to show correct info initially
                        let subType = mainToolInfo.subscriptionType;
                        let isAnnualDefault = (subType === 'Annually');
                        // Note: 'Monthly,Annually' defaults to Monthly logic later, so we assume Monthly here unless strictly Annual

                        let freq = isAnnualDefault ? 'Annually' : 'Monthly';
                        // Use correct price for summary item
                        itemRate = isAnnualDefault ? mainToolInfo.annualPrice : mainToolInfo.price;

                        // Calculate renewal date (1 month or 1 year from now)
                        let renDate = new Date();
                        if (isAnnualDefault) {
                            renDate.setFullYear(renDate.getFullYear() + 1);
                        } else {
                            renDate.setMonth(renDate.getMonth() + 1);
                        }
                        let renDateStr = renDate.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });

                        itemDescription = "Subscription for " + mainToolInfo.name;

                        itemLongDescription =
                            "Subscription Plan: " + formatCurrency(itemRate) + " (" + freq + ")\n" +
                            "Learn more: " + button.data("post_link") + "\n\n" +
                            "This subscription includes all standard features listed on the landing page for " + mainToolInfo.name + ".\n" +
                            "Your plan renews on: " + renDateStr + "\n\n" +
                            "If you need any clarification, feel free to reply to the subscription email—our team will do our best to understand your needs and assist you.\n\n" +
                            "Enhancements from our Ideas List are released periodically based on feasibility, demand, and priority.\n" +
                            "If you require a specific, urgent, or fully custom feature, we can prepare a separate quote.";

                    } else if (priceType === 'fixed_price') {
                        // --- FIXED PRICE LOGIC ---
                        itemDescription = "Payment for " + mainToolInfo.name;
                        itemLongDescription = "Fixed Price: " + formatCurrency(mainToolInfo.price) + "\n" +
                            "Learn more: " + button.data("post_link") + "\n\n" +
                            "This payment covers everything shown as included on the landing page for " + mainToolInfo.name + ".\n" +
                            "If you need extra features, new ideas, or additional custom work, those items will be quoted and billed separately.\n\n" +
                            "Implementation begins immediately once we receive Admin access to your BD website, and after confirming any requested extras (if applicable).";
                    } else if (priceType === 'starts_from') {
                        // --- STARTS FROM (BASE PRICE) LOGIC ---
                        itemDescription = "Payment for " + mainToolInfo.name;
                        itemLongDescription = "Starts From: " + formatCurrency(mainToolInfo.price) + "\n" +
                            "Learn more: " + button.data("post_link") + "\n\n" +
                            "This payment covers the standard version of " + mainToolInfo.name + ", including everything listed as “included” on the landing page. Any item marked as “additional,” “priced extra,” or otherwise excluded on the landing page will be quoted separately.\n\n" +
                            "Most projects fit fully within the base scope. If your final requirements need additional work beyond what the landing page outlines, we’ll issue a separate invoice only for the difference.";
                    }

                    selectedNewItems.push({
                        id: button.data('postid') || 'N/A',
                        description: itemDescription,
                        long_description: itemLongDescription,
                        qty: 1,
                        rate: itemRate,
                        order: selectedNewItems.length + 1,
                        tool_type: toolprice_type,
                        unit: '',
                        taxname: [],
                    });
                }
                // === END MODIFIED ===

                // --- END MODIFICATION ---
                basePrice = mainToolInfo.price;

                var modal = $(this);

                // === NEW: Hide subscription dropdown by default (it's in the summary now) ===
                modal.find("#bdgsSubscriptionOptions").hide();
                // === END NEW ===


                // === NEW: Reset/Set initial header title ===
                var isLoggedIn = <?php echo isset($_COOKIE["userid"]) ? 'true' : 'false'; ?>;

                if (!isLoggedIn) {
                    updateModalHeader("Let’s Get Started — Enter Your Email");
                } else if (mainToolInfo.paymentUnit === 'ask_for_quote') {
                    updateModalHeader("Submit Your Estimate Request");
                    updateModalHeaderIcon('fa-file-text-o');
                } else {
                    updateModalHeader("Review & Complete Your Purchase");
                }
                // === END NEW ===

                // Populate main product card
                modal.find("#bdgsToolName").text(mainToolInfo.name);
                modal.find("#bdgsToolDescription").text(mainToolInfo.subtitle);
                modal.find("#bdgsToolId").text(mainToolInfo.id);
                modal.find('#bdgs-warranty-time').text(mainToolInfo.warranty);
                modal.find('#bdgs-delivery-time').text(mainToolInfo.delivery);
                modal.find('#bdgs-implementation-type').text(mainToolInfo.implementation);

                // --- MODIFICATION: Replaced price logic block ---
                let priceLabel = "";
                let priceValue = formatCurrency(mainToolInfo.price);
                var showSummary = true;
                var showBookCall = false;
                var disclaimerKey = 'fixed_price'; // Default disclaimer key
                var orderItemName = mainToolInfo.name;
                // mainToolInfo.priceToCharge is already set to mainToolInfo.price

                if (priceType == "free") {
                    priceLabel = "Price:";
                    priceValue = "Free";
                    disclaimerKey = 'free';
                    mainToolInfo.priceToCharge = 0;
                } else if (priceType == "fixed_price") {
                    priceLabel = "Fixed Price:";
                    disclaimerKey = 'fixed_price';
                } else if (priceType == "starts_from") {
                    showBookCall = true; // Show book a call for both starts_from types

                    if (mainToolInfo.startsFromType === 'deposit' && mainToolInfo.commitmentPrice > 0) {
                        // --- DEPOSIT LOGIC ---
                        priceLabel = "Starts From:"; // Shows the full price, e.g., "$1500"
                        priceValue = formatCurrency(mainToolInfo.price);
                        disclaimerKey = 'starts_from_deposit';

                        orderItemName = mainToolInfo.name;
                        mainToolInfo.priceToCharge = mainToolInfo.commitmentPrice; // Charge the deposit

                    } else {
                        // --- BASE LOGIC ---
                        priceLabel = "Starts From:";
                        priceValue = formatCurrency(mainToolInfo.price);
                        disclaimerKey = 'starts_from';
                        orderItemName = mainToolInfo.name + " (Base Price)";
                        // priceToCharge is already mainToolInfo.price, which is correct
                    }

                } else if (priceType == "subscription") {
                    priceLabel = "Subscription:";
                    disclaimerKey = 'subscription';

                    // === NEW SUBSCRIPTION LOGIC (TABS) ===
                    var subType = mainToolInfo.subscriptionType; // e.g., 'Monthly,Annually', 'Monthly', 'Annually'
                    var $subOptionsDiv = modal.find("#bdgsSubscriptionOptions");
                    // Instead of finding select, find our new tabs container
                    var $subTabsContainer = modal.find("#subscriptionTypeTabs");

                    if (subType === 'Monthly,Annually') {
                        // Show the container
                        $subOptionsDiv.show();

                        // === Calculate save amount ===
                        var saveAmount = (mainToolInfo.price * 12) - mainToolInfo.annualPrice;
                        if (saveAmount < 0) saveAmount = 0;

                        // Badge HTML
                        var saveText = saveAmount > 0 ? '<span class="bdgs-save-badge">Save ' + formatCurrency(saveAmount) + '</span>' : '';

                        // === BUILD TAB HTML ===
                        // Note: Default to monthly active
                        var tabsHtml = `
                            <div class="bdgs-sub-tab active" data-value="monthly">
                                <span class="bdgs-tab-title">Monthly</span>
                                <span class="bdgs-tab-price">${formatCurrency(mainToolInfo.price)}</span>
                            </div>
                            <div class="bdgs-sub-tab" data-value="annually">
                                <span class="bdgs-tab-title">Annually</span>
                                <span class="bdgs-tab-price">${formatCurrency(mainToolInfo.annualPrice)}</span>
                                ${saveText}
                            </div>
                        `;

                        $subTabsContainer.html(tabsHtml);

                        // Set initial price to monthly by default
                        mainToolInfo.priceToCharge = mainToolInfo.price;
                        orderItemName = mainToolInfo.name + " (Monthly)";
                        priceValue = formatCurrency(mainToolInfo.price) + "/ Monthly";

                        mainToolInfo.selectedSubscriptionType = 'monthly';
                        discount = 0;

                    } else if (subType === 'Annually') {
                        $subOptionsDiv.hide();
                        mainToolInfo.priceToCharge = mainToolInfo.annualPrice;
                        orderItemName = mainToolInfo.name + " (Annually)";
                        priceValue = formatCurrency(mainToolInfo.annualPrice) + "/ Annually";
                        mainToolInfo.selectedSubscriptionType = 'annually';
                        discount = 0;

                    } else { // Default to 'Monthly'
                        $subOptionsDiv.hide();
                        mainToolInfo.priceToCharge = mainToolInfo.price;
                        orderItemName = mainToolInfo.name + " (Monthly)";
                        priceValue = formatCurrency(mainToolInfo.price) + "/ Monthly";
                        mainToolInfo.selectedSubscriptionType = 'monthly';
                        discount = 0;
                    }
                    // === END NEW SUBSCRIPTION LOGIC ===

                } else if (priceType == "ask_for_quote") {
                    priceLabel = "Price:";
                    priceValue = "Ask for Quote";
                    // --- MODIFICATION: Set showSummary to FALSE for quotes ---
                    showSummary = false;
                    // --- END MODIFICATION ---
                    showBookCall = true;
                    disclaimerKey = 'ask_for_quote';
                    mainToolInfo.priceToCharge = 0;
                    orderItemName = "Quote Request for " + mainToolInfo.name;
                }
                // --- END MODIFICATION ---

                modal.find("#bdgs-footer-price-label").text(priceLabel);
                modal
                    .find("#bdgsMainToolPrice .bdgs-footer-price-value")
                    .text(priceValue);

                // Populate order summary
                // --- MODIFICATION: Use dynamic item name and price ---
                $("#bdgsOrderItemsList")
                    .empty()
                    .append(
                        createOrderItemHtml(
                            mainToolInfo.id,
                            orderItemName, // Use dynamic name
                            mainToolInfo.priceToCharge, // Use dynamic price from mainToolInfo
                            true
                        )
                    );
                // --- END MODIFICATION ---

                updateSummary(); // Call updateSummary to set initial totals (subtotal, discount, total)

                // --- MODIFICATION: Pass specific disclaimer key ---
                updateDisclamier(disclaimerKey);
                // --- END MODIFICATION ---

                /*
                 REMOVED: updateDisclamier(toolprice_type);
                */


                // Reset checkout button
                // --- MODIFICATION: Updated button text logic ---
                let buttonText = "Proceed to Checkout";
                if (priceType === 'free') {
                    buttonText = "Complete Order";
                } else if (priceType === 'subscription') {
                    buttonText = "Proceed to Subscription";
                    // --- MODIFICATION: 'ask_for_quote' is now handled by a different form ---
                } else if (priceType === 'starts_from' && mainToolInfo.startsFromType === 'deposit' && mainToolInfo.commitmentPrice > 0) {
                    buttonText = 'Pay ' + formatCurrency(mainToolInfo.commitmentPrice) + ' Deposit';
                }
                // --- END MODIFICATION ---

                $("#bdgsProceedBtn").html(buttonText).prop("disabled", false);

                // +++ NEW: Show/hide summary vs quote form +++
                // This is now redundant for quotes, as the entire #tool-order-now div is replaced,
                // but we keep it for the "showSummary = true" case.
                if (showSummary) {
                    modal.find('.bdgs-order-summary').show();
                    // modal.find('.bdgs-quote-form-card').hide(); // No longer exists
                } else {
                    modal.find('.bdgs-order-summary').hide();
                    // modal.find('.bdgs-quote-form-card').show(); // No longer exists
                }

                // +++ NEW: Show/hide "Book a Call" link +++
                if (showBookCall) {
                    $('#bdgsBookCallWrapper').show();
                } else {
                    $('#bdgsBookCallWrapper').hide();
                }

                isProcessing = false;

                // BUG FIX: Show the correct initial section.
                // This ensures the thank you screen isn't shown on modal open.
                //var isLoggedIn = <?php echo isset($_COOKIE["userid"]) ? 'true' : 'false'; ?>; // Already defined above
                if (isLoggedIn) {
                    // For logged-in users, show the loading placeholder
                    // The AJAX call will replace this
                    showSection('#tool-order-now-content');
                    $('#tool-order-now-content').html('<div class="text-center" id="form-loading-placeholder"><p>Loading your order form...</p></div>');
                } else {
                    // For logged-out users, the form is already in the HTML
                    showSection('#tool-order-now-content');
                }

            });


            /**
             * Event handler for when the modal is fully hidden.
             * Resets the processing state just in case.
             */
            $("#bdgsCheckoutModal").on("hidden.bs.modal", function() {
                if (isProcessing) {
                    $("#bdgsProceedBtn")
                        .html("Proceed to Checkout")
                        .prop("disabled", false);
                    isProcessing = false;
                }
                // Reset to default
                showSection('#tool-order-now-content');
                // *** FIX: Reset the content of the div to its original state ***
                $('#tool-order-now-content').html(
                    '<?php if (!isset($_COOKIE["userid"])) { ?>' +
                    '<form class="bdgs-tool-form" action="" id="next">' +
                    '<div class="form-group">' +
                    '<label for="email">Enter your email</label>' +
                    '<div class="input-group">' +
                    '<span class="input-group-addon" id="email"><i class="fa fa-envelope" aria-hidden="true"></i></span>' +
                    '<input type="email" class="form-control" id="input-email" placeholder="jhon@gmail.com" aria-describedby="email" required>' +
                    '</div>' +
                    '<span style="color: red; display: none; font-size: 14px;" id="error-email">Please enter valid Email</span>' +
                    '</div>' +
                    '<button type="submit" class="btn btn-success" id="next-btn">Next</button>' +
                    '</form>' +
                    '<?php } else { ?>' +
                    '<div class="text-center" id="form-loading-placeholder">' +
                    '<p>Loading your order form...</p>' +
                    '</div>' +
                    '<?php } ?>'
                );
            });


            $("#logout_txt").text("LogOut");
            const postid = $("#tool-order-now").data("postid");
            const service = $("#tool-order-now").data("service");
            const linkPage = $("#tool-order-now").data("link");

            // *** FIX 2: Load logged-in user form ON MODAL SHOWN ***
            // Using 'shown.bs.modal' to ensure modal is visible
            $('#bdgsCheckoutModal').on('shown.bs.modal', function(event) {
                var $container = $(this).find('.tool-order-now');

                // Check if the user is logged in AND the form needs loading
                if ($container.find('#form-loading-placeholder').length > 0) {

                    // User is logged in, and form is not loaded.
                    // *** NEW LOGIC: Check if it's a quote or paid product ***
                    if (mainToolInfo.paymentUnit === 'ask_for_quote') {
                        // --- NEW QUOTE FLOW (Logged-in) ---
                        $container.html('<div class="text-center" id="form-loading-placeholder"><p>Loading quote form...</p></div>');

                        // We load the entire quote form widget
                        $container.load('/api/widget/get/html/ask-for-form', function(response, status, xhr) {
                            if (status == "error") {
                                $container.html('<div class="text-center text-danger">Failed to load quote form. Please try again.</div>');
                                return;
                            }

                            // Now, we fetch the user's data to pre-fill the form
                            $.ajax({
                                type: "POST",
                                url: "/api/widget/get/html/purchase-quick-services", // This widget gets user data
                                data: {
                                    data: "data",
                                    tools_pars: tools_pars_url
                                },
                                success: function(response) {
                                    var $tempForm = $('<div>').html(response);
                                    var firstName = $tempForm.find('#input-first-name').val() || '';
                                    var lastName = $tempForm.find('#input-last-name').val() || '';
                                    var email = $tempForm.find('#input-email').val() || '';
                                    var phone = $tempForm.find('#input-phone-number').val() || '';
                                    var company = $tempForm.find('#input-website-url').val() || '';

                                    // Pre-fill the loaded quote form
                                    $container.find('input[name="lead_name"]').val((firstName + ' ' + lastName).trim());
                                    $container.find('input[name="lead_email"]').val(email);
                                    $container.find('input[name="lead_phone"]').val(phone);
                                    // *** UPDATED: Prefill bd_website_url instead of company ***
                                    $container.find('input[name="bd_website_url"]').val(company);
                                    $container.find('input[name="service_needed"]').val(mainToolInfo.name);

                                    updateModalHeader("Submit Your Estimate Request");
                                    updateModalHeaderIcon('fa-file-text-o');
                                }
                            });
                        });

                    } else {
                        // --- ORIGINAL PAID/FREE FLOW (Logged-in) ---
                        $.ajax({
                            type: "POST",
                            url: "/api/widget/get/html/purchase-quick-services",
                            data: {
                                data: "data",
                                tools_pars: tools_pars_url
                            },
                            success: function(response) {
                                $container.html(response);

                                var $titleEl = $container.find('.title').first();
                                if ($titleEl.length) {
                                    updateModalHeader($titleEl.text());
                                    $titleEl.hide();
                                } else {
                                    updateModalHeader("Review & Complete Your Purchase");
                                }
                            },
                            error: function() {
                                $container.html('<div class="text-center text-danger">Failed to load form. Please try again.</div>');
                            }
                        });
                    }
                }
            });


            /**First step to purchase quick services */
            $("#next").submit(function(e) {
                e.preventDefault();
                $('#next-btn').text("Please wait...");
                const datavalue = $('#input-email').val();
                $.ajax({
                    url: '/api/widget/get/html/custom-manage-data',
                    type: 'POST',
                    data: {
                        datapost: datavalue,
                        tools_pars: tools_pars_url
                    },
                    success: function(result, status) {
                        // --- ORIGINAL PAID/FREE FLOW (New User) ---
                        $(".tool-order-now").html(result);
                        var $titleEl = $(".tool-order-now").find('.title').first();
                        if ($titleEl.length) {
                            updateModalHeader($titleEl.text());
                            $titleEl.hide();
                        }

                    }
                });
            });
            /**End first step to purchase quick services */


            /**For older user*/
            $('body').on('submit', '#login-user', function(event) {
                event.preventDefault();
                $('#login-user-btn').text("Please wait..");
                const inputvalue = $('#input-email').val();
                const password = $('#input-password').val();
                $.ajax({
                    type: 'POST',
                    url: '/api/widget/json/get/Bootstrap%20Theme%20-%20Member%20Login%20Page',
                    dataType: 'json',
                    data: {
                        email: inputvalue,
                        pass: password,
                        formname: 'member_login',
                        form: 'myform',
                        action: 'login',
                        sized: 0,
                        dowiz: 1,
                        save: 1
                    },
                    success: function(response, status) {
                        if (response.result != 'success') {
                            $('#login-password').text('You entered incorrect password, please try again. Forgot your password?')
                            $('#input-password').prop("value", "");
                            $('#login-user-btn').text("Login");
                        } else {
                            $("#check_mem_login").hide();

                            // *** NEW LOGIC: Check if it's a quote or paid product ***
                            if (mainToolInfo.paymentUnit === 'ask_for_quote') {
                                // --- NEW QUOTE FLOW (After Login) ---
                                var $container = $('.tool-order-now');
                                $container.html('<div class="text-center" id="form-loading-placeholder"><p>Loading quote form...</p></div>');

                                // We load the entire quote form widget
                                $container.load('/api/widget/get/html/ask-for-form', function(response, status, xhr) {
                                    if (status == "error") {
                                        $container.html('<div class="text-center text-danger">Failed to load quote form. Please try again.</div>');
                                        return;
                                    }

                                    // Fetch user data to pre-fill
                                    $.ajax({
                                        type: "POST",
                                        url: "/api/widget/get/html/purchase-quick-services", // This widget gets user data
                                        data: {
                                            data: "data",
                                            tools_pars: tools_pars_url
                                        },
                                        success: function(response) {
                                            var $tempForm = $('<div>').html(response);
                                            var firstName = $tempForm.find('#input-first-name').val() || '';
                                            var lastName = $tempForm.find('#input-last-name').val() || '';
                                            var email = $tempForm.find('#input-email').val() || '';
                                            var phone = $tempForm.find('#input-phone-number').val() || '';
                                            var company = $tempForm.find('#input-website-url').val() || '';

                                            // Pre-fill the loaded quote form
                                            $container.find('input[name="lead_name"]').val((firstName + ' ' + lastName).trim());
                                            $container.find('input[name="lead_email"]').val(email);
                                            $container.find('input[name="lead_phone"]').val(phone);
                                            // *** UPDATED: Prefill bd_website_url instead of company ***
                                            $container.find('input[name="bd_website_url"]').val(company);
                                            $container.find('input[name="service_needed"]').val(mainToolInfo.name);

                                            updateModalHeader("Submit Your Estimate Request");
                                            updateModalHeaderIcon('fa-file-text-o');
                                        }
                                    });
                                });

                            } else {
                                // --- ORIGINAL PAID/FREE FLOW (After Login) ---
                                $.ajax({
                                    type: "POST",
                                    url: "/api/widget/get/html/purchase-quick-services",
                                    data: {
                                        data: "data",
                                        tools_pars: tools_pars_url
                                    },
                                    success: function(response) {
                                        $('.tool-order-now').html(response);
                                        var $titleEl = $(".tool-order-now").find('.title').first();
                                        if ($titleEl.length) {
                                            updateModalHeader($titleEl.text());
                                            $titleEl.hide();
                                        } else {
                                            updateModalHeader("Review & Complete Your Purchase");
                                        }

                                        // +++ REMOVED: The logic to inject quote form was here +++
                                    }
                                });
                            }
                        }
                    }
                });
            });
            /**End for older user*/

            async function ensureCustomerExists(userData) {
                let pmpclientId, pmpclientEmail, Firstname, Lastname, Phonenumber, LastOrderId;

                // Assign data from parameter
                pmpclientEmail = userData.email;
                Firstname = userData.firstname;
                Lastname = userData.lastname;
                Phonenumber = userData.phonenumber;
                LastOrderId = userData.last_order_id;

                let customerResult;

                // --- 1. Get or Create Customer ---
                showLoaderStep("Verifying Your Customer Profile...", "Securing and validating your account details.", 65);
                try {
                    customerResult = await $.ajax({
                        type: "GET",
                        url: "/api/widget/json/get/tool-add-customer-pmp-api",
                        data: {
                            firstname: Firstname,
                            lastname: Lastname,
                            email: pmpclientEmail,
                            phonenumber: Phonenumber,
                            websiteurl: userData.websiteurl,
                            last_order_id: LastOrderId,
                        },
                        dataType: "json"
                    });
                } catch (e) {
                    console.warn("GET customer failed, trying POST...", e.statusText);
                    try {
                        const createResult = await $.ajax({
                            type: "POST",
                            url: "/api/widget/json/get/tool-add-customer-pmp-api",
                            data: {
                                firstname: Firstname,
                                lastname: Lastname,
                                email: pmpclientEmail,
                                phonenumber: Phonenumber,
                                websiteurl: userData.websiteurl,
                                last_order_id: LastOrderId,
                            },
                            dataType: "json"
                        });

                        if (createResult.status !== "created") {
                            throw new Error("Failed to create customer profile.");
                        }

                        // Fetch the newly created customer to get the ID
                        customerResult = await $.ajax({
                            type: "GET",
                            url: "/api/widget/json/get/tool-add-customer-pmp-api",
                            data: {
                                firstname: Firstname,
                                lastname: Lastname,
                                email: pmpclientEmail,
                                phonenumber: Phonenumber,
                                websiteurl: userData.websiteurl,
                                last_order_id: LastOrderId,
                            },
                            dataType: "json"
                        });

                    } catch (creationError) {
                        console.error("Failed to create or find customer:", creationError);
                        throw new Error("Failed to create or find customer profile. Please contact support.");
                    }
                }

                if (!customerResult || (customerResult.status !== "created" && customerResult.status !== "found") || !customerResult.data || !customerResult.data.userid) {
                    console.error("Invalid customer data received:", customerResult);
                    throw new Error("Could not retrieve customer profile. Please try again.");
                }

                return customerResult.data; // Returns { userid: ..., ... }
            }

            async function createInvoiceWorkflow(userData) {
                let pmpclientId, pmpclientEmail, Firstname, Lastname, Phonenumber, Service, ShortTitle, LastOrderId;

                // Assign data from parameter
                pmpclientEmail = userData.email;
                Firstname = userData.firstname;
                Lastname = userData.lastname;
                Phonenumber = userData.phonenumber;
                Service = userData.service;
                ShortTitle = userData.short_title;
                LastOrderId = userData.last_order_id;

                // --- 1. Get or Create Customer (Refactored) ---
                const customerData = await ensureCustomerExists(userData);
                pmpclientId = customerData.userid;
                console.log("PMP Client ID:", pmpclientId, pmpclientEmail);

                // --- 2. Get Last Invoice Number ---
                showLoaderStep("Generating Secure Invoice...", "Calculating totals and preparing payment details.", 80);
                const lastInvoiceResponse = await $.ajax({
                    type: "GET",
                    url: "/api/widget/json/get/tools-invoice-api",
                    dataType: "json"
                });

                if (lastInvoiceResponse.status !== "success" || !lastInvoiceResponse.data) {
                    console.warn("No max ID invoice found:", lastInvoiceResponse.message);
                    throw new Error("Could not get last invoice number.");
                }
                const lastInvoiceNumber = lastInvoiceResponse.data.number;

                // --- 3. PREPARE FINANCIALS AND ITEMS ---

                // Update Item Rate and Description based on Subscription selection
                if (selectedNewItems && selectedNewItems.length > 0) {
                    // Ensure the rate matches the selected cycle price
                    selectedNewItems[0].rate = mainToolInfo.priceToCharge;

                    if (mainToolInfo.paymentUnit === 'subscription') {
                        let freqLabel = (mainToolInfo.selectedSubscriptionType === 'annually') ? ' (Annually)' : ' (Monthly)';
                        // Update description to include frequency (e.g., "SEO Tool (Annually)")
                        // Check if it already exists to avoid "Tool (Annually) (Annually)"
                        if (!selectedNewItems[0].description.includes('Monthly') && !selectedNewItems[0].description.includes('Annually')) {
                            selectedNewItems[0].description = selectedNewItems[0].description + freqLabel;
                        }
                    }
                }

                //const subtotal_val = parseFloat(mainToolInfo.priceToCharge).toFixed(2);
                const subtotal_val = parseFloat(subtotal).toFixed(2);
                const discount_val = parseFloat(discount).toFixed(2);
                const currentTotal_val = parseFloat(currentTotal).toFixed(2);

                // --- 4. PREPARE RECURRING SETTINGS ---

                // Initialize variables based on documentation types
                let recurring_val = "0"; // String: "0" ensures NO recurring by default (Critical for Fixed/Starts From)
                let cycles_val = 0; // Number: 0 = Infinite (or not applicable if recurring is 0)

                // Subscription Logic
                if (mainToolInfo.paymentUnit === 'subscription') {

                    // Set cycles to 0 for infinite subscription
                    cycles_val = 0;

                    if (mainToolInfo.selectedSubscriptionType === 'annually') {
                        // API Doc: "recurring 1 to 12". 
                        // "12" = Every 12 Months (Yearly)
                        recurring_val = "12";
                    } else {
                        // Default to Monthly
                        // "1" = Every 1 Month
                        recurring_val = "1";
                    }
                }
                // Note: For 'fixed', 'starts_from', etc., recurring_val remains "0".
                // This prevents "unnecessary invoices" from generating on fixed products.

                // --- 5. Create New Invoice Payload ---
                const invoiceData = {
                    clientid: pmpclientId,
                    clientemail: pmpclientEmail,
                    FirstName: Firstname,
                    LastName: Lastname,
                    PhoneNumber: Phonenumber,
                    test_mode: 'false',
                    ServiceName: Service,
                    ShortTitle: ShortTitle,
                    post_id: postid,
                    LastOrderIdTool: LastOrderId,
                    last_invoice_number: lastInvoiceNumber,
                    date: currentDate,
                    currency: 1,
                    newitems: selectedNewItems,
                    allowed_payment_modes: ["1", "razorpay", "paypal", "stripe"],
                    billing_street: "null",
                    subtotal: subtotal_val,
                    total: currentTotal_val,
                    discount_type: "after_tax",
                    discount_total: discount_val,
                    clientnote: "For questions, contact us on WhatsApp: https://wa.me/919771610433",
                    terms: "<h4>License Type:</h4> Single Domain-Locked Commercial License (SDCL v1.0)...",

                    // === RECURRING FIELDS ===
                    recurring: recurring_val, // Passes "1", "12", or "0" as String
                    cycles: cycles_val // Passes 0 as Number
                };

                console.log("Sending Invoice Payload:", invoiceData);

                // POST the new invoice
                await $.ajax({
                    url: "/api/widget/json/get/tools-invoice-api",
                    method: "POST",
                    timeout: 0,
                    contentType: "application/x-www-form-urlencoded; charset=UTF-8",
                    data: invoiceData
                });

                // --- 6. Fetch Final Invoice Details ---
                showLoaderStep("Confirming Order Registration...", "Finalizing transaction details on the server.", 90);

                const finalInvoiceResponse = await $.ajax({
                    type: "GET",
                    url: "/api/widget/json/get/tools-invoice-api",
                    dataType: "json"
                });

                if (finalInvoiceResponse.status !== "success" || !finalInvoiceResponse.data || !finalInvoiceResponse.data.id || !finalInvoiceResponse.data.hash) {
                    console.error("Final invoice fetch failed:", finalInvoiceResponse);
                    throw new Error("Failed to retrieve final invoice details.");
                }

                const invoiceURL = "https://pmp.businesslabs.org/invoice/" + finalInvoiceResponse.data.id + "/" + finalInvoiceResponse.data.hash;

                /**
                if (finalInvoiceResponse.data.total != 0.01) {
                    localStorage.setItem("invoice_link", invoiceURL);
                }
                */

                return finalInvoiceResponse;
            }


            /**For new user */
            // This handler is for the #signup-user form (paid/free products).
            // *** THIS IS THE CORRECTED HANDLER ***
            $('body').on('submit', '#signup-user', async function(event) {
                event.preventDefault();

                // This form is ONLY for paid/free flows.

                const quote_message = '';
                const firstname = $('#input-first-name').val();
                const origin_url = $('#origin_url').val();
                const lastname = $('#input-last-name').val();
                const email = $('#input-email').val();
                const password = $('#input-password').val();
                const phonenumber = $('#input-phone-number').val();
                const cnfpassword = $('#input-cnf-password').val();
                const websiteurl = $('#input-website-url').val();
                const userloggin = $('#signup-user-btn').data("usid");
                const postid = $('#tool-order-now').data("postid");
                const service = $("#tool-order-now").data("link");

                if (userloggin == '') {
                    // --- NEW USER FLOW (Paid/Free) ---
                    if (password != cnfpassword) {
                        $('#error-cnf-password').text('');
                        $('#error-cnf-password').text('Password does not match.');
                        $('#signup-user-btn').attr('disabled', 'disabled');
                        return;
                    }

                    $('#error-cnf-password').text('');
                    $('#signup-user-btn').attr('disabled', true).text("Please wait while we process your purchase order...");

                    try {
                        const signupResult = await $.ajax({
                            url: '/api/widget/get/html/custom-login-signup-manage-data-tool-v2',
                            type: 'POST',
                            data: {
                                firstname: firstname,
                                lastname: lastname,
                                origin_url: origin_url,
                                email: email,
                                password: password,
                                phonenumber: phonenumber,
                                websiteurl: websiteurl,
                                postid: postid,
                                service: service,
                                short_title: $("#tool-order-now").data("short-title"),
                                price_unit: mainToolInfo.paymentUnit,
                                requirements: quote_message
                            }
                        });

                        showLoaderStep("Logging you in...", "Securing your session.", 40);
                        const loginResult = await $.ajax({
                            type: 'POST',
                            url: '/api/widget/json/get/Bootstrap%20Theme%20-%20Member%20Login%20Page',
                            dataType: 'json',
                            data: {
                                email: email,
                                pass: password,
                                formname: 'member_login',
                                form: 'myform',
                                action: 'login',
                                sized: 0,
                                dowiz: 1,
                                save: 1
                            }
                        });

                        if (loginResult.result != 'success') {
                            $("#error_msg").show();
                            throw new Error("There was an issue logging you in. Please try again.");
                        }

                        if (loginResult.result = 'success' && mainToolInfo.paymentUnit === 'ask_for_quote') {
                            // --- NEW QUOTE FLOW (After Login) ---
                            var $container = $('.tool-order-now');
                            $container.html('<div class="text-center" id="form-loading-placeholder"><p>Loading quote form...</p></div>');

                            // We load the entire quote form widget
                            $container.load('/api/widget/get/html/ask-for-form', function(response, status, xhr) {
                                if (status == "error") {
                                    $container.html('<div class="text-center text-danger">Failed to load quote form. Please try again.</div>');
                                    return;
                                }

                                // Fetch user data to pre-fill
                                $.ajax({
                                    type: "POST",
                                    url: "/api/widget/get/html/purchase-quick-services", // This widget gets user data
                                    data: {
                                        data: "data",
                                        tools_pars: tools_pars_url
                                    },
                                    success: function(response) {
                                        var $tempForm = $('<div>').html(response);
                                        var firstName = $tempForm.find('#input-first-name').val() || '';
                                        var lastName = $tempForm.find('#input-last-name').val() || '';
                                        var email = $tempForm.find('#input-email').val() || '';
                                        var phone = $tempForm.find('#input-phone-number').val() || '';
                                        var company = $tempForm.find('#input-website-url').val() || '';

                                        // Pre-fill the loaded quote form
                                        $container.find('input[name="lead_name"]').val((firstName + ' ' + lastName).trim());
                                        $container.find('input[name="lead_email"]').val(email);
                                        $container.find('input[name="lead_phone"]').val(phone);
                                        // *** UPDATED: Prefill bd_website_url instead of company ***
                                        $container.find('input[name="bd_website_url"]').val(company);
                                        $container.find('input[name="service_needed"]').val(mainToolInfo.name);

                                        updateModalHeader("Submit Your Estimate Request");
                                        updateModalHeaderIcon('fa-file-text-o');
                                    }
                                });
                            });

                        } else {

                            $('#loaderModal').modal('hide');
                            $("#check_mem_login").hide();

                            const user_name = firstname + ' ' + lastname;
                            const payment_unit = mainToolInfo.paymentUnit;

                            updateModalHeader("Review & Complete Your Purchase");

                            // Show the main summary screen
                            showSection('#mainmodal-content');

                            // Attach Stripe (or free completion) handler
                            $("#bdgsProceedBtn").off('click').on("click", async function() {

                                if (isProcessing) return;
                                isProcessing = true;

                                var $btn = $(this);
                                $btn
                                    .html('<i class="fa fa-spinner fa-spin"></i> Processing...')
                                    .prop("disabled", true);

                                if (mainToolInfo.paymentUnit === 'free') {
                                    $('#loaderModal').modal('show'); // Ensure loader is shown
                                    try {
                                        // 1. Ensure Customer Exists
                                        const Userdata = {
                                            firstname: firstname,
                                            lastname: lastname,
                                            email: email,
                                            phonenumber: phonenumber,
                                            websiteurl: websiteurl,
                                            last_order_id: signupResult.last_order_id
                                        };

                                        //await ensureCustomerExists(Userdata);

                                        // 2. Proceed with Quote/Free Handler
                                        showLoaderStep("Finalizing Order...", "Registering your free tool.", 80);

                                        $.ajax({
                                            url: 'https://bdgrowthsuite.com/api/widget/get/html/tools-quote-handler',
                                            type: 'POST',
                                            data: {
                                                post_name: mainToolInfo.post_name,
                                                user_email: email,
                                                user_name: $("#input-first-name").val() + ' ' + $("#input-last-name").val(),
                                                payment_unit: mainToolInfo.paymentUnit,
                                                origin_url: origin_url,
                                                post_url: mainToolInfo.post_url,
                                                implementation_type: mainToolInfo.implementation,
                                                delivery_time_description: mainToolInfo.delivery,
                                                warranty_time_description: mainToolInfo.warranty,
                                                postid: postid,
                                                service: service,
                                                tool_name: $("#tool-order-now").data("short-title"),
                                                bd_user_id: '<?php echo $_COOKIE["userid"]; ?>'
                                            },
                                            success: function(res) {
                                                if (res.success === true) {
                                                    $('#loaderModal').modal('hide');
                                                    showDynamicThankYou(mainToolInfo);
                                                } else {
                                                    // Use loader modal for error
                                                    $('#loaderModal').modal('show');
                                                    showLoaderError("Quote Error", res.error || 'Unable to submit quote.');
                                                    $("#signup-user-btn").attr("disabled", false).text("Try Again");
                                                }
                                            }
                                        });
                                    } catch (error) {
                                        showLoaderError("Registration Failed", error.message);
                                        $btn.html("Complete Order").prop("disabled", false);
                                        isProcessing = false;
                                    }
                                    return;
                                }

                                $('#loaderModal').modal('show');
                                showLoaderStep("Redirecting to payment...", "Building secure payment link.", 50);

                                const Userdata = {
                                    firstname: firstname,
                                    lastname: lastname,
                                    email: email,
                                    phonenumber: phonenumber,
                                    websiteurl: websiteurl,
                                    service: service,
                                    short_title: $("#tool-order-now").data("short-title"),
                                    last_order_id: signupResult.last_order_id // For new users, this will be undefined
                                };


                                // START: RECURRING LOGIC CALCULATION (Moved from createInvoiceWorkflow)
                                // Initialize variables based on documentation types
                                let recurring_val = "0"; // String: "0" ensures NO recurring by default
                                let cycles_val = 0; // Number: 0 = Infinite

                                // Subscription Logic
                                if (mainToolInfo.paymentUnit === 'subscription') {
                                    cycles_val = 0; // Infinite
                                    if (mainToolInfo.selectedSubscriptionType === 'annually') {
                                        recurring_val = "12"; // Yearly
                                    } else {
                                        recurring_val = "1"; // Monthly
                                    }
                                }
                                // END: RECURRING LOGIC

                                const finaltotal = parseFloat(currentTotal).toFixed(2);
                                const finalsubtotal = parseFloat(subtotal).toFixed(2);
                                const finaldiscount = parseFloat(discount).toFixed(2);

                                var payload = {
                                    tool_name: mainToolInfo.name,
                                    tool_id: mainToolInfo.id,
                                    tool_price: mainToolInfo.priceToCharge,

                                    actual_price: mainToolInfo.price,
                                    subtotal: finalsubtotal,
                                    total: finaltotal,
                                    discount: finaldiscount,
                                    startsfromtype: mainToolInfo.startsFromType,
                                    user_email: email,
                                    user_name: user_name,
                                    user_phone: phonenumber,
                                    user_website: websiteurl,
                                    payment_unit: mainToolInfo.paymentUnit,

                                    // NEW: Pass Invoice Params to Stripe Metadata
                                    // customer_id: ... We might NOT have this yet if 'ensureCustomerExists' wasn't called. 
                                    // If we rely on Webhook, we should pass parameters to Find/Create customer there too?
                                    // Actually, let's keep ensureCustomerExists separately if we want to be safe, OR just pass details.
                                    // The plan said we KEEP ensureCustomerExists.
                                    customer_id: '', // Will be filled below if we keep it, otherwise Webhook must handle.

                                    // Params for Webhook Invoice Generation:
                                    client_first_name: firstname,
                                    client_last_name: lastname,
                                    client_website: websiteurl,
                                    last_order_id: signupResult.last_order_id,
                                    service_name: service,
                                    short_title: $("#tool-order-now").data("short-title"),
                                    post_id: postid,
                                    recurring_val: recurring_val,
                                    cycles_val: cycles_val,
                                    client_note: "For questions, contact us on WhatsApp: https://wa.me/919771610433",
                                    terms_content: "<h4>License Type:</h4> Single Domain-Locked Commercial License (SDCL v1.0)...",

                                    // CRITICAL: Pass EXACT Items Logic
                                    // Modified to STRIP long_description to fix Stripe 500 char limit
                                    newitems_json: (function() {
                                        // Clone array to safely modify
                                        let itemsToSend = JSON.parse(JSON.stringify(selectedNewItems || []));
                                        if (itemsToSend.length > 0) {
                                            itemsToSend[0].rate = mainToolInfo.priceToCharge;
                                            if (mainToolInfo.paymentUnit === 'subscription') {
                                                let freqLabel = (mainToolInfo.selectedSubscriptionType === 'annually') ? ' (Annually)' : ' (Monthly)';
                                                if (!itemsToSend[0].description.includes('Monthly') && !itemsToSend[0].description.includes('Annually')) {
                                                    itemsToSend[0].description = itemsToSend[0].description + freqLabel;
                                                }
                                            }
                                            // FIX: Stripe Limit > 500 chars. Truncate long_description.
                                            itemsToSend[0].long_description = "Payment for " + service;
                                        }
                                        return JSON.stringify(itemsToSend);
                                    })(),

                                    implementation_type: mainToolInfo.implementation,
                                    delivery_time_description: mainToolInfo.delivery,
                                    warranty_time_description: mainToolInfo.warranty,
                                    post_url: mainToolInfo.post_url,
                                    sub_type: mainToolInfo.selectedSubscriptionType
                                };

                                // OPTIONAL: We can still ensure customer exists here to be safe and reduce Webhook complexity
                                try {
                                    const custData = await ensureCustomerExists(Userdata);
                                    payload.customer_id = custData.userid;
                                } catch (e) {
                                    console.warn("Could not ensure customer exists, Webhook will attempt.", e);
                                }

                                console.log("Stripe Checkout Payload:", payload);

                                showLoaderStep("Redirecting to Secure Checkout...", "Transferring securely to Stripe/Payment Gateway.", 100);

                                function resetBtn() {
                                    let buttonText = "Proceed to Checkout";
                                    if (mainToolInfo.paymentUnit === 'subscription') {
                                        buttonText = "Proceed to Subscription";
                                    } else if (mainToolInfo.paymentUnit === 'starts_from' && mainToolInfo.startsFromType === 'deposit' && mainToolInfo.commitmentPrice > 0) {
                                        buttonText = 'Pay ' + formatCurrency(mainToolInfo.commitmentPrice) + ' Deposit';
                                    }
                                    $btn.html(buttonText).prop("disabled", false);
                                    isProcessing = false;
                                }
                                /**
                                 * * New User Flow - When signing up and purchasing
                                 * call to initiate Stripe Checkout session
                                 * */
                                await $.ajax({
                                    url: '/api/widget/get/html/bdgs-tools-stripe-checkout',
                                    type: 'POST',
                                    data: JSON.stringify(payload),
                                    contentType: 'application/json',
                                    success: function(res) {
                                        if (res.url) {
                                            window.location.href = res.url;
                                        } else {
                                            console.error("Stripe Error:", res.error);
                                            showLoaderError("Payment Error", res.error || 'Unable to start checkout');
                                            resetBtn();
                                        }
                                    },
                                    error: function(xhr) {
                                        console.error("AJAX Error:", xhr.statusText);
                                        showLoaderError("Server Error", 'Could not connect to payment gateway. Please try again.');
                                        resetBtn();
                                    }
                                });
                            });

                        }
                    } catch (error) {
                        console.error("New user signup flow failed:", error);
                        $("#signup-user-btn").attr("disabled", false).text("Try Again");
                        showLoaderError("Signup Failed", error.message || "An unknown error occurred.");
                    }

                } else {
                    // --- LOGGED-IN USER FLOW (Paid/Free) ---
                    $('#signup-user-btn').attr('disabled', true).text("Please wait...");

                    try {
                        const updateUserResult = await $.ajax({
                            url: '/api/widget/get/html/custom-login-signup-manage-data-tool-v2',
                            type: 'POST',
                            data: {
                                firstname: firstname,
                                origin_url: origin_url,
                                lastname: lastname,
                                email: email,
                                phonenumber: phonenumber,
                                websiteurl: websiteurl,
                                postid: postid,
                                service: service,
                                short_title: $("#tool-order-now").data("short-title"),
                                price_unit: mainToolInfo.paymentUnit,
                                requirements: quote_message
                            }
                        });

                        updateModalHeader("Review & Complete Your Purchase");

                        showSection('#mainmodal-content');

                        // Attach Stripe (or free completion) handler
                        $("#bdgsProceedBtn").off('click').on("click", async function() {

                            if (isProcessing) return;
                            isProcessing = true;

                            var $btn = $(this);
                            $btn
                                .html('<i class="fa fa-spinner fa-spin"></i> Processing...')
                                .prop("disabled", true);

                            if (mainToolInfo.paymentUnit === 'free') {
                                $('#loaderModal').modal('show'); // Ensure loader is shown
                                try {
                                    // 1. Ensure Customer Exists
                                    const Userdata = {
                                        firstname: firstname,
                                        lastname: lastname,
                                        email: email,
                                        phonenumber: phonenumber,
                                        websiteurl: websiteurl,
                                        last_order_id: updateUserResult.last_order_id
                                    };

                                    //await ensureCustomerExists(Userdata);

                                    // 2. Proceed with Quote/Free Handler
                                    showLoaderStep("Finalizing Order...", "Registering your free tool.", 80);

                                    $.ajax({
                                        url: 'https://bdgrowthsuite.com/api/widget/get/html/tools-quote-handler',
                                        type: 'POST',
                                        data: {
                                            post_name: mainToolInfo.post_name,
                                            user_email: email,
                                            user_name: $("#input-first-name").val() + ' ' + $("#input-last-name").val(),
                                            payment_unit: mainToolInfo.paymentUnit,
                                            origin_url: origin_url,
                                            post_url: mainToolInfo.post_url,
                                            implementation_type: mainToolInfo.implementation,
                                            delivery_time_description: mainToolInfo.delivery,
                                            warranty_time_description: mainToolInfo.warranty,
                                            postid: postid,
                                            service: service,
                                            tool_name: $("#tool-order-now").data("short-title"),
                                            bd_user_id: '<?php echo $_COOKIE["userid"]; ?>'
                                        },
                                        success: function(res) {
                                            if (res.success === true) {
                                                $('#loaderModal').modal('hide');
                                                showDynamicThankYou(mainToolInfo);

                                            } else {
                                                // Use loader modal for error
                                                $('#loaderModal').modal('show');
                                                showLoaderError("Quote Error", res.error || 'Unable to submit quote.');
                                                $("#signup-user-btn").attr("disabled", false).text("Try Again");
                                            }
                                        }
                                    });
                                } catch (error) {
                                    showLoaderError("Registration Failed", error.message);
                                    $btn.html("Complete Order").prop("disabled", false);
                                    isProcessing = false;
                                }
                                return;
                            }

                            $('#loaderModal').modal('show');
                            showLoaderStep("Redirecting to payment...", "Building secure payment link.", 50);

                            const Userdata = {
                                firstname: firstname,
                                lastname: lastname,
                                email: email,
                                phonenumber: phonenumber,
                                websiteurl: websiteurl,
                                service: service,
                                short_title: $("#tool-order-now").data("short-title"),
                                last_order_id: updateUserResult.last_order_id // For logged-in users, this will be defined
                            };

                            // START: RECURRING LOGIC CALCULATION (Logged-In User)
                            let recurring_val = "0";
                            let cycles_val = 0;

                            if (mainToolInfo.paymentUnit === 'subscription') {
                                cycles_val = 0;
                                if (mainToolInfo.selectedSubscriptionType === 'annually') {
                                    recurring_val = "12";
                                } else {
                                    recurring_val = "1";
                                }
                            }
                            // END: RECURRING LOGIC

                            const finaltotal = parseFloat(currentTotal).toFixed(2);
                            const finalsubtotal = parseFloat(subtotal).toFixed(2);
                            const finaldiscount = parseFloat(discount).toFixed(2);


                            var payload = {
                                tool_name: mainToolInfo.name,
                                tool_id: mainToolInfo.id,
                                tool_price: mainToolInfo.priceToCharge,
                                actual_price: mainToolInfo.price,
                                subtotal: finalsubtotal,
                                total: finaltotal,
                                discount: finaldiscount,
                                startsfromtype: mainToolInfo.startsFromType,
                                user_email: $('#input-email').val(),
                                user_name: $('#input-first-name').val() + ' ' + $('#input-last-name').val(),
                                user_phone: $('#input-phone-number').val(),
                                user_website: $('#input-website-url').val(),
                                payment_unit: mainToolInfo.paymentUnit,

                                // NEW: Pass Invoice Params to Stripe Metadata
                                customer_id: '', // Will be filled below

                                // Params for Webhook Invoice Generation:
                                client_first_name: firstname,
                                client_last_name: lastname,
                                client_website: websiteurl,
                                last_order_id: updateUserResult.last_order_id,
                                service_name: service,
                                short_title: $("#tool-order-now").data("short-title"),
                                post_id: postid,
                                recurring_val: recurring_val,
                                cycles_val: cycles_val,
                                client_note: "For questions, contact us on WhatsApp: https://wa.me/919771610433",
                                terms_content: "<h4>License Type:</h4> Single Domain-Locked Commercial License (SDCL v1.0)...",

                                // CRITICAL: Pass EXACT Items Logic
                                // Modified to STRIP long_description to fix Stripe 500 char limit
                                newitems_json: (function() {
                                    // Clone array to safely modify
                                    let itemsToSend = JSON.parse(JSON.stringify(selectedNewItems || []));
                                    if (itemsToSend.length > 0) {
                                        itemsToSend[0].rate = mainToolInfo.priceToCharge;
                                        if (mainToolInfo.paymentUnit === 'subscription') {
                                            let freqLabel = (mainToolInfo.selectedSubscriptionType === 'annually') ? ' (Annually)' : ' (Monthly)';
                                            if (!itemsToSend[0].description.includes('Monthly') && !itemsToSend[0].description.includes('Annually')) {
                                                itemsToSend[0].description = itemsToSend[0].description + freqLabel;
                                            }
                                        }
                                        // FIX: Stripe Limit > 500 chars. Truncate long_description.
                                        itemsToSend[0].long_description = "Payment for " + service;
                                    }
                                    return JSON.stringify(itemsToSend);
                                })(),

                                implementation_type: mainToolInfo.implementation,
                                delivery_time_description: mainToolInfo.delivery,
                                warranty_time_description: mainToolInfo.warranty,
                                post_url: mainToolInfo.post_url,
                                sub_type: mainToolInfo.selectedSubscriptionType
                            };

                            // Ensure customer exists (Logged in user should be easier)
                            try {
                                const custData = await ensureCustomerExists(Userdata);
                                payload.customer_id = custData.userid;
                            } catch (e) {
                                console.warn("Could not ensure customer exists, Webhook will attempt.", e);
                            }

                            console.log("Stripe Checkout Payload:", payload);


                            function resetBtn() {
                                let buttonText = "Proceed to Checkout";
                                if (mainToolInfo.paymentUnit === 'subscription') {
                                    buttonText = "Proceed to Subscription";
                                } else if (mainToolInfo.paymentUnit === 'starts_from' && mainToolInfo.startsFromType === 'deposit' && mainToolInfo.commitmentPrice > 0) {
                                    buttonText = 'Pay ' + formatCurrency(mainToolInfo.commitmentPrice) + ' Deposit';
                                }
                                $btn.html(buttonText).prop("disabled", false);
                                isProcessing = false;
                            }

                            /**
                             * * Logged-in User Flow - When updating info and purchasing
                             * call to initiate Stripe Checkout session
                             * */

                            $.ajax({
                                url: '/api/widget/get/html/bdgs-tools-stripe-checkout',
                                type: 'POST',
                                data: JSON.stringify(payload),
                                contentType: 'application/json',
                                success: function(res) {
                                    if (res.url) {
                                        window.location.href = res.url;
                                    } else {
                                        console.error("Stripe Error:", res.error);
                                        showLoaderError("Payment Error", res.error || 'Unable to start checkout');
                                        resetBtn();
                                    }
                                },
                                error: function(xhr) {
                                    console.error("AJAX Error:", xhr.statusText);
                                    showLoaderError("Server Error", 'Could not connect to payment gateway. Please try again.');
                                    resetBtn();
                                }
                            });

                            /** End AJAX call to initiate Stripe Checkout session */

                        });
                    } catch (error) {
                        console.error("Logged-in user update failed:", error);
                        $('#signup-user-btn').attr('disabled', false).text("Try Again");
                        showSection('#mainmodal-content');
                        $('#bdgsProceedBtn').parent().prepend('<p class="text-danger">Error saving user info. Please try again.</p>');
                    }
                }
            });
            /**End for new user */

            // =================================================================
            // === START: Handlers for 'ask-for-form.php' (Quote Form) ===
            // =================================================================

            /**For new Quote Form submission */
            $('body').on('submit', '.requirements_form', function(e) {
                e.preventDefault();
                var $form = $(this);
                var $submitButton = $form.find("button[type='submit']");
                $submitButton.prop("disabled", true).html('<i class="fa fa-spinner fa-spin" style="color:white;"></i> Processing...');

                var formData = new FormData(this);

                // *** UPDATED: Add all available data for the handler ***
                formData.append('tool_name', mainToolInfo.name);
                formData.append('payment_unit', 'ask_for_quote');

                // Data for $tool_id in PHP
                formData.append('postid', mainToolInfo.post_id);

                // Data for $post_name in PHP
                formData.append('post_name', mainToolInfo.post_name);

                // Data for $user_email and $user_name in PHP
                formData.append('user_email', $form.find('input[name="lead_email"]').val());
                formData.append('user_name', $form.find('input[name="lead_name"]').val());

                // Data for $requirement in PHP
                // *** UPDATED: Send quote notes as 'requirements' ***
                formData.append('requirements', $form.find('textarea[name="lead_notes"]').val());
                formData.append('specification_links', $form.find('textarea[name="specifications"]').val());

                var files = $form.find('input[name="files"]')[0].files;

                // Append each file name (if you only want names)
                for (var i = 0; i < files.length; i++) {
                    formData.append('files[]', files[i]);
                }

                // Data for $amount_paid in PHP
                formData.append('amount_paid', mainToolInfo.priceToCharge); // Will be 0 for quotes

                // Data for $startsfrom_type in PHP
                formData.append('starts_from_type', mainToolInfo.startsFromType);

                // Note: The form already contains:
                // lead_name, lead_email, bd_website_url, lead_phone, service_needed, specifications, lead_notes

                $.ajax({
                    // *** UPDATED: URL now points to the main handler ***
                    url: 'https://bdgrowthsuite.com/api/widget/get/html/tools-quote-handler',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    // *** UPDATED: Success callback now expects JSON ***
                    success: function(response) {
                        // Check for a JSON response
                        if (response && (response.success === true || response.status === 'success')) {
                            // Also update the main modal header
                            updateModalHeader("Quote Request Submitted!");
                            showDynamicThankYou(mainToolInfo, true);

                        } else {
                            // Handle JSON error or unexpected text
                            var errorMsg = response.message || "There was an error submitting your quote. Please try again.";
                            alert(errorMsg);
                            $submitButton.prop("disabled", false).html('Send Inquiry');
                        }
                    },
                    error: function() {
                        alert("There was an error with the submission. Please try again.");
                        $submitButton.prop("disabled", false).html('Send Inquiry');
                    }
                });
            });

            /** File upload button click */
            $('body').on('click', '#fileSelectButton', function() {
                // Find the file input relative to the button
                $(this).closest('.input-group-file').find('#fileUpload').click();
            });

            /** File upload change event */
            $('body').on('change', '#fileUpload', function() {
                var file = this.files[0];
                var $fileNameDisplay = $(this).closest('.input-group-file').find('#fileNameDisplay');

                if (file) {
                    var fileName = file.name;
                    var maxSize = 20 * 1024 * 1024; // 20MB in bytes

                    if (file.size > maxSize) {
                        swal({
                            title: "File Too Large!",
                            html: "<h3>You can upload one file up to 20MB.</h3><p>Max 20MB per file. For larger or multiple files, upload to Drive/Dropbox and share the link. </p><br><p>You can restrict access to our team by sharing it with <a>testblab8@gmail.com.</a></p>",
                            type: "warning",
                            confirmButtonText: "Got it!",
                            confirmButtonColor: "#FFC107",
                        });
                        $(this).val(""); // Clear file input
                        $fileNameDisplay.val("No file chosen");
                    } else {
                        $fileNameDisplay.val(fileName); // Update text field with file name
                    }
                } else {
                    $fileNameDisplay.val("No file chosen");
                }
            });

            /** Accordion toggle for quote form */
            $('body').on('click', '.bdgs-asq-form-section .list .list-flex', function() {
                var targetId = $(this).data("target");
                var $targetElement = $("#" + targetId);
                var isVisible = $targetElement.is(":visible");

                // Close all other elements
                $(".hire_container_section .list .list-flex").each(function() {
                    var otherTargetId = $(this).data("target");
                    if (otherTargetId !== targetId) {
                        $("#" + otherTargetId).slideUp(500);
                        $(this).find(".toggle-eye").removeClass("fa-minus").addClass("fa-plus");
                    }
                });

                // Toggle clicked element
                if (!isVisible) {
                    $targetElement.slideDown(500);
                    $(this).find(".toggle-eye").removeClass("fa-plus").addClass("fa-minus");
                } else {
                    $targetElement.slideUp(500);
                    $(this).find(".toggle-eye").removeClass("fa-minus").addClass("fa-plus");
                }
            });

            // =================================================================
            // === END: Handlers for 'ask-for-form.php' (Quote Form) ===
            // =================================================================


            /**Validate password */
            let passwordCheck;
            $('body').on('keyup', '#input-password', function() {
                let that = $(this);
                let val = that.val();
                if (password_check(val)) {
                    passwordCheck = that.val();
                    $('#error-password').text('');
                    $('#signup-user-btn').removeAttr('disabled');
                } else {
                    $('#error-password').text('6 to 15 characters which contain at least one letter, one number, and one special character');
                    $('#signup-user-btn').attr('disabled', 'disabled');
                }
            });

            $('body').on('keyup', '#input-cnf-password', function() {
                let that = $(this);
                if (that.val() === passwordCheck) {
                    $('#error-cnf-password').text('');
                    $('#signup-user-btn').removeAttr('disabled');
                } else {
                    $('#error-cnf-password').text('Password does not match.');
                    $('#signup-user-btn').attr('disabled', 'disabled');
                }
            });
            /**End validate password */

            /**Validate Email */
            $('body').on('keyup', '#input-email', function(event) {
                var data = $(this).val();

                if (data.indexOf('.') > -1) {

                    if (isValidEmailAddress(data)) {
                        $('#error-email').hide();
                        $('#next-btn').removeAttr('disabled');
                        $('#signup-user-btn').removeAttr('disabled');
                        $('#login-user').removeAttr('disabled');
                    } else {
                        $('#error-email').show();
                        $('#next-btn').attr('disabled', 'disabled');
                        $('#login-user').attr('disabled', 'disabled');
                        $('#signup-user-btn').attr('disabled', 'disabled');
                    }
                } else {
                    $('#error-email').show();
                    $('#next-btn').attr('disabled', 'disabled');
                    $('#login-user').attr('disabled', 'disabled');
                    $('#signup-user-btn').attr('disabled', 'disabled');
                }
            });

            $('body').on('change', '#input-email', function(event) {
                $('.edit-email').click();
            });
            /**End validate email */

            /**Validate phone number*/
            $('body').on('keyup', '#input-phone-number', function(event) {
                var data = $(this).val();
                if (phone_validate(data)) {
                    if (data.length <= 13 && data.length >= 10) {
                        $('.error_Msg').hide();
                        $('#signup-user-btn').removeAttr('disabled');
                    } else if (data.length == 0) {
                        $('.error_Msg').hide();
                        $('#signup-user-btn').removeAttr('disabled');
                    } else {
                        $('.error_Msg').show();
                        $('#signup-user-btn').attr('disabled', 'disabled');
                    }
                } else {
                    if (data != '') {
                        $('.error_Msg').show();
                        $('#signup-user-btn').attr('disabled', 'disabled');
                    } else {
                        $('.error_Msg').hide();
                        $('#signup-user-btn').removeAttr('disabled');
                    }
                }
            });
            /**End validate phone number*/

            /**Final check of phone number And Email*/
            $('body').on('mouseenter', '#signup-user-btn', function() {
                let dataEmail = $('#input-email').val();
                let dataPhone = $('#input-phone-number').val();
                if (phone_validate(dataPhone)) {
                    if (dataPhone.length > 13) {
                        $('.error_Msg').show();
                        $('#signup-user-btn').attr('disabled', 'disabled');
                    } else if (dataPhone.length < 10) {
                        if (dataPhone != '') {
                            $('.error_Msg').show();
                            $('#signup-user-btn').attr('disabled', 'disabled');
                        } else {
                            $('.error_Msg').hide();
                            $('#signup-user-btn').removeAttr('disabled');
                        }
                    } else {
                        $('.error_Msg').hide();
                        $('#signup-user-btn').removeAttr('disabled');
                    }

                } else {
                    //console.log("test dfnkjghe");
                    $('.error_Msg').show();
                    $('#signup-user-btn').attr('disabled', 'disabled');
                }
            });

            $('body').on('mouseenter', '#login-user', function() {
                let dataEmail = $('#input-email').val();
                if (dataEmail.indexOf('.') > -1) {

                    if (isValidEmailAddress(dataEmail)) {
                        $('#error-email').hide();
                        $('#signup-user-btn').removeAttr('disabled');
                    } else {
                        $('#error-email').show();
                        $('#signup-user-btn').attr('disabled', 'disabled');
                    }

                } else {
                    $('#error-email').show();
                    $('#signup-user-btn').attr('disabled', 'disabled');
                }
            });

            $('body').on('mouseenter', '#next-btn', function() {
                let dataEmail = $('#input-email').val();
                if (isValidEmailAddress(dataEmail)) {
                    $('#error-email').hide();
                    $('#next-btn').removeAttr('disabled');
                } else {
                    $('#error-email').show();
                    $('#next-btn').attr('disabled', 'disabled');
                }
            });
            /**End final check of phone number And Email*/

            /**Edit email in second step*/
            $('body').on('click', '.edit-email', function() {
                if ($(this).hasClass('saved-email')) {
                    $(this).text('Saving...');
                    $('#input-email').prop('disabled', true);
                    $(this).removeClass('saved-email');

                    const datavalue = $('#input-email').val();
                    $.ajax({
                        url: '/api/widget/get/html/custom-manage-data',
                        type: 'POST',
                        data: {
                            datapost: datavalue,
                            tools_pars: tools_pars_url
                        },
                        success: function(result, status) {
                            $(this).text('Edit');
                            $(".tool-order-now").html(result);
                        }
                    });
                } else {
                    $(this).text('Save');
                    $(this).addClass('saved-email');
                    $('#input-email').prop('disabled', false);
                }
            });
            /**End Edit email in second step*/

            // === NEW: Handler for Subscription Type Change (TABS) ===
            $('body').on('click', '.bdgs-sub-tab', function() {
                var selectedType = $(this).data('value'); // 'monthly' or 'annually'

                // Toggle Active State
                $('.bdgs-sub-tab').removeClass('active');
                $(this).addClass('active');

                var $mainPriceDisplay = $("#bdgsMainToolPrice .bdgs-footer-price-value");
                var $orderItem = $("#bdgsOrderItemsList .bdgs-main-item");

                if (selectedType === 'monthly') {
                    mainToolInfo.priceToCharge = mainToolInfo.price; // Use mainToolInfo.price for monthly
                    mainToolInfo.selectedSubscriptionType = 'monthly';
                    discount = 0; // FIX: Reset discount

                    // Update main product card price
                    $mainPriceDisplay.text(formatCurrency(mainToolInfo.price) + "/ Monthly");

                    // Update order summary
                    $orderItem.find('.bdgs-item-name').text(mainToolInfo.name + " (Monthly)");
                    $orderItem.find('.bdgs-item-price').text(formatCurrency(mainToolInfo.price));

                } else if (selectedType === 'annually') {
                    mainToolInfo.priceToCharge = mainToolInfo.annualPrice;
                    mainToolInfo.selectedSubscriptionType = 'annually';
                    // FIX: Calculate discount
                    discount = (mainToolInfo.price * 12) - mainToolInfo.annualPrice;
                    if (discount < 0) discount = 0;

                    // Update main product card price
                    $mainPriceDisplay.text(formatCurrency(mainToolInfo.annualPrice) + "/ Annually");

                    // Update order summary
                    $orderItem.find('.bdgs-item-name').text(mainToolInfo.name + " (Annually)");
                    $orderItem.find('.bdgs-item-price').text(formatCurrency(mainToolInfo.annualPrice));
                }

                // Recalculate totals
                updateSummary();
            });
            // === END NEW HANDLER ===

        }); // <-- End of $(document).ready()

        /**Function for check phone number */
        function phone_validate(phno) {
            var regexPattern = new RegExp(/^[0-9-+]+$/);
            return regexPattern.test(phno);
        }
        /**End function for check phone number */

        /**Function for check Email*/
        function isValidEmailAddress(emailAddress) {
            var pattern = new RegExp(/^[+a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/i);
            return pattern.test(emailAddress);
        }
        /**End function for check email */

        /**Function Check Password */
        function password_check(phno) {
            var passwordPattern = new RegExp("^(?=.*[a-z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])(?=.{8,})");
            return passwordPattern.test(phno);
        }
        /**End function Check Password */

        /*Logout code*/
        window.logout = function(params) {
            $("#logout_txt").text("Processing...");
            $.ajax({
                type: "GET",
                url: "/logout",
                success: function(response) {
                    // Force a reload from the server to update session/cookie state
                    location.reload(true);
                },
                error: function() {
                    // Fallback: Still reload even if ajax fails
                    location.reload(true);
                }
            });
        }
        /*logout code*/
    </script>

<?php endif; ?>

<?php
unset($_SESSION["servicesOrdered"]);
unset($_SESSION["service_ordered_type"]);
?>