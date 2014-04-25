<?php
namespace TYPO3\ExtJS\ViewHelpers\ExtDirect;

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
 * Ext Direct Provider view helper
 *
 * @api
 */
class ProviderViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $localReflectionService;

	/**
	 * Inject the Reflection service
	 *
	 * A _private_ property "reflectionService" already exists in the AbstractViewHelper,
	 * therefore we need to switch to another property name.
	 *
	 * @param \TYPO3\Flow\Reflection\ReflectionService $reflectionService Reflection service
	 * @return void
	 */
	public function injectLocalReflectionService(\TYPO3\Flow\Reflection\ReflectionService $reflectionService) {
		$this->localReflectionService = $reflectionService;
	}

	/**
	 * Injects the security context
	 *
	 * @param \TYPO3\Flow\Security\Context $securityContext The security context
	 * @return void
	 */
	public function injectSecurityContext(\TYPO3\Flow\Security\Context $securityContext) {
		$this->securityContext = $securityContext;
	}

	/**
	 * Returns the JavaScript to declare the Ext Direct provider for all
	 * controller actions that are annotated with "@TYPO3\ExtJS\Annotations\ExtDirect"
	 *
	 * = Examples =
	 *
	 * <code title="Simple">
	 * {namespace ext=TYPO3\ExtJS\ViewHelpers}
	 *  ...
	 * <script type="text/javascript">
	 * <ext:extdirect.provider />
	 * </script>
	 *  ...
	 * </code>
	 *
	 * TODO Cache ext direct provider config
	 * @param string $namespace The base ExtJS namespace (with dots) for the direct provider methods
	 * @return string JavaScript needed to include Ext Direct provider
	 * @api
	 */
	public function render($namespace = NULL) {
		$providerConfig = array(
			'url' => '?TYPO3_ExtJS_ExtDirectRequest=1&__csrfToken=' . $this->securityContext->getCsrfProtectionToken(),
			'type' => 'remoting',
			'actions' => array()
		);
		if (!empty($namespace)) {
			$providerConfig['namespace'] = $namespace;
		}
		$controllerClassNames = $this->localReflectionService->getAllImplementationClassNamesForInterface('TYPO3\Flow\Mvc\Controller\ControllerInterface');
		foreach ($controllerClassNames as $controllerClassName) {
			$methodNames = get_class_methods($controllerClassName);
			foreach ($methodNames as $methodName) {
				$methodTagsValues = $this->localReflectionService->getMethodTagsValues($controllerClassName, $methodName);
				if (isset($methodTagsValues['extdirect'])) {
					$methodParameters = $this->localReflectionService->getMethodParameters($controllerClassName, $methodName);
					$requiredMethodParametersCount = 0;
					foreach ($methodParameters as $methodParameter) {
						if ($methodParameter['optional'] === TRUE) {
							break;
						}
						$requiredMethodParametersCount ++;
					}
					$extDirectAction = str_replace('\\', '_', $controllerClassName);

					$providerConfig['actions'][$extDirectAction][] = array(
						'name' => substr($methodName, 0, -6),
						'len' => $requiredMethodParametersCount
					);
				}
			}
		}

		return 'Ext.Direct.addProvider(' . json_encode($providerConfig) . ');' . chr(10);
	}
}
