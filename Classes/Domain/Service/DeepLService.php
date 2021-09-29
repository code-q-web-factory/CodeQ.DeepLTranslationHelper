<?php

namespace CodeQ\DeepLTranslationHelper\Domain\Service;

use Neos\Flow\Annotations as Flow;
use Psr\Log\LoggerInterface;

/**
 * @Flow\Scope("singleton")
 */
class DeepLService
{

    /**
     * @var array
     * @Flow\InjectConfiguration(path="DeepLService")
     */
    protected array $settings;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param string[] $texts
     * @param string $targetLanguage
     * @param string|null $sourceLanguage
     * @return array
     */
    public function translate(array $texts, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        $keys = array_keys($texts);
        $values = array_values($texts);

        $baseUri = $this->settings['useFreeApi'] ? $this->settings['baseUriFree'] : $this->settings['baseUri'];

        $curlHandle = curl_init($baseUri . 'translate');
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, 0);
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, ['Expect:']);
        curl_setopt($curlHandle, CURLOPT_POST, true);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, [
            'Accept: */*',
            'Content-Type: application/x-www-form-urlencoded',
            sprintf('Authorization: DeepL-Auth-Key %s', $this->settings['apiAuthKey'])
        ]);

        // create request body ... neither psr nor guzzle can create the body format that
        // is required here
        $body = http_build_query($this->settings['defaultOptions']);
        if ($sourceLanguage) {
            $body .= '&source_lang=' . urlencode($sourceLanguage);
        }
        $body .= '&target_lang=' . urlencode($targetLanguage);

        foreach($values as $part) {
            $body .= '&text=' . urlencode($part);
        }

        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $body);

        // return
        $curlResult = curl_exec($curlHandle);
        if ($curlResult === false) {
            return $texts;
        }

        $status = curl_getinfo($curlHandle, CURLINFO_RESPONSE_CODE);

        if ($status != 200) {
            if ($status === 403) {
                $this->logger->critical('Your DeepL API credentials are either wrong, or you don\'t have access to the requested API.');
            } elseif ($status === 429) {
                $this->logger->warning('You sent too many requests to the DeepL API, we\'ll retry to connect to the API on the next request');
            } elseif ($status === 456) {
                $this->logger->warning('You reached your DeepL API character limit. Upgrade your plan or wait until your quota is filled up again.');
            } elseif ($status === 400) {
                $this->logger->warning('Your DeepL API request was not well-formed. Please check the source and the target language in particular.', [
                    'sourceLanguage' => $sourceLanguage,
                    'targetLanguage' => $targetLanguage
                ]);
            } else {
                $this->logger->warning('Unexpected status from Deepl API', ['status' => $status]);
            }
            return $texts;
        }

        curl_close($curlHandle);

        $returnedData = json_decode($curlResult, true);

        if (is_null($returnedData)) {
            return $texts;
        }

        $translations = array_map(
            function($part) {
                return $part['text'];
            },
            $returnedData['translations']
        );

        return array_combine($keys, $translations);
    }
}
