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
 * @author Thomas Hunziker
 * 
 * @Bean
 */
class Customweb_WorldPay_Method_Factory extends Customweb_Payment_Authorization_Method_AbstractFactory{
	
	protected function getMethodPackages() {
		return array('Customweb_WorldPay_Method');
	}
	
	/**
	 * @return Customweb_WorldPay_Method_DefaultMethod
	 */
	public function getPaymentMethod(Customweb_Payment_Authorization_IPaymentMethod $method, $authorizationMethodName) {
		return parent::getPaymentMethod($method, $authorizationMethodName);
	}
}