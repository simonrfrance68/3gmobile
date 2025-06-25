<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\SeoXTemplates\Model\DataProvider\Category;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\ResourceModel\Category;
use Magento\Framework\App\ResourceConnection;
use MageWorx\SeoAll\Helper\LinkFieldResolver;
use MageWorx\SeoXTemplates\Helper\Store as HelperStore;
use MageWorx\SeoXTemplates\Model\ConverterCategoryFactory;

class Eav extends \MageWorx\SeoXTemplates\Model\DataProvider\Category
{
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connection;

    /**
     *
     * @var int
     */
    protected $_defaultStore;

    /**
     * Store ID for obtaining and preparing data
     *
     * @var int
     */
    protected $_storeId;

    /**
     * @var HelperStore
     */
    protected $helperStore;

    /**
     *
     * @var array
     */
    protected $_attributeCodes = [];

    /**
     *
     * @var \Magento\Framework\Data\Collection
     */
    protected $_collection;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category
     */
    protected $categoryResource;

    /**
     * @var \MageWorx\SeoAll\Helper\LinkFieldResolver
     */
    protected $linkFieldResolver;

    /**
     * @var \MageWorx\SeoXTemplates\Model\ExcludeCategoriesRegistry
     */
    protected $excludeCategoriesRegistry;

    /**
     * Eav constructor.
     *
     * @param ResourceConnection $resource
     * @param ConverterCategoryFactory $converterCategoryFactory
     * @param Category $categoryResource
     * @param LinkFieldResolver $linkFieldResolver
     * @param HelperStore $helperStore
     */
    public function __construct(
        ResourceConnection                                      $resource,
        ConverterCategoryFactory                                $converterCategoryFactory,
        Category                                                $categoryResource,
        LinkFieldResolver                                       $linkFieldResolver,
        HelperStore                                             $helperStore,
        \MageWorx\SeoXTemplates\Model\ExcludeCategoriesRegistry $categoriesRegistry

    ) {
        parent::__construct($resource, $converterCategoryFactory);
        $this->linkFieldResolver         = $linkFieldResolver;
        $this->categoryResource          = $categoryResource;
        $this->helperStore               = $helperStore;
        $this->excludeCategoriesRegistry = $categoriesRegistry;
    }

    /**
     * Retrieve data
     *
     * @param \Magento\Framework\Data\Collection $collection
     * @param \MageWorx\SeoXTemplates\Model\AbstractTemplate $template
     * @param int|null $customStoreId
     * @return array
     */
    public function getData($collection, $template, $customStoreId = null)
    {
        if (!$collection) {
            return false;
        }

        $this->_collection = $collection;
        $this->_storeId    = $this->getStoreId($template, $customStoreId);

        $this->_attributeCodes = $template->getAttributeCodesByType();

        $attributes = [];
        $connection = $this->_getConnection();

        $select         = $connection->select()
                                     ->from($this->_resource->getTableName('eav_entity_type'))
                                     ->where("entity_type_code = 'catalog_category'");
        $categoryTypeId = $connection->fetchOne($select);

        foreach ($this->_attributeCodes as $_attrName) {
            $select = $connection->select()
                                 ->from($this->_resource->getTableName('eav_attribute'))
                                 ->where('entity_type_id = ?', (int)$categoryTypeId)
                                 ->where('attribute_code = ?', $_attrName);

            if ($res = $connection->fetchRow($select)) {
                $attributes[$_attrName] = $res;
            }
        }

        $categoryIds = array_keys($this->getCollectionIds());

        $data = [];

        $linkField = $this->getLinkField();
        foreach ($attributes as $attribute) {

            $idsByAttribute = [
                'insert' => array_fill_keys($categoryIds, []),
                'update' => []
            ];

            $tableName = $this->_resource->getTableName('catalog_category_entity') . '_' . $attribute['backend_type'];
            $storeId   = $template->getIsSingleStoreMode() ? $template->getStoreId() : $this->_storeId;

            $select = $connection->select([$linkField])
                                 ->from($tableName)
                                 ->where("attribute_id = ?", (int)$attribute['attribute_id'])
                                 ->where($linkField . " IN(?)", $categoryIds)
                                 ->where("store_id = ?", (int)$storeId);

            $existRecords = $connection->fetchAll($select);

            foreach ($existRecords as $record) {
                if ($template->isScopeForAll()) {
                    $idsByAttribute['update'][$record[$linkField]] = ['old_value' => $record['value']];
                }

                if (!$record['value']) {
                    $idsByAttribute['update'][$record[$linkField]] = ['old_value' => $record['value']];
                }

                unset($idsByAttribute['insert'][$record[$linkField]]);
            }

            $attributeHash        =
                $attribute['attribute_id'] . '#' . $attribute['attribute_code'] . '#' . $attribute['backend_type'];
            $data[$attributeHash] = $idsByAttribute;
        }

        $this->fillData($template, $data);

        return $data;
    }

