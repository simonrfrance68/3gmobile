<?php
/**
 * Copyright ©  MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\Info\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Composer\ComposerInformation;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadFactory;

class MetaPackageList
{
    const VENDOR = 'MageWorx';

    /**
     * @var ReadFactory
     */
    protected $readFactory;

    /**
     * @var Filesystem\DirectoryList
     */
    protected $dir;

    /**
     * @var array
     */
    protected $packages;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var ComposerInformation
     */
    private $composerInformation;

    /**
     * MetaPackageList constructor.
     *
     * @param DirectoryList $dir
     * @param Filesystem $filesystem
     * @param ReadFactory $readFactory
     * @param ComposerInformation $composerInformation
     */
    public function __construct(
        DirectoryList       $dir,
        Filesystem          $filesystem,
        ReadFactory         $readFactory,
        ComposerInformation $composerInformation
    ) {
        $this->dir                 = $dir;
        $this->filesystem          = $filesystem;
        $this->readFactory         = $readFactory;
        $this->composerInformation = $composerInformation;
    }

    /**
     * @return array
     */
    public function getInstalledExtensionCodes()
    {
        $list = $this->getInstalledExtensionList();

        return array_keys($list);
    }

    /**
     * @return array|null
     */
    public function getInstalledExtensionList()
    {
        if ($this->packages === null) {
            try {
                $this->packages = array_merge($this->readLocalCodePath(), $this->readVendorPath());
            } catch (FileSystemException $e) {
                return $this->packages = [];
            }
        }

        return $this->packages;
    }

    /**
     * @return array
     * @throws FileSystemException
     */
    protected function readLocalCodePath()
    {
        $result = [];

        $path = $this->dir->getPath(DirectoryList::APP) . DIRECTORY_SEPARATOR . 'code' .
            DIRECTORY_SEPARATOR . self::VENDOR . DIRECTORY_SEPARATOR;

        $directoryRead = $this->readFactory->create($path);

        if (!$directoryRead->isDirectory($path)) {
            return $result;
        }

        try {
            $directories = $directoryRead->read();
            foreach ($directories as $directory) {
                if ($directoryRead->isDirectory($path . $directory) &&
                    $directoryRead->isExist($path . $directory . '/' . 'composer.json')
                ) {
                    $composerJsonData = $directoryRead->readFile($directory . '/' . 'composer.json');
                    $data             = json_decode($composerJsonData, true);
                    if (isset($data['type']) && isset($data['name']) && $data['type'] == 'metapackage') {
                        if (!isset($result[$data['name']])) {
                            $result[$data['name']] = $data;
                        }
                    }
                }
            }
        } catch (FileSystemException $e) {
            return [];
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function readVendorPath()
    {
        $result = [];
        $data   = $this->composerInformation->getInstalledMagentoPackages();

        foreach ($data as $package) {
            if (strpos($package['name'], strtolower(self::VENDOR)) === 0 && $package['type'] == 'metapackage') {
                $result[$package['name']] = $package;
            }
        }

        return $result;
    }

    /**
     * @param string $metaPackageName
     * @return string
     */
    public function getInstalledVersion($metaPackageName)
    {
        $list = $this->getInstalledExtensionList();

        if (isset($list[$metaPackageName]['version'])) {
            return $list[$metaPackageName]['version'];
        }

        return '';
    }
}
