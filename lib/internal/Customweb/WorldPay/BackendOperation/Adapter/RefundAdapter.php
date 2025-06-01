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
class Customweb_WorldPay_BackendOperation_Adapter_RefundAdapter extends Customweb_WorldPay_AbstractAdapter
	 implements Customweb_Payment_BackendOperation_Adapter_Service_IRefund {

	public function refund(Customweb_Payment_Authorization_ITransaction $transaction){
		$items = $transaction->getTransactionContext()->getOrderContext()->getInvoiceItems();
		return $this->partialRefund($transaction, $items, true);
	}

	public function partialRefund(Customweb_Payment_Authorization_ITransaction $transaction, $items, $close){
		
		if (!($transaction instanceof Customweb_WorldPay_Authorization_Transaction)) {
			throw new Customweb_Core_Exception_CastException('Customweb_WorldPay_Authorization_Transaction');
		}
		
		$transaction->refundByLineItemsDry($items, $close);
		
		$amount = Customweb_Util_Invoice::getTotalAmountIncludingTax($items);
		$totalNotRefundedAmount = $transaction->getRefundableAmount();

		
		//In case of an ELV, a separate kind of Refunding is necessary:
		if($transaction->getTransactionContext()->getOrderContext()->getPaymentMethod()->getPaymentMethodName() == "intercarddirectdebits") {
			$paymentMethodClass = $this->getContainer()->getBean('Customweb_WorldPay_Method_Factory')->getPaymentMethod($transaction->getTransactionContext()->getOrderContext()->getPaymentMethod(), self::AUTHORIZATION_METHOD_NAME);
			return $paymentMethodClass->doRefund ($transaction, $this->getConfiguration(), $amount);
		}
		
		if(strtoupper($transaction->getAuthorizationType()) == "XML") {
			$this->doXmlRefund($transaction, $this->getConfiguration(), $amount, $close);
		} else if (strtoupper($transaction->getAuthorizationType()) == 'PP') {
			$this->doHttpRefund($transaction, $amount, $totalNotRefundedAmount, $close);
		} else {
			throw new Customweb_Payment_Exception_PaymentErrorException(
					new Customweb_Payment_Authorization_ErrorMessage(
							Customweb_I18n_Translation::__("The refund could not be conducted because no authorization type was set on the transaction."),
							Customweb_I18n_Translation::__("The refund could not be conducted because no authorization type was set on the transaction.")
					)
			);
		}
		

	}
	
	protected function doHttpRefund(Customweb_WorldPay_Authorization_Transaction $transaction, $amount, $totalNotRefundedAmount, $close) {
		$additionalInformation = array();
		//Additional Information for partial Refund
		if($amount < $totalNotRefundedAmount) {
			$additionalInformation['op'] = 'refund-partial';
			$additionalInformation['amount'] = $amount;
			$additionalInformation['currency'] = $transaction->getTransactionContext()->getOrderContext()->getCurrencyCode();
		} else {
			$additionalInformation['op'] = 'refund-full';
		}
			
		$transaction->refundDry($amount, $close);
		$parameters = $this->doRefund($additionalInformation, $close, $transaction, $amount);
		if($parameters[0] === 'A' && $parameters[1] === $transaction->getPaymentId()) {
			$transaction->refund($amount, $close, "The refund could successfully be conducted.");
			return true;
		} else {
			throw new Exception('Refunding failed.');
		}
	}

	//Processing the HTTP-Request to refund
	public function doRefund($additionalInformation, $close, Customweb_WorldPay_Authorization_Transaction $transaction, $amount){
	
		$standardInfos = array(
			'authPW' 		=> $this->getConfiguration()->getAuthPW(),
			'instId'		=> $this->getConfiguration()->getRemoteInstId(),
			'transId'		=> $transaction->getPaymentId(),
		);
	
		if($this->getConfiguration()->isTestMode()) {
			$standardInfos['testMode'] = "100";
		}
		$body = array_merge($additionalInformation, $standardInfos);
	
		$request = new Customweb_Http_Request($this->getConfiguration()->getRemoteUrlWorldpay());
		$request->setMethod("POST");
		$request->setBody($body);
		$handler = new Customweb_Http_Response();
		$handler = $request->send();
	
		if (substr($handler->getStatusCode(), 0, 1) != '2' ) {
			throw new Exception(Customweb_I18n_Translation::__("Refund failed because the remote server does not response with a 200 status code. The HTTP status code is: !code", array(
				'!code' => $handler->getStatusCode(),
			)));
		}
		
	
		$parameters = explode(',', $handler->getBody());
	
		return $parameters;
	}
	
	public function doXmlRefund(Customweb_Payment_Authorization_ITransaction $transaction, Customweb_WorldPay_Configuration $configuration, $amount, $close){
		$modifiedAmount = Customweb_Util_Currency::formatAmount($amount, $transaction->getTransactionContext()->getOrderContext()->getCurrencyCode(), '','');
		$merchantCode = $configuration->getMerchantCode();
		if($transaction->getAuthorizationMethod() == Customweb_Payment_Authorization_Recurring_IAdapter::AUTHORIZATION_METHOD_NAME) {
			$merchantCode = $configuration->getSecondMerchantCode();
		}
		$queryString =
		'<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE paymentService PUBLIC "-//WorldPay//DTD WorldPay PaymentService v1//EN" "http://dtd.worldpay.com/paymentService_v1.dtd">
				<paymentService merchantCode="' . $merchantCode . '" version="1.4">
						<modify>
							<orderModification orderCode="' . $transaction->getPaymentId() . '">
								<refund>
									<amount value="'. $modifiedAmount .  '" currencyCode="' . $transaction->getTransactionContext()->getOrderContext()->getCurrencyCode() . '" exponent="2" debitCreditIndicator="credit" />
								</refund>
							</orderModification>
						</modify>
					</paymentService>' ;
		$handler = Customweb_WorldPay_Util::sendRequest($queryString, $merchantCode, $this->getConfiguration()->getInvisibleXMLPassword(), $this->getConfiguration());
		
		if (substr($handler->getStatusCode(), 0, 1) != '2' ) {
			throw new Exception(Customweb_I18n_Translation::__("Refund failed because the remote server does not response with a 200 status code. The HTTP status code is: !code", array(
				'!code' => $handler->getStatusCode(),
			)));
		}
		
		$xmlArray = simplexml_load_string($handler->getBody());
		
		// Check if we got an error:
		if (isset($xmlArray->reply->error)) {
			throw new Exception(Customweb_I18n_Translation::__('Refunding failed. Error: !error', array('!code' => (string)$xmlArray->reply->orderStatus->error->attributes()->code)));
		}
		$orderCodeAttributes = $xmlArray->reply->ok->refundReceived->attributes();
		$orderCode = $orderCodeAttributes[0][0];
		
		if($orderCode == $transaction->getPaymentId()) {
			$transaction->refund($amount, $close, "The refund could successfully be conducted.");
			return true;
		} else {
			throw new Exception('Refunding failed.');
		}
	}
}