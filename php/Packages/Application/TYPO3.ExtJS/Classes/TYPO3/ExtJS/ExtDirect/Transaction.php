<?php
namespace TYPO3\ExtJS\ExtDirect;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.ExtJS".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * An Ext Direct transaction
 *
 */
class Transaction {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * The direct request this transaction belongs to
	 *
	 * @var \TYPO3\ExtJS\ExtDirect\Request
	 */
	protected $request;

	/**
	 * The controller / class to use
	 *
	 * @var string
	 */
	protected $action;

	/**
	 * The action / method to execute
	 *
	 * @var string
	 */
	protected $method;

	/**
	 * The arguments to be passed to the method
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * The transaction ID to associate with this request
	 *
	 * @var integer
	 */
	protected $tid;

	/**
	 * Constructs the Transaction
	 *
	 * @param \TYPO3\ExtJS\ExtDirect\Request $request The direct request this transaction belongs to
	 * @param string $action The "action" – the "controller object name" in Flow terms
	 * @param string $method The "method" – the "action name" in Flow terms
	 * @param array $data Numeric array of arguments which are eventually passed to the Flow action method
	 * @param mixed $tid The ExtDirect transaction id
	 */
	public function __construct(\TYPO3\ExtJS\ExtDirect\Request $request, $action, $method, array $data, $tid) {
		$this->request = $request;
		$this->action = $action;
		$this->method = $method;
		$this->data = $data;
		$this->tid = $tid;
	}

	/**
	 * Build a web request for dispatching this Ext Direct transaction
	 *
	 * @param \TYPO3\ExtJS\ExtDirect\Request $extDirectRequest
	 * @return \TYPO3\Flow\Mvc\ActionRequest A web request for this transaction
	 */
	public function buildRequest(\TYPO3\ExtJS\ExtDirect\Request $extDirectRequest) {
		$request = new \TYPO3\Flow\Mvc\ActionRequest($extDirectRequest);
		$request->setControllerObjectName($this->getControllerObjectName());
		$request->setControllerActionName($this->getMethod());
		$request->setFormat('extdirect');
		$request->setArguments($this->getArguments());
		return $request;
	}

	/**
	 * Build a response for dispatching this Ext Direct transaction
	 *
	 * @return \TYPO3\ExtJS\ExtDirect\TransactionResponse A response for dispatching this transaction
	 */
	public function buildResponse() {
		return new \TYPO3\ExtJS\ExtDirect\TransactionResponse();
	}

	/**
	 * Getter for action
	 *
	 * @return string
	 */
	public function getAction() {
		return $this->action;
	}

	/**
	 * Getter for method
	 *
	 * @return string
	 */
	public function getMethod() {
		return $this->method;
	}

	/**
	 * Getter for data
	 *
	 * @return array
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Getter for type
	 *
	 * @return string The transaction type, currently always "rpc"
	 */
	public function getType() {
		return 'rpc';
	}

	/**
	 * Getter for tid
	 *
	 * @return integer
	 */
	public function getTid() {
		return $this->tid;
	}


	/**
	 * Getter for the controller object name
	 *
	 * @return string
	 */
	public function getControllerObjectName() {
		return str_replace('_', '\\', $this->action);
	}

	/**
	 * Ext Direct does not provide named arguments by now, so we have
	 * to map them by reflecting on the action parameters.
	 *
	 * @return array The mapped arguments
	 */
	public function getArguments() {
		if ($this->data === array()) {
			return array();
		}

		$arguments = array();

		if (!$this->request->isFormPost()) {
			$parameters = $this->reflectionService->getMethodParameters($this->getControllerObjectName(), $this->method . 'Action');

				// TODO Add checks for parameters
			foreach ($parameters as $name => $options) {
				$parameterIndex = $options['position'];
				if (isset($this->data[$parameterIndex])) {
					$arguments[$name] = $this->convertObjectToArray($this->data[$parameterIndex]);
				}
			}

		} else {
				// TODO Reuse setArgumentsFromRawRequestData from Web/RequestBuilder
		}

		return $arguments;
	}

	/**
	 * Convert an object to an array recursively
	 *
	 * @param \stdClass $object The object to convert
	 * @return array The object converted to an array
	 */
	protected function convertObjectToArray($object) {
		return json_decode(json_encode($object), TRUE);
	}
}
