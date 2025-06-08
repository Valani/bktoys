<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ModelOctemplatesModuleOctAbandonedCart extends Model {

    public function getAbandonedCartByCustomerId($customer_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "oct_abandoned_cart`
            WHERE customer_id = '" . (int)$customer_id . "'
              AND status = 'active'
            LIMIT 1");

        return $query->num_rows ? $query->row : false;
    }

    public function getAbandonedCart($rawToken, $signature) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "oct_abandoned_cart`
            WHERE cookie_token = '" . $this->db->escape($rawToken) . "'
              AND cookie_signature = '" . $this->db->escape($signature) . "'
              AND status = 'active'
            LIMIT 1");

        return $query->num_rows ? $query->row : false;
    }

    public function getAbandonedCartGuests($rawToken, $signature) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "oct_abandoned_cart`
            WHERE cookie_token = '" . $this->db->escape($rawToken) . "'
              AND cookie_signature = '" . $this->db->escape($signature) . "' 
              AND customer_id = 0 
              AND status = 'active' 
            LIMIT 1");

        return $query->num_rows ? $query->row : false;
    }

    public function assignCartToCustomer($abandoned_cart_id, $customer_id) {
        $this->db->query("UPDATE `" . DB_PREFIX . "oct_abandoned_cart`
            SET customer_id = '" . (int)$customer_id . "'
            WHERE abandoned_cart_id = '" . (int)$abandoned_cart_id . "'");
    }

    public function deleteAbandonedCart($abandoned_cart_id) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "oct_abandoned_cart`
            WHERE abandoned_cart_id = '" . (int)$abandoned_cart_id . "'");
    }

    public function getAbandonedCartsWithContacts($data = []) {
        $sql = "SELECT * FROM `" . DB_PREFIX . "oct_abandoned_cart` 
                WHERE (email != '' OR phone != '') 
                  AND status = 'active'";
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function markCartAsConvertedByCustomerId($customer_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "oct_abandoned_cart`
            WHERE customer_id = '" . (int)$customer_id . "'
              AND status = 'active'
            LIMIT 1");
    
        if ($query->num_rows) {
            $this->db->query("UPDATE `" . DB_PREFIX . "oct_abandoned_cart`
                SET status = 'converted',
                    date_modified = NOW()
                WHERE customer_id = '" . (int)$customer_id . "'
                  AND status = 'active'");
        }
    }

    public function markCartAsConverted($rawToken, $signature) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "oct_abandoned_cart`
            WHERE cookie_token = '" . $this->db->escape($rawToken) . "'
              AND cookie_signature = '" . $this->db->escape($signature) . "'
              AND status = 'active'
            LIMIT 1");

        if ($query->num_rows) {
            $this->db->query("UPDATE `" . DB_PREFIX . "oct_abandoned_cart`
                SET status = 'converted',
                    date_modified = NOW()
                WHERE cookie_token = '" . $this->db->escape($rawToken) . "'
                  AND cookie_signature = '" . $this->db->escape($signature) . "'
                  AND status = 'active'");
        }
    }

    public function getAbandonedCartData($rawToken, $signature) {
        $module_settings = $this->config->get('oct_abandoned_cart') ?: [];
        $cookie_lifetime_days = (int)($module_settings['cookie_lifetime'] ?? 10);
        $sql = "SELECT * FROM `" . DB_PREFIX . "oct_abandoned_cart`
                WHERE cookie_token = '" . $this->db->escape($rawToken) . "'
                  AND cookie_signature = '" . $this->db->escape($signature) . "'
                  AND status = 'active'
                  AND date_added >= (NOW() - INTERVAL $cookie_lifetime_days DAY)
                LIMIT 1";
        $query = $this->db->query($sql);
        if (!$query->num_rows) {
            return false;
        }

        $row = $query->row;
        $cart_data = json_decode($row['cart_data'], true);
        if (!is_array($cart_data)) {
            $this->log->write('Error decoding cart_data for abandoned_cart_id: ' . $row['abandoned_cart_id'] . '. Data: ' . $row['cart_data']);
            return false;
        }

        return $row;
    }    

    public function deleteConvertedCarts() {
        $module_settings = $this->config->get('oct_abandoned_cart') ?: [];
        $converted_lifetime = (int)($module_settings['converted_lifetime'] ?? 45);

        if ($converted_lifetime <= 0) {
            return;
        }

        $sql = "
            DELETE FROM `" . DB_PREFIX . "oct_abandoned_cart`
            WHERE status = 'converted'
              AND date_added < (NOW() - INTERVAL $converted_lifetime DAY)
        ";
        $this->db->query($sql);
    }

    public function deleteExpiredCarts() {
        $module_settings = $this->config->get('oct_abandoned_cart') ?: [];
        $cookie_lifetime_days = (int)($module_settings['cookie_lifetime'] ?? 10);

        if ($cookie_lifetime_days <= 0) {
            return;
        }

        $sql = "
            DELETE FROM `" . DB_PREFIX . "oct_abandoned_cart`
            WHERE date_added < (NOW() - INTERVAL $cookie_lifetime_days DAY)
        ";
        $this->db->query($sql);
    }

    public function getPromocodeByCode($code) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon`
            WHERE code = '" . $this->db->escape($code) . "'
              AND status = 1
              AND date_start <= NOW()
              AND date_end >= NOW()
            LIMIT 1");

        return $query->num_rows ? $query->row : false;
    }

    private function sendOneAbandonedPrivate($row, $wave_index, $cart_id) {
        $notification_sent = false;

        $this->load->language('octemplates/module/oct_abandoned_cart');
        $module_settings = $this->config->get('oct_abandoned_cart') ?: [];

        if (!isset($module_settings['status'])) {
            return $notification_sent;
        }

        $email = $row['email'];
        $sms_promo_code = '';

        $sql_cart = "SELECT language_id, coupon_code FROM `" . DB_PREFIX . "oct_abandoned_cart` WHERE abandoned_cart_id = '" . (int)$cart_id . "'";
        $query = $this->db->query($sql_cart);
        $current_language_id = $query->row['language_id'] ? $query->row['language_id'] : $this->config->get('config_language_id');

        if ($query->num_rows && !empty($query->row['coupon_code'])) {
            $promo_code = $query->row['coupon_code'];
            $sms_promo_code = $promo_code;
        } else {
            $promo_code = $this->generatePromoCode();
            if ($promo_code) {
                $this->db->query("
                    UPDATE `" . DB_PREFIX . "oct_abandoned_cart`
                    SET coupon_code = '" . $this->db->escape($promo_code) . "'
                    WHERE abandoned_cart_id = '" . (int)$cart_id . "'
                ");
            }
            $sms_promo_code = $promo_code;
        }

        $restore_link = $this->url->link('octemplates/module/oct_abandoned_cart/restoreCart', 'restore_token=' . $row['cookie_token'] . '|' . $row['cookie_signature'], true);

        $this->load->model('account/customer');
        if (!empty($row['customer_id'])) {
            $customer_info = $this->model_account_customer->getCustomer($row['customer_id']);
            if ($customer_info) {
                $firstname = $customer_info['firstname'];
                $lastname = $customer_info['lastname'];
                $email = $customer_info['email'];
            } else {
                $firstname = $row['firstname'];
                $lastname = $row['lastname'];
                $email = $row['email'];
            }
        } else {
            $firstname = $row['firstname'];
            $lastname = $row['lastname'];
            $email = $row['email'];
        }
        
        $combined_name = $firstname . ' ' . $lastname;

        if (!empty($email) && $email != $this->config->get('config_email')) {
            $products = [];
            $this->load->model('catalog/product');
            $this->load->model('tool/image');

            $cart_data = json_decode($row['cart_data'], true);
            if (!is_array($cart_data)) {
                $this->log->write('Error decoding cart_data in sendOneAbandonedPrivate for abandoned_cart_id: ' . $cart_id);
                return;
            }
            $currency_code = $this->session->data['currency'];

            foreach ($cart_data as $item) {
                $product_info = $this->model_catalog_product->getProduct($item['product_id']);
                if ($product_info) {
                    $product_image = $this->model_tool_image->resize($product_info['image'], 40, 40);
                    $product_name = $product_info['name'];
                    $product_quantity = $item['quantity'];
                    $product_price = $product_info['price'];
                    $product_special = $product_info['special'];
                    $product_discounts = $this->model_catalog_product->getProductDiscounts($item['product_id']);
                    $product_options = [];
                    $option_price = 0;

                    if (!empty($item['option'])) {
                        $product_options_data = $this->model_catalog_product->getProductOptions($item['product_id']);
                        foreach ($item['option'] as $option) {
                            foreach ($product_options_data as $product_option) {
                                if ($product_option['product_option_id'] == $option['product_option_id']) {
                                    foreach ($product_option['product_option_value'] as $product_option_value) {
                                        if ($product_option_value['product_option_value_id'] == $option['product_option_value_id']) {
                                            $product_options[] = [
                                                'name' => $product_option_value['name'],
                                                'value' => $product_option_value['name'],
                                                'price' => $product_option_value['price']
                                            ];
                                            if ($product_option_value['price_prefix'] == '+') {
                                                $option_price += (float)$product_option_value['price'];
                                            } else {
                                                $option_price -= (float)$product_option_value['price'];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    $product_special_price = !empty($product_special) ? $product_special : null;
                    $product_discount_price = null;
                    if (!empty($product_discounts)) {
                        foreach ($product_discounts as $discount) {
                            if ($discount['quantity'] <= $product_quantity) {
                                $product_discount_price = $discount['price'];
                            }
                        }
                    }

                    if ($product_special_price !== null) {
                        $product_price = (float)$product_special_price;
                    } elseif ($product_discount_price !== null) {
                        $product_price = (float)$product_discount_price;
                    }

                    $total_price = ($product_price + $option_price) * $product_quantity;

                    $products[] = [
                        'image' => $product_image,
                        'name' => $product_name,
                        'quantity' => $product_quantity,
                        'options' => $product_options,
                        'price' => $this->currency->format($total_price, $currency_code)
                    ];
                }
            }

            $subject = $this->language->get('email_subject');
            $email_message_template = $this->language->get('email_message');

            if (!empty($module_settings['email_template_status']) && $module_settings['email_template_status'] == 'on') {
                if (!empty($module_settings['email_template'][$current_language_id])) {
                    $subject = $module_settings['email_template'][$current_language_id]['subject'];
                    $email_message_template = html_entity_decode($module_settings['email_template'][$current_language_id]['body']);
                } else {
                    $subject = $this->language->get('email_subject');
                    $email_message_template = $this->language->get('email_message');
                }
            } else {
                $subject = $this->language->get('email_subject');
                $email_message_template = $this->language->get('email_message');
            }

            $products_list = '';
            foreach ($products as $product) {
                $product_options = '';
                if (!empty($product['options'])) {
                    foreach ($product['options'] as $option) {
                        $product_options .= '<div style="margin: 12px 0 12px 0; color: #666;">'. $option['value'] . '</div>';
                    }
                }

                $new_price = '<span style="font-weight: bold;">' . $product['price'] . '</span>';

                $products_list .= '<div style="margin: 20px; 10px; padding: 10px; border: 1px solid #f0f0f0;">';
                $products_list .= '<img src="' . $product['image'] . '" alt="' . $product['name'] . '" style="max-width: 100px; display: block;">';
                $products_list .= '<div style="margin: 12px 0 12px 0;"><strong>' . $product['name'] . '</strong></div>';
                $products_list .= '<div style="margin: 12px 0 12px 0;">Ціна: ' . $new_price . '</div>';
                $products_list .= $product_options;
                $products_list .= '</div>';
            }

            if ($promo_code) {
                $promo_code = '<br><div style="margin: 8px 0px;"> '.$this->language->get('email_promo_code').' <span style="color: red; font-weight: bold;">' . $promo_code . '</span></div>';
            } else {
                $promo_code = '';
            }

            $placeholders = [
                '[customer_name]' => $combined_name,
                '[restore_link]' => $restore_link,
                '[products]' => $products_list,
                '[store_name]' => $this->config->get('config_name'),
                '[promo_code]' => $promo_code 
            ];

            $message = str_replace(array_keys($placeholders), array_values($placeholders), $email_message_template);

            $mail = new Mail($this->config->get('config_mail_engine'));
            $mail->protocol = $this->config->get('config_mail_protocol');
            $mail->parameter = $this->config->get('config_mail_parameter');
            $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
            $mail->smtp_username = $this->config->get('config_mail_smtp_username');
            $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
            $mail->smtp_port = $this->config->get('config_mail_smtp_port');
            $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

            $mail->setTo($email);
            $mail->setFrom($this->config->get('config_email'));
            $mail->setSender($this->config->get('config_name'));
            $mail->setSubject($subject);
            $mail->setHtml($message);
            try {
                $mail->send();
                $notification_sent = true;
            } catch (Exception $e) {
                $this->log->write('Error sending abandoned cart email: ' . $e->getMessage());
            }
        }

        $phone = $row['phone'] ?? '';
        if (!empty($phone)) {
            $oct_sms_settings = $this->config->get('oct_sms_settings');
            $template_code = 'oct_abandoned_cart';

            if (isset($oct_sms_settings['templates'][$template_code]['status']) && $oct_sms_settings['templates'][$template_code]['status']) {
                if (isset($oct_sms_settings['templates'][$template_code]['message'][$current_language_id]) && !empty($oct_sms_settings['templates'][$template_code]['message'][$current_language_id]) && isset($oct_sms_settings['templates'][$template_code]['edit_localization'])) {
                    $sms_template = $oct_sms_settings['templates'][$template_code]['message'][$current_language_id];
                } else {
                    if (!empty($sms_promo_code)) {
                        $sms_template = $this->language->get('default_sms_template_vs_promocode');
                    } else {
                        $sms_template = $this->language->get('default_sms_template');
                    }
                }

                if (!empty($sms_template)) {
                    $replace = array(
                        '[customer_name]' => $combined_name,
                        '[restore_link]' => $restore_link,
                        '[store_name]' => $this->config->get('config_name'),
                        '[promo_code]' => $sms_promo_code 
                    );

                    $sms_message = str_replace(array_keys($replace), array_values($replace), $sms_template);

                    $this->load->model('octemplates/module/oct_sms_notify');
                    $this->model_octemplates_module_oct_sms_notify->sendNotification(array(
                        'phone' => $phone,
                        'message' => $sms_message,
                        'template_code' => $template_code,
                        'access_token' => $oct_sms_settings['oct_sms_token']
                    ));

                    $notification_sent = true;
                }
            }
        }
        
        return $notification_sent;
    }

    public function sendAbandonedReminders() {
        $module_settings = $this->config->get('oct_abandoned_cart') ?: [];
        if (empty($module_settings['status'])) {
            return;
        }
    
        $waves = [];
        $waves[] = [
            'enabled'        => (isset($module_settings['reminder_hours_first']) && $module_settings['reminder_hours_first'] != 0) ? 1 : 0,
            'reminder_count' => 0,
            'hours_after'    => (int)($module_settings['reminder_hours_first'] ?? 0),
            'wave_index'     => 1
        ];
        $waves[] = [
            'enabled'        => (isset($module_settings['reminder_hours_second']) && $module_settings['reminder_hours_second'] != 0) ? 1 : 0,
            'reminder_count' => 1,
            'hours_after'    => (int)($module_settings['reminder_hours_second'] ?? 0),
            'wave_index'     => 2
        ];
    
        foreach ($waves as $wave) {
            if (!$wave['enabled']) continue;
            $this->processReminderWave($wave);
        }
    }
    
    private function processReminderWave($wave) {
        $reminder_count = (int)$wave['reminder_count'];
        $hours_after    = (int)$wave['hours_after'];
        $wave_index     = (int)$wave['wave_index'];
    
        if (!$hours_after) {
            return;
        }
    
        $sql = "
            SELECT * FROM `" . DB_PREFIX . "oct_abandoned_cart`
            WHERE status = 'active'
              AND reminder_count = $reminder_count
              AND (last_reminder IS NULL OR last_reminder < (NOW() - INTERVAL $hours_after HOUR))
        ";
        $query = $this->db->query($sql);
    
        foreach ($query->rows as $row) {
            $notification_sent = $this->sendOneAbandonedPrivate($row, $wave_index, $row['abandoned_cart_id']);

            if ($notification_sent) {
                $this->db->query("
                    UPDATE `" . DB_PREFIX . "oct_abandoned_cart`
                    SET reminder_count = reminder_count + 1,
                        last_reminder = NOW()
                    WHERE abandoned_cart_id = '" . (int)$row['abandoned_cart_id'] . "'
                ");
            }
        }
    }

    public function sendOneAbandoned($cart_id) {
        $this->load->language('octemplates/module/oct_abandoned_cart');
        
        $sql = "SELECT * FROM `" . DB_PREFIX . "oct_abandoned_cart`
                WHERE abandoned_cart_id = '" . (int)$cart_id . "'";
        $query = $this->db->query($sql);
        if (!$query->num_rows) {
            return false;
        }
        $row = $query->row;
        if ($row['status'] !== 'active') {
            return false;
        }
    
        $wave_index = ($row['reminder_count'] == 0) ? 1 : 2;
    
        $notification_sent = $this->sendOneAbandonedPrivate($row, $wave_index, $row['abandoned_cart_id']);
        if ($notification_sent) {
            $this->db->query("
                UPDATE `" . DB_PREFIX . "oct_abandoned_cart`
                SET reminder_count = reminder_count + 1,
                    last_reminder = NOW()
                WHERE abandoned_cart_id = '" . (int)$row['abandoned_cart_id'] . "'
            ");
        
            return [
                'abandoned_cart_id' => $row['abandoned_cart_id'],
                'reminder_count' => $row['reminder_count'] + 1,
                'last_reminder' => date('Y-m-d H:i:s')
            ];

        } else {
            return false;
        }
    }

    public function getCartDataForAutoRestore($rawToken, $signature) {
        $sql = "SELECT * FROM `" . DB_PREFIX . "oct_abandoned_cart`
                WHERE cookie_token = '" . $this->db->escape($rawToken) . "'
                  AND cookie_signature = '" . $this->db->escape($signature) . "'
                  AND status = 'active'
                LIMIT 1";
        $query = $this->db->query($sql);

        if (!$query->num_rows) {
            return false;
        }

        $row = $query->row;
        $cart_data = json_decode($row['cart_data'], true);
        if (!is_array($cart_data)) {
            $this->log->write('Error decoding cart_data in getCartDataForAutoRestore for token: ' . $rawToken);
            return false;
        }

        return $row;
    }

    public function saveAbandonedCartData($data) {
        $module_settings = $this->config->get('oct_abandoned_cart') ?: [];
        $apiKey = $module_settings['api_key'] ?? '';

        if (empty($data['cookie_token']) || empty($data['cookie_signature'])) {
            try {
                $rawToken = bin2hex(random_bytes(16));
            } catch (Exception $e) {
                $this->log->write('Error generating token: ' . $e->getMessage());
                return;
            }

            $signature = $this->makeSignature($rawToken, $apiKey);

            $lifetime = time() + (int)$module_settings['cookie_lifetime'] * 24 * 60 * 60;

            setcookie(
                'oct_abandoned_cart_token',
                $rawToken . '|' . $signature,
                [
                    'expires' => $lifetime,
                    'path' => '/',
                    'domain' => $this->request->server['HTTP_HOST'],
                    'secure' => isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == 1),
                    'httponly' => true,
                    'samesite' => 'Strict',
                ]
            );

            $data['cookie_token'] = $rawToken;
            $data['cookie_signature'] = $signature;
        }

        if (!$this->checkSignature($data['cookie_token'], $data['cookie_signature'], $apiKey)) {
            return;
        }

        $customer_id = (int)($data['customer_id'] ?? 0);
        $firstname = $this->db->escape($data['firstname'] ?? '');
        $lastname = $this->db->escape($data['lastname'] ?? '');
        $email = $this->db->escape($data['email'] ?? '');
        $phone = $this->db->escape($data['phone'] ?? '');

        $store_id = (int)($data['store_id'] ?? 0);
        $store_name = $this->db->escape($data['store_name'] ?? 'Store');
        $language_id = (int)($data['language_id'] ?? 0);

        $cartData = $data['cart_data'] ?? [];
        if (empty($cartData)) {
            return;
        }
        
        $cart_json = json_encode($cartData);
        if ($cart_json === false) {
            $this->log->write('Error encoding cart_data: ' . json_last_error_msg());
            return;
        }

        if ($customer_id > 0) {
            $sql = "SELECT * FROM " . DB_PREFIX . "oct_abandoned_cart
                    WHERE customer_id = '" . $customer_id . "'
                      AND status = 'active'
                    LIMIT 1";

            $this->db->query("
                DELETE FROM " . DB_PREFIX . "oct_abandoned_cart
                WHERE customer_id = 0
                  AND status = 'active'
                  AND cookie_token = '" . $this->db->escape($data['cookie_token']) . "'
                  AND cookie_signature = '" . $this->db->escape($data['cookie_signature']) . "'
            ");
        } else {
            $sql = "SELECT * FROM " . DB_PREFIX . "oct_abandoned_cart
                    WHERE cookie_token = '" . $this->db->escape($data['cookie_token']) . "'
                      AND cookie_signature = '" . $this->db->escape($data['cookie_signature']) . "'
                      AND status = 'active'
                    LIMIT 1";
        }

        $query = $this->db->query($sql);

        $customer_ip = $this->request->server['REMOTE_ADDR'];

        if ($query->num_rows) {
            $cart_id = (int)$query->row['abandoned_cart_id'];
            $update_fields = [];
            
            if (!empty($customer_id)) {
                $update_fields[] = "customer_id = " . (int)$customer_id;
            }
            if (!empty($firstname)) {
                $update_fields[] = "firstname = '" . $this->db->escape($firstname) . "'";
            }
            if (!empty($lastname)) {
                $update_fields[] = "lastname = '" . $this->db->escape($lastname) . "'";
            }
            if (!empty($email)) {
                $update_fields[] = "email = '" . $this->db->escape($email) . "'";
            }
            if (!empty($phone)) {
                $update_fields[] = "phone = '" . $this->db->escape($phone) . "'";
            }
            if (!empty($customer_ip)) {
                $update_fields[] = "ip_changed = '" . $this->db->escape($customer_ip) . "'";
            }
            
            $update_fields[] = "cart_data = '" . $this->db->escape($cart_json) . "'";
            $update_fields[] = "date_modified = NOW()";
            
            $update_sql = implode(", ", $update_fields);
            
            $this->db->query("
                UPDATE " . DB_PREFIX . "oct_abandoned_cart
                SET " . $update_sql . "
                WHERE abandoned_cart_id = " . (int)$cart_id
            );
        } else {
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "oct_abandoned_cart
                SET cookie_token    = '" . $this->db->escape($data['cookie_token']) . "',
                    cookie_signature= '" . $this->db->escape($data['cookie_signature']) . "',
                    customer_id     = " . (int)$customer_id . ",
                    firstname       = '" . $this->db->escape($firstname) . "',
                    lastname        = '" . $this->db->escape($lastname) . "',
                    email           = '" . $this->db->escape($email) . "',
                    phone           = '" . $this->db->escape($phone) . "',
                    store_id        = " . (int)$store_id . ",
                    store_name      = '" . $this->db->escape($store_name) . "',
                    language_id     = " . (int)$language_id . ",
                    cart_data       = '" . $this->db->escape($cart_json) . "',
                    status          = 'active',
                    reminder_count  = 0,
                    ip_added        = '" . $this->db->escape($customer_ip) . "',
                    date_added      = NOW(),
                    date_modified   = NOW()
            ");
        }    
    }

    public function generatePromoCode() {
        $module_settings = $this->config->get('oct_abandoned_cart') ?: [];

        if (empty($module_settings['coupon_status'])) {
            return false;
        }

        do {
            try {
                $code = strtoupper(bin2hex(random_bytes(4)));
            } catch (Exception $e) {
                $this->log->write('Error generating promo code: ' . $e->getMessage());
                return false;
            }
            $query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "coupon` WHERE code = '" . $this->db->escape($code) . "'");
        } while ($query->row['total'] > 0);

        $discount_type = ($module_settings['coupon_type'] === 'fixed') ? 'F' : 'P';

        $date_start = date('Y-m-d');
        $date_end = date('Y-m-d', strtotime("+{$module_settings['coupon_lifetime']} days"));

        $coupon_data = [
            'name'             => 'abo_' . $code,
            'code'             => $code,
            'discount'         => (float)$module_settings['coupon_discount'],
            'type'             => $discount_type,
            'total'            => 0.00,
            'logged'           => 0,
            'shipping'         => 0,
            'date_start'       => $date_start,
            'date_end'         => $date_end,
            'uses_total'       => 1,
            'uses_customer'    => 1,
            'status'           => (isset($module_settings['coupon_status']) && $module_settings['coupon_status'] == "on") ? 1 : 0
        ];

        $this->addCoupon($coupon_data);

        return $code;
    }    

    private function addCoupon($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "coupon SET name = '" . $this->db->escape($data['name']) . "', code = '" . $this->db->escape($data['code']) . "', discount = '" . (float)$data['discount'] . "', type = '" . $this->db->escape($data['type']) . "', total = '" . (float)$data['total'] . "', logged = '" . (int)$data['logged'] . "', shipping = '" . (int)$data['shipping'] . "', date_start = '" . $this->db->escape($data['date_start']) . "', date_end = '" . $this->db->escape($data['date_end']) . "', uses_total = '" . (int)$data['uses_total'] . "', uses_customer = '" . (int)$data['uses_customer'] . "', status = '" . (int)$data['status'] . "', date_added = NOW()");

        return $data['code'];
    }

    public function getEmailByCustomerId($customer_id) {
        $query = $this->db->query("SELECT `email` FROM `" . DB_PREFIX . "customer` 
                                   WHERE `customer_id` = " . (int)$customer_id . " LIMIT 1");

        if ($query->num_rows) {
            return $query->row['email'];
        }

        return false;
    }
    
    private function makeSignature($rawToken, $apiKey) {
        return hash_hmac('sha256', $rawToken, $apiKey);
    }

    private function checkSignature($rawToken, $signature, $apiKey) {
        $expectedSignature = $this->makeSignature($rawToken, $apiKey);
        return hash_equals($expectedSignature, $signature);
    }
}