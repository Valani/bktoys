<?php
class ControllerOCTemplatesModuleCsvBookToys extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('octemplates/module/csv_booktoys');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('csv_booktoys', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('octemplates/module/csv_booktoys', 'user_token=' . $this->session->data['user_token'], true));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('octemplates/module/csv_booktoys', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('octemplates/module/csv_booktoys', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        $data['user_token'] = $this->session->data['user_token'];

        $settings = array(
            'csv_booktoys_upd_name',
            'csv_booktoys_upd_desc',
            'csv_booktoys_upd_cat',
            'csv_booktoys_upd_attr',
            'csv_booktoys_upd_status',
            'csv_booktoys_ignore_empty'
        );

        foreach ($settings as $setting) {
            if (isset($this->request->post[$setting])) {
                $data[$setting] = $this->request->post[$setting];
            } else {
                $data[$setting] = $this->config->get($setting);
            }
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('octemplates/module/csv_booktoys', $data));
    }

    public function import() {
        $this->load->language('octemplates/module/csv_booktoys');
        $this->load->model('octemplates/module/csv_booktoys');

        $json = array();

        if (!$this->validate()) {
            $json['error'] = $this->language->get('error_permission');
        } elseif (empty($this->request->files['upload']['name']) || !is_file($this->request->files['upload']['tmp_name'])) {
            $json['error'] = $this->language->get('error_upload');
        } elseif (strtolower(pathinfo($this->request->files['upload']['name'], PATHINFO_EXTENSION)) != 'csv') {
            $json['error'] = $this->language->get('error_filetype');
        } else {
            $file = $this->request->files['upload']['tmp_name'];
            $handle = fopen($file, "r");

            if ($handle) {
                $total_rows = 0;
                $success_count = 0;
                $errors = array();

                $upd_name = isset($this->request->post['csv_booktoys_upd_name']) ? $this->request->post['csv_booktoys_upd_name'] : $this->config->get('csv_booktoys_upd_name');
                $upd_desc = isset($this->request->post['csv_booktoys_upd_desc']) ? $this->request->post['csv_booktoys_upd_desc'] : $this->config->get('csv_booktoys_upd_desc');
                $upd_cat  = isset($this->request->post['csv_booktoys_upd_cat']) ? $this->request->post['csv_booktoys_upd_cat'] : $this->config->get('csv_booktoys_upd_cat');
                $upd_attr = isset($this->request->post['csv_booktoys_upd_attr']) ? $this->request->post['csv_booktoys_upd_attr'] : $this->config->get('csv_booktoys_upd_attr');
                $upd_status = isset($this->request->post['csv_booktoys_upd_status']) ? $this->request->post['csv_booktoys_upd_status'] : $this->config->get('csv_booktoys_upd_status');
                $ignore_empty = isset($this->request->post['csv_booktoys_ignore_empty']) ? $this->request->post['csv_booktoys_ignore_empty'] : $this->config->get('csv_booktoys_ignore_empty');

                $details = array();

                $language_id = $this->config->get('config_language_id');

                $headers = fgetcsv($handle, 0, ";");
                if ($headers) {
                    // Remove UTF-8 BOM if it exists
                    $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
                }
                
                if ($headers && strtolower(trim($headers[0])) == 'sku') {
                    $line_number = 1;

                    while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
                        $line_number++;
                        $total_rows++;
                        
                        $sku = trim($data[0]);
                        if (empty($sku)) continue;

                        $product_id = $this->model_octemplates_module_csv_booktoys->getProductIdBySku($sku);

                        if (!$product_id) {
                            $errors[] = "Line $line_number (SKU: $sku): Product not found in database. Skipped.";
                            continue;
                        }

                        $success = true;
                        $desc_data = array();
                        $updated_fields = array();

                        foreach ($headers as $index => $header_name) {
                            if (!isset($data[$index])) continue;
                            $value = trim($data[$index]);
                            
                            if ($ignore_empty && $value === '') {
                                continue;
                            }
                            
                            $header_name_lower = strtolower(trim($header_name));

                            if ($header_name_lower == 'name' && $upd_name) {
                                $desc_data['name'] = $value;
                                $desc_data['meta_h1'] = $value;
                                if (!in_array('Name', $updated_fields)) $updated_fields[] = 'Name';
                            }
                            elseif ($header_name_lower == 'description' && $upd_desc) {
                                $desc_data['description'] = $value;
                                if (!in_array('Description', $updated_fields)) $updated_fields[] = 'Description';
                            }
                            elseif ($header_name_lower == 'status' && $upd_status) {
                                $status_val = (strtolower($value) == 'on' || $value == '1') ? 1 : 0;
                                $this->model_octemplates_module_csv_booktoys->updateProductStatus($product_id, $status_val);
                                $updated_fields[] = 'Status (' . ($status_val ? 'on' : 'off') . ')';
                            }
                            elseif ($header_name_lower == 'category' && $upd_cat) {
                                $main_cat_value = '';
                                $main_index = array_search('main_category', array_map('strtolower', array_map('trim', $headers)));
                                if ($main_index !== false && isset($data[$main_index])) {
                                    $main_cat_value = trim($data[$main_index]);
                                }

                                if (!isset($categories_deleted)) {
                                    $this->model_octemplates_module_csv_booktoys->deleteProductCategories($product_id);
                                    $categories_deleted = true;
                                }

                                $cats = explode('/', $value);
                                foreach ($cats as $cat) {
                                    $cat = trim($cat);
                                    if (empty($cat)) continue;
                                    
                                    $category_id = $this->model_octemplates_module_csv_booktoys->getCategoryIdByName($cat);
                                    if ($category_id) {
                                        $is_main = ($cat === $main_cat_value) ? 1 : 0;
                                        $this->model_octemplates_module_csv_booktoys->linkProductToCategory($product_id, $category_id, $is_main);
                                        if (!in_array('Categories', $updated_fields)) $updated_fields[] = 'Categories';
                                    } else {
                                        $errors[] = "Line $line_number (SKU: $sku): Category '$cat' not found in the database. Product is not linked to it.";
                                    }
                                }
                            }
                            elseif (strpos(strtoupper(trim($header_name)), 'ATR-') === 0 && $upd_attr) {
                                $attr_name = substr(trim($header_name), 4);
                                $attribute_id = $this->model_octemplates_module_csv_booktoys->getAttributeIdByName($attr_name);
                                
                                if ($attribute_id) {
                                    $this->model_octemplates_module_csv_booktoys->saveProductAttribute($product_id, $attribute_id, $language_id, $value);
                                    if (!in_array('Attributes', $updated_fields)) $updated_fields[] = 'Attributes';
                                } else {
                                    $errors[] = "Line $line_number (SKU: $sku): Attribute '$attr_name' not found in the system. Value was not added.";
                                }
                            }
                        }

                        if ($upd_name || $upd_desc) {
                            if (!empty($desc_data)) {
                                $this->model_octemplates_module_csv_booktoys->updateProductDescription($product_id, $language_id, $desc_data);
                            }
                        }
                        
                        if (!empty($updated_fields)) {
                            $details[] = array(
                                'sku' => $sku,
                                'updated' => implode(', ', $updated_fields)
                            );
                        }

                        unset($categories_deleted);
                        $success_count++;
                    }
                } else {
                    $json['error'] = 'Invalid CSV format. The first column must be "sku".';
                }

                fclose($handle);

                if (!isset($json['error'])) {
                    $json['success'] = "Total rows processed in file: " . $total_rows . "\n";
                    $json['success'] .= "Successfully updated existing products: $success_count\n";
                    if (!empty($errors)) {
                        $json['errors'] = implode("\n", $errors);
                    }
                    if (!empty($details)) {
                        $json['details'] = $details;
                    }
                }
            } else {
                $json['error'] = 'Failed to open the uploaded file.';
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function install() {
        $this->load->model('user/user_group');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'octemplates/module/csv_booktoys');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'octemplates/module/csv_booktoys');

        $this->load->model('setting/setting');
        $settings = array(
            'csv_booktoys_upd_name' => '1',
            'csv_booktoys_upd_desc' => '1',
            'csv_booktoys_upd_cat'  => '1',
            'csv_booktoys_upd_attr' => '1',
            'csv_booktoys_upd_status' => '1',
            'csv_booktoys_ignore_empty' => '1',
        );
        $this->model_setting_setting->editSetting('csv_booktoys', $settings);
    }

    public function uninstall() {
        $this->load->model('user/user_group');
        $this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'octemplates/module/csv_booktoys');
        $this->model_user_user_group->removePermission($this->user->getGroupId(), 'modify', 'octemplates/module/csv_booktoys');

        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('csv_booktoys');
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'octemplates/module/csv_booktoys')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}
