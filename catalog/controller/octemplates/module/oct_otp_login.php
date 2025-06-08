<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOctemplatesModuleOctOtpLogin extends Controller {

    public function index() {
        return;
    }

    public function sendOtp() {

        if (!$this->isValidRequest()) {
            $this->redirectToNotFound();
            return;
        }

        if (!$this->isModuleEnabled()) {
            return $this->jsonResponse(['error' => $this->language->get('error_module_disabled')]);
        }

        $this->load->language('octemplates/module/oct_otp_login');
        $this->load->model('octemplates/module/oct_otp_login');

        $ip_address = $this->request->server['REMOTE_ADDR'];
        $lockout_time = $this->model_octemplates_module_oct_otp_login->getLockoutTime($ip_address);
        
        if ($this->model_octemplates_module_oct_otp_login->isIpBanned($ip_address)) {
            return $this->jsonResponse(['error' => sprintf($this->language->get('error_max_attempts'), $lockout_time)]);
        }

        if (!isset($this->request->post['csrf_token']) || !isset($this->session->data['csrf_token']) || $this->request->post['csrf_token'] !== $this->session->data['csrf_token']) {
            return $this->jsonResponse(['error' => $this->language->get('error_csrf')]);
        }

        $json = ['error' => false];

        $telephone = $this->sanitizeTelephone($this->request->post['telephone'] ?? '');

        if (!$this->isTelephoneValid($telephone)) { 
            $this->model_octemplates_module_oct_otp_login->incrementLoginAttempt($ip_address);
            return $this->jsonResponse(['error' => $this->language->get('error_telephone')]);
        }

        if ($this->request->server['REQUEST_METHOD'] === 'POST') {
            $customer_info = $this->getCustomerByTelephone($telephone);

            if (!$customer_info) {
                $this->model_octemplates_module_oct_otp_login->incrementLoginAttempt($ip_address);
                return $this->jsonResponse(['error' => $this->language->get('error_not_found')]);
            }

            $this->session->data['otp_customer_id'] = $customer_info['customer_id'];

            if (isset($this->request->post['redirect'])) {
                $redirect = $this->request->post['redirect'];
                $config_url = $this->config->get('config_url');
                $config_ssl = $this->config->get('config_ssl');
                
                if ($this->isValidRedirect($redirect, $config_url, $config_ssl)) {
                    $this->session->data['otp_redirect'] = $redirect;
                } else {
                    $this->session->data['otp_redirect'] = $this->getDefaultRedirect();
                }
            } else {
                $this->session->data['otp_redirect'] = $this->getDefaultRedirect();
            }

            if ($this->isMaxAttemptsExceeded($customer_info['customer_id'])) {
                $otp_data = $this->model_octemplates_module_oct_otp_login->getOtp($customer_info['customer_id']);
                $lockout_time = $this->getOtpSettings('lockout_time', 15);
                $time_left = ceil((strtotime($otp_data['last_attempt']) + $lockout_time * 60 - time()) / 60);
            
                return $this->jsonResponse([
                    'error' => sprintf($this->language->get('error_max_attempts'), $time_left),
                    'max_attempts_exceeded' => true
                ]);
            }            

            if ($this->isThrottleExceeded($customer_info['customer_id'])) {
                $otp_data = $this->model_octemplates_module_oct_otp_login->getOtp($customer_info['customer_id']);
                $throttle_time = $this->getOtpSettings('throttle_time', 60);
                $time_left = max(0, strtotime($otp_data['date_added']) + $throttle_time - time());
            
                return $this->jsonResponse(['error' => sprintf($this->language->get('error_throttle'), $time_left), 'otp_throttle' => true]);
            }
            
            $this->model_octemplates_module_oct_otp_login->resetLoginAttempts($ip_address);

            $otp_code = $this->generateOtpCode($customer_info['customer_id']);
            $this->sendSms($telephone, $otp_code);

            return $this->jsonResponse(['success' => true, 'message' => $this->language->get('text_otp_sent')]);
        } else {
            return $this->jsonResponse(['error' => $this->language->get('error_invalid_request')]);
        }
    }

    public function verifyOtp() {

        if (!$this->isValidRequest()) {
            $this->redirectToNotFound();
            return;
        }
    
        if (!$this->isModuleEnabled()) {
            return $this->jsonResponse(['error' => $this->language->get('error_module_disabled')]);
        }
    
        $moduleSettings = $this->getOtpSettings();
    
        $this->load->model('octemplates/module/oct_otp_login');
        $this->load->model('account/customer');
        $this->load->language('octemplates/module/oct_otp_login');
        
        $ip_address = $this->request->server['REMOTE_ADDR'];
        $lockout_time = $this->model_octemplates_module_oct_otp_login->getLockoutTime($ip_address);
        
        if ($this->model_octemplates_module_oct_otp_login->isIpBanned($ip_address)) {
            return $this->jsonResponse(['error' => sprintf($this->language->get('error_max_attempts'), $lockout_time)]);
        }

        if (!isset($this->request->post['csrf_token']) || !isset($this->session->data['csrf_token']) || $this->request->post['csrf_token'] !== $this->session->data['csrf_token']) {
            return $this->jsonResponse(['error' => $this->language->get('error_csrf')]);
        }
    
        $json = ['error' => false];
    
        $otp_code_input = $this->request->post['otp_code'] ?? '';
    
        if (!$this->isOtpCodeValid($otp_code_input)) {
            return $this->jsonResponse(['error' => $this->language->get('error_otp')]);
        }
    
        $customer_id = $this->session->data['otp_customer_id'] ?? 0;
        
        if (!$customer_id) {
            return $this->jsonResponse(['error' => $this->language->get('error_session_expired')]);
        }
    
        $otp_data = $this->model_octemplates_module_oct_otp_login->getOtp($customer_id);
    
        if (!$otp_data) {
            return $this->jsonResponse(['error' => $this->language->get('error_otp_not_found')]);
        }
    
        $customer_info = $this->model_account_customer->getCustomer($customer_id);
        $telephone = $customer_info['telephone'] ?? '';
    
        if ($this->isMaxAttemptsExceeded($customer_id)) {
            $lockout_time = $this->getOtpSettings('lockout_time', 15);
            $time_left = ceil((strtotime($otp_data['last_attempt']) + $lockout_time * 60 - time()) / 60);
        
            return $this->jsonResponse([
                'error' => sprintf($this->language->get('error_max_attempts'), $time_left),
                'max_attempts_exceeded' => true
            ]);
        }
    
        if (strtotime($otp_data['expires_at']) < time()) {
            $this->model_octemplates_module_oct_otp_login->deleteOtp($customer_id);
            return $this->jsonResponse(['error' => $this->language->get('error_otp_expired')]);
        }
    
        $otp_code_input_hashed = hash('sha256', $otp_code_input);
        if ($otp_code_input_hashed !== $otp_data['otp_code']) {
            $this->model_octemplates_module_oct_otp_login->incrementAttempts($customer_id);
            $logging = $moduleSettings['logging'] ?? false;
            $this->model_octemplates_module_oct_otp_login->logOtpAttempt($customer_id, $telephone, 'failure', $logging);
            return $this->jsonResponse(['error' => $this->language->get('error_otp')]);
        }
    
        $this->model_octemplates_module_oct_otp_login->deleteOtp($customer_id);
        $this->model_octemplates_module_oct_otp_login->resetLoginAttempts($ip_address);
        $this->model_octemplates_module_oct_otp_login->clearOldLoginAttempts();
    
        $this->loginCustomer($customer_id);
    
        $redirect = $this->session->data['otp_redirect'] ?? $this->url->link('account/account', '', true);
    
        unset($this->session->data['otp_customer_id'], $this->session->data['otp_redirect'], $this->session->data['csrf_token']);
    
        $logging = $moduleSettings['logging'] ?? false;
        $this->model_octemplates_module_oct_otp_login->logOtpAttempt($customer_id, $telephone, 'success', $logging);
    
        return $this->jsonResponse([
            'success' => true,
            'message' => $this->language->get('text_success_login'),
            'redirect' => str_replace('&amp;', '&', $redirect)
        ]);
    }

    private function isValidRequest() {
        return isset($this->request->server['HTTP_X_REQUESTED_WITH']) &&
               !empty($this->request->server['HTTP_X_REQUESTED_WITH']) &&
               strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    private function redirectToNotFound() {
        $this->response->redirect($this->url->link('error/not_found', '', true));
    }

    private function jsonResponse($data) {
        $this->load->language('octemplates/module/oct_otp_login');
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

    private function isValidRedirect($redirect, $config_url, $config_ssl) {
        return strpos($redirect, $config_url) !== false || strpos($redirect, $config_ssl) !== false;
    }
    
    private function getDefaultRedirect() {
        return $this->url->link('account/account', '', true);
    }

    private function isOtpCodeValid($otp_code_input) {
        return preg_match('/^\d+$/', $otp_code_input);
    }

    private function isModuleEnabled() {
        return $this->config->get('oct_otp_login_settings');
    }

    private function getOtpSettings($key = null, $default = null) {
        $settings = $this->config->get('oct_otp_login_settings');
        return $key ? ($settings[$key] ?? $default) : $settings;
    }

    private function sanitizeTelephone($telephone) {
        return preg_replace("/[^0-9\(\)\-\_\+\s]/", '', $telephone);
    }

    private function isTelephoneValid($telephone) {
        $cleanedTelephone = str_replace(['_', '-', '(', ')', '+', ' '], '', $telephone);
        $phoneMask = str_replace(['_', '-', '(', ')', '+', ' '], '', $this->getOtpSettings('phone_mask', ''));

        $theme_data = $this->config->get('theme_oct_remarket_data');
        $phoneRegex = $theme_data['phone_regex'] ?? '';

        if (utf8_strlen($phoneMask) > 0 && utf8_strlen($cleanedTelephone) !== utf8_strlen($phoneMask)) {
            return false;
        }
        
        if (!empty($phoneRegex)) {
            if (@preg_match($phoneRegex, '') !== false) {
                if (!preg_match($phoneRegex, $telephone)) {
                    return false;
                }
            } else {
                return false;
            }
        }

        return utf8_strlen($cleanedTelephone) >= 3 && utf8_strlen($cleanedTelephone) <= 15;
    }

    private function getCustomerByTelephone($telephone) {
        $this->load->model('octemplates/module/oct_otp_login');
        return $this->model_octemplates_module_oct_otp_login->getCustomerByTelephone($telephone);
    }

    private function isThrottleExceeded($customer_id) {
        $last_request_time = $this->model_octemplates_module_oct_otp_login->getLastOtpRequestTime($customer_id);
        return (time() - $last_request_time) < $this->getOtpSettings('throttle_time', 60);
    }

    private function isMaxAttemptsExceeded($customer_id) {
        $otp_data = $this->model_octemplates_module_oct_otp_login->getOtp($customer_id);
    
        if ($otp_data) {
            $max_attempts = $this->getOtpSettings('max_attempts', 5);
            $lockout_time = $this->getOtpSettings('lockout_time', 15);
    
            if ($otp_data['attempts'] >= $max_attempts && strtotime($otp_data['last_attempt']) + $lockout_time * 60 > time()) {
                return true;
            }
        }
    
        return false;
    }
    

    private function generateOtpCode($customer_id) {
        $otp_length = (int) $this->getOtpSettings('otp_length', 6);
        $otp_code = rand(pow(10, $otp_length - 1), pow(10, $otp_length) - 1);

        $hashed_otp_code = hash('sha256', $otp_code);
        $expires_at = date('Y-m-d H:i:s', time() + $this->getOtpSettings('otp_expiry', 5) * 60);

        $this->model_octemplates_module_oct_otp_login->createOtp($customer_id, $hashed_otp_code, $expires_at);
        return $otp_code;
    }

    private function sendSms($telephone, $otp_code) {
        $this->load->language('octemplates/module/oct_otp_login');

        $oct_sms_settings = $this->config->get('oct_sms_settings');
        $template_code = 'oct_otp_login';
        $language_id = $this->config->get('config_language_id');

        if (isset($oct_sms_settings['templates'][$template_code]['status']) && $oct_sms_settings['templates'][$template_code]['status']) {
            if (isset($oct_sms_settings['templates'][$template_code]['message'][$language_id]) && !empty($oct_sms_settings['templates'][$template_code]['message'][$language_id]) && isset($oct_sms_settings['templates'][$template_code]['edit_localization'])) {
                $sms_template = $oct_sms_settings['templates'][$template_code]['message'][$language_id];
            } else {
                $sms_template = $this->language->get('default_sms_template');
            }

            if (!empty($sms_template)) {
                $replace = array(
                    '[code]' => $otp_code,
                    '[store]' => $this->config->get('config_name')
                );

                $sms_message = str_replace(array_keys($replace), array_values($replace), $sms_template);

                $this->load->model('octemplates/module/oct_sms_notify');
                $this->model_octemplates_module_oct_sms_notify->sendNotification(array(
                    'phone' => $telephone,
                    'message' => $sms_message,
                    'template_code' => $template_code,
                    'access_token' => $oct_sms_settings['oct_sms_token']
                ));
            }
        }

        $json['success'] = $this->language->get('text_success');
    }    

    private function loginCustomer($customer_id) {
        $this->load->model('account/customer');
        $customer_info = $this->model_account_customer->getCustomer($customer_id);

        if ($customer_info) {
            $this->customer->login($customer_info['email'], '', true);
        }
    }
}
