<?php
namespace TYPO3\Neos\TypoScript\FlowQueryOperations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Eel\FlowQuery\Operations\AbstractOperation;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * "children" operation working on TYPO3CR nodes. It iterates over all
 * context elements and returns all child nodes or only those matching
 * the filter expression specified as optional argument.
 */
class ChildrenOperation extends AbstractOperation {

	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	static protected $shortName = 'children';

	/**
	 * {@inheritdoc}
	 *
	 * @var integer
	 */
	static protected $priority = 100;

	/**
	 * {@inheritdoc}
	 *
	 * @param array (or array-like object) $context onto which this operation should be applied
	 * @return boolean TRUE if the operation can be applied onto the $context, FALSE otherwise
	 */
	public function canEvaluate($context) {
		return count($context) === 0 || (isset($context[0]) && ($context[0] instanceof NodeInterface));
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param FlowQuery $flowQuery the FlowQuery object
	 * @param array $arguments the arguments for this operation
	 * @return void
	 */
	public function evaluate(FlowQuery $flowQuery, array $arguments) {
		$output = array();
		$outputNodePaths = array();
		if (isset($arguments[0]) && !empty($arguments[0])) {
			$parsedFilter = \TYPO3\Eel\FlowQuery\FizzleParser::parseFilterGroup($arguments[0]);
			if ($this->earlyOptimizationOfFilters($flowQuery, $parsedFilter)) {
				return;
			}
		}

		foreach ($flowQuery->getContext() as $contextNode) {
			foreach ($contextNode->getChildNodes() as $childNode) {
				if (!isset($outputNodePaths[$childNode->getPath()])) {
					$output[] = $childNode;
					$outputNodePaths[$childNode->getPath()] = TRUE;
				}
			}
		}
		$flowQuery->setContext($output);

		if (isset($arguments[0]) && !empty($arguments[0])) {
			$flowQuery->pushOperation('filter', $arguments);
		}
	}

	/**
	 * @param FlowQuery $flowQuery
	 * @param array $parsedFilter
	 * @return boolean
	 */
	protected function earlyOptimizationOfFilters(FlowQuery $flowQuery, array $parsedFilter) {
		$output = array();
		$outputNodePaths = array();

		if (isset($parsedFilter['Filters'][0]['PropertyNameFilter']) && count($parsedFilter['Filters']) === 1) {
			$flowQuery->pushOperation('find', array($parsedFilter['Filters'][0]['PropertyNameFilter']));
			return TRUE;
		}

		if (isset($parsedFilter['Filters'][0]['AttributeFilters']) && count($parsedFilter['Filters']) === 1 && count($parsedFilter['Filters'][0]['AttributeFilters']) === 1 && $parsedFilter['Filters'][0]['AttributeFilters'][0]['Operator'] === 'instanceof') {
			foreach ($flowQuery->getContext() as $contextNode) {
				foreach ($contextNode->getChildNodes($parsedFilter['Filters'][0]['AttributeFilters'][0]['Operand']) as $childNode) {
					if (!isset($outputNodePaths[$childNode->getPath()])) {
						$output[] = $childNode;
						$outputNodePaths[$childNode->getPath()] = TRUE;
					}
				}
			}
			$flowQuery->setContext($output);
			return TRUE;
		}

		return FALSE;
	}
}
