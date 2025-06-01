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



class Customweb_WorldPay_Authorization_Transaction extends Customweb_Payment_Authorization_DefaultTransaction
{
	private $transaction_id;
	private $authorizationType;
	private $check3DSecure;	
	private $echoData;
	private $ibanNumber;
	private $brandName = null;
	private $CCDetails;
	private $payment_id;
	private $recurring_id;
	private $cookies = array();
	private $threeDsParameters = array();
	
	
	public function __construct(Customweb_Payment_Authorization_ITransactionContext $transactionContext) {
		parent::__construct($transactionContext);
	
		$this->key = Customweb_Util_Rand::getRandomString(32, '');
		$this->setCheck3DSecure(false);
		$this->brandName = "NOTIDENTIFIED";	
		$this->authorizationType = "";
	}
	
	
	protected function getTransactionSpecificLabels() {
		$labels = array();
		$authParameters = $this->getAuthorizationParameters();
		if(isset($authParameters['cvcResult'])){
			$labels['cvcResult'] = array(
				'label' => Customweb_I18n_Translation::__("CVC Result Code"),
				'value' => $authParameters['cvcResult']
			);		
		}
		if(isset($authParameters['avsResult'])){
			$labels['avsResult'] = array(
				'label' => Customweb_I18n_Translation::__("AVS Result Code"),
				'value' => $authParameters['avsResult']
			);
		}
		return $labels;
	}
	
	public function getCaptureSetting() {
		return $this->getTransactionContext()->getOrderContext()->getPaymentMethod()->getPaymentMethodConfigurationValue('capturing');
	}

	public function setCheck3DSecure($flag){
		$this->check3DSecure = $flag;
	}
	
	public function is3DSecure(){
		return $this->check3DSecure;
		
	}
	
	public function getKey() {
		return $this->key;
	}
	
	public function setAuthorizationType($authorizationType) {
		$this->authorizationType = $authorizationType;		
	}
	
	public function getAuthorizationType() {
		return $this->authorizationType;
	}
	
	public function setEchoData($echoData) {
		$this->echoData = $echoData;
	}
	
	public function getEchoData() {
		return $this->echoData;
	}
	
	public function encrypt($string) {
		return base64_encode($this->getCipher()->encrypt($string));
	}
	
	public function decrypt($string) {
		return $this->getCipher()->decrypt(base64_decode($string));
	}
	
	/**
	 * @return Crypt_AES
	 */
	private function getCipher() {
		$cipher = new Crypt_AES(CRYPT_AES_MODE_CTR);
		$cipher->setKey($this->getKey());
		return $cipher;
	}
	
	public function setIbanNumber($ibanNumber) {
		$this->ibanNumber = $ibanNumber;
	}
	
	public function getIbanNumber() {
		return $this->ibanNumber;
	}
	
	public function setBrandName($brandName) {
		$this->brandName = $brandName;
	}
	
	public function getBrandName() {
		return $this->brandName;
	}
	
	public function setCCDetails(array $input) {
		$this->CCDetails = $input;
	}
	
	public function getCCDetails() {
		return $this->CCDetails;
	}

	public function getPaymentId() {
		//Legacy
		if(!empty($this->payment_id)) {
			return $this->payment_id;
		}
		return parent::getPaymentId();
	}
	
	
	public function getRecurringId() {
		//Legacy
		if( !empty($this->recurring_id)) {
			return $this->recurring_id;
		}
		return $this->getPaymentId();
	}

	public function getCookies(){
		return $this->cookies;
	}

	public function setCookies($cookies){
		$this->cookies = $cookies;
		return $this;
	}
	
	public function getThreeDsParameters(){
		return $this->threeDsParameters;
	}
	
	public function setThreeDsParameters(array $parameters){
		$this->threeDsParameters = $parameters;
		return $this;
	}
	
	public function addAuthorizationParameters(array $new){
		$existing = $this->getAuthorizationParameters();
		foreach($new as $key => $value){
			$existing[$key] = $value;
		}
		$this->setAuthorizationParameters($existing);
	}
	
}