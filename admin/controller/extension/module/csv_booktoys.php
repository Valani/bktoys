<?php
class ControllerExtensionModuleCsvBookToys extends Controller {
    public function index() {
        $this->response->redirect($this->url->link('octemplates/module/csv_booktoys', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function install() {
        // Forward the install call to our actual controller
        $this->load->controller('octemplates/module/csv_booktoys/install');
    }

    public function uninstall() {
        // Forward the uninstall call to our actual controller
        $this->load->controller('octemplates/module/csv_booktoys/uninstall');
    }
}
