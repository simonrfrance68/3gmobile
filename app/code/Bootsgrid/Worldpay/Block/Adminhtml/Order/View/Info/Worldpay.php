<?php

namespace Bootsgrid\Worldpay\Block\Adminhtml\Order\View\Info;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order;

class Worldpay extends \Magento\Backend\Block\Template
{
    protected $registry;
    protected $order;

    public function __construct(Context $context, Registry $registry, Order $order, array $data = []) {
        $this->registry = $registry;
        $this->order = $order;
        parent::__construct($context, $data);
    }

    public function getOrder()
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->registry->registry('current_order');

        $orderId = $order->getIncrementId();

        $_order = $this->order->loadByIncrementId($orderId);

        return $_order;
    }

}