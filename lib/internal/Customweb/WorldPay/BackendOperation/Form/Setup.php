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
 * @BackendForm
 */
class Customweb_WorldPay_BackendOperation_Form_Setup extends Customweb_Payment_BackendOperation_Form_Abstract {

	public function getTitle(){
		return Customweb_I18n_Translation::__("Setup");
	}

	public function getElementGroups(){
		return array(
			$this->getSetupGroup() 
		);
	}

	private function getSetupGroup(){
		$group = new Customweb_Form_ElementGroup();
		$group->setTitle(Customweb_I18n_Translation::__("Short Installation Instructions:"));
		
		$control = new Customweb_Form_Control_Html('description', 
				Customweb_I18n_Translation::__(
						'This is a brief installation instruction of the main and most important installation steps. It is important that you strictly follow the check-list. Only by doing so, the secure usage in correspondence with all security regulations can be guaranteed. This short integration description only describes the setup of the WorldPay Junior integration.'));
		$element = new Customweb_Form_WideElement($control);
		$group->addElement($element);
		
		$control = new Customweb_Form_Control_Html('steps', $this->createOrderedList($this->getSteps()));
		$element = new Customweb_Form_WideElement($control);
		$group->addElement($element);
		
		$control = new Customweb_Form_Control_Html('postdescription', 
				Customweb_I18n_Translation::__(
						'We recommend you to set the Capture Delay to off and capture directly. Make sure that the capture delay under Profile > Configuration Details is set to off.'));
		$element = new Customweb_Form_WideElement($control);
		$group->addElement($element);
		
		return $group;
	}

	private function getSteps(){
		return array(
			Customweb_I18n_Translation::__(
					'Enter the installation ID that you find in the WorldPay Backend under Installations.'),
			Customweb_I18n_Translation::__(
					'Enter the Merchant Code that you find in the backend on the left side under “merchant:”. The second merchant code is only required in case you want to do recurring transactions.'),
			Customweb_I18n_Translation::__(
					'Set the MD5 Password in the WorldPay backend under Installation > YOUR INSTALLATION > MD5 secret for transactions. Set the same passphrase also in the main module.'),
			Customweb_I18n_Translation::__("Make sure that the other fields especially the Payment Response URL according to the manual."),
			Customweb_I18n_Translation::__('Activate the payment methods that you want to process transactions with.') 
		);
	}

	private function createOrderedList(array $steps){
		$list = '<ol>';
		foreach ($steps as $step) {
			$list .= "<li>$step</li>";
		}
		$list .= '</ol>';
		return $list;
	}
}