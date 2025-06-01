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



final class Customweb_WorldPay_Util {

	private function __construct() {
		// prevent any instantiation of this class
	}

	public static function getCleanLanguageCode($lang) {
		$supportedLanguages = array('de_DE','en_US','fr_FR','da_DK',
				'cs_CZ','es_ES','hr_HR','it_IT','hu_HU','nl_NL',
				'no_NO','pl_PL','pt_PT','ru_RU','ro_RO','sk_SK',
				'sl_SI','fi_FI','sv_SE','tr_TR','el_GR','ja_JP'
		);
		return str_replace('_', '-', Customweb_Payment_Util::getCleanLanguageCode($lang,$supportedLanguages));
	}

	
	/**
	 * Sends a HTTP reqeust to the XML URL with the given message body and with the given user and password.
	 * 
	 * @param string $body
	 * @param string $username
	 * @param string $password
	 * @param Customweb_WorldPay_Configuration $configuration
	 * @return Customweb_Http_Response
	 */
	public static function sendRequest($body, $username, $password, Customweb_WorldPay_Configuration $configuration, $cookies = array()) {
		$authorization = new Customweb_Core_Http_Authorization_Basic();
		$authorization->setPassword($password);
		$authorization->setUsername($username);
		$requestUrl = new Customweb_Core_Url($configuration->getXMLUrlWorldpay());
		$request = new Customweb_Core_Http_Request($requestUrl);
		$request->setAuthorization($authorization);
		$request->setBody($body);
		$request->setMethod("POST");
		$request->appendHeader('Content-Type:text/xml');
		foreach($cookies as $cookie) {
			$request->appendHeader('cookie:' . $cookie->getRawName() . "=" . $cookie->getRawValue());	
		}
		$client = Customweb_Core_Http_Client_Factory::createClient();
		return $client->send($request);
	}

	/**
	 * This is a default implementation of the sendRequest which prefills the user and password.
	 * 
	 * @param string $body
	 * @param Customweb_WorldPay_Configuration $configuration
	 * @return Customweb_Http_Response
	 */
	public static function sendDefaultRequest($body, Customweb_WorldPay_Configuration $configuration, $cookies = array()) {
		return self::sendRequest($body, $configuration->getMerchantCode(), $configuration->getInvisibleXMLPassword(), $configuration, $cookies);
	}
	
	/**
	 * Checks whether an urls exceeds a certain limit.
	 *
	 * @param array $url
	 * @param int $size
	 */
	public static function checkUrlSize($url, $size) {
		if(strlen($url) > $size) {
			throw new Customweb_Payment_Exception_PaymentErrorException(Customweb_I18n_Translation::__("The Url is is too long."));
		}
	
		return $url;
	}
	
	
	
}