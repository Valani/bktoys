<?php
class ModelOCTemplatesModuleCsvBookToys extends Model {
    
    public function getProductIdBySku($sku) {
        $query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE sku = '" . $this->db->escape($sku) . "' LIMIT 1");
        
        if ($query->num_rows) {
            return $query->row['product_id'];
        }
        
        return false;
    }
    
    public function updateProductDescription($product_id, $language_id, $data) {
        $set = array();
        
        if (isset($data['name'])) {
            $set[] = "name = '" . $this->db->escape($data['name']) . "'";
        }
        if (isset($data['meta_h1'])) {
            $set[] = "meta_h1 = '" . $this->db->escape($data['meta_h1']) . "'";
        }
        if (isset($data['description'])) {
            $set[] = "description = '" . $this->db->escape($data['description']) . "'";
        }
        
        if (!empty($set)) {
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$product_id . "' AND language_id = '" . (int)$language_id . "'");
            
            if ($query->num_rows) {
                $this->db->query("UPDATE " . DB_PREFIX . "product_description SET " . implode(", ", $set) . " WHERE product_id = '" . (int)$product_id . "' AND language_id = '" . (int)$language_id . "'");
            }
        }
    }
    
    public function getCategoryIdByName($name) {
        $query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "category_description WHERE name = '" . $this->db->escape($name) . "' LIMIT 1");
        
        if ($query->num_rows) {
            return $query->row['category_id'];
        }
        
        return false;
    }
    
    public function linkProductToCategory($product_id, $category_id, $main_category = 0) {
        $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "', main_category = '" . (int)$main_category . "'");
    }
    
    public function deleteProductCategories($product_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");
    }
    
    public function getAttributeIdByName($name) {
        $query = $this->db->query("SELECT attribute_id FROM " . DB_PREFIX . "attribute_description WHERE name = '" . $this->db->escape($name) . "' LIMIT 1");
        
        if ($query->num_rows) {
            return $query->row['attribute_id'];
        }
        
        return false;
    }
    
    public function saveProductAttribute($product_id, $attribute_id, $language_id, $text) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$attribute_id . "' AND language_id = '" . (int)$language_id . "'");
        
        $this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$attribute_id . "', language_id = '" . (int)$language_id . "', text = '" . $this->db->escape($text) . "'");
    }

    public function updateProductStatus($product_id, $status) {
        $this->db->query("UPDATE " . DB_PREFIX . "product SET status = '" . (int)$status . "' WHERE product_id = '" . (int)$product_id . "'");
    }
}
