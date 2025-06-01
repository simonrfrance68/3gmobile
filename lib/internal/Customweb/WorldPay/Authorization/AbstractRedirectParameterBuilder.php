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
 * @author Thomas Brenner
 */
class Customweb_WorldPay_Authorization_AbstractRedirectParameterBuilder extends Customweb_WorldPay_Authorization_AbstractParameterBuilder {
	
	private $formData;
	
	public function __construct(Customweb_DependencyInjection_IContainer $container, Customweb_WorldPay_Authorization_Transaction $transaction, array $formData) {
		parent::__construct($container, $transaction);
		$this->formData = $formData;
	}
	
	
	public function buildParameters() {
		$parameters = array_merge(
			$this->getBaseParameters(),
			$this->getAddressParameters(),
			$this->getAmountParameters()
		);
		
		$md5String = $this->getConfiguration()->getMD5SecretKey().
		":".$parameters['instId'].
		":".$parameters['cartId'].
		":".$parameters['currency'].
		":".$parameters['MC_callback'].
		":".$parameters['amount'];
		
		$parameters['signature'] = md5($md5String);
	
		return $parameters;
	}
	
	protected function getAmountParameters() {
		$amount = Customweb_Util_Currency::formatAmount($this->getOrderContext()->getOrderAmountInDecimals(), $this->getOrderContext()->getCurrencyCode(), '.', '');
		return array(
			'amount' => $amount,
		);
	}
	
	protected function getAddressParameters() {
	
		$parameters = array(
			'name' 	=> Customweb_Util_String::substrUtf8($this->getOrderContext()->getBillingAddress()->getFirstName() . " " . $this->getOrderContext()->getBillingAddress()->getLastName(), 0, 40),
			'address1' 		=> Customweb_Util_String::substrUtf8($this->getOrderContext()->getBillingAddress()->getStreet(), 0 , 84),
			'town' 			=> Customweb_Util_String::substrUtf8($this->getOrderContext()->getBillingAddress()->getCity(), 0 ,30),
			'postcode' 			=> Customweb_Util_String::substrUtf8($this->getOrderContext()->getBillingAddress()->getPostCode(), 0, 12),
			'country' 		=> Customweb_Util_String::substrUtf8($this->getOrderContext()->getBillingAddress()->getCountryIsoCode(), 0 ,2),
			'email'			=> Customweb_Util_String::substrUtf8($this->getOrderContext()->getBillingAddress()->getEMailAddress(), 0, 80)
		);
	
		return $parameters;
	}
	
	protected function getBaseParameters() {
		
		$paymentMethodClass = $this->getContainer()->getBean('Customweb_WorldPay_Method_Factory')->getPaymentMethod($this->getTransaction()->getTransactionContext()->getOrderContext()->getPaymentMethod(), $this->getTransaction()->getAuthorizationMethod());
		$paymentMethod = $paymentMethodClass->getPaymentMethodIdentifier($this->getTransaction(), $this->formData);
		$this->getTransaction()->setPaymentId($this->getTransactionAppliedSchema());
	
		$parameters = array(
			'currency' => $this->getOrderContext()->getCurrencyCode(),
			'cartId' => $this->getTransaction()->getPaymentId(),
			'accId1' => $this->getConfiguration()->getMerchantCode(),
			'instId' => $this->getConfiguration()->getInstId(),
			'paymentType' => $paymentMethod,
			'lang' 	=> Customweb_WorldPay_Util::getCleanLanguageCode($this->getOrderContext()->getLanguage()),
			'noLanguageMenu' => 'true,',
		);
	
		if(!Customweb_WorldPay_Method_MethodHelper::isForcedByWorldpay($this->getTransaction())) {
			$parameters['authMode'] = "E";
		}
		
		$parameters['MC_callback'] = Customweb_WorldPay_Util::checkUrlSize($this->getContainer()->getProcessAuthorizationUrl($this->getTransaction()->getExternalTransactionId()),255);
		
		if($this->getConfiguration()->isTestMode()) {
			$parameters['testMode'] = 100;
		}
		
		return $parameters;
	}
	
}
