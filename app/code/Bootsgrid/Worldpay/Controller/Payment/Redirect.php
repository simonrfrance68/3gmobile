<?php
namespace Bootsgrid\Worldpay\Controller\Payment;

use Magento\Sales\Model\Order;

class Redirect extends \Magento\Framework\App\Action\Action {

    const SIGNATURE_TYPE_STATIC  = 1;    
    const SIGNATURE_TYPE_DYNAMIC = 2;

    protected $_pageFactory;
    protected $_order;
    protected $_checkoutSession;    

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Sales\Model\Order $order,
        \Magento\Checkout\Model\Session $checkoutSession) {
        $this->_pageFactory = $pageFactory;
        $this->_order = $order;
        $this->_checkoutSession = $checkoutSession;        
        return parent::__construct($context);
    }

    public function execute() {

        $lastOrderId = $this->_checkoutSession->getLastRealOrderId();
        $order = $this->_order->loadByIncrementId($lastOrderId);      

        if (!$order->getId()) {
            throw new \Exception('No order for processing found');
        }

        $order->setState(Order::STATE_PENDING_PAYMENT, true, 'Customer was redirected to Worldpay.');
        $order->setStatus('pending_payment');
        $order->addStatusToHistory(
            $order->getStatus(), 'Customer was redirected to Worldpay.', true
        );       
        $order->save();

        $postFields = $this->getFormData();
        $wp_url = $this->getFormAction();
        $html = '<form name="worldpay_checkout" id="worldpay_checkout" action="'.$wp_url.'" method="POST">
                    <input type="hidden" name="instId" value="'.$postFields['instId'].'"/>
                    <input type="hidden" name="cartId" value="'.$postFields['cartId'].'"/>
                    <input type="hidden" name="authMode" value="'.$postFields['authMode'].'"/>
                    <input type="hidden" name="testMode" value="'.$postFields['testMode'].'"/>
                    <input type="hidden" name="amount" value="'.$postFields['amount'].'"/>
                    <input type="hidden" name="currency" value="'.$postFields['currency'].'"/>
                    <input type="hidden" name="hideCurrency" value="'.$postFields['hideCurrency'].'"/>
                    <input type="hidden" name="desc" value="'.$postFields['desc'].'"/>
                    <input type="hidden" name="name" value="'.$postFields['name'].'"/>
                    <input type="hidden" name="address1" value="'.$postFields['address1'].'"/>
                    <input type="hidden" name="town" value="'.$postFields['town'].'"/>
                    <input type="hidden" name="postcode" value="'.$postFields['postcode'].'"/>
                    <input type="hidden" name="country" value="'.$postFields['country'].'"/>
                    <input type="hidden" name="tel" value="'.$postFields['tel'].'"/>
                    <input type="hidden" name="email" value="'.$postFields['email'].'"/>
                    <input type="hidden" name="lang" value="'.$postFields['lang'].'"/>
                    <input type="hidden" name="MC_orderid" value="'.$postFields['MC_orderid'].'"/>
                    <input type="hidden" name="MC_callback" value="'.$postFields['MC_callback'].'"/>
                    <input type="hidden" name="fixContact" value="'.$postFields['fixContact'].'"/>
                    <input type="hidden" name="hideContact" value="'.$postFields['hideContact'].'"/>
               </form>    
                <script>
                    var paymentform = document.getElementById("worldpay_checkout");
                    window.onload = paymentform.submit();              
                </script>';
        echo $html;
        //return $this->_pageFactory->create();
    }

    public function getFormData() {

        $lastOrderId = $this->_checkoutSession->getLastRealOrderId();
        $order = $this->_order->loadByIncrementId($lastOrderId);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $baseurl = $storeManager->getStore()->getBaseUrl();

        $order = $objectManager->create('Magento\Sales\Api\Data\OrderInterface')->load($order->getId());
        $config = $objectManager->get('Bootsgrid\Worldpay\Model\Config');
        $address = $order->getBillingAddress()->getData();
        $params = array(

            'instId'        =>  $config->getInstallid(),

            'cartId'        =>  $order->getIncrementId(),

            'authMode'      =>  ($config->getRequesttype() == 'authorize') ? 'E' : 'A',

            'testMode'      =>  ($config->getMode() == 'test') ? '100' : '0',

            'amount'        =>  number_format($order->getGrandTotal(),2,'.',''),

            'currency'      =>  'GBP',

            'hideCurrency'  =>  'true',

            'desc'          =>  $config->getTitle(),

            'name'          =>  $address['firstname'],

            'address1'       => $address['street'],

            'town'          =>  $address['city'],

            'postcode'      =>  $address['postcode'],

            'country'       =>  $address['country_id'],

            'tel'           =>  $address['telephone'],

            'email'         =>  $address['email'],

            'lang'          =>  'en_US',

            'MC_orderid'    =>  $order->getIncrementId(),

            'MC_callback'   =>  $baseurl.'wpresponse/wporder.php'

        );

        // set additional flags
        if ($config->getFixcontact() == 1){
            $params['fixContact'] = 1;
        } else {
            $params['fixContact'] = 0;
        }

        if ($config->getHidecontact() == 1) {
            $params['hideContact'] = 1;    
        } else {
            $params['hideContact'] = 0;
        }            

        if ($config->getLangselect() == 1)
            $params['noLanguageMenu'] = null;

        // add md5 hash
        $securityKey = trim($config->getSecuritykey());

        if (empty($securityKey)) {

            return $params;

        }

        switch ($config->getSignaturetype()) {

            case self::SIGNATURE_TYPE_STATIC :

                $signatureParams = explode(':', $config->getSignatureparams());

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
                $signatureParamsString = $config->getSignatureparams();

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

        return $params;

    }

    public function getFormAction() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $config = $objectManager->get('Bootsgrid\Worldpay\Model\Config');
        if($config->getMode() == 'test') {

            $_testUrl = 'https://secure-test.worldpay.com/wcc/purchase';
            return $_testUrl;

        } else {

            $_liveUrl = 'https://secure.worldpay.com/wcc/purchase';
            return $_liveUrl;

        }
    }

}