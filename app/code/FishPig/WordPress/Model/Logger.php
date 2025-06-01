<?php
/*
 *
 */
namespace FishPig\WordPress\Model;

use Monolog\DateTimeImmutable;

class Logger extends \Monolog\Logger
{
    /*
     * Extended to add in calling object data to context array
     */
    public function addRecord(int $level, string $message, array $context = [], ?DateTimeImmutable $datetime = null): bool
    {
        if ($backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)) {
            $context['backtrace'] = array_pop($backtrace);
        }

        return parent::addRecord($level, $message, $context, $datetime);
    }
}
