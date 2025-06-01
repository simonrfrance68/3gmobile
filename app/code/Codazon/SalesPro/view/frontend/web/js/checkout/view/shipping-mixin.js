/**
 * @copyright  Codazon. All rights reserved.
 * @author     Nicolas
 */
define([
    'jquery',
    'Magento_Checkout/js/model/quote',
    'uiRegistry',
    'Magento_Checkout/js/model/step-navigator',
    'mage/translate',
    'ko',
    'Magento_Checkout/js/model/checkout-data-resolver',
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/action/get-payment-information',
    'Magento_Checkout/js/checkout-data',
    'Magento_Customer/js/model/address-list',
    'Magento_Checkout/js/action/create-shipping-address',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/action/create-billing-address',
    'Magento_Checkout/js/action/select-billing-address',
    'Magento_Ui/js/lib/view/utils/async'
], function ($, quote, registry, stepNavigator, $t, ko, checkoutDataResolver, addressConverter, getPaymentInformation, checkoutData, addressList,
createShippingAddress, selectShippingAddress, createBillingAddress, selectBillingAddress) {
    'use strict';
    var widget;
    return function (Component) {
        return Component.extend({
            defaults: {
                template: 'Codazon_SalesPro/checkout/shipping'
            },
            initialize: function () {
                var self = this;
                this.ingoreValidationMessage = true;
                this._adjustFunctions();
                this._super();
                widget = this;
                this._prepareData();
                return this;
            },
            _prepareData: function() {
                var self = this;
                $(window).on('refreshShippingInfomation', function () {
                    widget.setShippingInformation();
                });
                this.prepareFormEvents();
                
            },
            _adjustFunctions: function () {
                stepNavigator.setHash = function (hash) {
                    window.location.hash = '';
                };
                stepNavigator.oldIsProcessed = stepNavigator.isProcessed;
                stepNavigator.isProcessed = function (code) {
                    if (code == 'shipping') {
                        return true;
                    } else {
                        stepNavigator.oldIsProcessed(code);
                    }
                }
            },
            /* visible: function() {
                return (!quote.isVirtual());
            }, */
            canDisplayed: function() {
                return (!quote.isVirtual());
            },
            selectShippingMethod: function (shippingMethod) {
                this._super(shippingMethod);
                widget.setShippingInformation();
                return true;
            },
            hasShippingMethod: function () {
                return window.checkoutConfig.selectedShippingMethod !== null;
            },
            saveNewAddress: function() {
                this._super();
                widget.setShippingInformation();
            },
            collectAddress: function () {
                this.source.set('params.invalid', false);
                var addressData = this.source.get('shippingAddress');
                addressData['save_in_address_book'] = this.saveInAddressBook ? 1 : 0;
                var newShippingAddress = createShippingAddress(addressData);
                selectShippingAddress(newShippingAddress);
                console.log(quote.billingAddress());
                if (!quote.billingAddress()) {
                    selectBillingAddress(createBillingAddress(addressData));
                    console.log(quote.billingAddress());
                }
            },
            prepareFormEvents: function() {
                var self = this, t, refreshShipInfo = () => {
                    if (!$('.action-select-shipping-item').length) {
                        if (!widget.validateShippingInformation()) {
                            widget.collectAddress();
                        }
                        if (t) clearTimeout(t);
                        t = setTimeout(() => $(window).trigger('refreshShippingInfomation'), 50);
                    } else {
                        $(window).trigger('refreshShippingInfomation');
                    }
                };
                registry.async('checkoutProvider')(function (checkoutProvider) {
                    if (self.visible()) {
                        var it = setInterval(function() {
                            var $shippingForm = $('#co-shipping-form');
                            if ($shippingForm.length) {
                                clearInterval(it);
                                $('form[data-role=email-with-possible-login] input[name=username]').on('change', refreshShipInfo);
                                $shippingForm.on('change', 'input,select', refreshShipInfo);
                            }
                        }, 100);
                    }
                });
            },
            validateShippingInformation: function() {
                if (window.noValidateShippingAddress) {
                    return true;
                } else {
                    return this._super();
                }
            }
        });
    };
});