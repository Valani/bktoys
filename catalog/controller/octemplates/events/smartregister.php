<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesEventsSmartregister extends Controller {

    const REGISTER_ROUTE = 'account/register';
    const SMART_REGISTER_ROUTE = 'account/oct_smart_register';

    public function index(&$route, &$data) {
        $this->load->model('setting/setting');

        $smartregisterSettings = $this->model_setting_setting->getSetting('oct_smart_register_data');
        $smartregisterData = $smartregisterSettings['oct_smart_register_data'];

        $isRegisterRoute = ($route == self::REGISTER_ROUTE);
        $isSmartregisterActive = (isset($smartregisterData['status']) && $smartregisterData['status'] == "on");

        if ($isRegisterRoute && $isSmartregisterActive) {
            $route = self::SMART_REGISTER_ROUTE;
        }
    }
}
