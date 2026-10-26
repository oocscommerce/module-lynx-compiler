<?php
/**
 * Copyright © OOCS Commerce (https://www.oocscommerce.com). All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace OOCS\LynxCompiler\Console\Compiler;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use OOCS\LynxCompiler\Model\Compiled;
use OOCS\LynxCompiler\Model\Config\Reader\Components as ComponentsReader;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Compiles the merged etc/components/*.graphqls into generated/lynx_compile
 */
class Components implements CompilerInterface
{
    /**
     * Reader, filesystem
     *
     * @param ComponentsReader $reader
     * @param Filesystem $filesystem
     */
    public function __construct(
        private readonly ComponentsReader $reader,
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
        return 'the Lynx component definitions (etc/components/*.graphqls)';
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
        // readMerged(), never read(): the second would hand back the file this is about to write
        $data = $this->reader->readMerged();

        $content = "<?php\n"
            . "\n"
            . "/**\n"
            . " * Compiled Lynx component definitions — generated, do not edit.\n"
            . " *\n"
            . " * Rebuild with: bin/magento lynx:compile components\n"
            . " */\n"
            . "\n"
            . 'return ' . var_export($data, true) . ";\n";

        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::GENERATED);
        $relative = Compiled::DIRECTORY . '/' . ComponentsReader::FILE;
        $directory->writeFile($relative, $content);

        $output->writeln(\sprintf(
            '  %d components → %s',
            \count($data['components']),
            $directory->getAbsolutePath($relative)
        ));

        return true;
    }
}
