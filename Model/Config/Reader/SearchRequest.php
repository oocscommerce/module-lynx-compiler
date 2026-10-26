<?php
/**
 * Copyright © OOCS Commerce (https://www.oocscommerce.com). All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace OOCS\LynxCompiler\Model\Config\Reader;

use Magento\Framework\Search\Request\Config\FilesystemReader;
use OOCS\LynxCompiler\Model\Compiled;

/**
 * Search request declarations from generated/lynx_compile instead of merging the 2 etc/search_request.xml files
 */
class SearchRequest extends FilesystemReader
{
    /**
     * Read
     *
     * @param string|null $scope
     * @return array<string, mixed>
     */
    public function read($scope = null)
    {
        // The same first line as Filesystem::read(), so a caller that passed nothing gets the file for the scope the
        $scope = $scope ?: $this->_defaultScope;

        $path = Compiled::getDirectoryPath() . '/' . $this->getCompiledFileName((string) $scope);

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        if (file_exists($path)) {
            // phpcs:ignore Magento2.Security.IncludeFile
            $data = include $path;

            if (\is_array($data)) {
                return $data;
            }
        }

        return parent::read($scope);
    }

    /**
     * The merge itself, ignoring any compiled file
     *
     * @param string|null $scope
     * @return array<string, mixed>
     */
    public function readMerged($scope = null): array
    {
        return parent::read($scope);
    }

    /**
     * Compiled file name: search_request.xml + global → cache/search_request_xml_global.php
     *
     * @param string $scope
     * @return string
     */
    public function getCompiledFileName(string $scope): string
    {
        return Compiled::CACHED_DIRECTORY . '/'
            . preg_replace('/[^A-Za-z0-9_]+/', '_', $this->_fileName . '_' . $scope) . '.php';
    }
}
