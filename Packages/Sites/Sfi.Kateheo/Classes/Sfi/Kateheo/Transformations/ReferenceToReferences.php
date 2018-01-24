<?php
namespace Sfi\Kateheo\Transformations;

use Neos\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Migration\Transformations\AbstractTransformation;

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
     * @param \TYPO3\TYPO3CR\Domain\Model\NodeData $node
     * @return boolean
     */
    public function isTransformable(\TYPO3\TYPO3CR\Domain\Model\NodeData $node)
    {
        return ($node->hasProperty($this->sourcePropertyName));
    }

    /**
     * @param \TYPO3\TYPO3CR\Domain\Model\NodeData $node
     * @return void
     */
    public function execute(\TYPO3\TYPO3CR\Domain\Model\NodeData $node)
    {
        $sourcePropertyValue = $node->getProperty($this->sourcePropertyName);
        $node->setProperty($this->targetPropertyName, [$sourcePropertyValue]);
    }
}
