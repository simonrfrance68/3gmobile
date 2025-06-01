define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/full-screen-loader',
        'mage/url'
    ],
    function ($, quote, urlBuilder, storage, errorProcessor, customer, fullScreenLoader, url) {
        'use strict';
        return function (messageContainer) {
            $.mage.redirect(url.build('bworldpay/payment/redirect')); //url is your url
        };
    }
);