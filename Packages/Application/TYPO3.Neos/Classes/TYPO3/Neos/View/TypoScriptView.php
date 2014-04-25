<?php
namespace TYPO3\Neos\View;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\View\AbstractView;
use TYPO3\TYPO3CR\Domain\Model\Node;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * Controller for displaying nodes in the frontend
 *
 */
class TypoScriptView extends AbstractView {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Service\TypoScriptService
	 */
	protected $typoScriptService;

	/**
	 * The TypoScript path to use for rendering the node given in "value", defaults to "page".
	 *
	 * @var string
	 */
	protected $typoScriptPath = 'root';

	/**
	 * @var \TYPO3\TypoScript\Core\Runtime
	 */
	protected $typoScriptRuntime;

	/**
	 * Renders the view
	 *
	 * @return string The rendered view
	 * @throws \TYPO3\Neos\Exception if no node is given
	 * @api
	 */
	public function render() {
		$currentNode = $this->getCurrentNode();
		$currentSiteNode = $currentNode->getContext()->getCurrentSiteNode();
		$typoScriptRuntime = $this->getTypoScriptRuntime($currentSiteNode);

		$typoScriptRuntime->pushContextArray(array(
			'node' => $currentNode,
			'documentNode' => $this->getClosestDocumentNode($currentNode),
			'request' => $this->controllerContext->getRequest(),
			'site' => $currentSiteNode,
			'editPreviewMode' => isset($this->variables['editPreviewMode']) ? $this->variables['editPreviewMode'] : NULL
		));
		$output = $typoScriptRuntime->render($this->typoScriptPath);
		$typoScriptRuntime->popContext();

		return $output;
	}

	/**
	 * Is it possible to render $node with $his->typoScriptPath?
	 *
	 * @return boolean TRUE if $node can be rendered at $typoScriptPath
	 *
	 * @throws \TYPO3\Neos\Exception
	 */
	public function canRenderWithNodeAndPath() {
		$currentNode = $this->getCurrentNode();
		$currentSiteNode = $currentNode->getContext()->getCurrentSiteNode();
		$typoScriptRuntime = $this->getTypoScriptRuntime($currentSiteNode);

		return $typoScriptRuntime->canRender($this->typoScriptPath);
	}

	/**
	 * Set the TypoScript path to use for rendering the node given in "value"
	 *
	 * @param string $typoScriptPath
	 * @return void
	 */
	public function setTypoScriptPath($typoScriptPath) {
		$this->typoScriptPath = $typoScriptPath;
	}

	/**
	 * @return string
	 */
	public function getTypoScriptPath() {
		return $this->typoScriptPath;
	}

	/**
	 * @param NodeInterface $node
	 * @return NodeInterface
	 */
	protected function getClosestDocumentNode(NodeInterface $node) {
		while ($node !== NULL && !$node->getNodeType()->isOfType('TYPO3.Neos:Document')) {
			$node = $node->getParent();
		}
		return $node;
	}

	/**
	 * @return NodeInterface
	 * @throws \TYPO3\Neos\Exception
	 */
	protected function getCurrentNode() {
		$currentNode = isset($this->variables['value']) ? $this->variables['value'] : NULL;
		if (!$currentNode instanceof Node) {
			throw new \TYPO3\Neos\Exception('TypoScriptView needs a variable \'value\' set with a Node object.', 1329736456);
		}
		return $currentNode;
	}

	/**
	 * @param NodeInterface $currentSiteNode
	 * @return \TYPO3\TypoScript\Core\Runtime
	 */
	protected function getTypoScriptRuntime(NodeInterface $currentSiteNode) {
		if ($this->typoScriptRuntime === NULL) {
			$this->typoScriptRuntime = $this->typoScriptService->createRuntime($currentSiteNode, $this->controllerContext);
		}
		return $this->typoScriptRuntime;
	}

	/**
	 * Clear the cached runtime instance on assignment of variables
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return TypoScriptView
	 */
	public function assign($key, $value) {
		$this->typoScriptRuntime = NULL;
		return parent::assign($key, $value);
	}

}