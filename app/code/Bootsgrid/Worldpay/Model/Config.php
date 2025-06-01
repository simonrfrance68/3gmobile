<?php
namespace Bootsgrid\Worldpay\Model;

class Config
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfigInterface;
    protected $customerSession;

    public function __construct(
    \Magento\Framework\App\Config\ScopeConfigInterface $configInterface,
    \Magento\Customer\Model\Session $customerSession,
    \Magento\Backend\Model\Session\Quote $sessionQuote
    )
    {
        $this->_scopeConfigInterface = $configInterface;
        $this->customerSession = $customerSession;
        $this->sessionQuote = $sessionQuote;
    }

    public function isActive() {
        return $this->_scopeConfigInterface->getValue('payment/worldpay_cc/active');
    }

    public function getTitle() {
        return $this->_scopeConfigInterface->getValue('payment/worldpay_cc/title');
    }

    public function getMode() {
        return $this->_scopeConfigInterface->getValue('payment/worldpay_cc/transaction_mode');
    }

    public function getInstallid() {
        return $this->_scopeConfigInterface->getValue('payment/worldpay_cc/inst_id');
    }

    public function getstoreCurrency()
    {
        return $this->_scopeConfigInterface->getValue('payment/worldpay_cc/use_store_currency');        
    }

    public function getSignaturetype()
    {
        return $this->_scopeConfigInterface->getValue('payment/worldpay_cc/signature_type');        
    }

    public function getSignatureparams() 
    {
        return $this->_scopeConfigInterface->getValue('payment/worldpay_cc/signature_params');
    }

    public function debugMode() 
    {
        return !!$this->_scopeConfigInterface->getValue('payment/worldpay_cc/debug');
    }

    public function getRequesttype() 
    {
        return $this->_scopeConfigInterface->getValue('payment/worldpay_cc/request_type');
    }

    public function getFixcontact() 
    {
        return $this->_scopeConfigInterface->getValue('payment/worldpay_cc/fix_contact');
    }

    public function getHidecontact() 
    {
        return $this->_scopeConfigInterface->getValue('payment/worldpay_cc/hide_contact');
    }

    public function getLangselect() 
    {
        return $this->_scopeConfigInterface->getValue('payment/worldpay_cc/hide_language_select');
    }

    public function getSecuritykey() 
    {
        return $this->_scopeConfigInterface->getValue('payment/worldpay_cc/security_key');
    }

    public function getEnableonline() 
    {
        return $this->_scopeConfigInterface->getValue('payment/worldpay_cc/enable_online_operations');
    }

    public function getAuthpassword() 
    {
        return $this->_scopeConfigInterface->getValue('payment/worldpay_cc/auth_password');
    }

    public function getAdminInstId() 
    {
        return $this->_scopeConfigInterface->getValue('payment/worldpay_cc/admin_inst_id');
    }
}