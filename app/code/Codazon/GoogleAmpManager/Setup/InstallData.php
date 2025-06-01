<?php
/**
* Copyright © 2020 Codazon. All rights reserved.
* See COPYING.txt for license details.
*/

namespace Codazon\GoogleAmpManager\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface {
    
    private $setupFactory;
    
    private $importHelper;

    protected $_objectManager;

    protected $_state;
    protected $_importHelper;
    
    public function __construct(
        \Codazon\GoogleAmpManager\Setup\GoogleAmpManagerSetupFactory $setupFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->setupFactory = $setupFactory;
        $this->_objectManager = $objectManager;
    }
    
    
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        /* TO DO */
        $moduleSetup = $this->setupFactory->create(['setup' => $setup]);
        $moduleSetup->installEntities();
        
        $this->_state = $this->_objectManager->get(\Magento\Framework\App\State::class);
        $this->_state->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);
        /* import data */
        $this->_importHelper = $this->_objectManager->get(\Codazon\GoogleAmpManager\Helper\Import::class);
        $this->_importHelper->importData();
        
        $setup->endSetup();
    }
    
}