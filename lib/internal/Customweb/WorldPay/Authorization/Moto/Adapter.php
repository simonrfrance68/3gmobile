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
class Customweb_WorldPay_Authorization_Moto_Adapter extends Customweb_WorldPay_Authorization_AbstractAdapter implements Customweb_Payment_Authorization_Moto_IAdapter {
	
	private $cache = array();
	
	public function getAuthorizationMethodName(){
		return self::AUTHORIZATION_METHOD_NAME;
	}
	
	public function getAdapterPriority() {
		return 900;
	}
	
	public function isAuthorizationMethodSupported(Customweb_Payment_Authorization_IOrderContext $orderContext){
		return true;
	}
	
	public function validateTransaction(Customweb_Payment_Authorization_ITransaction $transaction) {
		return true;
	}
	
	public function createTransaction(Customweb_Payment_Authorization_Moto_ITransactionContext $transactionContext, $failedTransaction){
		$transaction = new Customweb_WorldPay_Authorization_Transaction($transactionContext);
		$transaction->setAuthorizationMethod(self::AUTHORIZATION_METHOD_NAME);
		$transaction->setLiveTransaction(!$this->getConfiguration()->isTestMode());
		return $transaction;
	}
	
	public function getParameters(Customweb_Payment_Authorization_ITransaction $transaction) {
		if (!isset($this->cache[$transaction->getExternalTransactionId()])) {
			$this->cache[$transaction->getExternalTransactionId()] = array();
			$builder = new Customweb_WorldPay_Authorization_Moto_ParameterBuilder($this->getContainer(), $transaction, array());
			try {
				$this->cache[$transaction->getExternalTransactionId()] = $builder->buildParameters();
			}
			catch (Customweb_Payment_Exception_PaymentErrorException $e) {
				$transaction->setAuthorizationFailed($e->getErrorMessage());
			}
			catch (Exception $e){
				$transaction->setAuthorizationFailed($e->getErrorMessage());
				
			}
		}
		return $this->cache[$transaction->getExternalTransactionId()];
		
	}
	
	public function getFormActionUrl(Customweb_Payment_Authorization_ITransaction $transaction){
		$this->getParameters($transaction);
		if($transaction->isAuthorizationFailed()){	
			return Customweb_Util_Url::appendParameters($transaction->getTransactionContext()->getBackendFailedUrl(), $transaction->getTransactionContext()->getCustomParameters());
		}
		
		return $this->getConfiguration()->getMoToUrl();
	}
	
	public function getVisibleFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext,
			$aliasTransaction,
			$failedTransaction,$paymentCustomerContext) {
		return $this->getContainer()->getBean('Customweb_WorldPay_Method_Factory')->getPaymentMethod($orderContext->getPaymentMethod(), self::AUTHORIZATION_METHOD_NAME)
		->getFormFields($orderContext, $aliasTransaction, $failedTransaction, self::AUTHORIZATION_METHOD_NAME, false, $paymentCustomerContext);
	}
	

	public function processAuthorization(Customweb_Payment_Authorization_ITransaction $transaction, array $parameters){
		/* @var $transaction Customweb_WorldPay_Authorization_Transaction */

		if ($transaction->isAuthorizationFailed() || $transaction->isAuthorized()) {
			return $this->finalizeAuthorizationRequest($transaction);
		}
		
		if (!isset($parameters['callbackPW'])) {
			$transaction->setAuthorizationFailed(Customweb_I18n_Translation::__("No callbackPw was return. You may need to set the response password."));
			return $this->finalizeAuthorizationRequest($transaction);
		}
		
		if ($parameters['callbackPW'] != $this->getConfiguration()->getResponsePassword()) {
			$transaction->setAuthorizationFailed(Customweb_I18n_Translation::__("The response password is wrong."));
			return $this->finalizeAuthorizationRequest($transaction);
		}
		if (!isset($parameters['transStatus']) || $parameters['transStatus'] != 'Y') {
			$transaction->setAuthorizationFailed(Customweb_I18n_Translation::__("The transaction is cancelled."));
			return $this->finalizeAuthorizationRequest($transaction);
		}
		
		if(!$this->validateInformation($transaction, $parameters)) {
			$transaction->setAuthorizationFailed(
				new Customweb_Payment_Authorization_ErrorMessage(
					Customweb_I18n_Translation::__('Payment was not successful.'),
					Customweb_I18n_Translation::__('The information could not be validated, Moto failed')
				)
			);
		} else {
			$transaction->authorize(Customweb_I18n_Translation::__('Moto was successfully completed with  WorldPay.'));
			$transaction->setAuthorizationType('PP');
			$transaction->setPaymentId($parameters['transId']);
			$this->handleCapturingCases($transaction);
		}	
		return $this->finalizeAuthorizationRequest($transaction);
	}
	

	public function finalizeAuthorizationRequest(Customweb_Payment_Authorization_ITransaction $transaction){
			if($transaction->isAuthorized()){
 				$url = Customweb_Util_Url::appendParameters($transaction->getTransactionContext()->getBackendSuccessUrl(), $transaction->getTransactionContext()->getCustomParameters());
 			} else {
 				$url = Customweb_Util_Url::appendParameters($transaction->getTransactionContext()->getBackendFailedUrl(), $transaction->getTransactionContext()->getCustomParameters());
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
	
	protected function validateInformation(Customweb_WorldPay_Authorization_Transaction $transaction, $parameters) {
		
		if($parameters['authCurrency'] != $transaction->getTransactionContext()->getOrderContext()->getCurrencyCode()) {
			return false;
		}
		return true;
		
	}
	
}