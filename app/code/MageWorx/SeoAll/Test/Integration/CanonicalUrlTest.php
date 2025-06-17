<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types = 1);

namespace MageWorx\SeoAll\Test\Integration;

use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;

class CanonicalUrlTest extends TestCase
{
    public function testCanonicalUrl()
    {
        $objectManager = Bootstrap::getObjectManager();
        $productRepository = $objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);

        // Create simple product with sku 'test_can_url'
        $product = $objectManager->create(\Magento\Catalog\Model\Product::class);
        $product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
                ->setAttributeSetId(4)
                ->setWebsiteIds([1])
                ->setName('Test Product')
                ->setSku('test_can_url')
                ->setPrice(10)
                ->setWeight(1)
                ->setStockData([
                        'use_config_manage_stock' => 0,
                        'manage_stock' => 1,
                        'is_in_stock' => 1,
                        'qty' => 100
                    ])
                ->save();

        // Load product from db
        $product = $productRepository->get('test_can_url');

        // Check canonical url
        $url = $product->getUrlModel()->getUrl($product, ['_ignore_category' => true]);

        // Check that url does not contain the _ignore_category string
        $this->assertStringNotContainsString('_ignore_category', $url);
    }
}
