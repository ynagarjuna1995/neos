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
 * An Ext Direct request
 *
 */
class Request extends \TYPO3\Flow\Mvc\ActionRequest {

	/**
	 * The transactions inside this request
	 *
	 * @var array
	 */
	protected $transactions = array();

	/**
	 * True if this request is a form post
	 *
	 * @var boolean
	 */
	protected $formPost = FALSE;

	/**
	 * True if this request is containing a file upload
	 *
	 * @var boolean
	 */
	protected $fileUpload = FALSE;

	/**
	 * Creates an Ext Direct Transaction and adds it to the request instance.
	 *
	 * @param string $action The "action" – the "controller object name" in Flow terms
	 * @param string $method The "method" – the "action name" in Flow terms
	 * @param array $data Numeric array of arguments which are eventually passed to the Flow action method
	 * @param mixed $tid The ExtDirect transaction id
	 * @return void
	 */
	public function createAndAddTransaction($action, $method, array $data, $tid) {
		$transaction = new \TYPO3\ExtJS\ExtDirect\Transaction($this, $action, $method, $data, $tid);
		$this->transactions[] = $transaction;
	}

	/**
	 * Getter for transactions.
	 *
	 * @return array
	 */
	public function getTransactions() {
		return $this->transactions;
	}

	/**
	 * Whether this request represents a form post or not.
	 *
	 * @return boolean
	 */
	public function isFormPost() {
		return $this->formPost;
	}

	/**
	 * Marks this request as representing a form post or not.
	 *
	 * @param boolean $formPost
	 * @return void
	 */
	public function setFormPost($formPost) {
		$this->formPost = $formPost;
	}

	/**
	 * Whether this request represents a file upload or not.
	 *
	 * @return boolean
	 */
	public function isFileUpload() {
		return $this->fileUpload;
	}

	/**
	 * Marks this request as representing a file upload or not.
	 *
	 * @param boolean $fileUpload
	 * @return void
	 */
	public function setFileUpload($fileUpload) {
		$this->fileUpload = $fileUpload ? TRUE : FALSE;
	}

}
