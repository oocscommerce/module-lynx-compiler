<?php
/**
 * Copyright © OOCS Commerce (https://www.oocscommerce.com). All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace OOCS\LynxCompiler\Controller\Adminhtml\Cache;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Filesystem;
use OOCS\LynxCompiler\Model\Compiled;

/**
 * Empties generated/lynx_compile from Cache Management
 */
class CleanCompiled extends Action implements HttpPostActionInterface
{
    /**
     * Same resource as the page the button lives on.
     */
    public const ADMIN_RESOURCE = 'Magento_Backend::cache';

    /**
     * Context, filesystem
     *
     * @param Context $context
     * @param Filesystem $filesystem
     */
    public function __construct(
        Context $context,
        private readonly Filesystem $filesystem,
    ) {
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        try {
            $directory = $this->filesystem->getDirectoryWrite(DirectoryList::GENERATED);
            if ($directory->isExist(Compiled::DIRECTORY)) {
                $directory->delete(Compiled::DIRECTORY);
            }
            $this->messageManager->addSuccessMessage(
                __('The Lynx compiled files have been removed. Rebuild them with bin/magento lynx:compile.')
            );
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('Could not remove the Lynx compiled files.')
            );
        }

        return $this->resultRedirectFactory->create()->setPath('adminhtml/cache');
    }
}
