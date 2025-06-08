<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ModelOctemplatesHelper extends Controller {

    public function getManualRecommendations($products) {

        if(empty($products)) return array();

        $recommended_products = array();

        foreach ($products as $product) {
            $manual_query = $this->db->query("
                SELECT pr.related_id 
                FROM " . DB_PREFIX . "product_related pr 
                JOIN " . DB_PREFIX . "product p ON (pr.related_id = p.product_id) 
                JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) 
                WHERE pr.product_id = '" . (int)$product['product_id'] . "' 
                AND p.status = '1' 
                AND p.date_available <= NOW() 
                AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' 
                AND p.quantity > 0 
                LIMIT 5
            ");

            foreach ($manual_query->rows as $result) {
                if (!isset($recommended_products[$result['related_id']])) {
                    $recommended_products[$result['related_id']] = $result['related_id'];
                }
            }
        }

        return $recommended_products;
    }

    public function getAutomaticRecommendations($products) {
        if (empty($products)) return array();

        $cache_table = DB_PREFIX . 'oct_bought_together_cache';
        if (!$this->tableExists($cache_table)) {
            return $this->getAutomaticRecommendationsDirect($products);
        }

        $first_product_id = isset($products[0]['product_id']) ? (int)$products[0]['product_id'] : 0;

        $query = $this->db->query("
            SELECT with_product_id 
            FROM {$cache_table}
            WHERE product_id = '" . $first_product_id . "'
            ORDER BY total_count DESC
            LIMIT 12
        ");

        if ($query->num_rows) {
            $recommended = [];
            foreach ($query->rows as $row) {
                $wid = (int)$row['with_product_id'];
                $recommended[$wid] = $wid;
            }
            return $recommended;
        } 
    }

    public function octMassGenerateBoughtTogether() {
        $query = $this->db->query("
            SELECT DISTINCT op.product_id
            FROM " . DB_PREFIX . "order_product op
            JOIN " . DB_PREFIX . "product p ON op.product_id = p.product_id
            JOIN " . DB_PREFIX . "product_to_store p2s ON p.product_id = p2s.product_id
            WHERE p.status = '1'
            AND p.date_available <= NOW()
            AND p.quantity > 0
            AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'
        ");
        $all_products = $query->rows;
        $cache_table = DB_PREFIX . 'oct_bought_together_cache';
        $this->db->query("TRUNCATE TABLE {$cache_table}");
        $values = [];

        foreach ($all_products as $p) {
            $pid = (int)$p['product_id'];
            $recommendations = $this->getAutomaticRecommendationsDirect(
                [['product_id' => $pid]],
                true
            );
            foreach ($recommendations as $with_id => $cnt) {
                if ($with_id == $pid) continue;
                $values[] = "(" . $pid . "," . (int)$with_id . "," . (int)$cnt . ")";
            }
        }
        
        if (!empty($values)) {
            $sql = "INSERT INTO {$cache_table} (product_id, with_product_id, total_count)
                    VALUES " . implode(',', $values);
            $this->db->query($sql);
        }
    }
    
    private function tableExists($table_name) {
        $check = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape($table_name) . "'");
        return (bool)$check->num_rows;
    }

    private function getAutomaticRecommendationsDirect($products, $return_count = false) {
        if (empty($products)) return array();
        $recommended_products = [];
        foreach ($products as $product) {
            $auto_query = $this->db->query("
                SELECT op2.product_id, COUNT(*) as count_total
                FROM " . DB_PREFIX . "order_product op1
                JOIN " . DB_PREFIX . "order_product op2 ON op1.order_id = op2.order_id
                JOIN " . DB_PREFIX . "product p ON (op2.product_id = p.product_id)
                JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id)
                WHERE op1.product_id = '" . (int)$product['product_id'] . "'
                  AND op2.product_id != '" . (int)$product['product_id'] . "'
                  AND p.status = '1'
                  AND p.date_available <= NOW()
                  AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'
                  AND p.quantity > 0
                GROUP BY op2.product_id
                ORDER BY count_total DESC
                LIMIT 12
            ");
            foreach ($auto_query->rows as $row) {
                $wid = (int)$row['product_id'];
                $cnt = (int)$row['count_total'];
                if ($return_count) {
                    $recommended_products[$wid] = ($recommended_products[$wid] ?? 0) + $cnt;
                } else {
                    $recommended_products[$wid] = $wid;
                }
            }
        }
        if ($return_count) {
            arsort($recommended_products);
        }
        return $recommended_products;
    }
}