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
use OOCS\LynxCompiler\Model\Config\Reader\Config as ConfigReader;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Compiles the default configuration into generated/lynx_compile
 */
class Config implements CompilerInterface
{
    /**
     * Reader, filesystem
     *
     * @param ConfigReader $reader
     * @param Filesystem $filesystem
     */
    public function __construct(
        private readonly ConfigReader $reader,
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
        return 'the default configuration';
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

        // Reported by what the merge actually produced rather than by count($data), which would always say 2
        $output->writeln(\sprintf(
            '  <info>global</info> — %d sections, %d metadata paths → %s',
            \count($data['data']['default'] ?? []),
            \count($data['metadata'] ?? []),
            $this->write($data)
        ));

        return true;
    }

    /**
     * Write the one file, answering the path it landed at
     *
     * @param array $data
     * @return string
     */
    private function write(array $data): string
    {
        $content = "<?php\n"
            . "\n"
            . "/**\n"
            . " * Compiled Lynx default configuration — generated, do not edit.\n"
            . " *\n"
            . " * Rebuild with: bin/magento lynx:compile config\n"
            . " */\n"
            . "\n"
            . 'return ' . var_export($data, true) . ";\n";

        // The reader names the file, so writer and reader cannot drift apart.
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::GENERATED);
        $relative = Compiled::DIRECTORY . '/' . $this->reader->getCompiledFileName();

        $directory->writeFile($relative, $content);

        return $directory->getAbsolutePath($relative);
    }
}
