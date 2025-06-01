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
 * @author Thomas Hunziker
 * @Controller("process")
 *
 */
class Customweb_WorldPay_Endpoint_Process extends Customweb_Payment_Endpoint_Controller_Process {

	/**
	 * @Action("ppn")
	 */
	public function processXmlRequest(Customweb_Payment_Authorization_ITransaction $transaction, Customweb_Core_Http_IRequest $request){
		$adapter = $this->getAdapterFactory()->getAuthorizationAdapterByName($transaction->getAuthorizationMethod());
		$parameters = $request->getParameters();
		$response = $adapter->processXmlAuthorization($transaction, $parameters);
		return $response;
	}

	/**
	 * @Action("threedsframe")
	 */
	public function threeDSFrameAction(Customweb_Core_Http_IRequest $request){
		$parameters = $request->getParameters();
		if (!isset($parameters['cwSign'])) {
			throw new Exception('Security Hash not set');
		}
		$transaction = null;
		$idKeys = $this->getTransactionId($request);
		$transaction = $this->getTransactionHandler()->findTransactionByTransactionExternalId($idKeys['id']);
		if (!($transaction instanceof Customweb_WorldPay_Authorization_Transaction)) {
			throw new Exception('Transaction not found');
		}
		$transaction->checkSecuritySignature('process/threedsframe', $parameters['cwSign']);
		
		if($transaction->isAuthorized()){
			return Customweb_Core_Http_Response::redirect($transaction->getSuccessUrl());
		}
		elseif ($transaction->isAuthorizationFailed()){
			return Customweb_Core_Http_Response::redirect($transaction->getFailedUrl());
		}
		
		$layoutContext = new Customweb_Mvc_Layout_RenderContext();
		$redirectUrl = $this->getEndpointAdapter()->getUrl('process', 'threedsredirect', 
				array(
					'cw_transaction_id' => $transaction->getExternalTransactionId(),
					'cwSign' => $transaction->getSecuritySignature('process/threedsredirect'),
					'mde' => $parameters['mde']
				));
		$layoutContext->setMainContent('<iframe src="' . $redirectUrl . '"  style="min-height: 500px; width: 100%;" class="worldpay-3ds-iframe"></iframe>');
		$layoutContext->setTitle(Customweb_I18n_Translation::__('3D secure'));
		return $this->getLayoutRenderer()->render($layoutContext);
	}

	/**
	 * @Action("threedsredirect")
	 */
	public function threeDSCheckAction(Customweb_Payment_Authorization_ITransaction $transaction, Customweb_Core_Http_IRequest $request){
		$parameters = $request->getParameters();
		if (!isset($parameters['cwSign'])) {
			throw new Exception('Security Hash not set');
		}
		$transaction = null;
		$idKeys = $this->getTransactionId($request);
		$transaction = $this->getTransactionHandler()->findTransactionByTransactionExternalId($idKeys['id']);
		if (!($transaction instanceof Customweb_WorldPay_Authorization_Transaction)) {
			throw new Exception('Transaction not found');
		}
		$transaction->checkSecuritySignature('process/threedsredirect', $parameters['cwSign']);
		$response = new Customweb_Core_Http_Response();
		$additionalParameters = $transaction->getThreeDsParameters();
		if(empty($additionalParameters['issuerUrl'])) {
			$transaction->setAuthorizationFailed(Customweb_I18n_Translation::__("3D issuer URL was not found."));
			return Customweb_Core_Http_Response::redirect($transaction->getFailedUrl());
		}
		$body = 
		'<html>
			<head>
				<title>3-D Secure helper page</title>
			</head>
			<body OnLoad="OnLoadEvent();">
				This page should forward you to your own card issuer for identification.
				If your browser does not start loading the page, press the button you
				see.
			<br/>
				After you successfully identify yourself you will be sent back to this
				site where the payment process will continue.<br/>
				<form name="theForm" method="POST" action="' . $additionalParameters['issuerUrl'] . '" >
					<input type="hidden" name="PaReq" value="' . $additionalParameters['paRequest'] . '"/>
					<input type="hidden" name="TermUrl" value="' .$this->getEndpointAdapter()->getUrl('process', 'index', array('cw_transaction_id' => $transaction->getExternalTransactionId())) . '" />
					<input type="hidden" name="MD" value="' . $parameters['mde']. '" />
					<input type="submit" name="Identify yourself" />
				</form>
				<script language="Javascript">
					<!--
					function OnLoadEvent() {
						document.theForm.submit();
					}
					// -->
				</script>
			</body>
		</html>';
		$response->setBody($body);
		return $response;
	}
	
}