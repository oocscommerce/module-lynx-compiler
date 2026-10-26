<?php
/**
 * Copyright © OOCS Commerce (https://www.oocscommerce.com). All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace OOCS\LynxCompiler\Console\Compiler;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * One kind of thing lynx:compile knows how to build
 */
interface CompilerInterface
{
    /**
     * One line, shown in the command's help beside the code and printed as a header when this compiler runs
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Build the files, reporting progress on $output
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool false when nothing was built and the operator should hear about it in the exit code
     */
    public function compile(InputInterface $input, OutputInterface $output): bool;
}
