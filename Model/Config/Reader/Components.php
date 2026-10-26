<?php
/**
 * Copyright © OOCS Commerce (https://www.oocscommerce.com). All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace OOCS\LynxCompiler\Model\Config\Reader;

use OOCS\LynxCompiler\Model\Compiled;
use OOCS\LynxComponent\Helper\Schema\Reader;

/**
 * Serves the component definitions from generated/lynx_compile instead of parsing every etc/components/*.graphqls
 */
class Components extends Reader
{
    /**
     * Under generated/lynx_compile: read once per cache flush, like the XML readers' files
     */
    public const FILE = Compiled::CACHED_DIRECTORY . '/components_schema.php';

    /**
     * Read
     *
     * @return array
     */
    public function read(): array
    {
        $path = Compiled::getDirectoryPath() . '/' . self::FILE;

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        if (file_exists($path)) {
            // phpcs:ignore Magento2.Security.IncludeFile
            $data = include $path;

            if (\is_array($data)) {
                return $data;
            }
        }

        return parent::read();
    }

    /**
     * The merge itself, ignoring any compiled file
     *
     * @return array
     */
    public function readMerged(): array
    {
        return parent::read();
    }
}
