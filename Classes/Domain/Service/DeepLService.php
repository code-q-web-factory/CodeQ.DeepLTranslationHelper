<?php

namespace CodeQ\DeepLTranslationHelper\Domain\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * @Flow\Scope("singleton")
 */
class DeepLService
{
    /**
     * @var Client|null
     */
    protected ?Client $deeplClient = null;

    /**
     * @var array
     * @Flow\InjectConfiguration(path="DeepLService")
     */
    protected array $settings;

    /**
     * @var VariableFrontend
     */
    protected $translationCache;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    protected function initializeObject()
    {
        $this->deeplClient = new Client([
            'base_uri' => $this->settings['baseUri'],
            'timeout' => 0,
            'headers' => [
                'Authorization' => sprintf('DeepL-Auth-Key %s', $this->settings['apiAuthKey'])
            ]
        ]);
    }

    /**
     * @param string      $text
     * @param string      $targetLanguage
     *
     * @param string|null $sourceLanguage
     *
     * @return string
     */
    public function translate(
        string $text,
        string $targetLanguage,
        string $sourceLanguage = null
    ): string {
        if ($sourceLanguage === $targetLanguage) {
            return $text;
        }

        // See: https://ideone.com/embed/0iwuGn
        $cacheIdentifier = sprintf('%s-%s', hash('haval256,3', $text),
            $targetLanguage);
        $translatedText = $this->translationCache->get($cacheIdentifier);

        if ($translatedText === false) {
            try {
                $response = $this->deeplClient->get('translate', [
                    'query' => [
                        'text' => $text,
                        'source_lang' => $sourceLanguage,
                        'target_lang' => $targetLanguage,
                        'tag_handling' => 'xml',
                        'split_sentences' => 'nonewlines'
                    ]
                ]);

                $responseBody = json_decode($response->getBody()->getContents(),
                    true);
                $translations = $responseBody['translations'];
                $translatedText = $translations[0]['text'];
                try {
                    $this->translationCache->set($cacheIdentifier,
                        $translatedText);
                } catch (\Neos\Cache\Exception $e) {
                    $this->logger->critical('Wrong cache frontend configuration for CodeQ_DeepLTranslationHelper_Translation cache defined!');
                } catch (InvalidArgumentException $e) {
                    $this->logger->critical($e->getMessage());
                }
            } catch (ClientException $e) {
                if ($e->getResponse()->getStatusCode() === 403) {
                    $this->logger->critical('Your DeepL API credentials are either wrong, or you don\'t have access to the requested API.');
                } elseif ($e->getResponse()->getStatusCode() === 429) {
                    $this->logger->warning('You sent too many requests to the DeepL API, we\'ll retry to connect to the API on the next request');
                } elseif ($e->getResponse()->getStatusCode() === 456) {
                    $this->logger->warning('You reached your DeepL API character limit. Upgrade your plan or wait until your quota is filled up again.');
                } elseif ($e->getResponse()->getStatusCode() === 400) {
                    $this->logger->warning('Your DeepL API request was not well-formed. Please check the source and the target language in particular.', [
                        'sourceLanguage' => $sourceLanguage,
                        'targetLanguage' => $targetLanguage
                    ]);
                } else {
                    $this->logger->warning('The DeepL API request did not complete successfully, see status code and message below.', [
                        'statusCode' => $e->getResponse()->getStatusCode(),
                        'message' => $e->getResponse()->getBody()->getContents()
                    ]);
                }

                // If the call went wrong, return the original text
                $translatedText = $text;
            } catch (GuzzleException $e) {
                $this->logger->warning('The DeepL API request did not complete successfully, see status code and message below.', [
                    'statusCode' => $e->getResponse()->getStatusCode(),
                    'message' => $e->getResponse()->getBody()->getContents()
                ]);

                // If the call went wrong, return the original text
                $translatedText = $text;
            }
        }

        return $translatedText;
    }
}
