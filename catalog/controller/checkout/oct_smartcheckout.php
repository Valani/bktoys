<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerCheckoutOctSmartcheckout extends Controller {
    public function index() {
        $data = [];

        $oct_smart_checkout_data         = $this->config->get('oct_smart_checkout_data');
        $data['oct_smart_checkout_data'] = $oct_smart_checkout_data;

        $this->load->model('setting/extension');
        $this->load->model('account/address');
        $this->load->model('setting/extension');
        $this->load->model('localisation/country');
        $this->load->model('tool/image');
        $this->load->model('tool/upload');

        if (!isset($oct_smart_checkout_data['status']) || $oct_smart_checkout_data['status'] != "on") {
            $this->response->redirect($this->url->link('checkout/cart'));
        }

        $this->load->language('octemplates/module/oct_smartcheckout');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->addStyle('catalog/view/theme/oct_remarket/stylesheet/smartcheckout.css');
        $this->document->addScript('catalog/view/theme/oct_remarket/js/jquery.inputmask.bundle.min.js');

        if (isset($this->session->data['shipping_address_id'])) {
            unset($this->session->data['shipping_address_id']);
        }

        if (!isset($this->session->data['guest']['customer_group_id'])) {
            $this->session->data['guest']['customer_group_id'] = (int) $this->config->get('config_customer_group_id');
        }

        if (!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) {
            $this->response->redirect($this->url->link('checkout/oct_smartcheckout/empty_cart'));
        }

        $abandoned_module_settings = $this->config->get('oct_abandoned_cart') ?: [];
        if (isset($abandoned_module_settings['status']) && $abandoned_module_settings['status']) {
            $this->load->controller('octemplates/module/oct_abandoned_cart/setOrUpdateCookie');
            $data['abandoned_module_status'] = true;
        }

        if (isset($this->session->data['customer_id'])) {
            $data['customer_id'] = $this->session->data['customer_id'];

            if (isset($this->session->data['checkout_customer_id']) && $this->session->data['checkout_customer_id'] === true) {
                unset($this->session->data['shipping_method']);
                unset($this->session->data['shipping_methods']);
                unset($this->session->data['shipping_address']);
                unset($this->session->data['shipping_address_id']);
                unset($this->session->data['payment_address']);
                unset($this->session->data['payment_address_id']);
                unset($this->session->data['payment_method']);
                unset($this->session->data['payment_methods']);
                unset($this->session->data['guest']);
                unset($this->session->data['account']);
                unset($this->session->data['shipping_country_id']);
                unset($this->session->data['shipping_zone_id']);
                unset($this->session->data['payment_country_id']);
                unset($this->session->data['payment_zone_id']);
                unset($this->session->data['oct_form_data']);
            }
        }

        $data['phone_mask'] = $oct_smart_checkout_data['phone_mask'] ?? '';
        $data['breadcrumbs'] = $this->prepareBreadcrumbs();
        $data['action'] = $this->url->link('checkout/oct_smartcheckout/submitForm', '', true); 
        $data['is_logged'] = $this->customer->isLogged();
        $data['language_id'] = (int) $this->config->get('config_language_id');
        $data['countries'] = $this->model_localisation_country->getCountries();
        $data['default_country_id'] = (int)  $this->config->get('config_country_id');
        $data['zones'] = $this->zone($data['default_country_id']);
        $data['cost_in_shipping_block'] = $oct_smart_checkout_data['cost_in_shipping_block'] ?? 0;
        $data['customer_fields_block'] = $this->getCustomerFields($data);
        $data['country_zone_fields_block'] = $this->getCountryZoneFields($data);
        $data['customer_address_fields'] = $this->getCustomerAddressFields($data);
        $data['recommended_poducts'] = $this->getRecommendedProducts($data);
        $data['commentIsAviable'] = $oct_smart_checkout_data['comment']['status'] ?? '';
        $data['commentData'] = $oct_smart_checkout_data['comment'] ?? '';
        $data['no_call'] = $oct_smart_checkout_data['no_call'] ?? '';
        $data['telegram_viber_contact'] = $oct_smart_checkout_data['telegram_viber_contact'] ?? '';
        $data['cart_status'] = $oct_smart_checkout_data['cart_status'] ?? '';
        $data['coupon_status_check'] = $oct_smart_checkout_data['coupon_status'] ?? '';
        $data['voucher_status_check'] = $oct_smart_checkout_data['voucher_status'] ?? '';
        $data['free_shipping_from'] = (int) $oct_smart_checkout_data['free_shipping_from'] ?? '';
        $data['registration_enabled'] = $oct_smart_checkout_data['registration'] ?? '';
        $data['authorization_enabled'] = $oct_smart_checkout_data['authorization'] ?? '';
        $data['sorting_blocks'] = $oct_smart_checkout_data['sorting_blocks'] ?? '';
        $data['autoselect_first_shipping'] = $oct_smart_checkout_data['autoselect_first_shipping'] ?? '';
        $data['autosubmit_payment'] = isset($oct_smart_checkout_data['autosubmit_payment']) ? $oct_smart_checkout_data['autosubmit_payment'] : '0';
        
        if ($data['sorting_blocks']) {
            $keys = array_keys($data['sorting_blocks']);
            foreach ($keys as $index => $key) {
                $data[$key] = $index;
            }
        }   

        $this->setDefaultFieldValues($data);
        $this->shippingAddress(false, $data);
        $this->shippingMethods(false, $data);
        $this->paymentMethods(false, $data);
        $this->paymentAddress(false, $data);
        $this->shoppingCart(false, $data);
        $this->PaymentMethodValidate();

        if ($data['has_reward']) {
            $data['entry_reward'] = sprintf($this->language->get('entry_reward'), $data['has_reward']);
            $reward_points = $this->customer->getRewardPoints();
            $data['entry_reward_title'] = sprintf($this->language->get('entry_reward_title'), $reward_points);
        }

        if (isset($this->session->data['error'])) {
            $data['error_warning'] = $this->session->data['error'];
            unset($this->session->data['error']);
        } else {
            $data['error_warning'] = '';
        }

        if ($this->config->get('config_customer_price') && !$this->customer->isLogged()) {
            $data['attention'] = sprintf($this->language->get('text_login'), $this->url->link('account/register'));
        } else {
            $data['attention'] = '';
        }

        if (isset($this->session->data['shipping_methods'])) {
            $data['shipping_methods'] = $this->session->data['shipping_methods'];
        } else {
            $data['shipping_methods'] = [];
        }

        if (isset($this->session->data['shipping_method']['code'])) {
            $data['shipping_code'] = $this->session->data['shipping_method']['code'];
        } else {
            $data['shipping_code'] = '';
        }

        if (isset($this->session->data['comment'])) {
            $data['comment'] = $this->session->data['comment'];
        } else {
            $data['comment'] = '';
        }

        if (isset($this->session->data['payment_methods'])) {
            $data['payment_methods'] = $this->session->data['payment_methods'];
        } else {
            $data['payment_methods'] = [];
        }

        if (isset($this->session->data['payment_method']['code'])) {
            $data['payment_code'] = $this->session->data['payment_method']['code'];
        } else {
            $data['payment_code'] = '';
        }

        if ($this->config->get('config_cart_weight') && (isset($oct_smart_checkout_data['cart_weight']) && $oct_smart_checkout_data['cart_weight'] == "on")) {
            $data['weight'] = $this->weight->format($this->cart->getWeight(), $this->config->get('config_weight_class_id'), $this->language->get('decimal_point'), $this->language->get('thousand_point'));
        } else {
            $data['weight'] = '';
        }

        if (isset($this->request->post['coupon'])) {
            $data['coupon'] = $this->request->post['coupon'];
        } elseif (isset($this->session->data['coupon'])) {
            $data['coupon'] = $this->session->data['coupon'];
        } else {
            $data['coupon'] = '';
        }

        $data['voucher_status'] = $this->config->get('voucher_status');

        if (isset($this->request->post['voucher'])) {
            $data['voucher'] = $this->request->post['voucher'];
        } elseif (isset($this->session->data['voucher'])) {
            $data['voucher'] = $this->session->data['voucher'];
        } else {
            $data['voucher'] = '';
        }

        if ($this->config->get('config_checkout_id')) {
            $this->load->model('catalog/information');

            $information_info = $this->model_catalog_information->getInformation($this->config->get('config_checkout_id'));

            if ($information_info) {

                $data['text_agree'] = sprintf($this->language->get('text_agree'), $this->url->link('information/information/agree', 'information_id=' . $this->config->get('config_checkout_id'), 'SSL'), $information_info['title'], $information_info['title']);

            } else {
                $data['text_agree'] = '';
            }
        } else {
            $data['text_agree'] = '';
        }

        if (isset($this->session->data['agree'])) {
            $data['agree'] = $this->session->data['agree'];
        } else {
            $data['agree'] = '';
        }

        $data['addresses'] = $this->model_account_address->getAddresses();

        if ($this->customer->isLogged()) {
            $data['firstname']          = $this->customer->getFirstName();
            $data['lastname']           = $this->customer->getLastName();
            $data['email_user']         = $this->customer->getEmail();
            $data['telephone_user']     = $this->customer->getTelephone();
            $data['payment_address_id'] = $this->customer->getAddressId();
            $data['user_logged']           = true;
            $data['address']            = $this->model_account_address->getAddress($this->customer->getAddressId());
            $data['total_addresses'] = $this->model_account_address->getTotalAddresses($this->customer->getId());
        }
        
        $data['loyalty_check'] = $data['coupon_status_check'] || $data['voucher_status_check'] || (!empty($data['has_reward']) && !empty($data['user_logged']));

        $data['modules'] = [];

        $files = glob(DIR_APPLICATION . '/controller/extension/total/*.php');

        if ($files) {
            foreach ($files as $file) {
                $result = $this->load->controller('extension/total/' . basename($file, '.php'));

                if (basename($file, '.php') != 'shipping') {
                    if ($result) {
                        $data['modules'][] = $result;
                    }
                }
            }
        }

        $data['column_left']    = $this->load->controller('common/column_left');
        $data['column_right']   = $this->load->controller('common/column_right');
        $data['content_top']    = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer']         = $this->load->controller('common/footer');
        $data['header']         = $this->load->controller('common/header');
       
        $this->response->setOutput($this->load->view('checkout/oct_smartcheckout/smartcheckout', $data));
    }  
    
    public function abandonedSave() {
        $abandoned_module_settings = $this->config->get('oct_abandoned_cart') ?: [];
        if (isset($abandoned_module_settings['status']) && $abandoned_module_settings['status']) {
            $this->load->controller('octemplates/module/oct_abandoned_cart/setOrUpdateCookie');
        }
    }
    
    public function shippingMethodsFront() {

        if ($this->isValidRequest()) {

            $json = array();

            $country_id = (int) $this->config->get('config_country_id');
            $zone_id = (int) $this->config->get('config_zone_id');

            $shipping_address = array(
                'country_id' => $country_id,
                'zone_id' => $zone_id,
                'iso_code_2' => '',
                'iso_code_3' => '',
                'address_format' => '',
                'firstname' => '',
                'lastname' => '',
                'company' => '',
                'address_1' => ''
            );

            $this->tax->setShippingAddress($shipping_address['country_id'], $shipping_address['zone_id']);

            $method_data = [];

            $this->load->model('setting/extension');

            $results = $this->model_setting_extension->getExtensions('shipping');

            foreach ($results as $result) {
                if ($this->config->get('shipping_' . $result['code'] . '_status')) {
                    $this->load->model('extension/shipping/' . $result['code']);

                    $quote = $this->{'model_extension_shipping_' . $result['code']}->getQuote($shipping_address);

                    if ($quote) {

                        $method_data[$result['code']] = array(
                            'title'      => $quote['title'],
                            'quote'      => $quote['quote'],
                            'sort_order' => $quote['sort_order'],
                            'error'      => $quote['error']
                        );
                    }
                }
            }

            $sort_order = [];

            foreach ($method_data as $key => $value) {
                $sort_order[$key] = $value['sort_order'];
            }

            array_multisort($sort_order, SORT_ASC, $method_data);

            $json = $method_data;


            $this->response->setOutput(json_encode($json));

        }

    }

    public function country($data = array()) {
        $json = [];

        $this->load->model('localisation/country');

        $country_info = $this->model_localisation_country->getCountry($this->request->get['country_id']);

        if ($country_info) {
            $this->load->model('localisation/zone');

            $json = array(
                'country_id' => $country_info['country_id'],
                'name' => $country_info['name'],
                'iso_code_2' => $country_info['iso_code_2'],
                'iso_code_3' => $country_info['iso_code_3'],
                'address_format' => $country_info['address_format'],
                'postcode_required' => $country_info['postcode_required'],
                'zone' => $this->model_localisation_zone->getZonesByCountryId($this->request->get['country_id']),
                'status' => $country_info['status']
            );
        }

        $this->response->setOutput(json_encode($json));
    }

    public function zone($def_country) {

        if (!isset($def_country)) {
            $def_country = (int) $this->config->get('config_country_id');
        }

        $data['zones'] = [];

        $this->load->model('localisation/zone');
        $data['zones'] = $this->model_localisation_zone->getZonesByCountryId($def_country);

        return $data['zones'];
    }

    public function empty_cart() {
        if (!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) {
            $this->load->language('checkout/cart');

            $this->document->setTitle($this->language->get('heading_title'));

            $data['breadcrumbs'] = [];

            $data['breadcrumbs'][] = array(
                'href' => $this->url->link('common/home'),
                'text' => $this->language->get('text_home')
            );

            $data['breadcrumbs'][] = array(
                'href' => $this->url->link('checkout/cart'),
                'text' => $this->language->get('heading_title')
            );

            $data['text_error'] = $this->language->get('text_empty');

            $data['continue'] = $this->url->link('common/home');

            unset($this->session->data['success']);

            $data['column_left']    = $this->load->controller('common/column_left');
            $data['column_right']   = $this->load->controller('common/column_right');
            $data['content_top']    = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer']         = $this->load->controller('common/footer');
            $data['header']         = $this->load->controller('common/header');

            $this->response->redirect($this->url->link('checkout/cart'));
        } else {
            $this->response->redirect($this->url->link('checkout/oct_smartcheckout'));
        }
    }

    public function shippingMethodValidate(&$data = array()) {
        $json = [];

        $this->load->language('octemplates/module/oct_smartcheckout');

        $this->load->model('account/address');

        if ($this->customer->isLogged() && isset($this->session->data['shipping_address_id'])) {
            $shipping_address = $this->model_account_address->getAddress($this->session->data['shipping_address_id']);
        } elseif (isset($this->session->data['guest'])) {
            $shipping_address = isset($this->session->data['guest']['shipping']) ? $this->session->data['guest']['shipping'] : '';
        }

        // Validate minimum quantity requirments.
        $products = $this->cart->getProducts();

        foreach ($products as $product) {
            $product_total = 0;

            foreach ($products as $product_2) {
                if ($product_2['product_id'] == $product['product_id']) {
                    $product_total += $product_2['quantity'];
                }
            }

            if ($product['minimum'] > $product_total) {
                $json['redirect'] = $this->url->link('checkout/oct_smartcheckout');
                break;
            }
        }

        if (!$json) {
            if (!isset($this->request->post['shipping_method'])) {
                $json['error']['warning'] = $this->language->get('error_shipping');
            } else {
                $shipping = explode('.', $this->request->post['shipping_method']);
                if (!isset($shipping[0]) || !isset($shipping[1])) {
                    $json['error']['warning'] = $this->language->get('error_shipping');
                }
            }

            if (!$json) {
                $shipping = explode('.', $this->request->post['shipping_method']);

                $this->shippingMethods(false, $data);

                if (isset($this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]]))
                    $this->session->data['shipping_method'] = $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];

                $this->session->data['comment'] = (isset($this->request->post['comment'])) ? strip_tags($this->request->post['comment']) : '';
            }
        }

        return $json;
    }

    private function checkAgree() {
        $json = [];

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

    public function validateForm($data = array()) {
        $json = [];

        if (!$this->customer->isLogged()) {
            if (isset($this->request->post['register'])) {
                $json = $this->registerValidate($data);
            } else {
                $json = $this->guestValidate($data);
            }
        } else {
            $json = $this->customerValidate($data);
        }

        $this->load->controller('octemplates/module/oct_abandoned_cart/setOrUpdateCookie');

        if (!isset($this->request->post['shipping_method'])) {
            $json['error']['shipping_warning'] = $this->language->get('error_shipping');
        }

        if (!isset($json['error'])) {
            $json = array_merge($json, $this->confirmCheckout($data));
        }

        $this->response->setOutput(json_encode($json));
    
    }

    public function formDataChange() {
        $json = [];
    
        if ($this->isValidRequest()) {

            if (!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) {
                $json['error'] = true;
            }

            if (!empty($this->request->post)) {
                foreach ($this->request->post as $key => $value) {
                    $this->session->data['oct_form_data'][$key] = $value;
                }

                $this->load->controller('octemplates/module/oct_abandoned_cart/setOrUpdateCookie');
            }
            
            if (!isset($json['error'])) {
                $json['recommended_poducts'] = $this->getRecommendedProducts();
                $json['cart'] = $this->shoppingCart('true');
                $json['shipping_block'] = $this->shippingMethods('true');
                $json['payment_block'] = $this->paymentMethods('true');
                $json['customer_fields'] = $this->getCustomerFields();
                $json['countryzone_fields'] = $this->getCountryZoneFields();
                $json['customer_address_fields'] = $this->getCustomerAddressFields();
                $json['payment_fields'] = $this->getPaymentFields(1);
            } else {
                $json['redirect'] = $this->url->link('checkout/cart');
                $json['error'] = 'Error data change';
            }

            unset($this->session->data['oct_form_data']);   

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(['html' => $json]));
        } else {
            $json['error'] = 'Error data change';
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
        }
    }

    public function statusCart() {
        $json = [];

        $this->load->language('octemplates/module/oct_smartcheckout');

        if (!$this->cart->hasProducts()) {
            $json['redirect'] = $this->url->link('checkout/oct_smartcheckout');
        }

        // Totals
        $this->load->model('setting/extension');

        $totals = [];
        $taxes  = $this->cart->getTaxes();
        $total  = 0;

        // Because __call can not keep var references so we put them into an array.
        $total_data = array(
            'totals' => &$totals,
            'taxes' => &$taxes,
            'total' => &$total
        );

        // Because __call can not keep var references so we put them into an array. 			
        $total_data = array(
            'totals' => &$totals,
            'taxes'  => &$taxes,
            'total'  => &$total
        );

        // Display prices
        if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
            $sort_order = array();

            $results = $this->model_setting_extension->getExtensions('total');

            foreach ($results as $key => $value) {
                $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
            }

            array_multisort($sort_order, SORT_ASC, $results);

            foreach ($results as $result) {
                if ($this->config->get('total_' . $result['code'] . '_status')) {
                    $this->load->model('extension/total/' . $result['code']);

                    // We have to put the totals in an array so that they pass by reference.
                    $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
                }
            }

            $sort_order = array();

            foreach ($totals as $key => $value) {
                $sort_order[$key] = $value['sort_order'];
            }

            array_multisort($sort_order, SORT_ASC, $totals);
        }

        $data['totals'] = [];

        foreach ($totals as $total_value) {
            $data['totals'][] = array(
                'title' => $total_value['title'],
                'text' => $this->currency->format($total_value['value'], $this->session->data['currency'])
            );
        }

        $json['total']      = $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0);
        $json['total_amount'] = $this->currency->format($total, $this->session->data['currency']);

        $this->load->controller('octemplates/module/oct_abandoned_cart/setOrUpdateCookie');

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function prepareBreadcrumbs() {
        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('checkout/oct_smartcheckout', '', true)
        ];

        return $data['breadcrumbs'];
    }

    private function getRecommendedProducts() {
        $this->load->model('catalog/product');
        $this->load->model('tool/image');
        $this->load->model('octemplates/helper');
        $this->load->language('octemplates/module/oct_smartcheckout');

        $oct_smart_checkout_data = $this->config->get('oct_smart_checkout_data');
        $data['oct_smart_checkout_data'] = $oct_smart_checkout_data;

        $data['recommended_poducts'] = array();
        $manual_recommendations = array();
        $automatic_recommendations = array();
        $products = $this->cart->getProducts();


        if (isset($oct_smart_checkout_data['recommended_poducts']['autorelated'])) {
            $manual_recommendations = $this->model_octemplates_helper->getManualRecommendations($products) ?? [];
        }

        if (isset($oct_smart_checkout_data['recommended_poducts']['relatedbysales'])) {
            $automatic_recommendations = $this->model_octemplates_helper->getAutomaticRecommendations($products) ?? [];
        }

        $data['manual_automatic_products'] = array_merge($manual_recommendations, $automatic_recommendations);

        if (isset($oct_smart_checkout_data['recommended_poducts']['status']) && $oct_smart_checkout_data['recommended_poducts']['status'] == "on") {

            $recommended_poducts = [];
            if (isset($oct_smart_checkout_data['recommended_poducts']['products']) && is_array($oct_smart_checkout_data['recommended_poducts']['products'])) {
                $recommended_poducts = $oct_smart_checkout_data['recommended_poducts']['products'];
            }
            
            $product_ids = array_merge($data['manual_automatic_products'], $recommended_poducts);
            $product_ids = array_unique($product_ids);       

            $exclude_ids = array_map(function($product) {
                return $product['product_id'];
            }, $products);
            
            $imageWidth = isset($oct_smart_checkout_data['recommended_poducts']['image_size']['width']) ? $oct_smart_checkout_data['recommended_poducts']['image_size']['width'] : 200;
            $imageHeight = isset($oct_smart_checkout_data['recommended_poducts']['image_size']['height']) ? $oct_smart_checkout_data['recommended_poducts']['image_size']['height'] : 200;
            $data['title'] = isset($oct_smart_checkout_data['recommended_poducts']['title'][$this->config->get('config_language_id')]) ? $oct_smart_checkout_data['recommended_poducts']['title'][$this->config->get('config_language_id')] : $this->language->get('text_recommended_products');
            $data['description'] = isset($oct_smart_checkout_data['recommended_poducts']['description'][$this->config->get('config_language_id')]) ? $oct_smart_checkout_data['recommended_poducts']['description'][$this->config->get('config_language_id')] : '';

            $results = [];
            foreach ($product_ids as $product_id) {

                if (in_array($product_id, $exclude_ids)) {
                    continue; 
                }

                $product_info = $this->model_catalog_product->getProduct($product_id);

                if ($product_info) {

                    if ($product_info['image']) {
                        $image = $this->model_tool_image->resize($product_info['image'], $imageWidth, $imageHeight);
                    } else {
                        $image = $this->model_tool_image->resize('placeholder.png', $imageWidth, $imageHeight);
                    }
    
                    if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                        $price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    } else {
                        $price = false;
                    }
    
                    if (!is_null($product_info['special']) && (float)$product_info['special'] >= 0) {
                        $special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                        $tax_price = (float)$product_info['special'];
                    } else {
                        $special = false;
                        $tax_price = (float)$product_info['price'];
                    }
        
                    if ($this->config->get('config_tax')) {
                        $tax = $this->currency->format($tax_price, $this->session->data['currency']);
                    } else {
                        $tax = false;
                    }
    
                    if ($this->config->get('config_review_status')) {
                        $rating = $product_info['rating'];
                    } else {
                        $rating = false;
                    }
    
                    $data['recommended_poducts'][] = array(
                        'product_id'  => $product_info['product_id'],
                        'thumb'       => $image,
                        'width'       => $imageWidth,
                        'height'      => $imageHeight,
                        'name'        => $product_info['name'],
                        'description' => utf8_substr(trim(strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
                        'price'       => $price,
                        'special'     => $special,
                        'tax'         => $tax,
                        'rating'      => $rating,
                        'href'        => $this->url->link('product/product', 'product_id=' . $product_info['product_id'])
                    );
                }
            }

            if ($data['recommended_poducts']) {
                return $this->load->view('checkout/oct_smartcheckout/recommended_products', $data);
            }
        } else {
            return '';
        }
    }
    
    private function getFieldList($fieldType, $FiledGroup = 'default') {

        if ($fieldType == 'payment') {
            $fieldsSettings = isset($this->config->get('oct_smart_checkout_data')[$fieldType]['methods'][$FiledGroup]['fields']) ? $this->config->get('oct_smart_checkout_data')[$fieldType]['methods'][$FiledGroup]['fields'] : [];
        } elseif ($fieldType == 'delivery') {
            $fieldsSettings = isset($this->config->get('oct_smart_checkout_data')[$fieldType]['fields'][$FiledGroup]) ? $this->config->get('oct_smart_checkout_data')[$fieldType]['fields'][$FiledGroup] : (isset($this->config->get('oct_smart_checkout_data')[$fieldType]['fields']['default']) ? $this->config->get('oct_smart_checkout_data')[$fieldType]['fields']['default'] : []);
        } else {
            $fieldsSettings = isset($this->config->get('oct_smart_checkout_data')[$fieldType]['fields'][$FiledGroup]) ? $this->config->get('oct_smart_checkout_data')[$fieldType]['fields'][$FiledGroup] : $this->config->get('oct_smart_checkout_data')[$fieldType]['fields']['default'];
        }

        $fieldList = [];
        $isLogged = $this->customer->isLogged();

        foreach ($fieldsSettings as $fieldName => $settings) {
            
            if (isset($settings['status'])) {
                
                if ($settings['display'] == 'all' ||
                    ($isLogged && $settings['display'] == 'registered') ||
                    (!$isLogged && $settings['display'] == 'guests')) {

                    if (isset($settings['custom_field']) && $settings['custom_field']) {
                        $customFieldSettings = $this->getCustomFieldSettings($settings['custom_field_id']);
                        $fieldList[$fieldName] = array_merge($settings, $customFieldSettings);
                    } else {
                        $fieldList[$fieldName] = $settings;
                    }
                }
            }
        }

        return $fieldList;
    }
    
    private function getCustomFieldSettings($customFieldId) {
        $this->load->model('account/custom_field');
        $customField = $this->model_account_custom_field->getCustomField($customFieldId);
        $languageId = (int) $this->config->get('config_language_id');
        $customFieldSettings = [];
        if ($customField) {
            $customFieldSettings['type'] = $customField['type'];
            $customFieldSettings['name'] = $customField['name'];
    
            if ($customField['type'] == 'select' or $customField['type'] == 'radio' or $customField['type'] == 'checkbox') {
                $customFieldSettings['options'] = $this->getCustomFieldOptions($customFieldId, $languageId);
            }
        }

        return $customFieldSettings;
    }

    private function getCustomFieldOptions($customFieldId, $languageId) {
        $query = $this->db->query("SELECT cfv.custom_field_value_id, cfvd.name, cfv.sort_order FROM " . DB_PREFIX . "custom_field_value cfv LEFT JOIN " . DB_PREFIX . "custom_field_value_description cfvd ON (cfv.custom_field_value_id = cfvd.custom_field_value_id) WHERE cfv.custom_field_id = '" . (int)$customFieldId . "' AND cfvd.language_id = '" . (int)$languageId . "' ORDER BY cfv.sort_order ASC");
    
        $options = [];
        if ($query->num_rows) {
            foreach ($query->rows as $result) {
                $options[] = [
                    'custom_field_value_id' => $result['custom_field_value_id'],
                    'name' => $result['name']
                ];
            }
        }
        return $options;
    }
    
    private function setDefaultFieldValues($data) {

        if ($this->customer->isLogged()) {
            $this->load->model('account/customer');

            $customer_info = $this->model_account_customer->getCustomer($this->customer->getId());

            $data['fields']['firstname']['value'] = $customer_info['firstname'];
            $data['fields']['lastname']['value'] = $customer_info['lastname'];
            $data['fields']['email']['value'] = $customer_info['email'];
            $data['fields']['telephone']['value'] = $customer_info['telephone'];
            $data['fields']['telephone']['fax'] = $customer_info['fax'];
            
        } else {
            $data['fields']['firstname']['value'] = '';
            $data['fields']['lastname']['value'] = '';
            $data['fields']['email']['value'] = '';
            $data['fields']['telephone']['value'] = '';
            $data['fields']['telephone']['fax'] = '';
        }
        
        return $data;
    }

    private function shippingAddress($render = false, &$data = array()) {
        $this->load->language('octemplates/module/oct_smartcheckout');

        $oct_smart_checkout_data = $data;

        if (isset($this->session->data['shipping_address_id'])) {
            $data['shipping_address_id'] = $this->session->data['shipping_address_id'];
        } else {
            $data['shipping_address_id'] = $this->customer->getAddressId();
        }

        $this->load->model('account/address');

        $data['addresses'] = $this->model_account_address->getAddresses();

        if (isset($this->session->data['shipping_postcode'])) {
            $data['postcode'] = $this->session->data['shipping_postcode'];
        } else {
            $data['postcode'] = '';
        }

        if (isset($this->session->data['shipping_country_id'])) {
            $data['country_id'] = $this->session->data['shipping_country_id'];
        } else {
            $data['country_id'] = (int) $this->config->get('config_country_id');
        }

        if (isset($this->session->data['shipping_zone_id'])) {
            $data['zone_id'] = $this->session->data['shipping_zone_id'];
        } else {
            $data['zone_id'] = '';
        }

        $this->load->model('localisation/country');

        $data['countries'] = $this->model_localisation_country->getCountries();

        if ($render !== false) {
            $this->response->setOutput($this->load->view('checkout/checkout/shipping_address', $data));
        }
    }

    private function shippingMethods($render = false, &$data = array()) {

        $this->load->language('octemplates/module/oct_smartcheckout');
        $this->load->model('account/address');
        $this->load->model('tool/image');

        $oct_smart_checkout_data         = $this->config->get('oct_smart_checkout_data');
        $data['oct_smart_checkout_data'] = $oct_smart_checkout_data;
        $oct_shipping_settings = isset($oct_smart_checkout_data['delivery']['methods']) ? $oct_smart_checkout_data['delivery']['methods'] : [];
        $data['cost_in_shipping_block'] = $oct_smart_checkout_data['cost_in_shipping_block'] ?? 0;
        $data['autoselect_first_shipping'] = $oct_smart_checkout_data['autoselect_first_shipping'] ?? 0;

        $data['heading_shipping_block'] = $this->language->get('heading_shipping_block');
        $data['text_shipping_method']   = $this->language->get('text_shipping_method');
        $data['text_comments']          = $this->language->get('text_comments');
        $data['text_loading']           = $this->language->get('text_loading');
        $data['button_continue']        = $this->language->get('button_continue');

        $shipping_address = array(
            'country_id'      => (int)$this->config->get('config_country_id'),
            'zone_id'         => 0,
            'iso_code_2'      => '',
            'iso_code_3'      => '',
            'address_format'  => '',
            'firstname'       => '',
            'lastname'        => '',
            'company'         => '',
            'address_1'       => ''
        );

        if ($this->customer->isLogged()) {
            if (isset($this->request->post['shipping_address']) && $this->request->post['shipping_address'] == 'new') {
                $country_id = $this->request->post['shipping_country_id'] ?? 0;
                $zone_id    = $this->request->post['shipping_zone_id'] ?? 0;
            } elseif (isset($this->request->post['payment_address']) && $this->request->post['payment_address'] == 'new') {
                $country_id = $this->request->post['country_id'] ?? 0;
                $zone_id    = $this->request->post['zone_id'] ?? 0;
            }
        }

        elseif (isset($this->request->post['country_id'])) {
            $country_id = $this->request->post['country_id'] ?? 0;
            $zone_id    = $this->request->post['zone_id'] ?? 0;
        }

        if (isset($country_id)) {
            $session_guest_shipping = isset($this->session->data['guest']['shipping']) ? $this->session->data['guest']['shipping'] : [];

            $session_guest_shipping_sub = array(
                'country_id'     => $country_id,
                'zone_id'        => $zone_id,
                'iso_code_2'     => '',
                'iso_code_3'     => '',
                'city'           => $this->request->post['city'] ?? '',
                'postcode'       => $this->request->post['postcode'] ?? ''
            );

            $this->session->data['guest']['shipping'] = array_merge($shipping_address, $session_guest_shipping, $session_guest_shipping_sub);

            $this->load->model('localisation/country');
            $country_info = $this->model_localisation_country->getCountry($country_id);
            if ($country_info) {
                $this->session->data['guest']['shipping']['country']        = $country_info['name'];
                $this->session->data['guest']['shipping']['iso_code_2']     = $country_info['iso_code_2'];
                $this->session->data['guest']['shipping']['iso_code_3']     = $country_info['iso_code_3'];
                $this->session->data['guest']['shipping']['address_format'] = $country_info['address_format'];
            } else {
                $this->session->data['guest']['shipping']['country']        = '';
                $this->session->data['guest']['shipping']['iso_code_2']     = '';
                $this->session->data['guest']['shipping']['iso_code_3']     = '';
                $this->session->data['guest']['shipping']['address_format'] = '';
            }

            $this->load->model('localisation/zone');
            $zone_info = $this->model_localisation_zone->getZone($zone_id);
            if ($zone_info) {
                $this->session->data['guest']['shipping']['zone']      = $zone_info['name'];
                $this->session->data['guest']['shipping']['zone_code'] = $zone_info['code'];
            } else {
                $this->session->data['guest']['shipping']['zone']      = '';
                $this->session->data['guest']['shipping']['zone_code'] = '';
            }

            $this->session->data['shipping_country_id'] = $country_id;
            $this->session->data['shipping_zone_id']    = $zone_id;
            $this->session->data['shipping_postcode']   = $this->request->post['postcode'] ?? '';
            $this->session->data['guest']['shipping']['address_1'] = $this->request->post['address_1'] ?? '';
            $this->session->data['guest']['shipping']['postcode']  = $this->request->post['postcode'] ?? '';

            $shipping_address = $this->session->data['guest']['shipping'];

        } elseif ($this->customer->isLogged()) {

            $shipping_address_id = $this->request->post['address_id'] ?? ($this->session->data['address_id'] ?? null);
    
            if ($shipping_address_id && !isset($this->request->post['new_address'])) {
                $shipping_address = $this->model_account_address->getAddress($shipping_address_id);
                $this->session->data['shipping_address_id'] = $shipping_address_id;
                $data['shipping_address_id'] = $shipping_address_id;
            } elseif (isset($this->request->post['country_id'])) {

                $cid = $this->request->post['country_id'];
                $zid = $this->request->post['zone_id'] ?? 0;

                $this->load->model('localisation/country');
                $this->load->model('localisation/zone');

                $country_info = $this->model_localisation_country->getCountry($cid);
                $zone_info = $this->model_localisation_zone->getZone($zid);

                $addr = array(
                    'country_id'      => $cid,
                    'zone_id'         => $zid,
                    'iso_code_2'      => $country_info['iso_code_2'] ?? '',
                    'iso_code_3'      => $country_info['iso_code_3'] ?? '',
                    'city'            => $this->request->post['city'] ?? '',
                    'postcode'        => $this->request->post['postcode'] ?? '',
                    'country'         => $country_info['name'] ?? '',
                    'address_format'  => $country_info['address_format'] ?? '',
                    'zone'            => $zone_info['name'] ?? '',
                    'zone_code'       => $zone_info['code'] ?? '',
                    'address_1'       => $this->request->post['address_1'] ?? '',
                );

                $this->session->data['shipping_country_id'] = $cid;
                $this->session->data['shipping_zone_id']    = $zid;
                $this->session->data['shipping_postcode']   = $this->request->post['postcode'] ?? '';
                $shipping_address = $addr;
            } else {
                $addresses = $this->model_account_address->getAddresses();
                $primary_address = null;

                if ($this->customer->getAddressId()) {
                    $primary_address = $this->model_account_address->getAddress($this->customer->getAddressId());
                }

                if (!$primary_address && !empty($addresses)) {
                    $primary_address = reset($addresses);
                }

                if ($primary_address) {
                    $addr = array(
                        'country_id'     => $primary_address['country_id'] ?? 0,
                        'zone_id'        => $primary_address['zone_id'] ?? 0,
                        'iso_code_2'     => $primary_address['iso_code_2'] ?? '',
                        'iso_code_3'     => $primary_address['iso_code_3'] ?? '',
                        'city'           => $primary_address['city'] ?? '',
                        'postcode'       => $primary_address['postcode'] ?? '',
                        'country'        => $primary_address['country'] ?? '',
                        'address_format' => $primary_address['address_format'] ?? '',
                        'zone'           => $primary_address['zone'] ?? '',
                        'zone_code'      => $primary_address['zone_code'] ?? '',
                        'address_1'      => $primary_address['address_1'] ?? ''
                    );

                    $this->session->data['shipping_country_id'] = $primary_address['country_id'] ?? 0;
                    $this->session->data['shipping_zone_id']    = $primary_address['zone_id'] ?? 0;
                    $this->session->data['shipping_postcode']   = $primary_address['postcode'] ?? '';
                    $shipping_address = $addr;
                }
            }
        } elseif (isset($this->session->data['guest']['shipping'])) {
            $shipping_address = array_merge($shipping_address, $this->session->data['guest']['shipping']);
        }

        if ($this->customer->isLogged() && ( !isset($shipping_address['country_id']) || !$shipping_address['country_id'])) {
            $primary_address = $this->model_account_address->getAddress($this->customer->getAddressId());
            if ($primary_address) {
                $shipping_address = array_merge($shipping_address, $primary_address);
                $this->session->data['shipping_address'] = $shipping_address;
            }
        }

        if (isset($shipping_address)) {

            $this->tax->setShippingAddress($shipping_address['country_id'], $shipping_address['zone_id']);
            $this->session->data['shipping_address'] = $shipping_address;

            $method_data = [];

            $this->load->model('setting/extension');
            $results = $this->model_setting_extension->getExtensions('shipping');

            foreach ($results as $result) {
                if ($this->config->get('shipping_' . $result['code'] . '_status')) {
                    $this->load->model('extension/shipping/' . $result['code']);
            
                    $quote = $this->{'model_extension_shipping_' . $result['code']}->getQuote($this->session->data['shipping_address']);
            
                    if ($quote) {
                        foreach ($quote['quote'] as $quote_code => $quote_info) {
    
                            $shippingQuoteCode = str_replace(".", "-", $quote_info['code']);
            
                            if (isset($oct_shipping_settings[$shippingQuoteCode])) {
                                $delivery_setting = $oct_shipping_settings[$shippingQuoteCode];
            
                                if ($delivery_setting['display'] !== 'all') {
                                    if ($delivery_setting['display'] === 'guests' && $this->customer->isLogged()) {
                                        continue;
                                    }
                                    if ($delivery_setting['display'] === 'registered' && !$this->customer->isLogged()) {
                                        continue;
                                    }
                                }
            
                                if (isset($delivery_setting['status']) && $delivery_setting['status'] == 'on') {
                                    $mainImage = $delivery_setting['image'] ?? '';
                                    $image = $mainImage ? $this->model_tool_image->resize($mainImage, 40, 40) : '';
            
                                    $localization = $delivery_setting['localization'][(int)$this->config->get('config_language_id')] ?? [];
                                    $localizedTitle = !empty($localization['title']) ? $localization['title'] : $quote_info['title'];
                                    $localizedDescription = $localization['description'] ?? '';
            
                                    $quote_info['title'] = $localizedTitle;
                                    $quote_info['description'] = $localizedDescription;
                                    $quote_info['image'] = $image;
                                }
                            }
            
                            $method_data[$result['code']]['quote'][$quote_code] = $quote_info;
                        }
            
                        if (!empty($method_data[$result['code']]['quote'])) {
                            $method_data[$result['code']]['title'] = $quote['title'];
                            $method_data[$result['code']]['sort_order'] = $quote['sort_order'];
                            $method_data[$result['code']]['error'] = $quote['error'];
                        }
                    }
                }
            }
            
            $sort_order = array_column($method_data, 'sort_order');
            array_multisort($sort_order, SORT_ASC, $method_data);
            
            $this->session->data['shipping_methods'] = $method_data;
        }

        foreach ($method_data as $method_code => &$method) {
            if (count($method['quote']) === 1) {
                $quote_key = key($method['quote']);
                $quote = &$method['quote'][$quote_key];

                if (!empty($method['title']) && strpos($method['title'], '<img') === false) {
                    if (!empty($quote['title'])) {
                        $method['title'] = $quote['title'];
                    }
                }
    
                if (!empty($quote['description'])) {
                    $quote['title'] = $quote['description'];
                }
            }
    
            foreach ($method['quote'] as &$quote) {
                if (isset($quote['description']) && !empty($quote['description'])) {
                    $quote['title'] = $quote['description'];
                }
            }
        }
        unset($method);

        $this->session->data['shipping_methods'] = $method_data;
        
        if (empty($this->session->data['shipping_methods'])) {
            $data['error_warning'] = sprintf($this->language->get('error_no_shipping'), $this->url->link('information/contact'));
        } else {
            $data['error_warning'] = '';
        }

        $data['shipping_methods'] = $this->session->data['shipping_methods'] ?? [];
        $data['shipping_code']    = $this->session->data['shipping_method']['code'] ?? '';
        $data['comment']          = $this->session->data['comment'] ?? '';

        if ($render) {
            return $this->load->view('checkout/oct_smartcheckout/shipping_methods', $data);
        }
    }

    private function paymentMethods($render = false, &$data = array()) {
        $this->load->language('octemplates/module/oct_smartcheckout');
        $this->load->model('account/address');        
        $this->load->model('tool/image');
        
        $oct_smart_checkout_data = $this->config->get('oct_smart_checkout_data');
        $data['oct_smart_checkout_data'] = $oct_smart_checkout_data;
        $oct_payment_settings = isset($oct_smart_checkout_data['payment']['methods']) ? $oct_smart_checkout_data['payment']['methods'] : [];

        $payment_address = $this->model_account_address->getAddress((isset($this->request->post['payment_address_id'])) ? $this->request->post['payment_address_id'] : 0);

        if ($payment_address === false) {
            $payment_address = array();
        }

        if (isset($this->request->post['country_id'])) {
            $payment_address['country_id'] = $this->request->post['country_id'];
            $payment_address['payment_country_id'] = $this->request->post['country_id'];

            $this->session->data['shipping_country_id'] = (isset($this->request->post['country_id']) && !empty($this->request->post['country_id'])) ? $this->request->post['country_id'] : '';
            $this->session->data['payment_country_id'] = (isset($this->request->post['country_id']) && !empty($this->request->post['country_id'])) ? $this->request->post['country_id'] : '';
            $this->session->data['guest']['payment']['country_id'] = (isset($this->request->post['country_id']) && !empty($this->request->post['country_id'])) ? $this->request->post['country_id'] : '';
            $this->session->data['guest']['payment']['payment_country_id'] = (isset($this->request->post['country_id']) && !empty($this->request->post['country_id'])) ? $this->request->post['country_id'] : '';
    
            if (isset($this->request->post['zone_id'])) {
                $this->session->data['shipping_zone_id'] = (isset($this->request->post['zone_id']) && !empty($this->request->post['zone_id'])) ? $this->request->post['zone_id'] : '';
                $this->session->data['payment_zone_id'] = (isset($this->request->post['zone_id']) && !empty($this->request->post['zone_id'])) ? $this->request->post['zone_id'] : '';
                $this->session->data['guest']['payment']['zone_id'] = (isset($this->request->post['zone_id']) && !empty($this->request->post['zone_id'])) ? $this->request->post['zone_id'] : '';
                $payment_address['zone_id'] = (isset($this->request->post['zone_id']) && !empty($this->request->post['zone_id'])) ? $this->request->post['zone_id'] : '';
            }
        } elseif ($this->customer->isLogged() && isset($this->session->data['payment_address_id'])) {
            $payment_address = $this->model_account_address->getAddress($this->session->data['payment_address_id']);
        } elseif (isset($this->session->data['guest']['payment'])) {
            $payment_address = $this->session->data['guest']['payment'];
        }

        if (empty($payment_address)) {
            if (!is_array($payment_address)) {
                $payment_address = array();
            }
            $payment_address['zone_id'] = (isset($this->request->post['zone_id']) && !empty($this->request->post['zone_id'])) ? $this->request->post['zone_id'] : '';
            $payment_address['country_id'] = (isset($this->request->post['country_id']) && !empty($this->request->post['country_id'])) ? $this->request->post['country_id'] : '';
        }

        $this->session->data['payment_address'] = $payment_address;

        if (!isset($this->session->data['payment_zone_id '])) {
            $this->session->data['payment_zone_id '] = $payment_address['zone_id'];
        }

        $this->tax->setPaymentAddress($payment_address['country_id'], $payment_address['zone_id']);

        // Totals
        $totals = [];
        $taxes  = $this->cart->getTaxes();
        $total  = 0;

        // Because __call can not keep var references so we put them into an array.
        $total_data = array(
            'totals' => &$totals,
            'taxes' => &$taxes,
            'total' => &$total
        );

        $this->load->model('setting/extension');

        $sort_order = [];

        $results = $this->model_setting_extension->getExtensions('total');

        foreach ($results as $key => $value) {
            $sort_order[$key] = $this->config->get($value['code'] . '_sort_order');
        }

        array_multisort($sort_order, SORT_ASC, $results);

        foreach ($results as $result) {
            if ($this->config->get('total_' . $result['code'] . '_status')) {
                $this->load->model('extension/total/' . $result['code']);

                // We have to put the totals in an array so that they pass by reference.
                $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
            }
        }

        // Payment Methods
        $method_data = [];

        $this->load->model('setting/extension');

        $results = $this->model_setting_extension->getExtensions('payment');

        $recurring = $this->cart->hasRecurringProducts();

        $shipping_method = !empty($this->request->post['shipping_method']) ? $this->request->post['shipping_method'] : null;

        if ($shipping_method) {
            $shipping_method_name = explode('.', $shipping_method)[0];
        }

        foreach ($results as $result) {
            if ($this->config->get('payment_' . $result['code'] . '_status')) {
                $this->load->model('extension/payment/' . $result['code']);

                $method = $this->{'model_extension_payment_' . $result['code']}->getMethod($this->session->data['payment_address'], $total);

                if (isset($oct_payment_settings[$result['code']]) && $method) {
                    $payment_setting = $oct_payment_settings[$result['code']];

                    $mainImage = isset($payment_setting['image']) ? $payment_setting['image'] : '';

                    if ($mainImage) {
                        $image = $this->model_tool_image->resize($mainImage, 40, 40);
                    } else {
                        $image = '';
                    }

                    $localization = $payment_setting['localization'][(int)$this->config->get('config_language_id')] ?? [];
                    $title = (!empty($localization['title'])) ? $localization['title'] : $method['title'];
                    $method['image'] = $image;

                    if (isset($payment_setting['local_edit'])) {
                        $method['description'] = isset($localization['description']) ? strip_tags(html_entity_decode($localization['description'], ENT_QUOTES, 'UTF-8'), '<a><b><ul><li><br><img>') : '';
                        $method['title'] = (!empty($localization['title'])) ? $localization['title'] : $method['title'];
                    }

                    if (!isset($payment_setting['status'])) {
                        continue;
                    }

                    if ($payment_setting['display'] !== 'all') {
                        if ($payment_setting['display'] === 'guests' && $this->customer->isLogged()) {
                            continue;
                        }

                        if ($payment_setting['display'] === 'registered' && !$this->customer->isLogged()) {
                            continue;
                        }
                    }
    
                    if ($shipping_method && isset($payment_setting['disable_in_shipping_methods']) && in_array($shipping_method_name, $payment_setting['disable_in_shipping_methods'])) {
                        $method['hidden'] = true;
                    }

                    if ($recurring) {
                        if (property_exists($this->{'model_extension_payment_' . $result['code']}, 'recurringPayments') && $this->{'model_extension_payment_' . $result['code']}->recurringPayments()) {
                            $method_data[$result['code']] = $method;
                        }
                    } else {
                        $method_data[$result['code']] = $method;
                    }
                } else {
                    if ($method) {
                        if ($recurring) {
                            if (property_exists($this->{'model_extension_payment_' . $result['code']}, 'recurringPayments') && $this->{'model_extension_payment_' . $result['code']}->recurringPayments()) {
                                $method_data[$result['code']] = $method;
                            }
                        } else {
                            $method_data[$result['code']] = $method;
                        }
                    }
                }
            }
        }

        $sort_order = [];

        foreach ($method_data as $key => $value) {
            $sort_order[$key] = isset($value['sort_order']) ? $value['sort_order'] : 0;
        }

        array_multisort($sort_order, SORT_ASC, $method_data);

        $this->session->data['payment_methods'] = $method_data;

        if (empty($this->session->data['payment_methods'])) {
            $data['error_warning'] = sprintf($this->language->get('error_no_payment'), $this->url->link('information/contact'));
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['payment_methods'])) {
            $data['payment_methods'] = $this->session->data['payment_methods'];
        } else {
            $data['payment_methods'] = [];
        }

        if (isset($this->request->post['payment_method'])) {
            $data['payment_code'] = $this->request->post['payment_method'];
        } else {
            $data['payment_code'] = '';
        }

        if (isset($this->session->data['comment'])) {
            $data['comment'] = $this->session->data['comment'];
        } else {
            $data['comment'] = '';
        }

        if ($render) {
            return $this->load->view('checkout/oct_smartcheckout/payment_methods', $data);
        }
    }

    private function paymentAddress($render = true, &$data = array()) {
        $this->load->language('octemplates/module/oct_smartcheckout');

        $oct_smart_checkout_data = $this->config->get('oct_smart_checkout_data');

        if (isset($this->session->data['payment_address']['address_id'])) {
            $data['payment_address_id'] = $this->session->data['payment_address']['address_id'];
        } else {
            $data['payment_address_id'] = $this->customer->getAddressId();
        }

        $this->load->model('account/address');

        $data['addresses'] = $this->model_account_address->getAddresses();

        $this->load->model('account/customer_group');

        if (isset($this->session->data['payment_address']['country_id'])) {
            $data['country_id'] = $this->session->data['payment_address']['country_id'];
        } else {
            $data['country_id'] = (int) $this->config->get('config_country_id');
        }

        if (isset($this->session->data['payment_address']['zone_id'])) {
            $data['zone_id'] = $this->session->data['payment_address']['zone_id'];
        } else {
            $data['zone_id'] = '';
        }

        $this->load->model('localisation/country');

        $data['countries'] = $this->model_localisation_country->getCountries();

        // Custom Fields
        $this->load->model('account/custom_field');
        $this->load->model('account/customer');

        $data['custom_fields'] = $this->model_account_custom_field->getCustomFields($this->config->get('config_customer_group_id'));

        $customer_info = $this->model_account_customer->getCustomer($this->customer->getId());

        if (isset($customer_info) && isset($customer_info['custom_field'])) {
            $data['account_custom_field'] = json_decode($customer_info['custom_field'], true);
		} else {
			$data['account_custom_field'] = [];
		}

        if (isset($this->session->data['payment_address']['custom_field'])) {
            $data['payment_address_custom_field'] = $this->session->data['payment_address']['custom_field'];
        } else {
            $data['payment_address_custom_field'] = [];
        }

        if ($render !== false) {
            $this->response->setOutput($this->load->view('checkout/checkout/payment_address', $data));
        }
    }

    private function fillFieldsFromSession($fields) {
        if (isset($this->session->data['oct_form_data'])) {
            foreach ($fields as $field_key => &$field) {
                if (isset($this->session->data['oct_form_data'][$field_key]) && !empty($this->session->data['oct_form_data'][$field_key])) {
                    $field['value'] = $this->session->data['oct_form_data'][$field_key];
                }
            }
            unset($field);
        }
        
        return $fields;
    }

    private function getCustomerFields(&$data = array()) {

        $oct_smart_checkout_data = $this->config->get('oct_smart_checkout_data');
        $data['config_checkout_guest'] = $this->config->get('config_checkout_guest') ?? 0;
        $this->load->language('octemplates/module/oct_smartcheckout');
        $this->load->model('account/address');

        $type = (isset($this->request->post['shipping_method']) && !empty($this->request->post['shipping_method'])) ? $this->cleanAndReplace($this->request->post['shipping_method']) : 'default';

        if(!isset($oct_smart_checkout_data['customer']['fields'][$type])) {
            $type = 'default';
        }

        $data['registration_enabled'] = $oct_smart_checkout_data['registration'] ?? '';
        $data['authorization_enabled'] = $oct_smart_checkout_data['authorization'] ?? '';
        $data['register_check'] = $this->request->post['register'] ?? '';
        $data['language_id'] = (int) $this->config->get('config_language_id');

        $data['fields'] = $this->getFieldList('customer', $type);

        if (isset($this->request->post['register']) && !isset($data['fields']['email'])) {
            $data['fields']['email'] = [
                'required' => 1,
                'display' => 'all',
                'sort_order' => 1,
                'status' => 'on',
                'custom_field' => 0,
                'custom_field_id' => 0,
                'type' => 'text',
                'localization' => '',
                'merge_field' => '',
            ];
        }

        $data['password'] = $this->request->post['password'] ?? '';

        if ($this->customer->isLogged()) {
            $this->load->model('localisation/country');

            $data['countries'] = $this->model_localisation_country->getCountries();
            $data['default_country_id'] = !empty($this->session->data['oct_form_data']['country_id']) ? $this->session->data['oct_form_data']['country_id'] : (int) $this->config->get('config_country_id');
            $data['default_zone_id'] = !empty($this->session->data['oct_form_data']['zone_id']) ? $this->session->data['oct_form_data']['zone_id'] : 0;
            $data['zones'] = $this->zone($data['default_country_id']);
            $data['language_id'] = (int) $this->config->get('config_language_id');
            $data['new_address_check'] = $this->request->post['new_address'] ?? '';

            $data['customer']['payment_address_id'] = $this->customer->getAddressId();
            $data['user_logged'] = true;
            $data['customer']['address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
            $data['customer']['total_addresses'] = $this->model_account_address->getTotalAddresses($this->customer->getId());
            $data['selected_address'] = isset($this->request->post['address_id']) ? $this->request->post['address_id'] : false;


            if (isset($data['fields']['firstname'])) {
                $data['fields']['firstname']['value'] = $this->customer->getFirstName();
            }

            if (isset($data['fields']['lastname'])) {
                $data['fields']['lastname']['value'] = $this->customer->getLastName();
            }

            if (isset($data['fields']['email'])) {
                $data['fields']['email']['value'] = $this->customer->getEmail();
            }

            if (isset($data['fields']['telephone'])) {
                $data['fields']['telephone']['value'] = $this->customer->getTelephone();
            }
            
            if ($data['customer']['total_addresses'] > 0) {
                $data['customer_addresses'] = $this->model_account_address->getAddresses();
            }

            $type = (isset($this->request->post['shipping_method']) && !empty($this->request->post['shipping_method'])) ? $this->cleanAndReplace($this->request->post['shipping_method']) : 'default';

            if(!isset($oct_smart_checkout_data['delivery']['fields'][$type])) {
                $type = 'default';
            }

            $data['delivery_fields'] = $this->getFieldList('delivery', $type);
            $data['customer_new_address_fields'] = $this->fillFieldsFromSession($data['delivery_fields']);
        }

        $data['fields'] = $this->fillFieldsFromSession($data['fields']);

        return $this->load->view('checkout/oct_smartcheckout/customer_fields', $data);
    }

    private function getCountryZoneFields(&$data = array()) {
        $this->load->language('octemplates/module/oct_smartcheckout');
        $this->load->model('localisation/country');
        $type = (isset($this->request->post['shipping_method']) && !empty($this->request->post['shipping_method'])) ? $this->cleanAndReplace($this->request->post['shipping_method']) : 'default';
        
        $data['fields'] = $this->getFieldList('delivery', $type);    
        $data['countries'] = $this->model_localisation_country->getCountries();
        $data['default_country_id'] = !empty($this->session->data['oct_form_data']['country_id']) ? $this->session->data['oct_form_data']['country_id'] : (int) $this->config->get('config_country_id');
        $data['default_zone_id'] = !empty($this->session->data['oct_form_data']['zone_id']) ? $this->session->data['oct_form_data']['zone_id'] : 0;
        $data['fields'] = $this->fillFieldsFromSession($data['fields']);
        $data['zones'] = $this->zone($data['default_country_id']);
        $data['language_id'] = (int) $this->config->get('config_language_id');

        if (!$this->hasEnabled($data['fields']) || !isset($data['fields']['country_id']) && !isset($data['fields']['zone_id'])) {
            return '';
        }

        return $this->load->view('checkout/oct_smartcheckout/countryzone_fields', $data);
    }

    private function getCustomerAddressFields(&$data = array()) {

        $oct_smart_checkout_data = $this->config->get('oct_smart_checkout_data');
        $this->load->language('octemplates/module/oct_smartcheckout');

        $type = (isset($this->request->post['shipping_method']) && !empty($this->request->post['shipping_method'])) ? $this->cleanAndReplace($this->request->post['shipping_method']) : 'default';

        if(!isset($oct_smart_checkout_data['delivery']['fields'][$type])) {
            $type = 'default';
        }

        $data['fields'] = $this->getFieldList('delivery', $type);
        $data['fields'] = $this->fillFieldsFromSession($data['fields']);
        $data['language_id'] = (int) $this->config->get('config_language_id');

        if (!$this->hasEnabled($data['fields'])) {
            return '';
        }

        return $this->load->view('checkout/oct_smartcheckout/customer_address_fields', $data);
    }

    private function cleanAndReplace($string) {
        $string = str_replace('.', '-', $string);
        $string = strip_tags($string);
        $string = stripslashes($string);
        return $string;
    }

    private function hasEnabled($fields) {
        foreach ($fields as $field) {
            if (isset($field['status']) && $field['status'] == "on") {
                return true;
            }
        }
    
        return false;
    }

    private function getPaymentFields($render) {
        $oct_smart_checkout_data = $this->config->get('oct_smart_checkout_data');
        $this->load->language('octemplates/module/oct_smartcheckout');

        $type = (isset($this->request->post['payment_method']) && !empty($this->request->post['payment_method'])) ? $this->request->post['payment_method'] : '';

        if(!isset($oct_smart_checkout_data['payment']['methods'][$type])) {
            $type = 'default';
        }

        $data['language_id'] = (int) $this->config->get('config_language_id');
        $data['fields'] = $this->getFieldList('payment', $type);
        $data['fields'] = $this->fillFieldsFromSession($data['fields']);

        if (!$data['fields']) {
            return '';
        }
        
        if ($render) {
            return $this->load->view('checkout/oct_smartcheckout/payment_fields', $data);
        } else {
            return $data;
        }
    }

    public function shoppingCart($render = false, &$data = array()) {

        $this->shippingMethodValidate();

        $oct_smart_checkout_data         = $this->config->get('oct_smart_checkout_data');
        $data['oct_smart_checkout_data'] = $oct_smart_checkout_data;
        $oct_shipping_settings = isset($oct_smart_checkout_data['delivery']['methods']) ? $oct_smart_checkout_data['delivery']['methods'] : [];

        $this->load->language('octemplates/module/oct_smartcheckout');

        if (!isset($this->session->data['vouchers'])) {
            $this->session->data['vouchers'] = [];
        }

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = array(
            'href' => $this->url->link('common/home'),
            'text' => $this->language->get('text_home'),
            'separator' => false
        );

        $data['breadcrumbs'][] = array(
            'href' => $this->url->link('checkout/cart'),
            'text' => $this->language->get('heading_title'),
            'separator' => $this->language->get('text_separator')
        );

        $points = $this->customer->getRewardPoints();

        $points_total = 0;

        foreach ($this->cart->getProducts() as $product) {
            if ($product['points']) {
                $points_total += $product['points'];
            }
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } elseif (!$this->cart->hasStock() && (!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning'))) {
            $data['error_warning'] = $this->language->get('error_stock');
        } else {
            $data['error_warning'] = '';
        }

        if ($this->config->get('config_customer_price') && !$this->customer->isLogged()) {
            $data['attention'] = sprintf($this->language->get('text_login'), $this->url->link('account/register'));
        } else {
            $data['attention'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        if ($this->config->get('config_cart_weight') && (isset($oct_smart_checkout_data['cart_weight']) && $oct_smart_checkout_data['cart_weight'] == "on")) {
            $data['weight'] = $this->weight->format($this->cart->getWeight(), $this->config->get('config_weight_class_id'), $this->language->get('decimal_point'), $this->language->get('thousand_point'));
        } else {
            $data['weight'] = '';
        }

        // Shopping Cart
        if (isset($this->request->request['remove'])) {
            $this->cart->remove($this->request->request['remove']);
            unset($this->session->data['vouchers'][$this->request->request['remove']]);
        }

        if (isset($this->request->request['update'])) {
            $this->cart->update($this->request->request['update'], $this->request->request['quantity']);
        }

        if (isset($this->request->request['add'])) {
            $this->cart->add($this->request->request['add'], $this->request->request['quantity']);
        }

        $this->load->model('tool/image');
        $this->load->model('tool/upload');

        $data['products'] = [];

        $products = $this->cart->getProducts();

        foreach ($products as $product) {
            $product_total = 0;

            foreach ($products as $product_2) {
                if ($product_2['product_id'] == $product['product_id']) {
                    $product_total += $product_2['quantity'];
                }
            }

            if ($product['minimum'] > $product_total) {
                $data['error_warning'] = sprintf($this->language->get('error_minimum'), $product['name'], $product['minimum']);
            }

            if ($product['image']) {
                $image = $this->model_tool_image->resize($product['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_height'));
            } else {
                $image = '';
            }

            $option_data = [];

            $this->load->model('catalog/product');

            $options_arr = [];

            foreach ($product['option'] as $value_opt) {
                array_push($options_arr, $value_opt['product_option_value_id']);
            }

        

            foreach ($product['option'] as $option) {
                if ($option['type'] != 'file') {
                    $value = $option['value'];
                } else {
                    $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

                    if ($upload_info) {
                        $value = $upload_info['name'];
                    } else {
                        $value = '';
                    }
                }

                $option_data[] = array(
                    'name' => $option['name'],
                    'sku' => (isset($option['sku'])) ? $option['sku'] : '',
                    'model' => (isset($option['model'])) ? $option['model'] : '',
                    'oct_quantity_value' => (isset($option['oct_quantity_value'])) ? $option['oct_quantity_value'] : '',
                    'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
                );
            }

            // Display prices
            if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
                $price = $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
            } else {
                $price = false;
            }

            // Display prices
            if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
                $total = $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')) * $product['quantity'], $this->session->data['currency']);
            } else {
                $total = false;
            }

            $recurring = '';

            if ($product['recurring']) {
                $frequencies = array(
                    'day' => $this->language->get('text_day'),
                    'week' => $this->language->get('text_week'),
                    'semi_month' => $this->language->get('text_semi_month'),
                    'month' => $this->language->get('text_month'),
                    'year' => $this->language->get('text_year')
                );

                if ($product['recurring']['trial']) {
                    $recurring = sprintf($this->language->get('text_trial_description'), $this->currency->format($this->tax->calculate($product['recurring']['trial_price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['trial_cycle'], $frequencies[$product['recurring']['trial_frequency']], $product['recurring']['trial_duration']) . ' ';
                }

                if ($product['recurring']['duration']) {
                    $recurring .= sprintf($this->language->get('text_payment_description'), $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
                } else {
                    $recurring .= sprintf($this->language->get('text_payment_cancel'), $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
                }
            }

            $data['products'][] = array(
                'key' => $product['cart_id'],
                'product_id' => $product['product_id'],
                'thumb' => $image,
                'name' => $product['name'],
                'model' => $product['model'],
                'option' => $option_data,
                'recurring' => $recurring,
                'quantity' => $product['quantity'],
                'stock' => $product['stock'] ? true : !(!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning')),
                'reward' => ($product['reward'] ? sprintf($this->language->get('text_points'), $product['reward']) : ''),
                'price' => $price,
                'total' => $total,
                'href' => $this->url->link('product/product', 'product_id=' . $product['product_id'])
            );
        }

        $data['products_recurring'] = [];

        // Gift Voucher
        $data['vouchers'] = [];

        if (!empty($this->session->data['vouchers'])) {
            foreach ($this->session->data['vouchers'] as $key => $voucher) {
                $data['vouchers'][] = array(
                    'key' => $key,
                    'description' => $voucher['description'],
                    'amount' => $this->currency->format($voucher['amount'], $this->session->data['currency']),
                    'remove' => $this->url->link('checkout/cart', 'remove=' . $key)
                );
            }
        }

        if (isset($this->request->post['coupon'])) {
            $data['coupon'] = $this->request->post['coupon'];
        } elseif (isset($this->session->data['coupon'])) {
            $data['coupon'] = $this->session->data['coupon'];
        } else {
            $data['coupon'] = '';
        }

        $data['voucher_status'] = $this->config->get('voucher_status');

        if (isset($this->request->post['voucher'])) {
            $data['voucher'] = $this->request->post['voucher'];
        } elseif (isset($this->session->data['voucher'])) {
            $data['voucher'] = $this->session->data['voucher'];
        } else {
            $data['voucher'] = '';
        }

        $data['reward_status'] = ($points && $points_total && $this->config->get('reward_status'));
        $data['has_reward'] = ($points_total > 0) ? $points_total : false;

        if (isset($this->request->post['reward'])) {
            $data['reward'] = $this->request->post['reward'];
        } elseif (isset($this->session->data['reward'])) {
            $data['reward'] = $this->session->data['reward'];
        } else {
            $data['reward'] = '';
        }

        $this->load->model('setting/extension');

        $data['modules'] = [];

        $files = glob(DIR_APPLICATION . '/controller/extension/total/*.php');

        if ($files) {
            foreach ($files as $file) {
                $result = $this->load->controller('extension/total/' . basename($file, '.php'));
                if (basename($file, '.php') != 'shipping') {
                    if ($result) {
                        $data['modules'][] = $result;
                    }
                }
            }
        }

        $data['shipping_status'] = $this->config->get('shipping_status') && $this->config->get('shipping_estimator') && $this->cart->hasShipping();

        if (isset($this->request->post['country_id']) && $this->request->post['country_id']) {
            $data['country_id'] = $this->request->post['country_id'];
        } elseif (isset($this->session->data['shipping_country_id']) && $this->session->data['shipping_country_id']) {
            $data['country_id'] = $this->session->data['shipping_country_id'];
        } else {
            $data['country_id'] = (int) $this->config->get('config_country_id');
        }

        $this->load->model('localisation/country');

        $data['countries'] = $this->model_localisation_country->getCountries();

        if (isset($this->request->post['zone_id'])) {
            $data['zone_id'] = $this->request->post['zone_id'];
        } elseif (isset($this->session->data['shipping_zone_id'])) {
            $data['zone_id'] = $this->session->data['shipping_zone_id'];
        } else {
            $data['zone_id'] = '';
        }

        if (isset($this->request->post['postcode'])) {
            $data['postcode'] = $this->request->post['postcode'];
        } elseif (isset($this->session->data['shipping_postcode'])) {
            $data['postcode'] = $this->session->data['shipping_postcode'];
        } else {
            $data['postcode'] = '';
        }

        if (isset($this->session->data['shipping_method'])) {
            $data['shipping_method'] = $this->session->data['shipping_method']['code'];
        } else {
            $data['shipping_method'] = '';
        }

        // Totals
        $this->load->model('setting/extension');

        $totals = array();
        $taxes = $this->cart->getTaxes();
        $total = 0;
        
        // Because __call can not keep var references so we put them into an array. 			
        $total_data = array(
            'totals' => &$totals,
            'taxes'  => &$taxes,
            'total'  => &$total
        );
        
        // Display prices
        if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
            $sort_order = array();

            $results = $this->model_setting_extension->getExtensions('total');

            foreach ($results as $key => $value) {
                $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
            }

            array_multisort($sort_order, SORT_ASC, $results);

            foreach ($results as $result) {
                if ($this->config->get('total_' . $result['code'] . '_status')) {
                    $this->load->model('extension/total/' . $result['code']);
                    
                    // We have to put the totals in an array so that they pass by reference.
                    $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
                }
            }

            $sort_order = array();

            foreach ($totals as $key => $value) {
                $sort_order[$key] = $value['sort_order'];
            }

            array_multisort($sort_order, SORT_ASC, $totals);
        }

        $oldTotals = $totals;

        $data['totals'] = [];

        $shipping_method = isset($this->request->post['shipping_method']) && !empty($this->request->post['shipping_method'])
            ? $this->cleanAndReplace($this->request->post['shipping_method'])
            : '';

        $freeShippingFrom    = isset($oct_smart_checkout_data['free_shipping_from'])    ? (float)$oct_smart_checkout_data['free_shipping_from']    : 0;
        $minimumOrderAmount  = isset($oct_smart_checkout_data['minimum_order_amount'])  ? (float)$oct_smart_checkout_data['minimum_order_amount']  : 0;

        if (isset($oct_shipping_settings[$shipping_method]['free_shipping_from']) && $oct_shipping_settings[$shipping_method]['free_shipping_from'] > 0) {
            $freeShippingFrom = (float)$oct_shipping_settings[$shipping_method]['free_shipping_from'];
        }

        if ($freeShippingFrom || $minimumOrderAmount) {

            $codes         = array_column($totals, 'code');
            $shipping_key  = array_search('shipping', $codes);
            $total_key     = array_search('total',    $codes);

            $totalForFreeShipping = 0;
            foreach ($totals as $t) {
                if ($t['code'] !== 'shipping' && $t['code'] !== 'total') {
                    $totalForFreeShipping += $t['value'];
                }
            }

            foreach ($totals as $t) {
                if ($t['code'] === 'sub_total' && $minimumOrderAmount && $minimumOrderAmount > $t['value']) {
                    $data['error_warning'] = sprintf(
                        $this->language->get('error_minimum_sum'),
                        $this->currency->format($minimumOrderAmount, $this->session->data['currency'])
                    );
                }

                $data['totals'][] = [
                    'title' => $t['title'],
                    'code'  => $t['code'],
                    'value' => max(0, $t['value']),
                    'text'  => $this->currency->format($t['value'], $this->session->data['currency'])
                ];
            }

            if ($totalForFreeShipping >= $freeShippingFrom && $shipping_key !== false) {
                $original_shipping_value            = $totals[$shipping_key]['value'];
                $totals[$shipping_key]['value']     = 0;
                $data['totals'][$shipping_key]      = [
                    'title' => $totals[$shipping_key]['title'],
                    'code'  => 'shipping',
                    'value' => 0,
                    'text'  => $this->currency->format(0, $this->session->data['currency'])
                ];

                if ($total_key !== false) {
                    $totals[$total_key]['value']            -= $original_shipping_value;
                    $data['totals'][$total_key]['value']     = $totals[$total_key]['value'];
                    $data['totals'][$total_key]['text']      = $this->currency->format($totals[$total_key]['value'], $this->session->data['currency']);
                }
            }

            if ($freeShippingFrom) {
                $data['total_value']      = $totalForFreeShipping;
                $shippingFree             = $freeShippingFrom - $data['total_value'];
                $data['total_percentage'] = ($data['total_value'] / $freeShippingFrom) * 100;

                $data['free_shipping_from_text'] = $shippingFree > 0
                    ? sprintf($this->language->get('free_shipping_from'), $this->currency->format($shippingFree, $this->session->data['currency']))
                    : $this->language->get('free_shipping_get');
            }
        } else {
            foreach ($totals as $t) {
                $data['totals'][] = [
                    'title' => $t['title'],
                    'code'  => $t['code'],
                    'value' => max(0, $t['value']),
                    'text'  => $this->currency->format($t['value'], $this->session->data['currency'])
                ];
            }
        }

        $data['continue'] = $this->url->link('common/home');
        $data['action']   = $this->url->link('checkout/cart');
        $data['checkout'] = $this->url->link('checkout/oct_smartcheckout', '', 'SSL');

        $data['checkout_buttons'] = [];

        if ($render) {
            return $this->load->view('checkout/oct_smartcheckout/cart', $data);
        }
    }

    private function PaymentMethodValidate() {
        $json = [];

        $this->load->language('octemplates/module/oct_smartcheckout');

        // Validate if payment address has been set.
        $this->load->model('account/address');

        if ($this->customer->isLogged() && isset($this->session->data['payment_address_id'])) {
            $payment_address = $this->model_account_address->getAddress($this->session->data['payment_address_id']);
        } elseif (isset($this->session->data['guest']['payment'])) {
            $payment_address = $this->session->data['guest']['payment'];
        } else {
            $payment_address = $this->model_account_address->getAddress(0);
        }

        // Validate minimum quantity requirments.
        $products = $this->cart->getProducts();

        foreach ($products as $product) {
            $product_total = 0;

            foreach ($products as $product_2) {
                if ($product_2['product_id'] == $product['product_id']) {
                    $product_total += $product_2['quantity'];
                }
            }

            if ($product['minimum'] > $product_total) {
                $json['redirect'] = $this->url->link('checkout/oct_smartcheckout');

                break;
            }
        }

        if (!$json) {
            if (!isset($this->request->post['payment_method'])) {
                $json['error']['warning'] = $this->language->get('error_payment');
            } elseif (!isset($this->session->data['payment_methods'][$this->request->post['payment_method']])) {
                $json['error']['warning'] = $this->language->get('error_payment');
            }

            if (!$json) {
                $this->session->data['payment_method'] = $this->session->data['payment_methods'][$this->request->post['payment_method']];
                $this->session->data['comment']        = (isset($this->request->post['comment'])) ? strip_tags($this->request->post['comment']) : '';
            }
        }

        return $json;
    }

    private function getCoutryZoneName($country_id, $zone_id) {
        $this->load->model('localisation/country');
        $this->load->model('localisation/zone');

        $country_info = $this->model_localisation_country->getCountry($country_id);
        $zone_info = $this->model_localisation_zone->getZone($zone_id);
        
        return [
            'country' => isset($country_info['name']) ? $country_info['name'] : '',
            'zone'    => isset($zone_info['name']) ? $zone_info['name'] : ''
        ];
    }

    private function confirmCheckout($render = false, $data = array()) {

        $oct_smart_checkout_data = $this->config->get('oct_smart_checkout_data');
        $oct_shipping_settings = isset($oct_smart_checkout_data['delivery']['methods']) ? $oct_smart_checkout_data['delivery']['methods'] : [];

        $this->load->language('octemplates/module/oct_smartcheckout');

        $redirect         = '';
        $data['payment']  = '';
        $data['products'] = '';

        if ($this->cart->hasShipping()) {
            // Validate if shipping address has been set.
            if (!isset($this->session->data['shipping_address'])) {
                $redirect = $this->url->link('checkout/oct_smartcheckout', '', 'SSL');
            }

            // Validate if shipping method has been set.
            if (!isset($this->session->data['shipping_method'])) {
                $redirect = $this->url->link('checkout/oct_smartcheckout', '', 'SSL');
            }
        } else {
            unset($this->session->data['shipping_address']);
            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
        }

        // Validate if payment address has been set.
        if (!isset($this->session->data['payment_address'])) {
            $redirect = $this->url->link('checkout/oct_smartcheckout', '', 'SSL');
        }

        // Validate if payment method has been set.
        if (!isset($this->session->data['payment_method'])) {
            $redirect = $this->url->link('checkout/oct_smartcheckout', '', 'SSL');
        }

        // Validate cart has products and has stock.
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            $redirect = $this->url->link('checkout/oct_smartcheckout');
        }

        // Validate minimum quantity requirements.
        $products = $this->cart->getProducts();

        foreach ($products as $product) {
            $product_total = 0;

            foreach ($products as $product_2) {
                if ($product_2['product_id'] == $product['product_id']) {
                    $product_total += $product_2['quantity'];
                }
            }

            if ($product['minimum'] > $product_total) {
                $redirect = $this->url->link('checkout/oct_smartcheckout');
                break;
            }
        }

        $order_data = [];

        $totals = [];
        $taxes  = $this->cart->getTaxes();
        $total  = 0;

        // Because __call can not keep var references so we put them into an array.
        $total_data = array(
            'totals' => &$totals,
            'taxes' => &$taxes,
            'total' => &$total
        );

        $this->load->model('setting/extension');

        $sort_order = [];

        $results = $this->model_setting_extension->getExtensions('total');

        foreach ($results as $key => $value) {
            $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
        }

        array_multisort($sort_order, SORT_ASC, $results);

        foreach ($results as $result) {
            if ($this->config->get('total_' . $result['code'] . '_status')) {
                $this->load->model('extension/total/' . $result['code']);

                // We have to put the totals in an array so that they pass by reference.
                $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
            }
        }

        $sort_order = [];

        foreach ($totals as $k => $v) {
            $sort_order[$k] = $v['sort_order'];
        }
        array_multisort($sort_order, SORT_ASC, $totals);

        $data['old_total_value'] = $total_data['total'];

        $freeShippingFrom = isset($oct_smart_checkout_data['free_shipping_from']) ? (float)$oct_smart_checkout_data['free_shipping_from'] : 0;
        $shipping_method  = isset($this->request->post['shipping_method']) && !empty($this->request->post['shipping_method'])
            ? $this->cleanAndReplace($this->request->post['shipping_method'])
            : '';

        if (isset($oct_shipping_settings[$shipping_method]['free_shipping_from']) && $oct_shipping_settings[$shipping_method]['free_shipping_from'] > 0) {
            $freeShippingFrom = (float)$oct_shipping_settings[$shipping_method]['free_shipping_from'];
        }

        $codes        = array_column($totals, 'code');
        $shipping_key = array_search('shipping', $codes);
        $total_key    = array_search('total',    $codes);
        $reward_key   = array_search('reward',   $codes);
        
        $sumForFree = 0;
        foreach ($totals as $t) {
            if ($t['code'] !== 'shipping' && $t['code'] !== 'total') {
                $sumForFree += $t['value'];
            }
        }

        if ($freeShippingFrom && $sumForFree >= $freeShippingFrom && $shipping_key !== false && $total_key !== false) {
            $orig_ship = $totals[$shipping_key]['value'];
            $totals[$shipping_key]['value'] = 0;
            $totals[$total_key]['value']   -= $orig_ship;
            $total_data['total']            = $totals[$total_key]['value'];
        }

        if ($reward_key !== false && $total_key !== false) {
            $total_data['total'] = $totals[$total_key]['value'];
        }

        $order_data['totals'] = $totals;
        
        $order_data['invoice_prefix'] = $this->config->get('config_invoice_prefix');
        $order_data['store_id'] = $this->config->get('config_store_id');
        $order_data['store_name'] = $this->config->get('config_name');

        if ($order_data['store_id']) {
            $order_data['store_url'] = $this->config->get('config_url');
        } else {
            $order_data['store_url'] = HTTP_SERVER;
        }

        if (isset($this->request->post) && !empty($this->request->post)) {
            $oct_smart_checkout_data                         = $this->config->get('oct_smart_checkout_data');

            if ($this->customer->isLogged()) {
                $this->load->model('account/customer');
                $this->load->model('account/address');

                $customer_info = $this->model_account_customer->getCustomer($this->customer->getId());

                $order_data['customer_id']       = $this->customer->getId();
                $order_data['customer_group_id'] = $customer_info['customer_group_id'];
                $order_data['firstname']         = isset($this->request->post['firstname']) ? $this->request->post['firstname'] : $customer_info['firstname'];
                $order_data['lastname']          = isset($this->request->post['lastname']) ? $this->request->post['lastname'] : $customer_info['lastname'];
                $order_data['email']             = $customer_info['email'];
                $order_data['telephone']         = isset($this->request->post['telephone']) ? $this->request->post['telephone'] : $customer_info['telephone'];
                $order_data['fax']               = $customer_info['fax'];
                $order_data['custom_field']      = isset($this->session->data['customer']['custom_field']) ? $this->session->data['customer']['custom_field'] : '';
                $payment_address_id = isset($customer_info['address_id']) ? $customer_info['address_id'] : 0;

                if (isset($this->request->post['address_id']) && $this->request->post['address_id'] != '' && !isset($this->request->post['new_address'])) {
                    $payment_address_id = (int) $this->request->post['address_id'];
                } 

                if (isset($this->session->data['shipping_address_id']) && !isset($this->request->post['default'])) {
                    $payment_address_id = $this->session->data['shipping_address_id'];
                } 

                $payment_address = $this->model_account_address->getAddress($payment_address_id);
                $shipping_address = $this->model_account_address->getAddress($payment_address_id);

            } elseif (isset($this->session->data['guest'])) {
                $order_data['customer_id']       = 0;
                $order_data['customer_group_id'] = isset($this->session->data['guest']['customer_group_id']) ? $this->session->data['guest']['customer_group_id'] : $this->config->get('config_customer_group_id');
                $order_data['firstname']    = isset($this->session->data['guest']['firstname']) ? $this->session->data['guest']['firstname'] : '';
                $order_data['lastname']     = isset($this->session->data['guest']['lastname']) ? $this->session->data['guest']['lastname'] : '';
                $order_data['email']        = (isset($this->session->data['guest']['email']) && !empty($this->session->data['guest']['email'])) ? $this->session->data['guest']['email'] : $oct_smart_checkout_data['when_empty_email'];
                $order_data['telephone']    = isset($this->session->data['guest']['telephone']) ? $this->session->data['guest']['telephone'] : '';
                $order_data['fax']          = isset($this->session->data['guest']['fax']) ? $this->session->data['guest']['fax'] : '';
                $order_data['custom_field'] = isset($this->session->data['guest']['custom_field']) ? $this->session->data['guest']['custom_field'] : '';

                if (isset($this->request->post['country_id']) && $this->request->post['country_id'] != '') {
                    $payment_country = $this->getCoutryZoneName($this->request->post['country_id'], $this->request->post['zone_id']);
                } 

                $order_data['payment_firstname']      = (isset($this->request->post['firstname'])) ? $this->request->post['firstname'] : '';
                $order_data['payment_lastname']       = (isset($this->request->post['lastname'])) ? $this->request->post['lastname'] : '';
                $order_data['payment_company']        = (isset($this->request->post['company'])) ? $this->request->post['company'] : '';
                $order_data['payment_address_1']      = (isset($this->request->post['address_1'])) ? $this->request->post['address_1'] : '';
                $order_data['payment_address_2']      = (isset($this->request->post['address_2'])) ? $this->request->post['address_2'] : '';
                $order_data['payment_city']           = (isset($this->request->post['city'])) ? $this->request->post['city'] : '';
                $order_data['payment_postcode']       = (isset($this->request->post['postcode'])) ? $this->request->post['postcode'] : '';
                $order_data['payment_zone']           = (isset($payment_country['zone'])) ? $payment_country['zone'] : '';
                $order_data['payment_zone_id']        = (isset($this->request->post['zone_id'])) ? $this->request->post['zone_id'] : '';
                $order_data['payment_country']        = (isset($payment_country['country'])) ? $payment_country['country'] : '';
                $order_data['payment_country_id']     = (isset($this->request->post['country_id'])) ? $this->request->post['country_id'] : '';
                $order_data['payment_address_format'] = '';
                $order_data['payment_custom_field']   = [];
                $order_data['payment_method']         = '';
                $order_data['payment_code']           = '';
                $order_data['payment_custom_field']   = '';
            }

            if ((isset($payment_address) && is_array($payment_address)) || isset($this->session->data['payment_address_id'])) {

                $order_data['payment_firstname']      = isset($payment_address['firstname']) ? $payment_address['firstname'] : '';
                $order_data['payment_lastname']       = isset($payment_address['lastname']) ? $payment_address['lastname'] : '';
                $order_data['payment_company']        = isset($payment_address['company']) ? $payment_address['company'] : '';
                $order_data['payment_company_id']     = isset($payment_address['company_id']) ? $payment_address['company_id'] : '';
                $order_data['payment_tax_id']         = isset($payment_address['tax_id']) ? $payment_address['tax_id'] : '';
                $order_data['payment_address_1']      = isset($payment_address['address_1']) ? $payment_address['address_1'] : '';
                $order_data['payment_address_2']      = isset($payment_address['address_2']) ? $payment_address['address_2'] : '';
                $order_data['payment_city']           = isset($payment_address['city']) ? $payment_address['city'] : '';
                $order_data['payment_postcode']       = isset($payment_address['postcode']) ? $payment_address['postcode'] : '';
                $order_data['payment_zone']           = isset($payment_address['zone']) ? $payment_address['zone'] : '';
                $order_data['payment_zone_id']        = isset($payment_address['zone_id']) ? $payment_address['zone_id'] : '';
                $order_data['payment_country']        = isset($payment_address['country']) ? $payment_address['country'] : '';
                $order_data['payment_country_id']     = isset($payment_address['country_id']) ? $payment_address['country_id'] : '';
                $order_data['payment_address_format'] = isset($payment_address['address_format']) ? $payment_address['address_format'] : '';
                $order_data['payment_custom_field']   = (isset($payment_address['custom_field'])) ? $payment_address['custom_field'] : '';
            }

            if (isset($this->session->data['payment_method']['title'])) {
                $order_data['payment_method'] = $this->session->data['payment_method']['title'];
            } else {
                $order_data['payment_method'] = '';
            }

            if (isset($this->session->data['payment_method']['code'])) {
                $order_data['payment_code'] = $this->session->data['payment_method']['code'];
            } else {
                $order_data['payment_code'] = '';
            }

            if ($this->cart->hasShipping()) {

                if (!$this->customer->isLogged()) {
                    if (!isset($this->request->post['shipping_address'])) {

                        $this->session->data['shipping_address']['firstname']  = isset($this->request->post['firstname']) ? $this->request->post['firstname'] : '';
                        $this->session->data['shipping_address']['lastname']   = isset($this->request->post['lastname']) ? $this->request->post['lastname'] : '';
                        $this->session->data['shipping_address']['company']    = isset($this->request->post['company']) ? $this->request->post['company'] : '';
                        $this->session->data['shipping_address']['address_1']  = isset($this->request->post['address_1']) ? $this->request->post['address_1'] : '';
                        $this->session->data['shipping_address']['address_2']  = isset($this->request->post['address_2']) ? $this->request->post['address_2'] : '';
                        $this->session->data['shipping_address']['postcode']   = isset($this->request->post['postcode']) ? $this->request->post['postcode'] : '';
                        $this->session->data['shipping_address']['city']       = isset($this->request->post['city']) ? $this->request->post['city'] : '';
                        $this->session->data['shipping_address']['country_id'] = isset($this->request->post['country_id']) ? $this->request->post['country_id'] : '';
                        $this->session->data['shipping_address']['zone_id']    = isset($this->request->post['zone_id']) ? $this->request->post['zone_id'] : '';

                        $this->load->model('localisation/country');

                        $country_info = $this->model_localisation_country->getCountry(isset($this->request->post['country_id']) ? $this->request->post['country_id'] : 0);

                        if ($country_info) {
                            $this->session->data['shipping_address']['country']        = $country_info['name'];
                            $this->session->data['shipping_address']['iso_code_2']     = $country_info['iso_code_2'];
                            $this->session->data['shipping_address']['iso_code_3']     = $country_info['iso_code_3'];
                            $this->session->data['shipping_address']['address_format'] = $country_info['address_format'];
                        } else {
                            $this->session->data['shipping_address']['country']        = '';
                            $this->session->data['shipping_address']['iso_code_2']     = '';
                            $this->session->data['shipping_address']['iso_code_3']     = '';
                            $this->session->data['shipping_address']['address_format'] = '';
                        }

                        $this->load->model('localisation/zone');

                        $zone_info = $this->model_localisation_zone->getZone(isset($this->request->post['zone_id']) ? $this->request->post['zone_id'] : 0);

                        if ($zone_info) {
                            $this->session->data['shipping_address']['zone']      = $zone_info['name'];
                            $this->session->data['shipping_address']['zone_code'] = $zone_info['code'];
                        } else {
                            $this->session->data['shipping_address']['zone']      = '';
                            $this->session->data['shipping_address']['zone_code'] = '';
                        }

                        if (isset($this->session->data['shipping_address'])) {
                            $order_data['shipping_firstname']      = $this->session->data['shipping_address']['firstname'];
                            $order_data['shipping_lastname']       = $this->session->data['shipping_address']['lastname'];
                            $order_data['shipping_company']        = $this->session->data['shipping_address']['company'];
                            $order_data['shipping_address_1']      = $this->session->data['shipping_address']['address_1'];
                            $order_data['shipping_address_2']      = (isset($this->session->data['shipping_address']['address_2'])) ? $this->session->data['shipping_address']['address_2'] : '';
                            $order_data['shipping_city']           = $this->session->data['shipping_address']['city'];
                            $order_data['shipping_postcode']       = $this->session->data['shipping_address']['postcode'];
                            $order_data['shipping_zone']           = $this->session->data['shipping_address']['zone'];
                            $order_data['shipping_zone_id']        = $this->session->data['shipping_address']['zone_id'];
                            $order_data['shipping_country']        = $this->session->data['shipping_address']['country'];
                            $order_data['shipping_country_id']     = $this->session->data['shipping_address']['country_id'];
                            $order_data['shipping_address_format'] = $this->session->data['shipping_address']['address_format'];
                            $order_data['shipping_custom_field']   = (isset($this->session->data['shipping_address']['custom_field']) ? $this->session->data['shipping_address']['custom_field'] : array());
                            $order_data['shipping_method']         = isset($this->session->data['shipping_method']['title']) ? $this->session->data['shipping_method']['title'] : '';
                        }
                    } 
                } else {

                    if ((isset($shipping_address) && is_array($shipping_address)) || isset($this->session->data['shipping_address_id'])) {
                        $order_data['shipping_firstname']      = isset($shipping_address['firstname']) ? $shipping_address['firstname'] : '';
                        $order_data['shipping_lastname']       = isset($shipping_address['lastname']) ? $shipping_address['lastname'] : '';
                        $order_data['shipping_company']        = isset($shipping_address['company']) ? $shipping_address['company'] : '';
                        $order_data['shipping_company_id']     = isset($shipping_address['company_id']) ? $shipping_address['company_id'] : '';
                        $order_data['shipping_tax_id']         = isset($shipping_address['tax_id']) ? $shipping_address['tax_id'] : '';
                        $order_data['shipping_address_1']      = isset($shipping_address['address_1']) ? $shipping_address['address_1'] : '';
                        $order_data['shipping_address_2']      = isset($shipping_address['address_2']) ? $shipping_address['address_2'] : '';
                        $order_data['shipping_city']           = isset($shipping_address['city']) ? $shipping_address['city'] : '';
                        $order_data['shipping_postcode']       = isset($shipping_address['postcode']) ? $shipping_address['postcode'] : '';
                        $order_data['shipping_zone']           = isset($shipping_address['zone']) ? $shipping_address['zone'] : '';
                        $order_data['shipping_zone_id']        = isset($shipping_address['zone_id']) ? $shipping_address['zone_id'] : '';
                        $order_data['shipping_country']        = isset($shipping_address['country']) ? $shipping_address['country'] : '';
                        $order_data['shipping_country_id']     = isset($shipping_address['country_id']) ? $shipping_address['country_id'] : '';
                        $order_data['shipping_address_format'] = isset($shipping_address['address_format']) ? $shipping_address['address_format'] : '';
                        $order_data['shipping_custom_field']   = (isset($shipping_address['custom_field'])) ? $shipping_address['custom_field'] : '';
                    } else {

                        $order_data['shipping_firstname']      = (isset($this->request->post['firstname'])) ? $this->request->post['firstname'] : '';
                        $order_data['shipping_lastname']       = (isset($this->request->post['lastname'])) ? $this->request->post['lastname'] : '';
                        $order_data['shipping_company']        = (isset($this->request->post['company'])) ? $this->request->post['company'] : '';
                        $order_data['shipping_address_1']      = (isset($this->request->post['address_1'])) ? $this->request->post['address_1'] : '';
                        $order_data['shipping_address_2']      = (isset($this->request->post['address_2'])) ? $this->request->post['address_2'] : '';
                        $order_data['shipping_city']           = (isset($this->request->post['city'])) ? $this->request->post['city'] : '';
                        $order_data['shipping_postcode']       = (isset($this->request->post['postcode'])) ? $this->request->post['postcode'] : '';
                        $order_data['shipping_zone']           = (isset($this->request->post['zone'])) ? $this->request->post['zone'] : '';
                        $order_data['shipping_zone_id']        = (isset($this->request->post['zone_id'])) ? $this->request->post['zone_id'] : '';
                        $order_data['shipping_country']        = (isset($this->request->post['country'])) ? $this->request->post['country'] : '';
                        $order_data['shipping_country_id']     = (isset($this->request->post['country_id'])) ? $this->request->post['country_id'] : '';
                        $order_data['shipping_address_format'] = '';
                        $order_data['shipping_custom_field']   = [];
                        $order_data['shipping_method']         = '';
                        $order_data['shipping_code']           = '';
                        $order_data['shipping_custom_field']   = '';
                    }
                }

                if (isset($this->session->data['shipping_method']['title'])) {
                    $order_data['shipping_method'] = $this->session->data['shipping_method']['title'];
                } else {
                    $order_data['shipping_method'] = '';
                }

                if (isset($this->session->data['shipping_method']['code'])) {
                    $order_data['shipping_code'] = $this->session->data['shipping_method']['code'];
                } else {
                    $order_data['shipping_code'] = '';
                }

            } else {
                $order_data['shipping_firstname']      = '';
                $order_data['shipping_lastname']       = '';
                $order_data['shipping_company']        = '';
                $order_data['shipping_address_1']      = '';
                $order_data['shipping_address_2']      = '';
                $order_data['shipping_city']           = '';
                $order_data['shipping_postcode']       = '';
                $order_data['shipping_zone']           = '';
                $order_data['shipping_zone_id']        = '';
                $order_data['shipping_country']        = '';
                $order_data['shipping_country_id']     = '';
                $order_data['shipping_address_format'] = '';
                $order_data['shipping_custom_field']   = [];
                $order_data['shipping_method']         = '';
                $order_data['shipping_code']           = '';
            }

            $oct_smart_checkout_data = $this->config->get('oct_smart_checkout_data');
            $this->load->language('octemplates/module/oct_smartcheckout');

            $order_data['comment'] = isset($this->session->data['comment']) ? $this->session->data['comment'] : '';

            if (isset($oct_smart_checkout_data['no_call']) && $oct_smart_checkout_data['no_call'] && isset($this->request->post['no_call']) && $this->request->post['no_call'] == 1) {
                $order_data['comment'] .= "\n" . $this->language->get('text_no_call');
            }

            if (isset($oct_smart_checkout_data['telegram_viber_contact']) && $oct_smart_checkout_data['telegram_viber_contact'] && isset($this->request->post['telegram_viber_contact']) && $this->request->post['telegram_viber_contact'] == 1) {
                $order_data['comment'] .= "\n" . $this->language->get('text_telegram_viber_contact');
            }

            $type = (isset($this->request->post['shipping_method']) && !empty($this->request->post['shipping_method'])) ? $this->cleanAndReplace($this->request->post['shipping_method']) : 'default';

            $data['delivery_fields'] = $this->getFieldList('delivery', $type);

            if(!isset($oct_smart_checkout_data['customer']['fields'][$type])) {
                $type = 'default';
            }

            $data['language_id'] = (int) $this->config->get('config_language_id');
            $data['fields'] = $this->getFieldList('customer', $type);

            // validate country and address fields
            if(!isset($oct_smart_checkout_data['delivery']['fields'][$type])) {
                $type = 'default';
            }
            $data['payment_fields'] = $this->getPaymentFields(0);
            $data['merged_fields'] = array_merge($data['fields'], $data['delivery_fields'], isset($data['payment_fields']['fields']) ? $data['payment_fields']['fields'] : []);
            $data['countries'] = $this->model_localisation_country->getCountries();
            $data['default_country_id'] = !empty($this->session->data['oct_form_data']['country_id']) ? $this->session->data['oct_form_data']['country_id'] : (int) $this->config->get('config_country_id');
            $data['default_zone_id'] = !empty($this->session->data['oct_form_data']['zone_id']) ? $this->session->data['oct_form_data']['zone_id'] : 0;
            $data['zones'] = $this->zone($data['default_country_id']);

            foreach ($data['merged_fields'] as $key => $field) {
                if (isset($field['merge']) && $field['merge'] == 'on' && isset($field['merge_field'])) {
                    $mergeField = $field['merge_field'];
                    $fieldName = $this->language->get('entry_' . $key) ?? $key;
            
                    if (isset($field['custom_field']) && $field['custom_field']) {
                        $customFieldSettings = $this->getCustomFieldSettings($field['custom_field_id']);
                        $fieldName = $customFieldSettings['name'];
                    }
            
                    if (isset($field['localization'])) {
                        $currentLanguageId = $data['language_id'];
                        if (isset($field['localization'][$currentLanguageId]['name']) && !empty($field['localization'][$currentLanguageId]['name'])) {
                            $fieldName = $field['localization'][$currentLanguageId]['name'];
                        }
                    }

                    $fieldValue = '';
                    if ($field['type'] == 'text' || $field['type'] == 'tel' || $field['type'] == 'textarea' || $field['type'] == 'date' || $field['type'] == 'time' or $field['type'] == 'datetime') {
                        $fieldValue = $this->request->post[$key];
                    } elseif ($field['type'] == 'radio' || $field['type'] == 'select') {
                        if ($key == 'country_id') {
                            $countryInfo = isset($this->request->post[$key]) ? $this->model_localisation_country->getCountry($this->request->post[$key]) : null;
                            $fieldValue = $countryInfo ? $countryInfo['name'] : '';
                        } elseif ($key == 'zone_id') {
                            $zoneInfo = isset($this->request->post[$key]) ? $this->model_localisation_zone->getZone($this->request->post[$key]) : null;
                            $fieldValue = $zoneInfo ? $zoneInfo['name'] : '';
                        } elseif (isset($field['options']) && isset($this->request->post[$key])) {
                            foreach ($field['options'] as $option) {
                                if ($option['custom_field_value_id'] == $this->request->post[$key]) {
                                    $fieldValue = $option['name'];
                                    break;
                                }
                            }
                        }
                    } elseif ($field['type'] == 'checkbox') {
                        if (isset($field['options']) && isset($this->request->post[$key]) && is_array($this->request->post[$key])) {
                            $selectedOptions = [];
                            foreach ($this->request->post[$key] as $selected) {
                                foreach ($field['options'] as $option) {
                                    if ($option['custom_field_value_id'] == $selected) {
                                        $selectedOptions[] = $option['name'];
                                    }
                                }
                            }
                            $fieldValue = implode(', ', $selectedOptions);
                        }
                    }
            
                    $fieldValue = $fieldName . ": " . $fieldValue;
            
                    if (isset($order_data[$mergeField])) {
                        $order_data[$mergeField] .= "\r\n" . $fieldValue . "\r\n";
                    } else {
                        $order_data[$mergeField] = $fieldValue;
                    }
                }
            }

            $order_data['products'] = [];

            foreach ($this->cart->getProducts() as $product) {
                $option_data = [];

                foreach ($product['option'] as $option) {

                    $oct_product_option_value_id_val = explode("|", $option['product_option_value_id']);

                    if ($option['type'] == 'oct_quantity') {
                        $oct_product_option_value_id = $oct_product_option_value_id_val[0];
                    } else {
                        $oct_product_option_value_id = $option['product_option_value_id'];
                    }

                    $option_data[] = array(
                        'product_option_id' => $option['product_option_id'],
                        'product_option_value_id' => $oct_product_option_value_id,
                        'option_id' => $option['option_id'],
                        'option_value_id' => $option['option_value_id'],
                        'name' => $option['name'],
                        'sku' => (isset($option['sku'])) ? $option['sku'] : '',
                        'model' => (isset($option['model'])) ? $option['model'] : '',
                        'oct_quantity_value' => (isset($oct_product_option_value_id_val[1])) ? $oct_product_option_value_id_val[1] : '',
                        'value' => $option['value'],
                        'type' => $option['type']
                    );
                }

                $order_data['products'][] = array(
                    'product_id' => $product['product_id'],
                    'name' => $product['name'],
                    'model' => $product['model'],
                    'option' => $option_data,
                    'download' => $product['download'],
                    'quantity' => $product['quantity'],
                    'subtract' => $product['subtract'],
                    'price' => $product['price'],
                    'total' => $product['total'],
                    'tax' => $this->tax->getTax($product['price'], $product['tax_class_id']),
                    'stock' => $product['stock'] ? true : !(!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning')),
                    'reward' => $product['reward']
                );
            }

            // Gift Voucher
            $order_data['vouchers'] = [];

            if (!empty($this->session->data['vouchers'])) {
                foreach ($this->session->data['vouchers'] as $voucher) {
                    $order_data['vouchers'][] = array(
                        'description' => $voucher['description'],
                        'code' => substr(md5(mt_rand()), 0, 10),
                        'to_name' => $voucher['to_name'],
                        'to_email' => $voucher['to_email'],
                        'from_name' => $voucher['from_name'],
                        'from_email' => $voucher['from_email'],
                        'voucher_theme_id' => $voucher['voucher_theme_id'],
                        'message' => $voucher['message'],
                        'amount' => $voucher['amount']
                    );
                }
            }

            $order_data['total']   = $total_data['total'];

            if (isset($this->request->cookie['tracking'])) {
                $order_data['tracking'] = $this->request->cookie['tracking'];

                $subtotal = $this->cart->getSubTotal();

                // Affiliate
                $this->load->model('account/customer');
                $affiliate_info = $this->model_account_customer->getAffiliateByTracking($this->request->cookie['tracking']);

                if ($affiliate_info) {
                    $order_data['affiliate_id'] = $affiliate_info['customer_id'];
                    $order_data['commission'] = ($subtotal / 100) * $affiliate_info['commission'];
                } else {
                    $order_data['affiliate_id'] = 0;
                    $order_data['commission'] = 0;
                }

                // Marketing
                $this->load->model('checkout/marketing');

                $marketing_info = $this->model_checkout_marketing->getMarketingByCode($this->request->cookie['tracking']);

                if ($marketing_info) {
                    $order_data['marketing_id'] = $marketing_info['marketing_id'];
                } else {
                    $order_data['marketing_id'] = 0;
                }
            } else {
                $order_data['affiliate_id'] = 0;
                $order_data['commission'] = 0;
                $order_data['marketing_id'] = 0;
                $order_data['tracking'] = '';
            }

            $order_data['language_id']    = $this->config->get('config_language_id');
            $order_data['currency_id']    = $this->currency->getId($this->session->data['currency']);
            $order_data['currency_code']  = $this->session->data['currency'];
            $order_data['currency_value'] = $this->currency->getValue($this->session->data['currency']);
            $order_data['ip']             = $this->request->server['REMOTE_ADDR'];

            if (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
                $order_data['forwarded_ip'] = $this->request->server['HTTP_X_FORWARDED_FOR'];
            } elseif (!empty($this->request->server['HTTP_CLIENT_IP'])) {
                $order_data['forwarded_ip'] = $this->request->server['HTTP_CLIENT_IP'];
            } else {
                $order_data['forwarded_ip'] = '';
            }

            if (isset($this->request->server['HTTP_USER_AGENT'])) {
                $order_data['user_agent'] = $this->request->server['HTTP_USER_AGENT'];
            } else {
                $order_data['user_agent'] = '';
            }

            if (isset($this->request->server['HTTP_ACCEPT_LANGUAGE'])) {
                $order_data['accept_language'] = $this->request->server['HTTP_ACCEPT_LANGUAGE'];
            } else {
                $order_data['accept_language'] = '';
            }

            $this->load->model('checkout/order');
            
            $this->session->data['order_id'] = $this->model_checkout_order->addOrder($order_data);

            if (isset($this->request->cookie['oct_abandoned_cart_token'])) {
                $module_settings = $this->config->get('oct_abandoned_cart') ?: [];

                if (isset($module_settings['status']) && $module_settings['status']) {
                    $this->load->model('octemplates/module/oct_abandoned_cart');

                    if ($this->customer->isLogged()) {
                        $customer_id = (int)$this->customer->getId();
                        $this->model_octemplates_module_oct_abandoned_cart->markCartAsConvertedByCustomerId($customer_id);
                    } else {
                        $tokenParts = explode('|', $this->request->cookie['oct_abandoned_cart_token']);
                        if (count($tokenParts) === 2) {
                            list($rawToken, $signature) = $tokenParts;
                            $this->model_octemplates_module_oct_abandoned_cart->markCartAsConverted($rawToken, $signature);
                        }
                    }

                    $this->deleteAbandonedCartCookie();
                }
            }

            unset($this->session->data['oct_form_data']);

            $data['text_recurring_item']    = $this->language->get('text_recurring_item');
            $data['text_payment_recurring'] = $this->language->get('text_payment_recurring');
        }

        $this->load->model('tool/image');
        $this->load->model('tool/upload');

        $data['products'] = [];

        $products = $this->cart->getProducts();

        foreach ($products as $product) {
            $product_total = 0;

            foreach ($products as $product_2) {
                if ($product_2['product_id'] == $product['product_id']) {
                    $product_total += $product_2['quantity'];
                }
            }

            if ($product['minimum'] > $product_total) {
                $data['error_warning'] = sprintf($this->language->get('error_minimum'), $product['name'], $product['minimum']);
            }

            if ($product['image']) {
                $image = $this->model_tool_image->resize($product['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_height'));
            } else {
                $image = '';
            }

            $option_data = [];

            $this->load->model('catalog/product');

            $options_arr = [];

            foreach ($product['option'] as $value_opt) {
                array_push($options_arr, $value_opt['product_option_value_id']);
            }

            foreach ($product['option'] as $option) {
                if ($option['type'] != 'file') {
                    $value = $option['value'];
                } else {
                    $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

                    if ($upload_info) {
                        $value = $upload_info['name'];
                    } else {
                        $value = '';
                    }
                }

                $option_data[] = array(
                    'name' => $option['name'],
                    'sku' => (isset($option['sku'])) ? $option['sku'] : '',
                    'model' => (isset($option['model'])) ? $option['model'] : '',
                    'oct_quantity_value' => (isset($option['oct_quantity_value'])) ? $option['oct_quantity_value'] : '',
                    'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
                );
            }

            // Display prices
            if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
                $price = $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
            } else {
                $price = false;
            }

            // Display prices
            if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
                $p_total = $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')) * $product['quantity'], $this->session->data['currency']);
            } else {
                $p_total = false;
            }

            $recurring = '';

            if ($product['recurring']) {
                $frequencies = array(
                    'day' => $this->language->get('text_day'),
                    'week' => $this->language->get('text_week'),
                    'semi_month' => $this->language->get('text_semi_month'),
                    'month' => $this->language->get('text_month'),
                    'year' => $this->language->get('text_year')
                );

                if ($product['recurring']['trial']) {
                    $recurring = sprintf($this->language->get('text_trial_description'), $this->currency->format($this->tax->calculate($product['recurring']['trial_price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['trial_cycle'], $frequencies[$product['recurring']['trial_frequency']], $product['recurring']['trial_duration']) . ' ';
                }

                if ($product['recurring']['duration']) {
                    $recurring .= sprintf($this->language->get('text_payment_description'), $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
                } else {
                    $recurring .= sprintf($this->language->get('text_payment_cancel'), $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
                }
            }

            $data['products'][] = array(
                'key' => $product['cart_id'],
                'product_id' => $product['product_id'],
                'thumb' => $image,
                'name' => $product['name'],
                'model' => $product['model'],
                'option' => $option_data,
                'recurring' => $recurring,
                'quantity' => $product['quantity'],
                'stock' => $product['stock'] ? true : !(!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning')),
                'reward' => ($product['reward'] ? sprintf($this->language->get('text_points'), $product['reward']) : ''),
                'price' => $price,
                'total' => $p_total,
                'href' => $this->url->link('product/product', 'product_id=' . $product['product_id'])
            );
        }

        // Gift Voucher
        $data['vouchers'] = [];

        if (!empty($this->session->data['vouchers'])) {
            foreach ($this->session->data['vouchers'] as $voucher) {
                $data['vouchers'][] = array(
                    'description' => $voucher['description'],
                    'amount' => $this->currency->format($voucher['amount'], $this->session->data['currency'])
                );
            }
        }

        $data['totals'] = [];

        foreach ($order_data['totals'] as $total) {
            $data['totals'][] = array(
                'title' => $total['title'],
                'code' => $total['code'],
                'text' => $this->currency->format($total['value'], $this->session->data['currency'])
            );
        }

        $json = [];

        $json['output'] = $this->load->controller('extension/payment/' . $this->session->data['payment_method']['code']);

        return $json;
    } 

    private function setCookieUnified($name, $value, $expires, $path = '/', $domain = '', $secure = false, $httponly = true, $samesite = 'Strict') {
        if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
            $options = [
                'expires'  => $expires,
                'path'     => $path,
                'domain'   => $domain,
                'secure'   => $secure,
                'httponly' => $httponly,
                'samesite' => $samesite,
            ];
            setcookie($name, $value, $options);
        } else {
            $pathWithSameSite = $path . '; samesite=' . $samesite;
            setcookie($name, $value, $expires, $pathWithSameSite, $domain, $secure, $httponly);
        }
    }

    private function deleteAbandonedCartCookie() {
        if (isset($this->request->cookie['oct_abandoned_cart_token'])) {
            $domain = $this->request->server['HTTP_HOST'];
            $secure = isset($this->request->server['HTTPS']) && 
                      ($this->request->server['HTTPS'] === 'on' || $this->request->server['HTTPS'] == 1);

            $this->setCookieUnified('oct_abandoned_cart_token', '', time() - 3600, '/', $domain, $secure, true, 'Strict');
        }
    }

    private function validateFields($fields = array()) {
        $json = [];

        $oct_smart_checkout_data         = $this->config->get('oct_smart_checkout_data');

        $paymentFields = $this->getPaymentFields(0);
        
        if ($paymentFields) {
            $fields = array_merge($fields, $paymentFields['fields']);
        }
 
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
                    if (empty($value)) {
                        if ($field_settings['required']) {
                            $json['error']['email'] = $this->getErrorMessage($field_settings, $fieldName);
                        }
                    } elseif (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $json['error']['email'] = $this->getErrorMessage($field_settings, $fieldName);
                    }
                    break;
                case 'telephone':case 'telephone':
                    $postedTelephone = $this->request->post['telephone'] ?? '';
                    $phoneMask       = $oct_smart_checkout_data['phone_mask'] ?? '';
                    $oct_remarket_data  = $this->config->get('theme_oct_remarket_data');
                    $phone_regex     = $oct_remarket_data['phone_regex'] ?? '';
                
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
                            if (@preg_match($phone_regex, '') !== false) {
                                if (!preg_match($phone_regex, $postedTelephone)) {
                                    $json['error']['telephone'] = $this->getErrorMessage($field_settings, $fieldName);
                                }
                            }
                        }
                    }
                    break;
                case 'address_1':
                    if ($field_settings['required'] && (utf8_strlen($value) < 1 || utf8_strlen($value) > 128)) {
                        $json['error']['address_1'] = $this->getErrorMessage($field_settings, $fieldName);
                    }
                    break;
                case 'address_2':
                    if ($field_settings['required'] && (utf8_strlen($value) < 1 || utf8_strlen($value) > 128)) {
                        $json['error']['address_2'] = $this->getErrorMessage($field_settings, $fieldName);
                    }
                    break;
                case 'city':
                    if ($field_settings['required'] && (utf8_strlen($value) < 2 || utf8_strlen($value) > 128)) {
                        $json['error']['city'] = $this->getErrorMessage($field_settings, $fieldName);
                    }
                    break;
                case 'password':
                    if ($field_settings['required'] && strlen($value) < 3) { 
                        $json['error']['password'] = $this->getErrorMessage($field_settings, $fieldName);
                    }
                    break;
                case 'postcode':
                    if ($field_settings['required'] && (utf8_strlen($value) < 2 || utf8_strlen($value) > 10)) {
                        $json['error']['postcode'] = $this->getErrorMessage($field_settings, $fieldName);
                    }
                    break;
                case 'company':
                    if ($field_settings['required'] && (utf8_strlen($value) < 3 || utf8_strlen($value) > 128)) {
                        $json['error']['company'] = $this->getErrorMessage($field_settings, $fieldName);
                    }
                    break;
                case 'fax':
                    if ($field_settings['required'] && (utf8_strlen($value) < 3 || utf8_strlen($value) > 32)) {
                        $json['error']['fax'] = $this->getErrorMessage($field_settings, $fieldName);
                    }
                    break;
            }

        }

        return $json;
    }

    private function getErrorMessage($field_settings, $field) {
        $language_id = (int) $this->config->get('config_language_id');
        if (!empty($field_settings['localization'][$language_id]['error_text'])) {
            return $field_settings['localization'][$language_id]['error_text'];
        } else {
            return $this->language->get('error_' . $field);
        }
    }

    private function guestValidate(&$data = array()) {
        $json = [];

        $this->load->language('octemplates/module/oct_smartcheckout');
        $this->load->model('account/customer_group');
        $this->load->model('localisation/country');
        $data['language_id'] = (int) $this->config->get('config_language_id');

        $customer_group_id = (int) $this->config->get('config_customer_group_id');

        $this->load->language('octemplates/module/oct_smartcheckout');

        if ($this->customer->isLogged()) {
            $json['redirect'] = $this->url->link('checkout/oct_smartcheckout', '', 'SSL');
        }

        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            $json['redirect'] = $this->url->link('checkout/oct_smartcheckout');
        }

        if (!$this->config->get('config_checkout_guest') || $this->config->get('config_customer_price')) {
            $json['redirect'] = $this->url->link('checkout/oct_smartcheckout', '', 'SSL');
        }

        if (!$json) {
            // validation customer fields
            $oct_smart_checkout_data = $this->config->get('oct_smart_checkout_data');
            $this->load->language('octemplates/module/oct_smartcheckout');

            $type = (isset($this->request->post['shipping_method']) && !empty($this->request->post['shipping_method'])) ? $this->cleanAndReplace($this->request->post['shipping_method']) : 'default';

            if(!isset($oct_smart_checkout_data['customer']['fields'][$type])) {
                $customerType = 'default';
            } else {
                $customerType = $type;
            }

            $data['language_id'] = (int) $this->config->get('config_language_id');
            $data['fields'] = $this->getFieldList('customer', $customerType);

            // validate country and address fields
            if(!isset($oct_smart_checkout_data['delivery']['fields'][$type])) {
                $type = 'default';
            }
            $data['delivery_fields'] = $this->getFieldList('delivery', $type);
            $data['merged_fields'] = array_merge($data['fields'], $data['delivery_fields']);
            $data['countries'] = $this->model_localisation_country->getCountries();
            $data['default_country_id'] = !empty($this->session->data['oct_form_data']['country_id']) ? $this->session->data['oct_form_data']['country_id'] : (int) $this->config->get('config_country_id');
            $data['default_zone_id'] = !empty($this->session->data['oct_form_data']['zone_id']) ? $this->session->data['oct_form_data']['zone_id'] : 0;
            $data['zones'] = $this->zone($data['default_country_id']);

            $json = $this->validateFields($data['merged_fields']);
        }

        if (!$json) {
            $json = $this->PaymentMethodValidate();
        }
       
        if (!$json) {
            $json = $this->checkAgree();
        }

        if (!$json) {
            $this->session->data['account'] = 'guest';

            $this->session->data['guest']['customer_group_id'] = $customer_group_id;
            $this->session->data['guest']['firstname']         = (isset($this->request->post['firstname']) && !empty($this->request->post['firstname'])) ? $this->request->post['firstname'] : '';
            $this->session->data['guest']['lastname']          = (isset($this->request->post['lastname']) && !empty($this->request->post['lastname'])) ? $this->request->post['lastname'] : '';
            $this->session->data['guest']['email']             = (isset($this->request->post['email']) && !empty($this->request->post['email'])) ? $this->request->post['email'] : '';
            $this->session->data['guest']['telephone']         = (isset($this->request->post['telephone']) && !empty($this->request->post['telephone'])) ? $this->request->post['telephone'] : '';
            $this->session->data['guest']['fax']               = (isset($this->request->post['fax']) && !empty($this->request->post['fax'])) ? $this->request->post['fax'] : '';

            $data['payment_fields'] = $this->getPaymentFields(0);
            $data['check_custom_fields'] = array_merge($data['merged_fields'], isset($data['payment_fields']['fields']) ? $data['payment_fields']['fields'] : []);

            $this->session->data['guest']['custom_field'] = [];

            foreach ($data['check_custom_fields'] as $field => $field_settings) {
                if ($field_settings['custom_field']) {
                    $this->session->data['guest']['custom_field'][$field_settings['custom_field_id']] = $this->request->post[$field];
                }
            }

            $this->session->data['payment_address']['firstname']  = (isset($this->request->post['firstname']) && !empty($this->request->post['firstname'])) ? $this->request->post['firstname'] : '';
            $this->session->data['payment_address']['lastname']   = (isset($this->request->post['lastname']) && !empty($this->request->post['lastname'])) ? $this->request->post['lastname'] : '';
            $this->session->data['payment_address']['company']    = (isset($this->request->post['company']) && !empty($this->request->post['company'])) ? $this->request->post['company'] : '';
            $this->session->data['payment_address']['address_1']  = (isset($this->request->post['address_1']) && !empty($this->request->post['address_1'])) ? $this->request->post['address_1'] : '';
            $this->session->data['payment_address']['address_2']  = (isset($this->request->post['address_2']) && !empty($this->request->post['address_2'])) ? $this->request->post['address_2'] : '';
            $this->session->data['payment_address']['postcode']   = (isset($this->request->post['postcode']) && !empty($this->request->post['postcode'])) ? $this->request->post['postcode'] : '';
            $this->session->data['payment_address']['city']       = (isset($this->request->post['city']) && !empty($this->request->post['city'])) ? $this->request->post['city'] : '';
            $this->session->data['payment_address']['country_id'] = (isset($this->request->post['country_id']) && !empty($this->request->post['country_id'])) ? $this->request->post['country_id'] : '';
            $this->session->data['payment_address']['zone_id']    = (isset($this->request->post['zone_id']) && !empty($this->request->post['zone_id'])) ? $this->request->post['zone_id'] : '';

            $this->load->model('localisation/country');

            if (isset($this->request->post['country_id'])) {
                $country_info = $this->model_localisation_country->getCountry($this->request->post['country_id']);
            } else {
                $country_info = false;
            }

            if ($country_info) {
                $this->session->data['payment_address']['country']        = $country_info['name'];
                $this->session->data['payment_address']['iso_code_2']     = $country_info['iso_code_2'];
                $this->session->data['payment_address']['iso_code_3']     = $country_info['iso_code_3'];
                $this->session->data['payment_address']['address_format'] = $country_info['address_format'];
            } else {
                $this->session->data['payment_address']['country']        = '';
                $this->session->data['payment_address']['iso_code_2']     = '';
                $this->session->data['payment_address']['iso_code_3']     = '';
                $this->session->data['payment_address']['address_format'] = '';
            }

            if (isset($this->request->post['custom_field']['address'])) {
                $this->session->data['payment_address']['custom_field'] = $this->request->post['custom_field']['address'];
            } else {
                $this->session->data['payment_address']['custom_field'] = [];
            }

            $this->load->model('localisation/zone');

            if (isset($this->request->post['zone_id'])) {
                $zone_info = $this->model_localisation_zone->getZone($this->request->post['zone_id']);
            } else {
                $zone_info = false;
            }

            if ($zone_info) {
                $this->session->data['payment_address']['zone']      = $zone_info['name'];
                $this->session->data['payment_address']['zone_code'] = $zone_info['code'];
            } else {
                $this->session->data['payment_address']['zone']      = '';
                $this->session->data['payment_address']['zone_code'] = '';
            }

            if (!empty($this->request->post['shipping_address'])) {
                $this->session->data['guest']['shipping_address'] = $this->request->post['shipping_address'];
            } else {
                $this->session->data['guest']['shipping_address'] = false;
            }

            // Default Payment Address
            if ($this->session->data['guest']['shipping_address'] || $this->session->data['shipping_address']) {
                $this->session->data['shipping_address']['firstname']  = (isset($this->request->post['firstname']) && !empty($this->request->post['firstname'])) ? $this->request->post['firstname'] : '';
                $this->session->data['shipping_address']['lastname']   = (isset($this->request->post['lastname']) && !empty($this->request->post['lastname'])) ? $this->request->post['lastname'] : '';
                $this->session->data['shipping_address']['company']    = (isset($this->request->post['company']) && !empty($this->request->post['company'])) ? $this->request->post['company'] : '';
                $this->session->data['shipping_address']['address_1']  = (isset($this->request->post['address_1']) && !empty($this->request->post['address_1'])) ? $this->request->post['address_1'] : '';
                $this->session->data['shipping_address']['address_2']  = (isset($this->request->post['address_2']) && !empty($this->request->post['address_2'])) ? $this->request->post['address_2'] : '';
                $this->session->data['shipping_address']['postcode']   = (isset($this->request->post['postcode']) && !empty($this->request->post['postcode'])) ? $this->request->post['postcode'] : '';
                $this->session->data['shipping_address']['city']       = (isset($this->request->post['city']) && !empty($this->request->post['city'])) ? $this->request->post['city'] : '';
                $this->session->data['shipping_address']['country_id'] = (isset($this->request->post['country_id']) && !empty($this->request->post['country_id'])) ? $this->request->post['country_id'] : '';
                $this->session->data['shipping_address']['zone_id']    = (isset($this->request->post['zone_id']) && !empty($this->request->post['zone_id'])) ? $this->request->post['zone_id'] : '';

                if ($country_info) {
                    $this->session->data['shipping_address']['country']        = $country_info['name'];
                    $this->session->data['shipping_address']['iso_code_2']     = $country_info['iso_code_2'];
                    $this->session->data['shipping_address']['iso_code_3']     = $country_info['iso_code_3'];
                    $this->session->data['shipping_address']['address_format'] = $country_info['address_format'];
                } else {
                    $this->session->data['shipping_address']['country']        = '';
                    $this->session->data['shipping_address']['iso_code_2']     = '';
                    $this->session->data['shipping_address']['iso_code_3']     = '';
                    $this->session->data['shipping_address']['address_format'] = '';
                }

                if ($zone_info) {
                    $this->session->data['shipping_address']['zone']      = $zone_info['name'];
                    $this->session->data['shipping_address']['zone_code'] = $zone_info['code'];
                } else {
                    $this->session->data['shipping_address']['zone']      = '';
                    $this->session->data['shipping_address']['zone_code'] = '';
                }

                if (isset($this->request->post['custom_field']['address'])) {
                    $this->session->data['shipping_address']['custom_field'] = $this->request->post['custom_field']['address'];
                } else {
                    $this->session->data['shipping_address']['custom_field'] = [];
                }
            }
        }

        return $json;
    }

    private function registerValidate(&$data = array()) {
        $json = [];

        $oct_smart_checkout_data = $this->config->get('oct_smart_checkout_data');

        $this->load->language('octemplates/module/oct_smartcheckout');
        $this->load->model('account/customer');
        $this->load->model('localisation/country');
        $data['language_id'] = (int) $this->config->get('config_language_id');

        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            $json['redirect'] = $this->url->link('checkout/oct_smartcheckout');
        }

        $products = $this->cart->getProducts();

        foreach ($products as $product) {
            $product_total = 0;

            foreach ($products as $product_2) {
                if ($product_2['product_id'] == $product['product_id']) {
                    $product_total += $product_2['quantity'];
                }
            }

            if ($product['minimum'] > $product_total) {
                $json['redirect'] = $this->url->link('checkout/oct_smartcheckout');

                break;
            }
        }

        if (!$json) {
            // validation customer fields
            $oct_smart_checkout_data = $this->config->get('oct_smart_checkout_data');
            $this->load->language('octemplates/module/oct_smartcheckout');

            $type = (isset($this->request->post['shipping_method']) && !empty($this->request->post['shipping_method'])) ? $this->cleanAndReplace($this->request->post['shipping_method']) : 'default';

            if(!isset($oct_smart_checkout_data['customer']['fields'][$type])) {
                $customerType = 'default';
            } else {
                $customerType = $type;
            }

            $data['language_id'] = (int) $this->config->get('config_language_id');
            $data['fields'] = $this->getFieldList('customer', $customerType);

            // validate country and address fields
            if(!isset($oct_smart_checkout_data['delivery']['fields'][$type])) {
                $type = 'default';
            }

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
                )
            );

            $data['delivery_fields'] = $this->getFieldList('delivery', $type);
            $data['merged_fields'] = array_merge($data['fields'], $data['delivery_fields'], $data['register_fields']);
            $data['countries'] = $this->model_localisation_country->getCountries();
            $data['default_country_id'] = !empty($this->session->data['oct_form_data']['country_id']) ? $this->session->data['oct_form_data']['country_id'] : (int) $this->config->get('config_country_id');
            $data['default_zone_id'] = !empty($this->session->data['oct_form_data']['zone_id']) ? $this->session->data['oct_form_data']['zone_id'] : 0;
            $data['zones'] = $this->zone($data['default_country_id']);

            $json = $this->validateFields($data['merged_fields']);

            $data['payment_fields'] = $this->getPaymentFields(0);
            $data['check_custom_fields'] = array_merge($data['merged_fields'], isset($data['payment_fields']['fields']) ? $data['payment_fields']['fields'] : []);

            $this->session->data['customer']['custom_field'] = [];

            foreach ($data['check_custom_fields'] as $field => $field_settings) {
                if ($field_settings['custom_field']) {
                    $this->session->data['customer']['custom_field'][$field_settings['custom_field_id']] = $this->request->post[$field];
                }
            }

            if ($this->model_account_customer->getTotalCustomersByEmail(isset($this->request->post['email']) ? $this->request->post['email'] : '')) {
                $json['error']['email'] = $this->language->get('error_exists');
            }
        }

        if (!$json) {
            $json = $this->PaymentMethodValidate();
        }

        if (!$json) {
            $json = $this->checkAgree();
        }

        if (!$json) {
            $json = $this->ShippingAddressValidate();
        }

        if (!$json) {
            $this->session->data['account'] = 'register';

            $this->request->post['lastname'] = $this->request->post['lastname'] ?? '';

            $this->session->data['customer_id'] = $customer_id = $this->model_account_customer->addCustomer($this->request->post);

			$this->session->data['checkout_customer_id'] = true;

            $this->load->model('account/customer_group');

            $customer_group_id = $this->config->get('config_customer_group_id');
            $customer_group = $this->model_account_customer_group->getCustomerGroup($customer_group_id);

            if ($customer_group && !$customer_group['approval']) {

                $this->customer->login($this->request->post['email'], $this->request->post['password']);

                $this->load->model('account/address');
                $addressData = [];

                $addressData['firstname']   = $this->request->post['firstname'] ?? '';
                $addressData['lastname']    = $this->request->post['lastname'] ?? '';
                $addressData['company']     = $this->request->post['company'] ?? '';
                $addressData['address_1'] = $this->request->post['address_1'] ?? '';
                $addressData['address_2'] = $this->request->post['address_2'] ?? '';
                $addressData['postcode'] = $this->request->post['postcode'] ?? '';
                $addressData['city'] = $this->request->post['city'] ?? '';
                $addressData['zone_id'] = $this->request->post['zone_id'] ?? '';
                $addressData['country_id'] = $this->request->post['country_id'] ?? '';
                $addressData['default'] = 1;

                $address_id = $this->model_account_address->addAddress($this->customer->getId(), $addressData);

                $default_address = $this->model_account_address->getAddress($this->customer->getAddressId());
    
                $this->session->data['shipping_address'] = $default_address;
                $this->session->data['shipping_address_id'] = $address_id;
                $this->session->data['payment_address'] = $address_id;
                $this->session->data['payment_address_id'] = $address_id;
                
            } else {
                $json['redirect'] = $this->url->link('account/success');
            }

            unset($this->session->data['guest']);

            // Add to activity log
            if ($this->config->get('config_customer_activity') && isset($this->session->data['checkout_customer_id'])) {
                $this->load->model('account/activity');

                $addressDataLastname    = $this->request->post['lastname'] ?? '';

                $activity_data = array(
                    'customer_id' => $this->session->data['checkout_customer_id'],
                    'name' => $this->request->post['firstname'] . ' ' . $addressDataLastname
                );

                $this->model_account_activity->addActivity('register', $activity_data);
            }
            $cart = new Cart\Cart($this->registry);
            $this->registry->set('cart', $cart);
        }

        return $json;
    }

    private function customerValidate(&$data = array()) {
        $json = [];

        $oct_smart_checkout_data = $this->config->get('oct_smart_checkout_data');

        $this->load->language('octemplates/module/oct_smartcheckout');
        $this->load->model('account/customer');
        $this->load->model('localisation/country');
        $data['language_id'] = (int) $this->config->get('config_language_id');

        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            $json['redirect'] = $this->url->link('checkout/oct_smartcheckout');
        }

        $products = $this->cart->getProducts();

        foreach ($products as $product) {
            $product_total = 0;

            foreach ($products as $product_2) {
                if ($product_2['product_id'] == $product['product_id']) {
                    $product_total += $product_2['quantity'];
                }
            }

            if ($product['minimum'] > $product_total) {
                $json['redirect'] = $this->url->link('checkout/oct_smartcheckout');

                break;
            }
        }

        if (!$json && isset($this->request->post['new_address'])) {
            $data['countries'] = $this->model_localisation_country->getCountries();
            $data['default_country_id'] = !empty($this->session->data['oct_form_data']['country_id']) ? $this->session->data['oct_form_data']['country_id'] : (int) $this->config->get('config_country_id');
            $data['default_zone_id'] = !empty($this->session->data['oct_form_data']['zone_id']) ? $this->session->data['oct_form_data']['zone_id'] : 0;
            $data['zones'] = $this->zone($data['default_country_id']);
            $data['language_id'] = (int) $this->config->get('config_language_id');


            $type = (isset($this->request->post['shipping_method']) && !empty($this->request->post['shipping_method'])) ? $this->cleanAndReplace($this->request->post['shipping_method']) : 'default';

            if(!isset($oct_smart_checkout_data['delivery']['fields'][$type])) {
                $type = 'default';
            }

            $data['delivery_fields'] = $this->getFieldList('delivery', $type);
            $json = $this->validateFields($data['delivery_fields']);
        }

        $data['payment_fields'] = $this->getPaymentFields(0);

        if (!$json && $data['payment_fields']) {
            $json = $this->validateFields($data['payment_fields']);
        }

        if (!$json) {
            $json = $this->PaymentMethodValidate();
        }

        if (!$json) {
            $json = $this->checkAgree();
        }

        if (!$json) {
            $json = $this->ShippingAddressValidate();
        }

        if (!$json) {

            $customer_id = $this->customer->getId();

			$this->session->data['checkout_customer_id'] = true;

            if (!isset($this->request->post['address_id']) && !isset($this->request->post['new_address'])) {
                $json['error']['address_id'] = $this->language->get('error_address');
            } 
        }

        $oct_smart_checkout_data = $this->config->get('oct_smart_checkout_data');

        $type = (isset($this->request->post['shipping_method']) && !empty($this->request->post['shipping_method'])) ? $this->cleanAndReplace($this->request->post['shipping_method']) : 'default';

        if(!isset($oct_smart_checkout_data['customer']['fields'][$type])) {
            $customerType = 'default';
        } else {
            $customerType = $type;
        }

        $data['language_id'] = (int) $this->config->get('config_language_id');
        $data['fields'] = $this->getFieldList('customer', $customerType);

        if(!isset($oct_smart_checkout_data['delivery']['fields'][$type])) {
            $type = 'default';
        }

        $data['delivery_fields'] = $this->getFieldList('delivery', $type);
        $data['merged_fields'] = array_merge($data['fields'], $data['delivery_fields'], isset($data['payment_fields']['fields']) ? $data['payment_fields']['fields'] : []);
        $data['countries'] = $this->model_localisation_country->getCountries();
        $data['default_country_id'] = !empty($this->session->data['oct_form_data']['country_id']) ? $this->session->data['oct_form_data']['country_id'] : (int) $this->config->get('config_country_id');
        $data['default_zone_id'] = !empty($this->session->data['oct_form_data']['zone_id']) ? $this->session->data['oct_form_data']['zone_id'] : 0;
        $data['zones'] = $this->zone($data['default_country_id']);

        if (!$json) {
            $json = $this->validateFields($data['fields']);
        }

        $this->session->data['customer']['custom_field'] = [];

        foreach ($data['merged_fields'] as $field => $field_settings) {
            if (isset($field_settings['custom_field']) && $field_settings['custom_field']) {
                if (isset($this->request->post[$field])) {
                    $this->session->data['customer']['custom_field'][$field_settings['custom_field_id']] = $this->request->post[$field];
                }
            }
        }

        if (!$json && isset($this->request->post['new_address'])) {
            $this->load->model('account/address');
            $addressData = [];

            $addressData['firstname']   = $this->request->post['firstname'] ?? '';
            $addressData['lastname']    = $this->request->post['lastname'] ?? '';
            $addressData['company']     = $this->request->post['company'] ?? '';
            $addressData['address_1'] = $this->request->post['address_1'] ?? '';
            $addressData['address_2'] = $this->request->post['address_2'] ?? '';
            $addressData['postcode'] = $this->request->post['postcode'] ?? '';
            $addressData['city'] = $this->request->post['city'] ?? '';
            $addressData['zone_id'] = $this->request->post['zone_id'] ?? '';
            $addressData['country_id'] = $this->request->post['country_id'] ?? '';
            $addressData['default'] = isset($this->request->post['default']) ? 1 : 0;

            $address_id = $this->model_account_address->addAddress($this->customer->getId(), $addressData);

            $default_address = $this->model_account_address->getAddress($this->customer->getAddressId());

            $this->session->data['payment_address_id'] = $address_id;
            $this->session->data['payment_address'] = $address_id;
            $this->session->data['shipping_address_id'] = $address_id;

            if ($addressData['default'] != 1) {
                $this->session->data['shipping_address'] = $address_id;
            } else {
                $this->session->data['shipping_address'] = $default_address;
            }
        }

        return $json;
    }

    private function ShippingAddressValidate(&$data = array()) {
        $json               = [];
        $oct_smart_checkout_data = $this->config->get('oct_smart_checkout_data');

        $this->load->language('octemplates/module/oct_smartcheckout');

        // Validate minimum quantity requirments.
        $products = $this->cart->getProducts();

        foreach ($products as $product) {
            $product_total = 0;

            foreach ($products as $product_2) {
                if ($product_2['product_id'] == $product['product_id']) {
                    $product_total += $product_2['quantity'];
                }
            }

            if ($product['minimum'] > $product_total) {
                $json['redirect'] = $this->url->link('checkout/oct_smartcheckout');
                break;
            }
        }

        if (!$json) {
            $this->load->model('account/address');

            if ($this->customer->getAddressId()) {
                $default_address = $this->model_account_address->getAddress($this->customer->getAddressId());
                
                $this->session->data['payment_address'] = $default_address;
                $this->session->data['shipping_address'] = $default_address;
            }
        }

        return $json;
    }

    private function isValidRequest() {
		return isset($this->request->server['HTTP_X_REQUESTED_WITH']) &&
			   !empty($this->request->server['HTTP_X_REQUESTED_WITH']) &&
			   strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
	}

    public function coupon() {
		$this->load->language('extension/total/coupon');

		$json = array();

		$this->load->model('extension/total/coupon');

		if (isset($this->request->post['coupon'])) {
			$coupon = $this->request->post['coupon'];
		} else {
			$coupon = '';
		}

		$coupon_info = $this->model_extension_total_coupon->getCoupon($coupon);

		if (empty($this->request->post['coupon'])) {
			$json['error'] = $this->language->get('error_empty');

			unset($this->session->data['coupon']);
		} elseif ($coupon_info) {
			$this->session->data['coupon'] = $this->request->post['coupon'];

			$this->session->data['success'] = $this->language->get('text_success');

			$json['success'] = $this->language->get('text_success');
		} else {
			$json['error'] = $this->language->get('error_coupon');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function voucher() {
		$this->load->language('extension/total/voucher');

		$json = array();

		$this->load->model('extension/total/voucher');

		if (isset($this->request->post['voucher'])) {
			$voucher = $this->request->post['voucher'];
		} else {
			$voucher = '';
		}

		$voucher_info = $this->model_extension_total_voucher->getVoucher($voucher);

		if (empty($this->request->post['voucher'])) {
			$json['error'] = $this->language->get('error_empty');
		} elseif ($voucher_info) {
			$this->session->data['voucher'] = $this->request->post['voucher'];

			$this->session->data['success'] = $this->language->get('text_success');

			$json['success'] = $this->language->get('text_success');
		} else {
			$json['error'] = $this->language->get('error_voucher');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

    public function reward() {
		$this->load->language('extension/total/reward');

		$json = array();

		$points = $this->customer->getRewardPoints();

		$points_total = 0;

		foreach ($this->cart->getProducts() as $product) {
			if ($product['points']) {
				$points_total += $product['points'];
			}
		}

		if (!isset($this->request->post['reward']) || !filter_var($this->request->post['reward'], FILTER_VALIDATE_INT) || ($this->request->post['reward'] <= 0)) {
			$json['error'] = $this->language->get('error_reward');
		}

		if ($this->request->post['reward'] > $points) {
			$json['error'] = sprintf($this->language->get('error_points'), $this->request->post['reward']);
		}

		if ($this->request->post['reward'] > $points_total) {
			$json['error'] = sprintf($this->language->get('error_maximum'), $points_total);
		}

		if (!$json) {
			$this->session->data['reward'] = abs($this->request->post['reward']);
			$this->session->data['success'] = $this->language->get('text_success');
            $json['success'] = $this->language->get('text_success');
		} 

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
    
}