<?php
namespace TYPO3\ExtJS\ViewHelpers;

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
 * ExtJS ux class inclusion view helper
 *
 * @api
 */
class UxViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Resource\Publishing\ResourcePublisher
	 */
	protected $resourcePublisher;

	/**
	 * Returns the HTML needed to include the ExtJS ux class.
	 *
	 * = Examples =
	 *
	 * <code title="Simple">
	 * {namespace ext=TYPO3\ExtJS\ViewHelpers}
	 *  ...
	 * <ext:ux name="StatusBar"/>
	 * </code>
	 * Renders the script tag to include the StatusBar ux class.
	 *
	 * @param string $name The name of the ux class
	 * @return string HTML needed to include ExtJS
	 * @api
	 */
	public function render($name) {
		$baseUri = $this->resourcePublisher->getStaticResourcesWebBaseUri() . 'Packages/TYPO3.ExtJS/';
		return '
<script type="text/javascript" src="' . $baseUri . 'JavaScript/ux/' . $name . '.js"></script>
';
	}
}
