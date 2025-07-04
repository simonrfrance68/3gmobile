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

namespace Geissweb\Euvat\Setup;

use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Class InstallData
 * @package Geissweb\Euvat\Setup
 */
class InstallData implements InstallDataInterface
{
    /**
     * @var \Magento\Customer\Setup\CustomerSetupFactory
     */
    public $customerSetupFactory;

    /**
     * @var \Magento\Eav\Model\AttributeRepository
     */
    //public $attributeRepository;

    /**
     * Init
     * @param \Magento\Customer\Setup\CustomerSetupFactory $customerSetupFactory
     */
    public function __construct(
        CustomerSetupFactory $customerSetupFactory
        //\Magento\Eav\Model\AttributeRepository $attributeRepository
    ) {
        $this->customerSetupFactory = $customerSetupFactory;
        //$this->attributeRepository = $attributeRepository;
    }

    /**
     * Installs DB schema for a module
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        /** @var \Magento\Customer\Setup\CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);

        $attributes = $this->getAttributesInfo();
        // Remove for testing
        //foreach ($attributes as $attributeCode => $attributeParams) {
        //    $customerSetup->removeAttribute('customer_address', $attributeCode);
        //}
        // Add attributes
        foreach ($attributes as $attributeCode => $attributeParams) {
            $customerSetup->addAttribute('customer_address', $attributeCode, $attributeParams);
        }
        $setup->endSetup();
    }

    /**
     * @return array
     */
    private function getAttributesInfo()
    {
        return [
            'vat_trader_name' => [
                'label' => 'VAT number company name',
                'type' => 'static',
                'input' => 'text',
                'required' => false,
                'position' => 150,
                'visible' => true,
                'system' => 0,
                'user_defined' => true,
                'is_user_defined' => 1,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'is_searchable_in_grid' => false,
            ],
            'vat_trader_address' => [
                'label' => 'VAT number company address',
                'type' => 'static',
                'input' => 'textarea',
                'required' => false,
                'position' => 160,
                'visible' => true,
                'system' => 0,
                'user_defined' => true,
                'is_user_defined' => 1,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'is_searchable_in_grid' => false,
            ],
        ];
    }
}
