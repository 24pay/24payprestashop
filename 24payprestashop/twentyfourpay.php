<?php
if (!defined('_PS_VERSION_'))
	exit;

require __DIR__ . '/twentyfourpay/TwentyFourPayGateways.class.php';
require __DIR__ . '/twentyfourpay/TwentyFourPayNotificationParser.class.php';
require __DIR__ . '/twentyfourpay/TwentyFourPayRequestBuilder.class.php';
require __DIR__ . "/twentyfourpay/country_code_converter.php";

class twentyfourpay extends PaymentModule {
	private $_html = '';
	private $_postErrors = array();
	private $_moduleWarnings = array();
	private $_config = null;

	function __construct() {
		$this->name = 'twentyfourpay';
		$this->tab = 'payments_gateways';
		$this->version = '1.2'; //Muro
		$this->author = '24pay s.r.o. LD';
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
		$this->bootstrap = true;

		parent::__construct();

		$this->displayName = $this->l('24pay');
		$this->description = $this->l('Multiple gateways payment solution.');

		$config = $this->getConfig();

//Muro		if ((!isset($config->gateways) || empty($config->gateways)))
		if ((!isset($config["TWENTYFOURPAY_GATEWAYS"]) || empty($config["TWENTYFOURPAY_GATEWAYS"]))) //Muro
			$this->_moduleWarnings[] = $this->l('There are no gateways loaded.');  //Muro
			
		if (!Configuration::get('PS_SHOP_ENABLE')) //Muro
			$this->_moduleWarnings[] = $this->l('The 24pay module does not run properly when the shop is in Maintenance mode. Enable shop for proper functionality.');  //Muro

		if (count($this->_moduleWarnings) > 0)
			$this->warning = "<BR>";
			$wrn_counter = 1;
			foreach ($this->_moduleWarnings as $wrn) {
				$this->warning .= (string)$wrn_counter . ". " .$wrn . "<BR>";
				$wrn_counter = $wrn_counter + 1;
			}
	}



	public function install() {
		if (!parent::install() ||
			!$this->registerHook('payment')
		) {
			if ($this->l('ERROR_MESSAGE_INSTALL_MODULE') == "ERROR_MESSAGE_INSTALL_MODULE") {
				$this->warning = "There was an Error installing the module.";
			} else {
				$this->warning = $this->l('ERROR_MESSAGE_INSTALL_MODULE');
			}
		}

		if (is_null($this->warning) && !$this->addOrderStatuses()) {
			if ($this->l('ERROR_MESSAGE_CREATE_ORDER_STATUS') == "ERROR_MESSAGE_CREATE_ORDER_STATUS") {
				$this->warning = "There was an Error creating a custom order status.";
			} else {
				$this->warning = $this->l('ERROR_MESSAGE_CREATE_ORDER_STATUS');
			}
		}

		return is_null($this->warning);
	}

	private function addOrderStatuses( ) {
		try {
			$stateConfig = [
				'color' => 'blue',
				'send_email' => false
			];

			$this->addOrderStatus(
				'TWENTYFOURPAY_PAYMENT_STATUS_PENDING',
				'Pending',
				$stateConfig
			);
		} catch (Exception $exception) {
			return false;
		}

		try {
			$stateConfig = [
				'color' => 'red',
				'send_email' => true
			];

			$this->addOrderStatus(
				'TWENTYFOURPAY_PAYMENT_STATUS_FAILED',
				'Failed',
				$stateConfig
			);

			return true;

		} catch (Exception $exception) {
			return false;
		}
	}


    public function addOrderStatus( $configKey, $statusName, $stateConfig ) {
		if ( ! Configuration::get( $configKey ) ) {
			$orderState              = new OrderState();
			$orderState->name        = array();
			$orderState->module_name = $this->name;
			$orderState->color       = $stateConfig['color'];
			$orderState->hidden      = false;
			$orderState->delivery    = false;
			$orderState->logable     = true;
			$orderState->invoice     = false;
			$orderState->paid        = false;
			$orderState->send_email  = $stateConfig['send_email'];

			foreach ( Language::getLanguages() as $language ) {
				$orderState->template[ $language['id_lang'] ] = 'payment';
				$orderState->name[ $language['id_lang'] ]     = $statusName;
			}

			if ( $orderState->add() ) {
				$twentyfourpayIcon = dirname( __FILE__ ) . '/logo.png';
				$newStateIcon = dirname( __FILE__ ) . '/../../img/os/' . (int) $orderState->id . '.gif';

                copy( $twentyfourpayIcon, $newStateIcon );
			}

			Configuration::updateValue( $configKey, (int) $orderState->id );
		}
	}


