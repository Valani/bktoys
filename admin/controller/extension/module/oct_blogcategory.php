<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerExtensionModuleOCTBlogCategory extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('octemplates/module/oct_blogcategory');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->addScript('view/javascript/octemplates/bootstrap-notify/bootstrap-notify.min.js');
		$this->document->addScript('view/javascript/octemplates/oct_main.js');
		$this->document->addStyle('view/stylesheet/oct_remarket.css');

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_oct_blogcategory', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
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
			'href' => $this->url->link('extension/module/oct_blogcategory', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/oct_blogcategory', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		if (isset($this->request->post['module_oct_blogcategory_status'])) {
			$data['module_oct_blogcategory_status'] = $this->request->post['module_oct_blogcategory_status'];
		} else {
			$data['module_oct_blogcategory_status'] = $this->config->get('module_oct_blogcategory_status');
		}

		if (isset($this->request->post['module_oct_blogcategory_search'])) {
			$data['module_oct_blogcategory_search'] = $this->request->post['module_oct_blogcategory_search'];
		} else {
			$data['module_oct_blogcategory_search'] = $this->config->get('module_oct_blogcategory_search');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('octemplates/module/oct_blogcategory', $data));
	}

	public function install() {
		$this->load->language('octemplates/blog/oct_blogsettings');

        $this->load->model('setting/setting');
		$this->load->model('user/user_group');

		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/oct_blogcategory');
	    $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/oct_blogcategory');

        	$oct_blog_info = $this->model_setting_setting->getSetting('oct_blogsettings');

		if (!$oct_blog_info) {
	        $this->response->redirect($this->url->link('octemplates/blog/oct_blogsettings/install', 'user_token=' . $this->session->data['user_token'], true));
	    }
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/oct_blogcategory')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}