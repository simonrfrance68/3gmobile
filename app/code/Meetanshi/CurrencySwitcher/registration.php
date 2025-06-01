<?php
use \Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Meetanshi_CurrencySwitcher', __DIR__);

if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'view/adminhtml/web/css/License/License.php')) {
    include_once(__DIR__ . DIRECTORY_SEPARATOR . 'view/adminhtml/web/css/License/License.php');
}
