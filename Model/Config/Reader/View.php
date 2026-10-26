<?php
/**
 * Copyright © OOCS Commerce (https://www.oocscommerce.com). All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace OOCS\LynxCompiler\Model\Config\Reader;

use Magento\Framework\Config\View as ViewConfig;
use OOCS\LynxCompiler\Model\Compiled;

/**
 * Serves view.xml from generated/lynx_compile instead of walking every module for it
 */
class View extends ViewConfig
{
    /**
     * theme full path + area, as ViewFactory was asked for them
     *
     * @var string
     */
    private string $compiledKey = '';

    /**
     * Set Compiled Key
     *
     * @param string $key
     * @return void
     */
    public function setCompiledKey(string $key): void
    {
        $this->compiledKey = $key;
    }

    /**
     * Read
     *
     * @param string|null $scope
     * @return array<string, mixed>
     */
    public function read($scope = null)
    {
        if ($this->compiledKey !== '') {
            $path = Compiled::getDirectoryPath() . '/' . $this->getCompiledFileName($this->compiledKey, $scope);

            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            if (file_exists($path)) {
                // phpcs:ignore Magento2.Security.IncludeFile
                $data = include $path;

                if (\is_array($data)) {
                    return $data;
                }
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
     * Compiled file name: view.xml + theme key + scope, e.g. view_xml_frontend_OOCS_LynxVue_global.php
     *
     * @param string $key
     * @param string|null $scope
     * @return string
     */
    public function getCompiledFileName(string $key, $scope = null): string
    {
        $scope = $scope ?: $this->_defaultScope;

        return preg_replace('/[^A-Za-z0-9_]+/', '_', $this->_fileName . '_' . $key . '_' . $scope) . '.php';
    }
}
