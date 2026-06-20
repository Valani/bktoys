<?php
// Шлях до XML файлу. Переконайтеся, що він правильний.
define('XML_FILE_PATH', '/home/cr548725/books-toys.com.ua/transfer/products.xml');

class ControllerApiExport1c extends Controller {
    private $log;
    private $report = [
        'status'    => 'success',
        'processed' => 0,
        'created'   => 0,
        'updated'   => 0,
        'skipped'   => 0,
        'errors'    => 0,
        'error_messages' => []
    ];
    
    // Кеш для конфігурацій, щоб не смикати їх в циклі
    private $cached_configs = [];

    public function index() {
        // ОПТИМІЗАЦІЯ: Знімаємо обмеження часу та виділяємо більше пам'яті для великих XML
        ini_set('memory_limit', '512M');
        set_time_limit(0);

        // Ініціалізація логування
        $this->log = new Log('import_1c.log');

        try {
            // 1. Перевірка наявності та доступності XML файлу
            if (!file_exists(XML_FILE_PATH) || !is_readable(XML_FILE_PATH)) {
                throw new Exception('XML file not found or is not readable at path: ' . XML_FILE_PATH);
            }

            // 2. Читання та парсинг XML файлу
            $xmlContent = file_get_contents(XML_FILE_PATH);
            
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xmlContent);
            if ($xml === false) {
                 $errors = libxml_get_errors();
                 $error_message = "Failed to parse XML. Errors: \n";
                 foreach ($errors as $error) {
                     $error_message .= trim($error->message) . " on line " . $error->line . "\n";
                 }
                 libxml_clear_errors();
                 throw new Exception($error_message);
            }
            
            // Звільняємо пам'ять від сирого рядка XML, він більше не потрібен
            unset($xmlContent);

            $this->load->model('catalog/product');

            // ОПТИМІЗАЦІЯ: Кешуємо часто використовувані налаштування
            $this->cached_configs = [
                'language_id'      => (int)$this->config->get('config_language_id'),
                'store_id'         => (int)$this->config->get('config_store_id'),
                'stock_status_id'  => (int)$this->config->get('config_stock_status_id'),
                'out_of_stock_id'  => (int)$this->config->get('config_stock_status_id_out_of_stock'),
                'customer_group_id'=> (int)$this->config->get('config_customer_group_id')
            ];

            // ОПТИМІЗАЦІЯ: Завантажуємо ВСІ існуючі MPN в пам'ять ОДНИМ запитом
            // Це усуває проблему "N+1 запитів" до БД
            $existing_products = $this->getAllMpns();
            
            $date_available = date('Y-m-d');

            // 3. Обробка кожного товару
            foreach ($xml->product as $product_xml) {
                $this->report['processed']++;
                
                $mpn = (string)$product_xml->mpn;

                // Перевірка статусу (відсіюємо неактивні одразу)
                if ((int)$product_xml->status !== 0) {
                    $this->report['skipped']++;
                    continue;
                }

                $quantity = max((int)$product_xml->quantity_m1, (int)$product_xml->quantity_m2);
                $price = (float)$product_xml->price5_m1;
                $stock_status_id = $quantity > 0 ? $this->cached_configs['stock_status_id'] : $this->cached_configs['out_of_stock_id'];

                // Якщо товар вже існує (перевірка через масив у пам'яті, миттєво)
                if (isset($existing_products[$mpn])) {
                    
                    $product_id = $existing_products[$mpn];
                    
                    // 5. Оновлення існуючого товару (тільки ціна, кількість, статус)
                    $update_data = [
                        'price'           => $price,
                        'quantity'        => $quantity,
                        'stock_status_id' => $stock_status_id
                    ];
                    
                    $this->updateProductFast($product_id, $update_data, $product_xml);
                    
                } else {
                    // 4. Створення нового товару (додаткові обчислення тільки для нових)
                    
                    $name_utf8 = iconv('windows-1251', 'UTF-8', (string)$product_xml->name);
                    $sanitized_name = $this->sanitizeProductName($name_utf8);
                    
                    $product_data = [
                        'mpn'          => $mpn,
                        'model'        => $mpn,
                        'sku'          => $mpn,
                        'price'        => $price,
                        'quantity'     => $quantity,
                        'status'       => 1,
                        'manufacturer_id' => 0,
                        'date_available'  => $date_available,
                        'stock_status_id' => $stock_status_id,
                        'product_description' => [
                            $this->cached_configs['language_id'] => [
                                'name'             => $sanitized_name,
                                'meta_h1'          => $sanitized_name,
                                'meta_title'       => $sanitized_name,
                                'description'      => '',
                                'meta_description' => '',
                                'meta_keyword'     => '',
                                'tag'              => ''
                            ]
                        ],
                        'product_category' => [507], // Категорія за замовчуванням
                        'main_category_id' => 507,
                    ];

                    $this->createProduct($product_data, $product_xml);
                }
            }

            // Очищаємо кеш товарів після успішного імпорту
            $this->cache->delete('product');

        } catch (Exception $e) {
            $this->report['status'] = 'error';
            $this->report['errors']++;
            $this->report['error_messages'][] = $e->getMessage();
            $this->log->write('Import Error: ' . $e->getMessage());
        }

