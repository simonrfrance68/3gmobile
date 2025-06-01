<?php
 
namespace Bootsgrid\Worldpay\Model;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Payment\Model\InfoInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartInterface;
/**
 * Worldpay Business payment method model
 */
class Cc extends \Magento\Payment\Model\Method\AbstractMethod
{
 
    const SIGNATURE_TYPE_STATIC  = 1;
    const SIGNATURE_TYPE_DYNAMIC = 2;

    /**
    * unique internal payment method identifier
    *
    * @var string [a-z0-9_]
    **/
    protected $_code = 'worldpay_cc';

    protected $_isInitializeNeeded      = true;
    protected $_isGateway               = false;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canVoid                 = false;
    protected $_canUseInternal          = true;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = false;

    protected $_paymentMethod           = 'cc';
    protected $_defaultLocale           = 'en';

    protected $_testUrl = 'https://secure-test.worldpay.com/wcc/purchase';
    protected $_liveUrl = 'https://secure.worldpay.com/wcc/purchase';

    protected $_testAdminUrl    = 'https://secure-test.worldpay.com/wcc/iadmin';
    protected $_liveAdminUrl    = 'https://secure.worldpay.com/wcc/iadmin';

    protected $_formBlockType = 'Bootsgrid\Worldpay\Block\Form';
    protected $_infoBlockType = 'Bootsgrid\Worldpay\Block\Info';

    protected $backendAuthSession;
    protected $cart;
    protected $urlBuilder;
    protected $_objectManager;
    protected $invoiceSender;
    protected $transactionFactory;
    protected $customerSession;
    protected $checkoutSession;
    protected $checkoutData;
    protected $quoteRepository;
    protected $quoteManagement;
    protected $orderSender;
    protected $order;
    protected $request;    

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \Bootsgrid\Worldpay\Model\Config $config,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Checkout\Helper\Data $checkoutData,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Api\CartManagementInterface $quoteManagement,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Sales\Model\Order $order,
        \Magento\Framework\App\Request\Http $request,        
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,        
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->urlBuilder = $urlBuilder;
        $this->backendAuthSession = $backendAuthSession;
        $this->config = $config;
        $this->cart = $cart;
        $this->_objectManager = $objectManager;
        $this->invoiceSender = $invoiceSender;
        $this->transactionFactory = $transactionFactory;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->checkoutData = $checkoutData;
        $this->quoteRepository = $quoteRepository;
        $this->quoteManagement = $quoteManagement;
        $this->orderSender = $orderSender;
        $this->sessionQuote = $sessionQuote;
        $this->order = $order;
        $this->_request = $request;        
    }

    public function initialize($paymentAction, $stateObject)
    {
        $stateObject->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);
    }

    /**
     * Get order model
     *
     * @return Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        $order = $this->checkoutSession->getLastRealOrder();
        $orderId = $order->getEntityId();
        return $order->getIncrementId();
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return \Magento\Payment\Model\Method\AbstractMethod::isAvailable($quote);
    }

    public function getOrderPlaceRedirectUrl() 
    {
        return $this->urlBuilder->getUrl('bworldpay/redirect');
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        // From Magento 2.0.7 onwards, the data is passed in a different property
        $additionalData = $data->getAdditionalData();
        if (is_array($additionalData))
            $data->setData(array_merge($data->getData(), $additionalData));

        $info = $this->getInfoInstance();
        return $this;
    }

    /**
     * Return payment method type string
     *
     * @return string
     */
    public function getPaymentMethodType()
    {
        return $this->_paymentMethod;
    }

    public function getUrl()
    {
        if ($this->config->getMode() == 'live')
            return $this->_liveUrl;
        return $this->_testUrl;
    }

    public function getAdminUrl()
    {
        if ($this->config->getMode() == 'live')
            return $this->_liveAdminUrl;
        return $this->_testAdminUrl;
    }
    
    /**
     * Refund money
     *
     * @param InfoInterface $payment
     * @param float $amount
     * @return  Bootsgrid\Worldpay\Model\Cc
     */
    public function refund(InfoInterface $payment, $amount)
    {
        $transactionId = $payment->getLastTransId();
        $params = $this->_prepareAdminRequestParams();

        $params['cartId']   = 'Refund';
        $params['op']       = 'refund-partial';
        $params['transId']  = $transactionId;
        $params['amount']   = $amount;
        $params['currency'] = $payment->getOrder()->getBaseCurrencyCode();

        $responseBody = $this->processAdminRequest($params);
        $response = explode(',', $responseBody);
        if (count($response) <= 0 || $response[0] != 'A' || $response[1] != $transactionId) {
            $message = 'Error during refunding online. Server response: %s -'.$responseBody;
            $this->_debug($message);
            throw new \Exception($message);
        }
        return $this;
    }

    /**
     * Capture preatutharized amount
     *
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function capture(InfoInterface $payment, $amount)
    {
        if (!$this->canCapture()) {
            return $this;
        }

        if ($this->_request->getParam('transId')) {
            // Capture is called from response action
            $payment->setStatus(self::STATUS_APPROVED);
            return $this;
        }
        $transactionId = $payment->getLastTransId();
        $params = $this->_prepareAdminRequestParams();
        $params['transId']  = $transactionId;
        $params['authMode'] = '0';
        $params['op']       = 'postAuth-full';

        $responseBody = $this->processAdminRequest($params);
        $response = explode(',', $responseBody);

        if (count($response) <= 0 || $response[0] != 'A' || $response[1] != $transactionId) {
            $message = 'Error during capture online. Server response: %s -'.$responseBody;
            $this->_debug($message);
            throw new \Exception($message);
        } else {
            $payment->getOrder()->addStatusToHistory($payment->getOrder()->getStatus(), 'Worldpay transaction has been captured.');
        }
    }


    /**
     * Check refund availability
     *
     * @return bool
     */
    public function canRefund ()
    {
        return $this->config->getEnableonline();
    }

    public function canRefundInvoicePartial()
    {
        return $this->config->getEnableonline();
    }

    public function canRefundPartialPerInvoice()
    {
        return $this->canRefundInvoicePartial();
    }

    public function canCapturePartial()
    {
        if ($this->_request->getFullActionName() != 'adminhtml_sales_order_creditmemo_new'){
            return false;
        }
        return $this->config->getEnableonline();
    }

    protected function processAdminRequest($params, $requestTimeout = 60)
    {
        try {
            $client = new \Zend\Http\Client();
            $client->setUri($this->getAdminUrl())
                ->setConfig(array('timeout'=>$requestTimeout,))
                ->setParameterPost($params)
                ->setMethod(\Zend\Http\Client::POST);

            $response = $client->request();
            $responseBody = $response->getBody();

            if (empty($responseBody))
                $message = 'Worldpay API failure. The request has not been processed.';
                $this->_debug($message);
                throw new \Exception($message);
            // create array out of response

        } catch (Exception $e) {
             $this->_debug('Worldpay API connection error: '.$e->getMessage());
             throw new \Exception('Worldpay API connection error. The request has not been processed.');
        }

        return $responseBody;
    }

    protected function _prepareAdminRequestParams()
    {
        $params = array (
            'authPW'   => $this->config->getAuthpassword(),
            'instId'   => $this->config->getAdminInstId(),
        );
        if ($this->config->getMode() == 'test') {
            $params['testMode'] = 100;
        }
        return $params;
    }    
    
}