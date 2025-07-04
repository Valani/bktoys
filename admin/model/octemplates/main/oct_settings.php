<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ModelOCTemplatesMainOctSettings extends Model {
    public function addOCTLocations($data = []) {
        $this->db->query("TRUNCATE `" . DB_PREFIX . "oct_location`");

        if ($data) {
            foreach ($data as $location) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "oct_location SET description = '" . $this->db->escape(json_encode($location['description'], true))  . "', phone = '" . $this->db->escape($location['phone'])  . "', map = '" . $this->db->escape($location['map'])  . "', image = '" . $this->db->escape($location['image'])  . "', link = '" . $this->db->escape($location['link'])  . "', sort = '". (int)$location['sort'] ."'");
            }
        }
    }
	
	public function installBoughtTogether() {
		$sql = "
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "oct_bought_together_cache` (
				`product_id` INT(11) NOT NULL,
				`with_product_id` INT(11) NOT NULL,
				`total_count` INT(11) NOT NULL DEFAULT 0,
				PRIMARY KEY (`product_id`, `with_product_id`),
				KEY `product_id` (`product_id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";

		$this->db->query($sql);
	}

	public function installStickersCache() {
        $sql = "
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "oct_product_stickers` (
                `product_id` INT(11) NOT NULL,
                `stickers_json` LONGTEXT NOT NULL,
                PRIMARY KEY (`product_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ";

		$this->db->query($sql);
	}	
	
	public function ensureHistoryIndexExists() {
		$check_history = $this->db->query("
			SELECT COUNT(1) AS total 
			FROM information_schema.statistics 
			WHERE table_schema = DATABASE() 
			AND table_name = '" . DB_PREFIX . "order_history' 
			AND index_name = 'order_status_lookup'
		");

		if ((int)$check_history->row['total'] === 0) {
			$this->db->query("
				CREATE INDEX `order_status_lookup` 
				ON `" . DB_PREFIX . "order_history` (`order_id`, `order_status_id`, `date_added`)
			");
		}

		$check_order = $this->db->query("
			SELECT COUNT(1) AS total 
			FROM information_schema.statistics 
			WHERE table_schema = DATABASE() 
			AND table_name = '" . DB_PREFIX . "order' 
			AND index_name = 'idx_order_status_date'
		");

		if ((int)$check_order->row['total'] === 0) {
			$this->db->query("
				CREATE INDEX `idx_order_status_date` 
				ON `" . DB_PREFIX . "order` (`order_status_id`, `date_added`)
			");
		}
	}

    public function getOCTLocations() {
        $this->load->model('tool/image');

        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "oct_location`");

        $oct_locations = [];

        foreach ($query->rows as $location) {
            if (isset($location['image']) && !empty($location['image']) && file_exists(DIR_IMAGE.$location['image'])) {
                $thumb = $this->model_tool_image->resize($location['image'], 100, 100);
            } else {
                $thumb = $this->model_tool_image->resize('no_image.png', 100, 100);
            }

            $description = json_decode($location['description'], true);

            $oct_locations[] = [
                'title' => isset($description[(int)$this->config->get('config_language_id')]['title']) ? $description[(int)$this->config->get('config_language_id')]['title'] : 'Location Title',
                'description' => $description,
                'phone' => $location['phone'],
                'map' => $location['map'],
                'thumb' => $thumb,
                'image' => $location['image'],
                'link' => $location['link'],
                'sort' => $location['sort'],
            ];
        }

        return $oct_locations;
    }

    public function addOCTMenu($data = []) {
        if ($data) {
            $menu_ids = [];

            foreach ($data as $oct_menu) {
                $menu_ids[] = (isset($oct_menu['setting']) && $oct_menu['setting'] && empty($oct_menu['settings'])) ? $oct_menu['setting'] : 0;
            }

            if ($menu_ids) {
                $menu_id = implode(',', $menu_ids);

                $this->db->query("DELETE FROM `" . DB_PREFIX . "oct_menu` WHERE `oct_menu_id` NOT IN (". $menu_id .")");
                $this->db->query("DELETE FROM `" . DB_PREFIX . "oct_menu_description` WHERE `oct_menu_id` NOT IN (". $menu_id .")");
                $this->db->query("DELETE FROM `" . DB_PREFIX . "oct_menu_to_customer_group` WHERE `oct_menu_id` NOT IN (". $menu_id .")");
                $this->db->query("DELETE FROM `" . DB_PREFIX . "oct_menu_to_store` WHERE `oct_menu_id` NOT IN (". $menu_id .")");
            }

            foreach ($data as $menu) {
                $menu['status'] = isset($menu['status']) ? 1 : 0;
                $menu['settings'] = isset($menu['settings']) ? $menu['settings'] : [];
                $menu['sort_order'] = (isset($menu['sort_order']) && !empty($menu['sort_order'])) ? $menu['sort_order'] : 0;

                if (isset($menu['setting']) && $menu['setting'] && empty($menu['settings'])) {
                    $set = $this->db->query("SELECT `settings`, `status`, `sort_order` FROM `" . DB_PREFIX . "oct_menu` WHERE `oct_menu_id` = '".(int)$menu['setting']."'");

                    if ($set->num_rows) {
                        $menu['status'] = $set->row['status'];
                        $menu['sort_order'] = $set->row['sort_order'];
                        $menu['settings'] = json_decode($set->row['settings'], true);
                    }

                    $this->db->query("UPDATE `" . DB_PREFIX . "oct_menu` SET `type` = '" . $this->db->escape($menu['type'])  . "', `settings` = '" . $this->db->escape(json_encode($menu['settings'], true))  . "', `sort_order` = '" . (int)$menu['sort_order']  . "', `status` = '" . (int)$menu['status']  . "' WHERE `oct_menu_id` = '" . (int)$menu['setting'] . "'");

                    $oct_menu_id = (int)$menu['setting'];
                } else {
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "oct_menu` SET `type` = '" . $this->db->escape($menu['type'])  . "', `settings` = '" . $this->db->escape(json_encode($menu['settings'], true))  . "', `sort_order` = '" . (int)$menu['sort_order']  . "', `status` = '" . (int)$menu['status']  . "'");

                    $oct_menu_id = $this->db->getLastId();
                }

                $this->db->query("DELETE FROM `" . DB_PREFIX . "oct_menu_description` WHERE `oct_menu_id` = '" . (int)$oct_menu_id . "'");
                $this->db->query("DELETE FROM `" . DB_PREFIX . "oct_menu_to_customer_group` WHERE `oct_menu_id` = '" . (int)$oct_menu_id . "'");
                $this->db->query("DELETE FROM `" . DB_PREFIX . "oct_menu_to_store` WHERE `oct_menu_id` = '" . (int)$oct_menu_id . "'");

                foreach ($menu['description'] as $language_id => $description) {
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "oct_menu_description` SET `oct_menu_id` = '" . (int)$oct_menu_id  . "', `language_id` = '" . (int)$language_id  . "', `title` = '" . $this->db->escape($description['title'])  . "', `link` = '" . $this->db->escape($description['link'])  . "'");
                }

                foreach ($menu['customers'] as $customer_id) {
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "oct_menu_to_customer_group` SET `oct_menu_id` = '" . (int)$oct_menu_id  . "', `customer_group_id` = '" . (int)$customer_id  . "'");
                }

                foreach ($menu['stories'] as $store_id) {
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "oct_menu_to_store` SET `oct_menu_id` = '" . (int)$oct_menu_id  . "', `store_id` = '" . (int)$store_id  . "'");
                }
            }
        } else {
            $this->db->query("TRUNCATE `" . DB_PREFIX . "oct_menu`");
            $this->db->query("TRUNCATE `" . DB_PREFIX . "oct_menu_description`");
            $this->db->query("TRUNCATE `" . DB_PREFIX . "oct_menu_to_customer_group`");
            $this->db->query("TRUNCATE `" . DB_PREFIX . "oct_menu_to_store`");
        }
    }

    public function getMenuItems() {
        $oct_menu_data = [];

        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "oct_menu` ORDER BY `sort_order`");

        foreach ($query->rows as $menu_item) {
            $description = $customers = $stories = [];

            $query_description = $this->db->query("SELECT * FROM `" . DB_PREFIX . "oct_menu_description` WHERE `oct_menu_id` = '".(int)$menu_item['oct_menu_id']."'");

            foreach ($query_description->rows as $descr) {
                $description[$descr['language_id']] = [
                    'title' => $descr['title'],
                    'link' => $descr['link'],
                ];
            }

            $query_customers = $this->db->query("SELECT `customer_group_id` FROM `" . DB_PREFIX . "oct_menu_to_customer_group` WHERE `oct_menu_id` = '". (int)$menu_item['oct_menu_id'] ."'");

            foreach ($query_customers->rows as $customer) {
                $customers[] = $customer['customer_group_id'];
            }

            $query_stories = $this->db->query("SELECT `store_id` FROM `" . DB_PREFIX . "oct_menu_to_store` WHERE `oct_menu_id` = '". (int)$menu_item['oct_menu_id'] ."'");

            foreach ($query_stories->rows as $store) {
                $stories[] = $store['store_id'];
            }

            $oct_menu_data[$menu_item['oct_menu_id']] = [
                'oct_menu_id' => $menu_item['oct_menu_id'],
                'title' => isset($description[(int)$this->config->get('config_language_id')]['title']) ? $description[(int)$this->config->get('config_language_id')]['title'] : 'Menu Title',
                'type' => $menu_item['type'],
                'settings' => json_decode($menu_item['settings'], true),
                'description' => $description,
                'customers' => $customers,
                'stories' => $stories
            ];
        }

        return $oct_menu_data;
    }

    public function getMenuItemsByMenuId($menu_id) {
        $oct_menu_data = [];

        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "oct_menu` WHERE `oct_menu_id` = '". (int)$menu_id ."'");

        if ($query->num_rows) {
            $oct_menu_data = [
                'oct_menu_id' => $query->row['oct_menu_id'],
                'type' => $query->row['type'],
                'settings' => json_decode($query->row['settings'], true),
                'sort_order' => $query->row['sort_order'],
                'status' => $query->row['status'],
            ];
        }

        return $oct_menu_data;
    }

    public function installOCTLocation() {
        $sql = "
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "oct_location` (
            	`oct_location_id` int(11) NOT NULL AUTO_INCREMENT,
            	`description` text DEFAULT NULL,
            	`phone` text DEFAULT NULL,
            	`map` text DEFAULT NULL,
            	`link` varchar(256) DEFAULT NULL,
            	`image` varchar(255) DEFAULT NULL,
                `sort` int(3) DEFAULT NULL,
            	PRIMARY KEY (`oct_location_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ";

        $this->db->query($sql);
    }

    public function installOCTMenu() {
        $sqls[] = "
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "oct_menu` (
              `oct_menu_id` int(11) NOT NULL AUTO_INCREMENT,
              `type` varchar(20) NOT NULL,
              `settings` text NOT NULL,
              `sort_order` int(3) NOT NULL DEFAULT '0',
              `status` tinyint(4) NOT NULL DEFAULT '0',
              PRIMARY KEY (`oct_menu_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ";

        $sqls[] = "
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "oct_menu_description` (
              `oct_menu_id` int(11) NOT NULL,
              `language_id` int(11) NOT NULL,
              `title` varchar(255) NOT NULL,
              `link` varchar(255) NULL,
              PRIMARY KEY (`oct_menu_id`,`language_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ";

        $sqls[] = "
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "oct_menu_to_customer_group` (
              `oct_menu_id` int(11) NOT NULL,
              `customer_group_id` int(11) NOT NULL,
              PRIMARY KEY (`oct_menu_id`,`customer_group_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ";

        $sqls[] = "
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "oct_menu_to_store` (
              `oct_menu_id` int(11) NOT NULL,
              `store_id` int(11) NOT NULL,
              PRIMARY KEY (`oct_menu_id`,`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ";

        foreach ($sqls as $sql) {
            $this->db->query($sql);
        }
    }

    public function deleteOCTMenu() {
        $this->db->query("DROP TABLE `" . DB_PREFIX . "oct_menu`");
        $this->db->query("DROP TABLE `" . DB_PREFIX . "oct_menu_description`");
        $this->db->query("DROP TABLE `" . DB_PREFIX . "oct_menu_to_customer_group`");
        $this->db->query("DROP TABLE `" . DB_PREFIX . "oct_menu_to_store`");
    }

    public function deleteOCTLocation() {
        $this->db->query("DROP TABLE `" . DB_PREFIX . "oct_location`");
    }

    public function installOCTFields() {
		if ($this->checkIfColumnExist(DB_PREFIX . "category", "page_group_links")) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "category` ADD `page_group_links` text NOT NULL AFTER `status`;");
		}

		if ($this->checkIfColumnExist(DB_PREFIX . "category", "oct_image")) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "category` ADD `oct_image` varchar(255) COLLATE 'utf8_general_ci' NULL AFTER `image`;");
		}

		if ($this->checkIfColumnExist(DB_PREFIX . "review", "reply")) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "review` ADD `reply` text NOT NULL AFTER `text`;");
		}

        if ($this->model_octemplates_main_oct_settings->checkIfColumnExist(DB_PREFIX . "product", "oct_stickers")) {
			$this->db->query("SET SQL_MODE = ''");

			$this->db->query("ALTER TABLE `" . DB_PREFIX . "product` ADD `oct_stickers` text NOT NULL;");

			$this->db->query("SET SESSION sql_mode = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION'");
		}
    }

    public function checkIfColumnExist($table_name, $table_column) {
		$query = $this->db->query("
        	SELECT
				COUNT(*) as total
			FROM information_schema.COLUMNS
			WHERE
				TABLE_SCHEMA = '".DB_DATABASE."'
			AND
				TABLE_NAME = '".$table_name."'
			AND
				COLUMN_NAME  = '".$table_column."'
		");

		return ($query->row['total'] == 0) ? true : false;
    }

    public function checkTypeColumn($table_name, $table_column) {
        $query = $this->db->query("
            SELECT DATA_TYPE
            FROM information_schema.COLUMNS
            WHERE
                TABLE_NAME = '". $table_name ."'
            AND
                COLUMN_NAME = '". $table_column ."'
        ");

        return ($query->num_rows) ? $query->row['DATA_TYPE'] : false;
    }

	public function checkIfTableExist($table_name) {
		$query = $this->db->query("
        	SELECT
				COUNT(*) as total
			FROM information_schema.COLUMNS
			WHERE
				TABLE_SCHEMA = '".DB_DATABASE."'
			AND
				TABLE_NAME = '".$table_name."'
		");

		return $query->row['total'];
    }

    public function faIcons() {
	    return [
	        'fab fa-500px' => '500px',
	        'fab fa-accessible-icon' => 'accessible-icon',
	        'fab fa-accusoft' => 'accusoft',
	        'fas fa-address-book' => 'address-book',
	        'far fa-address-book' => 'address-book',
	        'fas fa-address-card' => 'address-card',
	        'far fa-address-card' => 'address-card',
	        'fas fa-adjust' => 'adjust',
	        'fab fa-adn' => 'adn',
	        'fab fa-adversal' => 'adversal',
	        'fab fa-affiliatetheme' => 'affiliatetheme',
	        'fab fa-algolia' => 'algolia',
	        'fas fa-align-center' => 'align-center',
	        'fas fa-align-justify' => 'align-justify',
	        'fas fa-align-left' => 'align-left',
	        'fas fa-align-right' => 'align-right',
	        'fas fa-allergies' => 'allergies',
	        'fab fa-amazon' => 'amazon',
	        'fab fa-amazon-pay' => 'amazon-pay',
	        'fas fa-ambulance' => 'ambulance',
	        'fas fa-american-sign-language-interpreting' => 'american-sign-language-interpreting',
	        'fab fa-amilia' => 'amilia',
	        'fas fa-anchor' => 'anchor',
	        'fab fa-android' => 'android',
	        'fab fa-angellist' => 'angellist',
	        'fas fa-angle-double-down' => 'angle-double-down',
	        'fas fa-angle-double-left' => 'angle-double-left',
	        'fas fa-angle-double-right' => 'angle-double-right',
	        'fas fa-angle-double-up' => 'angle-double-up',
	        'fas fa-angle-down' => 'angle-down',
	        'fas fa-angle-left' => 'angle-left',
	        'fas fa-angle-right' => 'angle-right',
	        'fas fa-angle-up' => 'angle-up',
	        'fab fa-angrycreative' => 'angrycreative',
	        'fab fa-angular' => 'angular',
	        'fab fa-app-store' => 'app-store',
	        'fab fa-app-store-ios' => 'app-store-ios',
	        'fab fa-apper' => 'apper',
	        'fab fa-apple' => 'apple',
	        'fab fa-apple-pay' => 'apple-pay',
	        'fas fa-archive' => 'archive',
	        'fas fa-arrow-alt-circle-down' => 'arrow-alt-circle-down',
	        'far fa-arrow-alt-circle-down' => 'arrow-alt-circle-down',
	        'fas fa-arrow-alt-circle-left' => 'arrow-alt-circle-left',
	        'far fa-arrow-alt-circle-left' => 'arrow-alt-circle-left',
	        'fas fa-arrow-alt-circle-right' => 'arrow-alt-circle-right',
	        'far fa-arrow-alt-circle-right' => 'arrow-alt-circle-right',
	        'fas fa-arrow-alt-circle-up' => 'arrow-alt-circle-up',
	        'far fa-arrow-alt-circle-up' => 'arrow-alt-circle-up',
	        'fas fa-arrow-circle-down' => 'arrow-circle-down',
	        'fas fa-arrow-circle-left' => 'arrow-circle-left',
	        'fas fa-arrow-circle-right' => 'arrow-circle-right',
	        'fas fa-arrow-circle-up' => 'arrow-circle-up',
	        'fas fa-arrow-down' => 'arrow-down',
	        'fas fa-arrow-left' => 'arrow-left',
	        'fas fa-arrow-right' => 'arrow-right',
	        'fas fa-arrow-up' => 'arrow-up',
	        'fas fa-arrows-alt' => 'arrows-alt',
	        'fas fa-arrows-alt-h' => 'arrows-alt-h',
	        'fas fa-arrows-alt-v' => 'arrows-alt-v',
	        'fas fa-assistive-listening-systems' => 'assistive-listening-systems',
	        'fas fa-asterisk' => 'asterisk',
	        'fab fa-asymmetrik' => 'asymmetrik',
	        'fas fa-at' => 'at',
	        'fab fa-audible' => 'audible',
	        'fas fa-audio-description' => 'audio-description',
	        'fab fa-autoprefixer' => 'autoprefixer',
	        'fab fa-avianex' => 'avianex',
	        'fab fa-aviato' => 'aviato',
	        'fab fa-aws' => 'aws',
	        'fas fa-backward' => 'backward',
	        'fas fa-balance-scale' => 'balance-scale',
	        'fas fa-ban' => 'ban',
	        'fas fa-band-aid' => 'band-aid',
	        'fab fa-bandcamp' => 'bandcamp',
	        'fas fa-barcode' => 'barcode',
	        'fas fa-bars' => 'bars',
	        'fas fa-baseball-ball' => 'baseball-ball',
	        'fas fa-basketball-ball' => 'basketball-ball',
	        'fas fa-bath' => 'bath',
	        'fas fa-battery-empty' => 'battery-empty',
	        'fas fa-battery-full' => 'battery-full',
	        'fas fa-battery-half' => 'battery-half',
	        'fas fa-battery-quarter' => 'battery-quarter',
	        'fas fa-battery-three-quarters' => 'battery-three-quarters',
	        'fas fa-bed' => 'bed',
	        'fas fa-beer' => 'beer',
	        'fab fa-behance' => 'behance',
	        'fab fa-behance-square' => 'behance-square',
	        'fas fa-bell' => 'bell',
	        'far fa-bell' => 'bell',
	        'fas fa-bell-slash' => 'bell-slash',
	        'far fa-bell-slash' => 'bell-slash',
	        'fas fa-bicycle' => 'bicycle',
	        'fab fa-bimobject' => 'bimobject',
	        'fas fa-binoculars' => 'binoculars',
	        'fas fa-birthday-cake' => 'birthday-cake',
	        'fab fa-bitbucket' => 'bitbucket',
	        'fab fa-bitcoin' => 'bitcoin',
	        'fab fa-bity' => 'bity',
	        'fab fa-black-tie' => 'black-tie',
	        'fab fa-blackberry' => 'blackberry',
	        'fas fa-blind' => 'blind',
	        'fab fa-blogger' => 'blogger',
	        'fab fa-blogger-b' => 'blogger-b',
	        'fab fa-bluetooth' => 'bluetooth',
	        'fab fa-bluetooth-b' => 'bluetooth-b',
	        'fas fa-bold' => 'bold',
	        'fas fa-bolt' => 'bolt',
	        'fas fa-bomb' => 'bomb',
	        'fas fa-book' => 'book',
	        'fas fa-bookmark' => 'bookmark',
	        'far fa-bookmark' => 'bookmark',
	        'fas fa-bowling-ball' => 'bowling-ball',
	        'fas fa-box' => 'box',
	        'fas fa-box-open' => 'box-open',
	        'fas fa-boxes' => 'boxes',
	        'fas fa-braille' => 'braille',
	        'fas fa-briefcase' => 'briefcase',
	        'fas fa-briefcase-medical' => 'briefcase-medical',
	        'fab fa-btc' => 'btc',
	        'fas fa-bug' => 'bug',
	        'fas fa-building' => 'building',
	        'far fa-building' => 'building',
	        'fas fa-bullhorn' => 'bullhorn',
	        'fas fa-bullseye' => 'bullseye',
	        'fas fa-burn' => 'burn',
	        'fab fa-buromobelexperte' => 'buromobelexperte',
	        'fas fa-bus' => 'bus',
	        'fab fa-buysellads' => 'buysellads',
	        'fas fa-calculator' => 'calculator',
	        'fas fa-calendar' => 'calendar',
	        'far fa-calendar' => 'calendar',
	        'fas fa-calendar-alt' => 'calendar-alt',
	        'far fa-calendar-alt' => 'calendar-alt',
	        'fas fa-calendar-check' => 'calendar-check',
	        'far fa-calendar-check' => 'calendar-check',
	        'fas fa-calendar-minus' => 'calendar-minus',
	        'far fa-calendar-minus' => 'calendar-minus',
	        'fas fa-calendar-plus' => 'calendar-plus',
	        'far fa-calendar-plus' => 'calendar-plus',
	        'fas fa-calendar-times' => 'calendar-times',
	        'far fa-calendar-times' => 'calendar-times',
	        'fas fa-camera' => 'camera',
	        'fas fa-camera-retro' => 'camera-retro',
	        'fas fa-capsules' => 'capsules',
	        'fas fa-car' => 'car',
	        'fas fa-caret-down' => 'caret-down',
	        'fas fa-caret-left' => 'caret-left',
	        'fas fa-caret-right' => 'caret-right',
	        'fas fa-caret-square-down' => 'caret-square-down',
	        'far fa-caret-square-down' => 'caret-square-down',
	        'fas fa-caret-square-left' => 'caret-square-left',
	        'far fa-caret-square-left' => 'caret-square-left',
	        'fas fa-caret-square-right' => 'caret-square-right',
	        'far fa-caret-square-right' => 'caret-square-right',
	        'fas fa-caret-square-up' => 'caret-square-up',
	        'far fa-caret-square-up' => 'caret-square-up',
	        'fas fa-caret-up' => 'caret-up',
	        'fas fa-cart-arrow-down' => 'cart-arrow-down',
	        'fas fa-cart-plus' => 'cart-plus',
	        'fab fa-cc-amazon-pay' => 'cc-amazon-pay',
	        'fab fa-cc-amex' => 'cc-amex',
	        'fab fa-cc-apple-pay' => 'cc-apple-pay',
	        'fab fa-cc-diners-club' => 'cc-diners-club',
	        'fab fa-cc-discover' => 'cc-discover',
	        'fab fa-cc-jcb' => 'cc-jcb',
	        'fab fa-cc-mastercard' => 'cc-mastercard',
	        'fab fa-cc-paypal' => 'cc-paypal',
	        'fab fa-cc-stripe' => 'cc-stripe',
	        'fab fa-cc-visa' => 'cc-visa',
	        'fab fa-centercode' => 'centercode',
	        'fas fa-certificate' => 'certificate',
	        'fas fa-chart-area' => 'chart-area',
	        'fas fa-chart-bar' => 'chart-bar',
	        'far fa-chart-bar' => 'chart-bar',
	        'fas fa-chart-line' => 'chart-line',
	        'fas fa-chart-pie' => 'chart-pie',
	        'fas fa-check' => 'check',
	        'fas fa-check-circle' => 'check-circle',
	        'far fa-check-circle' => 'check-circle',
	        'fas fa-check-square' => 'check-square',
	        'far fa-check-square' => 'check-square',
	        'fas fa-chess' => 'chess',
	        'fas fa-chess-bishop' => 'chess-bishop',
	        'fas fa-chess-board' => 'chess-board',
	        'fas fa-chess-king' => 'chess-king',
	        'fas fa-chess-knight' => 'chess-knight',
	        'fas fa-chess-pawn' => 'chess-pawn',
	        'fas fa-chess-queen' => 'chess-queen',
	        'fas fa-chess-rook' => 'chess-rook',
	        'fas fa-chevron-circle-down' => 'chevron-circle-down',
	        'fas fa-chevron-circle-left' => 'chevron-circle-left',
	        'fas fa-chevron-circle-right' => 'chevron-circle-right',
	        'fas fa-chevron-circle-up' => 'chevron-circle-up',
	        'fas fa-chevron-down' => 'chevron-down',
	        'fas fa-chevron-left' => 'chevron-left',
	        'fas fa-chevron-right' => 'chevron-right',
	        'fas fa-chevron-up' => 'chevron-up',
	        'fas fa-child' => 'child',
	        'fab fa-chrome' => 'chrome',
	        'fas fa-circle' => 'circle',
	        'far fa-circle' => 'circle',
	        'fas fa-circle-notch' => 'circle-notch',
	        'fas fa-clipboard' => 'clipboard',
	        'far fa-clipboard' => 'clipboard',
	        'fas fa-clipboard-check' => 'clipboard-check',
	        'fas fa-clipboard-list' => 'clipboard-list',
	        'fas fa-clock' => 'clock',
	        'far fa-clock' => 'clock',
	        'fas fa-clone' => 'clone',
	        'far fa-clone' => 'clone',
	        'fas fa-closed-captioning' => 'closed-captioning',
	        'far fa-closed-captioning' => 'closed-captioning',
	        'fas fa-cloud' => 'cloud',
	        'fas fa-cloud-download-alt' => 'cloud-download-alt',
	        'fas fa-cloud-upload-alt' => 'cloud-upload-alt',
	        'fab fa-cloudscale' => 'cloudscale',
	        'fab fa-cloudsmith' => 'cloudsmith',
	        'fab fa-cloudversify' => 'cloudversify',
	        'fas fa-code' => 'code',
	        'fas fa-code-branch' => 'code-branch',
	        'fab fa-codepen' => 'codepen',
	        'fab fa-codiepie' => 'codiepie',
	        'fas fa-coffee' => 'coffee',
	        'fas fa-cog' => 'cog',
	        'fas fa-cogs' => 'cogs',
	        'fas fa-columns' => 'columns',
	        'fas fa-comment' => 'comment',
	        'far fa-comment' => 'comment',
	        'fas fa-comment-alt' => 'comment-alt',
	        'far fa-comment-alt' => 'comment-alt',
	        'fas fa-comment-dots' => 'comment-dots',
	        'fas fa-comment-slash' => 'comment-slash',
	        'fas fa-comments' => 'comments',
	        'far fa-comments' => 'comments',
	        'fas fa-compass' => 'compass',
	        'far fa-compass' => 'compass',
	        'fas fa-compress' => 'compress',
	        'fab fa-connectdevelop' => 'connectdevelop',
	        'fab fa-contao' => 'contao',
	        'fas fa-copy' => 'copy',
	        'far fa-copy' => 'copy',
	        'fas fa-copyright' => 'copyright',
	        'far fa-copyright' => 'copyright',
	        'fas fa-couch' => 'couch',
	        'fab fa-cpanel' => 'cpanel',
	        'fab fa-creative-commons' => 'creative-commons',
	        'fas fa-credit-card' => 'credit-card',
	        'far fa-credit-card' => 'credit-card',
	        'fas fa-crop' => 'crop',
	        'fas fa-crosshairs' => 'crosshairs',
	        'fab fa-css3' => 'css3',
	        'fab fa-css3-alt' => 'css3-alt',
	        'fas fa-cube' => 'cube',
	        'fas fa-cubes' => 'cubes',
	        'fas fa-cut' => 'cut',
	        'fab fa-cuttlefish' => 'cuttlefish',
	        'fab fa-d-and-d' => 'd-and-d',
	        'fab fa-dashcube' => 'dashcube',
	        'fas fa-database' => 'database',
	        'fas fa-deaf' => 'deaf',
	        'fab fa-delicious' => 'delicious',
	        'fab fa-deploydog' => 'deploydog',
	        'fab fa-deskpro' => 'deskpro',
	        'fas fa-desktop' => 'desktop',
	        'fab fa-deviantart' => 'deviantart',
	        'fas fa-diagnoses' => 'diagnoses',
	        'fab fa-digg' => 'digg',
	        'fab fa-digital-ocean' => 'digital-ocean',
	        'fab fa-discord' => 'discord',
	        'fab fa-discourse' => 'discourse',
	        'fas fa-dna' => 'dna',
	        'fab fa-dochub' => 'dochub',
	        'fab fa-docker' => 'docker',
	        'fas fa-dollar-sign' => 'dollar-sign',
	        'fas fa-dolly' => 'dolly',
	        'fas fa-dolly-flatbed' => 'dolly-flatbed',
	        'fas fa-donate' => 'donate',
	        'fas fa-dot-circle' => 'dot-circle',
	        'far fa-dot-circle' => 'dot-circle',
	        'fas fa-dove' => 'dove',
	        'fas fa-download' => 'download',
	        'fab fa-draft2digital' => 'draft2digital',
	        'fab fa-dribbble' => 'dribbble',
	        'fab fa-dribbble-square' => 'dribbble-square',
	        'fab fa-dropbox' => 'dropbox',
	        'fab fa-drupal' => 'drupal',
	        'fab fa-dyalog' => 'dyalog',
	        'fab fa-earlybirds' => 'earlybirds',
	        'fab fa-edge' => 'edge',
	        'fas fa-edit' => 'edit',
	        'far fa-edit' => 'edit',
	        'fas fa-eject' => 'eject',
	        'fab fa-elementor' => 'elementor',
	        'fas fa-ellipsis-h' => 'ellipsis-h',
	        'fas fa-ellipsis-v' => 'ellipsis-v',
	        'fab fa-ember' => 'ember',
	        'fab fa-empire' => 'empire',
	        'fas fa-envelope' => 'envelope',
	        'far fa-envelope' => 'envelope',
	        'fas fa-envelope-open' => 'envelope-open',
	        'far fa-envelope-open' => 'envelope-open',
	        'fas fa-envelope-square' => 'envelope-square',
	        'fab fa-envira' => 'envira',
	        'fas fa-eraser' => 'eraser',
	        'fab fa-erlang' => 'erlang',
	        'fab fa-ethereum' => 'ethereum',
	        'fab fa-etsy' => 'etsy',
	        'fas fa-euro-sign' => 'euro-sign',
	        'fas fa-exchange-alt' => 'exchange-alt',
	        'fas fa-exclamation' => 'exclamation',
	        'fas fa-exclamation-circle' => 'exclamation-circle',
	        'fas fa-exclamation-triangle' => 'exclamation-triangle',
	        'fas fa-expand' => 'expand',
	        'fas fa-expand-arrows-alt' => 'expand-arrows-alt',
	        'fab fa-expeditedssl' => 'expeditedssl',
	        'fas fa-external-link-alt' => 'external-link-alt',
	        'fas fa-external-link-square-alt' => 'external-link-square-alt',
	        'fas fa-eye' => 'eye',
	        'fas fa-eye-dropper' => 'eye-dropper',
	        'fas fa-eye-slash' => 'eye-slash',
	        'far fa-eye-slash' => 'eye-slash',
	        'fab fa-facebook' => 'facebook',
	        'fab fa-facebook-f' => 'facebook-f',
	        'fab fa-facebook-messenger' => 'facebook-messenger',
	        'fab fa-facebook-square' => 'facebook-square',
	        'fas fa-fast-backward' => 'fast-backward',
	        'fas fa-fast-forward' => 'fast-forward',
	        'fas fa-fax' => 'fax',
	        'fas fa-female' => 'female',
	        'fas fa-fighter-jet' => 'fighter-jet',
	        'fas fa-file' => 'file',
	        'far fa-file' => 'file',
	        'fas fa-file-alt' => 'file-alt',
	        'far fa-file-alt' => 'file-alt',
	        'fas fa-file-archive' => 'file-archive',
	        'far fa-file-archive' => 'file-archive',
	        'fas fa-file-audio' => 'file-audio',
	        'far fa-file-audio' => 'file-audio',
	        'fas fa-file-code' => 'file-code',
	        'far fa-file-code' => 'file-code',
	        'fas fa-file-excel' => 'file-excel',
	        'far fa-file-excel' => 'file-excel',
	        'fas fa-file-image' => 'file-image',
	        'far fa-file-image' => 'file-image',
	        'fas fa-file-medical' => 'file-medical',
	        'fas fa-file-medical-alt' => 'file-medical-alt',
	        'fas fa-file-pdf' => 'file-pdf',
	        'far fa-file-pdf' => 'file-pdf',
	        'fas fa-file-powerpoint' => 'file-powerpoint',
	        'far fa-file-powerpoint' => 'file-powerpoint',
	        'fas fa-file-video' => 'file-video',
	        'far fa-file-video' => 'file-video',
	        'fas fa-file-word' => 'file-word',
	        'far fa-file-word' => 'file-word',
	        'fas fa-film' => 'film',
	        'fas fa-filter' => 'filter',
	        'fas fa-fire' => 'fire',
	        'fas fa-fire-extinguisher' => 'fire-extinguisher',
	        'fab fa-firefox' => 'firefox',
	        'fas fa-first-aid' => 'first-aid',
	        'fab fa-first-order' => 'first-order',
	        'fab fa-firstdraft' => 'firstdraft',
	        'fas fa-flag' => 'flag',
	        'far fa-flag' => 'flag',
	        'fas fa-flag-checkered' => 'flag-checkered',
	        'fas fa-flask' => 'flask',
	        'fab fa-flickr' => 'flickr',
	        'fab fa-flipboard' => 'flipboard',
	        'fab fa-fly' => 'fly',
	        'fas fa-folder' => 'folder',
	        'far fa-folder' => 'folder',
	        'fas fa-folder-open' => 'folder-open',
	        'far fa-folder-open' => 'folder-open',
	        'fas fa-font' => 'font',
	        'fab fa-font-awesome' => 'font-awesome',
	        'fab fa-font-awesome-alt' => 'font-awesome-alt',
	        'fab fa-font-awesome-flag' => 'font-awesome-flag',
	        'fab fa-fonticons' => 'fonticons',
	        'fab fa-fonticons-fi' => 'fonticons-fi',
	        'fas fa-football-ball' => 'football-ball',
	        'fab fa-fort-awesome' => 'fort-awesome',
	        'fab fa-fort-awesome-alt' => 'fort-awesome-alt',
	        'fab fa-forumbee' => 'forumbee',
	        'fas fa-forward' => 'forward',
	        'fab fa-foursquare' => 'foursquare',
	        'fab fa-free-code-camp' => 'free-code-camp',
	        'fab fa-freebsd' => 'freebsd',
	        'fas fa-frown' => 'frown',
	        'far fa-frown' => 'frown',
	        'fas fa-futbol' => 'futbol',
	        'far fa-futbol' => 'futbol',
	        'fas fa-gamepad' => 'gamepad',
	        'fas fa-gavel' => 'gavel',
	        'fas fa-gem' => 'gem',
	        'far fa-gem' => 'gem',
	        'fas fa-genderless' => 'genderless',
	        'fab fa-get-pocket' => 'get-pocket',
	        'fab fa-gg' => 'gg',
	        'fab fa-gg-circle' => 'gg-circle',
	        'fas fa-gift' => 'gift',
	        'fab fa-git' => 'git',
	        'fab fa-git-square' => 'git-square',
	        'fab fa-github' => 'github',
	        'fab fa-github-alt' => 'github-alt',
	        'fab fa-github-square' => 'github-square',
	        'fab fa-gitkraken' => 'gitkraken',
	        'fab fa-gitlab' => 'gitlab',
	        'fab fa-gitter' => 'gitter',
	        'fas fa-glass-martini' => 'glass-martini',
	        'fab fa-glide' => 'glide',
	        'fab fa-glide-g' => 'glide-g',
	        'fas fa-globe' => 'globe',
	        'fab fa-gofore' => 'gofore',
	        'fas fa-golf-ball' => 'golf-ball',
	        'fab fa-goodreads' => 'goodreads',
	        'fab fa-goodreads-g' => 'goodreads-g',
	        'fab fa-google' => 'google',
	        'fab fa-google-drive' => 'google-drive',
	        'fab fa-google-play' => 'google-play',
	        'fab fa-google-plus' => 'google-plus',
	        'fab fa-google-plus-g' => 'google-plus-g',
	        'fab fa-google-plus-square' => 'google-plus-square',
	        'fab fa-google-wallet' => 'google-wallet',
	        'fas fa-graduation-cap' => 'graduation-cap',
	        'fab fa-gratipay' => 'gratipay',
	        'fab fa-grav' => 'grav',
	        'fab fa-gripfire' => 'gripfire',
	        'fab fa-grunt' => 'grunt',
	        'fab fa-gulp' => 'gulp',
	        'fas fa-h-square' => 'h-square',
	        'fab fa-hacker-news' => 'hacker-news',
	        'fab fa-hacker-news-square' => 'hacker-news-square',
	        'fas fa-hand-holding' => 'hand-holding',
	        'fas fa-hand-holding-heart' => 'hand-holding-heart',
	        'fas fa-hand-holding-usd' => 'hand-holding-usd',
	        'fas fa-hand-lizard' => 'hand-lizard',
	        'far fa-hand-lizard' => 'hand-lizard',
	        'fas fa-hand-paper' => 'hand-paper',
	        'far fa-hand-paper' => 'hand-paper',
	        'fas fa-hand-peace' => 'hand-peace',
	        'far fa-hand-peace' => 'hand-peace',
	        'fas fa-hand-point-down' => 'hand-point-down',
	        'far fa-hand-point-down' => 'hand-point-down',
	        'fas fa-hand-point-left' => 'hand-point-left',
	        'far fa-hand-point-left' => 'hand-point-left',
	        'fas fa-hand-point-right' => 'hand-point-right',
	        'far fa-hand-point-right' => 'hand-point-right',
	        'fas fa-hand-point-up' => 'hand-point-up',
	        'far fa-hand-point-up' => 'hand-point-up',
	        'fas fa-hand-pointer' => 'hand-pointer',
	        'far fa-hand-pointer' => 'hand-pointer',
	        'fas fa-hand-rock' => 'hand-rock',
	        'far fa-hand-rock' => 'hand-rock',
	        'fas fa-hand-scissors' => 'hand-scissors',
	        'far fa-hand-scissors' => 'hand-scissors',
	        'fas fa-hand-spock' => 'hand-spock',
	        'far fa-hand-spock' => 'hand-spock',
	        'fas fa-hands' => 'hands',
	        'fas fa-hands-helping' => 'hands-helping',
	        'fas fa-handshake' => 'handshake',
	        'far fa-handshake' => 'handshake',
	        'fas fa-hashtag' => 'hashtag',
	        'fas fa-hdd' => 'hdd',
	        'far fa-hdd' => 'hdd',
	        'fas fa-heading' => 'heading',
	        'fas fa-headphones' => 'headphones',
	        'fas fa-heart' => 'heart',
	        'far fa-heart' => 'heart',
	        'fas fa-heartbeat' => 'heartbeat',
	        'fab fa-hips' => 'hips',
	        'fab fa-hire-a-helper' => 'hire-a-helper',
	        'fas fa-history' => 'history',
	        'fas fa-hockey-puck' => 'hockey-puck',
	        'fas fa-home' => 'home',
	        'fab fa-hooli' => 'hooli',
	        'fas fa-hospital' => 'hospital',
	        'far fa-hospital' => 'hospital',
	        'fas fa-hospital-alt' => 'hospital-alt',
	        'fas fa-hospital-symbol' => 'hospital-symbol',
	        'fab fa-hotjar' => 'hotjar',
	        'fas fa-hourglass' => 'hourglass',
	        'far fa-hourglass' => 'hourglass',
	        'fas fa-hourglass-end' => 'hourglass-end',
	        'fas fa-hourglass-half' => 'hourglass-half',
	        'fas fa-hourglass-start' => 'hourglass-start',
	        'fab fa-houzz' => 'houzz',
	        'fab fa-html5' => 'html5',
	        'fab fa-hubspot' => 'hubspot',
	        'fas fa-i-cursor' => 'i-cursor',
	        'fas fa-id-badge' => 'id-badge',
	        'far fa-id-badge' => 'id-badge',
	        'fas fa-id-card' => 'id-card',
	        'far fa-id-card' => 'id-card',
	        'fas fa-id-card-alt' => 'id-card-alt',
	        'fas fa-image' => 'image',
	        'far fa-image' => 'image',
	        'fas fa-images' => 'images',
	        'far fa-images' => 'images',
	        'fab fa-imdb' => 'imdb',
	        'fas fa-inbox' => 'inbox',
	        'fas fa-indent' => 'indent',
	        'fas fa-industry' => 'industry',
	        'fas fa-info' => 'info',
	        'fas fa-info-circle' => 'info-circle',
	        'fab fa-instagram' => 'instagram',
	        'fab fa-internet-explorer' => 'internet-explorer',
	        'fab fa-ioxhost' => 'ioxhost',
	        'fas fa-italic' => 'italic',
	        'fab fa-itunes' => 'itunes',
	        'fab fa-itunes-note' => 'itunes-note',
	        'fab fa-java' => 'java',
	        'fab fa-jenkins' => 'jenkins',
	        'fab fa-joget' => 'joget',
	        'fab fa-joomla' => 'joomla',
	        'fab fa-js' => 'js',
	        'fab fa-js-square' => 'js-square',
	        'fab fa-jsfiddle' => 'jsfiddle',
	        'fas fa-key' => 'key',
	        'fas fa-keyboard' => 'keyboard',
	        'far fa-keyboard' => 'keyboard',
	        'fab fa-keycdn' => 'keycdn',
	        'fab fa-kickstarter' => 'kickstarter',
	        'fab fa-kickstarter-k' => 'kickstarter-k',
	        'fab fa-korvue' => 'korvue',
	        'fas fa-language' => 'language',
	        'fas fa-laptop' => 'laptop',
	        'fab fa-laravel' => 'laravel',
	        'fab fa-lastfm' => 'lastfm',
	        'fab fa-lastfm-square' => 'lastfm-square',
	        'fas fa-leaf' => 'leaf',
	        'fab fa-leanpub' => 'leanpub',
	        'fas fa-lemon' => 'lemon',
	        'far fa-lemon' => 'lemon',
	        'fab fa-less' => 'less',
	        'fas fa-level-down-alt' => 'level-down-alt',
	        'fas fa-level-up-alt' => 'level-up-alt',
	        'fas fa-life-ring' => 'life-ring',
	        'far fa-life-ring' => 'life-ring',
	        'fas fa-lightbulb' => 'lightbulb',
	        'far fa-lightbulb' => 'lightbulb',
	        'fab fa-line' => 'line',
	        'fas fa-link' => 'link',
	        'fab fa-linkedin' => 'linkedin',
	        'fab fa-linkedin-in' => 'linkedin-in',
	        'fab fa-linode' => 'linode',
	        'fab fa-linux' => 'linux',
	        'fas fa-lira-sign' => 'lira-sign',
	        'fas fa-list' => 'list',
	        'fas fa-list-alt' => 'list-alt',
	        'far fa-list-alt' => 'list-alt',
	        'fas fa-list-ol' => 'list-ol',
	        'fas fa-list-ul' => 'list-ul',
	        'fas fa-location-arrow' => 'location-arrow',
	        'fas fa-lock' => 'lock',
	        'fas fa-lock-open' => 'lock-open',
	        'fas fa-long-arrow-alt-down' => 'long-arrow-alt-down',
	        'fas fa-long-arrow-alt-left' => 'long-arrow-alt-left',
	        'fas fa-long-arrow-alt-right' => 'long-arrow-alt-right',
	        'fas fa-long-arrow-alt-up' => 'long-arrow-alt-up',
	        'fas fa-low-vision' => 'low-vision',
	        'fab fa-lyft' => 'lyft',
	        'fab fa-magento' => 'magento',
	        'fas fa-magic' => 'magic',
	        'fas fa-magnet' => 'magnet',
	        'fas fa-male' => 'male',
	        'fas fa-map' => 'map',
	        'far fa-map' => 'map',
	        'fas fa-map-marker' => 'map-marker',
	        'fas fa-map-marker-alt' => 'map-marker-alt',
	        'fas fa-map-pin' => 'map-pin',
	        'fas fa-map-signs' => 'map-signs',
	        'fas fa-mars' => 'mars',
	        'fas fa-mars-double' => 'mars-double',
	        'fas fa-mars-stroke' => 'mars-stroke',
	        'fas fa-mars-stroke-h' => 'mars-stroke-h',
	        'fas fa-mars-stroke-v' => 'mars-stroke-v',
	        'fab fa-maxcdn' => 'maxcdn',
	        'fab fa-medapps' => 'medapps',
	        'fab fa-medium' => 'medium',
	        'fab fa-medium-m' => 'medium-m',
	        'fas fa-medkit' => 'medkit',
	        'fab fa-medrt' => 'medrt',
	        'fab fa-meetup' => 'meetup',
	        'fas fa-meh' => 'meh',
	        'far fa-meh' => 'meh',
	        'fas fa-mercury' => 'mercury',
	        'fas fa-microchip' => 'microchip',
	        'fas fa-microphone' => 'microphone',
	        'fas fa-microphone-slash' => 'microphone-slash',
	        'fab fa-microsoft' => 'microsoft',
	        'fas fa-minus' => 'minus',
	        'fas fa-minus-circle' => 'minus-circle',
	        'fas fa-minus-square' => 'minus-square',
	        'far fa-minus-square' => 'minus-square',
	        'fab fa-mix' => 'mix',
	        'fab fa-mixcloud' => 'mixcloud',
	        'fab fa-mizuni' => 'mizuni',
	        'fas fa-mobile' => 'mobile',
	        'fas fa-mobile-alt' => 'mobile-alt',
	        'fab fa-modx' => 'modx',
	        'fab fa-monero' => 'monero',
	        'fas fa-money-bill-alt' => 'money-bill-alt',
	        'far fa-money-bill-alt' => 'money-bill-alt',
	        'fas fa-moon' => 'moon',
	        'far fa-moon' => 'moon',
	        'fas fa-motorcycle' => 'motorcycle',
	        'fas fa-mouse-pointer' => 'mouse-pointer',
	        'fas fa-music' => 'music',
	        'fab fa-napster' => 'napster',
	        'fas fa-neuter' => 'neuter',
	        'fas fa-newspaper' => 'newspaper',
	        'far fa-newspaper' => 'newspaper',
	        'fab fa-nintendo-switch' => 'nintendo-switch',
	        'fab fa-node' => 'node',
	        'fab fa-node-js' => 'node-js',
	        'fas fa-notes-medical' => 'notes-medical',
	        'fab fa-npm' => 'npm',
	        'fab fa-ns8' => 'ns8',
	        'fab fa-nutritionix' => 'nutritionix',
	        'fas fa-object-group' => 'object-group',
	        'far fa-object-group' => 'object-group',
	        'fas fa-object-ungroup' => 'object-ungroup',
	        'far fa-object-ungroup' => 'object-ungroup',
	        'fab fa-odnoklassniki' => 'odnoklassniki',
	        'fab fa-odnoklassniki-square' => 'odnoklassniki-square',
	        'fab fa-opencart' => 'opencart',
			'fab fa-tiktok' => 'tiktok',
	        'fab fa-openid' => 'openid',
	        'fab fa-opera' => 'opera',
	        'fab fa-optin-monster' => 'optin-monster',
	        'fab fa-osi' => 'osi',
	        'fas fa-outdent' => 'outdent',
	        'fab fa-page4' => 'page4',
	        'fab fa-pagelines' => 'pagelines',
	        'fas fa-paint-brush' => 'paint-brush',
	        'fab fa-palfed' => 'palfed',
	        'fas fa-pallet' => 'pallet',
	        'fas fa-paper-plane' => 'paper-plane',
	        'far fa-paper-plane' => 'paper-plane',
	        'fas fa-paperclip' => 'paperclip',
	        'fas fa-parachute-box' => 'parachute-box',
	        'fas fa-paragraph' => 'paragraph',
	        'fas fa-paste' => 'paste',
	        'fab fa-patreon' => 'patreon',
	        'fas fa-pause' => 'pause',
	        'fas fa-pause-circle' => 'pause-circle',
	        'far fa-pause-circle' => 'pause-circle',
	        'fas fa-paw' => 'paw',
	        'fab fa-paypal' => 'paypal',
	        'fas fa-pen-square' => 'pen-square',
	        'fas fa-pencil-alt' => 'pencil-alt',
	        'fas fa-people-carry' => 'people-carry',
	        'fas fa-percent' => 'percent',
	        'fab fa-periscope' => 'periscope',
	        'fab fa-phabricator' => 'phabricator',
	        'fab fa-phoenix-framework' => 'phoenix-framework',
	        'fas fa-phone' => 'phone',
	        'fas fa-phone-slash' => 'phone-slash',
	        'fas fa-phone-square' => 'phone-square',
	        'fas fa-phone-volume' => 'phone-volume',
	        'fab fa-php' => 'php',
	        'fab fa-pied-piper' => 'pied-piper',
	        'fab fa-pied-piper-alt' => 'pied-piper-alt',
	        'fab fa-pied-piper-hat' => 'pied-piper-hat',
	        'fab fa-pied-piper-pp' => 'pied-piper-pp',
	        'fas fa-piggy-bank' => 'piggy-bank',
	        'fas fa-pills' => 'pills',
	        'fab fa-pinterest' => 'pinterest',
	        'fab fa-pinterest-p' => 'pinterest-p',
	        'fab fa-pinterest-square' => 'pinterest-square',
	        'fas fa-plane' => 'plane',
	        'fas fa-play' => 'play',
	        'fas fa-play-circle' => 'play-circle',
	        'far fa-play-circle' => 'play-circle',
	        'fab fa-playstation' => 'playstation',
	        'fas fa-plug' => 'plug',
	        'fas fa-plus' => 'plus',
	        'fas fa-plus-circle' => 'plus-circle',
	        'fas fa-plus-square' => 'plus-square',
	        'far fa-plus-square' => 'plus-square',
	        'fas fa-podcast' => 'podcast',
	        'fas fa-poo' => 'poo',
	        'fas fa-pound-sign' => 'pound-sign',
	        'fas fa-power-off' => 'power-off',
	        'fas fa-prescription-bottle' => 'prescription-bottle',
	        'fas fa-prescription-bottle-alt' => 'prescription-bottle-alt',
	        'fas fa-print' => 'print',
	        'fas fa-procedures' => 'procedures',
	        'fab fa-product-hunt' => 'product-hunt',
	        'fab fa-pushed' => 'pushed',
	        'fas fa-puzzle-piece' => 'puzzle-piece',
	        'fab fa-python' => 'python',
	        'fab fa-qq' => 'qq',
	        'fas fa-qrcode' => 'qrcode',
	        'fas fa-question' => 'question',
	        'fas fa-question-circle' => 'question-circle',
	        'far fa-question-circle' => 'question-circle',
	        'fas fa-quidditch' => 'quidditch',
	        'fab fa-quinscape' => 'quinscape',
	        'fab fa-quora' => 'quora',
	        'fas fa-quote-left' => 'quote-left',
	        'fas fa-quote-right' => 'quote-right',
	        'fas fa-random' => 'random',
	        'fab fa-ravelry' => 'ravelry',
	        'fab fa-react' => 'react',
	        'fab fa-readme' => 'readme',
	        'fab fa-rebel' => 'rebel',
	        'fas fa-recycle' => 'recycle',
	        'fab fa-red-river' => 'red-river',
	        'fab fa-reddit' => 'reddit',
	        'fab fa-reddit-alien' => 'reddit-alien',
	        'fab fa-reddit-square' => 'reddit-square',
	        'fas fa-redo' => 'redo',
	        'fas fa-redo-alt' => 'redo-alt',
	        'fas fa-registered' => 'registered',
	        'far fa-registered' => 'registered',
	        'fab fa-rendact' => 'rendact',
	        'fab fa-renren' => 'renren',
	        'fas fa-reply' => 'reply',
	        'fas fa-reply-all' => 'reply-all',
	        'fab fa-replyd' => 'replyd',
	        'fab fa-resolving' => 'resolving',
	        'fas fa-retweet' => 'retweet',
	        'fas fa-ribbon' => 'ribbon',
	        'fas fa-road' => 'road',
	        'fas fa-rocket' => 'rocket',
	        'fab fa-rocketchat' => 'rocketchat',
	        'fab fa-rockrms' => 'rockrms',
	        'fas fa-rss' => 'rss',
	        'fas fa-rss-square' => 'rss-square',
	        'fas fa-ruble-sign' => 'ruble-sign',
	        'fas fa-rupee-sign' => 'rupee-sign',
	        'fab fa-safari' => 'safari',
	        'fab fa-sass' => 'sass',
	        'fas fa-save' => 'save',
	        'far fa-save' => 'save',
	        'fab fa-schlix' => 'schlix',
	        'fab fa-scribd' => 'scribd',
	        'fas fa-search' => 'search',
	        'fas fa-search-minus' => 'search-minus',
	        'fas fa-search-plus' => 'search-plus',
	        'fab fa-searchengin' => 'searchengin',
	        'fas fa-seedling' => 'seedling',
	        'fab fa-sellcast' => 'sellcast',
	        'fab fa-sellsy' => 'sellsy',
	        'fas fa-server' => 'server',
	        'fab fa-servicestack' => 'servicestack',
	        'fas fa-share' => 'share',
	        'fas fa-share-alt' => 'share-alt',
	        'fas fa-share-alt-square' => 'share-alt-square',
	        'fas fa-share-square' => 'share-square',
	        'far fa-share-square' => 'share-square',
	        'fas fa-shekel-sign' => 'shekel-sign',
	        'fas fa-shield-alt' => 'shield-alt',
	        'fas fa-ship' => 'ship',
	        'fas fa-shipping-fast' => 'shipping-fast',
	        'fab fa-shirtsinbulk' => 'shirtsinbulk',
	        'fas fa-shopping-bag' => 'shopping-bag',
	        'fas fa-shopping-basket' => 'shopping-basket',
	        'fas fa-shopping-cart' => 'shopping-cart',
	        'fas fa-shower' => 'shower',
	        'fas fa-sign' => 'sign',
	        'fas fa-sign-in-alt' => 'sign-in-alt',
	        'fas fa-sign-language' => 'sign-language',
	        'fas fa-sign-out-alt' => 'sign-out-alt',
	        'fas fa-signal' => 'signal',
	        'fab fa-simplybuilt' => 'simplybuilt',
	        'fab fa-sistrix' => 'sistrix',
	        'fas fa-sitemap' => 'sitemap',
	        'fab fa-skyatlas' => 'skyatlas',
	        'fab fa-skype' => 'skype',
	        'fab fa-slack' => 'slack',
	        'fab fa-slack-hash' => 'slack-hash',
	        'fas fa-sliders-h' => 'sliders-h',
	        'fab fa-slideshare' => 'slideshare',
	        'fas fa-smile' => 'smile',
	        'far fa-smile' => 'smile',
	        'fas fa-smoking' => 'smoking',
	        'fab fa-snapchat' => 'snapchat',
	        'fab fa-snapchat-ghost' => 'snapchat-ghost',
	        'fab fa-snapchat-square' => 'snapchat-square',
	        'fas fa-snowflake' => 'snowflake',
	        'far fa-snowflake' => 'snowflake',
	        'fas fa-sort' => 'sort',
	        'fas fa-sort-alpha-down' => 'sort-alpha-down',
	        'fas fa-sort-alpha-up' => 'sort-alpha-up',
	        'fas fa-sort-amount-down' => 'sort-amount-down',
	        'fas fa-sort-amount-up' => 'sort-amount-up',
	        'fas fa-sort-down' => 'sort-down',
	        'fas fa-sort-numeric-down' => 'sort-numeric-down',
	        'fas fa-sort-numeric-up' => 'sort-numeric-up',
	        'fas fa-sort-up' => 'sort-up',
	        'fab fa-soundcloud' => 'soundcloud',
	        'fas fa-space-shuttle' => 'space-shuttle',
	        'fab fa-speakap' => 'speakap',
	        'fas fa-spinner' => 'spinner',
	        'fab fa-spotify' => 'spotify',
	        'fas fa-square' => 'square',
	        'far fa-square' => 'square',
	        'fas fa-square-full' => 'square-full',
	        'fab fa-stack-exchange' => 'stack-exchange',
	        'fab fa-stack-overflow' => 'stack-overflow',
	        'fas fa-star' => 'star',
	        'far fa-star' => 'star',
	        'fas fa-star-half' => 'star-half',
	        'far fa-star-half' => 'star-half',
	        'fab fa-staylinked' => 'staylinked',
	        'fab fa-steam' => 'steam',
	        'fab fa-steam-square' => 'steam-square',
	        'fab fa-steam-symbol' => 'steam-symbol',
	        'fas fa-step-backward' => 'step-backward',
	        'fas fa-step-forward' => 'step-forward',
	        'fas fa-stethoscope' => 'stethoscope',
	        'fab fa-sticker-mule' => 'sticker-mule',
	        'fas fa-sticky-note' => 'sticky-note',
	        'far fa-sticky-note' => 'sticky-note',
	        'fas fa-stop' => 'stop',
	        'fas fa-stop-circle' => 'stop-circle',
	        'far fa-stop-circle' => 'stop-circle',
	        'fas fa-stopwatch' => 'stopwatch',
	        'fab fa-strava' => 'strava',
	        'fas fa-street-view' => 'street-view',
	        'fas fa-strikethrough' => 'strikethrough',
	        'fab fa-stripe' => 'stripe',
	        'fab fa-stripe-s' => 'stripe-s',
	        'fab fa-studiovinari' => 'studiovinari',
	        'fab fa-stumbleupon' => 'stumbleupon',
	        'fab fa-stumbleupon-circle' => 'stumbleupon-circle',
	        'fas fa-subscript' => 'subscript',
	        'fas fa-subway' => 'subway',
	        'fas fa-suitcase' => 'suitcase',
	        'fas fa-sun' => 'sun',
	        'far fa-sun' => 'sun',
	        'fab fa-superpowers' => 'superpowers',
	        'fas fa-superscript' => 'superscript',
	        'fab fa-supple' => 'supple',
	        'fas fa-sync' => 'sync',
	        'fas fa-sync-alt' => 'sync-alt',
	        'fas fa-syringe' => 'syringe',
	        'fas fa-table' => 'table',
	        'fas fa-table-tennis' => 'table-tennis',
	        'fas fa-tablet' => 'tablet',
	        'fas fa-tablet-alt' => 'tablet-alt',
	        'fas fa-tablets' => 'tablets',
	        'fas fa-tachometer-alt' => 'tachometer-alt',
	        'fas fa-tag' => 'tag',
	        'fas fa-tags' => 'tags',
	        'fas fa-tape' => 'tape',
	        'fas fa-tasks' => 'tasks',
	        'fas fa-taxi' => 'taxi',
	        'fab fa-telegram' => 'telegram',
	        'fab fa-telegram-plane' => 'telegram-plane',
	        'fab fa-tencent-weibo' => 'tencent-weibo',
	        'fas fa-terminal' => 'terminal',
	        'fas fa-text-height' => 'text-height',
	        'fas fa-text-width' => 'text-width',
	        'fas fa-th' => 'th',
	        'fas fa-th-large' => 'th-large',
	        'fas fa-th-list' => 'th-list',
	        'fab fa-themeisle' => 'themeisle',
	        'fas fa-thermometer' => 'thermometer',
	        'fas fa-thermometer-empty' => 'thermometer-empty',
	        'fas fa-thermometer-full' => 'thermometer-full',
	        'fas fa-thermometer-half' => 'thermometer-half',
	        'fas fa-thermometer-quarter' => 'thermometer-quarter',
	        'fas fa-thermometer-three-quarters' => 'thermometer-three-quarters',
	        'fas fa-thumbs-down' => 'thumbs-down',
	        'far fa-thumbs-down' => 'thumbs-down',
	        'fas fa-thumbs-up' => 'thumbs-up',
	        'far fa-thumbs-up' => 'thumbs-up',
	        'fas fa-thumbtack' => 'thumbtack',
	        'fas fa-ticket-alt' => 'ticket-alt',
	        'fas fa-times' => 'times',
	        'fas fa-times-circle' => 'times-circle',
	        'far fa-times-circle' => 'times-circle',
	        'fas fa-tint' => 'tint',
	        'fas fa-toggle-off' => 'toggle-off',
	        'fas fa-toggle-on' => 'toggle-on',
	        'fas fa-trademark' => 'trademark',
	        'fas fa-train' => 'train',
	        'fas fa-transgender' => 'transgender',
	        'fas fa-transgender-alt' => 'transgender-alt',
	        'fas fa-trash' => 'trash',
	        'fas fa-trash-alt' => 'trash-alt',
	        'far fa-trash-alt' => 'trash-alt',
	        'fas fa-tree' => 'tree',
	        'fab fa-trello' => 'trello',
	        'fab fa-tripadvisor' => 'tripadvisor',
	        'fas fa-trophy' => 'trophy',
	        'fas fa-truck' => 'truck',
	        'fas fa-truck-loading' => 'truck-loading',
	        'fas fa-truck-moving' => 'truck-moving',
	        'fas fa-tty' => 'tty',
	        'fab fa-tumblr' => 'tumblr',
	        'fab fa-tumblr-square' => 'tumblr-square',
	        'fas fa-tv' => 'tv',
	        'fab fa-twitch' => 'twitch',
	        'fab fa-twitter' => 'twitter',
			'fab fa-x-twitter' => 'x',
	        'fab fa-twitter-square' => 'twitter-square',
	        'fab fa-typo3' => 'typo3',
	        'fab fa-uber' => 'uber',
	        'fab fa-uikit' => 'uikit',
	        'fas fa-umbrella' => 'umbrella',
	        'fas fa-underline' => 'underline',
	        'fas fa-undo' => 'undo',
	        'fas fa-undo-alt' => 'undo-alt',
	        'fab fa-uniregistry' => 'uniregistry',
	        'fas fa-universal-access' => 'universal-access',
	        'fas fa-university' => 'university',
	        'fas fa-unlink' => 'unlink',
	        'fas fa-unlock' => 'unlock',
	        'fas fa-unlock-alt' => 'unlock-alt',
	        'fab fa-untappd' => 'untappd',
	        'fas fa-upload' => 'upload',
	        'fab fa-usb' => 'usb',
	        'fas fa-user' => 'user',
	        'far fa-user' => 'user',
	        'fas fa-user-circle' => 'user-circle',
	        'far fa-user-circle' => 'user-circle',
	        'fas fa-user-md' => 'user-md',
	        'fas fa-user-plus' => 'user-plus',
	        'fas fa-user-secret' => 'user-secret',
	        'fas fa-user-times' => 'user-times',
	        'fas fa-users' => 'users',
	        'fab fa-ussunnah' => 'ussunnah',
	        'fas fa-utensil-spoon' => 'utensil-spoon',
	        'fas fa-utensils' => 'utensils',
	        'fab fa-vaadin' => 'vaadin',
	        'fas fa-venus' => 'venus',
	        'fas fa-venus-double' => 'venus-double',
	        'fas fa-venus-mars' => 'venus-mars',
	        'fab fa-viacoin' => 'viacoin',
	        'fab fa-viadeo' => 'viadeo',
	        'fab fa-viadeo-square' => 'viadeo-square',
	        'fas fa-vial' => 'vial',
	        'fas fa-vials' => 'vials',
	        'fab fa-viber' => 'viber',
	        'fas fa-video' => 'video',
	        'fas fa-video-slash' => 'video-slash',
	        'fab fa-vimeo' => 'vimeo',
	        'fab fa-vimeo-square' => 'vimeo-square',
	        'fab fa-vimeo-v' => 'vimeo-v',
	        'fab fa-vine' => 'vine',
	        'fab fa-vk' => 'vk',
	        'fab fa-vnv' => 'vnv',
	        'fas fa-volleyball-ball' => 'volleyball-ball',
	        'fas fa-volume-down' => 'volume-down',
	        'fas fa-volume-off' => 'volume-off',
	        'fas fa-volume-up' => 'volume-up',
	        'fab fa-vuejs' => 'vuejs',
	        'fas fa-warehouse' => 'warehouse',
	        'fab fa-weibo' => 'weibo',
	        'fas fa-weight' => 'weight',
	        'fab fa-weixin' => 'weixin',
	        'fab fa-whatsapp' => 'whatsapp',
	        'fab fa-whatsapp-square' => 'whatsapp-square',
	        'fas fa-wheelchair' => 'wheelchair',
	        'fab fa-whmcs' => 'whmcs',
	        'fas fa-wifi' => 'wifi',
	        'fab fa-wikipedia-w' => 'wikipedia-w',
	        'fas fa-window-close' => 'window-close',
	        'far fa-window-close' => 'window-close',
	        'fas fa-window-maximize' => 'window-maximize',
	        'far fa-window-maximize' => 'window-maximize',
	        'fas fa-window-minimize' => 'window-minimize',
	        'far fa-window-minimize' => 'window-minimize',
	        'fas fa-window-restore' => 'window-restore',
	        'far fa-window-restore' => 'window-restore',
	        'fab fa-windows' => 'windows',
	        'fas fa-wine-glass' => 'wine-glass',
	        'fas fa-won-sign' => 'won-sign',
	        'fab fa-wordpress' => 'wordpress',
	        'fab fa-wordpress-simple' => 'wordpress-simple',
	        'fab fa-wpbeginner' => 'wpbeginner',
	        'fab fa-wpexplorer' => 'wpexplorer',
	        'fab fa-wpforms' => 'wpforms',
	        'fas fa-wrench' => 'wrench',
	        'fas fa-x-ray' => 'x-ray',
	        'fab fa-xbox' => 'xbox',
	        'fab fa-xing' => 'xing',
	        'fab fa-xing-square' => 'xing-square',
	        'fab fa-y-combinator' => 'y-combinator',
	        'fab fa-yahoo' => 'yahoo',
	        'fab fa-yelp' => 'yelp',
	        'fas fa-yen-sign' => 'yen-sign',
	        'fab fa-yoast' => 'yoast',
	        'fab fa-youtube' => 'youtube',
	    ];
	}
}
