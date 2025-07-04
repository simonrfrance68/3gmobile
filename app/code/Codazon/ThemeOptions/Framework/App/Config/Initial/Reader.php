<?php
/**
 * Default configuration data reader. Reads configuration data from storage
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Codazon\ThemeOptions\Framework\App\Config\Initial;

class Reader
{
    /**
     * File locator
     *
     * @var \Magento\Framework\Config\FileResolverInterface
     */
    protected $_fileResolver;

    /**
     * Config converter
     *
     * @var  \Magento\Framework\Config\ConverterInterface
     */
    protected $_converter;

    /**
     * Config file name
     *
     * @var string
     */
    protected $_fileName;

    /**
     * Class of dom configuration document used for merge
     *
     * @var string
     */
    protected $_domDocumentClass;

    /**
     * Scope priority loading scheme
     *
     * @var array
     */
    protected $_scopePriorityScheme = ['global'];

    /**
     * Path to corresponding XSD file with validation rules for config
     *
     * @var string
     */
    protected $_schemaFile;
    protected $domFactory;

    /**
     * @param \Magento\Framework\Config\FileResolverInterface $fileResolver
     * @param \Magento\Framework\Config\ConverterInterface $converter
     * @param SchemaLocator $schemaLocator
     * @param \Magento\Framework\Config\DomFactory $domFactory
     * @param string $fileName
     * @param string $domDocumentClass
     */
    public function __construct(
        //\Magento\Framework\Config\FileResolverInterface $fileResolver,
        \Codazon\ThemeOptions\Framework\App\Config\FileResolver $fileResolver,
        \Magento\Framework\App\Config\Initial\Converter $converter,
        \Magento\Framework\App\Config\Initial\SchemaLocator $schemaLocator,
        \Magento\Framework\Config\DomFactory $domFactory,
        $fileName = 'config.xml'
    ) {
        $this->_schemaFile = $schemaLocator->getSchema();
        $this->_fileResolver = $fileResolver;
        $this->_converter = $converter;
        $this->domFactory = $domFactory;
        $this->_fileName = $fileName;
    }

    /**
     * Read configuration scope
     *
     * @return array
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function read($themeId)
    {
        $fileList = [];
        foreach ($this->_scopePriorityScheme as $scope) {
            $directories = $this->_fileResolver->cdzget($this->_fileName, $scope, $themeId);
            foreach ($directories as $key => $directory) {
                $fileList[$key] = $directory;
            }
        }

        if (!count($fileList)) {
            return [];
        }

        /** @var \Magento\Framework\Config\Dom $domDocument */
        $domDocument = null;
        foreach ($fileList as $file) {
            try {
                if (!$domDocument) {
                    $domDocument = $this->domFactory->createDom(['xml' => $file, 'schemaFile' => $this->_schemaFile]);
                } else {
                    $domDocument->merge($file);
                }
            } catch (\Magento\Framework\Config\Dom\ValidationException $e) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    new \Magento\Framework\Phrase("Invalid XML in file %1:\n%2", [$file, $e->getMessage()])
                );
            }
        }

        $output = [];
        if ($domDocument) {
            $output = $this->_converter->convert($domDocument->getDom());
        }
        return $output;
    }
}
