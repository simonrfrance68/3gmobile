define(
    [
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/checkout-data',
        'Bootsgrid_Worldpay/js/action/set-payment-method-action'
    ],
    function (ko, Component, selectPaymentMethodAction, checkoutData, setPaymentMethodAction) {
        'use strict';
 
        return Component.extend({
            defaults: {
                redirectAfterPlaceOrder: false,
                template: 'Bootsgrid_Worldpay/payment/worldpay'
            },
            afterPlaceOrder: function () {
                setPaymentMethodAction(this.messageContainer);
                return false;
            },
            selectPaymentMethod: function() {
                selectPaymentMethodAction(this.getData());
                checkoutData.setSelectedPaymentMethod(this.item.method);
                return true;
            },
            getCode: function() {
                return 'worldpay_cc';
            },
            isActive: function() {
                return true;
            },
            getData: function () {
                return {
                    'method': this.getCode()
                };
            },
        });
    }
);