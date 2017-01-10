<?php

class TwentyFourPayValidationModuleFrontController extends ModuleFrontController {
	public $ssl = true;
	public $display_column_left = false;

	public function postProcess() {
		$module = $this->module;
		$cart = $this->context->cart;
		
		$customer = new Customer($cart->id_customer);
		$currency = new Currency($cart->id_currency);

		$this->module->validateOrder(
			(int) $cart->id, 
			Configuration::get('PS_OS_CHEQUE'),
			(float) $cart->getOrderTotal(true, Cart::BOTH), 
			$module->displayName, 
			NULL, 
			$mailVars, 
			(int) $currency->id, 
			false,
			$customer->secure_key
			);

		Tools::redirect('index.php?controller=order-confirmation&' .
			'id_cart=' . ((int) $cart->id) . '&' .
			'id_module=' . ((int) $module->id) . '&' .
			'id_order=' . $module->currentOrder . '&' .
			'key=' . $customer->secure_key
			);
	}
}
