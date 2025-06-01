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

final class Customweb_WorldPay_Method_MethodHelper {

	private function __construct() {
		// prevent any instantiation of this class
	}
	
	public static function isForcedByWorldpay(Customweb_WorldPay_Authorization_Transaction $transaction) {
		if($transaction->getCaptureSetting() == "worldpay") {
			return true;
		} else {
			return false;
		}
	}

	public static function finishBackend(Customweb_WorldPay_Authorization_Transaction $transaction, Customweb_WorldPay_Configuration $configuration) {
		// Test if a direct capturing is necessary:
		if ($transaction->getTransactionContext()->getCapturingMode() === null) {
			$capturingMode = $transaction->getCaptureSetting();
			if ($capturingMode == 'sale') {
				$paymentAction = true;
			} else {
				$paymentAction = false;
			}
		} else {
			if ($transaction->getTransactionContext()->getCapturingMode() == Customweb_Payment_Authorization_ITransactionContext::CAPTURING_MODE_DEFERRED) {
				$paymentAction = false;
			} else {
				$paymentAction = true;
			}
		}
		return $paymentAction;
	}
	
	
	public static function validateInformation(Customweb_Payment_Authorization_ITransaction $transaction, $xmlArray, Customweb_WorldPay_Configuration $configuration){
		
		// Check if we got an error:
		if (isset($xmlArray->reply->orderStatus->error)) {
			throw new Customweb_Payment_Exception_PaymentErrorException(new Customweb_Payment_Authorization_ErrorMessage(
				Customweb_I18n_Translation::__('Payment was not successful.'),
				Customweb_I18n_Translation::__("XML request failed with error code: !code", array('!code' => (string)$xmlArray->reply->orderStatus->error->attributes()->code))
			));
		}
		
		if(!isset($xmlArray->reply->orderStatus->payment->lastEvent)) {
			throw new Customweb_Payment_Exception_PaymentErrorException(new Customweb_Payment_Authorization_ErrorMessage(
					Customweb_I18n_Translation::__('Payment was not successful.'),
					Customweb_I18n_Translation::__("The last event state was not returned.")
			));
		}
		
		if ($xmlArray->reply->orderStatus->payment->lastEvent != "AUTHORISED") {
			throw new Customweb_Payment_Exception_PaymentErrorException(new Customweb_Payment_Authorization_ErrorMessage(
				Customweb_I18n_Translation::__('Payment was not successful.'),
				Customweb_I18n_Translation::__("The payment was not authorized. The last event status was not authorized. It has the status '!status'.", array('!status' => (string)$xmlArray->reply->orderStatus->payment->lastEvent))
			));
		}
		
		$currencyCode = null;
		$amount = 0;
		if (isset($xmlArray->reply->orderStatus->payment->amount)) {
			$currencyCode = $xmlArray->reply->orderStatus->payment->amount->attributes()->currencyCode;
			$amount = $xmlArray->reply->orderStatus->payment->amount->attributes()->value;
		}
		
		$merchantCode = $xmlArray->attributes()->merchantCode;
		$shopAmountFormatted = Customweb_Util_Currency::formatAmount($transaction->getTransactionContext()->getOrderContext()->getOrderAmountInDecimals(), $transaction->getTransactionContext()->getOrderContext()->getCurrencyCode(), '', '');
		$responseAmountFormatted = "$amount";
		
		if ($shopAmountFormatted != $responseAmountFormatted) {
			throw new Customweb_Payment_Exception_PaymentErrorException(new Customweb_Payment_Authorization_ErrorMessage(
				Customweb_I18n_Translation::__('Payment was not successful.'),
				Customweb_I18n_Translation::__('The payment was not authorized. Validation failed (Amount).')
			));
		}
		if ($currencyCode != $transaction->getTransactionContext()->getOrderContext()->getCurrencyCode()) {
			throw new Customweb_Payment_Exception_PaymentErrorException(new Customweb_Payment_Authorization_ErrorMessage(
				Customweb_I18n_Translation::__('Payment was not successful.'),
				Customweb_I18n_Translation::__('The payment was not authorized. Validation failed (Currency).')
			));
		}
		try {
			$secondMerchantCode = $configuration->getSecondMerchantCode();
		}
		catch(Exception $e) {
			// Ignore
		}
		
		if (($merchantCode != $configuration->getMerchantCode()) && ($merchantCode != $secondMerchantCode)) {
			throw new Customweb_Payment_Exception_PaymentErrorException(new Customweb_Payment_Authorization_ErrorMessage(
				Customweb_I18n_Translation::__('Payment was not successful.'),
				Customweb_I18n_Translation::__('The payment was not authorized. Validation failed (Merchant Code).')
			));
		}
		return true;
	}
		
}
