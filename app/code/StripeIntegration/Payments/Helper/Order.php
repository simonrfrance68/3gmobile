<?php

namespace StripeIntegration\Payments\Helper;

class Order
{
    public $orderComments = [];
    private $ordersCache = [];
    private $orderTaxManagement;
    private $subscriptionProductFactory;
    private $orderFactory;
    private $orderRepository;
    private $orderSender;
    private $orderCommentSender;
    private $logger;

    public function __construct(
        \Magento\Tax\Api\OrderTaxManagementInterface $orderTaxManagement,
        \Magento\Sales\Api\Data\OrderInterfaceFactory $orderFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Order\Email\Sender\OrderCommentSender $orderCommentSender,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \StripeIntegration\Payments\Model\SubscriptionProductFactory $subscriptionProductFactory,
        \StripeIntegration\Payments\Helper\Logger $logger
    )
    {
        $this->orderTaxManagement = $orderTaxManagement;
        $this->orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;
        $this->orderCommentSender = $orderCommentSender;
        $this->orderSender = $orderSender;
        $this->subscriptionProductFactory = $subscriptionProductFactory;
        $this->logger = $logger;
    }

    /**
     * Array
     * (
     *     [code] => US-CA-*-Rate 1
     *     [title] => US-CA-*-Rate 1
     *     [percent] => 8.2500
     *     [amount] => 1.65
     *     [base_amount] => 1.65
     * )
     */
    public function getAppliedTaxes($orderId)
    {
        $taxes = [];
        $appliedTaxes = $this->orderTaxManagement->getOrderTaxDetails($orderId)->getAppliedTaxes();

        foreach ($appliedTaxes as $appliedTax)
        {
            $taxes[] = $appliedTax->getData();
        }

        return $taxes;
    }

    public function orderAgeLessThan($minutes, $order)
    {
        $created = strtotime($order->getCreatedAt());
        $now = time();
        return (($now - $created) < ($minutes * 60));
    }

    public function setRiskDataFrom($paymentIntentResponse, $order)
    {
        if (is_array($paymentIntentResponse)) {
            if (isset($paymentIntentResponse['outcome']['risk_score']) && $paymentIntentResponse['outcome']['risk_score'] >= 0) {
                $order->setStripeRadarRiskScore($paymentIntentResponse['outcome']['risk_score']);
            }
            if (isset($paymentIntentResponse['outcome']['risk_level'])) {
                $order->setStripeRadarRiskLevel($paymentIntentResponse['outcome']['risk_level']);
            }
        } else {
            if (isset($paymentIntentResponse->charges->data[0]->outcome->risk_score) && $paymentIntentResponse->charges->data[0]->outcome->risk_score >= 0) {
                $order->setStripeRadarRiskScore($paymentIntentResponse->charges->data[0]->outcome->risk_score);
            }
            if (isset($paymentIntentResponse->charges->data[0]->outcome->risk_level)) {
                $order->setStripeRadarRiskLevel($paymentIntentResponse->charges->data[0]->outcome->risk_level);
            }
        }
    }

    public function hasSubscriptionsIn($orderItems)
    {
        foreach ($orderItems as $item)
        {
            $subscriptionProductModel = $this->subscriptionProductFactory->create()->fromOrderItem($item);
            if ($subscriptionProductModel->isSubscriptionProduct())
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Description
     * @param array<\Magento\Sales\Model\Order\Item> $orderItems
     * @return bool
     */
    public function hasTrialSubscriptionsIn($orderItems)
    {
        foreach ($orderItems as $item)
        {
            $subscriptionProductModel = $this->subscriptionProductFactory->create()->fromOrderItem($item);
            if ($subscriptionProductModel->isSubscriptionProduct() && $subscriptionProductModel->hasTrialPeriod())
            {
                return true;
            }
        }

        return false;
    }

    public function loadOrderById($orderId)
    {
        return $this->orderFactory->create()->load($orderId);
    }

    public function saveOrder($order)
    {
        return $this->orderRepository->save($order);
    }

    public function loadOrderByIncrementId($incrementId, $useCache = true)
    {
        if (empty($incrementId))
            return null;

        if (!empty($this->ordersCache[$incrementId]) && $useCache)
            return $this->ordersCache[$incrementId];

        try
        {
            $orderModel = $this->orderFactory->create();
            $order = $orderModel->loadByIncrementId($incrementId);
            if ($order && $order->getId())
                return $this->ordersCache[$incrementId] = $order;
        }
        catch (\Exception $e)
        {
            return null;
        }
    }

    public function getOrderDescription($order)
    {
        if ($order->getCustomerIsGuest())
            $customerName = $order->getBillingAddress()->getName();
        else
            $customerName = $order->getCustomerName();

        if ($this->hasSubscriptionsIn($order->getAllItems()))
            $subscription = "subscription ";
        else
            $subscription = "";

        if ($this->isMultiShipping($order))
            $description = "Multi-shipping {$subscription}order #" . $order->getRealOrderId() . " by $customerName";
        else
            $description = "{$subscription}order #" . $order->getRealOrderId() . " by $customerName";

        return ucfirst($description);
    }

    public function isMultishipping($order)
    {
        if (!$order)
            return false;

        $shippingAddresses = $order->getShippingAddressesCollection();
        if ($shippingAddresses && count($shippingAddresses) > 1) {
            return true;
        }

        return false;
    }

    public function clearCache()
    {
        $this->ordersCache = [];
    }

    public function sendNewOrderEmailFor($order, $forceSend = false)
    {
        if (empty($order) || !$order->getId())
            return;

        if (!$order->getEmailSent() && $forceSend)
        {
            $order->setCanSendNewEmailFlag(true);
        }

        // Send the order email
        if ($order->getCanSendNewEmailFlag())
        {
            try
            {
                $this->orderSender->send($order);
                return true;
            }
            catch (\Exception $e)
            {
                $this->logger->logError($e->getMessage(), $e->getTraceAsString());
            }
        }

        return false;
    }

    public function notifyCustomer($order, $comment)
    {
        $order->addStatusToHistory($status = false, $comment, $isCustomerNotified = true);
        $order->setCustomerNote($comment);

        try
        {
            $this->orderCommentSender->send($order, $notify = true, $comment);
        }
        catch (\Exception $e)
        {
            $this->logger->logError("Order email sending failed: " . $e->getMessage());
        }
    }

    public function addOrderComment($msg, $order, $isCustomerNotified = false)
    {
        if ($order)
            $order->addCommentToStatusHistory($msg);
    }

    public function holdOrder(&$order)
    {
        $order->setHoldBeforeState($order->getState());
        $order->setHoldBeforeStatus($order->getStatus());
        $order->setState(\Magento\Sales\Model\Order::STATE_HOLDED)
            ->setStatus($order->getConfig()->getStateDefaultStatus(\Magento\Sales\Model\Order::STATE_HOLDED));
        $comment = __("Order placed under manual review by Stripe Radar.");
        $order->addStatusToHistory(false, $comment, false);

        return $order;
    }
}
