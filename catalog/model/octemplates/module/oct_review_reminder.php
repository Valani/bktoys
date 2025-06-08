<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ModelOctemplatesModuleOctReviewReminder extends Model {

    public function getRemindersToSend() {
    
        $daysAfterOrder = (int)$this->config->get('oct_review_reminder_days_after_order') ?: 7;
    
        $allowedOrderStatuses = $this->config->get('oct_review_reminder_order_status');
        if (!is_array($allowedOrderStatuses) || empty($allowedOrderStatuses)) {
            $allowedOrderStatuses = [5];
        }
    
        $startOfDay = date('Y-m-d H:i:s', strtotime('-' . $daysAfterOrder . ' days midnight'));
        $endOfDay = date('Y-m-d H:i:s', strtotime('-' . $daysAfterOrder . ' days 23:59:59'));
    
        $dateLimitDays = 70;
        $cutoffDate = date('Y-m-d H:i:s', strtotime('-' . ($daysAfterOrder + $dateLimitDays) . ' days'));
    
        $sql = "
            SELECT 
                o.order_id,
                o.customer_id,
                o.telephone,
                o.language_id,
                o.email,
                o.firstname AS customer_name,
                o.lastname AS customer_lastname,
                o.order_status_id,
                MIN(oh.date_added) AS send_date
            FROM `" . DB_PREFIX . "order` o
            LEFT JOIN `" . DB_PREFIX . "order_history` oh 
                ON oh.order_id = o.order_id 
                AND oh.order_status_id = o.order_status_id
            LEFT JOIN `" . DB_PREFIX . "oct_review_reminder` r ON o.order_id = r.order_id
            WHERE o.order_status_id IN ('" . implode("','", $allowedOrderStatuses) . "')
            AND r.order_id IS NULL
            AND o.date_added >= '" . $this->db->escape($cutoffDate) . "'
            GROUP BY o.order_id
            HAVING send_date BETWEEN '" . $this->db->escape($startOfDay) . "' AND '" . $this->db->escape($endOfDay) . "'
            ORDER BY send_date ASC
            LIMIT 150
        ";
    
        $query = $this->db->query($sql);
        $reminders = $query->rows;
    
        foreach ($reminders as $reminder) {
            if (!$this->isReminderAlreadySent($reminder['order_id'])) {
                $this->addReminderRecord($reminder);
            }
        }
    
        return $reminders;
    }

    public function addReminderRecord($reminder) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "oct_review_reminder` SET `order_id` = '" . (int)$reminder['order_id'] . "', `customer_id` = '" . (int)$reminder['customer_id'] . "', `email` = '" . $this->db->escape($reminder['email']) . "', `telephone` = '" . $this->db->escape($reminder['telephone']) . "', `date_added` = NOW(), `is_sent` = 0");
    }

    public function isReminderAlreadySent($orderId) {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "oct_review_reminder` WHERE `order_id` = '" . (int)$orderId . "'");
        return $query->row['total'] > 0;
    }

    public function markReminderAsSent($orderId) {
        $this->db->query("UPDATE `" . DB_PREFIX . "oct_review_reminder` SET `is_sent` = 1 WHERE `order_id` = '" . (int)$orderId . "'");
    }

    public function delReminderRecord($orderId) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "oct_review_reminder` WHERE `order_id` = '" . (int)$orderId . "'");
    }
}