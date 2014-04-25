<?php
namespace TYPO3\TYPO3CR\Migration\Filters;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Filter nodes by node type.
 */
class NodeType implements FilterInterface {

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager
	 * @Flow\Inject
	 */
	protected $nodeTypeManager;

	/**
	 * The node type to match on.
	 *
	 * @var string
	 */
	protected $nodeTypeName;

	/**
	 * If set to true also all subtypes of the given nodeType will match.
	 *
	 * @var boolean
	 */
	protected $withSubTypes = FALSE;

	/**
	 * Sets the node type name to match on.
	 *
	 * @param string $nodeTypeName
	 * @return void
	 */
	public function setNodeType($nodeTypeName) {
		$this->nodeTypeName = $nodeTypeName;
	}

	/**
	 * Whether the filter should match also on all subtypes of the configured
	 * node type.
	 *
	 * Note: This can only be used with node types still available in the
	 * system!
	 *
	 * @param boolean $withSubTypes
	 * @return void
	 */
	public function setWithSubTypes($withSubTypes) {
		$this->withSubTypes = $withSubTypes;
	}

	/**
	 * Returns TRUE if the given node is of the node type this filter expects.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return boolean
	 */
	public function matches(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		if ($this->withSubTypes === TRUE) {
			return $this->nodeTypeManager->getNodeType($node->getNodeType())->isOfType($this->nodeTypeName);
		} else {
			$nodeData = \TYPO3\Flow\Reflection\ObjectAccess::getProperty($node, 'nodeData', TRUE);
			$nodeType = \TYPO3\Flow\Reflection\ObjectAccess::getProperty($nodeData, 'nodeType', TRUE);
			return $nodeType === $this->nodeTypeName;
		}
	}

}
