<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOctemplatesModuleOctAbandonedCart extends Controller {
    private $error = [];

    public function index() {
        $this->load->language('octemplates/module/oct_abandoned_cart');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');
        $this->load->model('octemplates/module/oct_abandoned_cart');
        $this->load->model('localisation/language');

        $this->document->addScript('view/javascript/octemplates/bootstrap-notify/bootstrap-notify.min.js');
        $this->document->addScript('view/javascript/octemplates/oct_main.js');
        $this->document->addStyle('view/stylesheet/oct_remarket.css');
        $this->document->addScript('view/javascript/codemirror/lib/codemirror.js');
        $this->document->addScript('view/javascript/codemirror/lib/xml.js');
        $this->document->addScript('view/javascript/codemirror/lib/formatting.js');
        $this->document->addStyle('view/javascript/codemirror/lib/codemirror.css');
        $this->document->addStyle('view/javascript/codemirror/theme/monokai.css');
        $this->document->addScript('view/javascript/summernote/summernote.js');
        $this->document->addScript('view/javascript/summernote/summernote-image-attributes.js');
        $this->document->addScript('view/javascript/summernote/opencart.js');
        $this->document->addStyle('view/javascript/summernote/summernote.css');
        $this->document->addScript('view/javascript/octemplates/bootstrap-notify/bootstrap-notify.min.js');
        $this->document->addScript('view/javascript/octemplates/oct_main.js');
        $this->document->addStyle('view/stylesheet/oct_remarket.css');

        if (!$this->model_setting_setting->getSetting('oct_abandoned_cart')) {
            $this->response->redirect($this->url->link('octemplates/module/oct_abandoned_cart/install', 'user_token=' . $this->session->data['user_token'], true));
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('oct_abandoned_cart', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('octemplates/module/oct_abandoned_cart', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['oct_abandoned_cart'] = isset($this->request->post['oct_abandoned_cart']) 
            ? $this->request->post['oct_abandoned_cart'] 
            : $this->config->get('oct_abandoned_cart');

        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
        $data['error_reminder_hours_first'] = isset($this->error['reminder_hours_first']) ? $this->error['reminder_hours_first'] : '';
        $data['error_reminder_hours_second'] = isset($this->error['reminder_hours_second']) ? $this->error['reminder_hours_second'] : '';
        $data['error_coupon_discount'] = isset($this->error['coupon_discount']) ? $this->error['coupon_discount'] : '';
        $data['error_coupon_lifetime'] = isset($this->error['coupon_lifetime']) ? $this->error['coupon_lifetime'] : '';
        $data['error_coupon_type'] = isset($this->error['coupon_type']) ? $this->error['coupon_type'] : '';
        $data['error_cookie_lifetime'] = isset($this->error['cookie_lifetime']) ? $this->error['cookie_lifetime'] : '';
        $data['error_converted_lifetime'] = isset($this->error['converted_lifetime']) ? $this->error['converted_lifetime'] : '';

        $data['success'] = isset($this->session->data['success']) ? $this->session->data['success'] : '';

        if (isset($data['oct_abandoned_cart']['cron_password']) && !empty($data['oct_abandoned_cart']['cron_password']) && !isset($this->error['cron_password'])) {
            $site_link = (!empty($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] === 'on') || ($this->request->server['HTTPS'] == 1))) ? HTTPS_CATALOG : HTTP_CATALOG;
            $data['cron_url'] = $site_link . "index.php?route=octemplates/module/oct_abandoned_cart/cron&cron_pass=" . urlencode($data['oct_abandoned_cart']['cron_password']);
        } else {
            $data['cron_url'] = '';
        }

        $data['languages'] = $this->model_localisation_language->getLanguages();

        $pagination_params = $this->getPaginationParams();
        $data['carts'] = $this->model_octemplates_module_oct_abandoned_cart->getAbandonedCarts($pagination_params);
        $data['ajax_lost_carts'] = htmlspecialchars_decode($this->url->link('octemplates/module/oct_abandoned_cart/getLostCarts', 'user_token=' . $this->session->data['user_token'], true));
        $data['ajax_convert_cart'] = htmlspecialchars_decode($this->url->link('octemplates/module/oct_abandoned_cart/convertCart', 'user_token=' . $this->session->data['user_token'], true));
        $data['ajax_delete_cart'] = htmlspecialchars_decode($this->url->link('octemplates/module/oct_abandoned_cart/deleteCart', 'user_token=' . $this->session->data['user_token'], true));

        $site_link = (isset($this->request->server['HTTPS']) && ($this->request->server['HTTPS'] == 'on' || $this->request->server['HTTPS'] == 1)) ? HTTPS_CATALOG : HTTP_CATALOG;
        $data['ajax_send_email'] = $site_link . "index.php?route=octemplates/module/oct_abandoned_cart/manualSend";

        $pagination = new Pagination();
        $pagination->total = $this->model_octemplates_module_oct_abandoned_cart->getTotalAbandonedCarts();
        $pagination->page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
        $pagination->limit = 10;
        $pagination->url = $this->url->link('octemplates/module/oct_abandoned_cart', 'user_token=' . $this->session->data['user_token'] . '&page={page}', true);

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf(
            $this->language->get('text_pagination'),
            ($pagination->total) ? (($pagination->page - 1) * $pagination->limit) + 1 : 0,
            ((($pagination->page - 1) * $pagination->limit) > ($pagination->total - $pagination->limit)) ? $pagination->total : (((($pagination->page - 1) * $pagination->limit) + $pagination->limit)),
            $pagination->total,
            ceil($pagination->total / $pagination->limit)
        );

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_edit'] = $this->language->get('text_edit');
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        $data['breadcrumbs'] = [
            [
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
            ],
            [
                'text' => $this->language->get('text_extension'),
                'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
            ],
            [
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('octemplates/module/oct_abandoned_cart', 'user_token=' . $this->session->data['user_token'], true)
            ]
        ];

        $data['header']       = $this->load->controller('common/header');
        $data['column_left']  = $this->load->controller('common/column_left');
        $data['footer']       = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('octemplates/module/oct_abandoned_cart', $data));
    }

    public function getLostCarts() {
        $this->load->model('octemplates/module/oct_abandoned_cart');
        $this->load->language('octemplates/module/oct_abandoned_cart');
        $json = [];

        $can_edit = $this->user->hasPermission('modify', 'octemplates/module/oct_abandoned_cart');

        if ($this->request->server['REQUEST_METHOD'] == 'GET') {

            $filter_firstname = isset($this->request->get['filter_firstname']) ? $this->sanitize($this->request->get['filter_firstname']) : '';
            $filter_lastname  = isset($this->request->get['filter_lastname']) ? $this->sanitize($this->request->get['filter_lastname']) : '';
            $filter_email     = isset($this->request->get['filter_email']) ? $this->sanitize($this->request->get['filter_email']) : '';
            $filter_phone     = isset($this->request->get['filter_phone']) ? $this->sanitize($this->request->get['filter_phone']) : '';
            $filter_status    = isset($this->request->get['filter_status']) ? $this->sanitize($this->request->get['filter_status']) : '';
            $filter_ip_added  = isset($this->request->get['filter_ip_added']) ? $this->sanitize($this->request->get['filter_ip_added']) : '';
            $filter_ip_changed= isset($this->request->get['filter_ip_changed']) ? $this->sanitize($this->request->get['filter_ip_changed']) : '';
            
            $filter_date_added_start    = isset($this->request->get['filter_date_added_start']) ? $this->sanitize($this->request->get['filter_date_added_start']) : '';
            $filter_date_added_end      = isset($this->request->get['filter_date_added_end']) ? $this->sanitize($this->request->get['filter_date_added_end']) : '';
            $filter_date_modified_start = isset($this->request->get['filter_date_modified_start']) ? $this->sanitize($this->request->get['filter_date_modified_start']) : '';
            $filter_date_modified_end   = isset($this->request->get['filter_date_modified_end']) ? $this->sanitize($this->request->get['filter_date_modified_end']) : '';

            $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
            $limit = $this->config->get('config_limit_admin');
            $start = ($page - 1) * $limit;

            $filter_data = [
                'filter_firstname'           => $filter_firstname,
                'filter_lastname'            => $filter_lastname,
                'filter_email'               => $filter_email,
                'filter_phone'               => $filter_phone,
                'filter_status'              => $filter_status,
                'filter_ip_added'            => $filter_ip_added,
                'filter_ip_changed'          => $filter_ip_changed,
                'filter_date_added_start'    => $filter_date_added_start,
                'filter_date_added_end'      => $filter_date_added_end,
                'filter_date_modified_start' => $filter_date_modified_start,
                'filter_date_modified_end'   => $filter_date_modified_end,
                'start'                      => $start,
                'limit'                      => $limit
            ];

            $carts = $this->model_octemplates_module_oct_abandoned_cart->getAbandonedCarts($filter_data);
            $cart_total = $this->model_octemplates_module_oct_abandoned_cart->getTotalAbandonedCarts($filter_data);

            if ($carts) {
                $data['carts'] = [];
                $cart_item = ($page - 1) * $limit;

                $this->load->model('catalog/product');
                $this->load->model('tool/image');

                foreach ($carts as $cart) {
                    $cart_item++;

                    $status_html = $cart['status'] == 'active'
                        ? '<span class="label label-warning">' . $this->language->get('text_active') . '</span>'
                        : '<span class="label label-success">' . $this->language->get('text_converted') . '</span>';

                    $ip_added_link = '';
                    if (!empty($cart['ip_added'])) {
                        $ip_added_link = '<b>ip: </b><a href="https://whatismyipaddress.com/ip/' . $cart['ip_added'] . '" target="_blank" rel="noopener noreferrer">'
                                       . $cart['ip_added'] . '</a>';
                    }

                    $ip_changed_link = '';
                    if (!empty($cart['ip_changed'])) {
                        $ip_changed_link = '<b>ip: </b><a href="https://whatismyipaddress.com/ip/' . $cart['ip_changed'] . '" target="_blank" rel="noopener noreferrer">'
                                        . $cart['ip_changed'] . '</a>';
                    }

                    $customer_link = '';
                    if (!empty($cart['customer_id'])) {
                        $customer_link = $this->url->link(
                            'customer/customer/edit',
                            'user_token=' . $this->session->data['user_token'] . '&customer_id=' . $cart['customer_id'],
                            true
                        );
                    }

                    $cart_data = json_decode($cart['cart_data'], true);
                    if (!is_array($cart_data)) {
                        $this->log->write('Error decoding cart_data for abandoned_cart_id: ' . $cart['abandoned_cart_id']);
                        $products = [];
                    } else {
                        $products = [];
                        foreach ($cart_data as $product) {
                            $product_info = $this->model_catalog_product->getProduct($product['product_id']);
                            if ($product_info) {
                                $product_image = $this->model_tool_image->resize($product_info['image'], 40, 40);
                                $product_link = $this->url->link(
                                    'catalog/product/edit', 
                                    'user_token=' . $this->session->data['user_token'] . '&product_id=' . $product['product_id'], 
                                    true
                                );
                                $options_arr = [];
                                if (!empty($product['option'])) {
                                    foreach ($product['option'] as $option) {
                                        $options_arr[] = [
                                            'name'  => $option['name'],
                                            'value' => $option['value']
                                        ];
                                    }
                                }
                                $products[] = [
                                    'product_id' => $product['product_id'],
                                    'name'       => $product_info['name'],
                                    'thumb'      => $product_image,
                                    'edit_link'  => $product_link,
                                    'quantity'   => $product['quantity'],
                                    'options'    => $options_arr
                                ];
                            }
                        }
                    }

                    $host = (isset($this->request->server['HTTPS']) && ($this->request->server['HTTPS'] == 'on' || $this->request->server['HTTPS'] == 1)) ? HTTPS_CATALOG : HTTP_CATALOG;
                    $restore_link = $host . 'index.php?route=octemplates/module/oct_abandoned_cart/restoreCart&restore_token=' 
                                  . urlencode($cart['cookie_token'] . '|' . $cart['cookie_signature']);

                    $data['carts'][] = [
                        'abandoned_cart_id' => $cart['abandoned_cart_id'],
                        'cart_item'         => $cart_item,
                        'firstname'         => htmlspecialchars($cart['firstname'], ENT_QUOTES),
                        'lastname'          => htmlspecialchars($cart['lastname'], ENT_QUOTES),
                        'email'             => htmlspecialchars($cart['email'], ENT_QUOTES),
                        'phone'             => htmlspecialchars($cart['phone'], ENT_QUOTES),
                        'customer_id'       => $cart['customer_id'],
                        'customer_link'     => $customer_link,
                        'products'          => $products,
                        'date_modified'     => $cart['date_modified'],
                        'date_added'        => $cart['date_added'],
                        'last_reminder'     => isset($cart['last_reminder']) ? $cart['last_reminder'] : $this->language->get('text_never'),
                        'reminder_count'    => $cart['reminder_count'],
                        'ip_added'          => $ip_added_link,
                        'ip_changed'        => $ip_changed_link,
                        'status_html'       => $status_html,
                        'restore_link'      => $restore_link,
                        'can_edit'          => $can_edit
                    ];
                }

                $data['text_quantity']       = $this->language->get('text_quantity');
                $data['text_view_profile']   = $this->language->get('text_view_profile');
                $data['button_copy_link']    = $this->language->get('button_copy_link');
                $data['button_convert']      = $this->language->get('button_convert');
                $data['button_delete']       = $this->language->get('button_delete');
                $data['button_send_email']   = $this->language->get('button_send_email');

                $pagination = new Pagination();
                $pagination->total = $cart_total;
                $pagination->page = $page;
                $pagination->limit = $limit;
                $pagination->url = $this->url->link(
                    'octemplates/module/oct_abandoned_cart/getLostCarts',
                    'user_token=' . $this->session->data['user_token'] 
                    . '&filter_firstname=' . urlencode($filter_firstname)
                    . '&filter_lastname=' . urlencode($filter_lastname)
                    . '&filter_email=' . urlencode($filter_email)
                    . '&filter_phone=' . urlencode($filter_phone)
                    . '&filter_status=' . urlencode($filter_status)
                    . '&filter_ip_added=' . urlencode($filter_ip_added)
                    . '&filter_ip_changed=' . urlencode($filter_ip_changed)
                    . '&filter_date_added_start=' . urlencode($filter_date_added_start)
                    . '&filter_date_added_end=' . urlencode($filter_date_added_end)
                    . '&filter_date_modified_start=' . urlencode($filter_date_modified_start)
                    . '&filter_date_modified_end=' . urlencode($filter_date_modified_end)
                    . '&page={page}',
                    true
                );

                $html_rows = $this->load->view('octemplates/module/oct_abandoned_cart_lost_carts', $data);

                $json['success'] = true;
                $json['html'] = $html_rows;
                $json['pagination'] = $pagination->render();
                $json['results'] = sprintf(
                    $this->language->get('text_pagination'),
                    ($cart_total) ? (($page - 1) * $limit) + 1 : 0,
                    ((($page - 1) * $limit) > ($cart_total - $limit)) ? $cart_total : ((($page - 1) * $limit) + $limit),
                    $cart_total,
                    ceil($cart_total / $limit)
                );

            } else {
                $json['success'] = false;
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function convertCart() {
        $this->load->language('octemplates/module/oct_abandoned_cart');

        if (!$this->user->hasPermission('modify', 'octemplates/module/oct_abandoned_cart')) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(['error' => $this->language->get('error_permission')]));
            return;
        }

        $json = [];

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $cart_id = isset($this->request->post['cart_id']) ? (int)$this->request->post['cart_id'] : 0;

            if ($cart_id) {
                $this->load->model('octemplates/module/oct_abandoned_cart');
                $result = $this->model_octemplates_module_oct_abandoned_cart->convertCart($cart_id);

                if ($result) {
                    $json['success'] = $this->language->get('text_convert_success');
                } else {
                    $json['error'] = $this->language->get('error_convert_failed');
                }
            } else {
                $json['error'] = $this->language->get('error_invalid_cart_id');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function deleteCart() {
        $this->load->language('octemplates/module/oct_abandoned_cart');

        if (!$this->user->hasPermission('modify', 'octemplates/module/oct_abandoned_cart')) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(['error' => $this->language->get('error_permission')]));
            return;
        }

        $json = [];

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $cart_id = isset($this->request->post['cart_id']) ? (int)$this->request->post['cart_id'] : 0;

            if ($cart_id) {
                $this->load->model('octemplates/module/oct_abandoned_cart');
                $result = $this->model_octemplates_module_oct_abandoned_cart->deleteCart($cart_id);

                if ($result) {
                    $json['success'] = $this->language->get('text_delete_success');
                } else {
                    $json['error'] = $this->language->get('error_delete_failed');
                }
            } else {
                $json['error'] = $this->language->get('error_invalid_cart_id');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function install() {
        $this->load->language('octemplates/module/oct_abandoned_cart');
        if (!$this->user->hasPermission('modify', 'octemplates/module/oct_abandoned_cart')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->load->model('octemplates/module/oct_abandoned_cart');
        $this->model_octemplates_module_oct_abandoned_cart->install();

        $default_settings = [
            'oct_abandoned_cart' => [
                'status' => 1,
                'can_login_by_token' => 0,
                'reminder_hours_first' => 0,
                'reminder_hours_second' => 0,
                'coupon_status' => 1,
                'coupon_discount' => 100,
                'coupon_lifetime' => 7,
                'coupon_type' => 'fixed',
                'cookie_lifetime' => 10,
                'converted_lifetime' => 45,
                'api_key' => bin2hex(random_bytes(16)),
                'cron_password' => substr(bin2hex(random_bytes(8)), 0, 16),
                'email_template_status' => 0,
                'email_template' => ''
            ]
        ];

        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('oct_abandoned_cart', $default_settings);

        $this->session->data['success'] = $this->language->get('text_install_success');

        $this->response->redirect($this->url->link('octemplates/module/oct_abandoned_cart', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function uninstall() {
        if (!$this->user->hasPermission('modify', 'octemplates/module/oct_abandoned_cart')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->load->model('octemplates/module/oct_abandoned_cart');
        $this->model_octemplates_module_oct_abandoned_cart->uninstall();

        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('oct_abandoned_cart');

        $this->session->data['success'] = $this->language->get('text_uninstall_success');

        $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
    }

    private function getPaginationParams() {
        $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
        $limit = 10;
        $start = ($page - 1) * $limit;
        return [
            'start' => $start,
            'limit' => $limit
        ];
    }

    private function sanitize($value) {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'octemplates/module/oct_abandoned_cart')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
    
        $numericFields = [
            'reminder_hours_first',
            'reminder_hours_second',
            'coupon_discount',
            'coupon_lifetime',
            'cookie_lifetime',
            'converted_lifetime'
        ];
    
        foreach ($numericFields as $field) {
            if (!isset($this->request->post['oct_abandoned_cart'][$field]) 
                || !is_numeric($this->request->post['oct_abandoned_cart'][$field])) {
                $this->error[$field] = $this->language->get('error_complete_all_fields');
            }
        }
    
        if (!isset($this->request->post['oct_abandoned_cart']['coupon_type']) 
            || !in_array($this->request->post['oct_abandoned_cart']['coupon_type'], ['percent', 'fixed'])) {
            $this->error['coupon_type'] = $this->language->get('error_complete_all_fields');
        }
    
        return !$this->error;
    }
}