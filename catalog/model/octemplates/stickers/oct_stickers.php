<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ModelOCTemplatesStickersOctStickers extends Model {
	public function getOCTStickers($result) {
		$oct_stickers = [];

		if ($this->config->get('oct_stickers_status') && !empty($result['product_id'])) {
			$languageId = (int)$this->config->get('config_language_id');

			$query = $this->db->query(
				"SELECT `stickers_json` FROM `" . DB_PREFIX . "oct_product_stickers` 
				WHERE `product_id` = '" . (int)$result['product_id'] . "' 
				LIMIT 1"
			);

			if ($query->num_rows) {
				$decoded = json_decode($query->row['stickers_json'], true);
				if (!empty($decoded[$languageId]) && is_array($decoded[$languageId])) {
					foreach ($decoded[$languageId] as $key => $sticker) {
						if (is_array($sticker)) {
							$oct_stickers['stickers'][$key] = $sticker['title'] ?? '';
						} else {
							$oct_stickers['stickers'][$key] = $sticker;
						}
					}
				}
			}
		}

		return $oct_stickers;
	}

	public function generateStickersForAllProductsJson() {
        $this->load->model('localisation/language');
        $languages = $this->model_localisation_language->getLanguages();
        $originalLangId = $this->config->get('config_language_id');
    
        $allProducts = $this->getProductsForStickers();
    
        $cache_table = DB_PREFIX . 'oct_product_stickers';
        $this->db->query("TRUNCATE TABLE `{$cache_table}`");
    
        $inserts = [];
        $chunkSize = 500;
    
        foreach ($allProducts as $product) {
            $productId = (int)$product['product_id'];
            $stickersJson = $this->generateStickersJson($product, $languages, $originalLangId);
    
            if ($stickersJson === null) {
                continue;
            }
    
            $inserts[] = "('$productId', '$stickersJson')";
    
            if (count($inserts) >= $chunkSize) {
                $this->db->query(
                    "INSERT INTO `{$cache_table}` (`product_id`, `stickers_json`) VALUES " . implode(", ", $inserts)
                );
                $inserts = [];
            }
        }
    
        if (!empty($inserts)) {
            $this->db->query(
                "INSERT INTO `{$cache_table}` (`product_id`, `stickers_json`) VALUES " . implode(", ", $inserts)
            );
        }
    
        $this->config->set('config_language_id', $originalLangId);
    }
    
    public function generateStickersForSingleProductJson($product_id) {
        $rows = $this->getProductsForStickers($product_id);
        if (empty($rows)) {
            return;
        }
    
        $product = $rows[0];
    
        $this->load->model('localisation/language');
        $languages = $this->model_localisation_language->getLanguages();
        $originalLangId = $this->config->get('config_language_id');
    
        $json = $this->generateStickersJson($product, $languages, $originalLangId);
    
        if ($json === null) {
            return;
        }
    
        $this->db->query(
            "DELETE FROM `" . DB_PREFIX . "oct_product_stickers` WHERE `product_id` = '" . (int)$product_id . "'"
        );
    
        $this->db->query(
            "INSERT INTO `" . DB_PREFIX . "oct_product_stickers` SET `product_id` = '" . (int)$product_id . "', `stickers_json` = '" . $json . "'"
        );
    }
    
    private function generateStickersJson($product, $languages, $originalLangId) {
        $stickersByLang = [];
    
        foreach ($languages as $lang) {
            $langId = (int)$lang['language_id'];
    
            $this->config->set('config_language_id', $langId);
            $stickersData = $this->getStickersLogic($product);
    
            if (!empty($stickersData['stickers'])) {
                $stickersByLang[$langId] = $stickersData['stickers'];
            }
        }
    
        $this->config->set('config_language_id', $originalLangId);
    
        if (empty($stickersByLang)) {
            return null;
        }
    
        return $this->db->escape(json_encode($stickersByLang, JSON_UNESCAPED_UNICODE));
    }

    private function getProductsForStickers($product_id = null) {
        $sql = "
            SELECT
                p.product_id,
                p.date_added,
                p.viewed,
                p.quantity,
                p.oct_stickers,
                p.price,
                (
                    SELECT price
                    FROM " . DB_PREFIX . "product_special ps
                    WHERE ps.product_id = p.product_id
                    AND ps.customer_group_id = '1'
                    AND (
                        (ps.date_start = '0000-00-00' OR ps.date_start < NOW())
                        AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())
                    )
                    ORDER BY ps.priority ASC, ps.price ASC
                    LIMIT 1
                ) AS special
            FROM `" . DB_PREFIX . "product` p
            WHERE p.status = '1'
        ";

        if (!is_null($product_id)) {
            $sql .= " AND p.product_id = '" . (int)$product_id . "' LIMIT 1";
        }

        $query = $this->db->query($sql);
        $rows = $query->rows;

        foreach ($rows as &$row) {
            if (!empty($row['oct_stickers'])) {
                $temp = @unserialize($row['oct_stickers']);
                $row['oct_stickers'] = is_array($temp) ? $temp : [];
            } else {
                $row['oct_stickers'] = [];
            }
        }

        return $rows;
    }

    private function getStickersLogic($product_data) {
        $generated = [];
        $generated['stickers'] = [];

        if (!$this->config->get('oct_stickers_status')) {
            return $generated;
        }

        $oct_config = $this->config->get('oct_stickers_data');
        $is_special = (!empty($product_data['special']) && (float)$product_data['special']) ? true : false;

        $sticker_types = ['new', 'bestseller', 'popular', 'special', 'sold', 'ends', 'free_shipping'];

        foreach ($sticker_types as $type) {
            if ($this->checkAutoSticker($oct_config, $type, $product_data, $is_special)) {
                $generated['stickers']['stickers_' . $type] = $this->buildStickerData($oct_config, $type);
            }
        }

        if (!empty($product_data['oct_stickers'])) {
            $this->load->model('tool/image');
            $current_lang = (int)$this->config->get('config_language_id');

            foreach ($product_data['oct_stickers'] as $sticker_key) {
                $parts = explode('_', $sticker_key);
                if (empty($oct_config[$parts[0]][$parts[1]])) {
                    continue;
                }
                $cfg = $oct_config[$parts[0]][$parts[1]];
                if (empty($cfg['status'])) {
                    continue;
                }
                $title = !empty($cfg['title'][$current_lang]) ? $cfg['title'][$current_lang] : '';
                if (!$title) {
                    continue;
                }
                $description = !empty($cfg['description'][$current_lang]) ? nl2br($cfg['description'][$current_lang]) : false;
                $image = !empty($cfg['image']) ? $cfg['image'] : false;
                $sort_order = isset($cfg['sort']) ? (int)$cfg['sort'] : 0;
                if ($image && is_file(DIR_IMAGE . $image)) {
                    $image = $this->model_tool_image->resize($image, 32, 32);
                }
                $generated['stickers']['stickers_' . $parts[1]] = [
                    'title'       => $title,
                    'description' => $description,
                    'image'       => $image,
                    'sort'        => $sort_order
                ];
            }
        }

        if (!empty($generated['stickers'])) {
            $sorts = [];
            foreach ($generated['stickers'] as $code => $info) {
                $sorts[$code] = isset($info['sort']) ? (int)$info['sort'] : 0;
            }
            array_multisort($sorts, SORT_ASC, $generated['stickers']);
        }

        return $generated;
    }

    private function checkAutoSticker($oct_config, $type, $product, $is_special) {
        if (empty($oct_config['stickers'][$type]['status']) ||
            empty($oct_config['stickers'][$type]['auto']) ||
            $oct_config['stickers'][$type]['auto'] !== 'on') {
            return false;
        }

        switch ($type) {
            case 'new':
                return strtotime($product['date_added']) >= strtotime('-' . (int)$oct_config['stickers'][$type]['count'] . ' day');
            case 'free_shipping':
                $actual_price = !empty($product['special']) ? (float)$product['special'] : (float)$product['price'];
                return $actual_price >= (float)$oct_config['stickers'][$type]['count'];
            case 'bestseller':
                return $this->getOCTBestSellerProducts($product['product_id']) >= (int)$oct_config['stickers'][$type]['count'];
            case 'popular':
                return $product['viewed'] > (int)$oct_config['stickers'][$type]['count'];
            case 'special':
                return $is_special;
            case 'sold':
                return ((int)$product['quantity'] === (int)$oct_config['stickers'][$type]['count']);
            case 'ends':
                return ((int)$product['quantity'] > 0) && ((int)$product['quantity'] <= (int)$oct_config['stickers'][$type]['count']);
            default:
                return false;
        }
    }

    private function getOCTBestSellerProducts($product_id) {
        $query = $this->db->query("SELECT SUM(op.quantity) AS total FROM " . DB_PREFIX . "order_product op LEFT JOIN `" . DB_PREFIX . "order` o ON (op.order_id = o.order_id) LEFT JOIN `" . DB_PREFIX . "product` p ON (op.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE o.order_status_id > '0' AND p.product_id = '". (int)$product_id ."' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id')."'");

        return $query->row['total'];
    }

    private function buildStickerData($oct_config, $type) {
        if ($type === 'special' && empty($oct_config['stickers']['special']['view_title'])) {
            return [];
        }

        $lang_id = (int)$this->config->get('config_language_id');
        $title = !empty($oct_config['stickers'][$type]['title'][$lang_id])
            ? $oct_config['stickers'][$type]['title'][$lang_id]
            : '';
        $sort_val = !empty($oct_config['stickers'][$type]['sort'])
            ? (int)$oct_config['stickers'][$type]['sort']
            : 0;

        return [
            'title' => $title,
            'sort'  => $sort_val
        ];
    }
}