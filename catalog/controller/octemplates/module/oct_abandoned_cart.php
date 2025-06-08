<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOctemplatesModuleOctAbandonedCart extends Controller {
    private $cookieName = 'oct_abandoned_cart_token';

    public function setOrUpdateCookie() {

        if (!$this->checkModuleStatus()) {
            return;
        }
        
        $module_settings = $this->config->get('oct_abandoned_cart') ?: [];
    
        if (empty($module_settings['api_key']) || !preg_match('/^[a-f0-9]{32,}$/i', $module_settings['api_key'])) {
            return;
        }
    
        $cart_products = $this->cart->getProducts();
        $filtered_cart_data = [];
        
        foreach ($cart_products as $product) {
            $filtered_options = [];
            foreach ($product['option'] as $option) {
                $filtered_options[] = [
                    'product_option_id'       => (int)$option['product_option_id'],
                    'product_option_value_id' => (int)$option['product_option_value_id'],
                    'option_id'               => (int)$option['option_id'],
                    'option_value_id'         => (int)$option['option_value_id'],
                    'name'                    => $this->sanitizeString($option['name']),
                    'value'                   => $this->sanitizeString($option['value'])
                ];
            }
        
            $filtered_cart_data[] = [
                'product_id' => (int)$product['product_id'],
                'quantity'   => (int)$product['quantity'],
                'option'     => $filtered_options
            ];
        }
        
        $data = [
            'cart_data'   => $filtered_cart_data,
            'customer_id' => $this->customer->isLogged() ? (int)$this->customer->getId() : 0,
            'firstname'   => isset($this->request->post['firstname']) ? $this->sanitizeString($this->request->post['firstname']) : ($this->customer->getFirstName() ? $this->sanitizeString($this->customer->getFirstName()) : ''),
            'lastname'    => isset($this->request->post['lastname']) ? $this->sanitizeString($this->request->post['lastname']) : ($this->customer->getLastName() ? $this->sanitizeString($this->customer->getLastName()) : ''),
            'email'       => isset($this->request->post['email']) ? filter_var($this->request->post['email'], FILTER_SANITIZE_EMAIL) : ($this->customer->getEmail() ? filter_var($this->customer->getEmail(), FILTER_SANITIZE_EMAIL) : ''),
            'phone'       => isset($this->request->post['telephone']) ? $this->sanitizeString($this->request->post['telephone']) : ($this->customer->getTelephone() ? $this->sanitizeString($this->customer->getTelephone()) : ''),
            'store_id'    => (int)$this->config->get('config_store_id'),
            'store_name'  => $this->sanitizeString($this->config->get('config_name')),
            'language_id' => (int)$this->config->get('config_language_id')
        ];
    
        $this->load->model('octemplates/module/oct_abandoned_cart');
    
        if ($this->customer->isLogged()) {
            $customer_id = (int)$this->customer->getId();
            $existing_cart = $this->model_octemplates_module_oct_abandoned_cart->getAbandonedCartByCustomerId($customer_id);
            
            if ($existing_cart) {
                $cookie_lifetime_seconds = (int)$module_settings['cookie_lifetime'] * 24 * 60 * 60;
                $cookie_expiry_time = strtotime($existing_cart['date_added']) + $cookie_lifetime_seconds;
    
                if ($cookie_expiry_time > time()) {
                    $data['cookie_token'] = $existing_cart['cookie_token'];
                    $data['cookie_signature'] = $existing_cart['cookie_signature'];
    
                    $this->setCookie(
                        $existing_cart['cookie_token'],
                        $existing_cart['cookie_signature'],
                        $cookie_expiry_time
                    );
    
                    $data = $this->prepareAbandonedData($data);
                    $this->model_octemplates_module_oct_abandoned_cart->saveAbandonedCartData($data);
                    return; 
                }
            }
        }
    
        if (empty($this->request->cookie[$this->cookieName])) {
            try {
                $rawToken = bin2hex(random_bytes(16));
            } catch (Exception $e) {
                $this->log->write('Error generating token: ' . $e->getMessage());
                return;
            }
    
            $api_key = $module_settings['api_key'] ?? '';
            $signature = $this->makeSignature($rawToken, $api_key);
    
            $lifetime = time() + (int)$module_settings['cookie_lifetime'] * 24 * 60 * 60;
            $this->setCookie($rawToken, $signature, $lifetime);
    
            $data['cookie_token'] = $rawToken;
            $data['cookie_signature'] = $signature;
        } else {
            if (strpos($this->request->cookie[$this->cookieName], '|') !== false) {
                list($rawToken, $signature) = explode('|', $this->request->cookie[$this->cookieName], 2);

                if (!$this->customer->isLogged()) {
                    $existing_guest_cart = $this->model_octemplates_module_oct_abandoned_cart->getAbandonedCartGuests($rawToken, $signature);
                    if (!$existing_guest_cart) {

                        $this->deleteCookie();
                        try {
                            $rawToken = bin2hex(random_bytes(16));
                        } catch (Exception $e) {
                            $this->log->write('Error generating token: ' . $e->getMessage());
                            return;
                        }
                        $api_key = $module_settings['api_key'] ?? '';
                        $signature = $this->makeSignature($rawToken, $api_key);
                        $lifetime = time() + (int)$module_settings['cookie_lifetime'] * 24 * 60 * 60;
                        $this->setCookie($rawToken, $signature, $lifetime);
                    }
                }
                $data['cookie_token'] = $rawToken;
                $data['cookie_signature'] = $signature;
            } else {
                $this->deleteCookie();
                return;
            }
        }
    
        $data = $this->prepareAbandonedData($data);
        $this->model_octemplates_module_oct_abandoned_cart->saveAbandonedCartData($data);
    }       

    public function cron() {

        if (!$this->checkModuleStatus()) {
            return;
        }

        $this->load->language('octemplates/module/oct_abandoned_cart');

        $module_settings = $this->config->get('oct_abandoned_cart') ?: [];

        $cron_pass_req = isset($this->request->get['cron_pass']) ? $this->sanitizeString($this->request->get['cron_pass']) : '';
        if (($module_settings['cron_password'] ?? '') !== $cron_pass_req) {
            exit($this->language->get('text_invalid_cron_pass'));
        }

        $this->load->model('octemplates/module/oct_abandoned_cart');

        $this->model_octemplates_module_oct_abandoned_cart->sendAbandonedReminders();
        
        $this->model_octemplates_module_oct_abandoned_cart->deleteConvertedCarts();

        $this->model_octemplates_module_oct_abandoned_cart->deleteExpiredCarts();

        exit($this->language->get('text_cron_done'));
    }

    public function manualSend() {

        if (!$this->checkModuleStatus()) {
            return;
        }

        $module_settings = $this->config->get('oct_abandoned_cart') ?: [];

        if (empty($module_settings['api_key']) || !preg_match('/^[a-f0-9]{32,}$/i', $module_settings['api_key'])) {
            $this->log->write('Invalid API key in module settings.');
            exit($this->language->get('text_error_invalid_settings'));
        }

        $api_key_req = isset($this->request->post['api_key']) ? $this->sanitizeString($this->request->post['api_key']) : '';
        if ($api_key_req !== $module_settings['api_key']) {
            exit($this->language->get('text_error_invalid_api_key'));
        }

        $cart_id = isset($this->request->post['cart_id']) ? (int)$this->request->post['cart_id'] : 0;
        if ($cart_id <= 0) {
            exit($this->language->get('text_manual_send_nothing'));
        }

        $this->load->model('octemplates/module/oct_abandoned_cart');
        $res = $this->model_octemplates_module_oct_abandoned_cart->sendOneAbandoned($cart_id);

        $this->load->language('octemplates/module/oct_abandoned_cart');

        $response = [];

        if ($res) {
            $response['success'] = true;
            $response['abandoned_cart_id'] = $res['abandoned_cart_id'];
            $response['reminder_count'] = $res['reminder_count'];
            $response['last_reminder'] = $res['last_reminder'];
        } else {
            $response['success'] = false;
            $response['message'] = $this->language->get('text_manual_send_nothing');
        }
        
        header('Content-Type: application/json');
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($response));
    }

    public function restoreCart() {

        if (!$this->checkModuleStatus()) {
            $this->response->redirect($this->url->link('common/home'));
            return;
        }

        if ($this->customer->isLogged()) {
            $this->response->redirect($this->url->link('checkout/checkout', '', true));
            return;
        }

        $this->load->language('octemplates/module/oct_abandoned_cart');

        $restore_token = isset($this->request->get['restore_token']) ? $this->sanitizeString($this->request->get['restore_token']) : '';
        if (!$restore_token) {
            $this->response->redirect($this->url->link('common/home'));
            return;
        }
        
        $restore_token = urldecode($restore_token);

        if (!preg_match('/^[a-f0-9]{32}\|[a-f0-9]{64}$/', $restore_token)) {
            $this->response->redirect($this->url->link('common/home'));
            return;
        }

        list($rawToken, $signature) = explode('|', $restore_token, 2);

        $module_settings = $this->config->get('oct_abandoned_cart') ?: [];
        $lifetime = time() + (int)$module_settings['cookie_lifetime'] * 24 * 60 * 60;
        $api_key = $module_settings['api_key'] ?? '';
        $can_login_by_token = $module_settings['can_login_by_token'] ?? false;

        if (!$this->checkSignature($rawToken, $signature, $api_key)) {
            $this->response->redirect($this->url->link('common/home'));
            return;
        }

        $this->load->model('octemplates/module/oct_abandoned_cart');
        $restored = $this->model_octemplates_module_oct_abandoned_cart->getAbandonedCartData($rawToken, $signature);

        if ($restored && !empty($restored['cart_data'])) {
            
            if (!empty($restored['coupon_code'])) {
                $is_active = $this->model_octemplates_module_oct_abandoned_cart->getPromocodeByCode($restored['coupon_code']);
                if ($is_active) {
                    $this->session->data['coupon'] = $restored['coupon_code'];
                }
            }

            if ($restored['customer_id'] && !$this->customer->isLogged()) {
                if ($can_login_by_token) {
                    $customer_email = $this->model_octemplates_module_oct_abandoned_cart->getEmailByCustomerId($restored['customer_id']);
                    $this->customer->login($customer_email, '', true);
                    $this->response->redirect($this->url->link('checkout/checkout', '', true));
                    return;
                } else {
                    $this->response->redirect($this->url->link('account/login', '', true));
                    return;
                }
            }
        
            $this->load->model('catalog/product');
        
            $cart_data = json_decode($restored['cart_data'], true);
            if (!is_array($cart_data)) {
                $this->session->data['error_warning'] = $this->language->get('text_error_no_cart');
                $this->response->redirect($this->url->link('common/home'));
                return;
            }
                
            $existingProducts = [];
            foreach ($this->cart->getProducts() as $cart_product) {
                $existingProducts[] = (int)$cart_product['product_id'];
            }
            
            foreach ($cart_data as $product) {
                $productId = (int)$product['product_id'];
                
                if (in_array($productId, $existingProducts)) {
                    continue;
                }
                
                $product_info = $this->model_catalog_product->getProduct($productId);
                
                if ($product_info && $product_info['quantity'] > 0) {
                    $options = [];
                    $specific_option_available = true;
            
                    if (!empty($product['option'])) {
                        $product_options = $this->model_catalog_product->getProductOptions($productId);
                        foreach ($product['option'] as $option) {
                            $option_found = false;
                            foreach ($product_options as $product_option) {
                                if ($product_option['product_option_id'] == (int)$option['product_option_id']) {
                                    foreach ($product_option['product_option_value'] as $product_option_value) {
                                        if ($product_option_value['product_option_value_id'] == (int)$option['product_option_value_id'] && $product_option_value['quantity'] > 0) {
                                            $options[$option['product_option_id']] = $option['product_option_value_id'];
                                            $option_found = true;
                                            break;
                                        }
                                    }
                                }
                                if ($option_found) {
                                    break;
                                }
                            }
                            if (!$option_found) {
                                $specific_option_available = false;
                                break;
                            }
                        }
                    }
            
                    if ($specific_option_available) {
                        $this->cart->add($productId, (int)$product['quantity'], $options);
                    }
                }
            }
        
            $this->setCookie($rawToken, $signature, $lifetime);
        
            $this->session->data['success'] = $this->language->get('text_success_restore');
            $this->response->redirect($this->url->link('checkout/checkout', '', true));
            return;
        } else {
            $this->session->data['error_warning'] = $this->language->get('text_error_no_cart');
            $this->response->redirect($this->url->link('common/home'));
            return;
        }
    }

    private function sanitizeString($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    private function checkModuleStatus() {
        $module_settings = $this->config->get('oct_abandoned_cart') ?: [];
        return !empty($module_settings['status']);
    }

    private function prepareAbandonedData($data) {
        if (!isset($data['customer_id'])) {
            $data['customer_id'] = $this->customer->isLogged() ? (int)$this->customer->getId() : 0;
        }
        if (!isset($data['firstname']) || !isset($data['lastname']) || !isset($data['email']) || !isset($data['phone'])) {
            if ($this->customer->isLogged()) {
                $data['firstname'] = $this->sanitizeString($this->customer->getFirstName());
                $data['lastname']  = $this->sanitizeString($this->customer->getLastName());
                $data['email']     = filter_var($this->customer->getEmail(), FILTER_SANITIZE_EMAIL);
                $data['phone']     = $this->sanitizeString($this->customer->getTelephone());
            } elseif (!empty($this->session->data['guest'])) {
                $g = $this->session->data['guest'];
                $data['firstname'] = isset($g['firstname']) ? $this->sanitizeString($g['firstname']) : '';
                $data['lastname']  = isset($g['lastname']) ? $this->sanitizeString($g['lastname']) : '';
                $data['email']     = isset($g['email']) ? filter_var($g['email'], FILTER_SANITIZE_EMAIL) : '';
                $data['phone']     = isset($g['telephone']) ? $this->sanitizeString($g['telephone']) : '';
            } else {
                $data['firstname'] = $data['firstname'] ?? '';
                $data['lastname']  = $data['lastname'] ?? '';
                $data['email']     = $data['email'] ?? '';
                $data['phone']     = $data['phone'] ?? '';
            }
        }

        if (!empty($data['email']) && !$this->validateEmail($data['email'])) {
            $data['email'] = '';
        }
        if (!empty($data['phone']) && !$this->validatePhone($data['phone'])) {
            $data['phone'] = '';
        }

        if (!isset($data['store_id'])) {
            $data['store_id'] = (int)$this->config->get('config_store_id');
        }

        if (!isset($data['store_name'])) {
            $data['store_name'] = $this->sanitizeString($this->config->get('config_name')) ?: 'Default Store';
        }

        if (!isset($data['language_id'])) {
            $data['language_id'] = (int)$this->config->get('config_language_id');
        }

        if (!isset($data['cart_data'])) {
            $data['cart_data'] = $this->makeCartData();
        }
        return $data;
    }

    private function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    private function validatePhone($phone) {
        return preg_match('/^[0-9\-\(\)\/\+\s]*$/', $phone);
    }

    private function makeSignature($rawToken, $apiKey) {
        return hash_hmac('sha256', $rawToken, $apiKey);
    }

    private function checkSignature($rawToken, $signature, $apiKey) {
        $expectedSignature = $this->makeSignature($rawToken, $apiKey);
        return hash_equals($expectedSignature, $signature);
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

    private function setCookie($rawToken, $signature, $lifetime) {
        $value   = $rawToken . '|' . $signature;
        $expires = time() + $lifetime;
        $domain  = $this->request->server['HTTP_HOST'];
        $secure  = isset($this->request->server['HTTPS']) && 
                   ($this->request->server['HTTPS'] === 'on' || $this->request->server['HTTPS'] == 1);
    
        $this->setCookieUnified($this->cookieName, $value, $expires, '/', $domain, $secure, true, 'Strict');
    }

    private function deleteCookie() {
        $domain = $this->request->server['HTTP_HOST'];
        $secure = isset($this->request->server['HTTPS']) && 
                  ($this->request->server['HTTPS'] === 'on' || $this->request->server['HTTPS'] == 1);
    
        $this->setCookieUnified($this->cookieName, '', time() - 3600, '/', $domain, $secure, true, 'Strict');
    }  

    private function makeCartData() {
        $products = $this->cart->getProducts();
        $result = [];
        foreach ($products as $p) {
            $product_options = [];
            if (isset($p['option']) && is_array($p['option'])) {
                foreach ($p['option'] as $option) {
                    $product_options[] = [
                        'product_option_id'       => (int)$option['product_option_id'],
                        'product_option_value_id' => (int)$option['product_option_value_id'],
                        'option_id'               => (int)$option['option_id'],
                        'option_value_id'         => (int)$option['option_value_id'],
                        'name'                    => $this->sanitizeString($option['name']),
                        'value'                   => $this->sanitizeString($option['value']),
                        'type'                    => $this->sanitizeString($option['type'])
                    ];
                }
            }

            $result[] = [
                'product_id' => (int)$p['product_id'],
                'quantity'   => (int)$p['quantity'],
                'name'       => $this->sanitizeString($p['name']),
                'price'      => $p['price'],
                'total'      => $p['total'],
                'option'     => $product_options,
            ];
        }
        return $result;
    }
}