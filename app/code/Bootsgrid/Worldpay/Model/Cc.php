<?php

namespace Bootsgrid\Worldpay\Model;

use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;
 
class Cc extends \Magento\Payment\Model\Method\AbstractMethod {

    protected $_code = 'worldpay_cc';
    protected $_canAuthorize = true;
    protected $_canCapture = true;

    protected $_testUrl = 'https://secure-test.worldpay.com/wcc/purchase';
    protected $_liveUrl = 'https://secure.worldpay.com/wcc/purchase';

    public function assignData(\Magento\Framework\DataObject $data) {

        $this->_eventManager->dispatch(
            'payment_method_assign_data_worldpay_cc',
            [
                AbstractDataAssignObserver::METHOD_CODE => $this,
                AbstractDataAssignObserver::MODEL_CODE => $this->getInfoInstance(),
                AbstractDataAssignObserver::DATA_CODE => $data
            ]
        );
     
        return $this;
    }
	
	public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null) {

		$_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $configData = $_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface');
        $bwEnable = $configData->getValue('payment/worldpay_cc/active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	
        if ($bwEnable != 1) {
            return false;
        }

        return parent::isAvailable($quote);
	}

    public function getUrl() {

        $_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $bwConfig = $_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface');
        $bwConfigMode = $bwConfig->getValue('payment/worldpay_cc/transaction_mode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if ($bwConfigMode == 'live')
            return $this->_liveUrl;
        return $this->_testUrl;

    }

	public function capture(InfoInterface $payment, $amount) {
       return $this;
    }

    public function authorize(InfoInterface $payment, $amount) {
    	return $this;
    }	
	
}