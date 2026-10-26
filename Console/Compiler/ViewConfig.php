<?php
/**
 * Copyright © OOCS Commerce (https://www.oocscommerce.com). All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace OOCS\LynxCompiler\Console\Compiler;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Config\View as ViewConfigReader;
use Magento\Framework\Config\ViewFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\View\Design\ThemeInterface;
use Magento\Theme\Model\ResourceModel\Theme\CollectionFactory as ThemeCollectionFactory;
use Magento\Theme\Model\ThemeFactory;
use OOCS\LynxCompiler\Model\Compiled;
use OOCS\LynxCompiler\Model\Config\Reader\View as CompiledView;
use OOCS\LynxCompiler\Plugin\View\CompiledViewKey;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Compiles view.xml into generated/lynx_compile, one file per registered theme
 */
class ViewConfig implements CompilerInterface
{
    /**
     * Theme collection factory, theme factory, view factory, filesystem, component registrar, themeless areas
     *
     * @param ThemeCollectionFactory $themeCollectionFactory
     * @param ThemeFactory $themeFactory
     * @param ViewFactory $viewFactory
     * @param Filesystem $filesystem
     * @param ComponentRegistrarInterface $componentRegistrar
     * @param string[] $themelessAreas Areas whose requests carry no design theme
     */
    public function __construct(
        private readonly ThemeCollectionFactory $themeCollectionFactory,
        private readonly ThemeFactory $themeFactory,
        private readonly ViewFactory $viewFactory,
        private readonly Filesystem $filesystem,
        private readonly ComponentRegistrarInterface $componentRegistrar,
        private readonly array $themelessAreas = [],
    ) {
    }

    /**
     * Get Description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'the view.xml of every theme';
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
        $themes = $this->themeCollectionFactory->create();
        if (!$themes->getSize()) {
            $output->writeln('<error>No theme is registered to compile.</error>');
            return false;
        }

        foreach ($themes as $theme) {
            // Null for a virtual theme
            $fullPath = (string) $theme->getFullPath();
            if ($fullPath === '') {
                $output->writeln(
                    \sprintf('  <comment>#%s</comment> — no theme path, left to the merge', $theme->getId())
                );
                continue;
            }

            // A theme row whose files were removed: no request can render it ('theme' = ComponentRegistrar::THEME)
            if ($this->componentRegistrar->getPath('theme', $fullPath) === null) {
                $output->writeln(\sprintf('  <comment>%s</comment> — not on disk, skipped', $fullPath));
                continue;
            }

            $area = (string) $theme->getArea();
            // The same two arguments View\Config::getViewConfig() passes on a page render
            $reader = $this->reader($theme, $area, $output);
            if ($reader === null) {
                return false;
            }

            // readMerged(), never read(): the second would hand back the file this is about to write
            $data = $reader->readMerged();

            $output->writeln(\sprintf(
                '  <info>%s</info> — %d nodes → %s',
                $fullPath,
                \count($data),
                $this->write($reader, $fullPath . '_' . $area, $data)
            ));
        }

        // GraphQL and REST never load Area::PART_DESIGN, so their design falls back to the theme model
        foreach ($this->themelessAreas as $area) {
            $reader = $this->reader($this->themeFactory->create(), (string) $area, $output);
            if ($reader === null) {
                return false;
            }

            $data = $reader->readMerged();

            $output->writeln(\sprintf(
                '  <info>%s</info> (no theme) — %d nodes → %s',
                $area,
                \count($data),
                $this->write($reader, CompiledViewKey::NO_THEME . '_' . $area, $data)
            ));
        }

        return true;
    }

    /**
     * A reader bound to one theme and area, the way ViewFactory builds one on a page render
     *
     * @param ThemeInterface $theme
     * @param string $area
     * @param OutputInterface $output
     * @return CompiledView|null
     */
    private function reader(ThemeInterface $theme, string $area, OutputInterface $output): ?CompiledView
    {
        $reader = $this->viewFactory->create(['themeModel' => $theme, 'area' => $area]);

        if (!$reader instanceof CompiledView) {
            $output->writeln(\sprintf(
                '<error>%s is not the compiled reader — the preference for %s is not in effect.</error>',
                $reader::class,
                ViewConfigReader::class
            ));
            return null;
        }

        return $reader;
    }

    /**
     * Write one theme, answering the path it landed at
     *
     * @param CompiledView $reader
     * @param string $key
     * @param array $data
     * @return string
     */
    private function write(CompiledView $reader, string $key, array $data): string
    {
        $content = "<?php\n"
            . "\n"
            . "/**\n"
            . " * Compiled Lynx view configuration — generated, do not edit.\n"
            . " *\n"
            . " * Rebuild with: bin/magento lynx:compile view_config\n"
            . " */\n"
            . "\n"
            . 'return ' . var_export($data, true) . ";\n";

        // The reader names the file, so writer and reader cannot drift apart.
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::GENERATED);
        $relative = Compiled::DIRECTORY . '/' . $reader->getCompiledFileName($key);

        $directory->writeFile($relative, $content);

        return $directory->getAbsolutePath($relative);
    }
}
