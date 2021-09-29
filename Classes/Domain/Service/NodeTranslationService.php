<?php

namespace CodeQ\DeepLTranslationHelper\Domain\Service;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\Context;
use Psr\Log\LoggerInterface;

class NodeTranslationService
{

    /**
     * @Flow\Inject
     * @var DeepLService
     */
    protected $deeplService;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @Flow\InjectConfiguration(path="nodeTranslations.translateInlineEditables")
     * @var bool
     */
    protected $translateRichtextProperties;

    /**
     * @param NodeInterface $node
     * @param Context $context
     * @param $recursive
     * @return void
     */
    public function afterAdoptNode(NodeInterface $node, Context $context, $recursive)
    {
        $propertyDefinitions = $node->getNodeType()->getProperties();
        $adoptedNode = $context->getNodeByIdentifier($node->getIdentifier());

        $sourceLanguage = explode('_', $node->getContext()->getTargetDimensions()['language'])[0];
        $targetLanguage = explode('_', $context->getTargetDimensions()['language'])[0];

        $propertiesToTranslate = [];
        foreach ($adoptedNode->getProperties() as $propertyName => $propertyValue) {

            if (empty($propertyValue)) {
                continue;
            }
            if (!array_key_exists($propertyName, $propertyDefinitions)) {
                continue;
            }
            if ($propertyDefinitions[$propertyName]['type'] != 'string' || !is_string($propertyValue)) {
                continue;
            }
            if ((trim(strip_tags($propertyValue))) == "") {
                continue;
            }

            $translateProperty = false;
            $isInlineEditable = $propertyDefinitions[$propertyName]['ui']['inlineEditable'] ?? false;
            $isTranslateEnabled = $propertyDefinitions[$propertyName]['options']['deeplTranslate'] ?? false;
            if ($this->translateRichtextProperties && $isInlineEditable == true) {
                $translateProperty = true;
            }
            if ($isTranslateEnabled) {
                $translateProperty = true;
            }

            if ($translateProperty) {
                $propertiesToTranslate[$propertyName] = $propertyValue;
            }
        }

        if (count($propertiesToTranslate) > 0) {
            $translatedProperties = $this->deeplService->translate($propertiesToTranslate, $targetLanguage, $sourceLanguage);
            foreach($translatedProperties as $propertyName => $translatedValue) {
                $adoptedNode->setProperty($propertyName, $translatedValue);
            }
        }
    }
}
