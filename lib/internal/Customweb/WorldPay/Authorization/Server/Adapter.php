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
 * @Bean
 *
 */
class Customweb_WorldPay_Authorization_Server_Adapter extends Customweb_WorldPay_Authorization_AbstractAdapter implements 
		Customweb_Payment_Authorization_Server_IAdapter {

	public function getAuthorizationMethodName(){
		return self::AUTHORIZATION_METHOD_NAME;
	}

	public function getAdapterPriority(){
		return 200;
	}

	public function createTransaction(Customweb_Payment_Authorization_Server_ITransactionContext $transactionContext, $failedTransaction){
		$transaction = new Customweb_WorldPay_Authorization_Transaction($transactionContext);
		$transaction->setAuthorizationMethod(self::AUTHORIZATION_METHOD_NAME);
		$transaction->setLiveTransaction(!$this->getConfiguration()->isTestMode());
		$transaction->setAuthorizationType("XML");
		return $transaction;
	}

	public function isPaymentMethodSupportingRecurring(Customweb_Payment_Authorization_IPaymentMethod $paymentMethod){
		return true;
	}

	public function getVisibleFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $customerPaymentContext){
		return $this->getContainer()->getBean('Customweb_WorldPay_Method_Factory')->getPaymentMethod($orderContext->getPaymentMethod(), 
				self::AUTHORIZATION_METHOD_NAME)->getFormFields($orderContext, $aliasTransaction, $failedTransaction, self::AUTHORIZATION_METHOD_NAME, 
				false, $customerPaymentContext);
	}

	public function isAuthorizationMethodSupported(Customweb_Payment_Authorization_IOrderContext $orderContext){
		return true;
	}

	public function processAuthorization(Customweb_Payment_Authorization_ITransaction $transaction, array $parameters){
		try {
			$transaction->setPaymentId($this->getTransactionAppliedSchema($transaction, $this->getConfiguration()));
			$paymentMethodClass = $this->getContainer()->getPaymentMethodFromTransaction($transaction);
			return $paymentMethodClass->processAuthorization($transaction, $parameters, $this->getContainer());
		}
		catch (Customweb_Payment_Exception_PaymentErrorException $e) {
			$transaction->setAuthorizationFailed($e->getErrorMessage());
		}
		catch (Exception $e) {
			$transaction->setAuthorizationFailed($e->getMessage());
		}
		
		return $paymentMethodClass->finalizeAuthorizationRequest($transaction, array(
			'breakout' => 'true' 
		));
	}
}