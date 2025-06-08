<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ModelOctemplatesModuleOctReviewReminder extends Model {

    public function install() {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "oct_review_reminder` (
            `reminder_id` INT(11) NOT NULL AUTO_INCREMENT,
            `order_id` INT(11) NOT NULL,
            `customer_id` INT(11) NOT NULL,
            `email` VARCHAR(255) NOT NULL,
            `telephone` VARCHAR(20) NOT NULL,
            `is_sent` TINYINT(1) NOT NULL DEFAULT '0',
            `date_added` DATETIME NOT NULL,
            PRIMARY KEY (`reminder_id`),
            UNIQUE KEY `order_id` (`order_id`),
            KEY `customer_id` (`customer_id`),
            KEY `is_sent` (`is_sent`),
            KEY `order_is_sent` (`order_id`, `is_sent`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
    }

    public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "oct_review_reminder`;");
    }

    public function getLogs() {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "oct_review_reminder` ORDER BY `date_added` DESC");
        return $query->rows;
    }

    public function getLogsAjax($start, $limit) {
        $query = $this->db->query("
            SELECT r.*, c.firstname, c.lastname 
            FROM `" . DB_PREFIX . "oct_review_reminder` r
            LEFT JOIN `" . DB_PREFIX . "customer` c ON r.customer_id = c.customer_id
            ORDER BY r.date_added DESC 
            LIMIT " . (int)$start . ", " . (int)$limit . ";
        ");
        return $query->rows;
    }

    public function getTotalLogs() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "oct_review_reminder`;");
        return $query->row['total'];
    }
}