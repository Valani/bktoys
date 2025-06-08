<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesEventsSmartcheckout extends Controller {

    const CHECKOUT_ROUTE = 'checkout/checkout';
    const SMART_CHECKOUT_ROUTE = 'checkout/oct_smartcheckout';

    public function index(&$route, &$data) {
        $this->load->model('setting/setting');

        $smartcheckoutSettings = $this->model_setting_setting->getSetting('oct_smart_checkout_data');
        $smartcheckoutData = $smartcheckoutSettings['oct_smart_checkout_data'];

        $isCheckoutRoute = ($route == self::CHECKOUT_ROUTE);
        $issmartcheckoutActive = (isset($smartcheckoutData['status']) && $smartcheckoutData['status'] == "on");

        if ($isCheckoutRoute && $issmartcheckoutActive) {
            $route = self::SMART_CHECKOUT_ROUTE;
        }
    }
}
