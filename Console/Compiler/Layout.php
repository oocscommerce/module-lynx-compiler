<?php
/**
 * Copyright © OOCS Commerce (https://www.oocscommerce.com). All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace OOCS\LynxCompiler\Console\Compiler;

use Magento\Framework\App\Area;
use Magento\Framework\App\AreaList;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Config\ScopeInterface as ConfigScope;
use Magento\Framework\Filesystem;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\Model\Layout\MergeFactory;
use Magento\Theme\Model\ResourceModel\Theme\CollectionFactory as ThemeCollectionFactory;
use OOCS\LynxCompiler\Model\Layout\Merge;
use OOCS\LynxTheme\Helper\Theme as ThemeHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Compiles the merged layout files of every Lynx theme into generated/lynx_compile
 */
class Layout implements CompilerInterface
{
    /**
     * Theme collection factory, component registrar, config scope, area list, design, merge factory, theme, filesystem
     *
     * @param ThemeCollectionFactory $themeCollectionFactory
     * @param ComponentRegistrarInterface $componentRegistrar
     * @param ConfigScope $configScope
     * @param AreaList $areaList
     * @param DesignInterface $design
     * @param MergeFactory $mergeFactory
     * @param ThemeHelper $themeHelper
     * @param Filesystem $filesystem
     */
    public function __construct(
        private readonly ThemeCollectionFactory $themeCollectionFactory,
        private readonly ComponentRegistrarInterface $componentRegistrar,
        private readonly ConfigScope $configScope,
        private readonly AreaList $areaList,
        private readonly DesignInterface $design,
        private readonly MergeFactory $mergeFactory,
        private readonly ThemeHelper $themeHelper,
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
        return 'the merged layout files of every Lynx theme';
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
        // Frontend plugins (the Lynx file filter) only run while the config scope is frontend
        $scope = $this->configScope->getCurrentScope();
        $this->configScope->setCurrentScope(Area::AREA_FRONTEND);

        try {
            return $this->compileThemes($output);
        } finally {
            $this->configScope->setCurrentScope($scope);
        }
    }

    /**
     * Compile Themes
     *
     * @param OutputInterface $output
     * @return bool
     */
    private function compileThemes(OutputInterface $output): bool
    {
        // The merge preference is frontend di; it stays loaded for the rest of the run
        $this->areaList->getArea(Area::AREA_FRONTEND)->load(Area::PART_CONFIG);

        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::GENERATED);
        $themes = $this->themeCollectionFactory->create()->addAreaFilter(Area::AREA_FRONTEND);

        foreach ($themes as $theme) {
            $fullPath = (string) $theme->getFullPath();
            // A virtual theme merges its physical parent's files; a removed one has none
            if ($fullPath === '' || $this->componentRegistrar->getPath('theme', $fullPath) === null) {
                continue;
            }

            // The file filter reads the design theme
            $this->design->setDesignTheme($theme, Area::AREA_FRONTEND);
            if (!$this->themeHelper->isLynxTheme()) {
                continue;
            }

            $merge = $this->mergeFactory->create(['theme' => $theme]);
            if (!$merge instanceof Merge) {
                $output->writeln(\sprintf(
                    '<error>%s is not the compiled merge — the preference is not in effect.</error>',
                    $merge::class
                ));
                return false;
            }

            $relative = $merge->getCompiledPath();
            $directory->writeFile(
                $relative,
                "<?php\n\n/**\n * Compiled Lynx layout files — generated, do not edit.\n *\n"
                . " * Rebuild with: bin/magento lynx:compile layout\n */\n\nreturn "
                . var_export($merge->mergeFiles(), true) . ";\n"
            );

            $output->writeln(\sprintf('  <info>%s</info> → %s', $fullPath, $directory->getAbsolutePath($relative)));
        }

        return true;
    }
}
