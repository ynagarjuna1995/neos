<?php
namespace TYPO3\ExtJS;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.ExtJS".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, version 3.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Package\Package as BasePackage;

/**
 * The ExtJS Package
 *
 */
class Package extends BasePackage {

	/**
	 * Invokes custom PHP code directly after the package manager has been initialized.
	 *
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap The current bootstrap
	 * @return void
	 */
	public function boot(\TYPO3\Flow\Core\Bootstrap $bootstrap) {
		$bootstrap->registerRequestHandler(new \TYPO3\ExtJS\ExtDirect\RequestHandler($bootstrap));
	}
}
