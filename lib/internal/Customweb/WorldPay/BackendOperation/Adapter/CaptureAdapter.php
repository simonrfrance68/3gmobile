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
class Customweb_WorldPay_BackendOperation_Adapter_CaptureAdapter extends Customweb_WorldPay_AbstractAdapter implements 
		Customweb_Payment_BackendOperation_Adapter_Service_ICapture {

	public function capture(Customweb_Payment_Authorization_ITransaction $transaction){
		/* @var $transaction Customweb_WorldPay_Authorization_Transaction */
		$items = $transaction->getUncapturedLineItems();
		$this->partialCapture($transaction, $items, true);
	}

	public function partialCapture(Customweb_Payment_Authorization_ITransaction $transaction, $items, $close){
		
		/* @var $transaction Customweb_WorldPay_Authorization_Transaction */
		$amount = Customweb_Util_Invoice::getTotalAmountIncludingTax($items);
		
		$allItems = $transaction->getUncapturedLineItems();
		$totalAmount = Customweb_Util_Invoice::getTotalAmountIncludingTax($allItems);
		
		if ($totalAmount <= $amount) {
			$close = true;
		}
		
		$transaction->partialCaptureByLineItemsDry($items, $close);
		
		//Check wheter Multi-Capturing is enabled and necessary, then execute corresponding function
		if (!($this->getConfiguration()->isInvisibleXMLAccessEnabled())) {
			$flag = $this->processFullCaptureRequest($transaction, $amount);
		}
		else {
			$flag = $this->processPartialCaptureRequest($transaction, $amount);
		}
		
		if (!$flag) {
			throw new Exception(Customweb_I18n_Translation::__('The transaction could not be captured.'));
		}
		else {
			$item = $transaction->partialCaptureByLineItems($items, $close);
		}
	}

	public function directCapture(Customweb_Payment_Authorization_ITransaction $transaction){
		$items = $transaction->getUncapturedLineItems();
		if (!$this->processPartialCaptureRequest($transaction, Customweb_Util_Invoice::getTotalAmountIncludingTax($items))) {
			throw new Exception(Customweb_I18n_Translation::__('The transaction could not be captured.'));
		}
		else {
			return true;
		}
	}

	protected function processPartialCaptureRequest(Customweb_Payment_Authorization_ITransaction $transaction, $amount){
		$formattedAmount = Customweb_Util_Currency::formatAmount(
				$amount,
				$transaction->getTransactionContext()->getOrderContext()->getCurrencyCode(), '', '') ;
		$handler = Customweb_WorldPay_Util::sendDefaultRequest($this->buildCaptureXML($transaction, $formattedAmount), $this->getConfiguration());
		
		$xmlArray = simplexml_load_string($handler->getBody());
		foreach ($xmlArray->reply[0]->ok[0]->captureReceived[0]->amount[0]->attributes() as $key => $value) {
			if ($key === 'value') {
				$rValue = $value;
			}
		}
		if($xmlArray->reply[0]->ok[0] !== null 
				&& $rValue == 
					$formattedAmount)
		{
			return true;
		}
		else 
		{
			return false;
		}
	}

	/**
	 *
	 * @param Customweb_Payment_Authorization_ITransaction $transaction
	 * @param $amount
	 * @param $completion
	 * @return the parameters of the HTTP-Request to capture the authorization
	 */
	protected function processFullCaptureRequest(Customweb_Payment_Authorization_ITransaction $transaction, $amount){
		$request = new Customweb_Http_Request($this->getConfiguration()->getRemoteUrlWorldpay());
		$request->setBody($this->buildParameters($transaction, $amount));
		$request->setMethod("POST");
		$handler = new Customweb_Http_Response();
		$handler = $request->send();
		
		$parameters = array();
		$parameters = explode(",", $handler->getBody());
		
		if ($parameters['0'] == "A") {
			return true;
		}
		else {
			return false;
		}
	}

	protected function buildParameters(Customweb_Payment_Authorization_ITransaction $transaction, $amount){
		$parameters = array(
			'transId' => $transaction->getPaymentId(),
			'authMode' => "O",
			'op' => 'postAuth-full',
			'authPW' => $this->getConfiguration()->getAuthPW(),
			'instId' => $this->getConfiguration()->getRemoteInstId() 
		);
		
		if ($this->getConfiguration()->isTestMode()) {
			$parameters['testMode'] = "100";
		}
		
		return $parameters;
	}

	protected function buildCaptureXML(Customweb_Payment_Authorization_ITransaction $transaction, $formattedAmount){
		$xml = '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE paymentService PUBLIC "-//WorldPay//DTD WorldPay PaymentService v1//EN" "http://dtd.worldpay.com/paymentService_v1.dtd">';
		
		$xml .= '<paymentService merchantCode="' . $this->getConfiguration()->getMerchantCode() . '" version="1.4"> ';
		$xml .= '<modify>';
		$xml .= '<orderModification orderCode="' . $transaction->getPaymentId() . '">';
		$xml .= '<capture>';
		$xml .= '<amount value="' . $formattedAmount . '" currencyCode="' .
				 $transaction->getTransactionContext()->getOrderContext()->getCurrencyCode() . '" exponent="2" ' . 'debitCreditIndicator="credit"/>';
		$xml .= '</capture>';
		$xml .= '</orderModification></modify></paymentService>';
		
		return $xml;
	}
}