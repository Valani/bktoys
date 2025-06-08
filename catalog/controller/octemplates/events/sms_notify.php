<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesEventsSmsNotify extends Controller {

    public function order(&$route, &$data, &$output) {
    
        $oct_sms_settings = $this->config->get('oct_sms_settings');
        
        if (!$oct_sms_settings) {
            return;
        }
    
        $oct_order_info = $data[0] ?? null;

        if (!$oct_order_info) {
            return;
        }

        $oct_order_info['order_id'] = $output;
    
        $this->load->language('octemplates/oct_remarket');
    
        $this->sendSmsNotification(
            $oct_sms_settings, 
            'oct_order_admin', 
            $oct_sms_settings['admin_phone'], 
            $oct_order_info, 
            'admin_order_sms_template'
        );
    
        $this->sendSmsNotification(
            $oct_sms_settings, 
            'oct_order_customer', 
            $oct_order_info['telephone'], 
            $oct_order_info, 
            'customer_order_sms_template'
        );
    }
    
    private function sendSmsNotification($oct_sms_settings, $template_code, $phone, $oct_order_info, $default_template_key) {
        if (!isset($oct_sms_settings['templates'][$template_code]['status']) || !$oct_sms_settings['templates'][$template_code]['status'] || empty($phone)) {
            return;
        }

        $language_id = $oct_order_info['language_id'];
        $sms_template = $this->getSmsTemplate($oct_sms_settings, $template_code, $language_id, $default_template_key);
    
        if (!empty($sms_template)) {
            $formatted_total = $this->currency->format($oct_order_info['total'], $oct_order_info['currency_code'], $oct_order_info['currency_value']);
    
            $replace = array(
                '[customer_name]' => $oct_order_info['firstname'],
                '[customer_lastname]' => $oct_order_info['lastname'],
                '[email]' => $oct_order_info['email'],
                '[telephone]' => $oct_order_info['telephone'],
                '[comment]' => $oct_order_info['comment'],
                '[shipping_method]' => $oct_order_info['shipping_method'],
                '[payment_method]' => $oct_order_info['payment_method'],
                '[order_id]' => $oct_order_info['order_id'],
                '[order_total]' => $formatted_total,
                '[store]' => $this->config->get('config_name')
            );
    
            $sms_message = str_replace(array_keys($replace), array_values($replace), $sms_template);
    
            $this->load->model('octemplates/module/oct_sms_notify');
            $this->model_octemplates_module_oct_sms_notify->sendNotification(array(
                'phone' => $phone,
                'message' => $sms_message,
                'template_code' => $template_code,
                'access_token' => $oct_sms_settings['oct_sms_token']
            ));  
        }
    }

    private function getSmsTemplate($oct_sms_settings, $template_code, $language_id, $default_template_key) {
        if (isset($oct_sms_settings['templates'][$template_code]['message'][$language_id]) && 
            !empty($oct_sms_settings['templates'][$template_code]['message'][$language_id]) && 
            isset($oct_sms_settings['templates'][$template_code]['edit_localization'])) {
                
            return $oct_sms_settings['templates'][$template_code]['message'][$language_id];
        } else {
            return $this->language->get($default_template_key);
        }
    }
}