<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ModelOctemplatesModuleOctOtpLogin extends Model {
    public function createOtp($customer_id, $otp_code, $expires_at) {
        $this->db->query("REPLACE INTO `" . DB_PREFIX . "oct_customer_otp` SET `customer_id` = '" . (int)$customer_id . "', `otp_code` = '" . $this->db->escape($otp_code) . "', `expires_at` = '" . $this->db->escape($expires_at) . "', `attempts` = '0', `date_added` = NOW()");
    }

    public function getOtp($customer_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "oct_customer_otp` WHERE `customer_id` = '" . (int)$customer_id . "'");
        return $query->row;
    }

    public function incrementAttempts($customer_id) {
        $this->db->query("UPDATE `" . DB_PREFIX . "oct_customer_otp` SET `attempts` = `attempts` + 1 WHERE `customer_id` = '" . (int)$customer_id . "'");
    }

    public function deleteOtp($customer_id) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "oct_customer_otp` WHERE `customer_id` = '" . (int)$customer_id . "'");
    }

    public function getLastOtpRequestTime($customer_id) {
        $query = $this->db->query("SELECT UNIX_TIMESTAMP(`date_added`) as last_request_time FROM `" . DB_PREFIX . "oct_customer_otp` WHERE `customer_id` = '" . (int)$customer_id . "'");
        if ($query->num_rows) {
            return $query->row['last_request_time'];
        } else {
            return false;
        }
    }

    public function logOtpAttempt($customer_id, $telephone, $status, $push_to_log = false) {
        if ($push_to_log) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "oct_customer_otp_log` SET `customer_id` = '" . (int)$customer_id . "', `telephone` = '" . $this->db->escape($telephone) . "', `status` = '" . $this->db->escape($status) . "', `date_added` = NOW()");
        }

        $this->db->query("UPDATE `" . DB_PREFIX . "oct_customer_otp` SET `last_attempt` = NOW() WHERE `customer_id` = '" . (int)$customer_id . "'");
    }

    public function getCustomerByTelephone($telephone) {
        $normalizedTelephone = preg_replace('/\D/', '', $telephone);
        $phomeWithoutCountryCode = preg_replace('/^38/', '', $normalizedTelephone);
        $phomeWithoutCountry = preg_replace('/^3/', '', $normalizedTelephone);
    
        $conditions = [
            "`telephone` = '" . $this->db->escape($telephone) . "'",
            "`telephone` = '" . $this->db->escape('+' . $normalizedTelephone) . "'",
            "`telephone` = '" . $this->db->escape($phomeWithoutCountryCode) . "'",
            "`telephone` = '" . $this->db->escape($phomeWithoutCountry) . "'",
            "`telephone` = '" . $this->db->escape($normalizedTelephone) . "'"
        ];

        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "customer` WHERE " . implode(' OR ', $conditions));
        return $query->row;
    }    
    
    public function getLoginAttempt($ip_address) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "oct_customer_otp_ip` WHERE `ip_address` = '" . $this->db->escape($ip_address) . "'");
        return $query->row;
    }

    public function incrementLoginAttempt($ip_address) {
        $login_attempt = $this->getLoginAttempt($ip_address);

        if ($login_attempt) {
            $this->db->query("UPDATE `" . DB_PREFIX . "oct_customer_otp_ip` SET `attempt_count` = `attempt_count` + 1, `last_attempt` = NOW() WHERE `ip_address` = '" . $this->db->escape($ip_address) . "'");
        } else {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "oct_customer_otp_ip` (`ip_address`, `attempt_count`, `last_attempt`) VALUES ('" . $this->db->escape($ip_address) . "', 1, NOW())");
        }
    }

    public function resetLoginAttempts($ip_address) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "oct_customer_otp_ip` WHERE `ip_address` = '" . $this->db->escape($ip_address) . "'");
    }

    public function isIpBanned($ip_address) {

        $max_attempts = $this->getOtpSettings('max_attempts', 5);
        $ban_time = $this->getOtpSettings('lockout_time', 15) * 60;

        $login_attempt = $this->getLoginAttempt($ip_address);

        if ($login_attempt) {
            if ($login_attempt['attempt_count'] >= $max_attempts) {
                $time_since_last_attempt = time() - strtotime($login_attempt['last_attempt']);
                if ($time_since_last_attempt < $ban_time) {
                    return true;
                } else {
                    $this->resetLoginAttempts($ip_address);
                }
            }
        }
        return false;
    }

    public function getLockoutTime($ip_address) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "oct_customer_otp_ip` WHERE `ip_address` = '" . $this->db->escape($ip_address) . "'");

        if ($query->num_rows) {
            $last_attempt_time = strtotime($query->row['last_attempt']);
            $current_time = time();
            $lockout_duration = $this->getOtpSettings('lockout_time', 15) * 60;

            if (($current_time - $last_attempt_time) < $lockout_duration) {
                $time_left = ceil(($last_attempt_time + $lockout_duration - $current_time) / 60);
                return $time_left;
            }
        }

        return 0;
    }

    public function clearOldLoginAttempts() {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "oct_customer_otp_ip` WHERE `last_attempt` < DATE_SUB(NOW(), INTERVAL 1 DAY)");
    }

    private function getOtpSettings($key = null, $default = null) {
        $settings = $this->config->get('oct_otp_login_settings');
        return $key ? ($settings[$key] ?? $default) : $settings;
    }
}
