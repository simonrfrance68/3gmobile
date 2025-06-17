<?php
/**
 * Copyright ©  MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\Info\Model;

use Magento\AdminNotification\Model\Feed;
use Magento\AdminNotification\Model\InboxFactory;
use Magento\Backend\App\ConfigInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\HTTP\Adapter\CurlFactory;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;

class AbstractFeed extends Feed
{
    /**
     * @var string
     */
    const CACHE_IDENTIFIER = '';

    /**
     * Update frequency in days
     *
     * @var int
     */
    const FREQUENCY = 1;

    /**
     * @var Escaper|null
     */
    protected $magentoEscaper;

    /**
     * AbstractFeed constructor.
     *
     * @param CurlFactory $curlFactory
     * @param DeploymentConfig $deploymentConfig
     * @param ProductMetadataInterface $productMetadata
     * @param Context $context
     * @param Registry $registry
     * @param ConfigInterface $backendConfig
     * @param InboxFactory $inboxFactory
     * @param UrlInterface $urlBuilder
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param Escaper|null $escaper
     */
    public function __construct(
        CurlFactory              $curlFactory,
        DeploymentConfig         $deploymentConfig,
        ProductMetadataInterface $productMetadata,
        Context                  $context,
        Registry                 $registry,
        ConfigInterface          $backendConfig,
        InboxFactory             $inboxFactory,
        UrlInterface             $urlBuilder,
        ?AbstractResource        $resource = null,
        ?AbstractDb              $resourceCollection = null,
        array                    $data = [],
        ?Escaper                 $escaper = null
    ) {
        parent::__construct(
            $context,
            $registry,
            $backendConfig,
            $inboxFactory,
            $curlFactory,
            $deploymentConfig,
            $productMetadata,
            $urlBuilder,
            $resource,
            $resourceCollection,
            $data
        );
        $this->magentoEscaper = $escaper ?? ObjectManager::getInstance()->get(
            Escaper::class
        );
    }

    /**
     * @return $this
     */
    public function checkUpdate()
    {
        if ($this->getFrequency() + $this->getLastUpdate() > time()) {
            return $this;
        }

        $feedData = [];
        $feedXml  = $this->getFeedData();

        if ($feedXml && $feedXml->channel && $feedXml->channel->item) {
            foreach ($feedXml->channel->item as $item) {
                $data = $this->prepareFeedItemData($item);

                if ($data) {
                    $feedData[] = $data;
                }
            }

            if ($feedData) {
                $this->_inboxFactory->create()->parse(array_reverse($feedData));
            }
        }
        $this->setLastUpdate();

        return $this;
    }

    /**
     * Retrieve Update Frequency
     *
     * @return int
     */
    public function getFrequency()
    {
        return self::FREQUENCY * 3600;
    }

    /**
     * Retrieve feed Last update time
     *
     * @return int
     */
    public function getLastUpdate()
    {
        return $this->_cacheManager->load(static::CACHE_IDENTIFIER);
    }

    /**
     * @param $item
     * @return array
     */
    protected function prepareFeedItemData($item)
    {
        $data                = [];
        $installDate         = $this->getMagentoInstallDate();
        $itemPublicationDate = strtotime((string)$item->pubDate);

        //Use current time for empty date case
        if (!$itemPublicationDate) {
            $itemPublicationDate = time();
        }

        if ($installDate <= $itemPublicationDate) {
            $data = [
                'severity'    => (int)$item->severity,
                'date_added'  => date('Y-m-d H:i:s', $itemPublicationDate),
                'title'       => $this->magentoEscaper->escapeHtml((string)$item->title),
                'description' => $this->magentoEscaper->escapeHtml((string)$item->description),
                'url'         => $this->magentoEscaper->escapeHtml((string)$item->link),
            ];
        }

        return $data;
    }

    /**
     * @return false|int
     * @throws FileSystemException
     * @throws RuntimeException
     */
    protected function getMagentoInstallDate()
    {
        return strtotime($this->_deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_INSTALL_DATE));
    }

    /**
     * Set feed last update time (now)
     *
     * @return $this
     */
    public function setLastUpdate()
    {
        $this->_cacheManager->save(time(), static::CACHE_IDENTIFIER);

        return $this;
    }
}
