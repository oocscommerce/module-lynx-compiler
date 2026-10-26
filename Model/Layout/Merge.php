<?php
/**
 * Copyright © OOCS Commerce (https://www.oocscommerce.com). All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace OOCS\LynxCompiler\Model\Layout;

use OOCS\LynxCompiler\Model\Compiled;
use OOCS\LynxOptimize\Model\Layout\Merge as LynxMerge;

/**
 * Serves a Lynx theme's merged layout files from generated/lynx_compile instead of reading every lynx_*.xml
 */
class Merge extends LynxMerge
{
    /**
     * The compiled file under generated/, of the physical theme the files are collected for; read on a cache miss only
     *
     * @return string
     */
    public function getCompiledPath(): string
    {
        return Compiled::DIRECTORY . '/' . Compiled::CACHED_DIRECTORY . '/' . $this->getCompiledFileName();
    }

    /**
     * Get Compiled File Name
     *
     * @return string
     */
    private function getCompiledFileName(): string
    {
        $path = (string) $this->_getPhysicalTheme($this->getTheme())->getFullPath();

        return 'layout_' . preg_replace('/[^A-Za-z0-9_]+/', '_', $path) . '.php';
    }

    /**
     * The merged layout files as core builds them, for lynx:compile
     *
     * @return string
     */
    public function mergeFiles(): string
    {
        return (string) parent::_loadFileLayoutUpdatesXml()->asXML();
    }

    /**
     * Load File Layout Updates Xml
     *
     * @return \Magento\Framework\View\Layout\Element
     */
    protected function _loadFileLayoutUpdatesXml()
    {
        $path = Compiled::getDirectoryPath() . '/' . Compiled::CACHED_DIRECTORY . '/' . $this->getCompiledFileName();

        // Not compiled, or not a Lynx theme: merged as core does
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        if (file_exists($path)) {
            // phpcs:ignore Magento2.Security.IncludeFile
            $xml = include $path;

            if (\is_string($xml)) {
                return $this->_loadXmlString($xml);
            }
        }

        return parent::_loadFileLayoutUpdatesXml();
    }
}
