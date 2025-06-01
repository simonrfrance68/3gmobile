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
class Customweb_WorldPay_Update_Adapter extends Customweb_WorldPay_Authorization_AbstractAdapter 
	implements Customweb_Payment_Update_IAdapter {
	
	const TRANSACTION_TIMEOUT = 7200;
	const TRANSACTION_UPDATE_INTERVAL = 600;
	
	public function updateTransaction(Customweb_Payment_Authorization_ITransaction $transaction) {
			
		/* @var $transaction Customweb_WorldPay_Authorization_Transaction */
		if ($this->getConfiguration()->isTransactionUpdateActive() && !$transaction->isAuthorizationFailed()) {
			$queryData = Customweb_WorldPay_Authorization_Inquiry_Executor::performInquiry($transaction, $this->getConfiguration());

			if (!$queryData->reply->orderStatus->payment->lastEvent == "AUTHORISED") {
		
				// Cancel after 2 hours, when the customer does not confirm the order.
				$createdOn = $transaction->getCreatedOn();
				$now = new Customweb_Core_DateTime();
				$diff = $now->getTimestamp() - $createdOn->getTimestamp();
				if ($diff > self::TRANSACTION_TIMEOUT) {
					$cancellationAdapter = new Customweb_WorldPay_BackendOperation_Adapter_CancellationAdapter($this->getConfiguration()->getConfigurationAdapter());
					$cancellationAdapter->cancel($transaction);
				} else {
					$transaction->setUpdateExecutionDate(Customweb_Core_DateTime::_()->addSeconds(self::TRANSACTION_UPDATE_INTERVAL));
				}
			}
			else {
				$transaction->authorize();
				if(Customweb_WorldPay_Method_MethodHelper::isForcedByWorldpay($transaction)) {
					$transaction->capture();
				} else {
					if($this->isCapturing($transaction)) {
						$captureAdapter = $this->getContainer()->getBean('Customweb_WorldPay_BackendOperation_Adapter_CaptureAdapter');
						$captureAdapter->capture($transaction);
					}
				}
				$transaction->setUpdateExecutionDate(null);
			}
		}
		
	}
	
}
