<?php
namespace TYPO3\ExtJS\Tests\Unit\ExtDirect;

/*                                                                        *
 * This script belongs to the Flow package "ExtJS".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the ExtDirect View
 *
 */
class ViewTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function assignErrorsConvertsErrorsToExtJSFormat() {
		$propertyError = new \TYPO3\Flow\Error\Error('Some error', 12345678);

		$errors = new \TYPO3\Flow\Error\Result();
		$errors->forProperty('title')->addError($propertyError);

		$expected = array(
			'errors' => array(
				'title' => 'Some error'
			),
			'success' => FALSE
		);
		$mockResponse = $this->getMock('TYPO3\ExtJS\ExtDirect\TransactionResponse');
		$mockResponse->expects($this->atLeastOnce())->method('setResult')->with($expected);

		$mockControllerContext = $this->getMock('TYPO3\Flow\Mvc\Controller\ControllerContext', array('getResponse'), array(), '', FALSE);
		$mockControllerContext->expects($this->any())->method('getResponse')->will($this->returnValue($mockResponse));

		$view = $this->getMock('TYPO3\ExtJS\ExtDirect\View', array('loadConfigurationFromYamlFile'));
		$view->setControllerContext($mockControllerContext);

		$view->expects($this->any())->method('loadConfigurationFromYamlFile')->will($this->returnValue(array()));

		$view->assignErrors($errors);

		$view->render();
	}
}
