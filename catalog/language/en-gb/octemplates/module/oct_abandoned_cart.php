<?php

// text
$_['text_invalid_cron_pass']                = 'Error: Invalid cron password!';
$_['text_cron_done']                        = 'Cron executed successfully!';
$_['text_module_disabled']                  = 'Module disabled!';
$_['text_error_invalid_api_key']            = 'Error: Invalid API key!';
$_['text_manual_send_nothing']              = 'Sending error, or the cart has already been converted!';
$_['text_manual_send_ok']                   = 'Message sent successfully!';
$_['text_error_invalid_restore_token']      = 'Error: Invalid restore token!';
$_['text_success_restore_cart']             = 'Cart restored successfully!';
$_['text_error_no_cart']                    = 'Error: Cart not found!';

// Email
$_['email_subject']                         = 'We saved the items you were interested in';
$_['email_message']                         = '<div style="font-weight: bold; font-size: 16px; margin: 4px 24px;">Good day, [customer_name]!<br>[promo_code]<br> The items in the cart are waiting for your decision:</div> <br>[products]<br> You can quickly purchase them by following the link: <br><br><a style="font-size: 20px; font-weight: bold;" href="[restore_link]">Complete the purchase</a><br> <br> <br> Best regards, [store_name]';
$_['email_promo_code']                      = 'Your personal discount promo code:';   

// SMS
$_['default_sms_template']                  = 'Good day![br]The items in the cart are waiting for your decision: [restore_link][br]Best regards, [store_name]';
$_['default_sms_template_vs_promocode']     = 'Good day![br]Your promo code: [promo_code][br]The items in the cart are waiting for your decision: [restore_link][br]Best regards, [store_name]';