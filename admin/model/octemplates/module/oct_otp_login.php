<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ModelOctemplatesModuleOctOtpLogin extends Model {
    public function install() {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "oct_customer_otp` (
                `customer_id` INT(11) NOT NULL,
                `otp_code` VARCHAR(255) NOT NULL,
                `expires_at` DATETIME NOT NULL,
                `attempts` INT(11) NOT NULL DEFAULT '0',
                `date_added` DATETIME NOT NULL,
                `last_attempt` DATETIME NOT NULL,
                PRIMARY KEY (`customer_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "oct_customer_otp_log` (
                `log_id` INT(11) NOT NULL AUTO_INCREMENT,
                `customer_id` INT(11) NOT NULL,
                `telephone` VARCHAR(32) NOT NULL,
                `status` VARCHAR(32) NOT NULL,
                `date_added` DATETIME NOT NULL,
                PRIMARY KEY (`log_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "oct_customer_otp_ip` (
                `attempt_id` INT(11) NOT NULL AUTO_INCREMENT,
                `ip_address` VARCHAR(45) NOT NULL,
                `attempt_count` INT(11) NOT NULL DEFAULT 0,
                `last_attempt` DATETIME NOT NULL,
                PRIMARY KEY (`attempt_id`),
                INDEX (`ip_address`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }

    public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "oct_customer_otp`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "oct_customer_otp_log`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "oct_customer_otp_ip`");
    }

    public function getTotalOtpLogs($filter_data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "oct_customer_otp_log`";

        $conditions = array();

        if (!empty($filter_data['filter_customer_id'])) {
            $conditions[] = "customer_id = '" . (int)$filter_data['filter_customer_id'] . "'";
        }

        if (!empty($filter_data['filter_telephone'])) {
            $conditions[] = "telephone LIKE '%" . $this->db->escape($filter_data['filter_telephone']) . "%'";
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    public function getOtpLogs($data = array()) {
        $sql = "SELECT * FROM `" . DB_PREFIX . "oct_customer_otp_log`";

        $conditions = array();

        if (!empty($data['filter_customer_id'])) {
            $conditions[] = "customer_id = '" . (int)$data['filter_customer_id'] . "'";
        }

        if (!empty($data['filter_telephone'])) {
            $conditions[] = "telephone LIKE '%" . $this->db->escape($data['filter_telephone']) . "%'";
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $data['order'] = isset($data['order']) ? $data['order'] : 'DESC';

        $sort_data = array(
            'date_added'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY date_added";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function deleteOtpLogs() {
        $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "oct_customer_otp_log`");
        $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "oct_customer_otp_ip`");
    }
    
    public function getTotalOtpIpLogs($filter_data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "oct_customer_otp_ip`";
    
        $conditions = array();
    
        if (!empty($filter_data['filter_ip_address'])) {
            $conditions[] = "ip_address LIKE '%" . $this->db->escape($filter_data['filter_ip_address']) . "%'";
        }
    
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
    
        $query = $this->db->query($sql);
    
        return $query->row['total'];
    }
    
    public function getOtpIpLogs($data = array()) {
        $sql = "SELECT * FROM `" . DB_PREFIX . "oct_customer_otp_ip`";
    
        $conditions = array();
    
        if (!empty($data['filter_ip_address'])) {
            $conditions[] = "ip_address LIKE '%" . $this->db->escape($data['filter_ip_address']) . "%'";
        }
    
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
    
        $data['order'] = isset($data['order']) ? $data['order'] : 'DESC';
    
        $sort_data = array(
            'last_attempt'
        );
    
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY last_attempt";
        }
    
        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }
    
        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }
    
            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }
    
            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }
    
        $query = $this->db->query($sql);
    
        return $query->rows;
    }
    
    public function deleteOtpIpLogs() {
        $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "oct_customer_otp_ip`");
    }
    
}
