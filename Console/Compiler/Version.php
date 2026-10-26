<?php
/**
 * Copyright © OOCS Commerce (https://www.oocscommerce.com). All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace OOCS\LynxCompiler\Console\Compiler;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Composer\ComposerInformation;
use Magento\Framework\Filesystem;
use OOCS\LynxCompiler\Model\Compiled;
use OOCS\LynxCompiler\Model\ProductMetadata;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Compiles the Magento product version into generated/lynx_compile
 */
class Version implements CompilerInterface
{
    /**
     * Composer information, filesystem
     *
     * @param ComposerInformation $composerInformation
     * @param Filesystem $filesystem
     */
    public function __construct(
        private readonly ComposerInformation $composerInformation,
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
        return 'the Magento product version';
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
        $version = $this->resolve();

        if ($version === '') {
            $output->writeln('<error>No product version found in composer.lock.</error>');
            return false;
        }

        $output->writeln(\sprintf(
            '  <info>%s</info> → %s',
            $version,
            $this->write($version)
        ));

        return true;
    }

    /**
     * The lookup ProductMetadata::getVersion() does, in its order
     *
     * @return string empty when Magento is neither installed as a package nor the root
     */
    private function resolve(): string
    {
        foreach ($this->composerInformation->getSystemPackages() as $package) {
            if (isset($package['name'], $package['version'])) {
                return (string) $package['version'];
            }
        }

        if ($this->composerInformation->isMagentoRoot()) {
            return (string) $this->composerInformation->getRootPackage()->getPrettyVersion();
        }

        return '';
    }

    /**
     * Write the version, answering the path it landed at
     *
     * @param string $version
     * @return string
     */
    private function write(string $version): string
    {
        $content = "<?php\n"
            . "\n"
            . "/**\n"
            . " * Compiled Lynx product version — generated, do not edit.\n"
            . " *\n"
            . " * Rebuild with: bin/magento lynx:compile version\n"
            . " */\n"
            . "\n"
            . 'return ' . var_export($version, true) . ";\n";

        // The reader names the file, so writer and reader cannot drift apart.
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::GENERATED);
        $relative = Compiled::DIRECTORY . '/' . ProductMetadata::FILE_NAME;

        $directory->writeFile($relative, $content);

        return $directory->getAbsolutePath($relative);
    }
}
