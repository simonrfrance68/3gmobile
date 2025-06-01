<?php

namespace StripeIntegration\Payments\Helper\Stripe;

class CheckoutSession
{
    private $config;
    private $quoteHelper;
    private $checkoutSessionFactory;
    private $tokenHelper;

    public function __construct(
        \StripeIntegration\Payments\Model\Config $config,
        \StripeIntegration\Payments\Model\CheckoutSessionFactory $checkoutSessionFactory,
        \StripeIntegration\Payments\Helper\Quote $quoteHelper,
        \StripeIntegration\Payments\Helper\Token $tokenHelper
    )
    {
        $this->config = $config;
        $this->checkoutSessionFactory = $checkoutSessionFactory;
        $this->quoteHelper = $quoteHelper;
        $this->tokenHelper = $tokenHelper;
    }

    public function getCheckoutSessionIdFromQuote($quote)
    {
        if (empty($quote) || empty($quote->getId()))
            return null;

        $checkoutSession = $this->checkoutSessionFactory->create()->load($quote->getId(), 'quote_id');

        return $checkoutSession->getCheckoutSessionId();
    }

    public function getPaymentIntentUpdateParams($params, $paymentIntent, $filterParams = [])
    {
        $updateParams = [];
        $allowedParams = ["amount", "currency", "description", "metadata"];

        foreach ($allowedParams as $key)
        {
            if (!empty($filterParams) && !in_array($key, $filterParams))
                continue;

            if (isset($params[$key]))
                $updateParams[$key] = $params[$key];
        }

        if (!empty($updateParams["amount"]) && $updateParams["amount"] == $paymentIntent->amount)
            unset($updateParams["amount"]);

        if (!empty($updateParams["currency"]) && $updateParams["currency"] == $paymentIntent->currency)
            unset($updateParams["currency"]);

        return $updateParams;
    }

    public function getLastTransactionId(\Magento\Payment\Model\InfoInterface $payment)
    {
        if ($payment->getLastTransId())
            return $this->tokenHelper->cleanToken($payment->getLastTransId());

        if ($payment->getAdditionalInformation("checkout_session_id"))
        {
            $csId = $payment->getAdditionalInformation("checkout_session_id");
            $cs = $this->config->getStripeClient()->checkout->sessions->retrieve($csId, ['expand' => ['payment_intent', 'subscription']]);
            if (!empty($cs->payment_intent->id))
                return $cs->payment_intent->id;
        }

        return null;
    }
}
