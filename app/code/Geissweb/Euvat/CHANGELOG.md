# Geissweb_Euvat for Magento 2
All notable changes to this project will be documented in this file.
### [1.5.0] 2019-11-22
#### Added
- Offline validation fallback with syntax check, applicable to selected countries
- Price display by customer group for "Cart Prices" and "Cart Subtotal"
#### Changed
- Some refactoring

### [1.4.1] 2019-10-21
#### Added
- Option to create tax rates (domestic rate) for non-EU countries while using the automatic setup
#### Fixed
- Country prefix autocomplete for Greece
- Fixed missing dependency for Magento\Tax\Model\Calculation

### [1.4.0.1] 2019-09-27
#### Fixed
- Compatibility with Magento 2.1.x and JSON decode

### [1.4.0] 2019-09-12
#### Added
- Option to always calculate VAT for selected countries, even with a valid VAT number
- Net VAT calculation works with threshold countries

### [1.3.11] 2019-08-16
#### Changed
- Admin validation on create order now uses store specific configuration
- Frontend AJAX validation now supports store code in URL properly

### [1.3.10] 2019-07-29
#### Added
- Checkout field visibility now respects address type of parentscope

### [1.3.9] 2019-07-18
#### Fixed
- Possible checkout error when company or vatid is missing on the address
- Possible group change confirm window loop

### [1.3.8] 2019-07-10
#### Changed
- Total reloading with Mageplaza Checkout
- Removed FIeldsetCompatibility class
- Read own version from composer.json
#### Fixed
- Issue with PayPal Captcha

### [1.3.7] 2019-07-02
#### Added
- VAT number validation data is saved to quote and order addresses

### [1.3.6.1] 2019-06-10
#### Changed
- Code cleanup

### [1.3.6] 2019-06-10
#### Changed
- Improved admin VAT validation on customer address edit page
- Totals reloading process at checkout

### [1.3.5.5] 2019-05-20
#### Changed
- Fixed issue with JS baseUrl

### [1.3.5.4] 2019-05-15
#### Changed
- Magento coding standard adjustments

#### Fixed
- Infinite loop when using dynamic shipping tax class

### [1.3.5.3] 2019-05-06
#### Changed
- Removed dependency on \Magento\Framework\App\Helper\AbstractHelper

### [1.3.5.2] 2019-04-30
#### Changed
- Use requester country from VAT number if present

### [1.3.5.1] 2019-04-08
#### Changed
- Compatibility with MagePlaza Checkout
- Compatibility with Magerun2, fixes customer:change-password command (Thanks to Alexander Menk)
- Magento_Checkout/js/model/cart/totals-processor/default::estimateTotals is executed after VAT number validation

### [1.3.5] 2019-03-31
#### Changed
- Adminhtml Customer Adress Edit VAT number validation (compatible with Magento 2.3.1)

### [1.3.4] 2019-02-07
#### Added
- Consideration of existing VAT number on cart estimate block
- Company name and VAT number rendering at all checkout addresses

#### Changed
- Compatibility with Amasty_Checkout 2.2.0

### [1.3.3] 2019-01-19
#### Fixed
- Fix for the Infinite Loop Fix

### [1.3.2] 2019-01-17
#### Added
- German Adminhtml Translation

#### Fixed
- Infinite Loop on Collect Totals

### [1.3.1] 2018-11-07
#### Added
- Solution for different tax rates on website level

#### Fixed
- Dynamic Shipping Tax

### [1.3.0] 2018-10-26
#### Added
- Feature to set catalog price display type per customer group

#### Fixed
- Performance improvement on the store configuration page

### [1.2.3] 2018-10-19
#### Fixed
- Issue on customer account save

### [1.2.2] 2018-10-04
#### Fixed
- VAT field visibility issue on Aheadworks Checkout

### [1.2.1] 2018-10-01
#### Fixed
- Issue at adminhtml_sales_order_create for M2.1
- Catalog price display excl. VAT

### [1.2.0] 2018-09-04
#### Added
- adminhtml_sales_order_create VAT number validation

#### Fixed
- Bug when changing store currency
- ACL issue

### [1.1.0] 2018-08-24
#### Fixed
- Issue with group assignment when customer creates the initial default address from customer account
- Issue with some themes not considering the JS path mappings correctly (use direct paths in JS components)

### [1.0.34] 2018-07-02
#### Fixed
- Bug with validation on Internet Explorer

#### Changed
- Ported checkout_submit_all_after event observer functions to a plugin

### [1.0.33] 2018-06-27
#### Changed
- Compatibility with Mageplaza Checkout

#### Added
- Support for admin orders with Cross-Border-Trade and Threshold countries
- Support for config checkout/options/display_billing_address_on (VAT validation on billing address at checkout)

### [1.0.32] 2018-06-19
#### Changed
- Compatibility with Amasty Checkout

### [1.0.31] 2018-06-06
#### Changed
- Field validation starts after configurable delay

### [1.0.30] 2018-05-29
#### Fixed
- Maintenance and minor Bugfixes

### [1.0.29] 2018-05-15
#### Added
- Possibility to revalidate VAT numbers on customer login within selectable periods

### [1.0.28] 2018-05-09
#### Added
- Possibility to disable dynamic tax class for specified customer groups

### [1.0.27] 2018-04-25
#### Added
- French translation

#### Changed
- Romania VAT rate to 19%
- Compatibility with Mageplaza Checkout

#### Fixed
- Empty response at validation

### [1.0.26] 2018-01-19
#### Fixed
- Adminhtml VAT fields

#### Added
- VAT number in checkout address renderer

### [1.0.25] 2018-01-12
#### Fixed
- AutoSetup product tax class mapping

#### Changed
- Country code is optional, if address country is present

### [1.0.24] 2017-12-21
#### Added 
- Compatibility with Aheadworks OneStepCheckout

#### Changed
- Mageplaza OSC compatibility

### [1.0.23] 2017-12-14
#### Added 
- New validation possibilities at Registration, Checkout and Customer Account (Address)

### [1.0.22] - 2017-11-22
#### Added
- Configurable debug logging
- Optional assignment of customer group on guest orders

#### Changed
- Reloading process for Magestore Checkout

### [1.0.21] - 2017-09-19
#### Changed
- Refactoring
- Threshold countries can now be all allowed countries

#### Fixed
- Minor Bugfixes


### [1.0.19] - 2017-08-03
#### Added
- Compatibility with Mageplaza Checkout

### [1.0.19] - 2017-07-25
#### Fixed
- Required VAT number at registration and address edit

### [1.0.18] - 2017-06-30
#### Changed
- Improved customer group assignment function

### [1.0.17] - 2017-06-28
#### Added
- Compatibility with OneStepCheckout (Amasty, Magestore)

### [1.0.16] - 2017-05-22
#### Added
- New shipping VAT algorithm allows to calculate the reduced VAT rate, if cart items have reduced VAT
- Support for Treshold countries when Cross-Border-Trade is used

### [1.0.15] - 2017-05-09
#### Fixed
- Several Bugfixes

#### Added
- Option to disable Cross-Border-Trade for EU customers with valid VAT number and for worldwide customers outside EU

### [1.0.0] - 2017-05-01
#### Added
- Initial Release
