<?php

/**
 * class handling preparation and form generation for payment request sended to 24pay gateway server
 */
class TwentyFourPayRequestBuilder {

	protected $twentyFourPay;

	protected $errors = array();

	protected $requestParams = array(
		"PreAuthProvided" => false,
		"RURL" => null,
		"NURL" => null,
		"MsTxnId" => null,
		"Amount" => null,
		"CurrNumCode" => null,
		"CurrAlphaCode" => null,
		"LangCode" => null,
		"ClientId" => null,
		"FirstName" => null,
		"FamilyName" => null,
		"Email" => null,
		"Phone" => null,
		"Street" => null,
		"Zip" => null,
		"City" => null,
		"Country" => null,
		"Timestamp" => null
		);



	/**
	 * @param TwentyFourPayGateways $twentyFourPayGateways
	 * @param array        $requestParams
	 */
	public function __construct(TwentyFourPayGateways $twentyFourPayGateways, array $requestParams = array()) {
		$this->twentyFourPayGateways = $twentyFourPayGateways;

		foreach ($requestParams as $key => $value) {
			if (method_exists ($this, "set" . $key))
				call_user_func(array($this, "set" . $key), $value);
		}
	}


	public function getTwentyFourPayGateways() {
		return $this->twentyFourPayGateways;
	}


	public function setPreAuthProvided($value) {
		$this->requestParams["PreAuthProvided"] = $value;
	}



	public function setRURL($value) {
		$this->requestParams["RURL"] = $value;
	}



	public function setNURL($value) {
		$this->requestParams["NURL"] = $value;
	}



	public function setMsTxnId($value) {
		$this->requestParams["MsTxnId"] = $value;
	}



	public function setAmount($value) {
		$this->requestParams["Amount"] = number_format($value, 2, ".", "");
	}



	public function setCurrNumCode($value) {
		$this->requestParams["CurrNumCode"] = $value;
	}



	public function setCurrAlphaCode($value) {
		$this->requestParams["CurrAlphaCode"] = $value;
	}



	public function setLangCode($value) {
		$this->requestParams["LangCode"] = $value;
	}



	public function setClientId($value) {
		$this->requestParams["ClientId"] = str_pad($value, 3, "0", STR_PAD_LEFT);
	}



	public function setFirstName($value) {
		$this->requestParams["FirstName"] = $value;
	}



	public function setFamilyName($value) {
		$this->requestParams["FamilyName"] = $value;
	}



	public function setEmail($value) {
		$this->requestParams["Email"] = $value;
	}


/*Muro
	public function setPhone($value) {
		$this->requestParams["Phone"] = $value;
	}
Muro */

	public function setPhone($value) {
		if (strlen($value) < 8)		//Muro
			$value = str_pad($value, 8, " ", STR_PAD_LEFT);  //Muro
		$this->requestParams["Phone"] = $value;
	}



	public function setStreet($value) {
		$this->requestParams["Street"] = $value;
	}



	public function setZip($value) {
		$this->requestParams["Zip"] = $value;
	}



	public function setCity($value) {
		$this->requestParams["City"] = $value;
	}



	public function setCountry($value) {
		$this->requestParams["Country"] = $value;
	}



	public function setTimestamp($value) {
		$this->requestParams["Timestamp"] = $value;
	}



