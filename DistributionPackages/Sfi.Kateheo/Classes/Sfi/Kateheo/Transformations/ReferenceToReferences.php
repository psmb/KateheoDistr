<?php
namespace Sfi\Kateheo\Transformations;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Migration\Transformations\AbstractTransformation;

/**
 * Migrates from reference to references type property
 */
class ReferenceToReferences extends AbstractTransformation
{
    /**
     * @var string
     */
    protected $targetPropertyName;

    /**
     * @var string
     */
    protected $sourcePropertyName;

    /**
     * @param string $propertyName
     * @return void
     */
    public function setTargetPropertyName($propertyName)
    {
        $this->targetPropertyName = $propertyName;
    }

    /**
     * @param string $propertyName
     * @return void
     */
    public function setSourcePropertyName($propertyName)
    {
        $this->sourcePropertyName = $propertyName;
    }

    /**
     * @param \Neos\ContentRepository\Domain\Model\NodeData $node
     * @return boolean
     */
    public function isTransformable(\Neos\ContentRepository\Domain\Model\NodeData $node)
    {
        return ($node->hasProperty($this->sourcePropertyName));
    }

    /**
     * @param \Neos\ContentRepository\Domain\Model\NodeData $node
     * @return void
     */
    public function execute(\Neos\ContentRepository\Domain\Model\NodeData $node)
    {
        $sourcePropertyValue = $node->getProperty($this->sourcePropertyName);
        $node->setProperty($this->targetPropertyName, [$sourcePropertyValue]);
    }
}
