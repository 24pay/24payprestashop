<?php

class TwentyFourPayGateChooserModuleFrontController extends ModuleFrontController {

    public function postProcess() {
        $gateways = $this->module->getAvailableGateways();

        if (!$gateways)
            die($this->l('This payment method is not available.', 'validation'));

        $orderId = Tools::getValue("id_order");
        $order = new Order($orderId);

        $customer = new Customer($order->id_customer);
        $address = new Address($order->id_address_invoice);
        $country = new Country($address->id_country);
        $language = new Language($order->id_lang);
        $currency = new Currency($order->id_currency);

        $twentyfourpay_request = $this->module->getTwentyFourPayRequestBuilder(array(
            "RURL" => Tools::getHttpHost(true) . __PS_BASE_URI__ . 'module/twentyfourpay/result',
            "NURL" => Tools::getHttpHost(true) . __PS_BASE_URI__ . 'module/twentyfourpay/notification',
            "MsTxnId" => $this->module->encodeMsTxnId($order->id),
            "Amount" => $order->total_paid,
            "CurrAlphaCode" => $currency->iso_code,
            "LangCode" => strtoupper($language->iso_code),
            "ClientId" => $customer->id,
            "FirstName" => $customer->firstname,
            "FamilyName" => $customer->lastname,
            "Email" => $customer->email,
            "Phone" => $address->phone ?: $address->phone_mobile,
            "Street" => $address->address1 . ( $address->address2 ? ', ' . $address->address2 : ''),
            "Zip" => $address->postcode,
            "City" => $address->city,
            "Country" => $this->module->convertCountryCodeToIsoA3($country->iso_code),
        ));

        $htmlForms = $twentyfourpay_request->generateRequestForms($this->module->getAvailableGateways());

        $this->context->smarty->assign('htmlForms', $htmlForms);
        $this->context->smarty->assign('objId', $orderId);

        return $this->setTemplate('gate_chooser.tpl');
	}

}