    /**
     * @param \MageWorx\SeoXTemplates\Model\AbstractTemplate $template
     * @param int|null $customStoreId
     * @return int|null
     */
    protected function getStoreId($template, $customStoreId = null)
    {
        if ($customStoreId) {
            return $customStoreId;
        }

        if ($template->getIsSingleStoreMode()) {
            return $this->helperStore->getCurrentStoreId();
        }

        return $template->getStoreId();
    }

    /**
     * return array row_id => entity_id or entity_id => entity_id
     */
    public function getCollectionIds()
    {
        $data      = [];
        $linkField = $this->getLinkField();
        foreach ($this->_collection as $item) {
            $data[$item->getData($linkField)] = $item->getData('entity_id');
        }

        return $data;
    }

    /**
     * @return string
     */
    protected function getLinkField()
    {
        return $this->linkFieldResolver->getLinkField(CategoryInterface::class, 'entity_id');
    }

    /**
     * Add data for each entityId
     *
     * @param \MageWorx\SeoXTemplates\Model\AbstractTemplate $template
     * @param array $data
     */
    protected function fillData($template, &$data)
    {
        $storeIdForApply       = $template->getIsSingleStoreMode() ? $template->getStoreId() : $this->_storeId;
        $connect               = $this->getCollectionIds();
        $emptyValueCategoryIds = [];
        $linkField             = $this->getLinkField();

        foreach ($data as $attributeHash => $attributeData) {
            [$attributeId, $attributeCode] = explode('#', $attributeHash);

            $converter = $this->converterCategoryFactory->create($attributeCode);

            foreach ($attributeData as $insertTypeName => $insertData) {
                foreach ($insertData as $entityId => $emptyValue) {
//                    $microtime = microtime(1);
                    $attributeValue = '';
                    $category       = $this->_collection->getItemById($connect[$entityId]);
                    if ($category) {
                        $attributeValue = $converter->convert(
                            $category->setStoreId($this->_storeId),
                            $template->getCode()
                        );

                        if (!$attributeValue) {
                            $attributeValue = null;
                        }

                        if (!$attributeValue && $template::SCOPE_EMPTY == $template->getScope()) {
                            $emptyValueCategoryIds = array_merge($emptyValueCategoryIds, [$category->getId()]);
                        }
                    }

//                    echo "<br><font color = green>" . number_format((microtime(1) - $microtime), 5) . " sec need for " . get_class($this) . "</font>";

                    $data[$attributeHash][$insertTypeName][$entityId] = array_merge(
                        $data[$attributeHash][$insertTypeName][$entityId],
                        [
                            'attribute_id' => $attributeId,
                            $linkField     => $entityId,
                            'store_id'     => $storeIdForApply,
                            'value'        => $attributeValue,
                        ]
                    );
                }
            }
        }

        $this->excludeCategoriesRegistry->addCategoriesIds($template->getId(), $emptyValueCategoryIds);
    }

    /**
     * You can load collection and add specific data to items here
     *
     * @param \MageWorx\SeoXTemplates\Model\Template\Category $template
     * @param \Magento\Framework\Data\Collection\AbstractDb $collection Non-loaded collection
     * @return mixed|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function onLoadEntityCollection($template, $collection)
    {
        /**
         * Exclude categories with empty generated values from category collection if template scope is "For Empty"
         * for avoid loop
         */
        $categoriesIds = $this->excludeCategoriesRegistry->getCategoriesIds($template->getId());

        if (!empty($categoriesIds)) {
            $collection->getSelect()->where('e.entity_id NOT IN (?)', $categoriesIds);
        }

        return parent::onLoadEntityCollection($template, $collection);
    }

    /**
     * @return array
     */
    public function getAttributeCodes()
    {
        return $this->_attributeCodes;
    }
}
