<?php
namespace TYPO3\Neos\ViewHelpers\ContentElement;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface;
use TYPO3\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Fluid\Core\ViewHelper\Exception as ViewHelperException;
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\Neos\Service\ContentElementWrappingService;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TypoScript\TypoScriptObjects\Helpers\TypoScriptAwareViewInterface;
use TYPO3\TypoScript\TypoScriptObjects\TemplateImplementation;

/**
 * Renders a wrapper around a whole block of editable content to enable frontend editing.
 * Note that using this view helper is usually not necessary as Neos will automatically wrap editables of content
 * elements. For cases where there are no real content element nodes (for example if you'd like to make properties
 * of a document node inline editable) this wrapper can be used.
 */
class WrapViewHelper extends AbstractViewHelper {

	/**
	 * @Flow\Inject
	 * @var ContentElementWrappingService
	 */
	protected $contentElementWrappingService;

	/**
	 * In live workspace this just renders a tag with the specified $tag-name containing the value of the given $property.
	 * For logged in users with access to the Backend this also adds the attributes for the RTE to work.
	 *
	 * @param NodeInterface $node The node of the content element. Optional, will be resolved from the TypoScript context by default.
	 * @return string The rendered property with a wrapping tag. In the user workspace this adds some required attributes for the RTE to work
	 * @throws ViewHelperException
	 */
	public function render(NodeInterface $node = NULL) {
		$view = $this->viewHelperVariableContainer->getView();
		if (!$view instanceof TypoScriptAwareViewInterface) {
			throw new ViewHelperException('This ViewHelper can only be used in a TypoScript content element. You have to specify the "node" argument if it cannot be resolved from the TypoScript context.', 1385737102);
		}
		$typoScriptObject = $view->getTypoScriptObject();
		$currentContext = $typoScriptObject->getTsRuntime()->getCurrentContext();

		if ($node === NULL) {
			$node = $currentContext['node'];
		}
		return $this->contentElementWrappingService->wrapContentObject($node, $typoScriptObject->getPath(), $this->renderChildren());
	}
}