	public function uninstall( ) {
		if (!$this->unregisterHook('payment') ||
			!parent::uninstall()
		) {
			return false;
		}

		Configuration::updateValue('TWENTYFOURPAY_MID', '');
		Configuration::updateValue('TWENTYFOURPAY_KEY', '');
		Configuration::updateValue('TWENTYFOURPAY_ESHOPID', '');

		return true;
	}



	public function getConfigFieldsValues() {
		return array(
			'TWENTYFOURPAY_MID' => Tools::getValue('TWENTYFOURPAY_MID', Configuration::get('TWENTYFOURPAY_MID')),
			'TWENTYFOURPAY_KEY' => Tools::getValue('TWENTYFOURPAY_KEY', Configuration::get('TWENTYFOURPAY_KEY')),
			'TWENTYFOURPAY_ESHOPID' => Tools::getValue('TWENTYFOURPAY_ESHOPID', Configuration::get('TWENTYFOURPAY_ESHOPID')),
			'TWENTYFOURPAY_ALLOW_TEST_GATEWAY' => Tools::getValue('TWENTYFOURPAY_ALLOW_TEST_GATEWAY', Configuration::get('TWENTYFOURPAY_ALLOW_TEST_GATEWAY')),
			'TWENTYFOURPAY_GATEWAYS' => Tools::getValue('TWENTYFOURPAY_GATEWAYS', Configuration::get('TWENTYFOURPAY_GATEWAYS'))
		);
	}



	public function getContent() {
		$this->_html = '';

		if (Tools::isSubmit('btnSubmit'))
		{
			$this->_postValidation();
			if (!count($this->_postErrors))
				$this->_postProcess();
			else
				foreach ($this->_postErrors as $err)
					$this->_html .= $this->displayError($err);
		}

		$this->_html .= $this->_displayTwentyForPay();
		$this->_html .= $this->renderForm();

		return $this->_html;
	}



