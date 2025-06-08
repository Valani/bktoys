<?php
// Heading
$_['heading_title']             = 'Login';

// Entry
$_['entry_telephone']           = 'Phone Number';
$_['entry_otp']                 = 'One-Time Password (OTP)';

// Button
$_['button_send_otp']           = 'Send';
$_['button_verify_otp']         = 'Verify';
$_['button_resend_otp']         = 'Send New Code';

// Text
$_['text_otp_sent']             = 'Verification code sent to your phone number.';
$_['text_otp_resent']           = 'New verification code sent to your phone number.';
$_['text_success_login']        = 'You have successfully logged in and will be redirected in a moment.';
$_['default_sms_template']      = 'Your verification code: [code]';
$_['text_entry_telephone']      = 'Please enter your phone number to receive a verification code.';
$_['text_choose_login']         = 'Login method:';
$_['text_otp_tab']              = 'By Phone';
$_['text_email_tab']            = 'By E-mail';
$_['text_otp_not_sent']         = 'If you have not received the code within 60 seconds, please do the following:';
$_['text_otp_not_sent_list']    = '<ul class="pl-3"><li>check the correctness of the phone number entered;</li><li>make sure there is an Internet connection;</li><li>check the memory on the phone, try deleting a few dialogs to make sure there is free space;</li><li>reboot the phone.</li></ul>';
$_['text_otp_not_received']     = 'Did not receive the code?';

// Error
$_['error_module_disabled']     = 'Phone login module is disabled.';
$_['error_telephone']           = 'Please enter a valid phone number.';
$_['error_not_found']           = 'User with this phone number not found.';
$_['error_otp']                 = 'Invalid OTP code. Please try again.';
$_['error_otp_expired']         = 'OTP code expired. Please request a new one.';
$_['error_otp_not_found']       = 'OTP code not found. Please request a new one.';
$_['error_max_attempts']        = 'You have exceeded the maximum number of attempts. Please try again in %s minutes.';
$_['error_throttle']            = 'Next OTP request attempt is possible in %s seconds.';
$_['error_session_expired']     = 'Session expired. Please start the process again.';
$_['error_empty_telephone']     = 'Please enter a phone number.';
$_['error_csrf']                = 'Invalid CSRF token.';