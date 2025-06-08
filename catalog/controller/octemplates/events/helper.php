<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesEventsHelper extends Controller {

    public function octProductReviews() {
        
        $product_id = $this->request->post['product_id']
                ?? $this->request->get['product_id']
                ?? $this->request->get['oct_product_id']
                ?? 0;

        $data['product_id'] = (int)$product_id;
    
        $this->load->language('product/product');
        $this->load->model('catalog/review');
        $this->load->model('octemplates/helper');
    
        $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
    
        $reviews_per_page = $this->config->get('theme_oct_remarket_data_pr_reviews_limit')
            ? (int)$this->config->get('theme_oct_remarket_data_pr_reviews_limit')
            : 20;
    
        $review_total = $this->model_catalog_review->getTotalReviewsByProductId($product_id);
    
        $offset = ($page - 1) * $reviews_per_page;
    
        $results = $this->model_catalog_review->getReviewsByProductId($product_id, $offset, $reviews_per_page);
    
        $data['reviews'] = [];
        foreach ($results as $result) {
            $data['reviews'][] = [
                'author'     => $result['author'],
                'review_id'  => $result['review_id'],
                'text'       => nl2br($result['text']),
                'rating'     => (int)$result['rating'],
                'reply'      => $result['reply'],
                'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added']))
            ];
        }
    
        $data['has_more']  = ($page * $reviews_per_page) < $review_total;
        $data['next_page'] = $page + 1;
    
        $data['ajax'] = false;
        if (!empty($this->request->server['HTTP_X_REQUESTED_WITH']) &&
            strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $data['ajax'] = true;
        }
    
        $output = $this->load->view('product/review', $data);
    
        if ($data['ajax']) {
            $this->response->setOutput($output);
        } else {
            return $output;
        }
    }    

    public function octMassGenerateBoughtTogether() {
        $json = array();
    
        if (!isset($this->request->get['cron_pass'])) {
            $json['error'] = 'No password provided.';
        } else {
            $valid_password = $this->config->get('theme_oct_remarket_data_bought_together_cron');
            if (!$valid_password) {
                $json['error'] = 'No valid password found in config.';
            } else {
                $incoming_pass = $this->request->get['cron_pass'];
                if ($incoming_pass !== $valid_password) {
                    $json['error'] = 'Invalid password.';
                } else {
                    $this->load->model('octemplates/helper');
                    $this->model_octemplates_helper->octMassGenerateBoughtTogether();
                    $json['success'] = 'Bought Together cache has been updated successfully!';
                }
            }
        }
    
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
}