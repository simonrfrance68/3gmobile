<?php
/**
 * Magento
 *
 * @category   Bootsgrid
 * @package    Bootsgrid_Worldpay
 * @copyright  Copyright (c) 2017-2018 Bootsgrid (https://www.bootsgrid.com)
 */
namespace Bootsgrid\Worldpay\Block;

class Redirect extends \Magento\Framework\View\Element\Template
{
    
    const SIGNATURE_TYPE_STATIC  = 1;
    
    const SIGNATURE_TYPE_DYNAMIC = 2;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Bootsgrid\Worldpay\Model\Config
     */
    protected $config;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Bootsgrid\Worldpay\Model\Config $config,        
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
        $this->config = $config;       
    }

    protected $_template = 'html/redirect.phtml';

    /**
     * Return checkout session instance
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout() {

        return $this->_checkoutSession;

    }

    /**
     * Return order instance
     *
     * @return Magento_Sales_Model_Order|null
     */
    protected function _getOrder() {

        if ($this->_orderFactory) {

            return $this->_orderFactory;

        } elseif ($orderIncrementId = $this->_getCheckout()->getLastRealOrderId()) {

            return $this->_orderFactory->loadByIncrementId($orderIncrementId);

        } else {

            return null;

        }

    }

    /**
     * Get form data
     *
     * @return array
     */
    public function getFormData() {  

        $_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $_objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $baseurl = $storeManager->getStore()->getBaseUrl();

        $resource = $_objectManager->get('Magento\Framework\App\ResourceConnection');

        $connection = $resource->getConnection();

        $getTableName = $connection->getTableName('sales_order_grid');

        $query_grid = "SELECT * FROM `$getTableName`";

        $result_grid = $connection->fetchAll($query_grid);

        $end_grid = end($result_grid); 

        if($end_grid['payment_method'] == 'worldpay_cc') {

            $getTableNameSoa = $connection->getTableName('sales_order_address');

            $query_address = "SELECT * FROM `$getTableNameSoa`";

            $result_address = $connection->fetchAll($query_address);

            $end_address = end($result_address);

            //$form_config = $block->getFormData();

            $params = array(

                'instId'        =>  $this->config->getInstallid(),

                'cartId'        =>  $end_grid['increment_id'],

                'authMode'      =>  ($this->config->getRequesttype() == 'authorize') ? 'E' : 'A',

                'testMode'      =>  ($this->config->getMode() == 'test') ? '100' : '0',

                'amount'        =>  number_format($end_grid['base_grand_total'],2,'.',''),

                'currency'      =>  $end_grid['order_currency_code'],

                'hideCurrency'  =>  'true',

                'desc'          =>  $this->config->getTitle(),

                'name'          =>  $end_grid['billing_name'],

                'address1'       => $end_address['street'],

                'town'          =>  $end_address['city'],

                'postcode'      =>  $end_address['postcode'],

                'country'       =>  $end_address['country_id'],

                'tel'           =>  $end_address['telephone'],

                'email'         =>  $end_address['email'],

                'lang'          =>  'en_US',

                'MC_orderid'    =>  $end_grid['increment_id'],

                'MC_callback'   =>  $baseurl.'wpresponse/wporder.php'

            );
          
            // set additional flags
            if ($this->config->getFixcontact() == 1)

                $params['fixContact'] = 1;

            if ($this->config->getHidecontact() == 1)

                $params['hideContact'] = 1;

            if ($this->config->getLangselect() == 1)

                $params['noLanguageMenu'] = null;

            // add md5 hash
            $securityKey = trim($this->config->getSecuritykey());

            if (empty($securityKey)) {

                return $params;

            }

            switch ($this->config->getSignaturetype()) {

                case self::SIGNATURE_TYPE_STATIC :

                    $signatureParams = explode(':', $this->config->getSignatureparams());

                    $signatureString = $securityKey;

                    foreach ($signatureParams as $param) {

                        if (array_key_exists($param, $params)) {

                            $signatureString .= ':' . $params[$param];

                        }

                    }

                    $params['signature'] = md5($signatureString);

                    break;

                case self::SIGNATURE_TYPE_DYNAMIC :

                    //'amount:currency:instId:cartId:authMode:email';
                    $signatureParamsString = $this->config->getSignatureparams();

                    $signatureParams = explode(':', $signatureParamsString);

                    $params['signatureFields'] = $signatureParamsString;

                    $signatureString = $securityKey . ';' . $signatureParamsString;

                    foreach ($signatureParams as $param) {

                        if (array_key_exists($param, $params)) {

                            $signatureString .= ';' . $params[$param];

                        }

                    }

                    $params['signature'] = md5($signatureString);

                    break;

            }            

        } 

        return $params;

    }

    /**
     * Getting gateway url
     *
     * @return string
     */
    public function getFormAction()
    {
        //return $this->_getOrder()->getPayment()->getMethodInstance()->getUrl();
        if($this->config->getMode() == 'test') {

            $_testUrl = 'https://secure-test.worldpay.com/wcc/purchase';

            return $_testUrl;

        } else {

            $_liveUrl = 'https://secure.worldpay.com/wcc/purchase';

            return $_liveUrl;

        }
    }

}