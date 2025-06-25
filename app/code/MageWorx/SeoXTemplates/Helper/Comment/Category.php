<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\SeoXTemplates\Helper\Comment;

use Magento\Framework\App\Helper\Context;
use MageWorx\SeoXTemplates\Model\Template\Category as CategoryTemplate;

class Category extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var Category\Source
     */
    protected $commentSource;

    /**
     * Category constructor.
     *
     * @param Category\Source $commentSource
     * @param Context $context
     */
    public function __construct(
        \MageWorx\SeoXTemplates\Helper\Comment\Category\Source $commentSource,
        Context                                                $context
    ) {
        parent::__construct($context);
        $this->commentSource = $commentSource;
    }

    /**
     * @param CategoryTemplate $type
     * Return comments for category template
     *
     * @return string
     * @throws \UnexpectedValueException
     */
    public function getComments($type)
    {
        $comment = '<br><small>' . $this->commentSource->getStaticVariableHeader();
        switch ($type) {
            case CategoryTemplate::TYPE_CATEGORY_META_TITLE:
            case CategoryTemplate::TYPE_CATEGORY_META_DESCRIPTION:
            case CategoryTemplate::TYPE_CATEGORY_DESCRIPTION:
            case CategoryTemplate::TYPE_CATEGORY_SEO_NAME:
            case CategoryTemplate::TYPE_CATEGORY_META_KEYWORDS:
                $comment .= '<br><p>' . $this->commentSource->getCategoryComment();
                $comment .= '<br><p>' . $this->commentSource->getCategoriesComment();
                $comment .= '<br><p>' . $this->commentSource->getParentCategoryComment();
                $comment .= '<br><p>' . $this->commentSource->getParentCategoryByLevelComment();
                $comment .= '<br><p>' . $this->commentSource->getWebsiteNameComment();
                $comment .= '<br><p>' . $this->commentSource->getStoreNameComment();
                $comment .= '<br><p>' . $this->commentSource->getStoreViewNameComment();
                $comment .= '<br><p>' . $this->commentSource->getDynamicVariableHeader();
                $comment .= '<br><p>' . $this->commentSource->getLnAllFiltersComment();
                $comment .= '<br><p>' . $this->commentSource->getLnPersonalFiltersComment();
                $comment .= '<br><p>' . $this->commentSource->getRandomizeComment();
                break;
            default:
                throw new \UnexpectedValueException(__('SEO XTemplates: Unknow Category Template Type'));
        }
        return $comment . '</small>';
    }

    /**
     * Return Static Variable header
     * @return string
     * @deprecated For backward compatibility with custom solutions
     */
    protected function getStaticVariableHeader()
    {
        return $this->commentSource->getStaticVariableHeader();
    }

    /**
     * Return comment for Category
     * @return string
     * @deprecated For backward compatibility with custom solutions
     */
    protected function getCategoryComment()
    {
        return $this->commentSource->getCategoriesComment();
    }

    /**
     * Return comment for Categories
     * @return string
     * @deprecated For backward compatibility with custom solutions
     */
    protected function getCategoriesComment()
    {
        return $this->commentSource->getCategoriesComment();
    }

    /**
     * Return comment for Parent Category
     * @return string
     * @deprecated For backward compatibility with custom solutions
     */
    protected function getParentCategoryComment()
    {
        return $this->commentSource->getParentCategoryComment();
    }

    /**
     * Return comment for Parent Category by level
     * @return string
     * @deprecated For backward compatibility with custom solutions
     */
    protected function getParentCategoryByLevelComment()
    {
        return $this->commentSource->getParentCategoryByLevelComment();
    }

    /**
     * Return comment for Website Name
     * @return string
     * @deprecated For backward compatibility with custom solutions
     */
    protected function getWebsiteNameComment()
    {
        return $this->commentSource->getWebsiteNameComment();
    }

    /**
     * Return comment for Store Name
     * @return string
     * @deprecated For backward compatibility with custom solutions
     */
    protected function getStoreNameComment()
    {
        return $this->commentSource->getStoreNameComment();
    }

    /**
     * Return comment for Store View Name
     * @return string
     * @deprecated For backward compatibility with custom solutions
     */
    protected function getStoreViewNameComment()
    {
        return $this->commentSource->getStoreViewNameComment();
    }

    /**
     * Return Dynamic Variable header
     * @return string
     * @deprecated For backward compatibility with custom solutions
     */
    protected function getDynamicVariableHeader()
    {
        return $this->commentSource->getDynamicVariableHeader();
    }

    /**
     * Return comment for filter_all
     * @return string
     * @deprecated For backward compatibility with custom solutions
     */
    protected function getLnAllFiltersComment()
    {
        return $this->commentSource->getLnAllFiltersComment();
    }

    /**
     * Return comment for personal filters
     * @return string
     * @deprecated For backward compatibility with custom solutions
     */
    protected function getLnPersonalFiltersComment()
    {
        return $this->commentSource->getLnPersonalFiltersComment();
    }

    /**
     * Return comment for randomizer
     * @return string
     * @deprecated For backward compatibility with custom solutions
     */
    protected function getRandomizeComment()
    {
        return $this->commentSource->getRandomizeComment();
    }

    /**
     * Return comment for Subcategories
     * @return string
     * @deprecated For backward compatibility with custom solutions
     */
    protected function getSubcategoriesComment()
    {
        return $this->commentSource->getSubcategoriesComment();
    }
}
