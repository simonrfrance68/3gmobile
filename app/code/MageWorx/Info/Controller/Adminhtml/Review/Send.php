<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\Info\Controller\Adminhtml\Review;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\ResultInterface;
use MageWorx\Info\Helper\Data;
use MageWorx\Info\Model\MetaPackageList;

class Send extends Action
{

    /**
     * @var RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var MetaPackageList
     */
    protected $metaPackageList;

    /**
     * Send constructor.
     *
     * @param MetaPackageList $metaPackageList
     * @param Data $helper
     * @param Context $context
     * @param RawFactory $resultRawFactory
     */
    public function __construct(
        MetaPackageList $metaPackageList,
        Data            $helper,
        Context         $context,
        RawFactory      $resultRawFactory
    ) {
        parent::__construct(
            $context
        );
        $this->metaPackageList  = $metaPackageList;
        $this->helper           = $helper;
        $this->resultRawFactory = $resultRawFactory;
    }

    /**
     * @return ResponseInterface|Raw|ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPost()->toArray();
        if (isset($data['ext_code'])) {
            $data['from_url'] = str_replace(['https://', 'http://'], '', $this->helper->getStoreUrl());
            $data['version']  = $this->metaPackageList->getInstalledVersion($data['ext_code']);
            $result           = $this->helper->sendReviewData($data);
        } else {
            $result = false;
        }

        /** @var Raw $response */
        $response = $this->resultRawFactory->create();
        $response->setHeader('Content-type', 'text/plain');
        $response->setContents(json_encode($result));

        return $response;
    }
}
