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
 * @Method(paymentMethods={'Paypal'})
 *
 */
class Customweb_WorldPay_Method_RedirectionMethod extends Customweb_WorldPay_Method_RedirectionMethod {

	public function requiresPaymentPageXmlRedirect() {
		return true;
	}
	
	public function getPaymentPageXmlRequest(Customweb_WorldPay_Container $container, Customweb_WorldPay_Authorization_Transaction $transaction, array $formData) {
			
		$amount = Customweb_Util_Currency::formatAmount($transaction->getTransactionContext()->getOrderContext()->getOrderAmountInDecimals(), $transaction->getTransactionContext()->getOrderContext()->getCurrencyCode(), '', '');
		$xml='<?xml version="1.0" encoding="UTF-8"?> <!DOCTYPE paymentService PUBLIC "-//WorldPay//DTD WorldPay PaymentService v1//EN" "http://dtd.worldpay.com/paymentService_v1.dtd">
				<paymentService version="1.4" merchantCode="' . $this->getConfiguration()->getMerchantCode() . '">
				    <submit>
				        <order orderCode="' . $transaction->getPaymentId() . '">
				              <description>' . 'Details from order: ' . $transaction->getPaymentId() . '</description>
				              <amount value="' . $amount .'" currencyCode="' . $transaction->getTransactionContext()->getOrderContext()->getCurrencyCode() .'" exponent="2"/>
				              <paymentDetails>
				              		<PAYPAL-EXPRESS>
				              			<successURL>' . $container->getXmlPaymentPageNotificationUrl($this->getTransaction()->getExternalTransactionId())  .  '</successURL>
				              			<failureURL>' . $transaction->getFailedUrl()   .  '</failureURL>
				              			<cancelURL>' . 	$transaction->getFailedUrl()   .  '</cancelURL>
						            </PAYPAL-EXPRESS>
				                   <session shopperIPAddress="' . Customweb_Core_Http_ContextRequest::getClientIPAddressV6() . '" id="'. md5($transaction->getPaymentId()) . '" />' .
						                   '</paymentDetails>
				              <shopper>
				                  <shopperEmailAddress>'. $transaction->getTransactionContext()->getOrderContext()->getCustomerEMailAddress() .'</shopperEmailAddress>
				                  <browser>
				                       <acceptHeader>text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8</acceptHeader>
				                       <userAgentHeader>' . $_SERVER['HTTP_USER_AGENT'] .'</userAgentHeader>
				                   </browser>
				               </shopper>
				        </order>
				   </submit>
				</paymentService>';
		
		$xml = str_replace("&", "&amp;", $xml);
		
		return $xml;
		
	}
	
}