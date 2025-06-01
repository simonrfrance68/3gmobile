<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Model\Authorisation;

use Exception;

class MotoRedirectService extends \Magento\Framework\DataObject
{
    protected $_session;
    protected $_redirectResponseModel;

    public function __construct(
        \Sapient\Worldpay\Model\Mapping\Service $mappingservice,
        \Sapient\Worldpay\Model\Request\PaymentServiceRequest $paymentservicerequest,
        \Sapient\Worldpay\Logger\WorldpayLogger $wplogger,
        \Sapient\Worldpay\Model\Payment\Service $paymentservice,
        \Sapient\Worldpay\Model\Response\RedirectResponse $redirectresponse,
        \Sapient\Worldpay\Helper\Registry $registryhelper,
        \Sapient\Worldpay\Helper\Data $worldpayhelper,
        \Magento\Checkout\Model\Session $checkoutsession,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Sapient\Worldpay\Model\Utilities\PaymentMethods $paymentlist
    ) {
       $this->mappingservice = $mappingservice;
       $this->paymentservicerequest = $paymentservicerequest;
       $this->wplogger = $wplogger;
       $this->paymentservice = $paymentservice;
       $this->redirectresponse = $redirectresponse;
       $this->registryhelper = $registryhelper;
       $this->checkoutsession = $checkoutsession;
       $this->paymentlist = $paymentlist;
       $this->_urlBuilder = $urlBuilder;
       $this->worldpayhelper = $worldpayhelper;
    }

    public function authorizePayment(
        $mageOrder,
        $quote,
        $orderCode,
        $orderStoreId,
        $paymentDetails,
        $payment
    ) {      
        $this->checkoutsession->setauthenticatedOrderId($mageOrder->getIncrementId());

        $redirectOrderParams = $this->mappingservice->collectRedirectOrderParameters(
            $orderCode,
            $quote,
            $orderStoreId,
            $paymentDetails
        );

        $responseXml = $this->paymentservicerequest->redirectOrder($redirectOrderParams);

        $successUrl = $this->_buildRedirectUrl($responseXml, $redirectOrderParams['paymentType'], $this->_getCountryForQuote($quote));

        $payment->setIsTransactionPending(1);
        $this->checkoutsession->setAdminWpRedirecturl($successUrl);

    }

   

    private function _buildRedirectUrl($responseXml, $paymentType, $countryCode)
    {
        $redirectUrl = $this->_getUrlFromResponse($responseXml);
        $redirectUrl = $this->_addOutcomeRoutes($redirectUrl);
        $redirectUrl = $this->_addExtraUrlParameters($redirectUrl, $paymentType, $countryCode);

        return $redirectUrl;
    }

    private function _getUrlFromResponse($responseXml)
    {
        $responseXmlElement = new \SimpleXmlElement($responseXml);
        $url = $responseXmlElement->xpath('reply/orderStatus/reference');

        return trim($url[0]);
    }

    private function _addOutcomeRoutes($redirectUrl)
    {
        $redirectUrl .= '&successURL=' . $this->_encodeUrl('worldpay/motoRedirectResult/success');
        $redirectUrl .= '&cancelURL=' . $this->_encodeUrl('worldpay/motoRedirectResult/cancel');
        $redirectUrl .= '&failureURL=' . $this->_encodeUrl('worldpay/motoRedirectResult/failure');

        return $redirectUrl;
    }

    private function _addExtraUrlParameters($redirectUrl, $paymentType, $countryCode)
    {
        $redirectUrl .= '&preferredPaymentMethod=' . $paymentType;
        $redirectUrl .= '&country=' . $countryCode;
        $redirectUrl .= '&language=' . $this->_getLanguageForLocale();

        return $redirectUrl;
    }

    private function _encodeUrl($path, $additionalParams = array())
    {
        $urlParams = array('_type' => 'direct_link', '_secure' => true);
        $urlParams = array_merge($urlParams, $additionalParams);
        $rawurlencode = rawurlencode(
            $this->_urlBuilder->getUrl($path, $urlParams)
        );

        return $rawurlencode;
    }

    private function _getCountryForQuote($quote)
    {
        $address = $quote->getBillingAddress();
        if ($address->getId()) {
            return $address->getCountry();
        }

        return $this->worldpayhelper->getDefaultCountry();
    }

    protected function _getLanguageForLocale()
    {
        $locale = $this->worldpayhelper->getLocaleDefault();
        if (substr($locale, 3, 2) == 'NO') {
            return 'no';
        }
        return substr($locale, 0, 2); 
    
    }
 
}
