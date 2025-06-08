<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ModelOctemplatesModuleOctSmsNotify extends Model {

    public function install() {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "oct_sms_notify_log` (
                `sms_log_id` INT(11) NOT NULL AUTO_INCREMENT,
                `phone` VARCHAR(32) NOT NULL,
                `message` TEXT NOT NULL,
                `provider` VARCHAR(64) NOT NULL,
                `response` TEXT NOT NULL,
                `template_code` VARCHAR(64) NOT NULL,
                `date_added` DATETIME NOT NULL,
                PRIMARY KEY (`sms_log_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");
    }

    public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "oct_sms_notify_log`;");
    }

    public function getTotalSmsLogs() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "oct_sms_notify_log`");

        return $query->row['total'];
    }

    public function getSmsLogs($data = array()) {
        $sql = "SELECT * FROM `" . DB_PREFIX . "oct_sms_notify_log`";

        $sort_data = array(
            'date_added',
            'phone',
            'provider'
        );

        $sql .= " ORDER BY date_added DESC";

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = (int) $this->config->get('config_limit_admin');
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function deleteLogs() {
        $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "oct_sms_notify_log`");
    }
}