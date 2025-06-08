<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ModelOctemplatesModuleOctStockNotifier extends Model {
    public function addRequest($data) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "oct_stock_notifier 
            SET 
                product_id = '" . (int)$data['pid'] . "', 
                customer_id = '" . (int)$data['customer_id'] . "', 
                customer_name = '" . $this->db->escape($data['name']) . "', 
                store_id       = '" . (int)$this->config->get('config_store_id')  . "', 
                language_id    = '" . (int)$this->config->get('config_language_id')  . "', 
                email = '" . $this->db->escape($data['email']) . "', 
                phone = '" . $this->db->escape($data['phone']) . "', 
                subscribed_date = NOW(),
                status = '0'
        ");
    }

    public function isAlreadySubscribed($data) {
        $query = $this->db->query("
            SELECT * 
            FROM " . DB_PREFIX . "oct_stock_notifier 
            WHERE product_id = '" . (int) $data['pid'] . "' 
            AND status = 0 
            AND email = '" . $this->db->escape($data['email']) . "'
        ");
    
        return $query->num_rows > 0;
    }

    public function getWaitingProducts() {
        $query = $this->db->query("
            SELECT DISTINCT osn.product_id 
            FROM " . DB_PREFIX . "oct_stock_notifier osn 
            WHERE osn.status = 0 
            AND EXISTS (
                SELECT * 
                FROM " . DB_PREFIX . "product p 
                WHERE osn.product_id = p.product_id 
                AND p.quantity > 0 
                AND p.quantity IS NOT NULL
            )
        ");

        return $query->rows;
    }

    public function getCustomersByProductId($product_id) {
        $query = $this->db->query("
            SELECT osn.*, pd.name 
            FROM " . DB_PREFIX . "oct_stock_notifier osn 
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (osn.product_id = pd.product_id) 
            WHERE osn.product_id = '" . (int)$product_id . "' 
            AND osn.status = 0 
            AND pd.language_id = osn.language_id
        ");
    
        return $query->rows;
    }

    public function updateSubscriptionStatus($subscription_id) {
        $query = $this->db->query("UPDATE " . DB_PREFIX . "oct_stock_notifier SET status = 1, notified_date = NOW() WHERE subscription_id = '" . (int)$subscription_id . "'");
    }
    
    public function getUserRequests($customer_id, $start = 0, $limit = 20) {
        $query = $this->db->query("
            SELECT osn.*, pd.name AS product_name
            FROM " . DB_PREFIX . "oct_stock_notifier osn
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (osn.product_id = pd.product_id)
            WHERE osn.customer_id = '" . (int)$customer_id . "'
            AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ORDER BY osn.subscribed_date DESC
            LIMIT " . (int)$start . "," . (int)$limit
        );

        return $query->rows;
    }

    public function getTotalUserRequests($customer_id) {
        $query = $this->db->query("
            SELECT COUNT(*) AS total 
            FROM " . DB_PREFIX . "oct_stock_notifier 
            WHERE customer_id = '" . (int)$customer_id . "'
        ");

        return $query->row['total'];
    }

    public function removeRequest($request_id) {
        $customer_id = $this->customer->getId();
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "oct_stock_notifier WHERE subscription_id = '" . (int)$request_id . "' AND customer_id = '" . (int)$customer_id . "'");
    
        if($query->num_rows) {
            $this->db->query("DELETE FROM " . DB_PREFIX . "oct_stock_notifier WHERE subscription_id = '" . (int)$request_id . "'");
            return true;
        } else {
            return false;
        }
    }
    
}