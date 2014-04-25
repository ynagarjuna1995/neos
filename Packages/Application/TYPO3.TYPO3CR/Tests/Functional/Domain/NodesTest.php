<?php
namespace TYPO3\TYPO3CR\Tests\Functional\Domain;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\TYPO3CR\Domain\Service\Context;

/**
 * Functional test case which covers all Node-related behavior of the
 * content repository as long as they reside in the live workspace.
 */
class NodesTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextInterface
	 */
	protected $context;

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->nodeDataRepository = new \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository();
		$this->contextFactory = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface');
		$this->context = $this->contextFactory->create(array('workspaceName' => 'live'));
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();
		$this->inject($this->contextFactory, 'contextInstances', array());
	}

	/**
	 * @test
	 */
	public function setPathWorksRecursively() {
		$rootNode = $this->context->getRootNode();

		$fooNode = $rootNode->createNode('foo');
		$bazNode = $fooNode->createNode('bar')->createNode('baz');

		$fooNode->setPath('/quux');

		$this->assertEquals('/quux/bar/baz', $bazNode->getPath());
	}

	/**
	 * @test
	 */
	public function nodesCanBeRenamed() {
		$rootNode = $this->context->getRootNode();

		$fooNode = $rootNode->createNode('foo');
		$barNode = $fooNode->createNode('bar');
		$bazNode = $barNode->createNode('baz');

		$fooNode->setName('quux');
		$barNode->setName('lax');

		$this->assertNull($rootNode->getNode('foo'));
		$this->assertEquals('/quux/lax/baz', $bazNode->getPath());
	}

	/**
	 * @test
	 */
	public function nodesCreatedInTheLiveWorkspacesCanBeRetrievedAgainInTheLiveContext() {
		$rootNode = $this->context->getRootNode();
		$fooNode = $rootNode->createNode('foo');

		$this->assertSame($fooNode, $rootNode->getNode('foo'));

		$this->persistenceManager->persistAll();

		$this->assertSame($fooNode, $rootNode->getNode('foo'));
	}

	/**
	 * @test
	 */
	public function createdNodesHaveDefaultValuesSet() {
		$rootNode = $this->context->getRootNode();

		$nodeTypeManager = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager');
		$testNodeType = $nodeTypeManager->getNodeType('TYPO3.TYPO3CR:TestingNodeType');
		$fooNode = $rootNode->createNode('foo', $testNodeType);

		$this->assertSame('default value 1', $fooNode->getProperty('test1'));
	}

	/**
	 * @test
	 */
	public function postprocessorUpdatesNodeTypesProperty() {
		$rootNode = $this->context->getRootNode();

		$nodeTypeManager = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager');
		$testNodeType = $nodeTypeManager->getNodeType('TYPO3.TYPO3CR:TestingNodeTypeWithProcessor');
		$fooNode = $rootNode->createNode('foo', $testNodeType);

		$this->assertSame('The value of "someOption" is "someOverriddenValue", the value of "someOtherOption" is "someOtherValue"', $fooNode->getProperty('test1'));
	}

	/**
	 * @test
	 */
	public function createdNodesHaveSubNodesCreatedIfDefinedInNodeType() {
		$rootNode = $this->context->getRootNode();

		$nodeTypeManager = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager');
		$testNodeType = $nodeTypeManager->getNodeType('TYPO3.TYPO3CR:TestingNodeTypeWithSubnodes');
		$fooNode = $rootNode->createNode('foo', $testNodeType);
		$firstSubnode = $fooNode->getNode('subnode1');
		$this->assertInstanceOf('TYPO3\TYPO3CR\Domain\Model\Node', $firstSubnode);
		$this->assertSame('default value 1', $firstSubnode->getProperty('test1'));
	}

	/**
	 * @test
	 */
	public function removedNodesCannotBeRetrievedAnymore() {
		$rootNode = $this->context->getRootNode();

		$rootNode->createNode('quux');
		$rootNode->getNode('quux')->remove();
		$this->assertNull($rootNode->getNode('quux'));

		$barNode = $rootNode->createNode('bar');
		$barNode->remove();
		$this->persistenceManager->persistAll();
		$this->assertNull($rootNode->getNode('bar'));

		$rootNode->createNode('baz');
		$this->persistenceManager->persistAll();
		$rootNode->getNode('baz')->remove();
		$bazNode = $rootNode->getNode('baz');
			// workaround for PHPUnit trying to "render" the result *if* not NULL
		$bazNodeResult = $bazNode === NULL ? NULL : 'instance-of-' . get_class($bazNode);
		$this->assertNull($bazNodeResult);
	}

	/**
	 * @test
	 */
	public function removedNodesAreNotCountedAsChildNodes() {
		$rootNode = $this->context->getRootNode();
		$rootNode->createNode('foo');
		$rootNode->getNode('foo')->remove();

		$this->assertFalse($rootNode->hasChildNodes(), 'First check.');

		$rootNode->createNode('bar');
		$this->persistenceManager->persistAll();

		$this->assertTrue($rootNode->hasChildNodes(), 'Second check.');

		$context = $this->contextFactory->create(array('workspaceName' => 'user-admin'));
		$rootNode = $context->getRootNode();

		$rootNode->getNode('bar')->remove();
		$this->persistenceManager->persistAll();

		$this->assertFalse($rootNode->hasChildNodes(), 'Third check.');
	}

	/**
	 * @test
	 */
	public function creatingAChildNodeAndRetrievingItAfterPersistAllWorks() {
		$rootNode = $this->context->getRootNode();

		$firstLevelNode = $rootNode->createNode('firstlevel');
		$secondLevelNode = $firstLevelNode->createNode('secondlevel');
		$secondLevelNode->createNode('thirdlevel');

		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();
		$retrievedNode = $rootNode->getNode('/firstlevel/secondlevel/thirdlevel');

		$this->assertEquals('/firstlevel/secondlevel/thirdlevel', $retrievedNode->getPath());
		$this->assertEquals('thirdlevel', $retrievedNode->getName());
		$this->assertEquals(3, $retrievedNode->getDepth());
	}

	/**
	 * @test
	 */
	public function threeCreatedNodesCanBeRetrievedInSameOrder() {
		$rootNode = $this->context->getRootNode();

		$parentNode = $rootNode->createNode('parent');
		$node1 = $parentNode->createNode('node1');
		$node2 = $parentNode->createNode('node2');
		$node3 = $parentNode->createNode('node3');

		$this->assertTrue($parentNode->hasChildNodes());
		$childNodes = $parentNode->getChildNodes();
		$this->assertSameOrder(array($node1, $node2, $node3), $childNodes);

		$this->persistenceManager->persistAll();

		$this->assertTrue($parentNode->hasChildNodes());
		$childNodes = $parentNode->getChildNodes();
		$this->assertSameOrder(array($node1, $node2, $node3), $childNodes);
	}

	/**
	 * @test
	 */
	public function threeChildNodesOfTheRootNodeCanBeRetrievedInSameOrder() {
		$rootNode = $this->context->getRootNode();

		$node1 = $rootNode->createNode('node1');
		$node2 = $rootNode->createNode('node2');
		$node3 = $rootNode->createNode('node3');

		$this->assertTrue($rootNode->hasChildNodes(), 'child node check before persistAll()');
		$childNodes = $rootNode->getChildNodes();
		$this->assertSameOrder(array($node1, $node2, $node3), $childNodes);

		$this->persistenceManager->persistAll();

		$this->assertTrue($rootNode->hasChildNodes(), 'child node check after persistAll()');
		$childNodes = $rootNode->getChildNodes();
		$this->assertSameOrder(array($node1, $node2, $node3), $childNodes);
	}

	/**
	 * @test
	 */
	public function getChildNodesSupportsSettingALimitAndOffset() {
		$rootNode = $this->context->getRootNode();

		$node1 = $rootNode->createNode('node1');
		$node2 = $rootNode->createNode('node2');
		$node3 = $rootNode->createNode('node3');
		$node4 = $rootNode->createNode('node4');
		$node5 = $rootNode->createNode('node5');
		$node6 = $rootNode->createNode('node6');

		$childNodes = $rootNode->getChildNodes();
		$this->assertSameOrder(array($node1, $node2, $node3, $node4, $node5, $node6), $childNodes);

		$this->persistenceManager->persistAll();

		$childNodes = $rootNode->getChildNodes(NULL, 3, 2);
		$this->assertSameOrder(array($node3, $node4, $node5), $childNodes);
	}

	/**
	 * @test
	 */
	public function moveBeforeMovesNodesBeforeOthersWithoutPersistAll() {
		$rootNode = $this->context->getRootNode();

		$parentNode = $rootNode->createNode('parentNode');
		$childNodeA = $parentNode->createNode('childNodeA');
		$childNodeB = $parentNode->createNode('childNodeB');
		$childNodeB->setProperty('name' , __METHOD__);
		$childNodeD = $parentNode->createNode('childNodeD');
		$childNodeE = $parentNode->createNode('childNodeE');
		$childNodeF = $parentNode->createNode('childNodeF');
		$childNodeC = $parentNode->createNode('childNodeC');
		$childNodeG = $parentNode->createNode('childNodeG');

		$childNodeC->moveBefore($childNodeD);

		$expectedChildNodes = array($childNodeA, $childNodeB, $childNodeC, $childNodeD, $childNodeE, $childNodeF, $childNodeG);
		$actualChildNodes = $parentNode->getChildNodes();
		$this->assertSameOrder($expectedChildNodes, array_values($actualChildNodes));
	}

	/**
	 * @test
	 */
	public function moveIntoMovesNodesIntoOthersOnDifferentLevelWithoutPersistAll() {
		$rootNode = $this->context->getRootNode();

		$parentNode = $rootNode->createNode('parentNode');
		$childNodeA = $parentNode->createNode('childNodeA');
		$childNodeB = $parentNode->createNode('childNodeB');
		$childNodeB1 = $childNodeB->createNode('childNodeB1');

		$childNodeB->moveInto($childNodeA);

		$this->assertNull($parentNode->getNode('childNodeB'));
		$this->assertSame($childNodeB, $childNodeA->getNode('childNodeB'));
		$this->assertSame($childNodeB1, $childNodeA->getNode('childNodeB')->getNode('childNodeB1'));
	}

	/**
	 * @test
	 */
	public function moveBeforeMovesNodesBeforeOthersOnDifferentLevelWithoutPersistAll() {
		$rootNode = $this->context->getRootNode();

		$parentNode = $rootNode->createNode('parentNode');
		$parentNode->createNode('childNodeA');
		$childNodeB = $parentNode->createNode('childNodeB');
		$childNodeB1 = $childNodeB->createNode('childNodeB1');
		$childNodeC = $parentNode->createNode('childNodeC');
		$childNodeC1 = $childNodeC->createNode('childNodeC1');

		$childNodeB->moveBefore($childNodeC1);

		$this->assertNull($parentNode->getNode('childNodeB'));
		$this->assertSame($childNodeB, $childNodeC->getNode('childNodeB'));
		$this->assertSame($childNodeB1, $childNodeC->getNode('childNodeB')->getNode('childNodeB1'));

		$expectedChildNodes = array($childNodeB, $childNodeC1);
		$actualChildNodes = $childNodeC->getChildNodes();
		$this->assertSameOrder($expectedChildNodes, array_values($actualChildNodes));
	}

	/**
	 * @test
	 */
	public function moveAfterMovesNodesAfterOthersOnDifferentLevelWithoutPersistAll() {
		$rootNode = $this->context->getRootNode();

		$parentNode = $rootNode->createNode('parentNode');
		$parentNode->createNode('childNodeA');
		$childNodeB = $parentNode->createNode('childNodeB');
		$childNodeB1 = $childNodeB->createNode('childNodeB1');
		$childNodeC = $parentNode->createNode('childNodeC');
		$childNodeC1 = $childNodeC->createNode('childNodeC1');

		$childNodeB->moveAfter($childNodeC1);

		$this->assertNull($parentNode->getNode('childNodeB'));
		$this->assertSame($childNodeB, $childNodeC->getNode('childNodeB'));
		$this->assertSame($childNodeB1, $childNodeC->getNode('childNodeB')->getNode('childNodeB1'));

		$expectedChildNodes = array($childNodeC1, $childNodeB);
		$actualChildNodes = $childNodeC->getChildNodes();
		$this->assertSameOrder($expectedChildNodes, array_values($actualChildNodes));
	}

	/**
	 * @test
	 */
	public function moveBeforeNodesWithLowerIndexMovesNodesBeforeOthersWithPersistAll() {
		$rootNode = $this->context->getRootNode();

		$parentNode = $rootNode->createNode('parentNode');
		$childNodeA = $parentNode->createNode('childNodeA');
		$childNodeB = $parentNode->createNode('childNodeB');
		$childNodeB->setProperty('name' , __METHOD__);
		$childNodeD = $parentNode->createNode('childNodeD');
		$childNodeE = $parentNode->createNode('childNodeE');
		$childNodeF = $parentNode->createNode('childNodeF');
		$childNodeC = $parentNode->createNode('childNodeC');
		$childNodeG = $parentNode->createNode('childNodeG');

		$this->persistenceManager->persistAll();

		$childNodeC->moveBefore($childNodeD);

		$this->persistenceManager->persistAll();

		$expectedChildNodes = array($childNodeA, $childNodeB, $childNodeC, $childNodeD, $childNodeE, $childNodeF, $childNodeG);
		$actualChildNodes = $parentNode->getChildNodes();

		$this->assertSameOrder($expectedChildNodes, $actualChildNodes);
	}

	/**
	 * @test
	 */
	public function moveBeforeNodesWithHigherIndexMovesNodesBeforeOthersWithPersistAll() {
		$rootNode = $this->context->getRootNode();

		$parentNode = $rootNode->createNode('parentNode');
		$childNodeA = $parentNode->createNode('childNodeA');
		$childNodeB = $parentNode->createNode('childNodeB');
		$childNodeB->setProperty('name' , __METHOD__);
		$childNodeF = $parentNode->createNode('childNodeF');
		$childNodeC = $parentNode->createNode('childNodeC');
		$childNodeD = $parentNode->createNode('childNodeD');
		$childNodeE = $parentNode->createNode('childNodeE');
		$childNodeG = $parentNode->createNode('childNodeG');

		$this->persistenceManager->persistAll();

		$childNodeF->moveBefore($childNodeG);

		$this->persistenceManager->persistAll();

		$expectedChildNodes = array($childNodeA, $childNodeB, $childNodeC, $childNodeD, $childNodeE, $childNodeF, $childNodeG);
		$actualChildNodes = $parentNode->getChildNodes();

		$this->assertSameOrder($expectedChildNodes, $actualChildNodes);
	}

	/**
	 * @test
	 */
	public function moveBeforeNodesWithHigherIndexMovesNodesBeforeOthersWithoutPersistAll() {
		$rootNode = $this->context->getRootNode();

		$parentNode = $rootNode->createNode('parentNode');
		$childNodeA = $parentNode->createNode('childNodeA');
		$childNodeB = $parentNode->createNode('childNodeB');
		$childNodeB->setProperty('name' , __METHOD__);
		$childNodeF = $parentNode->createNode('childNodeF');
		$childNodeC = $parentNode->createNode('childNodeC');
		$childNodeD = $parentNode->createNode('childNodeD');
		$childNodeE = $parentNode->createNode('childNodeE');
		$childNodeG = $parentNode->createNode('childNodeG');

		$childNodeF->moveBefore($childNodeG);

		$expectedChildNodes = array($childNodeA, $childNodeB, $childNodeC, $childNodeD, $childNodeE, $childNodeF, $childNodeG);
		$actualChildNodes = $parentNode->getChildNodes();

		$this->assertSameOrder($expectedChildNodes, $actualChildNodes);
	}

	/**
	 * @test
	 */
	public function moveAfterNodesWithLowerIndexMovesNodesAfterOthersWithoutPersistAll() {
		$rootNode = $this->context->getRootNode();

		$parentNode = $rootNode->createNode('parentNode');
		$childNodeA = $parentNode->createNode('childNodeA');
		$childNodeB = $parentNode->createNode('childNodeB');
		$childNodeB->setProperty('name' , __METHOD__);
		$childNodeD = $parentNode->createNode('childNodeD');
		$childNodeE = $parentNode->createNode('childNodeE');
		$childNodeF = $parentNode->createNode('childNodeF');
		$childNodeC = $parentNode->createNode('childNodeC');
		$childNodeG = $parentNode->createNode('childNodeG');

		$childNodeC->moveAfter($childNodeB);

		$expectedChildNodes = array($childNodeA, $childNodeB, $childNodeC, $childNodeD, $childNodeE, $childNodeF, $childNodeG);
		$actualChildNodes = $parentNode->getChildNodes();

		$this->assertSameOrder($expectedChildNodes, $actualChildNodes);
	}

	/**
	 * @test
	 */
	public function moveIntoMovesNodesIntoOthersOnDifferentLevelWithPersistAll() {
		$rootNode = $this->context->getRootNode();

		$parentNode = $rootNode->createNode('parentNode');
		$childNodeA = $parentNode->createNode('childNodeA');
		$childNodeB = $parentNode->createNode('childNodeB');
		$childNodeB1 = $childNodeB->createNode('childNodeB1');

		$this->persistenceManager->persistAll();

		$childNodeB->moveInto($childNodeA);

		$this->persistenceManager->persistAll();

		$this->assertNull($parentNode->getNode('childNodeB'));
		$this->assertSame($childNodeB, $childNodeA->getNode('childNodeB'));
		$this->assertSame($childNodeB1, $childNodeA->getNode('childNodeB')->getNode('childNodeB1'));
	}

	/**
	 * @test
	 */
	public function moveBeforeMovesNodesBeforeOthersOnDifferentLevelWithPersistAll() {
		$rootNode = $this->context->getRootNode();

		$parentNode = $rootNode->createNode('parentNode');
		$parentNode->createNode('childNodeA');
		$childNodeB = $parentNode->createNode('childNodeB');
		$childNodeB1 = $childNodeB->createNode('childNodeB1');
		$childNodeC = $parentNode->createNode('childNodeC');
		$childNodeC1 = $childNodeC->createNode('childNodeC1');

		$this->persistenceManager->persistAll();

		$childNodeB->moveBefore($childNodeC1);

		$this->persistenceManager->persistAll();

		$this->assertNull($parentNode->getNode('childNodeB'));
		$this->assertSame($childNodeB, $childNodeC->getNode('childNodeB'));
		$this->assertSame($childNodeB1, $childNodeC->getNode('childNodeB')->getNode('childNodeB1'));

		$expectedChildNodes = array($childNodeB, $childNodeC1);
		$actualChildNodes = $childNodeC->getChildNodes();
		$this->assertSameOrder($expectedChildNodes, array_values($actualChildNodes));
	}

	/**
	 * @test
	 */
	public function moveAfterMovesNodesAfterOthersOnDifferentLevelWithPersistAll() {
		$rootNode = $this->context->getRootNode();

		$parentNode = $rootNode->createNode('parentNode');
		$parentNode->createNode('childNodeA');
		$childNodeB = $parentNode->createNode('childNodeB');
		$childNodeB1 = $childNodeB->createNode('childNodeB1');
		$childNodeC = $parentNode->createNode('childNodeC');
		$childNodeC1 = $childNodeC->createNode('childNodeC1');

		$this->persistenceManager->persistAll();

		$childNodeB->moveAfter($childNodeC1);

		$this->persistenceManager->persistAll();

		$this->assertNull($parentNode->getNode('childNodeB'));
		$this->assertSame($childNodeB, $childNodeC->getNode('childNodeB'));
		$this->assertSame($childNodeB1, $childNodeC->getNode('childNodeB')->getNode('childNodeB1'));

		$expectedChildNodes = array($childNodeC1, $childNodeB);
		$actualChildNodes = $childNodeC->getChildNodes();
		$this->assertSameOrder($expectedChildNodes, array_values($actualChildNodes));
	}

	/**
	 * @test
	 */
	public function moveAfterNodesWithLowerIndexMovesNodesAfterOthersWithPersistAll() {
		$rootNode = $this->context->getRootNode();

		$parentNode = $rootNode->createNode('parentNode');
		$childNodeA = $parentNode->createNode('childNodeA');
		$childNodeB = $parentNode->createNode('childNodeB');
		$childNodeB->setProperty('name' , __METHOD__);
		$childNodeD = $parentNode->createNode('childNodeD');
		$childNodeE = $parentNode->createNode('childNodeE');
		$childNodeF = $parentNode->createNode('childNodeF');
		$childNodeC = $parentNode->createNode('childNodeC');
		$childNodeG = $parentNode->createNode('childNodeG');

		$this->persistenceManager->persistAll();

		$childNodeC->moveAfter($childNodeB);

		$this->persistenceManager->persistAll();

		$expectedChildNodes = array($childNodeA, $childNodeB, $childNodeC, $childNodeD, $childNodeE, $childNodeF, $childNodeG);
		$actualChildNodes = $parentNode->getChildNodes();

		$this->assertSameOrder($expectedChildNodes, $actualChildNodes);
	}

	/**
	 * @test
	 */
	public function moveAfterNodesWithHigherIndexMovesNodesAfterOthersWithPersistAll() {
		$rootNode = $this->context->getRootNode();

		$parentNode = $rootNode->createNode('parentNode');
		$childNodeA = $parentNode->createNode('childNodeA');
		$childNodeB = $parentNode->createNode('childNodeB');
		$childNodeB->setProperty('name' , __METHOD__);
		$childNodeF = $parentNode->createNode('childNodeF');
		$childNodeC = $parentNode->createNode('childNodeC');
		$childNodeD = $parentNode->createNode('childNodeD');
		$childNodeE = $parentNode->createNode('childNodeE');
		$childNodeG = $parentNode->createNode('childNodeG');

		$this->persistenceManager->persistAll();

		$childNodeF->moveAfter($childNodeE);

		$this->persistenceManager->persistAll();

		$expectedChildNodes = array($childNodeA, $childNodeB, $childNodeC, $childNodeD, $childNodeE, $childNodeF, $childNodeG);
		$actualChildNodes = $parentNode->getChildNodes();

		$this->assertSameOrder($expectedChildNodes, $actualChildNodes);
	}

	/**
	 * @test
	 */
	public function moveAfterNodesWithHigherIndexMovesNodesAfterOthersWithoutPersistAll() {
		$rootNode = $this->context->getNode('/');

		$parentNode = $rootNode->createNode('parentNode');
		$childNodeA = $parentNode->createNode('childNodeA');
		$childNodeB = $parentNode->createNode('childNodeB');
		$childNodeF = $parentNode->createNode('childNodeF');
		$childNodeC = $parentNode->createNode('childNodeC');
		$childNodeD = $parentNode->createNode('childNodeD');
		$childNodeE = $parentNode->createNode('childNodeE');
		$childNodeG = $parentNode->createNode('childNodeG');

		$childNodeF->moveAfter($childNodeE);

		$expectedChildNodes = array($childNodeA, $childNodeB, $childNodeC, $childNodeD, $childNodeE, $childNodeF, $childNodeG);
		$actualChildNodes = $parentNode->getChildNodes();

		$this->assertSameOrder($expectedChildNodes, $actualChildNodes);
	}

	/**
	 * @test
	 */
	public function moveBeforeInASeparateWorkspaceLeadsToCorrectSortingAcrossWorkspaces() {
		$rootNode = $this->context->getNode('/');

		$liveParentNode = $rootNode->createNode('parentNode');
		$childNodeA = $liveParentNode->createNode('childNodeA');
		$childNodeC = $liveParentNode->createNode('childNodeC');
		$childNodeD = $liveParentNode->createNode('childNodeD');
		$childNodeE = $liveParentNode->createNode('childNodeE');
		$childNodeG = $liveParentNode->createNode('childNodeG');

		$this->persistenceManager->persistAll();

		$userContext = $this->contextFactory->create(array('workspaceName' => 'live2'));
		$userParentNode = $userContext->getNode('/parentNode');

		$childNodeB = $userParentNode->createNode('childNodeB');
		$childNodeB->moveBefore($childNodeC);

		$childNodeF = $userParentNode->createNode('childNodeF');
		$childNodeF->moveBefore($childNodeG);

		$this->persistenceManager->persistAll();

		$expectedChildNodes = array($childNodeA, $childNodeB, $childNodeC, $childNodeD, $childNodeE, $childNodeF, $childNodeG);
		$actualChildNodes = $userParentNode->getChildNodes();

		$this->assertSameOrder($expectedChildNodes, $actualChildNodes);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\TYPO3CR\Exception\NodeExistsException
	 */
	public function moveBeforeThrowsExceptionIfTargetExists() {
		$rootNode = $this->context->getNode('/');

		$alfaNode = $rootNode->createNode('alfa');
		$alfaChildNode = $alfaNode->createNode('alfa');

		$alfaChildNode->moveBefore($alfaNode);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\TYPO3CR\Exception\NodeExistsException
	 */
	public function moveAfterThrowsExceptionIfTargetExists() {
		$rootNode = $this->context->getNode('/');

		$alfaNode = $rootNode->createNode('alfa');
		$alfaChildNode = $alfaNode->createNode('alfa');

		$alfaChildNode->moveAfter($alfaNode);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\TYPO3CR\Exception\NodeExistsException
	 */
	public function moveIntoThrowsExceptionIfTargetExists() {
		$rootNode = $this->context->getNode('/');

		$alfaNode = $rootNode->createNode('alfa');
		$alfaChildNode = $alfaNode->createNode('alfa');

		$alfaChildNode->moveInto($rootNode);
	}

	/**
	 * Testcase for bug #34291 (TYPO3CR reordering does not take unpersisted
	 * node order changes into account)
	 *
	 * The error can be reproduced in the following way:
	 *
	 * - First, create some nodes, and persist.
	 * - Then, move a node after another one, filling the LAST free sorting index between the nodes. Do NOT persist after that.
	 * - After that, try to *again* move a node to this spot. In this case, we need to *renumber*
	 *   the node indices, and the system needs to take the before-moved node into account as well.
	 *
	 * The bug tested by this testcase led to wrong orderings on the floworg website in
	 * the documentation part under some circumstances.
	 *
	 * @test
	 */
	public function renumberingTakesUnpersistedNodeOrderChangesIntoAccount() {
		$rootNode = $this->context->getRootNode();

		$liveParentNode = $rootNode->createNode('parentNode');
		$nodes = array();
		$nodes[1] = $liveParentNode->createNode('node001');
		$nodes[1]->setIndex(1);
		$nodes[2] = $liveParentNode->createNode('node002');
		$nodes[2]->setIndex(2);
		$nodes[3] = $liveParentNode->createNode('node003');
		$nodes[3]->setIndex(4);
		$nodes[4] = $liveParentNode->createNode('node004');
		$nodes[4]->setIndex(5);

		$this->nodeDataRepository->persistEntities();

		$nodes[1]->moveAfter($nodes[2]);
		$nodes[3]->moveAfter($nodes[2]);

		$this->nodeDataRepository->persistEntities();

		$actualChildNodes = $liveParentNode->getChildNodes();

		$newNodeOrder = array(
			$nodes[2],
			$nodes[3],
			$nodes[1],
			$nodes[4]
		);
		$this->assertSameOrder($newNodeOrder, $actualChildNodes);
	}

	/**
	 * @test
	 */
	public function nodeDataRepositoryRenumbersNodesIfNoFreeSortingIndexesAreAvailable() {
		$rootNode = $this->context->getRootNode();

		$liveParentNode = $rootNode->createNode('parentNode');
		$nodes = array();
		$nodes[0] = $liveParentNode->createNode('node000');
		$nodes[150] = $liveParentNode->createNode('node150');

		$this->persistenceManager->persistAll();

		for ($i = 1; $i < 150; $i++) {
			$nodes[$i] = $liveParentNode->createNode('node' . sprintf('%1$03d', $i));
			$nodes[$i]->moveAfter($nodes[$i - 1]);
		}
		$this->persistenceManager->persistAll();

		ksort($nodes);
		$actualChildNodes = $liveParentNode->getChildNodes();
		$this->assertSameOrder($nodes, $actualChildNodes);
	}

	/**
	 * Asserts that the order of the given nodes is the same.
	 * This doesn't check if the node objects are the same or equal but rather tests
	 * if their path is identical. Therefore nodes can be in different workspaces
	 * or nodes.
	 *
	 * @param array $expectedNodes The expected order
	 * @param array $actualNodes The actual order
	 * @return void
	 */
	protected function assertSameOrder(array $expectedNodes, array $actualNodes) {
		if (count($expectedNodes) !== count($actualNodes)) {
			$this->fail(sprintf('Number of nodes did not match: got %s expected and %s actual nodes.', count($expectedNodes), count($actualNodes)));
		}

		reset($expectedNodes);
		foreach ($actualNodes as $actualNode) {
			$expectedNode = current($expectedNodes);
			if ($expectedNode->getPath() !== $actualNode->getPath()) {
				$this->fail(sprintf('Expected node %s (index %s), actual node %s (index %s)', $expectedNode->getPath(), $expectedNode->getIndex(), $actualNode->getPath(), $actualNode->getIndex()));
			}
			next($expectedNodes);
		}
		$this->assertTrue(TRUE);
	}

	/**
	 * @test
	 */
	public function getLabelCropsTheLabelIfNecessary() {
		$workspace = new \TYPO3\TYPO3CR\Domain\Model\Workspace('live');
		$nodeData = new \TYPO3\TYPO3CR\Domain\Model\NodeData('/bar', $workspace);
		$this->inject($nodeData, 'nodeDataRepository', $this->getMock('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository'));
		$this->assertEquals('unstructured (bar)', $nodeData->getLabel());

		$nodeData->setProperty('title', 'The point of this title is, that it`s a bit long and needs to be cropped.');
		$this->assertEquals('The point of this title is, th …', $nodeData->getLabel());

		$nodeData->setProperty('title', 'A better title');
		$this->assertEquals('A better title', $nodeData->getLabel());
	}

	/**
	 * @test
	 */
	public function nodesCanBeCopiedAfterAndBeforeAndKeepProperties() {
		$rootNode = $this->context->getNode('/');

		$bazNode = $rootNode->createNode('baz');
		$fluxNode = $rootNode->createNode('flux');
		$fluxNode->setProperty('someProperty', 42);

		$bachNode = $fluxNode->copyBefore($bazNode, 'bach');
		$flussNode = $fluxNode->copyAfter($bazNode, 'fluss');
		$this->assertNotSame($fluxNode, $flussNode);
		$this->assertNotSame($fluxNode, $bachNode);
		$this->assertEquals($fluxNode->getProperties(), $bachNode->getProperties());
		$this->assertEquals($fluxNode->getProperties(), $flussNode->getProperties());
		$this->persistenceManager->persistAll();

		$this->assertSame($bachNode, $rootNode->getNode('bach'));
		$this->assertSame($flussNode, $rootNode->getNode('fluss'));
	}

	/**
	 * @test
	 */
	public function nodesCanBeCopiedBefore() {
		$rootNode = $this->context->getNode('/');

		$bazNode = $rootNode->createNode('baz');
		$fluxNode = $rootNode->createNode('flux');

		$fluxNode->copyBefore($bazNode, 'fluss');

		$childNodes = $rootNode->getChildNodes();
		$names = new \stdClass();
		$names->names = array();
		array_walk($childNodes, function ($value, $key, &$names) {$names->names[] = $value->getName();}, $names);
		$this->assertSame(array('fluss', 'baz', 'flux'), $names->names);
	}

	/**
	 * @test
	 */
	public function nodesCanBeCopiedAfter() {
		$rootNode = $this->context->getNode('/');

		$bazNode = $rootNode->createNode('baz');
		$fluxNode = $rootNode->createNode('flux');

		$fluxNode->copyAfter($bazNode, 'fluss');

		$childNodes = $rootNode->getChildNodes();
		$names = new \stdClass();
		$names->names = array();
		array_walk($childNodes, function ($value, $key, &$names) {$names->names[] = $value->getName();}, $names);
		$this->assertSame(array('baz', 'fluss', 'flux'), $names->names);
	}

	/**
	 * @test
	 */
	public function nodesCanBeCopiedInto() {
		$rootNode = $this->context->getNode('/');

		$alfaNode = $rootNode->createNode('alfa');
		$bravoNode = $rootNode->createNode('bravo');
		$bravoNode->setProperty('test', TRUE);

		$bravoNode->copyInto($alfaNode, 'charlie');

		$this->assertSame($alfaNode->getNode('charlie')->getProperty('test'), TRUE);
	}

	/**
	 * @test
	 */
	public function nodesCanBeCopiedIntoThemselves() {
		$rootNode = $this->context->getNode('/');

		$alfaNode = $rootNode->createNode('alfa');
		$alfaNode->setProperty('test', TRUE);

		$bravoNode = $alfaNode->copyInto($alfaNode, 'bravo');

		$this->assertSame($bravoNode->getProperty('test'), TRUE);
		$this->assertSame($alfaNode->getNode('bravo'), $bravoNode);
	}

	/**
	 * @test
	 */
	public function nodesAreCopiedBeforeRecursively() {
		$rootNode = $this->context->getNode('/');

		$bazNode = $rootNode->createNode('baz');
		$fluxNode = $rootNode->createNode('flux');
		$fluxNode->createNode('capacitor');
		$fluxNode->createNode('second');
		$fluxNode->createNode('third');

		$copiedChildNodes = $fluxNode->copyBefore($bazNode, 'fluss')->getChildNodes();

		$names = new \stdClass();
		$names->names = array();
		array_walk($copiedChildNodes, function ($value, $key, &$names) {$names->names[] = $value->getName();}, $names);
		$this->assertSame(array('capacitor', 'second', 'third'), $names->names);
	}

	/**
	 * @test
	 */
	public function nodesAreCopiedAfterRecursively() {
		$rootNode = $this->context->getNode('/');

		$bazNode = $rootNode->createNode('baz');
		$fluxNode = $rootNode->createNode('flux');
		$fluxNode->createNode('capacitor');
		$fluxNode->createNode('second');
		$fluxNode->createNode('third');

		$copiedChildNodes = $fluxNode->copyAfter($bazNode, 'fluss')->getChildNodes();

		$names = new \stdClass();
		$names->names = array();
		array_walk($copiedChildNodes, function ($value, $key, &$names) {$names->names[] = $value->getName();}, $names);
		$this->assertSame(array('capacitor', 'second', 'third'), $names->names);
	}

	/**
	 * @test
	 */
	public function nodesAreCopiedIntoRecursively() {
		$rootNode = $this->context->getNode('/');

		$alfaNode = $rootNode->createNode('alfa');
		$bravoNode = $rootNode->createNode('bravo');
		$bravoNode->setProperty('test', TRUE);
		$charlieNode = $bravoNode->createNode('charlie');
		$charlieNode->setProperty('test2', TRUE);

		$deltaNode = $bravoNode->copyInto($alfaNode, 'delta');

		$this->assertSame($alfaNode->getNode('delta'), $deltaNode);
		$this->assertSame($alfaNode->getNode('delta')->getProperty('test'), TRUE);
		$this->assertSame($alfaNode->getNode('delta')->getNode('charlie')->getProperty('test2'), TRUE);
	}

	/**
	 * @test
	 */
	public function nodesAreCopiedIntoThemselvesRecursively() {
		$rootNode = $this->context->getNode('/');

		$alfaNode = $rootNode->createNode('alfa');
		$bravoNode = $alfaNode->createNode('bravo');
		$bravoNode->setProperty('test', TRUE);

		$charlieNode = $alfaNode->copyInto($alfaNode, 'charlie');

		$this->assertSame($alfaNode->getNode('charlie'), $charlieNode);
		$this->assertSame($alfaNode->getNode('charlie')->getNode('bravo')->getProperty('test'), TRUE);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\TYPO3CR\Exception\NodeExistsException
	 */
	public function copyBeforeThrowsExceptionIfTargetExists() {
		$rootNode = $this->context->getNode('/');

		$rootNode->createNode('exists');
		$bazNode = $rootNode->createNode('baz');
		$fluxNode = $rootNode->createNode('flux');

		$fluxNode->copyBefore($bazNode, 'exists');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\TYPO3CR\Exception\NodeExistsException
	 */
	public function copyAfterThrowsExceptionIfTargetExists() {
		$rootNode = $this->context->getNode('/');

		$rootNode->createNode('exists');
		$bazNode = $rootNode->createNode('baz');
		$fluxNode = $rootNode->createNode('flux');

		$fluxNode->copyAfter($bazNode, 'exists');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\TYPO3CR\Exception\NodeExistsException
	 */
	public function copyIntoThrowsExceptionIfTargetExists() {
		$rootNode = $this->context->getNode('/');

		$rootNode->createNode('exists');
		$alfaNode = $rootNode->createNode('alfa');

		$alfaNode->copyInto($rootNode, 'exists');
	}

	/**
	 * @test
	 */
	public function setPropertyAcceptsAndConvertsIdentifierIfTargetTypeIsReference() {
		$nodeTypeManager = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager');
		$nodeType = $nodeTypeManager->getNodeType('TYPO3.TYPO3CR:TestingNodeTypeWithReferences');

		$rootNode = $this->context->getNode('/');
		$nodeA = $rootNode->createNode('nodeA', $nodeType, '30e893c1-caef-0ca5-b53d-e5699bb8e506');
		$nodeB = $rootNode->createNode('nodeB', $nodeType, '81c848ed-abb5-7608-a5db-7eea0331ccfa');

		$nodeA->setProperty('property2', '81c848ed-abb5-7608-a5db-7eea0331ccfa');
		$this->assertSame($nodeB, $nodeA->getProperty('property2'));

		$nodeA->setProperty('property2', $nodeB);
		$this->assertSame($nodeB, $nodeA->getProperty('property2'));
	}

	/**
	 * @test
	 */
	public function setPropertyAcceptsAndConvertsIdentifiersIfTargetTypeIsReferences() {
		$nodeTypeManager = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager');
		$nodeType = $nodeTypeManager->getNodeType('TYPO3.TYPO3CR:TestingNodeTypeWithReferences');

		$rootNode = $this->context->getNode('/');
		$nodeA = $rootNode->createNode('nodeA', $nodeType, '30e893c1-caef-0ca5-b53d-e5699bb8e506');
		$nodeB = $rootNode->createNode('nodeB', $nodeType, '81c848ed-abb5-7608-a5db-7eea0331ccfa');
		$nodeC = $rootNode->createNode('nodeC', $nodeType, 'e3b99700-f632-4a4c-2f93-0ad07eaf733f');

		$expectedNodes = array($nodeB, $nodeC);

		$nodeA->setProperty('property3', array('81c848ed-abb5-7608-a5db-7eea0331ccfa', 'e3b99700-f632-4a4c-2f93-0ad07eaf733f'));
		$this->assertSame($expectedNodes, $nodeA->getProperty('property3'));

		$nodeA->setProperty('property3', $expectedNodes);
		$this->assertSame($expectedNodes, $nodeA->getProperty('property3'));
	}

	/**
	 * @test
	 */
	public function getPropertiesReturnsReferencePropertiesAsNodeObjects() {
		$nodeTypeManager = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager');
		$nodeType = $nodeTypeManager->getNodeType('TYPO3.TYPO3CR:TestingNodeTypeWithReferences');

		$rootNode = $this->context->getNode('/');
		$nodeA = $rootNode->createNode('nodeA', $nodeType, '30e893c1-caef-0ca5-b53d-e5699bb8e506');
		$nodeB = $rootNode->createNode('nodeB', $nodeType, '81c848ed-abb5-7608-a5db-7eea0331ccfa');
		$nodeC = $rootNode->createNode('nodeC', $nodeType, 'e3b99700-f632-4a4c-2f93-0ad07eaf733f');

		$expectedNodes = array($nodeB, $nodeC);

		$nodeA->setProperty('property2', '81c848ed-abb5-7608-a5db-7eea0331ccfa');
		$nodeA->setProperty('property3', array('81c848ed-abb5-7608-a5db-7eea0331ccfa', 'e3b99700-f632-4a4c-2f93-0ad07eaf733f'));

		$actualProperties = $nodeA->getProperties();
		$this->assertSame($nodeB, $actualProperties['property2']);
		$this->assertSame($expectedNodes, $actualProperties['property3']);
	}

	/**
	 * @test
	 */
	public function getPropertyDoesNotReturnNodeReferencesIfTheyAreNotVisibleAccordingToTheContentContext() {
		$nodeTypeManager = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager');
		$nodeType = $nodeTypeManager->getNodeType('TYPO3.TYPO3CR:TestingNodeTypeWithReferences');

		$this->context = $this->contextFactory->create(array('workspaceName' => 'live', 'invisibleContentShown' => FALSE));

		$rootNode = $this->context->getNode('/');
		$nodeA = $rootNode->createNode('nodeA', $nodeType, '30e893c1-caef-0ca5-b53d-e5699bb8e506');
		$nodeB = $rootNode->createNode('nodeB', $nodeType, '81c848ed-abb5-7608-a5db-7eea0331ccfa');
		$nodeC = $rootNode->createNode('nodeC', $nodeType, 'e3b99700-f632-4a4c-2f93-0ad07eaf733f');

		$nodeA->setProperty('property2', '81c848ed-abb5-7608-a5db-7eea0331ccfa');
		$nodeA->setProperty('property3', array('81c848ed-abb5-7608-a5db-7eea0331ccfa', 'e3b99700-f632-4a4c-2f93-0ad07eaf733f'));

		$nodeB->setHidden(TRUE);

		$this->assertNull($nodeA->getProperty('property2'));
		$this->assertSame(array($nodeC), $nodeA->getProperty('property3'));
	}

	/**
	 * @test
	 */
	public function getPropertyReturnsReferencedNodesInCorrectWorkspace() {
		$nodeTypeManager = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\NodeTypeManager');
		$nodeType = $nodeTypeManager->getNodeType('TYPO3.TYPO3CR:TestingNodeTypeWithReferences');

		$identifier = '81c848ed-abb5-7608-a5db-7eea0331ccfa';
		$rootNode = $this->context->getNode('/');
		$referencedNode = $rootNode->createNode('referencedNode', $nodeType, $identifier);
		$node = $rootNode->createNode('node', $nodeType, '30e893c1-caef-0ca5-b53d-e5699bb8e506');
		$node->setProperty('property2', $identifier);

		$testContext = $this->contextFactory->create(array('workspaceName' => 'test'));

		$testRootNode = $testContext->getNode('/');
		$testReferencedNode = $testRootNode->createNode('testReferencedNode', $nodeType, $identifier);
		$testNode = $testRootNode->getNode('node');

		$referencedNodeProperty = $node->getProperty('property2');
		$this->assertNotSame($referencedNodeProperty->getWorkspace(), $testReferencedNode->getWorkspace());
		$this->assertSame($referencedNodeProperty->getWorkspace(), $referencedNode->getWorkspace());

		$testReferencedNodeProperty = $testNode->getProperty('property2');
		$this->assertNotSame($testReferencedNodeProperty->getWorkspace(), $referencedNode->getWorkspace());
		$this->assertSame($testReferencedNodeProperty->getWorkspace(), $testReferencedNode->getWorkspace());
	}
}