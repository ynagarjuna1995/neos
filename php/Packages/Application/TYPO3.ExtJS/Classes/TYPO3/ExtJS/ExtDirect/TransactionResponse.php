<?php
namespace TYPO3\ExtJS\ExtDirect;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.ExtJS".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, version 3.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A Ext Direct specific response implementation with raw content for json encodable results
 *
 */
class TransactionResponse extends \TYPO3\Flow\Http\Response {

	/**
	 * The Ext Direct result that will be JSON encoded
	 *
	 * @var mixed
	 */
	protected $result;

	/**
	 * The Ext Direct success code. Defaults to TRUE.
	 *
	 * @var boolean
	 */
	protected $success = TRUE;

	/**
	 * Setter for the transaction result.
	 *
	 * @param mixed $result The result of the called action
	 * @return void
	 */
	public function setResult($result) {
		$this->result = $result;
	}

	/**
	 * Sette for success.
	 *
	 * @param boolean $success The success of the called action
	 * @return void
	 */
	public function setSuccess($success) {
		$this->success = $success;
	}


	/**
	 * Returns the result of the transaction.
	 *
	 * @return mixed The result
	 */
	public function getResult() {
		return $this->result;
	}

	/**
	 * Returns the state (success/fail) of the transaction.
	 *
	 * @return boolean The success
	 */
	public function getSuccess() {
		return $this->success;
	}
}
