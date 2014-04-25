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
 * A transparent view that extends JsonView and passes on the prepared array
 * to the Ext Direct response.
 *
 */
class View extends \TYPO3\Flow\Mvc\View\JsonView {
	/**
	 * Renders the Ext Direct view by delegating to the JsonView
	 * for rendering a serializable array.
	 *
	 * @return string An empty string
	 */
	public function render() {
		$result = $this->renderArray();
		$this->controllerContext->getResponse()->setResult($result);
		$this->controllerContext->getResponse()->setSuccess(TRUE);
	}

	/**
	 * Assigns errors to the view and converts them to a format that Ext JS
	 * understands.
	 *
	 * @param \TYPO3\Flow\Error\Result $result Errors e.g. from mapping results
	 */
	public function assignErrors(\TYPO3\Flow\Error\Result $result) {
		$errors = $result->getFlattenedErrors();
		$output = array();
		foreach ($errors as $propertyPath => $propertyErrors) {
			$message = '';
			foreach ($propertyErrors as $propertyError) {
				$message .= $propertyError->getMessage();
			}
			$output[$propertyPath] = $message;
		}
		$this->assign('value', array(
			'errors' => $output,
			'success' => FALSE
		));
	}
}
