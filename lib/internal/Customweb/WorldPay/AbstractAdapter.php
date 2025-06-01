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



class Customweb_WorldPay_AbstractAdapter
{
	/**
	 * Configuration object.
	 *
	 * @var Customweb_WorldPay_Configuration
	 */
	private $configuration;
	private $container;
	
	public function __construct(Customweb_Payment_IConfigurationAdapter $configuration, Customweb_DependencyInjection_IContainer $container) {
		$this->configuration = new Customweb_WorldPay_Configuration($configuration);
		$this->container = new Customweb_WorldPay_Container($container);
	}
	
	public function getContainer() {
		return $this->container;
	}
	
	public function getConfiguration() {
		return $this->configuration;
	}
	
	public function setConfiguration(Customweb_WorldPay_Configuration $configuration) {
		$this->configuration = $configuration;
	}
	
	
	public function validate(Customweb_Payment_Authorization_IOrderContext $orderContext,
			Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext, array $formData) {
	}
	
	public function preValidate(Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext) {
	}
	
	
	protected function validateCustomParameters(Customweb_Payment_Authorization_ITransaction $transaction, array $parameters){
		$customParametersBefore = $transaction->getTransactionContext()->getCustomParameters();
		foreach($customParametersBefore as $key => $value){
			if(!isset($parameters[$key])){
				return false;
			}
			if($parameters[$key] != $value){
				return false;
			}
		}
		return true;
	}
	
	/**
	 * @return string
	 */
	public final function getTransactionAppliedSchema(Customweb_Payment_Authorization_ITransaction $transaction, Customweb_WorldPay_Configuration $configuration)
	{
		$schema = $configuration->getOrderIdSchema();
		$id = $transaction->getExternalTransactionId();
	
		return Customweb_Payment_Util::applyOrderSchema($schema, $id, 64);
	}
		
}