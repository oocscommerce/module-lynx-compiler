<?php
/**
 * Copyright © OOCS Commerce (https://www.oocscommerce.com). All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace OOCS\LynxCompiler\Plugin\View;

use Magento\Framework\Config\View as ViewConfig;
use Magento\Framework\Config\ViewFactory;
use Magento\Framework\View\Design\ThemeInterface;
use OOCS\LynxCompiler\Model\Config\Reader\View as CompiledView;

/**
 * Tells a freshly built view config which theme it is for
 */
class CompiledViewKey
{
    /**
     * Stands in for the theme in the key of an area that has none. Console\Compiler\
     * ViewConfig writes those files and takes this constant from here, so the two
     * cannot drift apart.
     */
    public const NO_THEME = 'no_theme';

    /**
     * After Create
     *
     * @param ViewFactory $subject
     * @param ViewConfig $result
     * @param array $arguments
     * @return ViewConfig
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCreate(ViewFactory $subject, ViewConfig $result, array $arguments = []): ViewConfig
    {
        if (!$result instanceof CompiledView) {
            return $result;
        }

        $theme = $arguments['themeModel'] ?? null;
        $area = $arguments['area'] ?? null;

        if (!$theme instanceof ThemeInterface || !\is_string($area) || $area === '') {
            return $result;
        }

        $fullPath = (string) $theme->getFullPath();
        if ($fullPath !== '') {
            $result->setCompiledKey($fullPath . '_' . $area);
            return $result;
        }

        // No theme path
        if ((string) $theme->getId() !== '') {
            return $result;
        }

        $result->setCompiledKey(self::NO_THEME . '_' . $area);

        return $result;
    }
}
