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
 * @Method(paymentMethods={'IDeal', 'Giropay', 'Alipay', 'Bcmc', 'Paysafecard', 'PayU', 'Poli', 'Postepay', 'Przelewy24', 'Qiwi', 'SafetyPay', 'Skrill', 'Sofortueberweisung', 'TrustPay', 'Teleingreso'})
 *
 */
class Customweb_WorldPay_Method_RedirectionMethod extends Customweb_WorldPay_Method_DefaultMethod {

	public function getFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $authorizationMethod, $isMoto, $customerPaymentContext) {
		$elements = array();
		return $elements;
	}
		

	public function createXMLString($parameters, Customweb_Payment_Authorization_ITransaction $transaction) {
		/* @var $transaction Customweb_WorldPay_Authorization_Transaction */
		$xmlString =
					'<successURL>' . $transaction->getSuccessUrl() . 	'</successURL>
					 <failureURL>' . $transaction->getFailedUrl() . 	'</failureURL>
					 <cancelURL>'  . $transaction->getFailedUrl() . 	'</cancelURL>';
		return $xmlString;
	}		
	
}