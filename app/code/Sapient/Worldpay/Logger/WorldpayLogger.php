<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\Worldpay\Logger;

use Monolog\DateTimeImmutable;

class WorldpayLogger extends \Monolog\Logger
{
    public function addRecord(int $level, string $message, array $context = [], ?DateTimeImmutable $datetime = null): bool
    {
        $ObjectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $logEnabled = (bool) $ObjectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')
                                ->getValue('worldpay/general_config/enable_logging');
        if ($logEnabled) {
            return parent::addRecord($level, $message, $context, $datetime);
        }

        // If logging is disabled, still return a boolean as per the new signature.
        return false;
    }
}
