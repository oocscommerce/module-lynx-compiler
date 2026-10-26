<?php
/**
 * Copyright © OOCS Commerce (https://www.oocscommerce.com). All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace OOCS\LynxCompiler\Model;

use Magento\Framework\App\ProductMetadata as FrameworkProductMetadata;

/**
 * Serves the product version from generated/lynx_compile instead of composer.lock
 */
class ProductMetadata extends FrameworkProductMetadata
{
    /**
     * Console\Compiler\Version writes this file and takes the name from here, so the two cannot drift apart
     */
    public const FILE_NAME = 'product_version.php';

    /**
     * The compiled version when there is one, else what core finds
     *
     * @return string
     */
    public function getVersion()
    {
        // Set beforehand, core skips its cache and composer.lock lookups
        $this->version = $this->version ?: $this->getCompiledVersion();

        return parent::getVersion();
    }

    /**
     * The compiled version, '' when the file is missing or holds no string
     *
     * @return string
     */
    private function getCompiledVersion(): string
    {
        $path = Compiled::getDirectoryPath() . '/' . self::FILE_NAME;

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        if (!file_exists($path)) {
            return '';
        }

        // phpcs:ignore Magento2.Security.IncludeFile
        $version = include $path;

        return \is_string($version) ? $version : '';
    }
}
