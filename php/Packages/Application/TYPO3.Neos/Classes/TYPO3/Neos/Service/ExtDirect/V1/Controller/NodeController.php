<?php
namespace TYPO3\Neos\Service\ExtDirect\V1\Controller;

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
use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\ExtJS\Annotations\ExtDirect;
use TYPO3\TYPO3CR\Domain\Model\Node;

/**
 * ExtDirect Controller for managing Nodes
 *
 * @Flow\Scope("singleton")
 */
class NodeController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @var string
	 */
	protected $viewObjectNamePattern = 'TYPO3\Neos\Service\ExtDirect\V1\View\NodeView';

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager
	 */
	protected $nodeTypeManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Service\NodeSearchService
	 */
	protected $nodeSearchService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * Select special error action
	 *
	 * @return void
	 */
	protected function initializeAction() {
		$this->errorMethodName = 'extErrorAction';
		if ($this->arguments->hasArgument('referenceNode')) {
			$this->arguments->getArgument('referenceNode')->getPropertyMappingConfiguration()->setTypeConverterOption('TYPO3\TYPO3CR\TypeConverter\NodeConverter', \TYPO3\TYPO3CR\TypeConverter\NodeConverter::REMOVED_CONTENT_SHOWN, TRUE);
		}
		$this->uriBuilder->setRequest($this->request->getMainRequest());
	}

	/**
	 * Returns the specified node
	 *
	 * @param Node $node
	 * @return void
	 * @ExtDirect
	 */
	public function showAction(Node $node) {
		$this->view->assignNode($node);
	}

	/**
	 * Returns the primary child node (if any) of the specified node
	 *
	 * @param Node $node
	 * @return void
	 * @ExtDirect
	 */
	public function getPrimaryChildNodeAction(Node $node) {
		$this->view->assignNode($node->getPrimaryChildNode());
	}

	/**
	 * Return child nodes of specified node for usage in a TreeLoader
	 *
	 * @param Node $node The node to find child nodes for
	 * @param string $nodeTypeFilter A node type filter
	 * @param integer $depth levels of childNodes (0 = unlimited)
	 * @param \TYPO3\TYPO3CR\Domain\Model\Node $untilNode expand the child nodes until $untilNode is reached, independent of $depth
	 * @return void
	 * @ExtDirect
	 */
	public function getChildNodesForTreeAction(Node $node, $nodeTypeFilter, $depth, Node $untilNode) {
		$this->view->assignChildNodes($node, $nodeTypeFilter, \TYPO3\Neos\Service\ExtDirect\V1\View\NodeView::STYLE_TREE, $depth, $untilNode);
	}

	/**
	 * Return child nodes of specified node for usage in a TreeLoader based on filter
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\Node $node The node to find child nodes for
	 * @param string $term
	 * @param string $nodeType
	 * @return void
	 * @ExtDirect
	 */
	public function filterChildNodesForTreeAction(\TYPO3\TYPO3CR\Domain\Model\Node $node, $term, $nodeType) {
		$nodeTypes = strlen($nodeType) > 0 ? array($nodeType) : array_keys($this->nodeTypeManager->getSubNodeTypes('TYPO3.Neos:Document', FALSE));
		$this->view->assignFilteredChildNodes(
			$node,
			$this->nodeSearchService->findByProperties($term, $nodeTypes, $node->getContext())
		);
	}

	/**
	 * Return child nodes of specified node with all details and
	 * metadata.
	 *
	 * @param Node $node
	 * @param string $nodeTypeFilter A node type filter
	 * @param integer $depth levels of childNodes (0 = unlimited)
	 * @return void
	 * @ExtDirect
	 */
	public function getChildNodesAction(Node $node, $nodeTypeFilter, $depth) {
		$this->view->assignChildNodes($node, $nodeTypeFilter, \TYPO3\Neos\Service\ExtDirect\V1\View\NodeView::STYLE_LIST, $depth);
	}

	/**
	 * Return child nodes of specified node with all details and
	 * metadata.
	 *
	 * @param Node $node
	 * @param string $nodeTypeFilter A node type filter
	 * @param integer $depth levels of childNodes (0 = unlimited)
	 * @return void
	 * @ExtDirect
	 */
	public function getChildNodesFromParentAction(Node $node, $nodeTypeFilter, $depth) {
		$this->getChildNodesAction($node->getParent(), $nodeTypeFilter, $depth);
	}

	/**
	 * Creates a new node
	 *
	 * @param Node $referenceNode
	 * @param array $nodeData
	 * @param string $position where the node should be added (allowed: before, into, after)
	 * @return void
	 * @throws \InvalidArgumentException
	 * @todo maybe the actual creation should be put in a helper / service class
	 * @ExtDirect
	 */
	public function createAction(Node $referenceNode, array $nodeData, $position) {
		$newNode = $this->createNewNode($referenceNode, $nodeData, $position);
		$nextUri = $this->uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $newNode), 'Frontend\Node', 'TYPO3.Neos');
		$this->view->assign('value', array('data' => array('nextUri' => $nextUri), 'success' => TRUE));
	}

	/**
	 * Creates a new node and renders the node inside the containing section
	 *
	 * @param Node $referenceNode
	 * @param string $typoScriptPath The TypoScript path of the collection
	 * @param array $nodeData
	 * @param string $position where the node should be added (allowed: before, into, after)
	 * @return string
	 * @throws \InvalidArgumentException
	 * @ExtDirect
	 */
	public function createAndRenderAction(Node $referenceNode, $typoScriptPath, array $nodeData, $position) {
		$newNode = $this->createNewNode($referenceNode, $nodeData, $position);

		$view = new \TYPO3\Neos\View\TypoScriptView();
		$this->controllerContext->getRequest()->setFormat('html');
		$view->setControllerContext($this->controllerContext);

		$view->setTypoScriptPath($typoScriptPath);
		$view->assign('value', $newNode->getParent());

		$result = $view->render();
		$this->response->setResult(array('collectionContent' => $result, 'nodePath' => $newNode->getContextPath()));
		$this->response->setSuccess(TRUE);

		return '';
	}

	/**
	 * Creates a new node and returns tree structure
	 *
	 * @param Node $referenceNode
	 * @param array $nodeData
	 * @param string $position where the node should be added, -1 is before, 0 is in, 1 is after
	 * @return void
	 * @throws \InvalidArgumentException
	 * @todo maybe the actual creation should be put in a helper / service class
	 * @ExtDirect
	 */
	public function createNodeForTheTreeAction(Node $referenceNode, array $nodeData, $position) {
		$newNode = $this->createNewNode($referenceNode, $nodeData, $position);

		if (!isset($nodeData['nodeName']) && $newNode->getNodeType()->isOfType('TYPO3.Neos:Document')) {
			// TODO: we should actually give preference to $nodeData['nodeName'] here if it is set.
			$idealNodeName = \TYPO3\TYPO3CR\Utility::renderValidNodeName($newNode->hasProperty('title') ? $newNode->getProperty('title') : uniqid('node'));
			$possibleNodeName = $idealNodeName;
			$counter = 1;
			while ($referenceNode->getNode($possibleNodeName) !== NULL) {
				$possibleNodeName = $idealNodeName . '-' . $counter;
				$counter++;
			}

			$newNode->setName($possibleNodeName);
		}

		$this->view->assign('value', array('data' => $this->view->collectTreeNodeData($newNode), 'success' => TRUE));
	}

	/**
	 * @param Node $referenceNode
	 * @param array $nodeData
	 * @param string $position
	 * @return Node
	 * @throws \InvalidArgumentException
	 */
	protected function createNewNode(Node $referenceNode, array $nodeData, $position) {
		if (!in_array($position, array('before', 'into', 'after'), TRUE)) {
			throw new \InvalidArgumentException('The position should be one of the following: "before", "into", "after".', 1347133640);
		}

		if (empty($nodeData['nodeName'])) {
			$nodeData['nodeName'] = uniqid('node');
		}
		$nodeType = $this->nodeTypeManager->getNodeType($nodeData['nodeType']);

		if ($position === 'into') {
			$newNode = $referenceNode->createNode($nodeData['nodeName'], $nodeType);
		} else {
			$parentNode = $referenceNode->getParent();
			$newNode = $parentNode->createNode($nodeData['nodeName'], $nodeType);

			if ($position === 'before') {
				$newNode->moveBefore($referenceNode);
			} else {
				$newNode->moveAfter($referenceNode);
			}
		}

		if (isset($nodeData['properties']) && is_array($nodeData['properties'])) {
			foreach ($nodeData['properties'] as $propertyName => $propertyValue) {
				$newNode->setProperty($propertyName, $propertyValue);
			}
		}

		return $newNode;
	}

	/**
	 * Move $node before, into or after $targetNode
	 *
	 * @param Node $node
	 * @param Node $targetNode
	 * @param string $position where the node should be added (allowed: before, into, after)
	 * @return void
	 * @throws \TYPO3\TYPO3CR\Exception\NodeException
	 * @ExtDirect
	 */
	public function moveAction(Node $node, Node $targetNode, $position) {
		if (!in_array($position, array('before', 'into', 'after'), TRUE)) {
			throw new \TYPO3\TYPO3CR\Exception\NodeException('The position should be one of the following: "before", "into", "after".', 1296132542);
		}

		switch ($position) {
			case 'before':
				$node->moveBefore($targetNode);
			break;
			case 'into':
				$node->moveInto($targetNode);
			break;
			case 'after':
				$node->moveAfter($targetNode);
		}

		$data = array('newNodePath' => $node->getContextPath());
		if ($node->getNodeType()->isOfType('TYPO3.Neos:Document')) {
			$data['nextUri'] = $this->uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $node), 'Frontend\Node', 'TYPO3.Neos');
		}
		$this->view->assign('value', array('data' => $data, 'success' => TRUE));
	}

	/**
	 * Move $node before $targetNode
	 *
	 * @param Node $node
	 * @param Node $targetNode
	 * @return void
	 * @ExtDirect
	 */
	public function moveBeforeAction(Node $node, Node $targetNode) {
		$node->moveBefore($targetNode);
		$this->view->assign('value', array('success' => TRUE));
	}

	/**
	 * Move $node after $targetNode
	 *
	 * @param Node $node
	 * @param Node $targetNode
	 * @return void
	 * @ExtDirect
	 */
	public function moveAfterAction(Node $node, Node $targetNode) {
		$node->moveAfter($targetNode);
		$this->view->assign('value', array('success' => TRUE));
	}

	/**
	 * Move $node into $targetNode
	 *
	 * @param Node $node
	 * @param Node $targetNode
	 * @return void
	 * @ExtDirect
	 */
	public function moveIntoAction(Node $node, Node $targetNode) {
		$node->moveInto($targetNode);
		$this->view->assign('value', array('success' => TRUE));
	}

	/**
	 * Copy $node before, into or after $targetNode
	 *
	 * @param Node $node
	 * @param Node $targetNode
	 * @param string $position where the node should be added (allowed: before, into, after)
	 * @param string $nodeName optional node name (if empty random node name will be generated)
	 * @return void
	 * @throws \TYPO3\TYPO3CR\Exception\NodeException
	 * @ExtDirect
	 */
	public function copyAction(Node $node, Node $targetNode, $position, $nodeName) {
		if (!in_array($position, array('before', 'into', 'after'), TRUE)) {
			throw new \TYPO3\TYPO3CR\Exception\NodeException('The position should be one of the following: "before", "into", "after".', 1346832303);
		}

		if ($nodeName !== '') {
			$idealNodeName = $nodeName;
			$possibleNodeName = $idealNodeName;
			$counter = 1;
			$parentNode = $position === 'into' ? $targetNode : $targetNode->getParent();
			while ($parentNode->getNode($possibleNodeName) !== NULL) {
				$possibleNodeName = $idealNodeName . '-' . $counter;
				$counter++;
			}
		}
		$nodeName = isset($possibleNodeName) ? $possibleNodeName : uniqid('node');

		switch ($position) {
			case 'before':
				$copiedNode = $node->copyBefore($targetNode, $nodeName);
			break;
			case 'after':
				$copiedNode = $node->copyAfter($targetNode, $nodeName);
			break;
			case 'into':
			default:
				$copiedNode = $node->copyInto($targetNode, $nodeName);
		}

		$q = new FlowQuery(array($copiedNode));
		$closestDocumentNode = $q->closest('[instanceof TYPO3.Neos:Document]')->get(0);
		$this->view->assign(
			'value',
			array(
				'data' => array(
					'nodeUri' => $this->uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $copiedNode), 'Frontend\Node', 'TYPO3.Neos'),
					'nextUri' => $this->uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $closestDocumentNode), 'Frontend\Node', 'TYPO3.Neos'),
					'newNodePath' => $copiedNode->getContextPath()
				),
				'success' => TRUE
			)
		);
	}

	/**
	 * Copy $node before $targetNode
	 *
	 * @param Node $node
	 * @param Node $targetNode
	 * @param string $nodeName optional node name (if empty random node name will be generated)
	 * @return void
	 * @ExtDirect
	 */
	public function copyBeforeAction(Node $node, Node $targetNode, $nodeName = '') {
		$nodeName = $nodeName === '' ? uniqid('node') : $nodeName;
		$node->copyBefore($targetNode, $nodeName);
		$this->view->assign('value', array('success' => TRUE));
	}

	/**
	 * Copy $node after $targetNode
	 *
	 * @param Node $node
	 * @param Node $targetNode
	 * @param string $nodeName optional node name (if empty random node name will be generated)
	 * @return void
	 * @ExtDirect
	 */
	public function copyAfterAction(Node $node, Node $targetNode, $nodeName = '') {
		$nodeName = $nodeName === '' ? uniqid('node') : $nodeName;
		$node->copyAfter($targetNode, $nodeName);
		$this->view->assign('value', array('success' => TRUE));
	}

	/**
	 * Copy $node into $targetNode
	 *
	 * @param Node $node
	 * @param Node $targetNode
	 * @param string $nodeName optional node name (if empty random node name will be generated)
	 * @return void
	 * @ExtDirect
	 */
	public function copyIntoAction(Node $node, Node $targetNode, $nodeName = '') {
		$nodeName = $nodeName === '' ? uniqid('node') : $nodeName;
		$node->copyInto($targetNode, $nodeName);
		$this->view->assign('value', array('success' => TRUE));
	}

	/**
	 * Updates the specified node. Returns the following data:
	 * - the (possibly changed) workspace name of the node
	 * - the URI of the closest document node. If $node is a document node (f.e. a Page), the own URI is returned.
	 *   This is important to handle renamings of nodes correctly.
	 *
	 * Note: We do not call $nodeDataRepository->update() here, as TYPO3CR has a stateful API for now.
	 *
	 * @param Node $node
	 * @return void
	 * @ExtDirect
	 */
	public function updateAction(Node $node) {
		$q = new FlowQuery(array($node));
		$closestDocumentNode = $q->closest('[instanceof TYPO3.Neos:Document]')->get(0);
		$nextUri = $this->uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $closestDocumentNode), 'Frontend\Node', 'TYPO3.Neos');
		$this->view->assign('value', array('data' => array('workspaceNameOfNode' => $node->getWorkspace()->getName(), 'nextUri' => $nextUri), 'success' => TRUE));
	}

	/**
	 * Deletes the specified node and all of its sub nodes
	 *
	 * @param Node $node
	 * @return void
	 * @ExtDirect
	 */
	public function deleteAction(Node $node) {
		$q = new FlowQuery(array($node));
		$node->remove();
		$closestDocumentNode = $q->closest('[instanceof TYPO3.Neos:Document]')->get(0);
		$nextUri = $this->uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(TRUE)->uriFor('show', array('node' => $closestDocumentNode), 'Frontend\Node', 'TYPO3.Neos');

		$this->view->assign('value', array('data' => array('nextUri' => $nextUri), 'success' => TRUE));
	}

	/**
	 * Search a page, needed for internal links.
	 *
	 * @param string $query
	 * @return void
	 * @ExtDirect
	 */
	public function searchPageAction($query) {
		$searchResult = array();

		$documentNodeTypes = $this->nodeTypeManager->getSubNodeTypes('TYPO3.Neos:Document');
		foreach ($this->nodeSearchService->findByProperties($query, $documentNodeTypes, $this->createContext('live')) as $node) {
			$searchResult[$node->getPath()] = $this->processNodeForEditorPlugins($node);
		}

		$this->view->assign('value', array('searchResult' => $searchResult, 'success' => TRUE));
	}

	/**
	 * Get the page by the node path, needed for internal links.
	 *
	 * @param string $nodePath
	 * @return void
	 * @ExtDirect
	 */
	public function getPageByNodePathAction($nodePath) {
		$contentContext = $this->createContext('live');

		$node = $contentContext->getNode($nodePath);
		$this->view->assign('value', array('node' => $this->processNodeForEditorPlugins($node), 'success' => TRUE));
	}

	/**
	 * Returns an array with the data needed by for example the Hallo and Aloha
	 * link plugins to represent the passed Node instance.
	 *
	 * @param Node $node
	 * @return array
	 */
	protected function processNodeForEditorPlugins(Node $node) {
		return array(
			'id' => $node->getPath(),
			'name' => $node->getLabel(),
			'url' => $this->uriBuilder->uriFor('show', array('node' => $node), 'Frontend\Node', 'TYPO3.Neos'),
			'type' => 'neos/internal-link'
		);
	}

	/**
	 * A preliminary error action for handling validation errors
	 * by assigning them to the ExtDirect View that takes care of
	 * converting them.
	 *
	 * @return void
	 */
	public function extErrorAction() {
		$this->view->assignErrors($this->arguments->getValidationResults());
	}

	/**
	 * Create a Context for a workspace given by name to be used in this
	 * controller.
	 *
	 * @param string $workspaceName
	 * @return \TYPO3\TYPO3CR\Domain\Service\ContextInterface
	 */
	protected function createContext($workspaceName) {
		$contextProperties = array(
			'workspaceName' => $workspaceName
		);

		return $this->contextFactory->create($contextProperties);
	}
}
