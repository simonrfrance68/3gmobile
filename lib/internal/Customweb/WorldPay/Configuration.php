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
 */
class Customweb_WorldPay_Configuration{
	
	/**
	 *       	 			   		 	 	 
	 * @var Customweb_Payment_IConfigurationAdapter
	 */
	private $configurationAdapter = null;

	
	public function __construct(Customweb_Payment_IConfigurationAdapter $configurationAdapter) {
		$this->configurationAdapter = $configurationAdapter;
	}
	
	private $handler;
	/**
	 * Returns whether the gateway is in test mode or in live mode.
	 *       	 			   		 	 	 
	 * @return boolean True if the system is in test mode. Else return false.
	 */
	public function isTestMode()
	{
		return $this->getConfigurationAdapter()->getConfigurationValue('operation_mode') != 'live';
	}
	
	public function getMerchantCode() {
		$value = $this->getConfigurationAdapter()->getConfigurationValue('merchant_code');
		$value = trim($value);
		if (empty($value)) {
			throw new Exception(Customweb_I18n_Translation::__("You need to set the Merchant Code in the module settings."));
		}
		return $value;
	}
	
	
	/**
	 * Checks wheter an XML Installation ID and therefore the access to WorldPay via XML is possible, only 
	 * through XML Access capturing and refunding via the Shop-Backend is possible
	 * 
	 * @return boolean
	 */
	public function isInvisibleXMLAccessEnabled() {
		$installationId = $this->getConfigurationAdapter()->getConfigurationValue('xml_installation_id');
		if(strlen($installationId) > 0) {
			return true;
		}	else {
			return false;
		}
	}
	
	public function getInvisibleXMLInstallationId() {
		$xmlInstallationId = $this->getConfigurationAdapter()->getConfigurationValue('xml_installation_id');
		$xmlInstallationId = trim($xmlInstallationId);
		
		if (empty($xmlInstallationId)) {
			throw new Exception(Customweb_I18n_Translation::__("You need to set the XML installation ID (Invisible XML) in the module settings."));
		}
		
		return $xmlInstallationId;
	}
	
	public function getInvisibleXMLPassword() {
		$password = $this->getConfigurationAdapter()->getConfigurationValue('xml_password');
		$password = trim($password);
		
		if (empty($password)) {
			throw new Exception(Customweb_I18n_Translation::__("You need to set the XML password in the module settings."));
		}
		
		return $password;
	}

	public function getBaseUrl() {
		if($this->isTestMode()) {
			return "https://secure-test.worldpay.com/";
		}
		else{
			return "https://secure.worldpay.com/";
		}
	}

	public function getUrlWorldpay() {
		return $this->getBaseUrl() . "wcc/purchase";
	}
	
	public function getRemoteUrlWorldpay() {
		return $this->getBaseUrl() . "wcc/itransaction";
	}
	
	public function getXMLUrlWorldpay() {
		return $this->getBaseUrl() . "jsp/merchant/xml/paymentService.jsp";
	}
	
	public function getMoToUrl() {
		return $this->getBaseUrl() . "wcc/purchase";
	}
	
	public function getMotoInstallationId() {
		return $this->getConfigurationAdapter()->getConfigurationValue('moto_instllation_id');
	}
	
	public function getInstId() {
		$installationID = $this->getConfigurationAdapter()->getConfigurationValue('installation_id');
		$installationID = trim($installationID);
		if (empty($installationID)) {
			throw new Exception(Customweb_I18n_Translation::__("You need to set the installation id (Select Junior) in the module settings."));
		}
		
		return $installationID;
	}
	
	public function getRemoteInstId() {
		$remoteInstallationId = $this->getConfigurationAdapter()->getConfigurationValue('remote_installation_id');
		$remoteInstallationId = trim($remoteInstallationId);
		if (empty($remoteInstallationId)) {
			throw new Exception(Customweb_I18n_Translation::__("You need to set the Installation ID (Select Junior - Remote Admin) in the module settings."));
		}
		
		return $remoteInstallationId;
	}

	public function getAuthPW() {
		$value = $this->getConfigurationAdapter()->getConfigurationValue('remote_authentication_password');
		$value = trim($value);
		if (empty($value)) {
			throw new Exception(Customweb_I18n_Translation::__("You need to set the Remote Authentication Password in the module settings."));
		}
		return $value;
	}

	public function getResponsePassword() {
		$value = $this->getConfigurationAdapter()->getConfigurationValue('response_password');
		$value = trim($value);
		if (empty($value)) {
			throw new Exception(Customweb_I18n_Translation::__("You need to set the Payment Response Password in the module settings."));
		}
		return $value;
	}
	
	public function getMD5SecretKey() {
		$value = $this->getConfigurationAdapter()->getConfigurationValue('md5_secret');
		$value = trim($value);
		if (empty($value)) {
			throw new Exception(Customweb_I18n_Translation::__("You need to set the MD-5 Secret for transaction in the module settings."));
		}
		return $value;
	}
	
	public function getSecondMerchantCode() {
		$value = $this->getConfigurationAdapter()->getConfigurationValue('second_merchant_code');
		$value = trim($value);
		if (empty($value)) {
			throw new Exception(Customweb_I18n_Translation::__("You need to set the Second Merchant Code for transaction in the module settings."));
		}
		return $value;
	}
	
	public function getOrderIdSchema(){
		return $this->getConfigurationAdapter()->getConfigurationValue('order_id_schema');
	}
	
	public function isTransactionUpdateActive() {
		if ($this->configurationAdapter->existsConfiguration('transaction_updates') && $this->configurationAdapter->getConfigurationValue('transaction_updates') == 'active') {
			return true;
		}
		return false;
	}
	
	
	/**
	 *
	 * @return Customweb_Payment_IConfigurationAdapter
	 */
	public function getConfigurationAdapter() {
		return $this->configurationAdapter;
	}
	

}
