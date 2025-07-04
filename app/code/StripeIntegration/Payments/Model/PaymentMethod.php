<?php

namespace StripeIntegration\Payments\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use StripeIntegration\Payments\Exception\GenericException;
use StripeIntegration\Payments\Exception\RefundOfflineException;

class PaymentMethod extends \Magento\Payment\Model\Method\Adapter
{
    private $config;
    private $paymentElement;
    private $paymentIntent;
    private $multishippingHelper;
    private $refundsHelper;
    private $subscriptionsHelper;
    private $helper;
    private $stripePaymentMethod;
    private $api;
    private $paymentIntentHelper;
    private $tokenHelper;
    private $setupIntentHelper;
    private $quoteHelper;
    private $orderHelper;
    private $checkoutFlow;

    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Payment\Gateway\Config\ValueHandlerPoolInterface $valueHandlerPool,
        \Magento\Payment\Gateway\Data\PaymentDataObjectFactory $paymentDataObjectFactory,
        string $code,
        string $formBlockType,
        string $infoBlockType,
        \StripeIntegration\Payments\Model\Config $config,
        \StripeIntegration\Payments\Model\PaymentElement $paymentElement,
        \StripeIntegration\Payments\Model\PaymentIntent $paymentIntent,
        \StripeIntegration\Payments\Model\Stripe\PaymentMethod $stripePaymentMethod,
        \StripeIntegration\Payments\Model\Checkout\Flow $checkoutFlow,
        \StripeIntegration\Payments\Helper\Generic $helper,
        \StripeIntegration\Payments\Helper\Subscriptions $subscriptionsHelper,
        \StripeIntegration\Payments\Helper\Multishipping $multishippingHelper,
        \StripeIntegration\Payments\Helper\Refunds $refundsHelper,
        \StripeIntegration\Payments\Helper\Api $api,
        \StripeIntegration\Payments\Helper\PaymentIntent $paymentIntentHelper,
        \StripeIntegration\Payments\Helper\Token $tokenHelper,
        \StripeIntegration\Payments\Helper\SetupIntent $setupIntentHelper,
        \StripeIntegration\Payments\Helper\Quote $quoteHelper,
        \StripeIntegration\Payments\Helper\Order $orderHelper,
        \Magento\Payment\Gateway\Command\CommandPoolInterface $commandPool = null,
        \Magento\Payment\Gateway\Validator\ValidatorPoolInterface $validatorPool = null
    ) {
        $this->config = $config;
        $this->paymentElement = $paymentElement;
        $this->paymentIntent = $paymentIntent;
        $this->stripePaymentMethod = $stripePaymentMethod;
        $this->helper = $helper;
        $this->subscriptionsHelper = $subscriptionsHelper;
        $this->multishippingHelper = $multishippingHelper;
        $this->refundsHelper = $refundsHelper;
        $this->api = $api;
        $this->paymentIntentHelper = $paymentIntentHelper;
        $this->tokenHelper = $tokenHelper;
        $this->setupIntentHelper = $setupIntentHelper;
        $this->quoteHelper = $quoteHelper;
        $this->orderHelper = $orderHelper;
        $this->checkoutFlow = $checkoutFlow;

        if ($this->helper->isMultiShipping())
            $formBlockType = 'StripeIntegration\Payments\Block\Multishipping\Billing';
        else if ($this->helper->isAdmin())
            $formBlockType = 'StripeIntegration\Payments\Block\Adminhtml\Payment\Form';
        else
            $formBlockType = 'Magento\Payment\Block\Form';

        parent::__construct(
            $eventManager,
            $valueHandlerPool,
            $paymentDataObjectFactory,
            $code,
            $formBlockType,
            $infoBlockType,
            $commandPool,
            $validatorPool
        );
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        if ($this->config->getIsStripeAPIKeyError())
            $this->helper->throwError("Invalid API key provided");

        $additionalData = $data->getAdditionalData();

        $info = $this->getInfoInstance();

        $this->helper->assignPaymentData($info, $additionalData);

        return $this;
    }

    public function checkIfCartIsSupported(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->subscriptionsHelper->hasTrialSubscriptions())
            return;

        if ($this->subscriptionsHelper->isZeroAmountOrder($payment->getOrder()))
            return;

        if ($payment->getOrder()->getDiscountAmount() < 0)
        {
            // The cart includes both trial subscriptions and regular products. In the case of expiring discounts, the full discount will be applied
            // on the initial payment which is incorrect. We need to apply only the discount which applies to the regular product, without applying
            // the discount that applies on the trial subscription. Currently this is not supported.
            if ($this->subscriptionsHelper->hasExpiringDiscountCoupons())
            {
                throw new LocalizedException(__("Limited-time discount coupons cannot be applied on carts that include both regular products and trial subscriptions."));
            }
        }
    }

    public function order(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $customer = $this->helper->getCustomerModel();
        $customer->createStripeCustomerIfNotExists(false, $payment->getOrder());

        $createParams = $this->setupIntentHelper->getCreateParams($payment->getOrder());
        $confirmParams = $this->setupIntentHelper->getConfirmParams($payment->getOrder());

        // Load the setup intent from the customer session
        $setupIntentId = $this->helper->getCheckoutSession()->getStripeSetupIntentId();
        if (!$setupIntentId)
        {
            $setupIntent = $this->config->getStripeClient()->setupIntents->create($createParams);
        }
        else
        {
            $setupIntent = $this->config->getStripeClient()->setupIntents->retrieve($setupIntentId, []);
            $this->helper->getCheckoutSession()->setStripeSetupIntentId(null);

            if ($setupIntent->status == "requires_confirmation" || $setupIntent->status == "requires_payment_method")
            {
                $setupIntent = $this->config->getStripeClient()->setupIntents->confirm($setupIntentId, $confirmParams);
            }
        }

        if ($setupIntent->status == "requires_action")
        {
            // Save the setup intent ID in the customer session
            $this->helper->getCheckoutSession()->setStripeSetupIntentId($setupIntent->id);
            return $this->helper->throwError("Authentication Required: " . $setupIntent->client_secret);
        }
        else if ($setupIntent->status == "canceled")
        {
            return $this->helper->throwError("The payment method could not be saved. Please try again.");
        }
        else if (in_array($setupIntent->status, ["succeeded", "processing"]))
        {
            // Processing or succeeded status
            $payment->setAdditionalInformation("customer_stripe_id", $customer->getStripeId());
            $payment->setAdditionalInformation("payment_action", $this->config->getPaymentAction());

            // If the order was placed with a confirmation token, switch things around to a normal PM token
            $payment->setAdditionalInformation("token", $setupIntent->payment_method);
            $payment->setAdditionalInformation("confirmation_token", true);
        }
        else
        {
            throw new GenericException(__("Something went wrong. Please try again."));
        }

        return $this;
    }

    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->checkIfCartIsSupported($payment, $amount);

        if ($amount > 0)
        {
            if ($this->subscriptionsHelper->isSubscriptionUpdate())
            {
                $this->subscriptionsHelper->updateSubscription($payment);
            }
            else if ($this->helper->isMultiShipping())
            {
                $this->doNotPay($payment);
            }
            else if ($payment->getAdditionalInformation('is_migrated_subscription'))
            {
                $this->doNotPay($payment);
            }
            else
            {
                $this->pay($payment, $amount);
            }
        }

        return $this;
    }

    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->checkIfCartIsSupported($payment, $amount);

        if ($amount > 0)
        {
            // We get in here when the store is configured in Authorize Only mode and we are capturing a payment from the admin
            $token = $payment->getTransactionId();
            if (empty($token))
            {
                $token = $payment->getLastTransId(); // In case where the transaction was not created during the checkout, i.e. with a Stripe Webhook redirect
            }

            if ($payment->getAdditionalInformation('payment_action') == "order")
            {
                $this->api->createNewCharge($payment, $amount);
            }
            else if ($token)
            {
                // Capture an authorized payment from the admin area

                $token = $this->tokenHelper->cleanToken($token);

                $orders = $this->helper->getOrdersByTransactionId($token);
                $quoteId = (($payment->getOrder() && $payment->getOrder()->getQuoteId()) ? $payment->getOrder()->getQuoteId() : null);
                if ($this->multishippingHelper->isMultishippingQuote($quoteId))
                {
                    if (count($orders) > 1)
                    {
                        $this->multishippingHelper->captureOrdersFromAdminArea($orders, $token, $payment, $amount, $this->config->retryWithSavedCard());
                    }
                    else
                    {
                        return $this->helper->throwError(__("This order cannot be captured because no transactions have been recorded against it."));
                    }
                }
                else
                {
                    $this->helper->capture($token, $payment, $amount, $this->config->retryWithSavedCard());
                }
            }
            else if ($payment->getAdditionalInformation('is_migrated_subscription'))
            {
                return $this->helper->throwError(__("It is not possible to capture subscription orders that were created from the CLI."));
            }
            else if ($this->helper->isAdmin() && $payment->getOrder()->getState() == "pending_payment")
            {
                return $this->helper->throwError(__("It is not possible to capture the payment because the transaction has not yet been authorized."));
            }
            else if ($this->subscriptionsHelper->isSubscriptionUpdate())
            {
                $this->subscriptionsHelper->updateSubscription($payment);
            }
            else if ($this->helper->isMultiShipping())
            {
                $this->doNotPay($payment);
            }
            else
            {
                $this->pay($payment, $amount);
            }
        }

        return $this;
    }

    public function doNotPay(\Magento\Payment\Model\InfoInterface $payment)
    {
        $payment->setIsFraudDetected(false);
        $payment->setIsTransactionPending(true); // not authorized yet
        $payment->setIsTransactionClosed(false); // not captured
        $payment->getOrder()->setCanSendNewEmailFlag(false);
    }

    public function pay(InfoInterface $payment, $amount)
    {
        if ($payment->getAdditionalInformation("is_recurring_subscription"))
            return $this;

        if (!$payment->getAdditionalInformation("token") && !$payment->getAdditionalInformation("confirmation_token"))
            return $this->helper->throwError(__("Cannot place order because a payment method was not provided."));

        $order = $payment->getOrder();

        try
        {
            // Update the payment intent by loading it from cache - the load method with update it if its different.
            $this->paymentElement->fromQuoteId($order->getQuoteId());
            $this->paymentElement->updateFromOrder($order);
            $this->paymentElement->cancelInvalidOrders($order);

            $result = $this->paymentElement->confirm($order);

            if (!empty($result->client_secret)) // Trial subscriptions will not have a client secret
            {
                $payment->setAdditionalInformation("client_secret", $result->client_secret);
            }
        }
        catch (\Exception $e)
        {
            $this->helper->sendPaymentFailedEmail($this->quoteHelper->getQuote(), $e->getMessage());
            throw $e;
        }

        if ($this->checkoutFlow->isPendingMicrodepositsVerification)
        {
            if ($this->tokenHelper->isSetupIntentToken($result->id))
            {
                $this->paymentIntent->processPendingOrder($order, $result);
            }
            else
            {
                throw new GenericException(__("Something went wrong. Please contact us for assistance."));
            }
        }
        else if ($this->paymentIntent->requiresAction($result))
        {
            if ($this->helper->isAdmin())
            {
                return $this->helper->throwError(__("This payment method cannot be used because it requires a customer authentication. To avoid authentication in the admin area, please contact Stripe support to request access to the MOTO gate for your Stripe account."));
            }

            if ($this->shouldAuthenticateManually($result))
            {
                // Certain versions of Magento such as 2.4.4 and 2.4.6 cause order increment ID skipping after a payment failure.
                // We save the quote so that the order increment ID is saved. We intentionally do not use the quotes repository,
                // because that triggers a bug in older versions of Magento (2.4.2), where configurable products with a QTY of 1
                // would fail order placement with an error that the product quantity is not available.
                $this->quoteHelper->getQuote()->save();

                return $this->helper->throwError("Authentication Required: {$result->client_secret}");
            }

            $this->paymentIntent->processPendingOrder($order, $result);
        }
        else if ($this->paymentElement->isTrialSubscription())
        {
            $this->paymentIntent->processTrialSubscriptionOrder($order, $this->paymentElement->getSubscription());
        }
        else if ($this->paymentElement->getPaymentIntent())
        {
            if ($this->paymentIntentHelper->isSuccessful($result))
            {
                $this->paymentIntent->processSuccessfulOrder($order, $result);
            }
            else
            {
                $this->paymentIntent->processPendingOrder($order, $result);
            }
            $payment->setAdditionalInformation("server_side_transaction_id", $result->id);
        }
        else if ($this->checkoutFlow->isFutureSubscriptionSetup)
        {
            // The subscription starts at a future date
            if ($this->tokenHelper->isSubscriptionToken($result->id))
            {
                $this->paymentIntent->processFutureSubscriptionOrder($order, $result->customer, $result->id);
            }
            else if ($this->tokenHelper->isSetupIntentToken($result->id))
            {
                $this->paymentIntent->processFutureSubscriptionOrder($order, $result->customer, $this->paymentElement->getSubscriptionId());
            }
            else
            {
                throw new GenericException(__("Something went wrong. Please contact us for assistance."));
            }
        }
        else if ($this->paymentElement->getSetupIntent())
        {
            $this->paymentIntent->processPendingOrder($order, $result);
        }
    }

    private function shouldAuthenticateManually($intent)
    {
        $methods = $this->config->getManualAuthenticationPaymentMethods();

        if (!empty($intent->payment_method) && is_string($intent->payment_method))
        {
            $paymentMethod = $this->stripePaymentMethod->fromPaymentMethodId($intent->payment_method)->getStripeObject();

            if (in_array($paymentMethod->type, $methods))
            {
                return true;
            }
        }

        return false;
    }

    public function cancel(InfoInterface $payment, $amount = null)
    {
        if ($payment->getCancelOfflineWithComment())
        {
            $this->helper->overrideCancelActionComment($payment, $payment->getCancelOfflineWithComment());
            return $this;
        }

        try
        {
            $paymentIntentId = $this->refundsHelper->getTransactionId($payment);
            $paymentIntent = $this->config->getStripeClient()->paymentIntents->retrieve($paymentIntentId, []);

            if ($this->multishippingHelper->isMultishippingPayment($paymentIntent) && $paymentIntent->status == "requires_capture")
            {
                $this->refundsHelper->refundMultishipping($paymentIntent, $payment, $amount);
            }
            else
            {
                $this->refundsHelper->refund($payment, $amount);
            }
        }
        catch (RefundOfflineException $e)
        {
            if ($this->helper->isAdmin())
            {
                $this->helper->addWarning($e->getMessage());
            }

            if ($this->refundsHelper->isCancelation($payment))
                $this->helper->overrideCancelActionComment($payment, $e->getMessage());
            else
                $this->orderHelper->addOrderComment($e->getMessage(), $payment->getOrder());
        }
        catch (\Exception $e)
        {
            $this->helper->throwError(__('Could not refund payment: %1', $e->getMessage()), $e);
        }

        return $this;
    }

    public function cancelInvoice($invoice)
    {
        return $this;
    }

    public function refund(InfoInterface $payment, $amount)
    {
        $this->cancel($payment, $amount);

        return $this;
    }

    public function void(InfoInterface $payment)
    {
        $this->cancel($payment);

        return $this;
    }

    public function acceptPayment(InfoInterface $payment)
    {
        return parent::acceptPayment($payment);
    }

    public function denyPayment(InfoInterface $payment)
    {
        return parent::denyPayment($payment);
    }

    public function canCapture()
    {
        $info = $this->getInfoInstance();
        if ($info)
        {
            $paymentAction = $info->getAdditionalInformation("payment_action");
            $token = $info->getAdditionalInformation("token");
            if ($paymentAction == "order" && !empty($token))
            {
                return true;
            }
        }
        return parent::canCapture();
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ($quote && $quote->getIsRecurringOrder())
            return true;

        if ($this->subscriptionsHelper->isSubscriptionUpdate())
            return true;

        if (!$this->config->isEnabled())
            return false;

        if ($this->helper->isAdmin())
            return parent::isAvailable($quote);

        if ($this->config->isRedirectPaymentFlow() && !$this->isExpressCheckout() && !$this->helper->isMultiShipping())
            return false;

        return parent::isAvailable($quote);
    }

    public function isExpressCheckout()
    {
        return $this->checkoutFlow->isExpressCheckout;
    }

    public function getConfigPaymentAction()
    {
        $info = $this->getInfoInstance();
        if ($info && $info->getAdditionalInformation("is_migrated_subscription") ||
            $this->subscriptionsHelper->isSubscriptionUpdate())
        {
            return 'authorize';
        }

        // Subscriptions do not support authorize only mode
        if ($this->subscriptionsHelper->hasSubscriptions())
        {
            return 'authorize_capture';
        }

        return $this->config->getPaymentAction();
    }

    public function canEdit()
    {
        $info = $this->getInfoInstance();

        if (!empty($info->getTransactionId()))
            return false;

        if (!empty($info->getLastTransId()))
            return false;

        if (empty($info->getAdditionalInformation("token")))
            return false;

        if (empty($info->getAdditionalInformation("customer_stripe_id")))
            return false;

        $token = $info->getAdditionalInformation("token");

        if (strpos($token, "pm_") !== 0)
            return false;

        return true;
    }

    protected function getConfig()
    {
        return $this->config;
    }
}
