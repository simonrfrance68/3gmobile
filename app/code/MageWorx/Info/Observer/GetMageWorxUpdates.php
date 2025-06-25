<?php
/**
 * Copyright ©  MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\Info\Observer;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MageWorx\Info\Helper\Data;
use MageWorx\Info\Model\OffersFeed;
use MageWorx\Info\Model\OffersFeedFactory;
use MageWorx\Info\Model\UpdatesFeedFactory;

class GetMageWorxUpdates implements ObserverInterface
{
    /**
     * @var OffersFeedFactory
     */
    protected $feedFactory;

    /**
     * @var Session
     */
    protected $backendSession;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * GetMageWorxOffers constructor.
     *
     * @param UpdatesFeedFactory $feedFactory
     * @param Data $helper
     * @param Session $backendSession
     */
    public function __construct(
        UpdatesFeedFactory $feedFactory,
        Data               $helper,
        Session            $backendSession
    ) {
        $this->feedFactory    = $feedFactory;
        $this->helper         = $helper;
        $this->backendSession = $backendSession;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if ($this->backendSession->isLoggedIn()
            && $this->helper->isNotificationExtensionEnabled()
            && $this->helper->isUpdatesNotificationEnabled()
        ) {
            $feedModel = $this->feedFactory->create();
            /* @var $feedModel OffersFeed */
            $feedModel->checkUpdate();
        }
    }
}
