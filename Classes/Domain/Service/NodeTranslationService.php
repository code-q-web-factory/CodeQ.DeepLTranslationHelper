<?php

namespace CodeQ\DeepLTranslationHelper\Domain\Service;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\Context;

class NodeTranslationService
{

    /**
     * @Flow\Inject
     * @var DeepLService
     */
    protected $deeplService;

    /**
     * @Flow\InjectConfiguration(path="translateRichtextProperties")
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

            $translateProperty = false;
            $isInlineEditable = $propertyDefinitions[$propertyName]['ui']['inlineEditable'] ?? false;
            $isTranslateEnabled = $propertyDefinitions[$propertyName]['options']['autotranslate'] ?? false;
            if ($this->translateRichtextProperties && $isInlineEditable == true) {
                $translateProperty = true;
            }
            if ($isTranslateEnabled) {
                $translateProperty = true;
            }

            if ($translateProperty) {
                $translatedValue = $this->deeplService->translate($propertyValue, $targetLanguage, $sourceLanguage);
                $adoptedNode->setProperty($propertyName, $translatedValue);
            }
        }
    }
}
