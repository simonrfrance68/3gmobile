<?php
/**
 * Copyright ©  MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\Info\Model\System;

use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\Js;

class Extensions extends Fieldset
{
    /**
     * @var \MageWorx\Info\Block\Adminhtml\Extensions
     */
    protected $extensionBlock;

    /**
     * Extensions constructor.
     *
     * @param \MageWorx\Info\Block\Adminhtml\Extensions $extensionBlock
     * @param Context $context
     * @param Session $authSession
     * @param Js $jsHelper
     * @param array $data
     */
    public function __construct(
        \MageWorx\Info\Block\Adminhtml\Extensions $extensionBlock,
        Context                                   $context,
        Session                                   $authSession,
        Js                                        $jsHelper,
        array                                     $data = []
    ) {
        $this->extensionBlock = $extensionBlock;
        parent::__construct($context, $authSession, $jsHelper, $data);
    }

    /**
     * Render fieldset html
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        return $this->extensionBlock->toHtml();
    }
}
