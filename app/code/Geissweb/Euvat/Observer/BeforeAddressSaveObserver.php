<?php
/**
 * ||GEISSWEB| EU VAT Enhanced
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GEISSWEB End User License Agreement
 * that is available through the world-wide-web at this URL: https://www.geissweb.de/legal-information/eula
 *
 * DISCLAIMER
 *
 * Do not edit this file if you wish to update the extension in the future. If you wish to customize the extension
 * for your needs please refer to our support for more information.
 *
 * @package     Geissweb_Euvat
 * @copyright   Copyright (c) 2015-2019 GEISS Weblösungen (https://www.geissweb.de)
 * @license     https://www.geissweb.de/legal-information/eula GEISSWEB End User License Agreement
 */

namespace Geissweb\Euvat\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\Address;

/**
 * Customer Observer Model
 */
class BeforeAddressSaveObserver implements ObserverInterface
{
    /**
     * @var \Geissweb\Euvat\Model\ValidationRepository
     */
    public $validationRepository;

    /**
     * @var \Geissweb\Euvat\Logger\Logger
     */
    public $logger;

    /**
     * Constructor
     *
     * @param \Geissweb\Euvat\Model\ValidationRepository $validationRepository
     * @param \Geissweb\Euvat\Logger\Logger              $logger
     */
    public function __construct(
        \Geissweb\Euvat\Model\ValidationRepository $validationRepository,
        \Geissweb\Euvat\Logger\Logger $logger
    ) {
        $this->validationRepository = $validationRepository;
        $this->logger = $logger;
    }

    /**
     * Address before save event handler
     * Adds VAT number validation data from table to address
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->logger->debug("[BeforeAddressSaveObserver] START");

        /** @var $customerAddress Address */
        $customerAddress = $observer->getCustomerAddress();
        $vatId = $customerAddress->getVatId();

        if (!empty($vatId)) {

            $this->logger->debug("Searching for validation: $vatId");
            /** @var \Geissweb\Euvat\Api\Data\ValidationInterface $validation */
            $validation = $this->validationRepository->getByVatId($vatId);
            if ($validation) {
                $this->logger->debug("Applying validation data to address id: ".$customerAddress->getId());
                $customerAddress->setVatIsValid($validation->getVatIsValid());
                $customerAddress->setVatTraderName($validation->getVatTraderName());
                $customerAddress->setVatTraderAddress($validation->getVatTraderAddress());
                $customerAddress->setVatRequestSuccess($validation->getVatRequestSuccess());
                $customerAddress->setVatRequestDate($validation->getVatRequestDate());
                $customerAddress->setVatRequestId($validation->getVatRequestId());
            }

        } else {
            $this->logger->debug("No VAT number on address id: ".$customerAddress->getId());
            $customerAddress->setVatIsValid();
            $customerAddress->setVatTraderName();
            $customerAddress->setVatTraderAddress();
            $customerAddress->setVatRequestSuccess();
            $customerAddress->setVatRequestDate();
            $customerAddress->setVatRequestId();
        }

        $this->logger->debug("[BeforeAddressSaveObserver] END");
    }
}
