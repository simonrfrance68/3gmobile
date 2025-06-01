define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'worldpay_cc',
                component: 'Bootsgrid_Worldpay/js/view/payment/method-renderer/worldpay'
            }
        );
        return Component.extend({});
    }
);