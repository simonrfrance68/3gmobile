define(['jquery'], function ($) {
    'use strict';

    return function (configurable) {
        $.widget('mage.configurable', $.mage.configurable, {
            _create: function () {
                var wrap = '.quickshop-modal ';
                if($(wrap + this.options.superSelector).length){
                    this.options.superSelector = wrap + this.options.superSelector;
                    this.options.selectSimpleProduct = wrap + this.options.selectSimpleProduct;
                    this.options.priceHolderSelector = wrap + this.options.priceHolderSelector;
                    this.options.normalPriceLabelSelector = wrap + this.options.normalPriceLabelSelector;
                    this.options.tierPriceTemplateSelector = wrap + this.options.tierPriceTemplateSelector;
                    this.options.tierPriceBlockSelector = wrap + this.options.tierPriceBlockSelector;
                    this.options.slyOldPriceSelector = wrap + this.options.slyOldPriceSelector;
                    this.options.mediaGallerySelector = wrap + this.options.mediaGallerySelector;
                }
                this._super();
            }
        });
        return $.mage.configurable;
    };
});