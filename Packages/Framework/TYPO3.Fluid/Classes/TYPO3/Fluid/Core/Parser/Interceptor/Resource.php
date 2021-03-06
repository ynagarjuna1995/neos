<?php
namespace TYPO3\Fluid\Core\Parser\Interceptor;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Fluid".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Package\Package;
use TYPO3\Fluid\Core\Parser\InterceptorInterface;
use TYPO3\Fluid\Core\Parser\ParsingState;
use TYPO3\Fluid\Core\Parser\SyntaxTree\NodeInterface;
use TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode;
use TYPO3\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;

/**
 * This interceptor looks for URIs pointing to package resources and in place
 * of those adds ViewHelperNode instances using the ResourceViewHelper to
 * make those URIs work in the rendered template.
 *
 * That means you can build your template so that it can be previewed as is and
 * pointers to CSS, JS, images, ... will still work when the resources are
 * mirrored by Flow.
 *
 * Currently the supported URIs are of the form
 *  [../]Public/Some/<Path/To/Resource> (will use current package)
 *  [../]<PackageKey>/Resources/Public/<Path/To/Resource> (will use given package)
 *
 */
class Resource implements InterceptorInterface {

	/**
	 * Split a text at what seems to be a package resource URI.
	 * @var string
	 */
	const PATTERN_SPLIT_AT_RESOURCE_URIS = '!
		(
			(?:                       # Start URL Part
				\.\./                 # Either the string "../"
				|[^"\'(]+/            # ... or a string with no quotes, and no opening bracket.
			)*                        # a URL consists of multiple URL parts
			Public/                   # the string "Public/"
			[^"\')]+                  # followed by arbitrary characters except quotes or closing brackets.
		)
		!x';

	/**
	 * Is the text at hand a resource URI and what are path/package?
	 * @var string
	 * @see \TYPO3\Flow\Pckage\Package::PATTERN_MATCH_PACKAGEKEY
	 */
	const PATTERN_MATCH_RESOURCE_URI = '!(?:../)*(?:(?P<Package>[A-Z][A-Za-z0-9_]+)/Resources/)?Public/(?P<Path>[^"]+)!';

	/**
	 * The default package key to use when rendering resource links without a
	 * package key in the source URL.
	 * @var string
	 */
	protected $defaultPackageKey;

	/**
	 * @var ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Inject object factory
	 *
	 * @param ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Set the default package key to use for resource URIs.
	 *
	 * @param string $defaultPackageKey
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	public function setDefaultPackageKey($defaultPackageKey) {
		if (!preg_match(Package::PATTERN_MATCH_PACKAGEKEY, $defaultPackageKey)) {
			throw new \InvalidArgumentException('The given argument was not a valid package key.', 1277287099);
		}
		$this->defaultPackageKey = $defaultPackageKey;
	}

	/**
	 * Looks for URIs pointing to package resources and in place of those adds
	 * ViewHelperNode instances using the ResourceViewHelper.
	 *
	 * @param NodeInterface $node
	 * @param integer $interceptorPosition One of the INTERCEPT_* constants for the current interception point
	 * @param ParsingState $parsingState the current parsing state. Not needed in this interceptor.
	 * @return NodeInterface the modified node
	 */
	public function process(NodeInterface $node, $interceptorPosition, ParsingState $parsingState) {
		/** @var $node TextNode */
		if (strpos($node->getText(), 'Public/') === FALSE) {
			return $node;
		}
		$textParts = preg_split(self::PATTERN_SPLIT_AT_RESOURCE_URIS, $node->getText(), -1, PREG_SPLIT_DELIM_CAPTURE);
		$node = $this->objectManager->get('TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode');
		foreach ($textParts as $part) {
			$matches = array();
			if (preg_match(self::PATTERN_MATCH_RESOURCE_URI, $part, $matches)) {
				$arguments = array(
					'path' => $this->objectManager->get('TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode', $matches['Path'])
				);
				if (isset($matches['Package']) && preg_match(Package::PATTERN_MATCH_PACKAGEKEY, $matches['Package'])) {
					$arguments['package'] = $this->objectManager->get('TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode', $matches['Package']);
				} elseif ($this->defaultPackageKey !== NULL) {
					$arguments['package'] = $this->objectManager->get('TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode', $this->defaultPackageKey);
				}
				$viewHelper = $this->objectManager->get('TYPO3\Fluid\ViewHelpers\Uri\ResourceViewHelper');
				/** @var $viewHelperNode ViewHelperNode */
				$viewHelperNode = $this->objectManager->get('TYPO3\Fluid\Core\Parser\SyntaxTree\ViewHelperNode', $viewHelper, $arguments);
				$node->addChildNode($viewHelperNode);
			} else {
				/** @var $textNode TextNode */
				$textNode = $this->objectManager->get('TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode', $part);
				$node->addChildNode($textNode);
			}
		}

		return $node;
	}

	/**
	 * This interceptor wants to hook into text nodes.
	 *
	 * @return array Array of INTERCEPT_* constants
	 */
	public function getInterceptionPoints() {
		return array(
			InterceptorInterface::INTERCEPT_TEXT
		);
	}
}
