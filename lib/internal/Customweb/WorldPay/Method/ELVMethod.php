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
 * @Method(paymentMethods={'DirectDebits' })
 *
 */
class Customweb_WorldPay_Method_ELVMethod extends Customweb_WorldPay_Method_DefaultMethod {

	public function getFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $authorizationMethod, $isMoto, $customerPaymentContext) {
		$elements = array();
		
		if (Customweb_Payment_Authorization_Server_IAdapter::AUTHORIZATION_METHOD_NAME == $authorizationMethod) {
		
			$cardHolder = $orderContext->getBillingFirstName() . ' ' . $orderContext->getBillingLastName();
		
			$elements[] = Customweb_Form_ElementFactory::getAccountOwnerNameElement("account_owner", $cardHolder);
			$elements[] = Customweb_Form_ElementFactory::getIbanNumberElement("iban_number");
			$elements[] = Customweb_Form_ElementFactory::getBankNameElement('bank_name');
			$elements[] = Customweb_Form_ElementFactory::getBankLocationElement('bank_location');
		}
		return $elements;
	}

	public function createXMLString($parameters, Customweb_Payment_Authorization_ITransaction $transaction) {
		$xmlString =
			'<iban>' . $parameters['iban_number'] . '</iban>
				<accountHolderName>' . $parameters['account_owner'] .'</accountHolderName>
				<bankName>' . $parameters['bank_name'] . '</bankName>
			<bankLocation>' . $parameters['bank_location'] . '</bankLocation>
			<cardAddress>
				<address>
					<street>' . $transaction->getTransactionContext()->getOrderContext()->getBillingAddress()->getStreet().'</street>
					<postalCode>'. $transaction->getTransactionContext()->getOrderContext()->getBillingAddress()->getPostCode() .'</postalCode>
					<countryCode>' . $transaction->getTransactionContext()->getOrderContext()->getBillingAddress()->getCountryIsoCode().'</countryCode>
				</address>
			</cardAddress> ';
		
		$transaction->setIbanNumber($parameters['iban_number']);	
		return $xmlString;
	}
		
	public function doRefund(Customweb_Payment_Authorization_ITransaction $transaction, Customweb_WorldPay_Configuration $configuration, $amount){
		$modifiedAmount = Customweb_Util_Currency::formatAmount($amount, $transaction->getTransactionContext()->getOrderContext()->getCurrencyCode(), '','');
		$queryString = 
				'<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE paymentService PUBLIC "-//WorldPay//DTD WorldPay PaymentService v1//EN" "http://dtd.worldpay.com/paymentService_v1.dtd">
					<paymentService merchantCode="' . $configuration->getMerchantCode() . '" version="1.4">
							<modify>
								<orderModification orderCode="' . $transaction->getPaymentId() . '">
									<refund>
										<amount value="'. $modifiedAmount .  '" currencyCode="' . $transaction->getTransactionContext()->getOrderContext()->getCurrencyCode() . '" exponent="2" debitCreditIndicator="credit" />
										<iban>' . $transaction->getIbanNumber() .  '</iban>
									</refund>
								</orderModification>
							</modify>
						</paymentService>' ;
		$handler = Customweb_WorldPay_Util::sendDefaultRequest($queryString, $this->getConfiguration());
		
		if (substr($handler->getStatusCode(), 0, 1) != '2' ) {
			throw new Exception(Customweb_I18n_Translation::__("Refund failed because the remote server does not response with a 200 status code. The HTTP status code is: !code", array(
				'!code' => $handler->getStatusCode(),
			)));
		}
		
		$xmlArray = simplexml_load_string($handler->getBody());
		
		return $xmlArray;
	}		
	
}