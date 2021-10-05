<?php

namespace CodeQ\DeepLTranslationHelper\Domain\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Client\Browser;
use Neos\Flow\Http\Client\CurlEngine;
use Neos\Http\Factories\ServerRequestFactory;
use Neos\Http\Factories\StreamFactory;
use Psr\Http\Message\ResponseInterface;
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
     * @Flow\Inject
     * @var ServerRequestFactory
     */
    protected $serverRequestFactory;

    /**
     * @Flow\Inject
     * @var StreamFactory
     */
    protected $streamFactory;

    /**
     * @param string[] $texts
     * @param string $targetLanguage
     * @param string|null $sourceLanguage
     * @return array
     */
    public function translate(array $texts, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        // store keys and values seperately for later reunion
        $keys = array_keys($texts);
        $values = array_values($texts);

        $baseUri = $this->settings['useFreeApi'] ? $this->settings['baseUriFree'] : $this->settings['baseUri'];

        // request body ... this has to be done manually because of the non php ish format
        // with multiple text arguments
        $body = http_build_query($this->settings['defaultOptions']);
        if ($sourceLanguage) {
            $body .= '&source_lang=' . urlencode($sourceLanguage);
        }
        $body .= '&target_lang=' . urlencode($targetLanguage);
        foreach($values as $part) {
            $body .= '&text=' . urlencode($part);
        }

        $apiRequest = $this->serverRequestFactory->createServerRequest('POST', $baseUri . 'translate')
            ->withHeader('Accept', 'application/json')
            ->withHeader('Authorization', sprintf('DeepL-Auth-Key %s', $this->settings['apiAuthKey']))
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody($this->streamFactory->createStream($body));

        $browser = new Browser();
        $engine = new CurlEngine();
        $engine->setOption(CURLOPT_TIMEOUT, 0);
        $browser->setRequestEngine($engine);

        /**
         * @var ResponseInterface $apiResponse
         */
        $apiResponse = $browser->sendRequest($apiRequest);

        if ($apiResponse->getStatusCode() == 200) {
            $returnedData = json_decode($apiResponse->getBody()->getContents(), true);
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
        } else {
            if ($apiResponse->getStatusCode() === 403) {
                $this->logger->critical('Your DeepL API credentials are either wrong, or you don\'t have access to the requested API.');
            } elseif ($apiResponse->getStatusCode() === 429) {
                $this->logger->warning('You sent too many requests to the DeepL API, we\'ll retry to connect to the API on the next request');
            } elseif ($apiResponse->getStatusCode() === 456) {
                $this->logger->warning('You reached your DeepL API character limit. Upgrade your plan or wait until your quota is filled up again.');
            } elseif ($apiResponse->getStatusCode() === 400) {
                $this->logger->warning('Your DeepL API request was not well-formed. Please check the source and the target language in particular.', [
                    'sourceLanguage' => $sourceLanguage,
                    'targetLanguage' => $targetLanguage
                ]);
            } else {
                $this->logger->warning('Unexpected status from Deepl API', ['status' => $apiResponse->getStatusCode()]);
            }
            return $texts;
        }
    }
}
