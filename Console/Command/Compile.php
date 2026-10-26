<?php
/**
 * Copyright © OOCS Commerce (https://www.oocscommerce.com). All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace OOCS\LynxCompiler\Console\Command;

use OOCS\LynxCompiler\Console\Compiler\CompilerInterface;
use OOCS\LynxCompiler\Model\Compiled;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Builds what the Lynx storefront reads from generated/lynx_compile
 */
class Compile extends Command
{
    private const ARGUMENT_TYPE = 'type';

    /**
     * Compilers, name
     *
     * @param array $compilers
     * @param string|null $name
     */
    public function __construct(
        private readonly array $compilers = [],
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    /**
     * Configure
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('lynx:compile')
            ->setDescription('Compile the Lynx generated files into ' . Compiled::DIRECTORY)
            ->addArgument(
                self::ARGUMENT_TYPE,
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                $this->getTypeDescription()
            );

        parent::configure();
    }

    /**
     * Execute
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $compilers = $this->select((array) $input->getArgument(self::ARGUMENT_TYPE), $output);
        if ($compilers === null) {
            return Command::FAILURE;
        }

        $failed = [];

        foreach ($compilers as $code => $compiler) {
            $output->writeln(\sprintf('<info>%s</info> — %s', $code, $compiler->getDescription()));

            if (!$compiler->compile($input, $output)) {
                $failed[] = $code;
            }
        }

        if ($failed !== []) {
            $output->writeln(\sprintf('<error>Nothing came of: %s.</error>', implode(', ', $failed)));
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * The compilers that were asked for, or null when one of the names is not registered
     *
     * @param string[] $requested
     * @param OutputInterface $output
     * @return array<string, CompilerInterface>|null
     */
    private function select(array $requested, OutputInterface $output): ?array
    {
        if ($this->compilers === []) {
            $output->writeln('<error>Nothing is registered to compile.</error>');
            return null;
        }

        if ($requested === []) {
            return $this->compilers;
        }

        $selected = [];

        foreach ($requested as $code) {
            if (!isset($this->compilers[$code])) {
                $output->writeln(\sprintf(
                    '<error>Nothing registered under "%s".</error> There is: %s.',
                    $code,
                    implode(', ', array_keys($this->compilers))
                ));

                return null;
            }

            // Keyed, so naming the same one twice compiles it once.
            $selected[$code] = $this->compilers[$code];
        }

        return $selected;
    }

    /**
     * Symfony prints this under the argument in --help, which is the only place an operator can find out what the
     *
     * @return string
     */
    private function getTypeDescription(): string
    {
        $lines = ['What to compile. Everything is compiled otherwise. Registered:'];

        foreach ($this->compilers as $code => $compiler) {
            $lines[] = \sprintf('  %s — %s', $code, $compiler->getDescription());
        }

        return implode("\n", $lines);
    }
}
