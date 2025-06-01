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
class Customweb_WorldPay_Authorization_PaymentPage_XmlAdapter extends Customweb_WorldPay_AbstractAdapter {
	
	public function produceRedirectionUrl(Customweb_Payment_Authorization_ITransaction $transaction, array $formData){
		
		if (!($transaction instanceof Customweb_WorldPay_Authorization_Transaction)) {
			throw new Exception("The transaction must be of type Customweb_WorldPay_Authorization_Transaction.");
		}
		if($transaction->isAuthorizationFailed()){
			return $transaction->getFailedUrl();
		}
		$transaction->setPaymentId($this->getTransactionAppliedSchema($transaction, $this->getConfiguration()));
		$paymentMethod = $this->getContainer()->getPaymentMethodFromTransaction($transaction);
		$xmlRequest = $paymentMethod->getPaymentPageXmlRequest($this->getContainer(), $transaction, $formData);
					
		$url = null;		
		try{
			$handler = Customweb_WorldPay_Util::sendDefaultRequest(
					$xmlRequest,
					$this->getConfiguration()
					);
			if($handler->getStatusCode() != "200") {
				throw new Customweb_Payment_Exception_PaymentErrorException(
						new Customweb_Payment_Authorization_ErrorMessage(
								Customweb_I18n_Translation::__("Your payment was not successful, please contact the merchant."),
								Customweb_I18n_Translation::__("The XML response was not correct. Please Check your Worldpay XML Credentials")
						)
				);
			}
			$xmlArray = simplexml_load_string($handler->getBody());
			$url = $xmlArray->reply->orderStatus->reference;
			$transaction->setUpdateExecutionDate(Customweb_Core_DateTime::_()->addMinutes(10));
			
		}
		catch(Customweb_Payment_Exception_PaymentErrorException $e){
			$transaction->setAuthorizationFailed($e->getErrorMessage());
			$url = $transaction->getFailedUrl();
		}
		catch(Exception $e){
			$transaction->setAuthorizationFailed($e->getMessage());
			$url = $transaction->getFailedUrl();
		}
		return $url;
		
	}
	
	public function processXmlRequestAuthorization(Customweb_WorldPay_Authorization_Transaction $transaction, array $parameters) {
		//Set transaction as xml-Transaction
		$transaction->setAuthorizationType("XML");

		$xmlArray = Customweb_WorldPay_Authorization_Inquiry_Executor::performInquiry($transaction, $this->getConfiguration());
		
		try {
			Customweb_WorldPay_Method_MethodHelper::validateInformation($transaction, $xmlArray, $this->getConfiguration());
			$paymentAction = Customweb_WorldPay_Method_MethodHelper::finishBackend($transaction, $this->getConfiguration());
			if ($paymentAction) {
				$captureAdapter = $this->getContainer()->getBean('Customweb_WorldPay_BackendOperation_Adapter_CaptureAdapter');
				if($captureAdapter->directCapture($transaction)) {
					$transaction->capture();
				}
			}
		}
		catch(Customweb_Payment_Exception_PaymentErrorException $e) {
			$transaction->setAuthorizationFailed($e->getErrorMessage());
		}
		catch(Exception $e) {
			$transaction->setAuthorizationFailed($e->getMessage());
		}
	}

	public function isAnXmlRedirect(Customweb_Payment_Authorization_ITransaction $transaction) { 
		$flag = $this->getContainer()->getPaymentMethodFromTransaction($transaction)->requiresPaymentPageXmlRedirect();		
		if ($flag && !$this->getConfiguration()->isInvisibleXMLAccessEnabled()) {
			new Customweb_Payment_Authorization_ErrorMessage(
							Customweb_I18n_Translation::__('Payment was not successful.'),
							Customweb_I18n_Translation::__('Invisible XML would be needed to complete the payment.')
					);
		} else if ($flag && $this->getConfiguration()->isInvisibleXMLAccessEnabled()) {
			return true;
		} else {
			return false;
		}
		
	}
	
}