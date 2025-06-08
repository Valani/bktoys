<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerAccountOctSmartRegister extends Controller {
    private $error = array();

    public function index() {
        if ($this->customer->isLogged()) {
            $this->response->redirect($this->url->link('account/account', '', true));
        }

        $this->load->model('account/customer');
        $this->load->language('octemplates/module/oct_smart_register');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->addStyle('catalog/view/theme/oct_remarket/stylesheet/smartcheckout.css');

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_account'),
            'href' => $this->url->link('account/account', '', true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_register'),
            'href' => $this->url->link('account/register', '', true)
        );

        $data['text_account_already'] = sprintf(
            $this->language->get('text_account_already'),
            $this->url->link('account/login', '', true)
        );

        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';

        $data['action'] = $this->url->link('account/oct_smart_register/validate', '', true);
        $data['account_url'] = $this->url->link('account/account', '', true);

        $data['customer_groups'] = array();
        if (is_array($this->config->get('config_customer_group_display'))) {
            $this->load->model('account/customer_group');
            $customer_groups = $this->model_account_customer_group->getCustomerGroups();
            foreach ($customer_groups as $customer_group) {
                if (in_array($customer_group['customer_group_id'], $this->config->get('config_customer_group_display'))) {
                    $data['customer_groups'][] = $customer_group;
                }
            }
        }

        $data['newsletter'] = isset($this->request->post['newsletter']) ? $this->request->post['newsletter'] : '';

        if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') &&
            in_array('register', (array)$this->config->get('config_captcha_page'))) {
            $data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'), $this->error);
        } else {
            $data['captcha'] = '';
        }

        if ($this->config->get('config_account_id')) {
            $this->load->model('catalog/information');
            $information_info = $this->model_catalog_information->getInformation($this->config->get('config_account_id'));
            if ($information_info) {
                $data['text_agree'] = sprintf(
                    $this->language->get('text_agree'),
                    $this->url->link('information/information/agree', 'information_id=' . $this->config->get('config_account_id'), true),
                    $information_info['title']
                );
            } else {
                $data['text_agree'] = '';
            }
        } else {
            $data['text_agree'] = '';
        }

        $oct_smart_register_data = $this->config->get('oct_smart_register_data');
        $data['phone_mask'] = isset($oct_smart_register_data['phone_mask']) ? $oct_smart_register_data['phone_mask'] : '';
        $data['newsletter_status'] = isset($oct_smart_register_data['newsletter']) ? $oct_smart_register_data['newsletter'] : '';

        try {
            $csrf_reg_token = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            $csrf_reg_token = md5(uniqid(rand(), true));
        }
        $this->session->data['csrf_reg_token'] = $csrf_reg_token;
        $data['csrf_reg_token'] = $csrf_reg_token;

        $data['agree'] = isset($this->request->post['agree']) ? $this->request->post['agree'] : false;

        $data['customer_fields_block'] = $this->getCustomerFields($data);

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('account/oct_smart_register', $data));
    }

    public function validate() {
        if (isset($this->request->server['HTTP_X_REQUESTED_WITH']) &&
            !empty($this->request->server['HTTP_X_REQUESTED_WITH']) &&
            strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

            $json = array();
            $data['account_url'] = $this->url->link('account/account', '', true);

            $this->language->load('octemplates/module/oct_smart_register');
            $this->load->model('account/customer');

            if (
                !isset($this->request->post['csrf_reg_token']) ||
                !isset($this->session->data['csrf_reg_token']) ||
                !hash_equals($this->session->data['csrf_reg_token'], $this->request->post['csrf_reg_token'])
            ) {
                $json['warning'] = $this->language->get('error_csrf');
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($json));
                return;
            }

            if (isset($this->request->post['email_check_confirm']) && !empty(trim($this->request->post['email_check_confirm']))) {
                $json['warning'] = $this->language->get('error_bot_detected');
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($json));
                return;
            }

            $oct_smart_register_data = $this->config->get('oct_smart_register_data');
            $data['language_id'] = (int)$this->config->get('config_language_id');

            if (!$json) {
                $this->load->language('octemplates/module/oct_smart_register');

                $data['language_id'] = (int)$this->config->get('config_language_id');
                $data['fields'] = $this->getFieldList('customer', 'default');

                $data['register_fields'] = array(
                    'password' => array(
                        'required' => 1,
                        'display' => 'all',
                        'sort_order' => 1,
                        'status' => 'on',
                        'custom_field' => 0,
                        'custom_field_id' => 0,
                        'type' => 'password',
                        'localization' => '',
                        'merge_field' => '',
                    ),
                    'email' => array(
                        'required' => 1,
                        'display' => 'all',
                        'sort_order' => 1,
                        'status' => 'on',
                        'custom_field' => 0,
                        'custom_field_id' => 0,
                        'type' => 'text',
                        'localization' => '',
                        'merge_field' => '',
                    ),
                    'telephone' => array(
                        'required' => 1,
                        'display' => 'all',
                        'sort_order' => 1,
                        'status' => 'on',
                        'custom_field' => 0,
                        'custom_field_id' => 0,
                        'type' => 'text',
                        'localization' => '',
                        'merge_field' => '',
                    )
                );

                $data['merged_fields'] = array_merge($data['fields'], $data['register_fields']);
                $json = $this->validateFields($data['merged_fields']);

                if ($this->model_account_customer->getTotalCustomersByEmail(isset($this->request->post['email']) ? $this->request->post['email'] : '')) {
                    $json['error']['email'] = $this->language->get('error_email_exists');
                }
            }

            if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') &&
                in_array('register', (array)$this->config->get('config_captcha_page'))) {
                $captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');
                if ($captcha) {
                    $json['error']['captcha'] = $captcha;
                }
            }

            if ($this->customer->isLogged()) {
                $json['error']['logged'] = $this->language->get('error_logged');
            }

            if (!$json) {
                $json = $this->checkAgree();
            }

            if (!$json) {
                $this->session->data['account'] = 'register';
                $this->request->post['lastname'] = isset($this->request->post['lastname']) ? $this->request->post['lastname'] : '';

                $customer_id = $this->model_account_customer->addCustomer($this->request->post);
                $this->session->data['customer_id'] = $customer_id;
                $this->session->data['checkout_customer_id'] = true;

                $this->load->model('account/customer_group');
                $customer_group_id = $this->config->get('config_customer_group_id');
                $customer_group = $this->model_account_customer_group->getCustomerGroup($customer_group_id);

                if ($customer_group && !$customer_group['approval']) {
                    $this->customer->login($this->request->post['email'], $this->request->post['password']);
                    $json['redirect'] = $this->url->link('account/success');
                } else {
                    $json['redirect'] = $this->url->link('account/success');
                }

                unset($this->session->data['guest'], $this->session->data['csrf_reg_token']);

                if ($this->config->get('config_customer_activity') && isset($this->session->data['checkout_customer_id'])) {
                    $this->load->model('account/activity');
                    $addressDataLastname = isset($this->request->post['lastname']) ? $this->request->post['lastname'] : '';
                    $activity_data = array(
                        'customer_id' => $this->session->data['checkout_customer_id'],
                        'name' => $this->request->post['firstname'] . ' ' . $addressDataLastname
                    );
                    $this->model_account_activity->addActivity('register', $activity_data);
                }
                $cart = new Cart\Cart($this->registry);
                $this->registry->set('cart', $cart);
            }

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
        } else {
            $this->response->redirect($this->url->link('error/not_found', '', true));
        }
    }

    private function getCustomerFields(&$data = array()) {
        $this->load->language('octemplates/module/oct_smart_register');
        $this->load->model('account/address');

        $data['language_id'] = (int)$this->config->get('config_language_id');
        $data['fields'] = $this->getFieldList('customer', 'default');

        $data['register_fields'] = array(
            'email' => array(
                'required' => 1,
                'display' => 'all',
                'sort_order' => 1,
                'status' => 'on',
                'custom_field' => 0,
                'custom_field_id' => 0,
                'type' => 'text',
                'localization' => '',
                'merge_field' => '',
            ),
            'telephone' => array(
                'required' => 1,
                'display' => 'all',
                'sort_order' => 1,
                'status' => 'on',
                'custom_field' => 0,
                'custom_field_id' => 0,
                'type' => 'text',
                'localization' => '',
                'merge_field' => '',
            )
        );

        $data['fields'] = array_merge($data['fields'], $data['register_fields']);

        return $this->load->view('account/oct_smart_register', $data);
    }

    private function getFieldList($fieldType, $FieldGroup = 'default') {
        $configData = $this->config->get('oct_smart_register_data');
        $fieldsSettings = isset($configData[$fieldType]['fields'][$FieldGroup])
            ? $configData[$fieldType]['fields'][$FieldGroup]
            : $configData[$fieldType]['fields']['default'];

        $fieldList = [];
        $isLogged = $this->customer->isLogged();

        foreach ($fieldsSettings as $fieldName => $settings) {
            if (isset($settings['status'])) {
                if ($settings['display'] == 'all' ||
                    ($isLogged && $settings['display'] == 'registered') ||
                    (!$isLogged && $settings['display'] == 'guests')) {

                    $customFieldSettings = [];
                    if (isset($settings['custom_field']) && $settings['custom_field']) {
                        $fieldList[$fieldName] = array_merge($settings, $customFieldSettings);
                    } else {
                        $fieldList[$fieldName] = $settings;
                    }
                }
            }
        }

        return $fieldList;
    }

    private function checkAgree() {
        $json = array();

        if ($this->config->get('config_checkout_id')) {
            $this->load->model('catalog/information');
            $this->load->language('octemplates/module/oct_smartcheckout');

            $information_info = $this->model_catalog_information->getInformation($this->config->get('config_checkout_id'));

            if ($information_info && !isset($this->request->post['agree'])) {
                $json['error']['warning_agree'] = sprintf($this->language->get('error_agree'), $information_info['title']);
            }
        }

        return $json;
    }

    private function validateFields($fields = array()) {
        $json = array();
        $oct_smart_register_data = $this->config->get('oct_smart_register_data');

        foreach ($fields as $fieldName => $field_settings) {
            $value = isset($this->request->post[$fieldName]) ? $this->request->post[$fieldName] : '';

            if (!isset($field_settings['status'])) {
                continue;
            }

            if ($field_settings['required'] && empty($value)) {
                $json['error'][$fieldName] = $this->getErrorMessage($field_settings, $fieldName);
            }

            switch ($fieldName) {
                case 'firstname':
                    if ($field_settings['required'] && (utf8_strlen($value) < 1 || utf8_strlen($value) > 32)) {
                        $json['error']['firstname'] = $this->getErrorMessage($field_settings, $fieldName);
                    }
                    break;
                case 'lastname':
                    if ($field_settings['required'] && (utf8_strlen($value) < 1 || utf8_strlen($value) > 32)) {
                        $json['error']['lastname'] = $this->getErrorMessage($field_settings, $fieldName);
                    }
                    break;
                case 'email':
                    if ($field_settings['required'] && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $json['error']['email'] = $this->getErrorMessage($field_settings, $fieldName);
                    }
                    break;
                case 'telephone':
                    $postedTelephone = isset($this->request->post['telephone']) ? $this->request->post['telephone'] : '';
                    $phoneMask = isset($oct_smart_register_data['phone_mask']) ? $oct_smart_register_data['phone_mask'] : '';
                    $oct_remarket_data = $this->config->get('theme_oct_remarket_data');
                    $phone_regex = isset($oct_remarket_data['phone_regex']) ? $oct_remarket_data['phone_regex'] : '';

                    $cleanPostedTelephone = utf8_strlen(str_replace(['_', '-', '(', ')', '+', ' '], "", $postedTelephone));

                    if ($field_settings['required'] && !empty($postedTelephone)) {
                        if (!empty($phoneMask)) {
                            $phoneCount = utf8_strlen(str_replace(['_', '-', '(', ')', '+', ' '], "", $phoneMask));
                            if ($cleanPostedTelephone < $phoneCount) {
                                $json['error']['telephone'] = $this->getErrorMessage($field_settings, $fieldName);
                            }
                        } else {
                            if ($cleanPostedTelephone > 15 || $cleanPostedTelephone < 3) {
                                $json['error']['telephone'] = $this->getErrorMessage($field_settings, $fieldName);
                            }
                        }

                        if (!empty($phone_regex)) {
                            if (preg_match($phone_regex, $postedTelephone) !== 1) {
                                $json['error']['telephone'] = $this->getErrorMessage($field_settings, $fieldName);
                            }
                        }
                    }
                    break;
                case 'password':
                    if ($field_settings['required'] && strlen($value) < 4) {
                        $json['error']['password'] = $this->getErrorMessage($field_settings, $fieldName);
                    }
                    break;
            }
        }

        return $json;
    }

    private function getErrorMessage($field_settings, $field) {
        $language_id = (int)$this->config->get('config_language_id');
        if (!empty($field_settings['localization'][$language_id]['error_text'])) {
            return $field_settings['localization'][$language_id]['error_text'];
        } else {
            return $this->language->get('error_' . $field);
        }
    }
}