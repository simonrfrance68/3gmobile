<?php
/**
  * You are allowed to use this API in your web application.
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
 * @Bean
 *
 */
class Customweb_WorldPay_Authorization_PaymentPage_Adapter extends Customweb_WorldPay_Authorization_AbstractAdapter implements Customweb_Payment_Authorization_PaymentPage_IAdapter {

	public function getAuthorizationMethodName(){
		return self::AUTHORIZATION_METHOD_NAME;
	}
	
	public function getAdapterPriority() {
		return 100;
	}

	public function createTransaction(Customweb_Payment_Authorization_PaymentPage_ITransactionContext $transactionContext, $failedTransaction){
		$transaction = new Customweb_WorldPay_Authorization_Transaction($transactionContext);
		$transaction->setAuthorizationMethod(self::AUTHORIZATION_METHOD_NAME);
		$transaction->setLiveTransaction(!$this->getConfiguration()->isTestMode());
		return $transaction;
	}

	public function getVisibleFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $customerPaymentContext){
		return $this->getContainer()->getBean('Customweb_WorldPay_Method_Factory')->getPaymentMethod($orderContext->getPaymentMethod(), self::AUTHORIZATION_METHOD_NAME)
		->getFormFields($orderContext, $aliasTransaction, $failedTransaction, self::AUTHORIZATION_METHOD_NAME, false, $customerPaymentContext);
	}

	public function getRedirectionUrl(Customweb_Payment_Authorization_ITransaction $transaction, array $formData){
		
		try{
			$responsePW = $this->getConfiguration()->getResponsePassword();
			$md5PW = $this->getConfiguration()->getMD5SecretKey();
			if(strlen($responsePW) > 25){
				throw new Exception(Customweb_I18n_Translation::__("The Payment Response password must be shorter than 25 characters."));
			}
			if(strlen($md5PW) > 30 || strlen($md5PW) < 10) {
				throw new Exception(Customweb_I18n_Translation::__("The MD-5 Secret must between 10 and 30 characters long."));
			}
		}
		catch(Exception $e){
			$transaction->setAuthorizationFailed($e->getMessage());
			return $transaction->getFailedUrl();
		}
		
		
		
		/* @var $transaction Customweb_WorldPay_Authorization_Transaction */
		$xmlAdapter = new Customweb_WorldPay_Authorization_PaymentPage_XmlAdapter($this->getConfiguration()->getConfigurationAdapter(), $this->getContainer());
		//Check whether this redirect falls into the category of xml-redirect
		if($xmlAdapter->isAnXmlRedirect($transaction)) {
			return $xmlAdapter->produceRedirectionUrl($transaction, $formData);
			
		} 
		//Usually it doesn't and it is performed by html-redirect
		else {
			$builder = new Customweb_WorldPay_Authorization_PaymentPage_ParameterBuilder($this->getContainer(), $transaction, $formData);
				
			$parameters = $builder->buildParameters();
			
			return Customweb_Util_Url::appendParameters(
					$this->getConfiguration()->getUrlWorldpay(),
					$parameters
			);
		}		
	}

	public function isAuthorizationMethodSupported(Customweb_Payment_Authorization_IOrderContext $orderContext){
		return true;
	}

	public function processAuthorization(Customweb_Payment_Authorization_ITransaction $transaction, array $parameters){
		/* @var $transaction Customweb_WorldPay_Authorization_Transaction */
		
		if ($transaction->isAuthorizationFailed() || $transaction->isAuthorized()) {
			return $this->finalizeAuthorizationRequest($transaction);
		} else {
			$transaction->setAuthorizationType("PP");
			if (!isset($parameters['callbackPW'])) {
				$transaction->setAuthorizationFailed(Customweb_I18n_Translation::__("No callbackPw was return. You may need to set the response password."));
				return $this->finalizeAuthorizationRequest($transaction);
			}
			
			if ($parameters['callbackPW'] != $this->getConfiguration()->getResponsePassword()) {
				$transaction->setAuthorizationFailed(Customweb_I18n_Translation::__("The response password is wrong."));
				return $this->finalizeAuthorizationRequest($transaction);
			}
				
			if (!isset($parameters['transStatus']) || $parameters['transStatus'] != 'Y') {
				$transaction->setAuthorizationFailed(Customweb_I18n_Translation::__("The transaction is cancelled by the customer."));
				return $this->finalizeAuthorizationRequest($transaction);
			}
			
			if($this->validateInformation($transaction, $parameters)) {
				$transaction->setPaymentId($parameters['transId']);
				$transaction->authorize(Customweb_I18n_Translation::__('Customer sucessfully returned from the WorldPay payment page.'));
				$this->handleCapturingCases($transaction);
				return $this->finalizeAuthorizationRequest($transaction);
			} else {
				$transaction->setAuthorizationFailed(new Customweb_Payment_Authorization_ErrorMessage(
					Customweb_I18n_Translation::__('Payment was not successful.'),
					Customweb_I18n_Translation::__('The information could not be validated')
				));
				return $this->finalizeAuthorizationRequest($transaction);
			}
		}
	}
	
	
	public function processXmlAuthorization(Customweb_Payment_Authorization_ITransaction $transaction, array $parameters) {
		$xmlAdapter = new Customweb_WorldPay_Authorization_PaymentPage_XmlAdapter($this->getConfiguration()->getConfigurationAdapter(), $this->getContainer());
		$xmlAdapter->processXmlRequestAuthorization($transaction, $parameters);
		return $this->finalizeAuthorizationRequest($transaction, $transaction->isAuthorized());
	}
	
	/**
	 * 
	 * @param Customweb_WorldPay_Authorization_Transaction $transaction
	 * @param unknown $parameters
	 * @return number
	 */
	protected function validateInformation(Customweb_WorldPay_Authorization_Transaction $transaction, $parameters) {


		if($parameters['authCurrency'] != $transaction->getTransactionContext()->getOrderContext()->getCurrencyCode()) {
			return false;
		}
		if($parameters['installation'] != $this->getConfiguration()->getInstId()) {
			return false;
		}
		
		return true;
	}
	

	public function finalizeAuthorizationRequest(Customweb_Payment_Authorization_ITransaction $transaction){
			if($transaction->isAuthorized()){
 				$url = $transaction->getSuccessUrl();
 			} else {
 				$url = $transaction->getFailedUrl();
 			}
 			$out = '<html>
 						<head>
 		 					<meta http-equiv="refresh" content="0; url=' . $url . '" />
 						</head>
						<body><div>'.
							Customweb_I18n_Translation::__("You will be redirected to the shop")
						.'</div></body>
					</html>';
		return $out;
		
	}

	public function isHeaderRedirectionSupported(Customweb_Payment_Authorization_ITransaction $transaction, array $formData){
		return true;
	}

	public function getParameters(Customweb_Payment_Authorization_ITransaction $transaction, array $formData){
		return array();
	}

	public function getFormActionUrl(Customweb_Payment_Authorization_ITransaction $transaction, array $formData){
		return $this->getRedirectionUrl($transaction, $formData);
	}
	
	
}
