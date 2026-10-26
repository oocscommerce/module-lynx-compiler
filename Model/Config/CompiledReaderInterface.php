<?php
/**
 * Copyright © OOCS Commerce (https://www.oocscommerce.com). All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace OOCS\LynxCompiler\Model\Config;

/**
 * A filesystem config reader that can answer from a compiled file
 */
interface CompiledReaderInterface
{
    /**
     * The XML merge itself, with any compiled file ignored
     *
     * @param string|null $scope
     * @return array<string, mixed>
     */
    public function readMerged($scope = null): array;

    /**
     * What the compiled file for this reader and scope is called
     *
     * @param string $scope
     * @return string
     */
    public function getCompiledFileName(string $scope): string;
}
