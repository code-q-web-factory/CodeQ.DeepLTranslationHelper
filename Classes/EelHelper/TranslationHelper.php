<?php

namespace CodeQ\DeepLTranslationHelper\EelHelper;

use Neos\Flow\Annotations as Flow;
use Neos\Eel\ProtectedContextAwareInterface;

class TranslationHelper implements ProtectedContextAwareInterface {

    /**
     * @param string $methodName
     *
     * @return bool
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
