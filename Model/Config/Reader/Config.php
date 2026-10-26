<?php
/**
 * Copyright © OOCS Commerce (https://www.oocscommerce.com). All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace OOCS\LynxCompiler\Model\Config\Reader;

use Magento\Framework\App\Config\Initial\Reader;
use OOCS\LynxCompiler\Model\Compiled;

/**
 * Default configuration from generated/lynx_compile instead of merging the 131 etc/config.xml files
 */
class Config extends Reader
{
    /**
     * The one scope there is. Initial\Reader fixes _scopePriorityScheme to ['global'] and
     * exposes no way to ask for another, so this names the file rather than parameterising it.
     */
    private const SCOPE = 'global';

    /**
     * Read
     *
     * @return array<string, mixed>
     */
    public function read()
    {
        $path = Compiled::getDirectoryPath() . '/' . $this->getCompiledFileName();

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
     * @return array<string, mixed>
     */
    public function readMerged(): array
    {
        return parent::read();
    }

    /**
     * Compiled file name: config.xml + global → cache/config_xml_global.php
     *
     * @return string
     */
    public function getCompiledFileName(): string
    {
        return Compiled::CACHED_DIRECTORY . '/'
            . preg_replace('/[^A-Za-z0-9_]+/', '_', $this->_fileName . '_' . self::SCOPE) . '.php';
    }
}
