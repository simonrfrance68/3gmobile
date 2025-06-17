<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

/**
 * SEO templates converter helper
 *
 */

namespace MageWorx\SeoXTemplates\Helper;

class Converter extends \Magento\Framework\App\Helper\AbstractHelper
{
    const STATIC_VALUE_DELIMITER = '||';

    /**
     * @param string $rawValue
     * @return string
     */
    public function randomizePrefix($rawValue)
    {
        return $this->randomize($rawValue);
    }

    /**
     * @param $rawValue
     * @return string
     */
    public function randomize($rawValue)
    {
        if (strpos($rawValue, self::STATIC_VALUE_DELIMITER) === false) {
            return $rawValue;
        }

        $lValue         = ltrim($rawValue);
        $leftSpaceCount = strlen($rawValue) - strlen($lValue);

        $rValue          = rtrim($rawValue);
        $rightSpaceCount = strlen($rawValue) - strlen($rValue);

        $trimValue = trim($rawValue);

        $values = explode(self::STATIC_VALUE_DELIMITER, $trimValue);
        $value  = str_repeat(' ', $leftSpaceCount) . $values[array_rand($values)] . str_repeat(' ', $rightSpaceCount);

        return $value;
    }

    /**
     * @param string $rawValue
     * @return string
     */
    public function randomizeSuffix($rawValue)
    {
        return $this->randomize($rawValue);
    }

    /**
     * @param string $data
     * @return string
     */
    public function stripTags(string $data): string
    {
        $data = preg_replace("'<style[^>]*?>.*?</style>'si", "", $data);

        return strip_tags($data);
    }
}
