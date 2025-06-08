<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesModuleOctSmartRegister extends Controller {
    private $error = [];

    public function index() {
        $this->load->language('octemplates/module/oct_smart_register');


        $this->document->addScript('view/javascript/octemplates/bootstrap-notify/bootstrap-notify.min.js');
        $this->document->addScript('view/javascript/octemplates/oct_main.js');
        $this->document->addStyle('view/stylesheet/oct_remarket.css');

        // Sortable
        $this->document->addScript('view/javascript/octemplates/sortable/sortable.js');
        $this->document->addScript('view/javascript/octemplates/sortable/sortable-jquery.js');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');
        $this->load->model('setting/extension');
        $this->load->model('localisation/language');

        $oct_smart_register_data = $this->model_setting_setting->getSetting('oct_smart_register_data');

        if (!$oct_smart_register_data) {
            $this->response->redirect($this->url->link('octemplates/module/oct_smart_register/install', 'user_token=' . $this->session->data['user_token'], true));
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('oct_smart_register_data', $this->request->post);

            if (isset($this->request->post['oct_smart_register_data']['status']) && $this->request->post['oct_smart_register_data']['status'] == "on") {
                $this->addEvent();
            } else {
                $this->deleteEvent();
            }

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('octemplates/module/oct_smart_register', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }


        $data['languages'] = $this->model_localisation_language->getLanguages();
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } elseif (isset($this->session->data['error_warning'])) {
            $data['error_warning'] = $this->session->data['error_warning'];

            unset($this->session->data['error_warning']);
            
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('octemplates/module/oct_smart_register', 'user_token=' . $this->session->data['user_token'], true),
        ];

        $data['action'] = $this->url->link('octemplates/module/oct_smart_register', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);

        if (isset($this->request->post['oct_smart_register_data'])) {
            $data['oct_smart_register_data'] = $this->request->post['oct_smart_register_data'];
        } else {
            $data['oct_smart_register_data'] = $this->config->get('oct_smart_register_data');
        }

        if (defined('HTTP_CATALOG')) {
            $data['store_url'] = HTTP_CATALOG;
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('octemplates/module/oct_smart_register', $data));
    }

    public function install() {
        $this->load->language('octemplates/module/oct_smart_register');

        if (!$this->validate()) {
            $this->session->data['error_warning'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        $this->load->model('setting/setting');
        $this->load->model('user/user_group');

        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'octemplates/module/oct_smart_register');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'octemplates/module/oct_smart_register');

        $this->addEvent();

        $this->model_setting_setting->editSetting('oct_smart_register_data', [
            'oct_smart_register_data' => [
                'status' => 'on',
                'phone_mask' => '+38 (999) 999-99-99',
                'newsletter' => 'on',
                'customer' => [
                    'fields' => [
                        'default' => [
                            'firstname' => [
                                'status' => 'on',
                                'sort_order' => 0,
                                'required' => 1,
                                'display' => 'all',
                                'local_edit' => 0,
                                'type' => 'text',
                                'merge' => 0,
                                'merge_field' => '',
                                'custom_field' => 0,
                                'custom_field_id' => '',
                                'localization' => []
                            ],
                            'lastname' => [
                                'status' => 'on',
                                'sort_order' => 0,
                                'required' => 1,
                                'display' => 'all',
                                'local_edit' => 0,
                                'type' => 'text',
                                'merge' => 0,
                                'merge_field' => '',
                                'custom_field' => 0,
                                'custom_field_id' => '',
                                'localization' => []
                            ],
                            'telephone' => [
                                'status' => 'on',
                                'sort_order' => 0,
                                'required' => 1,
                                'display' => 'all',
                                'local_edit' => 0,
                                'type' => 'tel',
                                'merge' => 0,
                                'merge_field' => '',
                                'custom_field' => 0,
                                'custom_field_id' => '',
                                'localization' => []
                            ],
                            'email' => [
                                'status' => 'on',
                                'sort_order' => 0,
                                'required' => 1,
                                'display' => 'all',
                                'local_edit' => 0,
                                'type' => 'text',
                                'merge' => 0,
                                'merge_field' => '',
                                'custom_field' => 0,
                                'custom_field_id' => '',
                                'localization' => []
                            ]
                        ],
                    ],
                ]
            ],
        ]);

        $this->session->data['success'] = $this->language->get('text_success_install');

        $this->response->redirect($this->url->link('octemplates/module/oct_smart_register', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function uninstall($redirect = true) {
        $this->load->language('octemplates/module/oct_smart_register');

        if (!$this->validate()) {
            $this->session->data['error_warning'] = $this->language->get('error_permission');
            if ($redirect) {
                $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
            }
            return;
        }
    
        $this->load->model('setting/setting');
        $this->load->model('user/user_group');
    
        $this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'octemplates/module/oct_smart_register');
        $this->model_user_user_group->removePermission($this->user->getGroupId(), 'modify', 'octemplates/module/oct_smart_register');
    
        $this->deleteEvent();
    
        $this->model_setting_setting->deleteSetting('oct_smart_register_data');
    
        $this->session->data['success'] = $this->language->get('text_success_uninstall');

        if ($redirect) {
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    public function addEvent() {

        $this->load->model('setting/event');

        if ($this->model_setting_event->getEventByCode('octemplates-remarket-smart-register')) {
            return;
        }

        $this->deleteEvent();

        $events = [
            [
                'code' => 'octemplates-remarket-smart-register',
                'trigger' => 'catalog/controller/account/register/before',
                'action' => 'octemplates/events/smartregister',
            ],
        ];

        foreach ($events as $event) {
            if (!$this->model_setting_event->getEventByCode($event['code'])) {
                $this->model_setting_event->addEvent($event['code'], $event['trigger'], $event['action'], 1, 550);
            }
        }
    }

    public function deleteEvent() {

        $this->load->model('setting/event');

        $eventCodes = [
            'octemplates-remarket-smart-register',
        ];

        foreach ($eventCodes as $code) {
            if ($this->model_setting_event->getEventByCode($code)) {
                $this->model_setting_event->deleteEventByCode($code);
            }
        }
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'octemplates/module/oct_smart_register')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}