<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOctemplatesModuleOctReviewReminder extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('octemplates/module/oct_review_reminder');

        //Add Codemirror Styles && Scripts
        $this->document->addScript('view/javascript/codemirror/lib/codemirror.js');
        $this->document->addScript('view/javascript/codemirror/lib/xml.js');
        $this->document->addScript('view/javascript/codemirror/lib/formatting.js');
        $this->document->addStyle('view/javascript/codemirror/lib/codemirror.css');
        $this->document->addStyle('view/javascript/codemirror/theme/monokai.css');

        //Add Summernote Styles && Scripts
        $this->document->addScript('view/javascript/summernote/summernote.js');
        $this->document->addScript('view/javascript/summernote/summernote-image-attributes.js');
        $this->document->addScript('view/javascript/summernote/opencart.js');
        $this->document->addStyle('view/javascript/summernote/summernote.css');
        $this->document->addScript('view/javascript/octemplates/bootstrap-notify/bootstrap-notify.min.js');
        $this->document->addScript('view/javascript/octemplates/oct_main.js');
        $this->document->addStyle('view/stylesheet/oct_remarket.css');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');
        $this->load->model('localisation/language');
        $this->load->model('localisation/order_status');

        $oct_review_reminder_info = $this->model_setting_setting->getSetting('oct_review_reminder');

        if (!$oct_review_reminder_info) {
            $this->response->redirect($this->url->link('octemplates/module/oct_review_reminder/install', 'user_token=' . $this->session->data['user_token'], true));
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('oct_review_reminder', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('octemplates/module/oct_review_reminder', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }

        $data['heading_title'] = $this->language->get('heading_title');

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

        if (isset($this->error['days_after_order'])) {
            $data['error_days_after_order'] = $this->error['days_after_order'];
        } else {
            $data['error_days_after_order'] = '';
        }

        if (isset($this->error['cron_password'])) {
            $data['error_cron_password'] = $this->error['cron_password'];
        } else {
            $data['error_cron_password'] = '';
        }

        if (isset($this->error['order_status'])) {
            $data['error_order_status'] = $this->error['order_status'];
        } else {
            $data['error_order_status'] = '';
        }

        $data['user_token'] = $this->session->data['user_token'];

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('octemplates/module/oct_review_reminder', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('octemplates/module/oct_review_reminder', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        if (isset($this->request->post['oct_review_reminder_status'])) {
            $data['oct_review_reminder_status'] = $this->request->post['oct_review_reminder_status'];
        } else {
            $data['oct_review_reminder_status'] = $this->config->get('oct_review_reminder_status');
        }

        if (isset($this->request->post['oct_review_reminder_days_after_order'])) {
            $data['oct_review_reminder_days_after_order'] = $this->request->post['oct_review_reminder_days_after_order'];
        } else {
            $data['oct_review_reminder_days_after_order'] = $this->config->get('oct_review_reminder_days_after_order');
        }

        if (isset($this->request->post['oct_review_reminder_order_status'])) {
            $data['oct_review_reminder_order_status'] = $this->request->post['oct_review_reminder_order_status'];
        } else {
            $data['oct_review_reminder_order_status'] = $this->config->get('oct_review_reminder_order_status');
        }

        if (isset($this->request->post['oct_review_reminder_cron_password'])) {
            $data['oct_review_reminder_cron_password'] = $this->request->post['oct_review_reminder_cron_password'];
        } else {
            $data['oct_review_reminder_cron_password'] = $this->config->get('oct_review_reminder_cron_password');
        }

        if (isset($data['oct_review_reminder_cron_password']) && $data['oct_review_reminder_cron_password'] && !isset($this->error['cron_password'])) {
            $site_link = $this->request->server['HTTPS'] ? HTTP_CATALOG : HTTPS_CATALOG;
            $data['cron_url'] = $site_link . "index.php?route=octemplates/module/oct_review_reminder/cron&cron_secret=" . $data['oct_review_reminder_cron_password'];
        }

        if (isset($this->request->post['oct_review_reminder_etemplates_status'])) {
            $data['oct_review_reminder_etemplates_status'] = $this->request->post['oct_review_reminder_etemplates_status'];
        } else {
            $data['oct_review_reminder_etemplates_status'] = $this->config->get('oct_review_reminder_etemplates_status');
        }

        $data['oct_review_reminder_email_template'] = array();
        if (isset($this->request->post['oct_review_reminder_email_template'])) {
            $data['oct_review_reminder_email_template'] = $this->request->post['oct_review_reminder_email_template'];
        } elseif ($this->config->get('oct_review_reminder_email_template')) {
            $data['oct_review_reminder_email_template'] = $this->config->get('oct_review_reminder_email_template');
        }

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
        $data['languages'] = $this->model_localisation_language->getLanguages();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('octemplates/module/oct_review_reminder', $data));
    }

    public function install() {
        $this->load->language('octemplates/module/oct_review_reminder');

        if (!$this->checkModifyPermission()) {
            return;
        }

        $this->load->model('octemplates/module/oct_review_reminder');
        $this->model_octemplates_module_oct_review_reminder->install();
        $this->load->model('localisation/order_status');
        
        $order_statuses = $this->model_localisation_order_status->getOrderStatuses();
        
        $order_status_ids = array_map(function($status) {
            return $status['order_status_id'];
        }, $order_statuses);
    
        $default_settings = [
            'oct_review_reminder_status' => 1,
            'oct_review_reminder_days_after_order' => 10,
            'oct_review_reminder_order_status' => $order_status_ids,
            'oct_review_reminder_sms_integration' => 0,
            'oct_review_reminder_email_template' => ''
        ];
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('oct_review_reminder', $default_settings);
    
        $this->session->data['success'] = $this->language->get('text_install_success');
    
        $this->response->redirect($this->url->link('octemplates/module/oct_review_reminder', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function uninstall() {
        $this->load->language('octemplates/module/oct_review_reminder');

        if (!$this->checkModifyPermission()) {
            return;
        }
        
        $this->load->model('octemplates/module/oct_review_reminder');
        $this->model_octemplates_module_oct_review_reminder->uninstall();

        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('oct_review_reminder');

        $this->session->data['success'] = $this->language->get('text_uninstall_success');

        $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'octemplates/module/oct_review_reminder')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (isset($this->request->post['oct_review_reminder_days_after_order']) && (!is_numeric($this->request->post['oct_review_reminder_days_after_order']) || (int)$this->request->post['oct_review_reminder_days_after_order'] <= 0)) {
            $this->error['days_after_order'] = $this->language->get('error_days_after_order');
        }

        if (isset($this->request->post['oct_review_reminder_cron_password']) && strlen($this->request->post['oct_review_reminder_cron_password']) < 5) {
            $this->error['cron_password'] = $this->language->get('error_cron_password');
        }

        if (!isset($this->request->post['oct_review_reminder_order_status'])) {
            $this->error['order_status'] = $this->language->get('error_order_status');
        }

        return !$this->error;
    }

    public function getLogs() {
        $this->load->language('octemplates/module/oct_review_reminder');
        $this->load->model('octemplates/module/oct_review_reminder');
    
        $json = array();
        $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
        if ($page <= 0) {
            $page = 1;
        }
        $limit = 20;
        $start = ($page - 1) * $limit;

        $logs = $this->model_octemplates_module_oct_review_reminder->getLogsAjax($start, $limit);
        $total = $this->model_octemplates_module_oct_review_reminder->getTotalLogs();

        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->url = 'index.php?route=octemplates/module/oct_review_reminder/getLogs&user_token=' . $this->session->data['user_token'] . '&page={page}';

        $json['logs'] = $logs;
        $json['pagination'] = $pagination->render();
        $json['current'] = $page;
    
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }    

    private function checkModifyPermission($json_response = false) {
        if (!$this->user->hasPermission('modify', 'octemplates/module/oct_review_reminder')) {
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