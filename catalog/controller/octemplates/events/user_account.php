<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesEventsUserAccount extends Controller {
    public function accountLoginOtp(&$route, &$data) {
		$login_settings = $this->config->get('oct_otp_login_settings');
		$data['oct_otp_login_status'] = !empty($login_settings['status']) ? 1 : 0;
    }
}