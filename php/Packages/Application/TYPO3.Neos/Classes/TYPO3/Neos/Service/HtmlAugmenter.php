<?php
namespace TYPO3\Neos\Service;

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

/**
 * A tool that can augment HTML for example by adding arbitrary attributes.
 * This is used in order to add meta data arguments to content elements in the Backend.
 *
 * @Flow\Scope("singleton")
 */
class HtmlAugmenter {

	/**
	 * Adds the given $attributes to the $html by augmenting the root element.
	 * Attributes are merged with the existing root element's attributes.
	 * If no unique root node can be determined, a wrapping tag is added with all the given attributes. The name of this
	 * tag can be specified with $fallbackTagName.
	 *
	 * @param string $html
	 * @param array $attributes
	 * @param string $fallbackTagName
	 * @return string
	 */
	public function addAttributes($html, array $attributes, $fallbackTagName = 'div') {
		if ($attributes === array()) {
			return $html;
		}
		$rootElement = $this->getHtmlRootElement($html);
		if ($rootElement === NULL) {
			return sprintf('<%s%s>%s</%s>', $fallbackTagName, $this->renderAttributes($attributes), $html, $fallbackTagName);
		}
		$this->mergeAttributes($rootElement, $attributes);
		return preg_replace('/<(' . $rootElement->nodeName . ')\b[^>]*>/xi', '<$1' . $this->renderAttributes($attributes) . '>', $html, 1, $numberOfReplacements);
	}

	/**
	 * Detects a unique root tag in the given $html string and returns its DOMNode representation - or NULL if no unique root element could be found
	 *
	 * @param string $html
	 * @return \DOMNode
	 */
	protected function getHtmlRootElement($html) {
		if (trim($html) === '') {
			return NULL;
		}
		$domDocument = new \DOMDocument('1.0', 'UTF-8');
		// ignore parsing errors
		$useInternalErrorsBackup = libxml_use_internal_errors(TRUE);
		$domDocument->loadHTML($html);
		$xPath = new \DOMXPath($domDocument);
		$rootElement = $xPath->query('//html/body/*');
		if ($useInternalErrorsBackup !== TRUE) {
			libxml_use_internal_errors($useInternalErrorsBackup);
		}
		if ($rootElement === FALSE || $rootElement->length !== 1) {
			return NULL;
		}
		return $rootElement->item(0);
	}

	/**
	 * Merges the attributes of $element with the given $newAttributes
	 * If an attribute exists in both collections, it is merged to "<new attribute> <old attribute>" (if both values differ)
	 *
	 * @param \DOMNode $element
	 * @param array $newAttributes
	 * @return void
	 */
	protected function mergeAttributes(\DOMNode $element, array &$newAttributes) {
		/** @var $attribute \DOMAttr */
		foreach ($element->attributes as $attribute) {
			$oldAttributeValue = $attribute->hasChildNodes() ? $attribute->value : NULL;
			$newAttributeValue = isset($newAttributes[$attribute->name]) ? $newAttributes[$attribute->name] : NULL;
			$mergedAttributes = array();
			if ($newAttributeValue !== NULL && $newAttributeValue !== $oldAttributeValue) {
				$mergedAttributes[] = $newAttributeValue;
			}
			if ($oldAttributeValue !== NULL) {
				$mergedAttributes[] = $oldAttributeValue;
			}
			$newAttributes[$attribute->name] = $mergedAttributes !== array() ? implode(' ', $mergedAttributes) : NULL;
		}
	}

	/**
	 * Renders the given key/value pair to a valid attribute string in the format <key1>="<value1>" <key2>="<value2>"...
	 *
	 * @param array $attributes The attributes to render in the format array('<attributeKey>' => '<attributeValue>', ...)
	 * @return string
	 */
	protected function renderAttributes(array $attributes) {
		$renderedAttributes = '';
		foreach ($attributes as $attributeName => $attributeValue) {
			$encodedAttributeName = htmlspecialchars($attributeName, ENT_COMPAT, 'UTF-8', FALSE);
			if ($attributeValue === NULL) {
				$renderedAttributes .= ' ' . $encodedAttributeName;
			} else {
				$encodedAttributeValue = htmlspecialchars($attributeValue, ENT_COMPAT, 'UTF-8', FALSE);
				$renderedAttributes .= ' ' . $encodedAttributeName . '="' . $encodedAttributeValue . '"';
			}
		}
		return $renderedAttributes;
	}
}