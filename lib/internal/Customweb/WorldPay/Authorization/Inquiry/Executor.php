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
final class Customweb_WorldPay_Authorization_Inquiry_Executor { 
	

	private function __construct() {
		// prevent any instantiation of this class
	}

	/**
	 * 
	 * @param Customweb_Payment_Authorization_ITransaction $transaction
	 * @param Customweb_WorldPay_Configuration $configuration
	 * @throws Customweb_Payment_Exception_PaymentErrorException
	 * @return void|SimpleXMLElement
	 */
	public static function performInquiry(Customweb_Payment_Authorization_ITransaction $transaction, Customweb_WorldPay_Configuration $configuration) {
		
		$handler = Customweb_WorldPay_Util::sendDefaultRequest(
			self::createInquiry($transaction, $configuration), 
			$configuration
		);
			
		//The XMLHttpRequest was flawed:
		try{
			if($handler->getStatusCode() != "200") {
				throw new Customweb_Payment_Exception_PaymentErrorException(
						new Customweb_Payment_Authorization_ErrorMessage(
								Customweb_I18n_Translation::__("Your payment was not successful, please contact the merchant."),
								Customweb_I18n_Translation::__("The XML response was not correct. Please Check your Worldpay XML Credentials")
						)
				);
			}
		}
		catch(Exception $e){
			$transaction->setAuthorizationFailed($e->getErrorMessage());
			return;
		}
			
		return simplexml_load_string($handler->getBody());
	}
	
	private static function createInquiry(Customweb_Payment_Authorization_ITransaction $transaction, Customweb_WorldPay_Configuration $configuration) {
		
		$orderCode = $transaction->getPaymentId();
		
		$query = '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE paymentService PUBLIC "-//WorldPay//DTD WorldPay PaymentService v1//EN" "http://dtd.worldpay.com/paymentService_v1.dtd">
		<paymentService version="1.4" merchantCode="'. $configuration->getMerchantCode().'">
			<inquiry>
				<orderInquiry orderCode="'. $orderCode .'"/>
			</inquiry>
		</paymentService>';
		
		return $query;
		
	}
}