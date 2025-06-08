<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerAccountOctStockNotifier extends Controller {
    public function index() {

        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('account/oct_stock_notifier', '', true);
            $this->response->redirect($this->url->link('account/login', '', true));
        }

        if (!$this->config->get('oct_stock_notifier_status')) {
            $this->redirectToNotFound();
        }

        $this->language->load('octemplates/module/oct_stock_notifier');
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('octemplates/module/oct_stock_notifier');

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_account'),
            'href' => $this->url->link('account/account', '', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('account/oct_stock_notifier', '', true)
        );

        $data['requests'] = array();

        $url = '';

        if (isset($this->request->get['page'])) {
            $page = (int)$this->request->get['page'];
            $url .= '&page=' . $this->request->get['page'];
        } else {
            $page = 1;
        }

        $limit = 20;
        $start = ($page - 1) * $limit;

        $requests = $this->model_octemplates_module_oct_stock_notifier->getUserRequests($this->customer->getId(), $start, $limit);
        $request_total = $this->model_octemplates_module_oct_stock_notifier->getTotalUserRequests($this->customer->getId());

        foreach ($requests as $request) {
            $data['requests'][] = array(
                'subscription_id' => $request['subscription_id'],
                'product_name' => $request['product_name'],
                'product_id' => $request['product_id'],
                'product_href' => $this->url->link('product/product', 'product_id=' . $request['product_id']),
                'status' => $request['status'],
                'subscribed_date' => $this->load->controller('octemplates/main/oct_functions/OctDateTime', array($request['subscribed_date'], 1)),
                'notified_date' => (isset($request['notified_date']) && $request['notified_date']) ? $this->load->controller('octemplates/main/oct_functions/OctDateTime', array($request['notified_date'], 1)) : ''
            );
        }

        $data['continue'] = $this->url->link('account/account', '', true);
        $data['continue_redirect'] = $this->url->link('account/oct_stock_notifier', '', true);

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $pagination = new Pagination();
        $pagination->total = $request_total;
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->url = $this->url->link('account/oct_stock_notifier', 'page={page}', true);

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($request_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($request_total - $limit)) ? $request_total : ((($page - 1) * $limit) + $limit), $request_total, ceil($request_total / $limit));

        $this->response->setOutput($this->load->view('account/oct_stock_notifier', $data));
    }

    public function remove() {
        if ($this->isValidRequest()) {
            $this->load->language('octemplates/module/oct_stock_notifier');
            $this->load->model('octemplates/module/oct_stock_notifier');

            $json = array();

            if (!$this->customer->isLogged()) {
                $json['redirect'] = $this->url->link('account/login', '', true);
            } elseif (isset($this->request->post['subscription_id'])) {
                if ($this->model_octemplates_module_oct_stock_notifier->removeRequest($this->request->post['subscription_id'])) {
                    $json['success'] = $this->language->get('text_success_remove');
                    $this->session->data['success'] = $this->language->get('text_success_remove');
                } else {
                    $json['error'] = $this->language->get('error_remove');
                }
            } else {
                $json['error'] = $this->language->get('error_remove');
            }

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
        } else {
            $this->redirectToNotFound();
        }
    }

    private function redirectToNotFound() {
		$this->response->redirect($this->url->link('error/not_found', '', true));
	}

    private function isValidRequest() {
		return $this->config->get('oct_stock_notifier_status') &&
			   isset($this->request->server['HTTP_X_REQUESTED_WITH']) &&
			   !empty($this->request->server['HTTP_X_REQUESTED_WITH']) &&
			   strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
	}
}