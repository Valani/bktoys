<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOctemplatesModuleOctStockNotifier extends Controller {

	private $error = array();

    public function index() {
        if ($this->isValidRequest()) {
            $this->language->load('octemplates/module/oct_stock_notifier');

            if (isset($this->request->post['product_id'])) {
				$product_id = (int) $this->request->post['product_id'];
			} else {
				$product_id = 0;
			}

			$this->load->model('catalog/product');

			$product_info = $this->model_catalog_product->getProduct($product_id);

            if ($product_info) {
				$data['oct_stock_notifier_data'] = $oct_stock_notifier_data = $this->config->get('oct_stock_notifier_data');

                $this->load->model('tool/image');

				if ($product_info['image']) {
					$data['thumb'] = $this->model_tool_image->resize($product_info['image'], 100, 100);
				} else {
					$data['thumb'] = '';
				}

				if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
					$data['price'] = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$data['price'] = false;
				}

				if ((float) $product_info['special']) {
					$data['special'] = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$data['special'] = false;
				}

				$data['heading_title_product'] = $product_info['name'];
				$data['href']                  = $this->url->link('product/product', 'product_id=' . $product_id, true);
				$data['product_id']            = (int) $product_id;
				$data['model']                 = $product_info['model'];
				$data['name']      			   = $this->customer->isLogged() ? $this->customer->getFirstName() . " " . $this->customer->getLastName() : '';
				$data['phone'] 				   = $this->customer->isLogged() ? $this->customer->getTelephone() : '';
				$data['email']     			   = $this->customer->isLogged() ? $this->customer->getEmail() : '';
		        $data['mask']    			   = isset($oct_stock_notifier_data['mask']) ? $oct_stock_notifier_data['mask'] : '';

				if ($this->config->get('config_account_id')) {
		            $this->load->model('catalog/information');

		            $information_info = $this->model_catalog_information->getInformation($this->config->get('config_account_id'));

		            if ($information_info) {
		                $data['text_terms'] = sprintf($this->language->get('text_oct_terms'), $this->url->link('information/information', 'information_id=' . $this->config->get('config_account_id'), true), $information_info['title'], $information_info['title']);
		            } else {
		                $data['text_terms'] = '';
		            }
		        } else {
		            $data['text_terms'] = '';
		        }

		        $this->response->setOutput($this->load->view('octemplates/module/oct_stock_notifier', $data));
			} else {
				$this->redirectToNotFound();
			} 
        } else {
            $this->redirectToNotFound();
        }
    }

	public function add() {
		if ($this->isValidRequest()) {
			$this->processSubscription();
		} else {
			$this->redirectToNotFound();
		}
	}

	public function cron() {
		$configData = $this->config->get('oct_stock_notifier_data');
		
		if ($this->config->get('oct_stock_notifier_status') && isset($configData['cron_secret'])) {
			$secretKey = $configData['cron_secret'];
			$requestKey = $this->request->get['cron_secret'] ?? '';
		
			if ($requestKey === $secretKey) {
				$this->processSubscribers();
			} else {
				$this->redirectToNotFound();
			}
		}
	}
	
	private function isValidRequest() {
		return $this->config->get('oct_stock_notifier_status') &&
			   isset($this->request->server['HTTP_X_REQUESTED_WITH']) &&
			   !empty($this->request->server['HTTP_X_REQUESTED_WITH']) &&
			   strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
	}
	
	private function processSubscription() {
		$this->language->load('octemplates/module/oct_stock_notifier');
		$this->load->model('catalog/product');
	
		$json = [];
		$data['oct_stock_notifier_data'] = $this->config->get('oct_stock_notifier_data');
		$product_info = $this->model_catalog_product->getProduct((int) $this->request->post['pid']);
	
		if ($this->validate() && $product_info) {
			$this->load->model('octemplates/module/oct_stock_notifier');
			$data = $this->collectSubscriptionData();
	
			if (!$this->model_octemplates_module_oct_stock_notifier->isAlreadySubscribed($data)) {
				$this->model_octemplates_module_oct_stock_notifier->addRequest($data);
				$this->notifyAdmin($data);
				$json['output'] = $this->language->get('text_success_send');
			} else {
				$json['error']['subscribed'] = $this->language->get('error_already_subscribed');
			}
		} else {
			$json['error'] = $this->error;
		}
	
		$this->respondJson($json);
	}
	
	private function collectSubscriptionData() {
		return [
			'name' => $this->request->post['name'] ?? null,
			'phone' => $this->request->post['phone'] ?? null,
			'email' => $this->request->post['email'] ?? null,
			'pid' => (int) $this->request->post['pid'],
			'customer_id' => $this->customer->isLogged() ? $this->customer->getId() : 0
		];
	}
	
	private function respondJson($json) {
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	private function processSubscribers() {
		$this->load->model('octemplates/module/oct_stock_notifier');
		$this->language->load('octemplates/module/oct_stock_notifier');
		
		$waitingProducts = $this->model_octemplates_module_oct_stock_notifier->getWaitingProducts();
	
		foreach ($waitingProducts as $product) {
			$subscribers = $this->model_octemplates_module_oct_stock_notifier->getCustomersByProductId((int) $product['product_id']);
			$this->notifySubscribers($subscribers, $product);
		}
	}
	
	private function notifySubscribers($subscribers, $product) {
		foreach ($subscribers as $subscriber) {
			if (!empty($subscriber['email'])) {
				$mailData = $this->prepareMailData($subscriber, $product);
				$this->sendMail($subscriber['email'], $mailData);
				$this->model_octemplates_module_oct_stock_notifier->updateSubscriptionStatus((int) $subscriber['subscription_id']);
			}

			if (!empty($subscriber['phone'])) {
				$oct_sms_settings = $this->config->get('oct_sms_settings');
                $template_code = 'oct_stock_notifier';

				$smsData = $this->prepareMailData($subscriber, $product);

				$language_id = $smsData['language_id'];

                if (isset($oct_sms_settings['templates'][$template_code]['status']) && $oct_sms_settings['templates'][$template_code]['status']) {
                    if (isset($oct_sms_settings['templates'][$template_code]['message'][$language_id]) && !empty($oct_sms_settings['templates'][$template_code]['message'][$language_id]) && isset($oct_sms_settings['templates'][$template_code]['edit_localization'])) {
                        $sms_template = $oct_sms_settings['templates'][$template_code]['message'][$language_id];
                    } else {
                        $sms_template = $this->language->get('default_sms_template');
                    }

                    if (!empty($sms_template)) {
                        $replace = array(
							'[customer_name]' => $smsData['customerName'],
                            '[product_name]' => $smsData['productName'],
                            '[product_link]' => $smsData['productLink'],
                            '[store]' => $this->config->get('config_name')
                        );

                        $sms_message = str_replace(array_keys($replace), array_values($replace), $sms_template);

						$this->load->model('octemplates/module/oct_sms_notify');
						$this->model_octemplates_module_oct_sms_notify->sendNotification(array(
							'phone' => $subscriber['phone'],
							'message' => $sms_message,
							'template_code' => $template_code,
							'access_token' => $oct_sms_settings['oct_sms_token']
						));
                    }
                }
			}
		}
	}

	private function sendMail($toEmail, $mailData) {
		$mail                 = new Mail($this->config->get('config_mail_engine'));
		$mail->protocol      = $this->config->get('config_mail_protocol');
		$mail->parameter     = $this->config->get('config_mail_parameter');
		$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
		$mail->smtp_username = $this->config->get('config_mail_smtp_username');
		$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
		$mail->smtp_port     = $this->config->get('config_mail_smtp_port');
		$mail->smtp_timeout  = $this->config->get('config_mail_smtp_timeout');

		$mail->setTo($toEmail);
		$mail->setFrom($this->config->get('config_email'));
		$mail->setSender(html_entity_decode($mailData['storeName'], ENT_QUOTES, 'UTF-8'));
		$mail->setSubject($mailData['subject']);
		
		if (!$mailData['type']) {
			$message = $mailData['welcome'] . "<br /><br />\n\n" . $mailData['body'] . "<br /><br /><br />\n\n" . $mailData['storeName'];
		} else {
			$message = html_entity_decode($mailData['welcome'] . "\n\n" . $mailData['storeName'], ENT_QUOTES, 'UTF-8');
		}

		$mail->setHtml($message);
		$mail->send();
	}
	
	private function prepareMailData($subscriber, $product) {
		$module_data = $this->config->get('oct_stock_notifier_data');
		$storeName = $this->config->get('config_name');
		$type = 0;

		$email_data 				= array();
		$email_data['subject'] 		= sprintf($this->language->get('mail_subject'), html_entity_decode($storeName, ENT_QUOTES, 'UTF-8'), $subscriber['name']);
		$email_data['welcome'] 		= sprintf($this->language->get('mail_welcome'), html_entity_decode($storeName, ENT_QUOTES, 'UTF-8'));
		$email_data['body'] 		= sprintf($this->language->get('mail_body'), $subscriber['name'], $this->url->link('product/product', 'product_id=' . $product['product_id']));
		$email_data['product_link'] = $this->url->link('product/product', 'product_id=' . $product['product_id']);
		$email_data['product_name'] = $subscriber['name'];
		$email_data['store_name']   = $this->config->get('config_name');
		$email_data['customer_name'] = $subscriber['customer_name'];

		if (isset($module_data['custom_message']) && $module_data['custom_message'] == "on") {
			$email_data['subject']  = $module_data['subject'][$subscriber['language_id']];
			$email_data['welcome']  = $module_data['message'][$subscriber['language_id']];
			$email_data['body']		= '';
			$type     				= 1;
			$this->convertHtmlMailData($email_data);
		}

		return [
			'welcome' => $email_data['welcome'],
			'subject' => $email_data['subject'],
			'body' => $email_data['body'],
			'storeName' => html_entity_decode($storeName, ENT_QUOTES, 'UTF-8'),
			'customerName' => $subscriber['customer_name'],
			'productName' => $subscriber['name'], 
			'productLink' => $email_data['product_link'],
			'language_id' => $subscriber['language_id'],
			'type' => $type
		];
	}

	private function convertHtmlMailData(&$data) {
        $data = str_replace(
            array(
				'[customer_name]',
                '[store]',
                '[product_name]',
                '[product_link]'
            ),
            array(
				$data['customer_name'],
                $data['store_name'],
                $data['product_name'],
                $data['product_link']
            ),
            $data
        );

        return $data;
    }

	private function notifyAdmin($data) {
		$this->language->load('octemplates/module/oct_stock_notifier');
		$this->load->model('catalog/product');

		$product_info = $this->model_catalog_product->getProduct((int) $data['pid']);

		$mailData = array();
		$storeName = $this->config->get('config_name');
		$mailData = [
			'welcome' => sprintf($this->language->get('mail_admin_welcome'), html_entity_decode($storeName, ENT_QUOTES, 'UTF-8')),
			'subject' => sprintf($this->language->get('mail_admin_subject'), html_entity_decode($storeName, ENT_QUOTES, 'UTF-8'), $product_info['name']),
			'body' => sprintf($this->language->get('mail_admin_body'), $product_info['name'], $this->url->link('product/product', 'product_id=' . $data['pid'])),
			'storeName' => html_entity_decode($storeName, ENT_QUOTES, 'UTF-8'),
			'productName' => $product_info['name'],
			'type' => 0
		];

		$configData = $this->config->get('oct_stock_notifier_data');
		$emails = explode(',', $configData['email']);

		foreach ($emails as $email) {
			if ($email && preg_match('/^[^\@]+@.*.[a-z]{2,15}$/i', $email)) {
				$this->sendMail(trim($email), $mailData);
			}
		}

		$oct_sms_settings = $this->config->get('oct_sms_settings');
		$template_code = 'oct_stock_notifier_admin';

		$language_id = $this->config->get('config_language_id');

		if (isset($oct_sms_settings['templates'][$template_code]['status']) && $oct_sms_settings['templates'][$template_code]['status']) {
			if (isset($oct_sms_settings['templates'][$template_code]['message'][$language_id]) && !empty($oct_sms_settings['templates'][$template_code]['message'][$language_id]) && isset($oct_sms_settings['templates'][$template_code]['edit_localization'])) {
				$sms_template = $oct_sms_settings['templates'][$template_code]['message'][$language_id];
			} else {
				$sms_template = $this->language->get('default_sms_template_admin');
			}

			if (!empty($sms_template)) {
				$replace = array(
					'[customer_name]' => $data['name'],
					'[product_name]' => $product_info['name'],
					'[product_link]' => $this->url->link('product/product', 'product_id=' . $data['pid']),
					'[store]' => $this->config->get('config_name')
				);

				$sms_message = str_replace(array_keys($replace), array_values($replace), $sms_template);

				$this->load->model('octemplates/module/oct_sms_notify');
				$this->model_octemplates_module_oct_sms_notify->sendNotification(array(
					'phone' => $data['phone'],
					'message' => $sms_message,
					'template_code' => $template_code,
					'access_token' => $oct_sms_settings['oct_sms_token']
				));
			}
		}
	}
	
	private function redirectToNotFound() {
		$this->response->redirect($this->url->link('error/not_found', '', true));
	}

	protected function validate() {
		$oct_stock_notifier_data = $this->config->get('oct_stock_notifier_data');

		if (isset($this->request->post['name']) && (isset($oct_stock_notifier_data['name']) && $oct_stock_notifier_data['name'] == 2) && (utf8_strlen(trim($this->request->post['name'])) < 1 || utf8_strlen(trim($this->request->post['name'])) > 32)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		if (isset($this->request->post['phone'])) {
			$this->request->post['phone'] = preg_replace("/[^0-9()\-_\+ ]/", '', $this->request->post['phone']);
			$phone = $this->request->post['phone'];
			$mask = $oct_stock_notifier_data['mask'] ?? '';
			$phone_setting = $oct_stock_notifier_data['phone'] ?? '';
			$theme_data = $this->config->get('theme_oct_remarket_data');
			$phone_regex = $theme_data['phone_regex'] ?? '';
	
			$cleanPhone = utf8_strlen(str_replace(['_', '-', '(', ')', '+'], "", $phone));
	
			if (!empty($mask)) {
				$phone_count = utf8_strlen(str_replace(['_', '-', '(', ')', '+'], "", $mask));
				if ($phone_setting == 2) {
					if ($cleanPhone < $phone_count) {
						$this->error['phone'] = $this->language->get('error_phone_mask');
					}
					if (!preg_match('/^\+?[0-9\s\-\(\)_]+$/', $phone)) {
						$this->error['phone'] = $this->language->get('error_phone_mask');
					}
				}
			} else {
				if ($phone_setting == 2) {
					if ($cleanPhone > 15 || $cleanPhone < 3) {
						$this->error['phone'] = $this->language->get('error_phone');
					}
					if (!preg_match('/^\+?[0-9\s\-\(\)_]+$/', $phone)) {
						$this->error['phone'] = $this->language->get('error_phone');
					}
				}
			}
	
			if (!empty($phone_regex)) {
				if (@preg_match($phone_regex, '') !== false) {
					if (!preg_match($phone_regex, $phone)) {
						$this->error['phone'] = $this->language->get('error_phone_mask');
					}
				} else {
					$this->error['phone'] = $this->language->get('error_phone_mask');
				}
			}
		}

		if (isset($this->request->post['email']) && (utf8_strlen($this->request->post['email']) > 96 || !preg_match('/^[^\@]+@.*.[a-z]{2,15}$/i', $this->request->post['email']))) {
			$this->error['email'] = $this->language->get('error_email');
		}

		if ($this->config->get('config_account_id') && !isset($this->request->post['agree'])) {
			$this->load->model('catalog/information');

			$information_info = $this->model_catalog_information->getInformation($this->config->get('config_account_id'));
			if (isset($information_info) && !empty($information_info)) {
				$this->error['agree'] = sprintf($this->language->get('error_oct_terms'), $information_info['title']);
			}
		}

		return !$this->error;
	}

}