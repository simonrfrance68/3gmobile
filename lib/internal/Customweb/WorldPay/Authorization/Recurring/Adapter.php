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
class Customweb_WorldPay_Authorization_Recurring_Adapter extends Customweb_WorldPay_Authorization_AbstractAdapter 
	implements Customweb_Payment_Authorization_Recurring_IAdapter {
		
		
		public function isAuthorizationMethodSupported(Customweb_Payment_Authorization_IOrderContext $orderContext){
			return true;
		}
		
		public function getAuthorizationMethodName(){
			return self::AUTHORIZATION_METHOD_NAME;
		}
		
		public function getAdapterPriority() {
			return 300;
		}
		
		
		/**
		 * This method returns true, when the given payment method supports recurring payments.
		 *
		 * @param Customweb_Payment_Authorization_IPaymentMethod $paymentMethod
		 * @return boolean
		 */
		public function isPaymentMethodSupportingRecurring(Customweb_Payment_Authorization_IPaymentMethod $paymentMethod) {
			return true;
		}
		
		/**
		 * This method creates a new recurring transaction.
		 *
		 * @param Customweb_Payment_Recurring_ITransactionContext $transactionContext
		*/
		public function createTransaction(Customweb_Payment_Authorization_Recurring_ITransactionContext $transactionContext){
			$transaction = new Customweb_WorldPay_Authorization_Transaction($transactionContext);
			$transaction->setAuthorizationMethod(self::AUTHORIZATION_METHOD_NAME);
			$transaction->setLiveTransaction(!$this->getConfiguration()->isTestMode());
			$transaction->setAuthorizationType("XML");
			return $transaction;
		}
		
		/**
		 * This method debits the given recurring transaction on the customers card.
		 *
		 * @param Customweb_Payment_Authorization_ITransaction $transaction
		 * @throws If something goes wrong
		 * @return void 
		*/
		public function process(Customweb_Payment_Authorization_ITransaction $transaction) {
			try{
				$oldTransaction = $transaction->getTransactionContext()->getInitialTransaction();
				$recurring_id = $oldTransaction->getRecurringId();
				$amount = Customweb_Util_Currency::formatAmount($transaction->getTransactionContext()->getOrderContext()->getOrderAmountInDecimals(),$transaction->getTransactionContext()->getOrderContext()->getCurrencyCode(), '', '');
				$initialMerchantCode = $this->getConfiguration()->getMerchantCode();
				/*
				 * Not necessary as discussed in ticket #2016062915472002834 email #180 
				if(Customweb_WorldPay_Method_MethodHelper::isForcedByWorldpay($oldTransaction) && $oldTransaction->getAuthorizationMethod() == Customweb_WorldPay_Authorization_PaymentPage_Adapter::AUTHORIZATION_METHOD_NAME) {
					$initialMerchantCode = $this->getConfiguration()->getSecondMerchantCode();
				}
				*/
				
				$xml='<?xml version="1.0"?> <!DOCTYPE paymentService PUBLIC "-//WorldPay//DTD WorldPay PaymentService v1//EN" "http://dtd.worldpay.com/paymentService_v1.dtd">
						<paymentService version="1.4" merchantCode="'. $this->getConfiguration()->getSecondMerchantCode() . '">
						    <submit>
						        <order orderCode="' . $this->getTransactionAppliedSchema($transaction, $this->getConfiguration()) . '">
						              <description> Recurring subscription. </description>
						              		<amount value="' . $amount .'" currencyCode="' . $transaction->getTransactionContext()->getOrderContext()->getCurrencyCode() .'" exponent="2"/>
						             	<payAsOrder orderCode="' . $recurring_id .  '" merchantCode="' . $initialMerchantCode .  '">
						              		 <amount value="' . $amount .'" currencyCode="' . $transaction->getTransactionContext()->getOrderContext()->getCurrencyCode() . '" exponent="2"/>
						              </payAsOrder>
						  		</order>
						    </submit>
						</paymentService>';
	
				$handler = Customweb_WorldPay_Util::sendRequest(
						$xml, 
						$this->getConfiguration()->getSecondMerchantCode(), 
						$this->getConfiguration()->getInvisibleXMLPassword(), 
						$this->getConfiguration()
				);
					
				//The XMLHttpRequest was flawed:
				if($handler->getStatusCode() != "200") {
					throw new Exception(Customweb_I18n_Translation::__("The XML response was not correct. Please Check your Worldpay XML Credentials"));
				}
				
				$xmlArray = simplexml_load_string($handler->getBody());
				if(isset($xmlArray->reply->error)) {
					$message = Customweb_I18n_Translation::__('WorldPay Error: !message', array('!message' => (string)$xmlArray->reply->error));
					$transaction->setAuthorizationFailed(message);
					throw new Customweb_Payment_Exception_RecurringPaymentErrorException($message);
				}
				$status = $xmlArray->reply->orderStatus->payment->lastEvent;
				if($status != "AUTHORISED") {
					$message = Customweb_I18n_Translation::__('The notification request could not be verified by WorldPay.');
					$transaction->setAuthorizationFailed(message);
					throw new Customweb_Payment_Exception_RecurringPaymentErrorException($message);
				}
				Customweb_WorldPay_Method_MethodHelper::validateInformation($transaction, $xmlArray, $this->getConfiguration());
				
				$transaction->authorize(Customweb_I18n_Translation::__('Order successfully authorized via the WorldPay Server.'));
				$transaction->setPaymentId($this->getTransactionAppliedSchema($transaction, $this->getConfiguration()));
				//recurring transaction are always captured (in the psp backend it will take some time before it shows up as captured
				$transaction->capture();
			}
			catch(Exception $e) {
				throw new Customweb_Payment_Exception_RecurringPaymentErrorException($e->getMessage());
			}
			
		}

	

}