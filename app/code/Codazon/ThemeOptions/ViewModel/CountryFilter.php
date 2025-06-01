<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Codazon\ThemeOptions\ViewModel;

if (class_exists('\Magento\Config\ViewModel\CountryFilter')) {
    class MiddleManCountryFilter extends \Magento\Config\ViewModel\CountryFilter { }
} else {
    class MiddleManCountryFilter implements \Magento\Framework\View\Element\Block\ArgumentInterface { }
}

class CountryFilter extends MiddleManCountryFilter {
    // The class code here
}