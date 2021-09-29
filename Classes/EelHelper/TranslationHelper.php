<?php

namespace CodeQ\DeepLTranslationHelper\EelHelper;

use CodeQ\DeepLTranslationHelper\Domain\Service\DeepLService;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Annotations as Flow;
use Neos\Eel\ProtectedContextAwareInterface;

class TranslationHelper implements ProtectedContextAwareInterface {

    /**
     * @Flow\Inject
     * @var DeepLService
     */
    protected $deepLService;

    /**
     * @Flow\Inject
     * @var VariableFrontend
     */
    protected $translationCache;

    /**
     * @param string      $text
     * @param string      $targetLanguage
     * @param string|null $sourceLanguage
     *
     * @return string
     */
    public function translate(string $text, string $targetLanguage, string $sourceLanguage = null): string
    {
        // See: https://ideone.com/embed/0iwuGn
        $cacheIdentifier = sprintf('%s-%s-%s', hash('haval256,3', $text), $sourceLanguage, $targetLanguage);
        if ($translatedText = $this->translationCache->get($cacheIdentifier)) {
            return $translatedText;
        }
        $translatedText = $this->deepLService->translate($text, $targetLanguage, $sourceLanguage);
        $this->translationCache->set($cacheIdentifier, $translatedText);
        return $translatedText;
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
