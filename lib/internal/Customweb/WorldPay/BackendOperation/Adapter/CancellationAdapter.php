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
 * @Bean
 *
 */
class Customweb_WorldPay_BackendOperation_Adapter_CancellationAdapter extends Customweb_WorldPay_AbstractAdapter
	implements Customweb_Payment_BackendOperation_Adapter_Service_ICancel{


	public function cancel(Customweb_Payment_Authorization_ITransaction $transaction) {
		
		$transaction->cancelDry();
		
		if($this->doCancellation($transaction)) {
			$transaction->cancel();
		} else {
			throw new Customweb_Payment_Exception_PaymentErrorException(
				Customweb_I18n_Translation::__("The transaction could not be cancelled.")
			);
		}
	}
	
	protected function doCancellation(Customweb_Payment_Authorization_ITransaction $transaction) {
		
		$xmlString = '<?xml version="1.0" encoding="UTF-8"?> <!DOCTYPE paymentService PUBLIC "-//WorldPay//DTD WorldPay PaymentService v1//EN" "http://dtd.worldpay.com/paymentService_v1.dtd">
						<paymentService version="1.4" merchantCode="'. $this->getConfiguration()->getMerchantCode() .'">
							<modify>
								<orderModification orderCode="'. $transaction->getPaymentId() .'">
									<cancel/>
								</orderModification>
							</modify>
						</paymentService>';

		$handler = Customweb_WorldPay_Util::sendDefaultRequest(
				$xmlString,
				$this->getConfiguration()
		);
		
		$xmlArray = simplexml_load_string($handler->getBody());
		
		foreach($xmlArray->reply[0]->ok[0]->cancelReceived[0]->attributes() as $key => $value) {
			if($key === 'orderCode') {
				$orderCode = $value;
				break;
			}
		}
		
		if($orderCode == $transaction->getPaymentId()) {
			return true;
		} else {
			return false;
		}
	}
			
}