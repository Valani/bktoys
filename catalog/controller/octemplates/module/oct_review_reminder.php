<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOctemplatesModuleOctReviewReminder extends Controller {

    public function cron() {

        if (!isset($this->request->get['cron_secret']) || $this->request->get['cron_secret'] !== $this->config->get('oct_review_reminder_cron_password') || !$this->config->get('oct_review_reminder_status')) {
            return;
        }

        $this->load->language('octemplates/module/oct_review_reminder');
        $this->load->model('octemplates/module/oct_review_reminder');
        $this->load->model('catalog/product');
        $this->load->model('checkout/order');

        $reminders = $this->model_octemplates_module_oct_review_reminder->getRemindersToSend();

        if (empty($reminders)) {
            echo 'No reminders to send.' . PHP_EOL;
            return;
        }

        $sent_emails_count = 0;
        $sent_sms_count = 0;

        foreach ($reminders as $reminder) {

            $order_info = $this->model_checkout_order->getOrder($reminder['order_id']);

            $phone = $order_info['telephone'];

            if ((empty($reminder['email']) || $reminder['email'] == $this->config->get('config_email')) && empty($phone)) {
                $this->model_octemplates_module_oct_review_reminder->delReminderRecord($reminder['order_id']);
                continue;
            } 

            $order_products = $this->model_checkout_order->getOrderProducts($reminder['order_id']);
            $products_info = '';
            $products_sms_info = '';

            foreach ($order_products as $order_product) {
                $product = $this->model_catalog_product->getProduct($order_product['product_id']);
                $product_url = $this->url->link('product/product', 'product_id=' . $product['product_id'], true);
                $review_link = $product_url;
                $products_info .= '<p><a href="' . $product_url . '">' . $product['name'] . '</a></p>';
                $products_sms_info .= $product_url . ' - ' . $product['name'] . '[br]';
            }

            $language_id = $reminder['language_id'];

            if (!empty($reminder['email']) && $reminder['email'] != $this->config->get('config_email')) {
                if ($this->config->get('oct_review_reminder_etemplates_status')) {
                    $email_templates = $this->config->get('oct_review_reminder_email_template');
                    $subject_template = isset($email_templates[$language_id]['subject']) && !empty($email_templates[$language_id]['subject']) ? $email_templates[$language_id]['subject'] : $this->language->get('default_email_subject');
                    $message_template = isset($email_templates[$language_id]['body']) && !empty($email_templates[$language_id]['body']) ? html_entity_decode($email_templates[$language_id]['body'], ENT_QUOTES, 'UTF-8') : $this->language->get('default_email_template');
                } else {
                    $subject_template = $this->language->get('default_email_subject');
                    $message_template = $this->language->get('default_email_template');
                    if (count($order_products) > 1) {
                        $message_template = $this->language->get('default_email_template_multiple');
                    }
                }
                
                $mail = new Mail($this->config->get('config_mail_engine'));
                $mail->protocol = $this->config->get('config_mail_protocol');
                $mail->parameter = $this->config->get('config_mail_parameter');
                $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
                $mail->smtp_username = $this->config->get('config_mail_smtp_username');
                $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
                $mail->smtp_port = $this->config->get('config_mail_smtp_port');
                $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
                
                $mail->setTo($reminder['email']);
                $mail->setFrom($this->config->get('config_email'));
                $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
                $mail->setSubject($subject_template);

                $message = str_replace(
                    ['[customer_name]', '[order_id]', '[products]', '[review_link]', '[store]'],
                    [$reminder['customer_name'], $reminder['order_id'], $products_info, $review_link, $this->config->get('config_name')],
                    $message_template
                );

                $mail->setHtml($message);

                $mail->send();

                $sent_emails_count++;
            }

            if (!empty($phone)) {
                $oct_sms_settings = $this->config->get('oct_sms_settings');
                $template_code = 'oct_review_reminder';

                if (isset($oct_sms_settings['templates'][$template_code]['status']) && $oct_sms_settings['templates'][$template_code]['status']) {
                    if (isset($oct_sms_settings['templates'][$template_code]['message'][$language_id]) && !empty($oct_sms_settings['templates'][$template_code]['message'][$language_id]) && isset($oct_sms_settings['templates'][$template_code]['edit_localization'])) {
                        $sms_template = $oct_sms_settings['templates'][$template_code]['message'][$language_id];
                    } else {
                        $sms_template = $this->language->get('default_sms_template');
                    }

                    if (!empty($sms_template)) {
                        $replace = array(
                            '[customer_name]' => $reminder['customer_name'],
                            '[order_id]' => $reminder['order_id'],
                            '[products]' => $products_sms_info,
                            '[review_link]' => $review_link,
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

                        $sent_sms_count++;
                    }
                }
            }

            $this->model_octemplates_module_oct_review_reminder->markReminderAsSent($reminder['order_id']);
        }

        echo 'Sent ' . $sent_emails_count . ' emails and ' . $sent_sms_count . ' SMS messages.' . PHP_EOL;
    }
}
