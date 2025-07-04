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

namespace Geissweb\Euvat\Model\Setup;

/**
 * Class DataProvider
 */
class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * Form element mapping
     *
     * @var array
     */
    protected $formElement = [
        'text' => 'input',
        'hidden' => 'input',
        'boolean' => 'checkbox',
    ];

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        return [];
    }

    /**
     * @param \Magento\Framework\Api\Filter $filter
     *
     * @return void
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        return;
    }
}
