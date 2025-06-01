<?php

namespace Meetanshi\CurrencySwitcher\Controller\Adminhtml\System;

use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Backend\App\Action\Context;
use \Psr\Log\LoggerInterface;
use Magento\Backend\App\Action;
use Magento\Framework\Module\Dir\Reader;
use Meetanshi\CurrencySwitcher\Plugin\Datawrapper;
use Magento\Framework\Filesystem\DirectoryList;

class UpdateDb extends Action
{
    protected $_logger;
    private $jsonFactory;
    private $moduleReader;
    protected $dataWrapper;
    protected $directoryList;

    public function __construct(
        Context $context,
        LoggerInterface $logger,
        JsonFactory $jsonFactory,
        Reader $moduleReader,
        Datawrapper $datawrapper,
        DirectoryList $directoryList
    )
    {
        $this->_logger = $logger;
        $this->jsonFactory = $jsonFactory;
        $this->moduleReader = $moduleReader;
        $this->dataWrapper = $datawrapper;
        $this->directoryList = $directoryList;
        parent::__construct($context);
    }

    public function execute()
    {
        $response = [
            'succeess' => "true",
            'successmesg' => ""
        ];
        $result = $this->jsonFactory->create();
        $result->setData($response);

        try {
            $zipUrl = "https://meetanshi.com/GeoLite2-Country/GeoLite2-Country.mmdb";
            $path = $this->directoryList->getPath('var');
            file_put_contents($path . "/export/GeoLite2-Country.mmdb", fopen($zipUrl, 'r'));
            $this->messageManager->addSuccess(__('Update GeoIp Database Successfully'));
        }catch (\Exception $e){
            $this->_logger->log(0,$e->getMessage());
            $this->messageManager->addError(__('Error to Update GeoIp Database'));
        }
        return $result;
    }

    protected function _isAllowed()
    {
        return true;
    }
}