	public final function validate() {
		$this->errors = array();

		foreach ($this->requestParams as $key => $value) {
			switch ($key) {
				case "PreAuthProvided":
					if (!is_bool($value))
						$this->errors[] = $key;

					break;

				case "NURL":
				case "RURL":
					if ($value && !filter_var($value, FILTER_VALIDATE_URL) || strlen($value) > 255)
						$this->errors[] = $key;

					break;

				case "MsTxnId":
					if (!preg_match("/^[a-zA-Z0-9]{1,20}$/", $value))
						$this->errors[] = $key;

					break;

				case "Amount":
					if (!preg_match("/^[0-9]{1,6}(\.[0-9]{2})?$/", $value))
						$this->errors[] = $key;

					break;

				case "CurrNumCode":
					if ($value && !preg_match("/^[0-9]{3}$/", $value))
						$this->errors[] = $key;

					break;

				case "CurrAlphaCode":
					if (!preg_match("/^[A-Z]{3}$/", $value))
						$this->errors[] = $key;

					break;

				case "LangCode":
					if (!preg_match("/[A-Z]{2}/", $value))
						$this->errors[] = $key;

					break;

				case "ClientId":
					if (!preg_match("/[a-zA-Z0-9]{3,10}/", $value))
						$this->errors[] = $key;

					break;

				case "Email":
					if (!filter_var($value, FILTER_VALIDATE_EMAIL) || strlen($value) < 6 || strlen($value) > 128)
						$this->errors[] = $key;

					break;

				case "Phone":
					if ($value && (strlen($value) < 8 || strlen($value) > 25))
						$this->errors[] = $key;

					break;

				case "Street":
					if (!$value || strlen($value) < 5 || strlen($value) > 50)
						$this->errors[] = $key;

					break;

				case "Zip":
					if (!$value || strlen($value) < 1 || strlen($value) > 10)
						$this->errors[] = $key;

					break;

				case "City":
					if (!$value || strlen($value) < 2 || strlen($value) > 30)
						$this->errors[] = $key;

					break;

				case "Country":
					if (!preg_match("/[A-Z]{3}/", $value))
						$this->errors[] = $key;

					break;
			}
		}

		return count($this->errors) == 0;
	}



	/**
	 * returns all given parameters expanded by Mid, EshopId, current Timestamp and computed Sign params.
	 * also rise an exception if given params are not valid accordit to 24pay specs
	 * @return array
	 */
	public function getParams() {
		if (!$this->validate())
			throw new TwentyFourPayRequestException("Invalid request parameters: " . implode(", ", $this->errors));

		$requestParams = array_filter($this->requestParams);

		$requestParams["Mid"] = $this->twentyFourPayGateways->getMid();
		$requestParams["EshopId"] = $this->twentyFourPayGateways->getEshopId();
		$requestParams["Timestamp"] = date("Y-m-d H:i:s");

		$requestParams["Sign"] = $this->twentyFourPayGateways->computeSIGN(
			$requestParams["Mid"] .
			$requestParams["Amount"] .
			$requestParams["CurrAlphaCode"] .
			$requestParams["MsTxnId"] .
			$requestParams["FirstName"] .
			$requestParams["FamilyName"] .
			$requestParams["Timestamp"]
			);

		return $requestParams;
	}



	/**
	 * generates form input fields for for this request
	 * @return string
	 */
	public function generateRequestFormFields() {
		$formFields = '';

		foreach ($this->getParams() as $key => $value) {
			$formFields .= '<input type="hidden" name="' . $key . '" value="' . addcslashes($value, '"') . '" />' . "\n";
		}

		return $formFields;
	}



	/**
	 * generates forms for all provided gateways
	 * @param array $gateways array of gateways ids
	 * @return string html form
	 */	
	public function generateRequestForms($gateways) {
		$formFields = $this->generateRequestFormFields();
		$html = "";

		foreach ( (array) $gateways as $gateway ) {
			if ( $gateway == 3999 ) {
				$html .=
					'<form id="twentyfourpay-gateway" style="display: inline-block;" class="twentyfourpay-gateway twentyfourpay-gateway-' . $gateway . '" action="' . $this->twentyFourPayGateways->getGatewayUrl() . '" method="post">' .
						$formFields .
						'<input id="formButton" type="image" style="border:1px solid #323232;" src="' . $this->twentyFourPayGateways->getGatewayIcon( "universal" ) . '" />' .
					'</form>';
			} else {
//				$html .=
//					'<form id="twentyfourpay-gateway-' . $gateway . '" style="display: inline-block;" class="twentyfourpay-gateway twentyfourpay-gateway-' . $gateway . '" action="' . $this->twentyFourPayGateways->getGatewayUrl( $gateway ) . '" method="post">' .
//						$formFields .
//						'<input type="image" style="border:1px solid #323232;" src="' . $this->twentyFourPayGateways->getGatewayIcon( $gateway ) . '" alt="' . $this->twentyFourPayGateways->getGatewayName( $gateway ) . '" />' .
//					'</form>';
			}
		}

		$html .= '' .
			'<script language="javascript">' .
				'document.getElementById("formButton").click();' .
			'</script>';

		return '<div class="twentyfourpay-gateways">' . $html . '</div>';
	}

}



class TwentyFourPayRequestException extends TwentyFourPayGatewaysException {}
