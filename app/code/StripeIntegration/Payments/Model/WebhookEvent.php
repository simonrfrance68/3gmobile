<?php

namespace StripeIntegration\Payments\Model;

use StripeIntegration\Payments\Exception\WebhookException;
use StripeIntegration\Payments\Exception\GenericException;

class WebhookEvent extends \Magento\Framework\Model\AbstractModel
{
    private array $event;
    private $stdEvent;
    private $dateTime;
    private $config;
    private $defaultStripeClient;
    private $webhooksHelper;
    private $resourceModel;
    private $orderHelper;

    public function __construct(
        \StripeIntegration\Payments\Helper\Webhooks $webhooksHelper,
        \StripeIntegration\Payments\Helper\Order $orderHelper,
        \StripeIntegration\Payments\Model\Config $config,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \StripeIntegration\Payments\Model\ResourceModel\WebhookEvent $resourceModel,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->orderHelper = $orderHelper;
        $this->webhooksHelper = $webhooksHelper;
        $this->config = $config;
        $this->dateTime = $dateTime;
        $this->defaultStripeClient = $config->getStripeClient();
        $this->resourceModel = $resourceModel;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init('StripeIntegration\Payments\Model\ResourceModel\WebhookEvent');
    }

    public function fromStripeObject(array $event, bool $processMoreThanOnce)
    {
        if (empty($event['id']) || empty($event['type']))
        {
            throw new WebhookException("Invalid event data specified", 400);
        }

        $this->setEventId($event['id']);
        $this->setEventType($event['type']);

        if (!empty($event['data']['object']))
        {
            $object = $event['data']['object'];
            if (!empty($object['object']) && $object['object'] == "payment_intent")
            {
                $this->setPaymentIntentId($object['id']);
            }
            else if (!empty($object['payment_intent']) && is_string($object['payment_intent']))
            {
                $this->setPaymentIntentId($object['payment_intent']);
            }
        }

        try
        {
            // When the same event arrives multiple times, even in parallel, we expect that InnoDB
            // will fail with a unique constraint violation error. InnoDB writes are serial.
            $this->resourceModel->save($this);
        }
        catch (\Magento\Framework\Exception\AlreadyExistsException $e)
        {
            $this->resourceModel->load($this, $event['id'], 'event_id');

            if (!$processMoreThanOnce)
            {
                if ($this->getIsProcessed())
                {
                    throw new WebhookException(__("Already processed in previous request."), 202);
                }
                else
                {
                    throw new WebhookException(__("Event is queued for processing."), 202);
                }
            }
        }

        $this->event = $event;

        return $this;
    }

    public function markAsProcessed()
    {
        $this->setIsProcessed(true);
        $this->setProcessedAt($this->dateTime->formatDate(true));
        $this->setLastError(null);
        $this->setLastErrorStatusCode(null);
        $this->setLastErrorAt(null);
        $this->resourceModel->save($this);

        return $this;
    }

    public function setLastErrorFromException(\Exception $e, $statusCode = null)
    {
        $txt = sprintf("[%s] %s\n%s.", \StripeIntegration\Payments\Model\Config::$moduleVersion, $e->getMessage(), $e->getTraceAsString());
        $this->setLastError($txt);

        if ($statusCode)
        {
            $this->setLastErrorStatusCode($statusCode);
        }

        $this->setLastErrorAt($this->dateTime->formatDate(true));
        $this->resourceModel->save($this);

        return $this;
    }

    // Use this if the DB entry may have been mutated at other places in the codebase and we need to refresh the object.
    public function refresh()
    {
        if ($this->getEventId())
        {
            $this->resourceModel->load($this, $this->getEventId(), 'event_id');
        }
        else
        {
            $this->resourceModel->load($this, $this->event['id'], 'event_id');
        }

        return $this;
    }

    public function process($stripeClient = null)
    {
        if (!$this->getEventId())
        {
            throw new GenericException("Event ID has not been set.");
        }

        if (empty($this->stdEvent))
        {
            if ($stripeClient)
            {
                if ($this->getOrderIncrementId())
                {
                    $stripeClient = $this->findStripeClientByOrder();
                }
                else
                {
                    $stripeClient = $this->defaultStripeClient;
                }

                $this->stdEvent = $stripeClient->events->retrieve($this->getEventId(), []);
            }
            else
            {
                try
                {
                    $stripeClient = $this->findStripeClientByOrder();
                    $this->stdEvent = $stripeClient->events->retrieve($this->getEventId(), []);
                }
                catch (\Exception $e)
                {
                    $this->stdEvent = $this->findEventAcrossAllAccounts();
                }
            }
        }

        $this->webhooksHelper->dispatchEvent($this->stdEvent, true);
    }

    public function isOlderThanDays(int $days)
    {
        $createdAt = strtotime($this->getCreatedAt());

        $time = time() - ($days * 24 * 60 * 60);

        return ($createdAt < $time);
    }

    public function shouldRetry()
    {
        if ($this->getRetries() >= 6)
        {
            return false;
        }

        if ($this->getRetries() < 3)
        {
            return true;
        }

        $days = $this->getRetries() - 2;

        return $this->isOlderThanDays($days);
    }

    protected function isForSingleOrder()
    {
        return (!empty($this->getOrderIncrementId()) && !$this->isForMultipleOrders());
    }

    protected function isForMultipleOrders()
    {
        $incrementId = $this->getOrderIncrementId();

        if (empty($incrementId))
            return false;

        if (strpos($incrementId, ",") === false)
            return false;

        return true;
    }

    protected function findStripeClientByOrder()
    {
        if ($this->isForSingleOrder())
        {
            $orderIncrementId = $this->getOrderIncrementId();
            $order = $this->orderHelper->loadOrderByIncrementId($orderIncrementId);

            if ($order && $order->getStoreId())
            {
                $this->config->reInitStripeFromStoreId($order->getStoreId());
                return $this->config->getStripeClient();
            }
        }
        else if ($this->isForMultipleOrders())
        {
            $orderIncrementIds = explode(",", $this->getOrderIncrementId());
            foreach ($orderIncrementIds as $orderIncrementId)
            {
                $order = $this->orderHelper->loadOrderByIncrementId($orderIncrementId);
                if ($order && $order->getStoreId())
                {
                    $this->config->reInitStripeFromStoreId($order->getStoreId());
                    return $this->config->getStripeClient();
                }
            }
        }

        throw new GenericException("Stripe client not found.");
    }

    protected function findEventAcrossAllAccounts()
    {
        $keys = $this->config->getAllAPIKeys();

        foreach ($keys as $secretKey => $publicKey)
        {
            $this->config->initStripeFromSecretKey($secretKey);

            try
            {
                return $this->config->getStripeClient()->events->retrieve($this->getEventId(), []);
            }
            catch (\Exception $e)
            {

            }
        }

        throw new GenericException("Event not found across any configured Stripe accounts.");
    }
}
