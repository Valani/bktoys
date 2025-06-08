<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOctemplatesModuleOctSmsNotify extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('octemplates/module/oct_sms_notify');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        $this->document->addScript('view/javascript/octemplates/bootstrap-notify/bootstrap-notify.min.js');
        $this->document->addScript('view/javascript/octemplates/oct_main.js');
        $this->document->addStyle('view/stylesheet/oct_remarket.css');

        $oct_sms_notify_info = $this->model_setting_setting->getSetting('oct_sms_settings');

        if (!$oct_sms_notify_info) {
            $this->response->redirect($this->url->link('octemplates/module/oct_sms_notify/install', 'user_token=' . $this->session->data['user_token'], true));
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('oct_sms_settings', ['oct_sms_settings' => $this->request->post['oct_sms_settings']]);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('octemplates/module/oct_sms_notify', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['error_status'] = isset($this->error['status']) ? $this->error['status'] : '';
        $data['error_provider'] = isset($this->error['provider']) ? $this->error['provider'] : '';
        $data['error_turbosms_token'] = isset($this->error['turbosms_token']) ? $this->error['turbosms_token'] : '';
        $data['error_alphasms_api_key'] = isset($this->error['alphasms_api_key']) ? $this->error['alphasms_api_key'] : '';
        $data['error_sender_name'] = isset($this->error['sender_name']) ? $this->error['sender_name'] : '';
        $data['error_admin_phone'] = isset($this->error['admin_phone']) ? $this->error['admin_phone'] : '';
        $data['error_turbosms_viber_sender'] = isset($this->error['turbosms_viber_sender']) ? $this->error['turbosms_viber_sender'] : '';
        $data['error_alphasms_viber_sender'] = isset($this->error['alphasms_viber_sender']) ? $this->error['alphasms_viber_sender'] : '';

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

        // Breadcrumbs
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
            'href' => $this->url->link('octemplates/module/oct_sms_notify', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('octemplates/module/oct_sms_notify', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        if (isset($this->request->post['oct_sms_settings'])) {
            $data['oct_sms_settings'] = $this->request->post['oct_sms_settings'];
        } else {
            $data['oct_sms_settings'] = $this->config->get('oct_sms_settings');
        }

        if (!isset($data['oct_sms_settings']['status'])) {
            $data['oct_sms_settings']['status'] = 0;
        }

        if (!isset($data['oct_sms_settings']['translit'])) {
            $data['oct_sms_settings']['translit'] = 0;
        }

        if (!isset($data['oct_sms_settings']['provider'])) {
            $data['oct_sms_settings']['provider'] = '';
        }

        if (!isset($data['oct_sms_settings']['sender_name'])) {
            $data['oct_sms_settings']['sender_name'] = '';
        }

        if (!isset($data['oct_sms_settings']['admin_phone'])) {
            $data['oct_sms_settings']['admin_phone'] = '';
        }

        if (!isset($data['oct_sms_settings']['oct_sms_token'])) {
            $data['oct_sms_settings']['oct_sms_token'] = md5(uniqid(rand(), true));
        }

        if (!isset($data['oct_sms_settings']['turbosms'])) {
            $data['oct_sms_settings']['turbosms'] = array(
                'token' => '',
                'hybrid_sending' => 0,
                'viber_sender' => ''
            );
        }

        if (!isset($data['oct_sms_settings']['alphasms'])) {
            $data['oct_sms_settings']['alphasms'] = array(
                'api_key' => '',
                'hybrid_sending' => 0,
                'viber_sender' => ''
            );
        }

        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();

        $data['heading_title'] = $this->language->get('heading_title');

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $templates = [
            [
                'code' => 'oct_abandoned_cart',
                'name' => $this->language->get('text_template_abandoned_cart'),
                'description' => $this->language->get('text_template_abandoned_cart_desc'),
                'shortcodes' => $this->language->get('text_template_abandoned_cart_shortcodes')
            ],
            [
                'code' => 'oct_review_reminder',
                'name' => $this->language->get('text_template_review_reminder'),
                'description' => $this->language->get('text_template_review_reminder_desc'),
                'shortcodes' => $this->language->get('text_template_review_reminder_shortcodes')
            ],
            [
                'code' => 'stock_notifier',
                'name' => $this->language->get('text_stock_notifier'),
                'description' => '',
                'shortcodes' => '',
                'templates' => [
                    [
                        'code' => 'oct_stock_notifier',
                        'name' => $this->language->get('text_template_stock_notifier'),
                        'description' => $this->language->get('text_template_stock_notifier_desc'),
                        'shortcodes' => $this->language->get('text_template_stock_notifier_shortcodes')
                    ],
                    [
                        'code' => 'oct_stock_notifier_admin',
                        'name' => $this->language->get('text_template_stock_notifier_admin'),
                        'description' => $this->language->get('text_template_stock_notifier_admin_desc'),
                        'shortcodes' => $this->language->get('text_template_stock_notifier_admin_shortcodes')
                    ]
                ]
            ],
            [
                'code' => 'oct_order',
                'name' => $this->language->get('text_template_order'),
                'description' => '',
                'shortcodes' => '',
                'templates' => [
                    [
                        'code' => 'oct_order_admin',
                        'name' => $this->language->get('text_template_order_admin'),
                        'description' => $this->language->get('text_template_order_admin_desc'),
                        'shortcodes' => $this->language->get('text_template_order_admin_shortcodes')
                    ],
                    [
                        'code' => 'oct_order_customer',
                        'name' => $this->language->get('text_template_order_customer'),
                        'description' => $this->language->get('text_template_order_customer_desc'),
                        'shortcodes' => $this->language->get('text_template_order_customer_shortcodes')
                    ]
                ]
            ],
            [
                'code' => 'oct_product_faq',
                'name' => $this->language->get('text_template_product_faq'),
                'description' => $this->language->get('text_template_product_faq_desc'),
                'shortcodes' => $this->language->get('text_template_product_faq_shortcodes')
            ],
            [
                'code' => 'oct_popup_call_phone',
                'name' => $this->language->get('text_template_popup_call_phone'),
                'description' => $this->language->get('text_template_popup_call_phone_desc'),
                'shortcodes' => $this->language->get('text_template_popup_call_phone_shortcodes')
            ],
            [
                'code' => 'oct_popup_found_cheaper',
                'name' => $this->language->get('text_template_popup_found_cheaper'),
                'description' => $this->language->get('text_template_popup_found_cheaper_desc'),
                'shortcodes' => $this->language->get('text_template_popup_found_cheaper_shortcodes')
            ],
            [
                'code' => 'oct_blog_comment',
                'name' => $this->language->get('text_template_blog_comment'),
                'description' => $this->language->get('text_template_blog_comment_desc'),
                'shortcodes' => $this->language->get('text_template_blog_comment_shortcodes')
            ],
            [
                'code' => 'oct_sreview_reviews',
                'name' => $this->language->get('text_template_sreview_reviews'),
                'description' => $this->language->get('text_template_sreview_reviews_desc'),
                'shortcodes' => $this->language->get('text_template_sreview_reviews_shortcodes')
            ],
            [
                'code' => 'oct_otp_login',
                'name' => $this->language->get('text_template_otp_login'),
                'description' => $this->language->get('text_template_otp_login_desc'),
                'shortcodes' => $this->language->get('text_template_otp_login_shortcodes')
            ]
        ];
        
        usort($templates, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        $data['templates'] = $templates;

        $this->response->setOutput($this->load->view('octemplates/module/oct_sms_notify', $data));
    }

    public function getLogs() {
        if (!$this->user->hasPermission('access', 'octemplates/module/oct_sms_notify')) {
            $this->response->setOutput(json_encode(array('error' => $this->language->get('error_permission'))));
            return;
        }

        $this->load->model('octemplates/module/oct_sms_notify');

        $this->load->language('octemplates/module/oct_sms_notify');

        if (isset($this->request->get['page'])) {
            $page = (int)$this->request->get['page'];
        } else {
            $page = 1;
        }

        $json = array();

        $admin_limit = (int) $this->config->get('config_limit_admin');

        $filter_data = array(
            'start' => ($page - 1) * $admin_limit,
            'limit' => $admin_limit
        );


        $sms_total = $this->model_octemplates_module_oct_sms_notify->getTotalSmsLogs();

        $results = $this->model_octemplates_module_oct_sms_notify->getSmsLogs($filter_data);

        $json['logs'] = array();

        foreach ($results as $result) {
            $json['logs'][] = array(
                'phone'         => $result['phone'],
                'message'       => $result['message'],
                'provider'      => $result['provider'],
                'date_added'    => $result['date_added'],
                'template_code' => $result['template_code'],
                'response'      => $result['response']
            );
        }

        // Pagination
        $pagination = new Pagination();
        $pagination->total = $sms_total;
        $pagination->page = $page;
        $pagination->limit = $admin_limit;
        $pagination->url = $this->url->link('octemplates/module/oct_sms_notify/getLogs', 'user_token=' . $this->session->data['user_token'] . '&page={page}', true);

        $json['pagination'] = $pagination->render();

        $json['results'] = "";

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function install() {
        $this->load->language('octemplates/module/oct_sms_notify');

        if (!$this->checkModifyPermission()) {
            return;
        }

        $this->load->model('setting/setting');
        $this->load->model('octemplates/module/oct_sms_notify');

        $this->model_octemplates_module_oct_sms_notify->install();

        $default_settings = array(
            'oct_sms_settings' => array(
                'status' => 0,
                'translit' => 0,
                'logging' => 1,
                'provider' => '',
                'sender_name' => '',
                'oct_sms_token' => md5(uniqid(rand(), true)),
                'admin_phone' => '',
                'turbosms' => array(
                    'token' => '',
                    'hybrid_sending' => 0,
                    'viber_sender' => ''
                ),
                'alphasms' => array(
                    'api_key' => '',
                    'hybrid_sending' => 0,
                    'viber_sender' => ''
                ),
                'templates' => array()
            )
        );

        $templates = array(
            'oct_abandoned_cart' => array(
                'status' => 0,
                'code' => 'oct_abandoned_cart',
                'message' => array()
            ),
            'oct_review_reminder' => array(
                'status' => 0,
                'code' => 'oct_review_reminder',
                'message' => array()
            ),
            'oct_stock_notifier' => array(
                'status' => 0,
                'code' => 'oct_stock_notifier',
                'message' => array()
            ),
            'oct_stock_notifier_admin' => array(
                'status' => 0,
                'code' => 'oct_stock_notifier_admin',
                'message' => array()
            ),
            'oct_order_admin' => array(
                'status' => 0,
                'code' => 'oct_order_admin',
                'message' => array()
            ),
            'oct_order_customer' => array(
                'status' => 0,
                'code' => 'oct_order_customer',
                'message' => array()
            ),
            'oct_product_faq' => array(
                'status' => 0,
                'code' => 'oct_product_faq',
                'message' => array()
            ),
            'oct_popup_call_phone' => array(
                'status' => 0,
                'code' => 'oct_popup_call_phone',
                'message' => array()
            ),
            'oct_popup_found_cheaper' => array(
                'status' => 0,
                'code' => 'oct_popup_found_cheaper',
                'message' => array()
            ),
            'oct_blog_comment' => array(
                'status' => 0,
                'code' => 'oct_blog_comment',
                'message' => array()
            ),
            'oct_sreview_reviews' => array(
                'status' => 0,
                'code' => 'oct_sreview_reviews',
                'message' => array()
            ), 
            'oct_otp_login' => array(
                'status' => 0,
                'code' => 'oct_otp_login',
                'message' => array()
            )
        );

        $this->load->model('localisation/language');
        $languages = $this->model_localisation_language->getLanguages();

        foreach ($templates as &$template) {
            foreach ($languages as $language) {
                $template['message'][$language['language_id']] = '';
            }
        }

        $default_settings['oct_sms_settings']['templates'] = $templates;

        $this->model_setting_setting->editSetting('oct_sms_settings', $default_settings);
    
        $this->session->data['success'] = $this->language->get('text_install_success');

        $this->response->redirect($this->url->link('octemplates/module/oct_sms_notify', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function uninstall() {
        $this->load->language('octemplates/module/oct_sms_notify');

        if (!$this->checkModifyPermission()) {
            return;
        }
        
        $this->load->model('octemplates/module/oct_sms_notify');

        $this->model_octemplates_module_oct_sms_notify->uninstall();

        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('oct_sms_settings');

        $this->session->data['success'] = $this->language->get('text_uninstall_success');

        $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'octemplates/module/oct_sms_notify')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['oct_sms_settings']['provider'])) {
            $this->error['provider'] = $this->language->get('error_provider');
        } else {
            $provider = $this->request->post['oct_sms_settings']['provider'];

            if ($provider == 'turbosms') {
                if (empty($this->request->post['oct_sms_settings']['turbosms']['token']) || strlen(trim($this->request->post['oct_sms_settings']['turbosms']['token'])) < 10) {
                    $this->error['turbosms_token'] = $this->language->get('error_turbosms_token');
                }

                if (isset($this->request->post['oct_sms_settings']['turbosms']['hybrid_sending']) && $this->request->post['oct_sms_settings']['turbosms']['hybrid_sending']) {
                    if (empty($this->request->post['oct_sms_settings']['turbosms']['viber_sender']) || strlen(trim($this->request->post['oct_sms_settings']['turbosms']['viber_sender'])) < 3 || strlen(trim($this->request->post['oct_sms_settings']['turbosms']['viber_sender'])) > 25) {
                        $this->error['turbosms_viber_sender'] = $this->language->get('error_turbosms_viber_sender');
                    }
                }
            }

            if ($provider == 'alphasms') {
                if (empty($this->request->post['oct_sms_settings']['alphasms']['api_key']) || strlen(trim($this->request->post['oct_sms_settings']['alphasms']['api_key'])) < 5) {
                    $this->error['alphasms_api_key'] = $this->language->get('error_alphasms_api_key');
                }

                if (isset($this->request->post['oct_sms_settings']['alphasms']['hybrid_sending']) && $this->request->post['oct_sms_settings']['alphasms']['hybrid_sending']) {
                    if (empty($this->request->post['oct_sms_settings']['alphasms']['viber_sender']) || strlen(trim($this->request->post['oct_sms_settings']['alphasms']['viber_sender'])) < 3 || strlen(trim($this->request->post['oct_sms_settings']['alphasms']['viber_sender'])) > 25) {
                        $this->error['alphasms_viber_sender'] = $this->language->get('error_alphasms_viber_sender');
                    }
                }
            }
        }

        if (empty($this->request->post['oct_sms_settings']['sender_name']) || strlen(trim($this->request->post['oct_sms_settings']['sender_name'])) < 3 || strlen(trim($this->request->post['oct_sms_settings']['sender_name'])) > 11) {
            $this->error['sender_name'] = $this->language->get('error_sender_name');
        }

        if (empty($this->request->post['oct_sms_settings']['admin_phone']) || strlen(trim($this->request->post['oct_sms_settings']['admin_phone'])) < 9) {
            $this->error['admin_phone'] = $this->language->get('error_admin_phone');
        }

        return !$this->error;
    }    
    
    public function deleteLogs() {
        $this->load->language('octemplates/module/oct_sms_notify');
    
        if (!$this->checkModifyPermission(true)) {
            return;
        }
    
        $this->load->model('octemplates/module/oct_sms_notify');
    
        $this->model_octemplates_module_oct_sms_notify->deleteLogs();
    
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => $this->language->get('text_success_logs_deleted')]));
    }
    
    private function checkModifyPermission($json_response = false) {
        if (!$this->user->hasPermission('modify', 'octemplates/module/oct_sms_notify')) {
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
