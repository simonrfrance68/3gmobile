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
class Customweb_WorldPay_Authorization_Server_ParameterBuilder extends Customweb_WorldPay_Authorization_AbstractParameterBuilder {

	public function buildParameters(){
		return array();
	}

	public function generateXMLRequest(Customweb_Payment_Authorization_ITransaction $transaction, $parameters, Customweb_WorldPay_Method_AbstractMethod $paymentMethodClass, $flag3dsecure = FALSE){
		
		/* @var $transaction Customweb_WorldPay_Authorization_Transaction */
		$xml3dSection = array(
			'paResponse' => "",
			'echo' => "" 
		);
		
		if (!isset($parameters['cardno'])) {
			$parameters['cardno'] = 0;
		}
		
		$paymentMethod = $paymentMethodClass->getPaymentMethodIdentifier($transaction, $parameters);
		
		if ($flag3dsecure) {
			$xml3dSection = $this->form3dSection($parameters);
		}
		
		$amount = Customweb_Util_Currency::formatAmount($this->getOrderContext()->getOrderAmountInDecimals(), 
				$this->getOrderContext()->getCurrencyCode(), '', '');
		$xml = '<?xml version="1.0" encoding="UTF-8"?> <!DOCTYPE paymentService PUBLIC "-//WorldPay//DTD WorldPay PaymentService v1//EN" "http://dtd.worldpay.com/paymentService_v1.dtd">
				<paymentService version="1.4" merchantCode="' . $this->getConfiguration()->getMerchantCode() . '">
					<submit>
						<order orderCode="' . $transaction->getPaymentId() . '">
	
							<description>' . 'Details from order: ' .
									 $transaction->getPaymentId() .
							'</description>
	
				            <amount value="' .
				 $amount . '" currencyCode="' . $transaction->getTransactionContext()->getOrderContext()->getCurrencyCode() . '" exponent="2"/>
				            <paymentDetails>		
				            	<' . $paymentMethod . '>' . $paymentMethodClass->createXMLString($parameters, $transaction) . '</' .
				 $paymentMethod .'>		                  
				          		<session shopperIPAddress="' .
				 Customweb_Core_Http_ContextRequest::getClientIPAddressV6() . '" id="' . md5($transaction->getPaymentId()) . '" />' . $xml3dSection['paResponse'] .
							 '</paymentDetails>
	
				             <shopper>
				             	<shopperEmailAddress>' .
				 $transaction->getTransactionContext()->getOrderContext()->getCustomerEMailAddress() .
				 				'</shopperEmailAddress>
				                <browser>
				                	<acceptHeader>text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8</acceptHeader>
				                    <userAgentHeader>' . $_SERVER['HTTP_USER_AGENT'] . '</userAgentHeader>
				                </browser>
			            	</shopper>
			               ' . $xml3dSection['echo'] . '
				        </order>
				   </submit>
				</paymentService>';
		
		return html_entity_decode($xml);
	}

	protected function form3dSection($parameters){
		$section1 = '<info3DSecure>
							<paResponse>' . $parameters['PaRes'] . '</paResponse>
					</info3DSecure>';
		$section2 = '<echoData>' . $parameters['echo'] . '</echoData>';
		return (array(
			'paResponse' => $section1,
			'echo' => $section2 
		));
	}

	/**
	 *
	 * @return string
	 */
	private final function getTransactionAppliedSchemaServer(Customweb_Payment_Authorization_ITransaction $transaction){
		$schema = $this->getConfiguration()->getOrderIdSchema();
		$id = $transaction->getExternalTransactionId();
		
		return Customweb_Payment_Util::applyOrderSchema($schema, $id, 64);
	}
}