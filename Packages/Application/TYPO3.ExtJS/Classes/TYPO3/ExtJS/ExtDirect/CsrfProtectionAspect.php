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
 * An aspect which cares for CSRF protection of links used in the ExtDirect service.
 *
 * @Flow\Aspect
 */
class CsrfProtectionAspect {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $securityContext;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Policy\PolicyService
	 */
	protected $policyService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Core\Bootstrap
	 */
	protected $bootstrap;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface
	 */
	protected $authenticationManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Mvc\Routing\RouterInterface
	 */
	protected $router;

	/**
	 * Adds a CSRF token as argument in ExtDirect requests
	 *
	 * @Flow\Around("method(TYPO3\ExtJS\ExtDirect\Transaction->buildRequest()) && setting(TYPO3.Flow.security.enable)")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
	 * @return \TYPO3\Flow\Mvc\ActionRequest
	 */
	public function transferCsrfTokenToExtDirectRequests(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$extDirectRequest = $joinPoint->getAdviceChain()->proceed($joinPoint);

		$requestHandler = $this->bootstrap->getActiveRequestHandler();
		if ($requestHandler instanceof \TYPO3\Flow\Http\HttpRequestHandlerInterface) {
			$arguments = $requestHandler->getHttpRequest()->getArguments();
			if (isset($arguments['__csrfToken'])) {
				$requestArguments = $extDirectRequest->getMainRequest()->getArguments();
				$requestArguments['__csrfToken'] = $arguments['__csrfToken'];
				$extDirectRequest->getMainRequest()->setArguments($requestArguments);
			}
		}

		return $extDirectRequest;
	}
}
