<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOctemplatesStickersCronStickers extends Controller {

    public function index() {

        $cronPassword = $this->config->get('oct_stickers_cron_password');

        if (!$cronPassword) {
            exit('No Cron Password set in config');
        }

        if (!isset($this->request->get['key']) || $this->request->get['key'] !== $cronPassword) {
            exit('Access denied');
        }

        $this->load->model('octemplates/stickers/oct_stickers');

        if (!empty($this->request->get['pr_id'])) {
            $product_id = (int)$this->request->get['pr_id'];
            $this->model_octemplates_stickers_oct_stickers->generateStickersForSingleProductJson($product_id);

            echo 'Stickers generation done for product_id=' . $product_id;
        } else {
            $this->model_octemplates_stickers_oct_stickers->generateStickersForAllProductsJson();
            echo 'Stickers generation done for ALL products!';
        }
    }
}