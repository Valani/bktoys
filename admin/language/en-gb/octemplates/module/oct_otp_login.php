<?php
// Heading
$_['heading_title']             = 'Octemplates - SMS Authorization (OTP)';

// Text
$_['text_extension']            = 'Extensions';
$_['text_success']              = 'Module settings successfully changed!';
$_['text_edit']                 = 'Edit Phone Authorization Module';
$_['text_enabled']              = 'Enabled';
$_['text_disabled']             = 'Disabled';
$_['text_install_success']      = 'Module successfully installed!';
$_['text_uninstall_success']    = 'Module successfully removed!';
$_['text_logs']                 = 'OTP Logs';
$_['text_list']                 = 'Log List';
$_['text_pagination']           = 'Showing %d to %d of %d (%d Pages)';
$_['text_success_logs_deleted'] = 'Logs successfully deleted!';
$_['text_loading']              = 'Loading...';
$_['text_no_results']           = 'No data to display.';
$_['text_confirm_delete']       = 'Are you sure you want to delete all logs?';
$_['text_success_logs_deleted'] = 'Logs successfully deleted!';
$_['text_success_ip_logs_deleted'] = 'IP logs successfully deleted!';
$_['help_ip_logs']              = 'This list contains all IP addresses that tried to log in and entered an incorrect phone number or OTP.';

// Entry
$_['entry_status']              = 'Status';
$_['entry_otp_length']          = 'OTP Length';
$_['entry_otp_expiry']          = 'OTP Expiry (minutes)';
$_['entry_max_attempts']        = 'Max Attempts';
$_['entry_lockout_time']        = 'Lockout Time (minutes)';
$_['entry_throttle_time']       = 'Throttle Time (seconds)';
$_['entry_phone_mask']          = 'Phone Mask';
$_['entry_logging']             = 'Enable Logging';
$_['entry_customer_id']         = 'Customer ID';
$_['entry_telephone']           = 'Telephone';

// Help
$_['help_otp_length']           = 'Number of digits in the OTP code (from 4 to 10).';
$_['help_otp_expiry']           = 'Time in minutes during which the OTP is valid.';
$_['help_max_attempts']         = 'Maximum number of incorrect OTP or phone number entries before lockout.';
$_['help_lockout_time']         = 'Time in minutes during which the user will be locked out after exceeding attempts.';
$_['help_throttle_time']        = 'Minimum time in seconds between OTP requests to prevent spam.';
$_['help_logging']              = 'Enable to log OTP attempts.';
$_['help_logs']                 = 'This list contains all OTP entry attempts by users.';

// Error
$_['error_permission']          = 'Warning: You do not have permission to modify the Phone Authorization module!';
$_['error_status']              = 'Module status is required!';
$_['error_otp_length']          = 'OTP length must be a number between 4 and 10!';
$_['error_otp_expiry']          = 'OTP expiry must be greater than 0!';
$_['error_max_attempts']        = 'Max attempts must be greater than 0!';
$_['error_lockout_time']        = 'Lockout time must be greater than 0!';
$_['error_throttle_time']       = 'Throttle time must be greater than 0!';
$_['error_no_settings']         = 'Module settings not found!';
$_['warning_sms_template']      = 'Warning: SMS message template is not installed or disabled! Activate the "Authorization by SMS" template in the "SMS Notification Settings" module!';

// Tabs
$_['tab_settings']              = 'Settings';
$_['tab_logs']                  = 'OTP Logs';
$_['tab_ip_logs']               = 'IP Logs';

// Buttons
$_['button_save']               = 'Save';
$_['button_cancel']             = 'Cancel';
$_['button_delete_logs']        = 'Delete Logs';
$_['button_close']              = 'Close';

// Columns
$_['column_log_id']             = 'Log ID';
$_['column_customer_id']        = 'Customer ID';
$_['column_telephone']          = 'Telephone';
$_['column_status']             = 'Status';
$_['column_date_added']         = 'Date Added';
$_['column_attempt_id']         = 'ID';
$_['column_ip_address']         = 'IP Address';
$_['column_attempt_count']      = 'Attempt Count';
$_['column_last_attempt']       = 'Last Attempt';