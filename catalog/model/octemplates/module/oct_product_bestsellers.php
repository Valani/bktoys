<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ModelOctemplatesModuleOctProductBestsellers extends Model {

    public function getBestsellers($data) { 

        $categoryFilter = "";
    
        if (isset($data['category_id']) && $data['category_id']) {
            $categoriesToConsider = [(int)$data['category_id']];
    
            if (isset($data['subcategories']) && $data['subcategories']) {
                $subCategoryIds = $this->getAllSubCategories($data['category_id']);
                $categoriesToConsider = array_merge($categoriesToConsider, $subCategoryIds);
            }
    
            $categoryFilter = " AND p2c.category_id IN (" . implode(',', $categoriesToConsider) . ")";
        }
    
        $sql = "SELECT op.product_id, COUNT(op.product_id) AS total
                FROM " . DB_PREFIX . "order_product op
                JOIN " . DB_PREFIX . "order o ON op.order_id = o.order_id
                JOIN " . DB_PREFIX . "product p ON op.product_id = p.product_id
                JOIN " . DB_PREFIX . "product_to_store p2s ON p.product_id = p2s.product_id
                JOIN " . DB_PREFIX . "product_to_category p2c ON p.product_id = p2c.product_id
                WHERE o.order_status_id > '0' 
                AND p.status = '1' 
                AND p.date_available <= NOW() 
                AND p.quantity > '0' 
                AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'" . $categoryFilter;
    
        if (isset($data['manufacturer_id']) && $data['manufacturer_id']) {
            $sql .= " AND p.manufacturer_id = '" . (int)$data['manufacturer_id'] . "'";
        }
    
        $sql .= " GROUP BY op.product_id
                  ORDER BY total DESC";
    
        if (isset($data['limit'])) {
            $sql .= " LIMIT " . (int)$data['limit'];
        }
    
        $product_data = $this->db->query($sql)->rows;
    
        return $product_data;
    }
    
    private function getAllSubCategories($category_id) {
        $subCategories = [];
        $sql = "SELECT category_id FROM " . DB_PREFIX . "category WHERE parent_id = '" . (int)$category_id . "'";
        $result = $this->db->query($sql);
        foreach ($result->rows as $row) {
            $subCategories[] = $row['category_id'];
            $subCategories = array_merge($subCategories, $this->getAllSubCategories($row['category_id']));
        }
        return $subCategories;
    }
}