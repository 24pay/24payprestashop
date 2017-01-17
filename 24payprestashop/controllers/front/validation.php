<?php

class TwentyFourPayValidationModuleFrontController extends ModuleFrontController {
	public $ssl = true;
	public $display_column_left = false;

	public function postProcess() {
		$module = $this->module;
		$cart = $this->context->cart;

		$customer = new Customer($cart->id_customer);
		$currency = new Currency($cart->id_currency);

		if (!Validate::isLoadedObject($customer))
			Tools::redirect('index.php?controller=order&step=1');

		$total = (float) $cart->getOrderTotal(true, Cart::BOTH);

		$module->validateOrder(
			(int) $cart->id,
			Configuration::get('TWENTYFOURPAY_PAYMENT_STATUS_PENDING'),
			$total,
			$module->displayName,
			null,
			null,
			(int) $currency->id,
			false,
			$customer->secure_key
			);

		$this->redirectToGateChooser($module->currentOrder, $customer->secure_key);
	}

	private function redirectToGateChooser($orderId, $secureKey) {
		Tools::redirect(
			$this->context->link->getModuleLink(
				'twentyfourpay',
				'gateChooser',
				[
					'id_order' => $orderId,
					'key' => $secureKey
				],
				true
			)
		);
	}
}
