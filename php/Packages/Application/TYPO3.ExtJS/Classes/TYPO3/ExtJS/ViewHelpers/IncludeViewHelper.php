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
 * Include ExtJS view helper
 *
 * @api
 */
class IncludeViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Resource\Publishing\ResourcePublisher
	 */
	protected $resourcePublisher;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Returns the HTML needed to include ExtJS, that is, CSS and JS includes.
	 *
	 * = Examples =
	 *
	 * <code title="Simple">
	 * {namespace ext=TYPO3\ExtJS\ViewHelpers}
	 *  ...
	 * <ext:include/>
	 * </code>
	 * Renders the script and link tags needed to include everything needed to
	 * use ExtJS.
	 *
	 * <code title="Use a specific theme">
	 * <ext:include theme="xtheme-gray"/>
	 * </code>
	 *
	 * @param string $theme The theme to include, simply the name of the CSS
	 * @param boolean $debug Whether to use the debug version of ExtJS
	 * @param boolean $includeStylesheets Include ExtJS CSS files if true
	 * @return string HTML needed to include ExtJS
	 * @api
	 */
	public function render($theme = 'xtheme-blue', $debug = NULL, $includeStylesheets = TRUE) {
		if ($debug === NULL) {
			$debug = ($this->objectManager->getContext()->isDevelopment()) ?: FALSE;
		}
		$baseUri = $this->resourcePublisher->getStaticResourcesWebBaseUri() . 'Packages/TYPO3.ExtJS/';
		$output = '';
		if ($includeStylesheets) {
			$output .= '
<link rel="stylesheet" href="' . $baseUri . 'CSS/ext-all-notheme.css" />
<link rel="stylesheet" href="' . $baseUri . 'CSS/' . $theme . '.css" />';
		}
		if ($debug) {
			$output .= '
<script type="text/javascript" src="' . $baseUri . 'JavaScript/adapter/ext/ext-base-debug.js"></script>
<script type="text/javascript" src="' . $baseUri . 'JavaScript/ext-all-debug.js"></script>';
		} else {
			$output .= '
<script type="text/javascript" src="' . $baseUri . 'JavaScript/adapter/ext/ext-base.js"></script>
<script type="text/javascript" src="' . $baseUri . 'JavaScript/ext-all.js"></script>';
		}
		$output .= '
<script type="text/javascript">
	Ext.BLANK_IMAGE_URL = \'' . $baseUri . 'images/default/s.gif\';
	Ext.FlashComponent.EXPRESS_INSTALL_URL = \'' . $baseUri . 'Flash/expressinstall.swf\';
	Ext.chart.Chart.CHART_URL = \'' . $baseUri . 'Flash/chart.swf\';
</script>
';

		return $output;
	}
}
