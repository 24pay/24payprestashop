<?php

class TwentyFourPayResultModuleFrontController extends ModuleFrontController {
	
	public function postProcess() {
		$orderId = $this->module->decodeMsTxnId(Tools::getValue("MsTxnId"));

		$this->context->smarty->assign(array(
			"status" => Tools::getValue("Result"),
			"orderId" => $orderId,
			"amount" => Tools::getValue("Amount"),
			"currency" => Tools::getValue("CurrCode"),
			"order" => new Order($orderId),
			"module" => $this->module
			));

		return $this->setTemplate('transaction_result.tpl');
	}

}

