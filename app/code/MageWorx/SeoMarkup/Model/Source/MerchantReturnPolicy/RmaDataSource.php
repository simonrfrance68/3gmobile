<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */

declare(strict_types = 1);

namespace MageWorx\SeoMarkup\Model\Source\MerchantReturnPolicy;

use Magento\Framework\Module\Manager;

class RmaDataSource extends \MageWorx\SeoMarkup\Model\Source
{
    const MAGENTO_RMA = 'magento';
    const CUSTOM_RMA  = 'custom';

    /**
     * @var ProductMetadataInterface
     */
    protected Manager $moduleManager;

    public function __construct(Manager $moduleManager)
    {
        $this->moduleManager = $moduleManager;
    }

    /**
     * {@inheritDoc}
     */
    public function toOptionArray(): array
    {
        $result = [
            ['value' => self::CUSTOM_RMA, 'label' => 'Use custom attribute']
        ];

        if ($this->moduleManager->isEnabled('Magento_Rma')) {
            $result[] = ['value' => self::MAGENTO_RMA, 'label' => __('Use Magento RMA settings')];
        }

        return $result;
    }
}