	public function renderForm() {
		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('24pay configuration'),
					'icon' => 'icon-envelope'
				),
				'input' => array(
					array(
						'type' => 'text',
						'label' => $this->l('MID'),
						'name' => 'TWENTYFOURPAY_MID',
					),
					array(
						'type' => 'text',
						'label' => $this->l('Eshop ID'),
						'name' => 'TWENTYFOURPAY_ESHOPID',
					),
					array(
						'type' => 'text',
						'label' => $this->l('KEY'),
						'name' => 'TWENTYFOURPAY_KEY',
					),
					array(
						'type' => 'switch',
						'label' => $this->l('Use test gateway (debug)'),
						'name' => 'TWENTYFOURPAY_ALLOW_TEST_GATEWAY',
						'values' => array(
							array(
								'id' => 'active_on',
								'value' => 1,
								'label' => $this->l('Yes')
							),
							array(
								'id' => 'active_off',
								'value' => 0,
								'label' => $this->l('No')
							)
						),
					),
				),
				'submit' => array(
					'title' => $this->l('Save'),
				)
			),
		);
		
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));

		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table =  $this->table;
		
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		
		$this->fields_form = array();
		
		$helper->id = (int)Tools::getValue('id_carrier');
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'btnSubmit';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);

		return $helper->generateForm(array($fields_form));
	}



	private function _postValidation() {
		if (Tools::isSubmit('btnSubmit')) {
/*Muro
		if (!Tools::getValue('TWENTYFOURPAY_MID'))
				$this->_postErrors[] = $this->l('The "MID" field is required.');
			
			if (!Tools::getValue('TWENTYFOURPAY_KEY'))
				$this->_postErrors[] = $this->l('The "KEY" field is required.');

			if (!Tools::getValue('TWENTYFOURPAY_ESHOPID'))
				$this->_postErrors[] = $this->l('The "Eshop ID" field is required.');
Muro */
			$MidValue = Tools::getValue('TWENTYFOURPAY_MID');
			$KeyValue = Tools::getValue('TWENTYFOURPAY_KEY');
			$EshopValue = Tools::getValue('TWENTYFOURPAY_ESHOPID');
			
			if (!$MidValue)
				$this->_postErrors[] = $this->l('The "MID" field is required.');

			elseif (!preg_match("/^[a-zA-Z0-9]{8}$/", $MidValue))
				$this->_postErrors[] = $this->l('Invalid "MID" value.');

			
			if (!$KeyValue)
				$this->_postErrors[] = $this->l('The "KEY" field is required.');
//Muro			elseif (!preg_match("/[a-zA-Z0-9]{64}/", $KeyValue))
			elseif (!preg_match("/^[a-zA-Z0-9]{64}$/", $KeyValue))
				$this->_postErrors[] = $this->l('Invalid "KEY" value.');


			if (!$EshopValue)
				$this->_postErrors[] = $this->l('The "Eshop ID" field is required.');
//Muro			elseif (!preg_match("/[0-9]{1,10}/", $EshopValue))
			elseif (!preg_match("/^[0-9]{1,10}$/", $EshopValue))
				$this->_postErrors[] = $this->l('Invalid "Eshop ID" value.');

		}
	}



	private function _postProcess() {
		Configuration::updateValue('TWENTYFOURPAY_MID', Tools::getValue('TWENTYFOURPAY_MID'));
		Configuration::updateValue('TWENTYFOURPAY_KEY', Tools::getValue('TWENTYFOURPAY_KEY'));
		Configuration::updateValue('TWENTYFOURPAY_ESHOPID', Tools::getValue('TWENTYFOURPAY_ESHOPID'));
		Configuration::updateValue('TWENTYFOURPAY_ALLOW_TEST_GATEWAY', Tools::getValue('TWENTYFOURPAY_ALLOW_TEST_GATEWAY'));

		if (!count($this->_postErrors)) {
			try {
				$twentyfourpay = new TwentyFourPayGateways(
					Tools::getValue("TWENTYFOURPAY_MID"),
					Tools::getValue("TWENTYFOURPAY_KEY"),
					Tools::getValue("TWENTYFOURPAY_ESHOPID")
					);

				if (!$twentyfourpay->checkSignGeneration())
					$this->_postErrors[] = $this->l('Sign generation failed – check if you are providing right credentials (MID, KEY and Eshop ID');

				if (count(!$this->_postErrors)){
					$gateways = $twentyfourpay->loadAvailableGateways();

					if (!count($gateways)) { 
						$this->_postErrors[] = $this->l('No gateways available for given credentials');
					} else {
						Configuration::updateValue('TWENTYFOURPAY_GATEWAYS', json_encode($gateways));
					}

				}
			} catch (TwentyFourPayGatewaysException $e) {
				$this->_postErrors[] = $e->getMessage();
			}
		}

		if (count($this->_postErrors)) {
			//if there are problems loading gateways, reset GATEWAYS in the database to NULL
			Configuration::updateValue('TWENTYFOURPAY_GATEWAYS', null);
			foreach ($this->_postErrors as $err)		//Muro
			$this->_html .= $this->displayError($err);  //Muro
			
		} else {
			$gatewaysList = array();

			foreach ($gateways as $gateway)
				$gatewaysList[] = '[' . $gateway . '] ' . $twentyfourpay->getGatewayName($gateway);

			$this->_html .= $this->displayConfirmation(
				sprintf($this->l('Settings updated. Loaded gateways: %s'), implode(", ", $gatewaysList))
			);
		}
	}



	private function _displayTwentyForPay() {
		return $this->display(__FILE__, 'infos.tpl');
	}



	public function hookPayment($params) {
        if (!$this->active)
			return;

		$this->smarty->assign(array(
			'this_path' => $this->_path,
			'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/'
		));

		return $this->display(__FILE__, 'payment.tpl');
	}


	public function getConfig() {
		if (!$this->_config) {
			$this->_config = Configuration::getMultiple(array(
				"TWENTYFOURPAY_MID",
				"TWENTYFOURPAY_KEY",
				"TWENTYFOURPAY_ESHOPID",
				"TWENTYFOURPAY_ALLOW_TEST_GATEWAY",
				"TWENTYFOURPAY_GATEWAYS",
				));
		}

		return $this->_config;
	}



	public function getTwentyFourPayGateways() {
		$config = $this->getConfig();

		return new TwentyFourPayGateways(
			$config["TWENTYFOURPAY_MID"],
			$config["TWENTYFOURPAY_KEY"],
			$config["TWENTYFOURPAY_ESHOPID"]
			);
	}



	public function getTwentyFourPayRequestBuilder($params) {
		return new TwentyFourPayRequestBuilder($this->getTwentyFourPayGateways(), $params);
	}



	public function getTwentyFourPayNotificationParser($xmlNotification) {
		return new TwentyFourPayNotificationParser($this->getTwentyFourPayGateways(), $xmlNotification);
	}



	public function getAvailableGateways() {
		$config = $this->getConfig();
		$gateways = json_decode($config["TWENTYFOURPAY_GATEWAYS"], true);

		if ($config["TWENTYFOURPAY_ALLOW_TEST_GATEWAY"])
			unset($gateways[$this->getTwentyFourPayGateways()->getTestGatewayId()]);

		return $gateways;
	}



	public function convertCountryCodeToIsoA3($isoa2code) {
		return convert_country_code_from_isoa2_to_isoa3($isoa2code);
	}



	public function encodeMsTxnId($id) {
		return "240000" . $id;
	}



	public function decodeMsTxnId($msTxnId) {
		return substr($msTxnId, 6);
	}

}

