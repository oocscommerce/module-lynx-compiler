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
use OOCS\LynxCompiler\Model\Config\Reader\Widget as WidgetReader;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Compiles the widget declarations into generated/lynx_compile
 */
class Widget implements CompilerInterface
{
    /**
     * Reader, filesystem, scopes
     *
     * @param WidgetReader $reader
     * @param Filesystem $filesystem
     * @param string[] $scopes Config scopes to compile
     */
    public function __construct(
        private readonly WidgetReader $reader,
        private readonly Filesystem $filesystem,
        private readonly array $scopes = ['global'],
    ) {
    }

    /**
     * Get Description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'the widget declarations';
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
        if ($this->scopes === []) {
            $output->writeln('<error>No scope configured to compile.</error>');
            return false;
        }

        foreach ($this->scopes as $scope) {
            // readMerged(), never read(): the second would hand back the file this is about to write
            $data = $this->reader->readMerged($scope);

            // Written even when the merge came out empty
            $output->writeln(\sprintf(
                '  <info>%s</info> — %d widgets → %s',
                $scope,
                \count($data),
                $this->write($scope, $data)
            ));
        }

        return true;
    }

    /**
     * Write one scope, answering the path it landed at
     *
     * @param string $scope
     * @param array $data
     * @return string
     */
    private function write(string $scope, array $data): string
    {
        $content = "<?php\n"
            . "\n"
            . "/**\n"
            . " * Compiled Lynx widget declarations — generated, do not edit.\n"
            . " *\n"
            . " * Rebuild with: bin/magento lynx:compile widget\n"
            . " */\n"
            . "\n"
            . 'return ' . var_export($data, true) . ";\n";

        // The reader names the file, so writer and reader cannot drift apart.
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::GENERATED);
        $relative = Compiled::DIRECTORY . '/' . $this->reader->getCompiledFileName($scope);

        $directory->writeFile($relative, $content);

        return $directory->getAbsolutePath($relative);
    }
}
