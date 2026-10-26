<?php
/**
 * Copyright © OOCS Commerce (https://www.oocscommerce.com). All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace OOCS\LynxCompiler\Model\Translate;

use Magento\Framework\Translate as FrameworkTranslate;

/**
 * Magento's translation loading with the database left out
 */
class Source extends FrameworkTranslate
{
    /**
     * Load Db Translation
     *
     * @return $this
     */
    protected function _loadDbTranslation()
    {
        return $this;
    }
}
