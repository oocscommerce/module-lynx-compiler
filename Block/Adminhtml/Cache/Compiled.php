<?php
/**
 * Copyright © OOCS Commerce (https://www.oocscommerce.com). All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace OOCS\LynxCompiler\Block\Adminhtml\Cache;

use Magento\Backend\Block\Template;

/**
 * The "Flush Lynx Compile" button on Cache Management
 */
class Compiled extends Template
{
    /**
     * Endpoint the button posts to
     *
     * @return string
     */
    public function getCleanUrl(): string
    {
        return $this->getUrl('lynx_optimize/cache/cleanCompiled');
    }
}
