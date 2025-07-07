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

    public function index() {
        // Ініціалізація логування
        $this->log = new Log('import_1c.log');

        try {
            // 1. Перевірка наявності та доступності XML файлу
            if (!file_exists(XML_FILE_PATH) || !is_readable(XML_FILE_PATH)) {
                throw new Exception('XML file not found or is not readable at path: ' . XML_FILE_PATH);
            }

            // 2. Читання та парсинг XML файлу
            $xmlContent = file_get_contents(XML_FILE_PATH);
            
            // Помилка парсингу XML може виникнути, якщо файл пошкоджений або має неправильний формат
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

            $this->load->model('catalog/product');

            // 3. Обробка кожного товару
            foreach ($xml->product as $product_xml) {
                $this->report['processed']++;
                
                // Перекодування назви одразу
                $name_utf8 = iconv('windows-1251', 'UTF-8', (string)$product_xml->name);
                $sanitized_name = $this->sanitizeProductName($name_utf8);
                $mpn = (string)$product_xml->mpn;

                // Перевірка статусу
                if ((int)$product_xml->status !== 0) {
                    $this->report['skipped']++;
                    continue;
                }

                $product_data = [
                    'mpn'          => $mpn,
                    'model'        => $mpn,
                    'sku'          => $mpn,
                    'price'        => (float)$product_xml->price5_m1,
                    'quantity'     => max((int)$product_xml->quantity_m1, (int)$product_xml->quantity_m2),
                    'status'       => 1, // Увімкнено
                    'manufacturer_id' => 0,
                    'date_available'  => date('Y-m-d'),
                    'product_description' => [
                        $this->config->get('config_language_id') => [
                            'name'             => $sanitized_name,
                            'meta_h1'          => $sanitized_name,
                            'meta_title'       => $sanitized_name,
                            'description'      => '', // Або $sanitized_name
                            'meta_description' => '',
                            'meta_keyword'     => '',
                            'tag'              => ''
                        ]
                    ],
                    'product_category' => [507], // Категорія за замовчуванням
                    'main_category_id' => 507,
                ];
                
                // Визначення статусу залишку
                $product_data['stock_status_id'] = $product_data['quantity'] > 0 ? $this->config->get('config_stock_status_id') : $this->config->get('config_stock_status_id_out_of_stock');


                // Ідентифікація товару за MPN
                $existing_product = $this->getProductByMpn($mpn);

                if ($existing_product) {
                    // 5. Оновлення існуючого товару
                    $this->updateProduct($existing_product['product_id'], $product_data, $product_xml);
                } else {
                    // 4. Створення нового товару
                    $this->createProduct($product_data, $product_xml);
                }
            }

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
        // Додаємо дані, специфічні для створення
        $data['date_added'] = new DateTime();
        $data['date_modified'] = new DateTime();
        
        // SEO URL
        $data['product_seo_url'] = [
            $this->config->get('config_store_id') => [
                $this->config->get('config_language_id') => $this->generateSlug($data['product_description'][$this->config->get('config_language_id')]['name'])
            ]
        ];

        // Акційна ціна
        $special_price = (float)$product_xml->price6_m1;
        if ($special_price > 0) {
            $data['product_special'][] = [
                'customer_group_id' => $this->config->get('config_customer_group_id'),
                'priority'          => 1,
                'price'             => $special_price,
                'date_start'        => '',
                'date_end'          => ''
            ];
        }

        // Виклик стандартного методу OpenCart для додавання товару
        $this->model_catalog_product->addProduct($data);
        $this->report['created']++;
    }

    private function updateProduct($product_id, $data, $product_xml) {
        $data['date_modified'] = new DateTime();

        // Оновлення акційної ціни
        // 1. Спочатку видаляємо всі старі акційні ціни для цього товару
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$product_id . "'");
        
        // 2. Якщо нова акційна ціна є, додаємо її
        $special_price = (float)$product_xml->price6_m1;
        if ($special_price > 0) {
            $data['product_special'][] = [
                'customer_group_id' => $this->config->get('config_customer_group_id'),
                'priority'          => 1,
                'price'             => $special_price,
                'date_start'        => '',
                'date_end'          => ''
            ];
        }

        // SEO URL не оновлюємо, щоб зберегти індексацію
        
        // Виклик стандартного методу OpenCart для редагування товару
        $this->model_catalog_product->editProduct($product_id, $data);
        $this->report['updated']++;
    }
    
    private function getProductByMpn($mpn) {
        $query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE mpn = '" . $this->db->escape($mpn) . "'");
        return $query->row;
    }

    /**
     * 7. Функція очищення назви товару
     * @param string $name Назва в UTF-8
     * @return string Очищена назва
     */
    private function sanitizeProductName($name) {
        // Заміна кількох пробілів одним
        $name = preg_replace('/\s+/', ' ', $name);

        // Видалення заборонених спецсимволів (залишаємо букви, цифри, пробіл, дефіс, крапку, кому, лапки)
        $name = preg_replace('/[^\p{L}\p{N}\s\-\.\,\"\']/u', '', $name);

        // Заміна лапок на HTML-сутності
        $name = str_replace(
            ['"', "'", '«', '»'],
            ['&quot;', '&#39;', '&laquo;', '&raquo;'],
            $name
        );

        // Обрізання пробілів на початку та в кінці
        return trim($name);
    }
    
    /**
     * 6. Створення SEO URL (Slug)
     * @param string $text Назва товару в UTF-8
     * @return string SEO-оптимізований URL
     */
    private function generateSlug($text) {
        // Транслітерація
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

        // Переведення в нижній регістр
        $text = strtolower($text);

        // Заміна всіх символів, окрім літер та цифр, на дефіс
        $text = preg_replace('/[^\p{L}\p{N}]+/u', '-', $text);

        // Заміна кількох дефісів поспіль одним (це робить попередній preg_replace)
        
        // Видалення дефісів на початку та в кінці
        $text = trim($text, '-');

        return $text;
    }
}