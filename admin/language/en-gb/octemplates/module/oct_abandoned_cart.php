<?php
// Header
$_['heading_title']                 = 'Octemplates Smart Checkout - Abandoned Carts';

// Text
$_['text_extension']                = 'Extensions';
$_['text_success']                  = 'Settings successfully changed!';
$_['text_install_success']          = 'Module successfully installed!';
$_['text_edit']                     = 'Edit Abandoned Carts Module';
$_['text_lost_carts']               = 'Abandoned Carts';
$_['text_settings']                 = 'Settings';
$_['text_loading']                  = 'Loading...';
$_['text_error']                    = 'Execution error!';
$_['text_converted']                = 'converted';
$_['text_converted_success']        = 'Cart successfully converted!';
$_['text_percent']                  = 'Percent';
$_['text_fixed']                    = 'Fixed amount';
$_['text_no_results']               = 'No records found.';
$_['text_active']                   = 'unprocessed';
$_['text_copy_success']             = 'Code successfully copied!';
$_['text_copy_failed']              = 'Code copying error!';
$_['text_error_loading_carts']      = 'No carts found!';
$_['text_view_profile']             = 'View profile';
$_['text_quantity']                 = 'Quantity: ';
$_['text_link_copied']              = 'Link copied!';
$_['text_confirm_convert']          = 'Are you sure you want to mark this cart as converted?';
$_['text_convert_success']          = 'Cart successfully marked as converted!';
$_['text_confirm_delete']           = 'Are you sure you want to delete this cart?';
$_['text_delete_success']           = 'Cart successfully deleted!';
$_['text_shortcode_customer_name']  = 'Customer name';
$_['text_shortcode_restore_link']   = 'Cart restore link';
$_['text_shortcode_products']       = 'Products in cart';
$_['text_shortcode_coupon_code']    = 'Coupon code (if used)';
$_['text_shortcode_store']          = 'Store name';
$_['text_filter']                   = 'Filter';
$_['text_filter_firstname']         = 'First name';
$_['text_filter_lastname']          = 'Last name';
$_['text_filter_email']             = 'E-mail';
$_['text_filter_phone']             = 'Phone';
$_['text_filter_status']            = 'Status';
$_['text_filter_ip_added']          = 'IP when cart was first added';
$_['text_filter_ip_changed']        = 'IP when cart was changed';
$_['text_filter_date_added_start']  = 'Date added (from)';
$_['text_filter_date_added_end']    = 'Date added (to)';
$_['text_filter_date_modified_start'] = 'Date modified (from)';
$_['text_filter_date_modified_end'] = 'Date modified (to)';
$_['text_status_any']               = 'Any';
$_['text_never']                    = '-- Never --';
$_['text_email_sent_success']       = 'Message successfully sent!';
$_['text_manual_send_ok']           = 'Message successfully sent!';
$_['text_shortcodes']               = 'Available shortcodes for email body';

// Column
$_['column_cart_id']                = 'ID';
$_['column_customer']               = 'Customer';
$_['column_email']                  = 'E-mail';
$_['column_date_added']             = 'Date added';
$_['column_date_modified']          = 'Date modified';
$_['colim_last_reminder']           = 'Last reminder';
$_['column_reminder_count']         = 'Reminders';
$_['column_status']                 = 'Status';
$_['column_action']                 = 'Select action';
$_['column_products']               = 'Products';

// Entry
$_['entry_status']                  = 'Status';
$_['entry_reminder_hours_first']    = 'Hours to first reminder';
$_['entry_reminder_hours_second']   = 'Hours to second reminder';
$_['entry_coupon_discount']         = 'Discount amount';
$_['entry_coupon_type']             = 'Discount type';
$_['entry_coupon_lifetime']         = 'Coupon validity period';
$_['entry_coupon_status']           = 'Generate discount coupon';
$_['entry_cookie_lifetime']         = 'Cookie and cart validity period';
$_['entry_cron_password']           = 'Cron call password';
$_['entry_api_key']                 = 'API key';
$_['entry_cron']                    = 'Cron URL';
$_['entry_converted_lifetime']      = 'Days to delete converted carts';
$_['entry_can_login_by_token']      = 'Allow login by token';
$_['entry_email_template_status']   = 'Custom email templates';
$_['entry_email_subject']           = 'Email subject';
$_['entry_email_body']              = 'Email body';

// Tabs
$_['tab_lost_carts']                = 'Carts';
$_['tab_settings']                  = 'Settings';
$_['tab_email_templates']           = 'Email templates';

// Help
$_['help_reminder_hours_first']     = 'Specify the number of hours after which the first reminder will be sent (0 - do not send).';
$_['help_reminder_hours_second']    = 'Specify the number of hours after which the second reminder will be sent (0 - do not send).';
$_['help_coupon_discount']          = 'Set the discount amount to be applied to the coupon.';
$_['help_coupon_type']              = 'Select the discount type: percentage or fixed amount.';
$_['help_coupon_lifetime']          = 'Specify the number of days the coupon will be valid.';
$_['help_coupon_status']            = 'Generate a discount coupon for abandoned carts when sending reminders.';
$_['help_status']                   = 'Enable or disable the module.';
$_['help_cookie_lifetime']          = 'Specify the number of days the cookie and cart will be valid.';
$_['help_cron_password']            = 'Specify the password for cron call.';
$_['help_api_key']                  = 'API key for module API access.';
$_['help_cron']                     = 'Use this URL for cron call. Frequency of call - depending on reminder settings.';
$_['help_converted_lifetime']       = 'Specify the number of days after which converted carts will be deleted.';
$_['help_can_login_by_token']       = '<span class="text-danger"><b>Attention, when activating this option:</b><br></span>You will allow automatic login to the account by a secret token in the link if the cart was created by a user who was logged in on the site.<br/>It is advisable to add a cron job on the hosting/server to delete old carts.<br/>We recommend setting the "Cookie and cart validity period" option not too high, up to 10 days.';
$_['help_email_template_status']    = 'Customize email templates.';

// Buttons
$_['button_save']                   = 'Save';
$_['button_cancel']                 = 'Cancel';
$_['button_convert']                = 'Mark as converted';
$_['button_delete']                 = 'Delete this cart';
$_['button_send_email']             = 'Send notification with cart link to customer';
$_['button_copy_link']              = 'Copy cart link';
$_['button_filter']                 = 'Apply filter';
$_['button_toggle_filter']          = 'Show/hide filter';

// Error
$_['error_permission']              = 'You do not have permission to modify module settings!';
$_['error_warning']                 = 'Check the form for errors!';
$_['error_complete_all_fields']     = 'Please complete all fields!';
$_['error_convert_failed']          = 'Cart conversion error, or this cart is already converted!';
$_['error_invalid_cart_id']         = 'Invalid cart ID!';
$_['error_delete_failed']           = 'Cart deletion error!';