        // 8. Вивід звіту
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            "status" => $this->report['status'],
            "processed" => $this->report['processed'],
            "created" => $this->report['created'],
            "updated" => $this->report['updated'],
            "skipped" => $this->report['skipped'],
            "errors" => $this->report['errors'],
        ]));
    }

    private function createProduct($data, $product_xml) {
        $data['date_added'] = new DateTime();
        $data['date_modified'] = new DateTime();
        
        $data['product_seo_url'] = [
            $this->cached_configs['store_id'] => [
                $this->cached_configs['language_id'] => $this->generateSlug($data['product_description'][$this->cached_configs['language_id']]['name'])
            ]
        ];

        $special_price = (float)$product_xml->price6_m1;
        if ($special_price > 0) {
            $data['product_special'][] = [
                'customer_group_id' => $this->cached_configs['customer_group_id'],
                'priority'          => 1,
                'price'             => $special_price,
                'date_start'        => '',
                'date_end'          => ''
            ];
        }

        $this->model_catalog_product->addProduct($data);
        $this->report['created']++;
    }

    private function updateProductFast($product_id, $data, $product_xml) {
        $product_id = (int)$product_id;
        
        // ОПТИМІЗАЦІЯ: Використовуємо транзакцію бази даних для групи запитів. 
        // Це блокує таблицю на долі секунди, але записує всі зміни одним пакетом, 
        // що в рази швидше, ніж виконувати 3 окремі запити на диск.
        $this->db->query("START TRANSACTION");

        try {
            // Оновлюємо базові дані
            $this->db->query("UPDATE " . DB_PREFIX . "product SET 
                price = '" . (float)$data['price'] . "', 
                quantity = '" . (int)$data['quantity'] . "', 
                stock_status_id = '" . (int)$data['stock_status_id'] . "', 
                date_modified = NOW() 
                WHERE product_id = '" . $product_id . "'");

            // Видаляємо старі акції
            $this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '" . $product_id . "'");
            
            // Якщо є нова акція - додаємо
            $special_price = (float)$product_xml->price6_m1;
            if ($special_price > 0) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET 
                    product_id = '" . $product_id . "', 
                    customer_group_id = '" . $this->cached_configs['customer_group_id'] . "', 
                    priority = '1', 
                    price = '" . $special_price . "'");
            }

            // Завершуємо та підтверджуємо транзакцію
            $this->db->query("COMMIT");
            $this->report['updated']++;

        } catch (Exception $e) {
            // У разі помилки відкочуємо зміни для цього конкретного товару
            $this->db->query("ROLLBACK");
            throw clone $e; // Перекидаємо помилку вище для логування
        }
    }
    
    /**
     * ОПТИМІЗАЦІЯ: Завантажуємо ВСІ mpn з бази даних в асоціативний масив
     * Формат масиву: ['mpn_товару' => 'product_id']
     */
    private function getAllMpns() {
        $map = [];
        $query = $this->db->query("SELECT product_id, mpn FROM " . DB_PREFIX . "product WHERE mpn != ''");
        
        foreach ($query->rows as $row) {
            $map[$row['mpn']] = (int)$row['product_id'];
        }
        
        return $map;
    }

    private function sanitizeProductName($name) {
        $name = preg_replace('/\s+/', ' ', $name);
        $name = preg_replace('/[^\p{L}\p{N}\s\-\.\,\"\']/u', '', $name);
        $name = str_replace(
            ['"', "'", '«', '»'],
            ['&quot;', '&#39;', '&laquo;', '&raquo;'],
            $name
        );
        return trim($name);
    }
    
    private function generateSlug($text) {
        $translit = [
            'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','є'=>'ie','ж'=>'zh',
            'з'=>'z','и'=>'y','і'=>'i','ї'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m',
            'н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f',
            'х'=>'h','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'shch','ь'=>'','ю'=>'iu','я'=>'ia',
            'А'=>'A','Б'=>'B','В'=>'V','Г'=>'G','Д'=>'D','Е'=>'E','Є'=>'IE','Ж'=>'ZH',
            'З'=>'Z','И'=>'Y','І'=>'I','Ї'=>'I','Й'=>'Y','К'=>'K','Л'=>'L','М'=>'M',
            'Н'=>'N','О'=>'O','П'=>'P','Р'=>'R','С'=>'S','Т'=>'T','У'=>'U','Ф'=>'F',
            'Х'=>'H','Ц'=>'TS','Ч'=>'CH','Ш'=>'SH','Щ'=>'SHCH','Ь'=>'','Ю'=>'IU','Я'=>'IA'
        ];
        $text = strtr($text, $translit);
        $text = strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}]+/u', '-', $text);
        $text = trim($text, '-');
        return $text;
    }
}