<?php

namespace Bootsgrid\Worldpay\Helper;

use Magento\Checkout\Model\Session;
use Magento\Sales\Model\OrderFactory;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    
	protected $_checkoutSession;
    protected $_order;

    public function __construct(Session $checkoutSession, OrderFactory $order) 
    {

        $this->_checkoutSession = $checkoutSession;
        $this->_order = $order;
    }

    public function getPendingPaymentStatus()
    {
        return OrderFactory::STATE_PENDING_PAYMENT;
    }

}