<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ModelOctemplatesModuleOctAbandonedCart extends Model {

    public function install() {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "oct_abandoned_cart` (
            `abandoned_cart_id` INT(11) NOT NULL AUTO_INCREMENT,
            `cookie_token` VARCHAR(64) DEFAULT NULL,
            `cookie_signature` VARCHAR(128) DEFAULT NULL,
            `customer_id` INT(11) DEFAULT '0',
            `firstname` VARCHAR(100) DEFAULT NULL,
            `lastname` VARCHAR(100) DEFAULT NULL,
            `email` VARCHAR(255) DEFAULT NULL,
            `phone` VARCHAR(50) DEFAULT NULL,
            `store_id` INT(11) DEFAULT '0',
            `store_name` VARCHAR(255) DEFAULT NULL,
            `language_id` INT(11) DEFAULT '0',
            `cart_data` MEDIUMTEXT DEFAULT NULL,
            `status` VARCHAR(50) DEFAULT 'active',
            `reminder_count` INT(1) NOT NULL DEFAULT 0,
            `last_reminder` DATETIME DEFAULT NULL,
            `coupon_code` VARCHAR(50) DEFAULT NULL,
            `ip_added` VARCHAR(40) DEFAULT NULL,
            `ip_changed` VARCHAR(40) DEFAULT NULL,
            `date_added` DATETIME NOT NULL,
            `date_modified` DATETIME NOT NULL,
            PRIMARY KEY (`abandoned_cart_id`),
            INDEX `idx_cookie` (`cookie_token`, `cookie_signature`),
            INDEX `idx_customer` (`customer_id`),
            INDEX `idx_status` (`status`),
            INDEX `idx_store` (`store_id`, `language_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }

    public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "oct_abandoned_cart`;");
    }

    public function getAbandonedCarts($data = []) {

        $sql = "SELECT * FROM `" . DB_PREFIX . "oct_abandoned_cart` WHERE 1=1";
    
        if (!empty($data['filter_firstname'])) {
            $sql .= " AND firstname LIKE '%" . $this->db->escape($data['filter_firstname']) . "%'";
        }
    
        if (!empty($data['filter_lastname'])) {
            $sql .= " AND lastname LIKE '%" . $this->db->escape($data['filter_lastname']) . "%'";
        }
    
        if (!empty($data['filter_email'])) {
            $sql .= " AND email LIKE '%" . $this->db->escape($data['filter_email']) . "%'";
        }
    
        if (!empty($data['filter_phone'])) {
            $sql .= " AND phone LIKE '%" . $this->db->escape($data['filter_phone']) . "%'";
        }
    
        if (!empty($data['filter_status'])) {
            $sql .= " AND status = '" . $this->db->escape($data['filter_status']) . "'";
        }
    
        if (!empty($data['filter_ip_added'])) {
            $sql .= " AND ip_added LIKE '%" . $this->db->escape($data['filter_ip_added']) . "%'";
        }
    
        if (!empty($data['filter_ip_changed'])) {
            $sql .= " AND ip_changed LIKE '%" . $this->db->escape($data['filter_ip_changed']) . "%'";
        }
    
        if (!empty($data['filter_date_added_start'])) {
            $sql .= " AND DATE(date_added) >= '" . $this->db->escape($data['filter_date_added_start']) . "'";
        }
        if (!empty($data['filter_date_added_end'])) {
            $sql .= " AND DATE(date_added) <= '" . $this->db->escape($data['filter_date_added_end']) . "'";
        }
    
        if (!empty($data['filter_date_modified_start'])) {
            $sql .= " AND DATE(date_modified) >= '" . $this->db->escape($data['filter_date_modified_start']) . "'";
        }
        if (!empty($data['filter_date_modified_end'])) {
            $sql .= " AND DATE(date_modified) <= '" . $this->db->escape($data['filter_date_modified_end']) . "'";
        }
    
        $sql .= " ORDER BY date_added DESC";
    
        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }
            if ($data['limit'] < 1) {
                $data['limit'] = $this->config->get('config_limit_admin');
            }
            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }
    
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getTotalAbandonedCarts($data = []) {
        $sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "oct_abandoned_cart` WHERE 1=1";
    
        if (!empty($data['filter_firstname'])) {
            $sql .= " AND firstname LIKE '%" . $this->db->escape($data['filter_firstname']) . "%'";
        }
    
        if (!empty($data['filter_lastname'])) {
            $sql .= " AND lastname LIKE '%" . $this->db->escape($data['filter_lastname']) . "%'";
        }
    
        if (!empty($data['filter_email'])) {
            $sql .= " AND email LIKE '%" . $this->db->escape($data['filter_email']) . "%'";
        }
    
        if (!empty($data['filter_phone'])) {
            $sql .= " AND phone LIKE '%" . $this->db->escape($data['filter_phone']) . "%'";
        }
    
        if (!empty($data['filter_status'])) {
            $sql .= " AND status = '" . $this->db->escape($data['filter_status']) . "'";
        }
    
        if (!empty($data['filter_ip_added'])) {
            $sql .= " AND ip_added LIKE '%" . $this->db->escape($data['filter_ip_added']) . "%'";
        }
    
        if (!empty($data['filter_ip_changed'])) {
            $sql .= " AND ip_changed LIKE '%" . $this->db->escape($data['filter_ip_changed']) . "%'";
        }
    
        if (!empty($data['filter_date_added_start'])) {
            $sql .= " AND DATE(date_added) >= '" . $this->db->escape($data['filter_date_added_start']) . "'";
        }
        if (!empty($data['filter_date_added_end'])) {
            $sql .= " AND DATE(date_added) <= '" . $this->db->escape($data['filter_date_added_end']) . "'";
        }
    
        if (!empty($data['filter_date_modified_start'])) {
            $sql .= " AND DATE(date_modified) >= '" . $this->db->escape($data['filter_date_modified_start']) . "'";
        }
        if (!empty($data['filter_date_modified_end'])) {
            $sql .= " AND DATE(date_modified) <= '" . $this->db->escape($data['filter_date_modified_end']) . "'";
        }
    
        $query = $this->db->query($sql);
        return (int)$query->row['total'];
    } 

    public function convertCart($cart_id) {
        $this->db->query("UPDATE `" . DB_PREFIX . "oct_abandoned_cart` 
                          SET `status` = 'converted', `date_modified` = NOW() 
                          WHERE `abandoned_cart_id` = " . (int)$cart_id . " AND `status` = 'active'");
        $affected = $this->db->countAffected();
        if ($affected <= 0) {
            $this->log->write('No rows affected while converting cart_id: ' . (int)$cart_id);
        }
        return $affected > 0;
    }

    public function deleteCart($cart_id) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "oct_abandoned_cart` 
                          WHERE `abandoned_cart_id` = " . (int)$cart_id);
        $affected = $this->db->countAffected();
        if ($affected <= 0) {
            $this->log->write('No rows affected while deleting cart_id: ' . (int)$cart_id);
        }
        return $affected > 0;
    }

    public function getAbandonedCartDetails($cart_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "oct_abandoned_cart` 
                                   WHERE `abandoned_cart_id` = " . (int)$cart_id . " LIMIT 1");

        if ($query->num_rows) {
            return $query->row;
        }
        $this->log->write('Abandoned cart details not found for cart_id: ' . (int)$cart_id);
        return false;
    }

    public function getCartProducts($cart_id) {
        $query = $this->db->query("SELECT `cart_data` FROM `" . DB_PREFIX . "oct_abandoned_cart` 
                                   WHERE `abandoned_cart_id` = " . (int)$cart_id . " LIMIT 1");

        if ($query->num_rows) {
            $cart_data = json_decode($query->row['cart_data'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->log->write('Error decoding cart_data for abandoned_cart_id: ' . (int)$cart_id . ' - ' . json_last_error_msg());
            }
            if (!is_array($cart_data)) {
                return [];
            }
            $products = [];

            foreach ($cart_data as $item) {
                $product_id = isset($item['product_id']) ? (int)$item['product_id'] : 0;
                $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 1;

                $product_query = $this->db->query("SELECT `name`, `price` FROM `" . DB_PREFIX . "product` WHERE `product_id` = " . $product_id . " AND `status` = 1 LIMIT 1");

                if ($product_query->num_rows) {
                    $products[] = [
                        'product_id' => $product_id,
                        'name'       => $product_query->row['name'],
                        'quantity'   => $quantity,
                        'price'      => $product_query->row['price']
                    ];
                } else {
                    $this->log->write('Product not found or inactive for product_id: ' . $product_id . ' in cart_id: ' . (int)$cart_id);
                }
            }
            return $products;
        }
        return [];
    }  

}