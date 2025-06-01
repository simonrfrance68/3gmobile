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
 *
 */
class Customweb_WorldPay_Authorization_AbstractAdapter extends Customweb_WorldPay_AbstractAdapter {
	
	
	public function isDeferredCapturingSupported(Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext) {
		return $orderContext->getPaymentMethod()->existsPaymentMethodConfigurationValue('capturing');
	}
	
	protected function isCapturing(Customweb_WorldPay_Authorization_Transaction $transaction) {
		if ($transaction->getTransactionContext()->getCapturingMode() === null) {
			$capturingMode = $transaction->getCaptureSetting();
			if ($capturingMode == 'sale') {
				return true;
			}
			else {
				return false;
			}
		}
		else {
			if ($transaction->getTransactionContext()->getCapturingMode() == Customweb_Payment_Authorization_ITransactionContext::CAPTURING_MODE_DEFERRED) {
				return false;
			}
			else {
				return true;
			}
		}
	}
	
	/**
	 * Differentiation of cased due to mulitple possible settings on the shop-backend and on Worldpay-backend
	 * @param unknown $transaction
	 */
	protected function handleCapturingCases($transaction) {
		if(Customweb_WorldPay_Method_MethodHelper::isForcedByWorldpay($transaction)) {
			$transaction->capture();
		} else {
			if($this->isCapturing($transaction)) {
				
				$captureAdapter = $this->getContainer()->getBean('Customweb_WorldPay_BackendOperation_Adapter_CaptureAdapter');
				$captureAdapter->capture($transaction);
			}
		}
	}
	
	
}