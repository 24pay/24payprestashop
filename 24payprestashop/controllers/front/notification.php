<?php

class TwentyFourPayNotificationModuleFrontController extends ModuleFrontController {
	
	public function postProcess() {
		if (!isset($_POST["params"]))
			die("No notification received");

		$xmlNotification = $_POST["params"];			

		if (!($notification = $this->module->getTwentyFourPayNotificationParser($xmlNotification)))
			die("Invalid notification");

		$orderId = $this->module->decodeMsTxnId($notification->getMsTxnId());
		$order = new Order($orderId);

		if (!$order)
			die("No order with such ID");

		$history = new OrderHistory();
		$history->id_order = (int) $order->id;
		
		if ($notification->transactionIsOk()) {
			$order->setCurrentState(Configuration::get("PS_OS_PAYMENT"));
			
		} elseif ($notification->transactionHasFailed()) {
			$order->setCurrentState(Configuration::get("PS_OS_ERROR"));

		} elseif ($notification->transactionIsPending()) {
			// $history->changeIdOrderState(3, (int) $order->id); //order status=0

		}

		die("OK");
	}

}
