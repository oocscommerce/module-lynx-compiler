<?php
/**
 * Copyright © OOCS Commerce (https://www.oocscommerce.com). All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace OOCS\LynxCompiler\Console\Compiler;

use Magento\Framework\App\Area;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\State;
use Magento\Framework\Filesystem;
use Magento\Framework\Locale\ResolverInterface as LocaleResolver;
use Magento\Framework\View\Design\ThemeInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use OOCS\LynxCompiler\Model\Compiled;
use OOCS\LynxCompiler\Model\Translate as LynxTranslate;
use OOCS\LynxCompiler\Model\Translate\Source as SourceTranslate;
use OOCS\LynxTheme\Helper\Theme as ThemeHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Compiles the Lynx translation dictionary into generated/lynx_compile
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Translate implements CompilerInterface
{
    /**
     * App state, emulation, store manager, view design, locale resolver, translate, filesystem
     *
     * @param State $appState
     * @param Emulation $emulation
     * @param StoreManagerInterface $storeManager
     * @param DesignInterface $viewDesign
     * @param LocaleResolver $localeResolver
     * @param SourceTranslate $translate
     * @param Filesystem $filesystem
     */
    public function __construct(
        private readonly State $appState,
        private readonly Emulation $emulation,
        private readonly StoreManagerInterface $storeManager,
        private readonly DesignInterface $viewDesign,
        private readonly LocaleResolver $localeResolver,
        private readonly SourceTranslate $translate,
        private readonly Filesystem $filesystem,
    ) {
    }

    /**
     * Get Description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'the translation dictionary, one file per theme and locale';
    }

    /**
     * Compile
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     */
    public function compile(InputInterface $input, OutputInterface $output): bool
    {
        // One file per theme and locale: several stores commonly share both
        $written = [];
        $skipped = 0;

        foreach ($this->storeManager->getStores() as $store) {
            $result = $this->appState->emulateAreaCode(
                Area::AREA_FRONTEND,
                fn (): ?array => $this->compileForStore((int) $store->getId())
            );

            if ($result === null) {
                $skipped++;
                $output->writeln(\sprintf(
                    '  <comment>skipped</comment> store "%s" — not on the Lynx theme',
                    $store->getCode()
                ));
                continue;
            }

            [$themeCode, $locale, $count, $path] = $result;
            $key = $themeCode . '|' . $locale;

            if (isset($written[$key])) {
                continue;
            }
            $written[$key] = true;

            $output->writeln(\sprintf(
                '  <info>%s</info> %s — %d entries → %s',
                $themeCode,
                $locale,
                $count,
                $path
            ));
        }

        if ($written === []) {
            $output->writeln('<comment>Nothing compiled: no store runs the Lynx theme.</comment>');
            return $skipped > 0;
        }

        $output->writeln(\sprintf('  <info>Compiled %d dictionary file(s).</info>', \count($written)));

        return true;
    }

    /**
     * Compile For Store
     *
     * @param int $storeId
     * @return array{0: string, 1: string, 2: int, 3: string}|null theme, locale, entry count, path
     */
    private function compileForStore(int $storeId): ?array
    {
        // Emulation is what puts the store's design and locale in place
        $this->emulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);

        try {
            $theme = $this->viewDesign->getDesignTheme();
            if (!$this->isLynxTheme($theme)) {
                return null;
            }

            $locale = (string) $this->localeResolver->getLocale();
            // getThemePath(), not getCode(): Translate::setConfig() records the path
            $themeCode = (string) $theme->getThemePath();

            // Model\Translate\Source, never TranslateInterface
            $this->translate->setLocale($locale);
            $this->translate->loadData(Area::AREA_FRONTEND, true);
            $data = $this->translate->getData();

            $path = $this->write($themeCode, $locale, Area::AREA_FRONTEND, $data);

            return [$themeCode, $locale, \count($data), $path];
        } finally {
            $this->emulation->stopEnvironmentEmulation();
        }
    }

    /**
     * Write the dictionary as a PHP array, answering the path it landed at
     *
     * @param string $themePath
     * @param string $locale
     * @param string $area
     * @param array $data
     * @return string
     */
    private function write(string $themePath, string $locale, string $area, array $data): string
    {
        ksort($data);

        $content = "<?php\n"
            . "\n"
            . "/**\n"
            . " * Compiled Lynx translation dictionary — generated, do not edit.\n"
            . " *\n"
            . " * Rebuild with: bin/magento lynx:compile translate\n"
            . " */\n"
            . "\n"
            . 'return ' . var_export($data, true) . ";\n";

        // The name comes from Model\Translate, the class that will read the file back
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::GENERATED);
        $relative = Compiled::DIRECTORY
            . '/' . LynxTranslate::getCompiledFileName($themePath, $locale, $area);

        $directory->writeFile($relative, $content);

        return $directory->getAbsolutePath($relative);
    }

    /**
     * The helper caches its verdict for the request, which is right in a page render and wrong in a loop over stores
     *
     * @param ThemeInterface|null $theme
     * @return bool
     */
    private function isLynxTheme(?ThemeInterface $theme): bool
    {
        while ($theme !== null) {
            if (str_starts_with($theme->getCode(), ThemeHelper::THEME_CODE)) {
                return true;
            }
            $theme = $theme->getParentTheme();
        }

        return false;
    }
}
