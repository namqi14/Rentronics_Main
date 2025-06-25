<?php
/**
 * Instruction:
 *
 * 1. Replace the APIKEY with your API Key.
 * 2. Replace the COLLECTION with your Collection ID.
 * 3. Replace the X_SIGNATURE with your X Signature Key
 * 4. Change $is_sandbox = false to $is_sandbox = true for sandbox
 * 5. Replace the http://www.google.com with the full path to the site.
 * 6. Replace the http://www.google.com/success.html with the full path to your success page. *The URL can be overridden later
 * 7. OPTIONAL: Set $amount value.
 * 8. OPTIONAL: Set $fallbackurl if the user are failed to be redirected to the Billplz Payment Page.
 *
 */
$api_key = 'd433f3b1-8a7f-436e-8137-2af7ff6e2511';
$collection_id = 'dp7kyixs';
$x_signature = 'e89e1d5b8b254add82b0b670c462f963d79550fe34ba20ac984b9be638a1a620ad4b777b71f9e1d5b4e2825cec72643b933b11e2c86ad071f9bbf07ca67f1fe1';
$is_sandbox = true;

$websiteurl = 'http://localhost/rentronics';
$successpath = $websiteurl . '/module-payment/successpayment.php';
$callback_url = $websiteurl . '/module-payment/billplz/callback.php';
$redirect_url = $websiteurl . '/module-payment/billplz/redirect.php';
$amount = '';
$fallbackurl = $websiteurl . '/module-payment/successpayment.php';
$description = 'PAYMENT DESCRIPTION';
$reference_1_label = '';
$reference_2_label = '';

$debug = true;
