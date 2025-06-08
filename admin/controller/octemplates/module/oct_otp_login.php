<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOctemplatesModuleOctOtpLogin extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('octemplates/module/oct_otp_login');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');
        $this->load->model('octemplates/module/oct_otp_login');

        $this->document->addScript('view/javascript/octemplates/bootstrap-notify/bootstrap-notify.min.js');
        $this->document->addScript('view/javascript/octemplates/oct_main.js');
        $this->document->addStyle('view/stylesheet/oct_remarket.css');

        $oct_otp_login_info = $this->model_setting_setting->getSetting('oct_otp_login_settings');

        if (!$oct_otp_login_info) {
            $this->response->redirect($this->url->link('octemplates/module/oct_otp_login/install', 'user_token=' . $this->session->data['user_token'], true));
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('oct_otp_login_settings', ['oct_otp_login_settings' => $this->request->post['oct_otp_login_settings']]);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('octemplates/module/oct_otp_login', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['error_status'] = isset($this->error['status']) ? $this->error['status'] : '';
        $data['error_otp_length'] = isset($this->error['otp_length']) ? $this->error['otp_length'] : '';
        $data['error_otp_expiry'] = isset($this->error['otp_expiry']) ? $this->error['otp_expiry'] : '';
        $data['error_max_attempts'] = isset($this->error['max_attempts']) ? $this->error['max_attempts'] : '';
        $data['error_lockout_time'] = isset($this->error['lockout_time']) ? $this->error['lockout_time'] : '';
        $data['error_throttle_time'] = isset($this->error['throttle_time']) ? $this->error['throttle_time'] : '';
        $data['error_sms_template'] = isset($this->error['sms_template']) ? $this->error['sms_template'] : '';

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('octemplates/module/oct_otp_login', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('octemplates/module/oct_otp_login', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        if (isset($this->request->post['oct_otp_login_settings'])) {
            $data['oct_otp_login_settings'] = $this->request->post['oct_otp_login_settings'];
        } else {
            $data['oct_otp_login_settings'] = $this->config->get('oct_otp_login_settings');
        }

        $data['sms_template_status'] = false;
        $data['oct_sms_settings'] = $this->config->get('oct_sms_settings');
        if (isset($data['oct_sms_settings']['status']) && $data['oct_sms_settings']['status'] && isset($data['oct_sms_settings']['templates']['oct_otp_login']['status'])) {
            $data['sms_template_status'] = true;
        } 

        $defaults = array(
            'status' => 0,
            'otp_length' => 6,
            'otp_expiry' => 5,
            'max_attempts' => 10,
            'lockout_time' => 20,
            'throttle_time' => 120,
            'phone_mask' => '+38 (999) 999-99-99',
            'logging' => 0
        );

        foreach ($defaults as $key => $value) {
            if (!isset($data['oct_otp_login_settings'][$key])) {
                $data['oct_otp_login_settings'][$key] = $value;
            }
        }

        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();

        $data['heading_title'] = $this->language->get('heading_title');

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('octemplates/module/oct_otp_login', $data));
    }

    public function install() {
        $this->load->language('octemplates/module/oct_otp_login');

        if (!$this->checkModifyPermission()) {
            return;
        }

        $this->load->model('setting/setting');
        $this->load->model('octemplates/module/oct_otp_login');

        $this->model_octemplates_module_oct_otp_login->install();

        $default_settings = array(
            'oct_otp_login_settings' => array(
                'status' => 0,
                'otp_length' => 6,
                'otp_expiry' => 5,
                'max_attempts' => 10,
                'lockout_time' => 20,
                'throttle_time' => 120,
                'phone_mask' => '+38 (999) 999-99-99',
                'logging' => 0
            )
        );

        $this->model_setting_setting->editSetting('oct_otp_login_settings', $default_settings);

        $this->session->data['success'] = $this->language->get('text_install_success');

        $this->response->redirect($this->url->link('octemplates/module/oct_otp_login', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function uninstall() {
        $this->load->language('octemplates/module/oct_otp_login');

        if (!$this->checkModifyPermission()) {
            return;
        }

        $this->load->model('octemplates/module/oct_otp_login');

        $this->model_octemplates_module_oct_otp_login->uninstall();

        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('oct_otp_login_settings');

        $this->session->data['success'] = $this->language->get('text_uninstall_success');

        $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'octemplates/module/oct_otp_login')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (isset($this->request->post['oct_otp_login_settings'])) {
            $settings = $this->request->post['oct_otp_login_settings'];

            if (empty($settings['otp_length']) || !is_numeric($settings['otp_length']) || $settings['otp_length'] < 4 || $settings['otp_length'] > 10) {
                $this->error['otp_length'] = $this->language->get('error_otp_length');
            }

            if (empty($settings['otp_expiry']) || !is_numeric($settings['otp_expiry']) || $settings['otp_expiry'] <= 0) {
                $this->error['otp_expiry'] = $this->language->get('error_otp_expiry');
            }

            if (empty($settings['max_attempts']) || !is_numeric($settings['max_attempts']) || $settings['max_attempts'] <= 0) {
                $this->error['max_attempts'] = $this->language->get('error_max_attempts');
            }

            if (empty($settings['lockout_time']) || !is_numeric($settings['lockout_time']) || $settings['lockout_time'] <= 0) {
                $this->error['lockout_time'] = $this->language->get('error_lockout_time');
            }

            if (empty($settings['throttle_time']) || !is_numeric($settings['throttle_time']) || $settings['throttle_time'] <= 0) {
                $this->error['throttle_time'] = $this->language->get('error_throttle_time');
            }

        } else {
            $this->error['warning'] = $this->language->get('error_no_settings');
        }

        return !$this->error;
    }

    public function logs() {
        if (!$this->user->hasPermission('access', 'octemplates/module/oct_otp_login')) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(['error' => $this->language->get('error_permission')]));
            return;
        }

        $this->load->language('octemplates/module/oct_otp_login');
        $this->load->model('octemplates/module/oct_otp_login');
        $this->load->model('customer/customer');

        $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
        $limit = $this->config->get('config_limit_admin');
        $start = ($page - 1) * $limit;

        $filter_data = [
            'start' => $start,
            'limit' => $limit
        ];

        $log_total = $this->model_octemplates_module_oct_otp_login->getTotalOtpLogs();
        $results = $this->model_octemplates_module_oct_otp_login->getOtpLogs($filter_data);
        
        $logs = [];
        
        foreach ($results as $result) {
            $customer_info = $this->model_customer_customer->getCustomer($result['customer_id']);
            $email = $customer_info ? $customer_info['email'] : '';
            $profile_link = $this->url->link('customer/customer/edit', 'user_token=' . $this->session->data['user_token'] . '&customer_id=' . $result['customer_id'], true);

            $logs[] = [
                'log_id'       => $result['log_id'],
                'customer_id'  => $result['customer_id'],
                'telephone'    => $result['telephone'],
                'email'        => $email,
                'profile_link' => '<a href="' . $profile_link . '" target="_blank">' . $email . '</a>',
                'customer_firstname' => $customer_info['firstname'],
                'customer_lastname' => $customer_info['lastname'],
                'status'       => $result['status'],
                'date_added'   => $result['date_added']
            ];
        }

        $pagination = new Pagination();
        $pagination->total = $log_total;
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->url = $this->url->link('octemplates/module/oct_otp_login/logs', 'user_token=' . $this->session->data['user_token'] . '&page={page}', true);

        $pagination_html = $pagination->render();

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'logs' => $logs,
            'pagination' => $pagination_html
        ]));
    }
    
    public function deleteLogs() {
        $this->load->language('octemplates/module/oct_otp_login');

        if (!$this->checkModifyPermission(true)) {
            return;
        }

        $this->load->model('octemplates/module/oct_otp_login');

        $this->model_octemplates_module_oct_otp_login->deleteOtpLogs();

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => $this->language->get('text_success_logs_deleted')]));
    }

    public function ipLogs() {
        if (!$this->user->hasPermission('access', 'octemplates/module/oct_otp_login')) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(['error' => $this->language->get('error_permission')]));
            return;
        }
    
        $this->load->language('octemplates/module/oct_otp_login');
        $this->load->model('octemplates/module/oct_otp_login');
    
        $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
        $limit = $this->config->get('config_limit_admin');
        $start = ($page - 1) * $limit;
    
        $filter_data = [
            'start' => $start,
            'limit' => $limit
        ];
    
        $log_total = $this->model_octemplates_module_oct_otp_login->getTotalOtpIpLogs();
        $results = $this->model_octemplates_module_oct_otp_login->getOtpIpLogs($filter_data);
    
        $logs = [];
    
        foreach ($results as $result) {
            $logs[] = [
                'attempt_id'    => $result['attempt_id'],
                'ip_address'    => $result['ip_address'],
                'attempt_count' => $result['attempt_count'],
                'last_attempt'  => $result['last_attempt']
            ];
        }
    
        $pagination = new Pagination();
        $pagination->total = $log_total;
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->url = $this->url->link('octemplates/module/oct_otp_login/ipLogs', 'user_token=' . $this->session->data['user_token'] . '&page={page}', true);
    
        $pagination_html = $pagination->render();
    
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'logs' => $logs,
            'pagination' => $pagination_html
        ]));
    }
    
    public function deleteIpLogs() {
        $this->load->language('octemplates/module/oct_otp_login');
    
        if (!$this->checkModifyPermission(true)) {
            return;
        }
    
        $this->load->model('octemplates/module/oct_otp_login');
    
        $this->model_octemplates_module_oct_otp_login->deleteOtpIpLogs();
    
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => $this->language->get('text_success_ip_logs_deleted')]));
    }    

    private function checkModifyPermission($json_response = false) {
        if (!$this->user->hasPermission('modify', 'octemplates/module/oct_otp_login')) {
            $error_message = $this->language->get('error_permission');
    
            if ($json_response) {
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode(['error' => $error_message]));
            } else {
                $this->session->data['error'] = $error_message;
                $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
            }
            return false;
        }
        return true;
    }
}
