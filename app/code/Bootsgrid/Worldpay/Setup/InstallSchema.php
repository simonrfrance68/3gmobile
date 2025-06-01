<?php 

namespace Bootsgrid\Worldpay\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface {

    /**
     * Installs DB schema for a module
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context) {
        

    }
}