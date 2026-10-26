<?php
/**
 * Copyright © OOCS Commerce (https://www.oocscommerce.com). All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace OOCS\LynxCompiler\Plugin\App;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\State\CleanupFiles;
use Magento\Framework\Filesystem;
use OOCS\LynxCompiler\Model\Compiled;

/**
 * Drops generated/lynx_compile whenever setup:upgrade or module:enable/disable drops generated/code
 */
class CleanCompiledWithGenerated
{
    /**
     * Filesystem
     *
     * @param Filesystem $filesystem
     */
    public function __construct(
        private readonly Filesystem $filesystem,
    ) {
    }

    /**
     * A stale snapshot would serve old config without a warning
     *
     * @param CleanupFiles $subject
     * @param string[] $messages
     * @return string[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterClearCodeGeneratedClasses(CleanupFiles $subject, array $messages): array
    {
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::GENERATED);

        if ($directory->isExist(Compiled::DIRECTORY)) {
            $directory->delete(Compiled::DIRECTORY);
            $messages[] = $directory->getAbsolutePath(Compiled::DIRECTORY);
        }

        return $messages;
    }
}
