<?php
/**
 * Copyright © 2017 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\SeoXTemplates\Helper\Comment;

use Magento\Framework\App\Helper\Context;
use MageWorx\SeoXTemplates\Model\Template\Product as ProductTemplate;

class Product extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var Product\Source
     */
    protected $commentSource;

    /**
     * Product constructor.
     *
     * @param Product\Source $commentSource
     * @param Context $context
     */
    public function __construct(
        \MageWorx\SeoXTemplates\Helper\Comment\Product\Source $commentSource,
        Context                                               $context
    ) {
        parent::__construct($context);
        $this->commentSource = $commentSource;
    }

    /**
     * @param string $type
     * Return comments for product template
     *
     * @return string
     * @throws \UnexpectedValueException
     */
    public function getComments($type)
    {
        $comment = '<br><small>' . $this->getVariablesComment() . $this->getRandomizerComment();
        switch ($type) {
            case ProductTemplate::TYPE_PRODUCT_SHORT_DESCRIPTION:
            case ProductTemplate::TYPE_PRODUCT_DESCRIPTION:
            case ProductTemplate::TYPE_PRODUCT_META_DESCRIPTION:
                $comment .= $this->getAdditionalCategoryComment();
                $comment .= $this->getDescriptionExample();
                break;
            case ProductTemplate::TYPE_PRODUCT_META_KEYWORDS:
                $comment .= $this->getAdditionalCategoryComment();
                $comment .= $this->getKeywordsExample();
                break;
            case ProductTemplate::TYPE_PRODUCT_SEO_NAME:
                $comment .= $this->getSeoNameExample();
                break;
            case ProductTemplate::TYPE_PRODUCT_URL_KEY:
                $comment .= $this->getUrlExample();
                break;
            case ProductTemplate::TYPE_PRODUCT_META_TITLE:
                $comment .= $this->getAdditionalCategoryComment();
                $comment .= $this->getMetaTitleExample();
                break;
            case ProductTemplate::TYPE_PRODUCT_GALLERY:
                $comment .= $this->getAdditionalGalleryComment();
                $comment .= $this->getGalleryExample();
                break;
            default:
                throw new \UnexpectedValueException(__('SEO XTemplates: Unknow Product Template Type'));
        }

        return $comment . '</small>';
    }

    /**
     * Return comment for url variables
     *
     * @return string
     * @deprecated For backward compatibility with custom solutions
     */
    protected function getVariablesComment()
    {
        return $this->commentSource->getVariablesComment();
    }

    /**
     * Return comment for randomizer
     *
     * @return string
     * @deprecated For backward compatibility with custom solutions
     */
    protected function getRandomizerComment()
    {
        return $this->commentSource->getRandomizerComment();
    }

    /**
     * Return additional category comment
     *
     * @return string
     * @deprecated For backward compatibility
     */
    public function getAdditionalCategoryComment()
    {
        return $this->commentSource->getAdditionalGalleryComment();
    }

    /**
     * @return string
     * @deprecated For backward compatibility with custom solutions
     */
    public function getAdditionalGalleryComment()
    {
        return $this->commentSource->getAdditionalGalleryComment();
    }

    /**
     * Return example for description
     *
     * @return string
     * @deprecated For backward compatibility with custom solutions
     */
    protected function getDescriptionExample()
    {
        return $this->commentSource->getDescriptionExample();
    }

    /**
     * Return example for keywords
     *
     * @return string
     * @deprecated For backward compatibility with custom solutions
     */
    protected function getKeywordsExample()
    {
        return $this->commentSource->getKeywordsExample();
    }

    /**
     * Return example for seo name
     *
     * @return string
     * @deprecated For backward compatibility with custom solutions
     */
    protected function getSeoNameExample()
    {
        return $this->commentSource->getSeoNameExample();
    }

    /**
     * Return example for url
     *
     * @return string
     * @deprecated For backward compatibility with custom solutions
     */
    protected function getUrlExample()
    {
        $this->commentSource->getUrlExample();
    }

    /**
     * Return example for meta title
     *
     * @return string
     * @deprecated For backward compatibility with custom solutions
     */
    protected function getMetaTitleExample()
    {
        return $this->commentSource->getMetaTitleExample();
    }

    /**
     * Return example for gallery
     *
     * @return string
     * @deprecated For backward compatibility with custom solutions
     */
    protected function getGalleryExample()
    {
        return $this->commentSource->getGalleryExample();
    }
}
