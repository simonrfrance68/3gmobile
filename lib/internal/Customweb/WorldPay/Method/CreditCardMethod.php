<?php

/**
 *  * You are allowed to use this API in your web application.
 *
 * Copyright (C) 2018 by customweb GmbH
 *
 * This program is licenced under the customweb software licence. With the
 * purchase or the installation of the software in your application you
 * accept the licence agreement. The allowed usage is outlined in the
 * customweb software licence which can be found under
 * http://www.sellxed.com/en/software-license-agreement
 *
 * Any modification or distribution is strictly forbidden. The license
 * grants you the installation in one application. For multiuse you will need
 * to purchase further licences at http://www.sellxed.com/shop.
 *
 * See the customweb software licence agreement for more details.
 *
 */




/**
 *
 * @author Thomas Brenner
 * @Method(paymentMethods={'CreditCard', 'Visa', 'MasterCard', 'AmericanExpress', 'Aurore', 'AirPlus', 'CarteBancaire', 'Dankort', 'ECartebleue', 'Diners', 'Jcb', 'Uatp', 'LaserCard', 'DiscoverCard', 'Maestro' })
 *
 */
class Customweb_WorldPay_Method_CreditCardMethod extends Customweb_WorldPay_Method_DefaultMethod {

	public function getFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $authorizationMethod, $isMoto, $customerPaymentContext){
		$elements = array();
		
		/* @var $transaction Customweb_Payment_Authorization_Method_CreditCard_ElementBuilder */
		
		if ($authorizationMethod == Customweb_Payment_Authorization_Server_IAdapter::AUTHORIZATION_METHOD_NAME) {
			$formBuilder = new Customweb_Payment_Authorization_Method_CreditCard_ElementBuilder();
			
			// Set field names
			$formBuilder->setBrandFieldName('paymentmethod')->setCardHolderFieldName('card_holder')->setCardNumberFieldName('cardno')->setExpiryMonthFieldName(
					'expm')->setExpiryYearFieldName('expy')->setExpiryYearNumberOfDigits(2)->setCvcFieldName('cvv');
			
			// Handle brand selection
			if (strtolower($this->getPaymentMethodName()) == 'creditcard') {
				$formBuilder->setCardHandlerByBrandInformationMap($this->getPaymentInformationMap(), 
						$this->getPaymentMethodConfigurationValue('credit_card_brands'), 'PaymentMethod')->setAutoBrandSelectionActive(true);
			}
			else {
				$formBuilder->setCardHandlerByBrandInformationMap($this->getPaymentInformationMap(), $this->getPaymentMethodName(), 'PaymentMethod')->setSelectedBrand(
						$this->getPaymentMethodName())->setFixedBrand(true);
			}
			
			$formBuilder->setImageBrandSelectionActive(true);
			
			return $formBuilder->build();
		}
		else {
			if (strtolower($this->getPaymentMethodName()) == 'creditcard') {
				$options = array();
				foreach ($this->getPaymentMethodConfigurationValue('credit_card_brands') as $brand) {
					$info = $this->getPaymentInformationByBrand($brand);
					if (isset($info['parameters']['PaymentMethod'])) {
						$options[$info['parameters']['PaymentMethod']] = $info['method_name'];
					}
				}
				
				$control = new Customweb_Form_Control_Select('credit_card_brand', $options);
				$selectElement = new Customweb_Form_Element(Customweb_I18n_Translation::__('Select Card Type'), $control, 
						Customweb_I18n_Translation::__('Please select the brand of your card.'));
				$elements[] = $selectElement;
			}
		}
		return $elements;
	}

	/**
	 * This method maps a given brand to pmethod.
	 *
	 * @param string $brand
	 * @return null|array
	 */
	public function getPaymentInformationByBrand($brand){
		$map = $this->getPaymentInformationMap();
		$brand = strtolower($brand);
		if (isset($map[$brand])) {
			return $map[$brand];
		}
		else {
			return null;
		}
	}

	/**
	 * Gives back the internal Payment Method Name
	 */
	public function getPaymentMethodIdentifier(Customweb_WorldPay_Authorization_Transaction $transaction, $parameters){
		$methodName = parent::getPaymentMethodIdentifier($transaction, $parameters);
		if (!empty($methodName)) {
			return $methodName;
		}
		else {
			if ($transaction->getBrandName() != "NOTIDENTIFIED") {
				return $transaction->getBrandName();
			}
			else {
				if (isset($parameters['cardno'])) {
					$cardNumber = $parameters['cardno'];
					$formBuilder = new Customweb_Payment_Authorization_Method_CreditCard_ElementBuilder();
					$formBuilder->setCardHandlerByBrandInformationMap($this->getPaymentInformationMap(), 
							$this->getPaymentMethodConfigurationValue('credit_card_brands'), 'PaymentMethodXML');
					$brandKey = $formBuilder->getCardHandler()->getBrandKeyByCardNumber($cardNumber);
					
					$brandName = $formBuilder->getCardHandler()->mapBrandNameToExternalName($brandKey);
				}
				else if (isset($parameters['credit_card_brand'])) {
					$brandName = $parameters['credit_card_brand'];
				}
				else {
					$brandName = null;
				}
				$transaction->setBrandName($brandName);
				return $brandName;
			}
		}
	}

	public function processAuthorization(Customweb_Payment_Authorization_ITransaction $transaction, array $parameters, Customweb_WorldPay_Container $container){
		/* @var $transaction Customweb_WorldPay_Authorization_Transaction */
		
		//This loop is run for the initial run-through, the condition checks whether it is already the second run-through
		if (!isset($parameters['PaRes'])) {
			$transaction->setCheck3DSecure(false);
			$builder = new Customweb_WorldPay_Authorization_Server_ParameterBuilder($container, $transaction);
			$xmlString = $builder->generateXMLRequest($transaction, $parameters, $this, FALSE);
			$handler = Customweb_WorldPay_Util::sendDefaultRequest($xmlString, $this->getConfiguration());
			
			if ($handler->getStatusCode() != "200") {
				throw new Customweb_Payment_Exception_PaymentErrorException(
						new Customweb_Payment_Authorization_ErrorMessage(
								Customweb_I18n_Translation::__("Your payment was not successful, please contact the merchant."), 
								Customweb_I18n_Translation::__("The XML response was not correct. Please Check your Worldpay XML Credentials")));
			}
			
			$xmlArray = simplexml_load_string($handler->getBody());
			
			if (isset($xmlArray->reply->error)) {
				
				$attributes = $xmlArray->reply->error->attributes();
				$customerErrorMessage = Customweb_I18n_Translation::__("Your payment was not successful, please contact the merchant.");
				$code = 'unkown';
				
				if(isset($attributes['code'])){
					$code  =(string) $attributes['code'];
				}
				if($code == '5'){
					$customerErrorMessage = Customweb_I18n_Translation::__("Please enter correct card information");
				}
				
				throw new Customweb_Payment_Exception_PaymentErrorException(
						new Customweb_Payment_Authorization_ErrorMessage($customerErrorMessage,
								Customweb_I18n_Translation::__(
										"The initial request was not successful. WorldPay error code: !code",
										array(
											'!code' => $code
										))));
			}
			
			if (isset($xmlArray->reply->orderStatus->error)) {
				$customerErrorMessage = Customweb_I18n_Translation::__("Your payment was not successful, please contact the merchant.");
				$code = (string) $xmlArray->reply->orderStatus->error['code'];
				if ($code == 7) {
					$customerErrorMessage = Customweb_I18n_Translation::__("Please enter correct card information");
				}
				throw new Customweb_Payment_Exception_PaymentErrorException(
						new Customweb_Payment_Authorization_ErrorMessage($customerErrorMessage, 
								Customweb_I18n_Translation::__(
										"The initial request was not successful. WorldPay error code: !code", 
										array(
											'!code' => $code 
										))));
			}
			
			$transaction->setCookies($handler->getCookies());
			
			if (isset($xmlArray->reply->orderStatus->payment->lastEvent) && $xmlArray->reply->orderStatus->payment->lastEvent == "ERROR") {
				throw new Customweb_Payment_Exception_PaymentErrorException(
						new Customweb_Payment_Authorization_ErrorMessage(
								Customweb_I18n_Translation::__("Your payment was not successful, please contact the merchant."), 
								Customweb_I18n_Translation::__("The initial 3D request was not responsed properly. ")));
			}
			
			if (isset($xmlArray->reply->orderStatus->payment->lastEvent) && $xmlArray->reply->orderStatus->payment->lastEvent == "REFUSED") {
				throw new Customweb_Payment_Exception_PaymentErrorException(
						new Customweb_Payment_Authorization_ErrorMessage(
								Customweb_I18n_Translation::__("Your payment was not successful, please contact the merchant."), 
								Customweb_I18n_Translation::__("The initial 3D request was refused.")));
			}
			
			//No 3D check is necessary:			
			if (isset($xmlArray->reply->orderStatus->payment->lastEvent) && $xmlArray->reply->orderStatus->payment->lastEvent == "AUTHORISED") {
				Customweb_WorldPay_Method_MethodHelper::validateInformation($transaction, $xmlArray, $this->getConfiguration());
				if(isset($xmlArray->reply->orderStatus->payment->CVCResultCode) ){
					$attributes = $xmlArray->reply->orderStatus->payment->CVCResultCode->attributes();
					if(isset($attributes['description'])) {
						$transaction->addAuthorizationParameters(array('cvcResult' => (string) $attributes['description']));
					}
				}
				if(isset($xmlArray->reply->orderStatus->payment->AVSResultCode) ){
					$attributes = $xmlArray->reply->orderStatus->payment->AVSResultCode->attributes();
					if(isset($attributes['description'])) {
						$transaction->addAuthorizationParameters(array('avsResult' => (string) $attributes['description']));
					}
				}
				$transaction->setCheck3DSecure(true);
				$this->finishBackend($transaction, array(), $parameters, $container);
				return $this->finalizeAuthorizationRequest($transaction, array());
			}
			//Preparation of the 3D-Secure sequence
			else {
				$paRequest = (string) $xmlArray->reply->orderStatus->requestInfo->request3DSecure->paRequest;
				$issuerURL = (string) $xmlArray->reply->orderStatus->requestInfo->request3DSecure->issuerURL;
				$transaction->setEchoData((string) $xmlArray->reply->orderStatus->echoData);
				$additionalParameters = array(
					'paRequest' => $paRequest,
					'issuerUrl' => $issuerURL 
				);
				
				return $this->process3DSecure($transaction, $container, $additionalParameters, $parameters);
			}
		}
		//Second run-through after a successful 3DSecure-check, to finish the authorization
		else {
			$transaction->setCheck3DSecure(true);
			$transaction->setThreeDsParameters(array());
			$this->secondOrderMessage($transaction, $parameters, $container);
			$this->finishBackend($transaction, $parameters, array(), $container);
			return $this->finalizeAuthorizationRequest($transaction, array('breakout' => true));
		}
	}

	protected function process3DSecure(Customweb_Payment_Authorization_ITransaction $transaction, Customweb_WorldPay_Container $container, array $additionalParameters, array $parameters){
		$card_holder = "";
		if (isset($parameters['card_holder'])) {
			$card_holder = $parameters['card_holder'];
		}
		else if (isset($parameters['account_owner'])) {
			$card_holder = $parameters['account_owner'];
		}
		else {
			throw new Exception(Customweb_I18n_Translation::__("No valid card holder can be found."));
		}
		
		$transaction->setCCDetails(array(
			'card_holder' => $card_holder,
			'expy' => $parameters['expy'],
			'expm' => $parameters['expm'] 
		));
		
		if (isset($parameters['cvv']) && isset($parameters['cardno'])) {
			$details = json_encode(array(
				'cvv' => $parameters['cvv'],
				'cardno' => $parameters['cardno'] 
			));
		}
		else {
			$details = json_encode(array(
				'cardno' => $parameters['cardno'] 
			));
		}
		
		if (!isset($additionalParameters['issuerUrl'])) {
			throw new Exception(Customweb_I18n_Translation::__("No issuerUrl was given."));
		}
		$transaction->setThreeDsParameters($additionalParameters);
		
		return Customweb_Core_Http_Response::redirect(
				$container->getEndpointAdapter()->getUrl('process', 'threedsframe', 
						array(
							'cw_transaction_id' => $transaction->getExternalTransactionId(),
							'mde' => $transaction->encrypt($details),
							'cwSign' => $transaction->getSecuritySignature('process/threedsframe')
						)));
	}

	/**
	 * Creates the payment-specific part according to the payment method of the initial XML-Request
	 * 
	 * @see Customweb_WorldPay_Method_AbstractMethod::createXMLString()
	 */
	public function createXMLString($parameters, Customweb_Payment_Authorization_ITransaction $transaction){
		$xmlString = '<cardNumber>' . $parameters['cardno'] . '</cardNumber>
			<expiryDate>
				<date month="' . $parameters['expm'] . '" year="20' . $parameters['expy'] . '"/>
			</expiryDate>
			<cardHolderName>' . $parameters['card_holder'] . '</cardHolderName>
			<cvc>' . $parameters['cvv'] . '</cvc>
			<cardAddress>
				<address>
					<address1>' .
				 $transaction->getTransactionContext()->getOrderContext()->getBillingAddress()->getStreet() . '</address1>
					<postalCode>' .
				 $transaction->getTransactionContext()->getOrderContext()->getBillingAddress()->getPostCode() . '</postalCode>
				 	<city>'.$transaction->getTransactionContext()->getOrderContext()->getBillingAddress()->getCity().'</city>
					<countryCode>' .
				 $transaction->getTransactionContext()->getOrderContext()->getBillingAddress()->getCountryIsoCode() . '</countryCode>
				</address>
			</cardAddress> ';
		
		return $xmlString;
	}

	public function finalizeAuthorizationRequest(Customweb_Payment_Authorization_ITransaction $transaction, array $parameters){
		$response = new Customweb_Core_Http_Response();
		$url = $transaction->getSuccessUrl();
		if ($transaction->isAuthorizationFailed()) {
			$url =  $transaction->getFailedUrl();
		}
		if(!isset($parameters['breakout']) || !$parameters['breakout']){
			return Customweb_Core_Http_Response::redirect($url);
		}
		else{
			$response = new Customweb_Core_Http_Response();
			$response->setBody('<script type="text/javascript">
				top.location.href = "' . $url . '";
			</script>
		
			<noscript>
				<a class="button btn worldpay-continue-button submit" href="' . $url . '" target="_top">' . Customweb_I18n_Translation::__('Continue') . '</a>
			</noscript>');
			
			return $response;
		}
	}
}