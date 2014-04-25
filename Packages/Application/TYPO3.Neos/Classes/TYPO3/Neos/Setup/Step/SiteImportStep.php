<?php
namespace TYPO3\Neos\Setup\Step;

/*                                                                        *
 * This script belongs to the Flow package "TYPO3.Neos".                  *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class SiteImportStep extends \TYPO3\Setup\Step\AbstractStep {

	/**
	 * @var boolean
	 */
	protected $optional = TRUE;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Repository\SiteRepository
	 */
	protected $siteRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Service\SiteImportService
	 */
	protected $siteImportService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Repository\DomainRepository
	 */
	protected $domainRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository
	 */
	protected $workspaceRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Mvc\FlashMessageContainer
	 */
	protected $flashMessageContainer;

	/**
	 * @var \TYPO3\Form\Finishers\ClosureFinisher
	 */
	protected $closureFinisher;

	/**
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 * @Flow\Inject
	 */
	protected $systemLogger;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * Returns the form definitions for the step
	 *
	 * @param \TYPO3\Form\Core\Model\FormDefinition $formDefinition
	 * @return void
	 */
	protected function buildForm(\TYPO3\Form\Core\Model\FormDefinition $formDefinition) {
		$page1 = $formDefinition->createPage('page1');
		$page1->setRenderingOption('header', 'Import or create a site');

		$title = $page1->createElement('connectionSection', 'TYPO3.Form:Section');
		$title->setLabel('Import a site');

		$sitePackages = array();
		foreach ($this->packageManager->getFilteredPackages('available', NULL, 'typo3-flow-site') as $package) {
			$sitePackages[$package->getPackageKey()] = $package->getPackageKey();
		}

		if (count($sitePackages) > 0) {
			$site = $title->createElement('site', 'TYPO3.Form:SingleSelectDropdown');
			$site->setLabel('Select a site');
			$site->setProperty('options', $sitePackages);
			$site->addValidator(new \TYPO3\Flow\Validation\Validator\NotEmptyValidator());

			$sites = $this->siteRepository->findAll();
			if ($sites->count() > 0) {
				$prune = $title->createElement('prune', 'TYPO3.Form:Checkbox');
				$prune->setLabel('Delete existing sites');
			}
		} else {
			$error = $title->createElement('noSitePackagesError', 'TYPO3.Form:StaticText');
			$error->setProperty('text', 'No site packages were available, make sure you have an active site package');
			$error->setProperty('class', 'alert alert-warning');
		}

		if ($this->packageManager->isPackageActive('TYPO3.Neos.Kickstarter')) {
			$separator = $page1->createElement('separator', 'TYPO3.Form:StaticText');
			$separator->setProperty('elementClassAttribute', 'section-separator');

			$newPackageSection = $page1->createElement('newPackageSection', 'TYPO3.Form:Section');
			$newPackageSection->setLabel('Create a new site');
			$packageName = $newPackageSection->createElement('packageKey', 'TYPO3.Form:SingleLineText');
			$packageName->setLabel('Package Name (in form "Vendor.MyPackageName")');
			$packageName->addValidator(new \TYPO3\Neos\Validation\Validator\PackageKeyValidator());

			$siteName = $newPackageSection->createElement('siteName', 'TYPO3.Form:SingleLineText');
			$siteName->setLabel('Site Name');
		} else {
			$error = $title->createElement('neosKickstarterUnavailableError', 'TYPO3.Form:StaticText');
			$error->setProperty('text', 'The Neos Kickstarter package (TYPO3.Neos.Kickstarter) is not installed, install it for kickstarting new sites (using "composer require typo3/neos-kickstarter")');
			$error->setProperty('class', 'alert alert-warning');
		}

		$step = $this;
		$callback = function(\TYPO3\Form\Core\Model\FinisherContext $finisherContext) use ($step) {
			$step->importSite($finisherContext);
		};
		$this->closureFinisher = new \TYPO3\Form\Finishers\ClosureFinisher();
		$this->closureFinisher->setOption('closure', $callback);
		$formDefinition->addFinisher($this->closureFinisher);

		$formDefinition->setRenderingOption('skipStepNotice', 'You can always import a site using the site:import command');
	}

	/**
	 * @param \TYPO3\Form\Core\Model\FinisherContext $finisherContext
	 * @return void
	 * @throws \TYPO3\Setup\Exception
	 */
	public function importSite(\TYPO3\Form\Core\Model\FinisherContext $finisherContext) {
		$formValues = $finisherContext->getFormRuntime()->getFormState()->getFormValues();

		if (isset($formValues['prune']) && intval($formValues['prune']) === 1) {
			$this->nodeDataRepository->removeAll();
			$this->workspaceRepository->removeAll();
			$this->domainRepository->removeAll();
			$this->siteRepository->removeAll();
			$this->persistenceManager->persistAll();
		}

		if (!empty($formValues['packageKey'])) {
			if ($this->packageManager->isPackageAvailable($formValues['packageKey'])) {
				throw new \TYPO3\Setup\Exception(sprintf('The package key "%s" already exists.', $formValues['packageKey']), 1346759486);
			}
			$packageKey = $formValues['packageKey'];
			$siteName = $formValues['siteName'];

			$generatorService = $this->objectManager->get('TYPO3\Neos\Kickstarter\Service\GeneratorService');
			$generatorService->generateSitePackage($packageKey, $siteName);
			$this->packageManager->activatePackage($packageKey);
		} elseif (!empty($formValues['site'])) {
			$packageKey = $formValues['site'];
		}
		if (!empty($packageKey)) {
			try {
				$contentContext = $this->contextFactory->create(array('workspaceName' => 'live'));
				$this->siteImportService->importFromPackage($packageKey, $contentContext);
			} catch (\Exception $exception) {
				$finisherContext->cancel();
				$this->systemLogger->logException($exception);
				$this->flashMessageContainer->addMessage(new \TYPO3\Flow\Error\Error(sprintf('Error: During the import of the "Sites.xml" from the package "%s" an exception occurred: %s', $packageKey, $exception->getMessage())));
			}
		}
	}

}
