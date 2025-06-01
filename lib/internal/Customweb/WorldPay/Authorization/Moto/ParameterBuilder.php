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
 *
 */
class Customweb_WorldPay_Authorization_Moto_ParameterBuilder extends Customweb_WorldPay_Authorization_AbstractRedirectParameterBuilder {

	protected function getBaseParameters(){
		if (strlen($this->getConfiguration()->getMotoInstallationId()) == 0) {
			throw new Customweb_Payment_Exception_PaymentErrorException(
					new Customweb_Payment_Authorization_ErrorMessage(Customweb_I18n_Translation::__('Payment was not successful.'), 
							Customweb_I18n_Translation::__('The Moto Installation Id is not set')));
		}
		
		$parameters = parent::getBaseParameters();
		$parameters['instId'] = $this->getConfiguration()->getMotoInstallationId();
		return $parameters;
	}
}