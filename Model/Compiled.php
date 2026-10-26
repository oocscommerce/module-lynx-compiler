<?php
/**
 * Copyright © OOCS Commerce (https://www.oocscommerce.com). All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace OOCS\LynxCompiler\Model;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * The directory everything lynx:compile builds lives in
 */
class Compiled
{
    /**
     * Relative to generated/, as Filesystem::getDirectoryWrite() wants it; getDirectoryPath() makes it absolute
     */
    public const DIRECTORY = 'lynx_compile';

    /**
     * Under DIRECTORY, the files Magento reads once per cache flush; opcache can blacklist them
     */
    public const CACHED_DIRECTORY = 'cache';

    /**
     * Get Directory Path
     *
     * @return string
     */
    public static function getDirectoryPath(): string // phpcs:ignore Magento2.Functions.StaticFunction
    {
        $generated = DirectoryList::getDefaultConfig()[DirectoryList::GENERATED][DirectoryList::PATH];

        return BP . '/' . $generated . '/' . self::DIRECTORY;
    }
}
