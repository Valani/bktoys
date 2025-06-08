<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ModelOCTemplatesModuleOctLiveSearch extends Model {
    public function doSearch($data = array()) {
        $key = isset($data['search']) ? trim($data['search']) : '';
        
        if (!$key) {
            return [];
        }

        $originalKey = $key;
        $result = $this->performSearch($key, $data);

        if (!empty($result['results'])) {
            return $result;
        }

        $search_fallback_start = isset($data['oct_live_search_data']['search_fallback_start']) ? (int)$data['oct_live_search_data']['search_fallback_start'] : 4;

        if (isset($data['oct_live_search_data']['search_fallback']) && $data['oct_live_search_data']['search_fallback'] === "on" && mb_strlen($key) >= $search_fallback_start) {
            $keyboard_layouts = [$this->getKeyboardLayoutEngToUkr(), $this->getKeyboardLayoutUkrToEng()];

            foreach ($keyboard_layouts as $keyboard_layout) {
                $fixedKey = $this->fixKeyboardLayout($originalKey, $keyboard_layout);
                if ($fixedKey !== $originalKey) {
                    $result = $this->performSearch($fixedKey, $data);
                    if (!empty($result['results'])) {
                        $result['original_query'] = $originalKey;
                        $result['corrected_query'] = $fixedKey;
                        return $result;
                    }
                }
            }
        }

        return ['results' => []];
    }

    private function performSearch($key, $data) {
        if (!isset($data)) {
            $data = [];
        }
    
        $key_lower = mb_strtolower($key);
    
        $keywords = preg_split('/\s+/', $key_lower);
        $keywords = array_filter($keywords, function($word) {
            return mb_strlen($word) >= 2;
        });
    
        if (empty($keywords)) {
            return [];
        }
    
        $founded_content = [];
    
        $entity_keywords = array_filter($keywords, function($word) use ($data) {
            $data['oct_live_search_data'] = $this->config->get('theme_oct_remarket_live_search_data');
            return mb_strlen($word) >= (isset($data['oct_live_search_data']['count_subresults']) ? (int)$data['oct_live_search_data']['count_subresults'] : 4);
        });
    
        $categories = [];
        $manufacturers = [];
    
        if (!empty($entity_keywords)) {
            if (isset($data['category']) && $data['category']) {
                $categories = $this->searchEntities('category', $entity_keywords, $data);
                $founded_content = array_merge($founded_content, $categories);
            }
    
            if (isset($data['manufacturer']) && $data['manufacturer']) {
                $manufacturers = $this->searchEntities('manufacturer', $entity_keywords, $data);
                $founded_content = array_merge($founded_content, $manufacturers);
            }
        }
    
        $products = $this->searchProducts($keywords, $data);
        $founded_content = array_merge($founded_content, $products);
        
        if (isset($data['oct_live_search_data']['found_in_categories']) && $data['oct_live_search_data']['found_in_categories'] == "on" && empty($categories) && !empty($products)) {
            $product_categories = $this->getCategoriesFromProducts($products);
    
            foreach ($product_categories as &$category) {
                $category['found_in_products'] = true;
            }
    
            $founded_content = array_merge($founded_content, $product_categories);
        }
    
        return ['results' => $founded_content];
    }

    private function fixKeyboardLayout($text, $layoutMap) {
        $fixedText = '';
        $length = mb_strlen($text);
        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($text, $i, 1);
            $fixedText .= isset($layoutMap[$char]) ? $layoutMap[$char] : $char;
        }
        return $fixedText;
    }

    private function getKeyboardLayoutEngToUkr() {
        return [
            'q' => 'й', 'w' => 'ц', 'e' => 'у', 'r' => 'к', 't' => 'е',
            'y' => 'н', 'u' => 'г', 'i' => 'ш', 'o' => 'щ', 'p' => 'з',
            '[' => 'х', ']' => 'ї', 'a' => 'ф', 's' => 'і', 'd' => 'в',
            'f' => 'а', 'g' => 'п', 'h' => 'р', 'j' => 'о', 'k' => 'л',
            'l' => 'д', ';' => 'ж', "'" => 'є', 'z' => 'я', 'x' => 'ч',
            'c' => 'с', 'v' => 'м', 'b' => 'и', 'n' => 'т', 'm' => 'ь',
            ',' => 'б', '.' => 'ю', '/' => '.',
            'Q' => 'Й', 'W' => 'Ц', 'E' => 'У', 'R' => 'К', 'T' => 'Е',
            'Y' => 'Н', 'U' => 'Г', 'I' => 'Ш', 'O' => 'Щ', 'P' => 'З',
            '{' => 'Х', '}' => 'Ї', 'A' => 'Ф', 'S' => 'І', 'D' => 'В',
            'F' => 'А', 'G' => 'П', 'H' => 'Р', 'J' => 'О', 'K' => 'Л',
            'L' => 'Д', ':' => 'Ж', '"' => 'Є', 'Z' => 'Я', 'X' => 'Ч',
            'C' => 'С', 'V' => 'М', 'B' => 'И', 'N' => 'Т', 'M' => 'Ь',
            '<' => 'Б', '>' => 'Ю', '?' => ',',
        ];
    }

    private function getKeyboardLayoutUkrToEng() {
        return [
            'й' => 'q', 'ц' => 'w', 'у' => 'e', 'к' => 'r', 'е' => 't',
            'н' => 'y', 'г' => 'u', 'ш' => 'i', 'щ' => 'o', 'з' => 'p',
            'х' => '[', 'ї' => ']', 'ф' => 'a', 'і' => 's', 'в' => 'd',
            'а' => 'f', 'п' => 'g', 'р' => 'h', 'о' => 'j', 'л' => 'k',
            'д' => 'l', 'ж' => ';', 'є' => "'", 'я' => 'z', 'ч' => 'x',
            'с' => 'c', 'м' => 'v', 'и' => 'b', 'т' => 'n', 'ь' => 'm',
            'б' => ',', 'ю' => '.', '.' => '/',
            'Й' => 'Q', 'Ц' => 'W', 'У' => 'E', 'К' => 'R', 'Е' => 'T',
            'Н' => 'Y', 'Г' => 'U', 'Ш' => 'I', 'Щ' => 'O', 'З' => 'P',
            'Х' => '{', 'Ї' => '}', 'Ф' => 'A', 'І' => 'S', 'В' => 'D',
            'А' => 'F', 'П' => 'G', 'Р' => 'H', 'О' => 'J', 'Л' => 'K',
            'Д' => 'L', 'Ж' => ':', 'Є' => '"', 'Я' => 'Z', 'Ч' => 'X',
            'С' => 'C', 'М' => 'V', 'И' => 'B', 'Т' => 'N', 'Ь' => 'M',
            'Б' => '<', 'Ю' => '>', ',' => '?',
        ];
    }

    private function getCategoriesFromProducts($products) {
        $category_ids = [];

        foreach ($products as $product) {
            $product_category_ids = $this->getProductCategories($product['id']);
            $category_ids = array_merge($category_ids, $product_category_ids);
        }

        $category_ids = array_unique($category_ids);

        if (empty($category_ids)) {
            return [];
        }

        $categories = [];

        $category_ids = array_map('intval', array_unique($category_ids));

        $sql = "SELECT c.category_id AS id, cd.name
                FROM " . DB_PREFIX . "category c
                LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id)
                WHERE c.category_id IN (" . implode(',', $category_ids) . ")
                AND c.status = '1'
                AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        $query = $this->db->query($sql);

        foreach ($query->rows as $row) {
            $categories[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'type' => 'category',
                'relevance' => 0,
                'found_in_products' => true
            ];
        }

        return $categories;
    }

    private function getProductCategories($product_id) {
        $sql = "SELECT category_id FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'";
        $query = $this->db->query($sql);

        $category_ids = [];

        foreach ($query->rows as $row) {
            $category_ids[] = $row['category_id'];
        }

        return $category_ids;
    }

    private function searchEntities($type, $keywords, $data) {
        $conditions = [];
        $matched_keywords_expr = [];
        $limit = isset($data['oct_live_search_data']['limit_entities']) ? (int)$data['oct_live_search_data']['limit_entities'] : 12;

        foreach ($keywords as $keyword) {
            $escaped_keyword = $this->db->escape(mb_strtolower($keyword));
        
            if ($type === 'category') {
                $conditions[] = "(LOWER(cd.name) LIKE '%" . $escaped_keyword . "%')";
        
                $matched_keywords_expr[] = "
                    (CASE
                        WHEN LOWER(cd.name) = '" . $escaped_keyword . "' THEN 100
                        WHEN LOWER(cd.name) LIKE '" . $escaped_keyword . " %' THEN 90
                        WHEN LOWER(cd.name) LIKE '% " . $escaped_keyword . " %' THEN 80
                        WHEN LOWER(cd.name) LIKE '% " . $escaped_keyword . "' THEN 70
                        WHEN LOWER(cd.name) LIKE '" . $escaped_keyword . "%' THEN 60
                        WHEN LOWER(cd.name) LIKE '%" . $escaped_keyword . "%' THEN 50
                        ELSE 0
                    END)
                ";
            } elseif ($type === 'manufacturer') {
                $conditions[] = "(LOWER(m.name) LIKE '%" . $escaped_keyword . "%')";
        
                $matched_keywords_expr[] = "
                    (CASE
                        WHEN LOWER(m.name) = '" . $escaped_keyword . "' THEN 100
                        WHEN LOWER(m.name) LIKE '" . $escaped_keyword . " %' THEN 90
                        WHEN LOWER(m.name) LIKE '% " . $escaped_keyword . " %' THEN 80
                        WHEN LOWER(m.name) LIKE '% " . $escaped_keyword . "' THEN 70
                        WHEN LOWER(m.name) LIKE '" . $escaped_keyword . "%' THEN 60
                        WHEN LOWER(m.name) LIKE '%" . $escaped_keyword . "%' THEN 50
                        ELSE 0
                    END)
                ";
            }
        }        

        if (empty($conditions)) {
            return [];
        }

        $sql = '';
        if ($type === 'category') {
            $sql = "
                SELECT
                    cd.category_id AS id,
                    cd.name,
                    " . implode(' + ', $matched_keywords_expr) . " AS matched_keywords,
                    CHAR_LENGTH(cd.name) AS name_length
                FROM " . DB_PREFIX . "category_description cd
                LEFT JOIN " . DB_PREFIX . "category c ON (cd.category_id = c.category_id)
                WHERE (" . implode(" OR ", $conditions) . ")
                AND c.status = '1'
                GROUP BY cd.category_id
                HAVING matched_keywords >= 1
                ORDER BY
                    matched_keywords DESC,
                    name_length ASC,
                    cd.name ASC
                LIMIT " . (int)$limit . "
            ";
        } elseif ($type === 'manufacturer') {
            $sql = "
                SELECT
                    m.manufacturer_id AS id,
                    m.name,
                    " . implode(' + ', $matched_keywords_expr) . " AS matched_keywords
                FROM " . DB_PREFIX . "manufacturer m
                WHERE (" . implode(" OR ", $conditions) . ")
                GROUP BY m.manufacturer_id
                HAVING matched_keywords >= 1
                ORDER BY
                    matched_keywords DESC,
                    m.name ASC
                LIMIT " . (int)$limit . "
            ";
        }

        $query = $this->db->query($sql);
        $results = $query->rows;

        foreach ($results as &$result) {
            $result['type'] = $type;
            $result['relevance'] = $result['matched_keywords'];
            $result['found_in_products'] = false;
        }

        return $results;
    }

    private function searchProducts($keywords, $data) {
        $matched_keywords_expr = [];
        $conditions = [];
        $limit = isset($data['oct_live_search_data']['limit_products']) ? (int)$data['oct_live_search_data']['limit_products'] : 10;
        $keyword_count = count($keywords);
    
        foreach ($keywords as $keyword) {
            $escaped_keyword = $this->db->escape($keyword);
            $escaped_keyword_lower = $this->db->escape(mb_strtolower($keyword));
    
            $matched_keywords_expr[] = "
                (CASE
                    WHEN pd.name LIKE '" . $escaped_keyword . "' THEN 100
                    WHEN pd.name LIKE '" . $escaped_keyword . " %' THEN 90
                    WHEN pd.name LIKE '% " . $escaped_keyword . " %' THEN 80
                    WHEN pd.name LIKE '% " . $escaped_keyword . "' THEN 70
                    WHEN pd.name LIKE '" . $escaped_keyword . "%' THEN 60
                    WHEN pd.name LIKE '%" . $escaped_keyword . "%' THEN 50
                    ELSE 0
                END)
            ";
    
            if (isset($data['oct_live_search_data']['description']) && $data['oct_live_search_data']['description']) {
                $matched_keywords_expr[] = "
                    (CASE
                        WHEN pd.description LIKE '%" . $escaped_keyword . "%' THEN 10
                        ELSE 0
                    END)
                ";
            }

            if (isset($data['oct_live_search_data']['tags']) && $data['oct_live_search_data']['tags']) {
                $matched_keywords_expr[] = "
                    (CASE
                        WHEN pd.tag LIKE '%" . $escaped_keyword . "%' THEN 20
                        ELSE 0
                    END)
                ";
            }
    
            $matched_keywords_expr[] = "
                (CASE
                    WHEN LOWER(p.model) = '" . $escaped_keyword_lower . "' THEN 100
                    WHEN LOWER(p.sku) = '" . $escaped_keyword_lower . "' THEN 100
                    WHEN LOWER(p.model) LIKE '" . $escaped_keyword_lower . "%' THEN 80
                    WHEN LOWER(p.sku) LIKE '" . $escaped_keyword_lower . "%' THEN 80
                    WHEN LOWER(p.model) LIKE '%" . $escaped_keyword_lower . "%' THEN 60
                    WHEN LOWER(p.sku) LIKE '%" . $escaped_keyword_lower . "%' THEN 60
                    ELSE 0
                END)
            ";
    
            $keyword_conditions = [];
            $keyword_conditions[] = "pd.name LIKE '%" . $escaped_keyword . "%'";
    
            if (isset($data['oct_live_search_data']['description']) && $data['oct_live_search_data']['description']) {
                $keyword_conditions[] = "pd.description LIKE '%" . $escaped_keyword . "%'";
            }
            
            if (isset($data['oct_live_search_data']['tags']) && $data['oct_live_search_data']['tags']) {
                $keyword_conditions[] = "pd.tag LIKE '%" . $escaped_keyword . "%'";
            }
    
            $keyword_conditions[] = "LOWER(p.model) LIKE '%" . $escaped_keyword_lower . "%'";
            $keyword_conditions[] = "LOWER(p.sku) LIKE '%" . $escaped_keyword_lower . "%'";
    
            $conditions[] = "(" . implode(" OR ", $keyword_conditions) . ")";
        }
    
        if (empty($conditions)) {
            return [];
        }
    
        $where_conditions = implode(" OR ", $conditions);
        $matched_keywords_expr_total = "(" . implode(' + ', $matched_keywords_expr) . ")";
        $matched_keywords_expr_total .= " + ( (" . implode(' + ', $matched_keywords_expr) . ") / " . $keyword_count . " ) * 10";
    
        $sql = "
            SELECT
                p.product_id AS id,
                pd.name,
                " . $matched_keywords_expr_total . " AS matched_keywords,
                CHAR_LENGTH(pd.name) AS name_length
            FROM " . DB_PREFIX . "product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id)
            WHERE
                p.status = '1'
                AND p.date_available <= NOW()
                AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'
                AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
                AND (" . $where_conditions . ")
            GROUP BY p.product_id
            HAVING matched_keywords > 0
            ORDER BY
                (p.quantity > 0) DESC,
                matched_keywords DESC,
                name_length ASC,
                LOWER(pd.name) ASC
            LIMIT " . (int)$limit . "
        ";
    
        $query = $this->db->query($sql);
        $results = $query->rows;
    
        foreach ($results as &$result) {
            $result['type'] = 'product';
            $result['relevance'] = $result['matched_keywords'];
        }
    
        return $results;
    }    
}