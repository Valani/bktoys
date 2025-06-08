<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesModuleOctSmartCheckout extends Controller
{
    private $error = [];

    public function index() {
        $this->load->language('octemplates/module/oct_smart_checkout');

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

        // Sortable
        $this->document->addScript('view/javascript/octemplates/sortable/sortable.js');
        $this->document->addScript('view/javascript/octemplates/sortable/sortable-jquery.js');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');
        $this->load->model('localisation/country');
        $this->load->model('catalog/product');
        $this->load->model('setting/extension');
        $this->load->model('tool/image');
        $this->checkOldCheckout($data);

        $oct_smart_checkout_data = $this->model_setting_setting->getSetting('oct_smart_checkout_data');

        if (!$oct_smart_checkout_data) {
            $this->response->redirect($this->url->link('octemplates/module/oct_smart_checkout/install', 'user_token=' . $this->session->data['user_token'], true));
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('oct_smart_checkout_data', $this->request->post);

            if (isset($this->request->post['oct_smart_checkout_data']['status']) && $this->request->post['oct_smart_checkout_data']['status'] == "on") {
                $this->addEvent();
            } else {
                $this->deleteEvent();
            }

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('octemplates/module/oct_smart_checkout', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }

        $data['button_custom_field_value_add'] = $this->language->get('button_custom_field_value_add');

        $this->load->model('localisation/language');

        $data['languages'] = $this->model_localisation_language->getLanguages();

        $this->load->model('customer/customer_group');

        $data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->error['notify_email'])) {
            $data['error_notify_email'] = $this->error['notify_email'];
        } else {
            $data['error_notify_email'] = '';
        }

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

        if (isset($this->error['default_country'])) {
            $data['error_default_country'] = $this->error['default_country'];
        } else {
            $data['error_default_country'] = '';
        }

        if (isset($this->error['default_region'])) {
            $data['error_default_region'] = $this->error['default_region'];
        } else {
            $data['error_default_region'] = '';
        }

        if (isset($this->error['default_city'])) {
            $data['error_default_city'] = $this->error['default_city'];
        } else {
            $data['error_default_city'] = '';
        }

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('octemplates/module/oct_smart_checkout', 'user_token=' . $this->session->data['user_token'], true),
        ];

        $data['action'] = $this->url->link('octemplates/module/oct_smart_checkout', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('octemplates/module/oct_smart_checkout', 'user_token=' . $this->session->data['user_token'], true);

        $data['custom_fields_link'] = $this->url->link('customer/custom_field', 'user_token=' . $this->session->data['user_token'], true);

        if (isset($this->request->post['oct_smart_checkout_data'])) {
            $data['oct_smart_checkout_data'] = $this->request->post['oct_smart_checkout_data'];
        } else {
            $data['oct_smart_checkout_data'] = $this->config->get('oct_smart_checkout_data');
        }

        $data['sorting_blocks'] = isset($data['oct_smart_checkout_data']['sorting_blocks']) ? $data['oct_smart_checkout_data']['sorting_blocks'] : [];

        $products = array();

        if (isset($this->request->post['oct_smart_checkout_data']['recommended_poducts']['products'])) {
            $products = isset($this->request->post['recommended_poducts']['products']) ? $this->request->post['recommended_poducts']['products'] : array();
		}  else {
			$products = isset($data['oct_smart_checkout_data']['recommended_poducts']['products']) ? $data['oct_smart_checkout_data']['recommended_poducts']['products'] : array();
		}

		$data['product_relateds'] = array();

        if (is_array($products)) {
            foreach ($products as $product_id) {
                $related_info = $this->model_catalog_product->getProduct($product_id);
    
                if ($related_info) {
                    $data['product_relateds'][] = array(
                        'product_id' => $related_info['product_id'],
                        'name'       => $related_info['name']
                    );
                }
            }
        }

        if (!empty($data['oct_smart_checkout_data']['delivery']['methods'])) {
            foreach ($data['oct_smart_checkout_data']['delivery']['methods'] as $method => $details) {
                if (!empty($details['image'])) {
                    $data['oct_smart_checkout_data']['delivery']['methods'][$method]['image_cached'] = $this->model_tool_image->resize($details['image'], 60, 60);
                }
            }
        }

        if (!empty($data['oct_smart_checkout_data']['payment']['methods'])) {
            foreach ($data['oct_smart_checkout_data']['payment']['methods'] as $method => $details) {
                if (!empty($details['image'])) {
                    $data['oct_smart_checkout_data']['payment']['methods'][$method]['image_cached'] = $this->model_tool_image->resize($details['image'], 60, 60);
                }
            }
        }

        $data['thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);

        $shipping_extensions = $this->model_setting_extension->getInstalled('shipping');

        $data['shipping_methods'] = array();
            foreach ($shipping_extensions as $code) {
                $this->load->language('extension/shipping/' . $code, 'extension');
                
                $data['shipping_methods'][$code] = array(
                    'code' => $code,
                    'name' => $this->language->get('extension')->get('heading_title')
                );
        }

        $payment_methods = $this->model_setting_extension->getInstalled('payment');

        $data['payment_methods'] = array();

        foreach ($payment_methods as $code) {
            $this->load->language('extension/payment/' . $code, 'extension');
            
            $data['payment_methods'][$code] = array(
                'code' => $code,
                'name' => $this->language->get('extension')->get('heading_title')
            );
        }

        $data['countries'] = [];

        $countries = $this->model_localisation_country->getCountries();

        foreach ($countries as $country) {
            $data['countries'][] = [
                'country_id' => $country['country_id'],
                'name' => $country['name'] . (($country['country_id'] == $this->config->get('config_country_id')) ? $this->language->get('text_default') : null),
            ];
        }

        $this->load->model('customer/custom_field');
        $data['custom_fields'] = [];

        $filter_data = [
            'start' => 0,
            'limit' => 1000,
        ];

        $custom_field_total = $this->model_customer_custom_field->getTotalCustomFields();

        $results = $this->model_customer_custom_field->getCustomFields($filter_data);

        foreach ($results as $result) {
            $type = '';

            switch ($result['type']) {
                case 'select':
                    $type = $this->language->get('text_select');
                    break;
                case 'radio':
                    $type = $this->language->get('text_radio');
                    break;
                case 'checkbox':
                    $type = $this->language->get('text_checkbox');
                    break;
                case 'input':
                    $type = $this->language->get('text_input');
                    break;
                case 'text':
                    $type = $this->language->get('text_text');
                    break;
                case 'textarea':
                    $type = $this->language->get('text_textarea');
                    break;
                case 'file':
                    $type = $this->language->get('text_file');
                    break;
                case 'date':
                    $type = $this->language->get('text_date');
                    break;
                case 'datetime':
                    $type = $this->language->get('text_datetime');
                    break;
                case 'time':
                    $type = $this->language->get('text_time');
                    break;
            }

            $data['custom_fields'][$result['custom_field_id']] = [
                'custom_field_id' => $result['custom_field_id'],
                'name' => $result['name'],
                'location' => $this->language->get('text_' . $result['location']),
                'type' => $type,
                'status' => $result['status'],
                'sort_order' => $result['sort_order'],
            ];
        }

        if (defined('HTTP_CATALOG')) {
            $data['store_url'] = HTTP_CATALOG;
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('octemplates/module/oct_smart_checkout', $data));
    }

    public function addCustomerField() {
        $this->load->language('octemplates/module/oct_smart_checkout');
        $this->load->model('customer/custom_field');

        $json = [];

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateCustomFieldForm()) {
            $this->model_customer_custom_field->addCustomField($this->request->post);

            $json['success']['text'] = $this->language->get('text_success_dop');
            $json['success']['name'] = $this->request->post['custom_field_description'][(int) $this->config->get('config_language_id')]['name'];
            $json['success']['location'] = $this->language->get('text_' . $this->request->post['location']);
            $json['success']['sort_order'] = $this->request->post['sort_order'] ? $this->request->post['sort_order'] : 0;

            $type = '';

            switch ($this->request->post['type']) {
                case 'select':
                    $type = $this->language->get('text_select');
                    break;
                case 'radio':
                    $type = $this->language->get('text_radio');
                    break;
                case 'checkbox':
                    $type = $this->language->get('text_checkbox');
                    break;
                case 'input':
                    $type = $this->language->get('text_input');
                    break;
                case 'text':
                    $type = $this->language->get('text_text');
                    break;
                case 'textarea':
                    $type = $this->language->get('text_textarea');
                    break;
                case 'file':
                    $type = $this->language->get('text_file');
                    break;
                case 'date':
                    $type = $this->language->get('text_date');
                    break;
                case 'datetime':
                    $type = $this->language->get('text_datetime');
                    break;
                case 'time':
                    $type = $this->language->get('text_time');
                    break;
            }

            $json['success']['type'] = $type;
        } else {
            $json['error'] = $this->error;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function deleteAllSelected() {
        $this->load->language('octemplates/module/oct_smart_checkout');
        $this->load->model('customer/custom_field');

        $json = [];

        if (isset($this->request->post['selected']) && $this->validateCustomFieldDelete()) {
            foreach ($this->request->post['selected'] as $custom_field_id) {
                $this->model_customer_custom_field->deleteCustomField($custom_field_id);
            }

            $json['success'] = $this->language->get('text_success_delete');
        } else {
            $json['error'] = $this->error;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function editCustomFieldForm() {
        $this->load->language('octemplates/module/oct_smart_checkout');
        $this->load->model('customer/custom_field');
        $this->load->model('localisation/language');
        $this->load->model('customer/customer_group');

        if (($this->request->server['REQUEST_METHOD'] == 'POST')) {

            $custom_field_info = $this->model_customer_custom_field->getCustomField($this->request->post['custom_field_id']);

            $data['custom_field_id'] = (int) $this->request->post['custom_field_id'];

            if (isset($this->request->post['custom_field_id'])) {
                $data['custom_field_description'] = $this->model_customer_custom_field->getCustomFieldDescriptions($this->request->post['custom_field_id']);
            } else {
                $data['custom_field_description'] = array();
            }
    
            if (!empty($custom_field_info)) {
                $data['type'] = $custom_field_info['type'];
                $data['value'] = $custom_field_info['value'];
                $data['location'] = $custom_field_info['location'];
                $data['validation'] = $custom_field_info['validation'];
                $data['status'] = $custom_field_info['status'];
                $data['sort_order'] = $custom_field_info['sort_order'];
                $custom_field_values = $this->model_customer_custom_field->getCustomFieldValueDescriptions($this->request->post['custom_field_id']);
            } 

            $data['custom_field_values'] = array();
    
            foreach ($custom_field_values as $custom_field_value) {
                $data['custom_field_values'][] = array(
                    'custom_field_value_id'          => $custom_field_value['custom_field_value_id'],
                    'custom_field_value_description' => $custom_field_value['custom_field_value_description'],
                    'sort_order'                     => $custom_field_value['sort_order']
                );
            }
    
            if (isset($this->request->post['custom_field_id'])) {
                $custom_field_customer_groups = $this->model_customer_custom_field->getCustomFieldCustomerGroups($this->request->post['custom_field_id']);
            } else {
                $custom_field_customer_groups = array();
            }

    
            $data['custom_field_customer_group'] = array();
    
            foreach ($custom_field_customer_groups as $custom_field_customer_group) {
                $data['custom_field_customer_group'][] = $custom_field_customer_group['customer_group_id'];
            }
    
            $data['custom_field_required'] = array();
    
            foreach ($custom_field_customer_groups as $custom_field_customer_group) {
                if ($custom_field_customer_group['required']) {
                    $data['custom_field_required'][] = $custom_field_customer_group['customer_group_id'];
                }
            }
    
    
            $data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

            $data['languages'] = $this->model_localisation_language->getLanguages();

            $html = $this->load->view('octemplates/module/oct_smart_checkout_custom_field_item', $data, true);
            $this->response->setOutput($html);
        } else {
            $this->response->setOutput($this->error);
        }
    }

    public function editCustomField() {
        $this->load->language('octemplates/module/oct_smart_checkout');
        $this->load->model('customer/custom_field');

        $json = [];

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateCustomFieldForm()) {
            $this->model_customer_custom_field->editCustomField($this->request->post['custom_field_id'], $this->request->post);

            $json['success']['text'] = $this->language->get('text_success_edit');
            $json['success']['name'] = $this->request->post['custom_field_description'][(int) $this->config->get('config_language_id')]['name'];
            $json['success']['location'] = $this->language->get('text_' . $this->request->post['location']);
            $json['success']['sort_order'] = $this->request->post['sort_order'] ? $this->request->post['sort_order'] : 0;

            $type = '';

            switch ($this->request->post['type']) {
                case 'select':
                    $type = $this->language->get('text_select');
                    break;
                case 'radio':
                    $type = $this->language->get('text_radio');
                    break;
                case 'checkbox':
                    $type = $this->language->get('text_checkbox');
                    break;
                case 'input':
                    $type = $this->language->get('text_input');
                    break;
                case 'text':
                    $type = $this->language->get('text_text');
                    break;
                case 'textarea':
                    $type = $this->language->get('text_textarea');
                    break;
                case 'file':
                    $type = $this->language->get('text_file');
                    break;
                case 'date':
                    $type = $this->language->get('text_date');
                    break;
                case 'datetime':
                    $type = $this->language->get('text_datetime');
                    break;
                case 'time':
                    $type = $this->language->get('text_time');
                    break;
            }

            $json['success']['type'] = $type;
        } else {
            $json['error'] = $this->error;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function validateCustomFieldForm() {
        if (!$this->user->hasPermission('modify', 'customer/custom_field')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		foreach ($this->request->post['custom_field_description'] as $language_id => $value) {
			if ((utf8_strlen($value['name']) < 1) || (utf8_strlen($value['name']) > 128)) {
				$this->error['name'][$language_id] = $this->language->get('error_name');
			}
		}

		if (($this->request->post['type'] == 'select' || $this->request->post['type'] == 'radio' || $this->request->post['type'] == 'checkbox')) {
			if (!isset($this->request->post['custom_field_value'])) {
				$this->error['warning'] = $this->language->get('error_type');
			}

			if (isset($this->request->post['custom_field_value'])) {
				foreach ($this->request->post['custom_field_value'] as $custom_field_value_id => $custom_field_value) {
					foreach ($custom_field_value['custom_field_value_description'] as $language_id => $custom_field_value_description) {
						if ((utf8_strlen($custom_field_value_description['name']) < 1) || (utf8_strlen($custom_field_value_description['name']) > 128)) {
							$this->error['custom_field_value'][$custom_field_value_id][$language_id] = $this->language->get('error_custom_value');
						}
					}
				}
			}
		}

        return !$this->error;
    }

    private function validateCustomFieldDelete() {
        if (!$this->user->hasPermission('modify', 'customer/custom_field')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
        return !$this->error;
    }

    public function install() {
        $this->load->language('octemplates/module/oct_smart_checkout');

        $this->load->model('setting/setting');
        $this->load->model('user/user_group');

        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'octemplates/module/oct_smart_checkout');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'octemplates/module/oct_smart_checkout');

        $this->addEvent();

        $this->model_setting_setting->editSetting('oct_smart_checkout_data', [
            'oct_smart_checkout_data' => [
                'status' => 'on',
                'no_call' => 'on',
                'authorization' => 'on',
                'registration' => 'on',
                'telegram_viber_contact' => 'on',
                'cost_in_shipping_block' => '0',
                'autoselect_first_shipping' => 'on',
                'cart_status' => 'on',
                'cart_weight' => '0',
                'coupon_status' => 'on',
                'voucher_status' => '0',
                'autosubmit_payment' => 'on',
                'minimum_order_amount' => '0',
                'free_shipping_from' => '0',
                'when_empty_email' => $this->config->get('config_email'),
                'phone_mask' => '+38 (999) 999-99-99',
                'comment' => [
                    'status' => '0',
                    'title' => [],
                    'placeholder' => [],
                ],
                'recommended_poducts' => [
                    'status' => '0',
                    'autorelated' => '0',
                    'relatedbysales' => '0',
                    'products' => [],
                    'image_size' => ['width' => 88, 'height' => 88],
                    'title' => [],
                ],
                'notify_email' => $this->config->get('config_email'),
                'default_country' => $this->config->get('config_country_id'),
                'default_region' => $this->config->get('config_zone_id'),
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
                            ],
                            'fax' => [
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
                        ],
                    ],
                ],
                'delivery' => [
                    'fields' => [
                        'default' => [
                            'country_id' => [
                                'status' => 'on',
                                'sort_order' => 0,
                                'required' => 1,
                                'display' => 'all',
                                'local_edit' => 0,
                                'type' => 'select',
                                'merge' => 0,
                                'merge_field' => '',
                                'custom_field' => 0,
                                'custom_field_id' => '',
                                'localization' => []
                            ],
                            'zone_id' => [
                                'status' => 'on',
                                'sort_order' => 0,
                                'required' => 1,
                                'display' => 'all',
                                'local_edit' => 0,
                                'type' => 'select',
                                'merge' => 0,
                                'merge_field' => '',
                                'custom_field' => 0,
                                'custom_field_id' => '',
                                'localization' => []
                            ],
                            'city' => [
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
                            'address_1' => [
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
                            'address_2' => [
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
                            'postcode' => [
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
                            'company' => [
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
                        ],
                    ],
                    'methods' => [],
                ],
                'payment' => [
                    'methods' => [],
                ],
                'sorting_blocks' => [
                    'recommended_block_sort' => 1,
                    'cart_block_sort' => 2,
                    'customer_block_sort' => 3,
                    'country_block_sort' => 4,
                    'delivery_block_sort' => 5,
                    'address_block_sort' => 6,
                    'comment_block_sort' => 7,
                ]
            ],
        ]);

        $this->session->data['success'] = $this->language->get('text_success_install');

        $this->response->redirect($this->url->link('octemplates/module/oct_smart_checkout', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function uninstall($redirect = true) {
        $this->load->language('octemplates/module/oct_smart_checkout');
    
        $this->load->model('setting/setting');
        $this->load->model('user/user_group');
    
        $this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'octemplates/module/oct_smart_checkout');
        $this->model_user_user_group->removePermission($this->user->getGroupId(), 'modify', 'octemplates/module/oct_smart_checkout');
    
        $this->deleteEvent();
    
        $this->model_setting_setting->deleteSetting('oct_smart_checkout_data');
    
        $this->session->data['success'] = $this->language->get('text_success_uninstall');

        if ($redirect) {
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    public function reinstall() {
        $this->load->language('octemplates/module/oct_smart_checkout');

        if (!$this->validate()) {
            $this->session->data['error_warning'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('octemplates/module/oct_smart_checkout', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        $this->uninstall($redirect = false);
        $this->install();
    
        $this->session->data['success'] = $this->language->get('text_success_reinstall');
    
        $this->response->redirect($this->url->link('octemplates/module/oct_smart_checkout', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function exportSettings() {
        $this->load->language('octemplates/module/oct_smart_checkout');
        $this->load->model('setting/setting');
    
        if ($this->validate()) {
            $settings = $this->model_setting_setting->getSetting('oct_smart_checkout_data');
            $json_data = json_encode($settings, JSON_UNESCAPED_UNICODE);
    
            $this->response->addHeader('Content-Type: application/octet-stream');
            $this->response->addHeader('Content-Disposition: attachment; filename="oct_smart_checkout.json"');
            $this->response->addHeader('Content-Length: ' . strlen($json_data));
            $this->response->addHeader('Pragma: no-cache');
            $this->response->addHeader('Expires: 0');
    
            $this->response->setOutput($json_data);
        } else {
            $this->session->data['error_warning'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('octemplates/module/oct_smart_checkout', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }
    }

    public function importSettings() {
        $this->load->language('octemplates/module/oct_smart_checkout');
        $json = [];
    
        if (!$this->validate()) {
            $json['error'] = $this->language->get('error_permission');
            $this->sendResponse($json);
            return;
        }
    
        $content = $this->request->post['json_data'] ?? '';
        $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
    
        if (empty($content)) {
            $json['error'] = $this->language->get('error_upload');
            $this->sendResponse($json);
            return;
        }

        $data = json_decode($content, true);
    
        if (json_last_error() !== JSON_ERROR_NONE) {
            $json['error'] = $this->language->get('error_invalid_json') . ': ' . json_last_error_msg();
            $this->sendResponse($json);
            return;
        }
    
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('oct_smart_checkout_data', $data);
        $json['success'] = $this->language->get('text_success_import');
    
        $this->sendResponse($json);
    }
    
    protected function sendResponse($json) {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function country() {
        $json = [];

        $this->load->model('localisation/country');

        $country_info = $this->model_localisation_country->getCountry($this->request->get['country_id']);

        if ($country_info) {
            $this->load->model('localisation/zone');

            $json = [
                'country_id' => $country_info['country_id'],
                'name' => $country_info['name'],
                'iso_code_2' => $country_info['iso_code_2'],
                'iso_code_3' => $country_info['iso_code_3'],
                'address_format' => $country_info['address_format'],
                'postcode_required' => $country_info['postcode_required'],
                'zone' => $this->model_localisation_zone->getZonesByCountryId($this->request->get['country_id']),
                'status' => $country_info['status'],
            ];
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function addEvent() {

        // Install theme events
        $this->load->model('setting/event');

        if ($this->model_setting_event->getEventByCode('octemplates-remarket-smart-checkout')) {
            return;
        }

        $this->deleteEvent();

        $events = [
            [
                'code' => 'octemplates-remarket-smart-checkout',
                'trigger' => 'catalog/controller/checkout/checkout/before',
                'action' => 'octemplates/events/smartcheckout',
            ],
        ];

        foreach ($events as $event) {
            if (!$this->model_setting_event->getEventByCode($event['code'])) {
                $this->model_setting_event->addEvent($event['code'], $event['trigger'], $event['action'], 1, 550);
            }
        }
    }

    public function deleteEvent() {

        // Uninstall theme events
        $this->load->model('setting/event');

        $eventCodes = [
            'octemplates-remarket-smart-checkout',
        ];

        foreach ($eventCodes as $code) {
            if ($this->model_setting_event->getEventByCode($code)) {
                $this->model_setting_event->deleteEventByCode($code);
            }
        }
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'octemplates/module/oct_smart_checkout')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
    
    protected function checkOldCheckout(&$data) {
        $this->load->model('setting/setting');
        $this->language->load('octemplates/module/oct_smart_checkout');

        $oldCheckout = $this->model_setting_setting->getSetting('oct_fastorder_data');

		if ($oldCheckout) {
			$data['old_module_exists'] = $this->language->get('error_old_module_exists');
		}

        $file = DIR_SYSTEM  . 'oct_rm_fastorder.ocmod.xml';

		if (is_file($file) && is_file(DIR_SYSTEM . 'oct_rm_fastorder.ocmod.xml')) {
			$data['old_module_exists'] = $this->language->get('error_old_module_exists');
		}
        
    }
    
    public function offOldModle() {

        if (!$this->validate()) {
            return;
        }

        $json = [];

        $this->load->model('setting/setting');
        $this->load->model('user/user_group');

		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'octemplates/module/oct_fastorder');
		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'modify', 'octemplates/module/oct_fastorder');

        $this->model_setting_setting->deleteSetting('oct_fastorder_data');

		$file = DIR_SYSTEM  . 'oct_rm_fastorder.ocmod.xml';

		if (is_file($file) && is_file(DIR_SYSTEM . 'oct_rm_fastorder.ocmod.xml')) {
			unlink($file);
		}

        $this->load->controller('extension/theme/oct_remarket/refresh', 0);
        
        $json['success'] = $this->language->get('text_success_off_old_module');

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

}
