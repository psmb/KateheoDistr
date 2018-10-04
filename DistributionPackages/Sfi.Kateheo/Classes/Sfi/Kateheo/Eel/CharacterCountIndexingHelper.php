<?php
namespace Sfi\Kateheo\Eel;

use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\Unicode\Functions as UnicodeFunctions;

class CharacterCountIndexingHelper implements ProtectedContextAwareInterface
{
    /**
     * @param NodeInterface $node
     * @return int
     */
    public function childrenTextLength($node)
    {
        $length = 0;
        $q = new FlowQuery([$node]);
        $contentNodes = $q->children('main')->children('[instanceof Neos.Neos:Content]')->get();
        foreach ($contentNodes as $node) {
            $title = strip_tags($node->getProperty('title'));
            if (is_string($title)) {
                $length += UnicodeFunctions::strlen((string)$title);
            }

            $text = strip_tags($node->getProperty('text'));
            if (is_string($text)) {
                $length += UnicodeFunctions::strlen((string)$text);
            }
        }
        return $length;
    }

    /**
     * All methods are considered safe
     *
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
