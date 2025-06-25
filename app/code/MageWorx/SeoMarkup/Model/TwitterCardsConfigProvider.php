<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types = 1);

namespace MageWorx\SeoMarkup\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class TwitterCardsConfigProvider
{
    const XML_CONFIG_PATH_ENABLED_FOR_PRODUCT       = 'mageworx_seo/markup/tw_cards/enabled_for_product';
    const XML_CONFIG_PATH_PRODUCT_TITLE_CODE        = 'mageworx_seo/markup/tw_cards/product_title_code';
    const XML_CONFIG_PATH_PRODUCT_DESCRIPTION_CODE  = 'mageworx_seo/markup/tw_cards/product_description_code';
    const XML_CONFIG_PATH_CROP_PRODUCT_DESCRIPTION  = 'mageworx_seo/markup/tw_cards/crop_product_description';
    const XML_CONFIG_PATH_ENABLED_FOR_CATEGORY      = 'mageworx_seo/markup/tw_cards/enabled_for_category';
    const XML_CONFIG_PATH_CATEGORY_TITLE_CODE       = 'mageworx_seo/markup/tw_cards/category_title_code';
    const XML_CONFIG_PATH_CATEGORY_DESCRIPTION_CODE = 'mageworx_seo/markup/tw_cards/category_description_code';
    const XML_CONFIG_PATH_CROP_CATEGORY_DESCRIPTION = 'mageworx_seo/markup/tw_cards/crop_category_description';
    const XML_CONFIG_PATH_ENABLED_FOR_PAGE          = 'mageworx_seo/markup/tw_cards/enabled_for_page';
    const XML_CONFIG_PATH_PAGE_TITLE_CODE           = 'mageworx_seo/markup/tw_cards/page_title_code';
    const XML_CONFIG_PATH_PAGE_DESCRIPTION_CODE     = 'mageworx_seo/markup/tw_cards/page_description_code';
    const XML_CONFIG_PATH_ENABLED_FOR_HOME_PAGE     = 'mageworx_seo/markup/tw_cards/enabled_for_home_page';
    const XML_CONFIG_PATH_USERNAME                  = 'mageworx_seo/markup/tw_cards/username';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * TwitterCardsConfigProvider constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabledForProduct(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_CONFIG_PATH_ENABLED_FOR_PRODUCT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getProductTitleCode(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_CONFIG_PATH_PRODUCT_TITLE_CODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getProductDescriptionCode(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_CONFIG_PATH_PRODUCT_DESCRIPTION_CODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getCropProductDescription(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_CONFIG_PATH_CROP_PRODUCT_DESCRIPTION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabledForCategory(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_CONFIG_PATH_ENABLED_FOR_CATEGORY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getCategoryTitleCode(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_CONFIG_PATH_CATEGORY_TITLE_CODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getCategoryDescriptionCode(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_CONFIG_PATH_CATEGORY_DESCRIPTION_CODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getCropCategoryDescription(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_CONFIG_PATH_CROP_CATEGORY_DESCRIPTION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabledForPage(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_CONFIG_PATH_ENABLED_FOR_PAGE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getPageTitleCode(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_CONFIG_PATH_PAGE_TITLE_CODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getPageDescriptionCode(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_CONFIG_PATH_PAGE_DESCRIPTION_CODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabledForHomePage(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_CONFIG_PATH_ENABLED_FOR_HOME_PAGE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getUsername(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_CONFIG_PATH_USERNAME,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
