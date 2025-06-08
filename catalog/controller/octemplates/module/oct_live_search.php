<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesModuleOctLiveSearch extends Controller {
    public function index() {
        if ($this->config->get('theme_oct_remarket_live_search_status') && 
            isset($this->request->server['HTTP_X_REQUESTED_WITH']) && 
            !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && 
            strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            
            $this->load->model('tool/image');
            $this->load->model('octemplates/module/oct_live_search');
            $this->load->model('catalog/product');
            $this->load->model('catalog/category');
            $this->load->model('catalog/manufacturer');
            $this->load->language('product/product');
            $this->load->language('octemplates/module/oct_live_search');

            $data['products'] = [];
            $data['categories'] = [];
            $data['manufacturers'] = [];

            if (isset($this->request->post['key']) && !empty(trim($this->request->post['key']))) {
                $oct_live_search_data = $this->config->get('theme_oct_remarket_live_search_data');

                $data['oct_live_search_data'] = $oct_live_search_data;
                $data['search'] = trim($this->request->post['key']);
                $data['category'] = isset($oct_live_search_data['category']) && $oct_live_search_data['category'] ? true : false;
                $data['category_image'] = isset($oct_live_search_data['category_images']) && $oct_live_search_data['category_images'] ? true : false;
                $data['manufacturer'] = isset($oct_live_search_data['manufacturer']) && $oct_live_search_data['manufacturer'] ? true : false;
                $data['manufacturer_image'] = isset($oct_live_search_data['manufacturer_images']) && $oct_live_search_data['manufacturer_images'] ? true : false;
                $data['search_fallback'] = isset($oct_live_search_data['search_fallback']) && $oct_live_search_data['search_fallback'] ? true : false;

                $search_results = $this->model_octemplates_module_oct_live_search->doSearch($data);

                if (isset($search_results['original_query'])) {
                    $data['original_query'] = $search_results['original_query'];
                    $data['corrected_query'] = $search_results['corrected_query'];
                    $data['query_corrected'] = true;

                    $data['text_corrected_search'] = sprintf(
                        $this->language->get('text_corrected_search'),
                        htmlspecialchars($data['corrected_query'], ENT_QUOTES, 'UTF-8')
                    );

                    $search_results = $search_results['results'];
                } else {
                    $data['query_corrected'] = false;
                    $search_results = $search_results['results'];
                }

                $data['price_setting'] = isset($oct_live_search_data['price']) && $oct_live_search_data['price'] ? true : false;
                $data['model_setting'] = isset($oct_live_search_data['model']) && $oct_live_search_data['model'] ? true : false;
                $data['sku_setting'] = isset($oct_live_search_data['sku']) && $oct_live_search_data['sku'] ? true : false;

                foreach ($search_results as $result) {
                    switch ($result['type']) {
                        case 'product':
                            $product_info = $this->model_catalog_product->getProduct($result['id']);

                            if ($product_info) {
                                if ($product_info['image']) {
                                    $image = $this->model_tool_image->resize($product_info['image'], $this->config->get('theme_oct_remarket_image_product_width'), $this->config->get('theme_oct_remarket_image_product_height'));
                                } else {
                                    $image = $this->model_tool_image->resize('placeholder.png', 80, 80);
                                }

                                if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || 
                                    !$this->config->get('config_customer_price')) {
                                    $price = $this->currency->format(
                                        $this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), 
                                        $this->session->data['currency']
                                    );
                                } else {
                                    $price = false;
                                }

                                if ((float)$product_info['special']) {
                                    $special = $this->currency->format(
                                        $this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), 
                                        $this->session->data['currency']
                                    );
                                } else {
                                    $special = false;
                                }

                                if ($product_info['quantity'] <= 0) {
                                    $stock = $product_info['stock_status'];
                                } elseif ($this->config->get('config_stock_display')) {
                                    $stock = $product_info['quantity'];
                                } else {
                                    $stock = $this->language->get('text_instock');
                                }

                                $data['products'][] = [
                                    'name'     => $product_info['name'],
                                    'model'    => $product_info['model'],
                                    'sku'      => $product_info['sku'],
                                    'href'     => $this->url->link('product/product', 'product_id=' . $product_info['product_id'], true),
                                    'image'    => $image,
                                    'width'    => 80,
                                    'height'   => 80,
                                    'price'    => $price,
                                    'special'  => $special,
                                    'stock'    => $stock,
                                    'quantity' => $product_info['quantity']
                                ];
                            }
                            break;

                        case 'category':
                            $category_info = $this->model_catalog_category->getCategory($result['id']);

                            if ($category_info) {
                                if (isset($category_info['image']) && $category_info['image']) {
                                    $image = $this->model_tool_image->resize($category_info['image'], $this->config->get('theme_oct_remarket_image_sub_category_width'), $this->config->get('theme_oct_remarket_image_sub_category_height'));
                                } else {
                                    $image = $this->model_tool_image->resize('placeholder.png', 80, 80);
                                }
                        
                                $data['categories'][] = [
                                    'name'               => $category_info['name'],
                                    'href'               => $this->url->link('product/category', 'path=' . $category_info['category_id'], true),
                                    'image'              => $image,
                                    'width'              => 60,
                                    'height'             => 60,
                                    'found_in_products'  => isset($result['found_in_products']) ? $result['found_in_products'] : false
                                ];
                            }
                            break;

                        case 'manufacturer':
                            $manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($result['id']);

                            if ($manufacturer_info) {
                                if (isset($manufacturer_info['image']) && $manufacturer_info['image']) {
                                    $image = $this->model_tool_image->resize($manufacturer_info['image'], $this->config->get('theme_oct_remarket_image_manufacturer_width'), $this->config->get('theme_oct_remarket_image_manufacturer_height'));
                                } else {
                                    $image = $this->model_tool_image->resize('placeholder.png', 80, 80);
                                }

                                $data['manufacturers'][] = [
                                    'name'   => $manufacturer_info['name'],
                                    'href'   => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $manufacturer_info['manufacturer_id'], true),
                                    'image'  => $image,
                                    'width'  => 60,
                                    'height' => 60
                                ];
                            }
                            break;
                    }
                }

                $direct_categories = [];
                $product_categories = [];

                if (!empty($data['categories'])) {
                    foreach ($data['categories'] as $category) {
                        if (isset($category['found_in_products']) && $category['found_in_products']) {
                            $product_categories[] = $category;
                        } else {
                            $direct_categories[] = $category;
                        }
                    }
                }

                $data['direct_categories'] = $direct_categories;
                $data['product_categories'] = $product_categories;

                unset($data['categories']);

                $this->response->setOutput($this->load->view('octemplates/module/oct_live_search', $data));
            } else {
                $this->response->setOutput('');
            }
        } else {
            $this->response->redirect($this->url->link('error/not_found', '', true));
        }
    }
}
