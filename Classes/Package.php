<?php
namespace CodeQ\DeepLTranslationHelper;

use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Package\Package as BasePackage;
use Neos\ContentRepository\Domain\Service\Context;
use CodeQ\DeepLTranslationHelper\Domain\Service\NodeTranslationService;

/**
 * The Neos Package
 */
class Package extends BasePackage
{
    /**
     * @param Bootstrap $bootstrap The current bootstrap
     * @return void
     */
    public function boot(Bootstrap $bootstrap)
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();
        $dispatcher->connect(Context::class, 'afterAdoptNode', NodeTranslationService::class, 'afterAdoptNode');
    }
}
