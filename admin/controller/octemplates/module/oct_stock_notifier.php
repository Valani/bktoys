<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOctemplatesModuleOctStockNotifier extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('octemplates/module/oct_stock_notifier');

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
        $this->load->model('octemplates/module/oct_stock_notifier');

        $oct_stock_notifier_info = $this->model_setting_setting->getSetting('oct_stock_notifier');

        if (!$oct_stock_notifier_info) {
            $this->response->redirect($this->url->link('octemplates/module/oct_stock_notifier/install', 'user_token=' . $this->session->data['user_token'], true));
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('oct_stock_notifier', $this->request->post);

            if (isset($this->request->post['oct_stock_notifier_status']) && $this->request->post['oct_stock_notifier_status'] == "on") {
                $this->addEvent();
            } else {
                $this->deleteEvent();
            }

            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('octemplates/module/oct_stock_notifier', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
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

        $url = '';

        if (isset($this->request->get['filter_email'])) {
            $url .= '&filter_email=' . $this->request->get['filter_email'];
        }

        if (isset($this->request->get['filter_product'])) {
            $url .= '&filter_product=' . $this->request->get['filter_product'];
        }

        if (isset($this->request->get['filter_phone'])) {
            $url .= '&filter_phone=' . $this->request->get['filter_phone'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_email']) || isset($this->request->get['filter_product']) || isset($this->request->get['filter_phone']) || isset($this->request->get['filter_status'])) {
            $url .= '&tab=subscribers';
        }

        $filter_email = isset($this->request->get['filter_email']) ? $this->request->get['filter_email'] : null;
        $filter_product = isset($this->request->get['filter_product']) ? $this->request->get['filter_product'] : null;
        $filter_phone = isset($this->request->get['filter_phone']) ? $this->request->get['filter_phone'] : null;
        $filter_status = isset($this->request->get['filter_status']) ? $this->request->get['filter_status'] : null;

        // Breadcrumbs
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('octemplates/module/oct_stock_notifier', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['action'] = $this->url->link('octemplates/module/oct_stock_notifier', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);
        $data['user_token'] = $this->session->data['user_token'];
        $data['languages'] = $this->model_localisation_language->getLanguages();

        if (isset($this->request->post['oct_stock_notifier_status'])) {
            $data['oct_stock_notifier_status'] = $this->request->post['oct_stock_notifier_status'];
        } else {
            $data['oct_stock_notifier_status'] = $this->config->get('oct_stock_notifier_status');
        }

        if (isset($this->request->post['oct_stock_notifier_data'])) {
            $data['oct_stock_notifier_data'] = $this->request->post['oct_stock_notifier_data'];
        } else {
            $data['oct_stock_notifier_data'] = $this->config->get('oct_stock_notifier_data');
        }

        if (isset($data['oct_stock_notifier_data']['cron_secret']) && $data['oct_stock_notifier_data']['cron_secret']) {
            $site_link = $this->request->server['HTTPS'] ? HTTP_CATALOG : HTTPS_CATALOG;
            $data['cron_url'] = $site_link . "index.php?route=octemplates/module/oct_stock_notifier/cron&cron_secret=" . $data['oct_stock_notifier_data']['cron_secret'];
        }

        $filter_data = array(
            'filter_email' => $filter_email,
            'filter_product' => $filter_product,
            'filter_phone' => $filter_phone,
            'filter_status' => $filter_status
        );

        $data['filter_email'] = $filter_email;
        $data['filter_product'] = $filter_product;
        $data['filter_phone'] = $filter_phone;
        $data['filter_status'] = $filter_status;

        $total_subscribers = $this->model_octemplates_module_oct_stock_notifier->getTotalSubscribers($filter_data);

        $start = isset($this->request->get['page']) ? ($this->request->get['page'] - 1) * 20 : 0;
        $filter_data['start'] = $start;
        $filter_data['limit'] = 20;

        $subscribers = $this->model_octemplates_module_oct_stock_notifier->getSubscribers($filter_data);

        foreach ($subscribers as &$subscriber) {
            $product_id = $subscriber['product_id'];
            $customer_id = $subscriber['customer_id'];
            $user_link = false;

            $product_link = $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . (int)$product_id, true);

            if(!empty($customer_id)) {
                $user_link = $this->url->link('customer/customer/edit', 'user_token=' . $this->session->data['user_token'] . '&customer_id=' . (int)$customer_id, true);
            }

            $subscriber['product_link'] = $product_link;
            $subscriber['user_link'] = $user_link;
        }

        $data['subscribers'] = $subscribers;

        $current_page = (isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1);
        $start_item = (($current_page - 1) * 20) + 1;
        $end_item = ($current_page * 20 < $total_subscribers) ? $current_page * 20 : $total_subscribers;
        $total_pages = ceil($total_subscribers / 20);

        $data['results'] = sprintf($this->language->get('text_pagination'), $start_item, $end_item, $total_subscribers, $total_pages);

        $pagination = new Pagination();
        $pagination->total = $total_subscribers;
        $pagination->page = $start / 20 + 1;
        $pagination->limit = 20;
        $pagination->url = $this->url->link('octemplates/module/oct_stock_notifier', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}&tab=subscribers', true);
        $data['pagination'] = $pagination->render();

        $data['base'] = $this->url->link('octemplates/module/oct_stock_notifier', 'user_token=' . $this->session->data['user_token'] . '&tab=subscribers', true);
        $data['error_message'] = array();

        if (!empty($this->error['oct_stock_notifier_data_subject'])) {
			$data['error_message']['subject'] = $this->error['oct_stock_notifier_data_subject'];
		} 
        
        if (!empty($this->error['oct_stock_notifier_data_message'])) {
            $data['error_message']['message'] = $this->error['oct_stock_notifier_data_message'];
        }

        if (!empty($this->error['email'])) {
            $data['error_message']['email'] = $this->error['email'];
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('octemplates/module/oct_stock_notifier', $data));
    }

    public function deleteSelected() {
        $this->load->language('octemplates/module/oct_stock_notifier');

        if (!$this->checkModifyPermission(true)) {
            return;
        }

        $json = [];

        $this->load->model('octemplates/module/oct_stock_notifier');

        $info = $this->model_octemplates_module_oct_stock_notifier->checkSubscriber((int) $this->request->get['delete']);

        if ($info) {
            $this->model_octemplates_module_oct_stock_notifier->deleteSubscriber((int) $this->request->get['delete']);
            $this->session->data['success'] = $this->language->get('text_success_deleted');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function notify(&$route, &$data, &$output) {

        $oct_stock_notifier_data = $this->config->get('oct_stock_notifier_data');

        if ($this->shouldNotify($oct_stock_notifier_data)) {            
            $this->load->model('octemplates/module/oct_stock_notifier');

            $product_id = $data[0];
            $subscribers = $this->model_octemplates_module_oct_stock_notifier->getSubscribersByProductId((int) $product_id);

            if ($subscribers && $data[1]['quantity'] > 0) {
                $cron_url = $this->getCronUrl($oct_stock_notifier_data['cron_secret']);
                $this->triggerCron($cron_url);
            }
        }
    }

    private function shouldNotify($oct_stock_notifier_data) {
        return isset($oct_stock_notifier_data['notify_on_edit']) && $oct_stock_notifier_data['notify_on_edit'] &&
               isset($oct_stock_notifier_data['cron_secret']) && $oct_stock_notifier_data['cron_secret'];
    }
    
    private function getCronUrl($cron_secret) {
        $site_link = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;
        return $site_link . "index.php?route=octemplates/module/oct_stock_notifier/cron&cron_secret=" . $cron_secret;
    }

    private function triggerCron($cron_url) {
        $opts = [
            'http' => [
                'timeout' => 10
            ]
        ];
    
        $context = stream_context_create($opts);
        @file_get_contents($cron_url, false, $context);
    }

    public function deleteAllSelected() {
        $this->load->language('octemplates/module/oct_stock_notifier');

        if (!$this->checkModifyPermission(true)) {
            return;
        }

        $json = [];

        $this->load->model('octemplates/module/oct_stock_notifier');

        if (isset($this->request->request['selected'])) {
            foreach ($this->request->request['selected'] as $subscription_id) {
                $info = $this->model_octemplates_module_oct_stock_notifier->checkSubscriber((int) $subscription_id);

                if ($info) {
                    $this->model_octemplates_module_oct_stock_notifier->deleteSubscriber((int) $subscription_id);
                    $this->session->data['success'] = $this->language->get('text_success_deleted');
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function addEvent() {

        if (!$this->checkModifyPermission()) {
            return;
        }

        $this->load->model('setting/event');

        if ($this->model_setting_event->getEventByCode('octemplates-remarket-stock-notifier')) {
            return;
        }

        $this->deleteEvent();

        $events = [
            [
                'code' => 'octemplates-remarket-stock-notifier',
                'trigger' => 'admin/model/catalog/product/editProduct/after',
                'action' => 'octemplates/module/oct_stock_notifier/notify'
            ]
        ];

        foreach ($events as $event) {
            if (!$this->model_setting_event->getEventByCode($event['code'])) {
                $this->model_setting_event->addEvent($event['code'], $event['trigger'], $event['action'], 1, 550);
            }
        }
	}

	public function deleteEvent() {

        if (!$this->checkModifyPermission()) {
            return;
        }

        $this->load->model('setting/event');

        $eventCodes = [
            'octemplates-remarket-stock-notifier'
        ];

        foreach ($eventCodes as $code) {
            if ($this->model_setting_event->getEventByCode($code)) {
                $this->model_setting_event->deleteEventByCode($code);
            }
        }
	}

    public function install() {
        $this->load->language('octemplates/module/oct_stock_notifier');

        if (!$this->checkModifyPermission()) {
            return;
        }

        $this->load->model('setting/setting');
        $this->load->model('user/user_group');
        $this->load->model('octemplates/module/oct_stock_notifier');
        $this->model_octemplates_module_oct_stock_notifier->install();

        $this->addEvent();

        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'octemplates/module/oct_stock_notifier');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'octemplates/module/oct_stock_notifier');

        $this->model_setting_setting->editSetting('oct_stock_notifier', [
            'oct_stock_notifier_status' => '1',
            'oct_stock_notifier_data' => [
                'email' => $this->config->get('config_email'),
                'admin_alert' => 1,
                'cron_secret' => '',
                'name' => '2',
                'phone' => '2',
                'notify_on_edit' => 1,
                'custom_message' => 0,
                'mask' => '+38 (999) 999-99-99'
            ]
        ]);

        $this->session->data['success'] = $this->language->get('text_success_install');

        $this->response->redirect($this->url->link('octemplates/module/oct_stock_notifier', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function uninstall() {
        $this->load->language('octemplates/module/oct_stock_notifier');

        if (!$this->checkModifyPermission()) {
            return;
        }

        $this->load->model('setting/setting');
        $this->load->model('user/user_group');
        $this->load->model('octemplates/module/oct_stock_notifier');

        $this->model_octemplates_module_oct_stock_notifier->uninstall();

        $this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'octemplates/module/oct_stock_notifier');
        $this->model_user_user_group->removePermission($this->user->getGroupId(), 'modify', 'octemplates/module/oct_stock_notifier');

        $this->model_setting_setting->deleteSetting('oct_stock_notifier');

        $this->deleteEvent();

        $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
    }    

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'octemplates/module/oct_stock_notifier')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        foreach ($this->request->post['oct_stock_notifier_data'] as $key => $field) {
            if ($key === "email" && empty($field)) {
                $this->error['warning'] = $this->language->get('error_email');
                $this->error['email'] = $this->language->get('error_email');
            }
        }

        if (isset($this->request->post['oct_stock_notifier_data']['custom_message'])) {
            foreach ($this->request->post['oct_stock_notifier_data']['subject'] as $language_id=>$value) {
                if (!$value) {
                    $this->error['oct_stock_notifier_data_subject'][$language_id] = $this->language->get('error_subject_empty');
                    $this->error['warning'] = $this->language->get('text_warning');
                }
            }        
            
            foreach ($this->request->post['oct_stock_notifier_data']['message'] as $language_id=>$value) {
                if (!$value || strlen($value) < 30) {
                    $this->error['oct_stock_notifier_data_message'][$language_id] = $this->language->get('error_message_empty');
                    $this->error['warning'] = $this->language->get('text_warning');
                }
            }
        }

        return !$this->error;
    }
    
    private function checkModifyPermission($json_response = false) {
        if (!$this->user->hasPermission('modify', 'octemplates/module/oct_stock_notifier')) {
            $error_message = $this->language->get('error_permission');
    
            if ($json_response) {
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode(['error' => $error_message]));
            } else {
                $this->session->data['error_warning'] = $error_message;
                $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
            }
            return false;
        }
        return true;
    }
}