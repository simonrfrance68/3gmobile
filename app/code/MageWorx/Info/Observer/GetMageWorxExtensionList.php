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

class GetMageWorxExtensionList implements ObserverInterface
{
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
     * @param Data $helper
     * @param Session $backendSession
     */
    public function __construct(
        Data    $helper,
        Session $backendSession
    ) {
        $this->helper         = $helper;
        $this->backendSession = $backendSession;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if ($this->backendSession->isLoggedIn() && $this->helper->isExtensionInfoAutoloadEnabled()) {
            $this->helper->checkExtensionListUpdate();
        }
    }
}
