<?php
/**
 * Copyright © OOCS Commerce (https://www.oocscommerce.com). All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace OOCS\LynxCompiler\Model;

use Magento\Framework\App\Cache\Type\Translate as TranslateCacheType;
use Magento\Framework\Translate as FrameworkTranslate;
use OOCS\LynxTheme\Helper\Theme as ThemeHelper;

/**
 * Answers the Lynx storefront from a compiled dictionary instead of merging every module's i18n CSV
 */
class Translate extends FrameworkTranslate
{
    /**
     * @var array<string, bool> keyed by the theme path loadData() records
     */
    protected array $isLynx = [];

    /**
     * What a compiled dictionary is called, given what it was compiled for
     *
     * @param string $themePath Theme path, as Translate::setConfig() records it
     * @param string $locale
     * @param string $area
     * @return string
     */
    public static function getCompiledFileName(// phpcs:ignore Magento2.Functions.StaticFunction
        string $themePath,
        string $locale,
        string $area
    ): string {
        $name = implode('_', ['translate', $locale, $area, $themePath]);

        // Theme paths carry a slash (OOCS/Lynx); keep to what is safe in a file name and still readable in a directory
        return Compiled::CACHED_DIRECTORY . '/' . preg_replace('/[^A-Za-z0-9_]+/', '_', $name) . '.php';
    }

    /**
     * Theme, locale and area the memo below belongs to
     *
     * @var string|null
     */
    private ?string $compiledDictionaryKey = null;

    /**
     * @var array<string, string>|null
     */
    private ?array $compiledDictionary = null;

    /**
     * Load Module Translation
     *
     * @return $this
     */
    protected function _loadModuleTranslation()
    {
        $data = $this->getCompiledDictionary();
        if ($data === null) {
            return parent::_loadModuleTranslation();
        }

        // The dictionary arrives here, at the first loader, and the three below have nothing left to add
        $this->_data = $data;

        return $this;
    }

    /**
     * Load Pack Translation
     *
     * @return void
     */
    protected function _loadPackTranslation()
    {
        if ($this->getCompiledDictionary() !== null) {
            return;
        }

        parent::_loadPackTranslation();
    }

    /**
     * Load Theme Translation
     *
     * @return $this
     */
    protected function _loadThemeTranslation()
    {
        if ($this->getCompiledDictionary() !== null) {
            return $this;
        }

        return parent::_loadThemeTranslation();
    }

    /**
     * Load Db Translation
     *
     * @return $this
     */
    protected function _loadDbTranslation()
    {
        // Lynx translates from files only, so Inline Translation has no effect on it
        return $this->isLynx() ? $this : parent::_loadDbTranslation();
    }

    /**
     * Get Cache Id
     *
     * @return string
     */
    protected function getCacheId()
    {
        if (!$this->isLynx()) {
            return parent::getCacheId();
        }

        // Two of Magento's five parts go, leaving one entry per locale, area and theme that every page shares
        $this->_cacheId = TranslateCacheType::TYPE_IDENTIFIER
            . '_' . $this->_config[self::CONFIG_LOCALE_KEY]
            . '_' . $this->_config[self::CONFIG_AREA_KEY]
            . '_' . $this->_config[self::CONFIG_THEME_KEY];

        return $this->_cacheId;
    }

    /**
     * The compiled dictionary for what is being loaded, or null when Magento should do the work
     *
     * @return array<string, string>|null
     */
    private function getCompiledDictionary(): ?array
    {
        // Straight off the config loadData() fills in before calling any loader
        $theme = (string) ($this->_config[self::CONFIG_THEME_KEY] ?? '');
        $locale = (string) ($this->_config[self::CONFIG_LOCALE_KEY] ?? '');
        $area = (string) ($this->_config[self::CONFIG_AREA_KEY] ?? '');

        if ($theme === '' || !$this->isLynx()) {
            return null;
        }

        $key = $theme . '|' . $locale . '|' . $area;
        if ($this->compiledDictionaryKey === $key) {
            return $this->compiledDictionary;
        }

        $this->compiledDictionaryKey = $key;
        $this->compiledDictionary = null;

        $path = Compiled::getDirectoryPath() . '/' . self::getCompiledFileName($theme, $locale, $area);
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        if (!file_exists($path)) {
            return null;
        }

        // phpcs:ignore Magento2.Security.IncludeFile
        $data = include $path;
        $this->compiledDictionary = \is_array($data) ? $data : null;

        return $this->compiledDictionary;
    }

    /**
     * The design theme or one of its parents is Lynx; per theme, as CLI commands load data for several
     *
     * @return bool
     */
    private function isLynx(): bool
    {
        $key = (string) ($this->_config[self::CONFIG_THEME_KEY] ?? '');

        if ($key === '') {
            return false;
        }

        if (!isset($this->isLynx[$key])) {
            $this->isLynx[$key] = false;
            for ($theme = $this->_viewDesign->getDesignTheme(); $theme !== null; $theme = $theme->getParentTheme()) {
                if (str_starts_with((string) $theme->getCode(), ThemeHelper::THEME_CODE)) {
                    $this->isLynx[$key] = true;
                    break;
                }
            }
        }

        return $this->isLynx[$key];
    }
}
