<?php

namespace CodeQ\DeepLTranslationHelper\EelHelper;

use CodeQ\DeepLTranslationHelper\Domain\Service\DeepLService;
use Neos\Flow\Annotations as Flow;
use Neos\Eel\ProtectedContextAwareInterface;

class TranslationHelper implements ProtectedContextAwareInterface {

    /**
     * @Flow\Inject
     * @var DeepLService
     */
    protected $deepLService;

    /**
     * @param string      $text
     * @param string      $targetLanguage
     * @param string|null $sourceLanguage
     *
     * @return string
     */
    public function translate(string $text, string $targetLanguage, string $sourceLanguage = null): string
    {
        return $this->deepLService->translate($text, $targetLanguage, $sourceLanguage);
    }

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
