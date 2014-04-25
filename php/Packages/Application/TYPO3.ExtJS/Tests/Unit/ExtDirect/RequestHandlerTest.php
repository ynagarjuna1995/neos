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
 * Testcase for the ExtDirect Request Handler
 *
 */
class RequestHandlerTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var array
	 */
	protected $getSuperglobalBackup;

	public function setUp() {
		$this->getSuperglobalBackup = $_GET;
	}

	public function tearDown() {
		$_GET = $this->getSuperglobalBackup;
	}

	/**
	 * @test
	 */
	public function canHandleRequestReturnsTrueIfTheSapiTypeIsWebAndAnExtDirectGetParameterIsSent() {
		$requestHandler = $this->getAccessibleMock('TYPO3\ExtJS\ExtDirect\RequestHandler', array('sendResponse'), array(), '', FALSE);
		$this->assertFalse($requestHandler->canHandleRequest());
		$_GET['TYPO3_ExtJS_ExtDirectRequest'] = '1';
		$this->assertTrue($requestHandler->canHandleRequest());
	}

}
