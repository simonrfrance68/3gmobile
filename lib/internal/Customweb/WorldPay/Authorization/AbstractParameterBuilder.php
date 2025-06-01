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
class Customweb_WorldPay_Authorization_AbstractParameterBuilder {
	
	private $configuration;
	private $transaction;
	private $container;
	
	public function __construct(Customweb_DependencyInjection_IContainer $container, Customweb_WorldPay_Authorization_Transaction $transaction) {
		$this->container = new Customweb_WorldPay_Container($container);
		$this->configuration = $this->getContainer()->getBean('Customweb_WorldPay_Configuration');
		$this->transaction = $transaction;
	}

	/**
	 * @return Customweb_WorldPay_Configuration
	 */
	protected function getConfiguration(){
		return $this->configuration;
	}
	
	protected function getContainer() {
		return $this->container;
	}
	
	/**
	 * @return Customweb_WorldPay_Authorization_Transaction
	 */
	protected function getTransaction() {
		return $this->transaction;
	}
	
	protected function getTransactionContext() {
		return $this->getTransaction()->getTransactionContext();
	}
	
	/**
	 * @return Customweb_Payment_Authorization_IOrderContext
	 */
	protected function getOrderContext() {
		return $this->getTransactionContext()->getOrderContext();
	}
	
	/**
	 * @return string
	 */
	protected final function getTransactionAppliedSchema()
	{
		$schema = $this->getConfiguration()->getOrderIdSchema();
		$id = $this->getTransaction()->getExternalTransactionId();
	
		return Customweb_Payment_Util::applyOrderSchema($schema, $id, 64);
	}
	
	
	
}
