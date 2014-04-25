<?php

use Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\TableNode,
	Behat\MinkExtension\Context\MinkContext;
use TYPO3\Flow\Utility\Arrays;
use PHPUnit_Framework_Assert as Assert;

require_once(__DIR__ . '/../../../../../Flowpack.Behat/Tests/Behat/FlowContext.php');

/**
 * Features context
 */
class FeatureContext extends MinkContext {

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \Behat\Mink\Element\ElementInterface
	 */
	protected $selectedContentElement;

	/**
	 * Initializes the context
	 *
	 * @param array $parameters Context parameters (configured through behat.yml)
	 */
	public function __construct(array $parameters) {
		$this->useContext('flow', new \Flowpack\Behat\Tests\Behat\FlowContext($parameters));
		$this->objectManager = $this->getSubcontext('flow')->getObjectManager();
	}

	/**
	 * @Given /^I am not authenticated$/
	 */
	public function iAmNotAuthenticated() {
		// Do nothing, every scenario has a new session
	}

	/**
	 * @Then /^I should see a login form$/
	 */
	public function iShouldSeeALoginForm() {
		$this->assertSession()->fieldExists('Username');
		$this->assertSession()->fieldExists('Password');
	}

	/**
	 * @Given /^the following users exist:$/
	 */
	public function theFollowingUsersExist(TableNode $table) {
		$rows = $table->getHash();
		/** @var \TYPO3\Neos\Domain\Factory\UserFactory $userFactory */
		$userFactory = $this->objectManager->get('TYPO3\Neos\Domain\Factory\UserFactory');
		/** @var \TYPO3\Party\Domain\Repository\PartyRepository $partyRepository */
		$partyRepository = $this->objectManager->get('TYPO3\Party\Domain\Repository\PartyRepository');
		/** @var \TYPO3\Flow\Security\AccountRepository $accountRepository */
		$accountRepository = $this->objectManager->get('TYPO3\Flow\Security\AccountRepository');
		foreach ($rows as $row) {
			$roleIdentifiers = array_map(function($role) {
				return 'TYPO3.Neos:' . $role;
			}, Arrays::trimExplode(',', $row['roles']));
			$user = $userFactory->create($row['username'], $row['password'], $row['firstname'], $row['lastname'], $roleIdentifiers);

			$partyRepository->add($user);
			$accounts = $user->getAccounts();
			foreach ($accounts as $account) {
				$accountRepository->add($account);
			}
		}
		$this->getSubcontext('flow')->persistAll();
	}

	/**
	 * @Given /^I am authenticated with "([^"]*)" and "([^"]*)" for the backend$/
	 */
	public function iAmAuthenticatedWithAndForTheBackend($username, $password) {
		$this->visit('/neos/login');
		$this->fillField('Username', $username);
		$this->fillField('Password', $password);
		$this->pressButton('Login');
	}

	/**
	 * @Then /^I should be on the "([^"]*)" page$/
	 */
	public function iShouldBeOnThePage($page) {
		switch ($page) {
			case 'Login':
				$this->assertSession()->addressEquals('/neos/login');
				break;
			default:
				throw new PendingException();
		}
	}

	/**
	 * @Then /^I should be in the "([^"]*)" module$/
	 */
	public function iShouldBeInTheModule($moduleName) {
		switch ($moduleName) {
			case 'Content':
				$this->assertSession()->addressMatches('/^\/(?!neos).*@.+$/');
				break;
			default:
				throw new PendingException();
		}
	}

	/**
	 * @When /^I follow "([^"]*)" in the main menu$/
	 */
	public function iFollowInTheMainMenu($link) {
		$this->assertElementOnPage('ul.nav');
		$this->getSession()->getPage()->find('css', 'ul.nav')->findLink($link)->click();
	}

	/**
	 * @Given /^I should be logged in as "([^"]*)"$/
	 */
	public function iShouldBeLoggedInAs($name) {
		$this->assertSession()->elementTextContains('css', '#neos-user-actions .neos-user-menu', $name);
	}

	/**
	 * @Then /^I should not be logged in$/
	 */
	public function iShouldNotBeLoggedIn() {
		if ($this->getSession()->getPage()->findButton('logout')) {
			Assert::fail('"Logout" Button not expected');
		}
	}

	/**
	 * @Given /^I should see the page title "([^"]*)"$/
	 */
	public function iShouldSeeThePageTitle($title) {
		$this->assertSession()->elementTextContains('css', 'title', $title);
	}

	/**
	 * @Then /^I should not see the top bar$/
	 */
	public function iShouldNotSeeTheTopBar() {
		$this->assertElementOnPage('.neos-previewmode #neos-top-bar');
	}

	/**
	 * @Given /^the Previewbutton should be active$/
	 */
	public function thePreviewButtonShouldBeActive() {

		$button = $this->getSession()->getPage()->find('css', '.neos-preview-close > .neos-pressed');
		if ($button === NULL) {
			throw new \Behat\Mink\Exception\ElementNotFoundException($this->getSession(), 'button', 'id|name|label|value');
		}

		Assert::assertTrue($button->hasClass('neos-pressed'), 'Button should be pressed');
	}

	/**
	 * @Given /^I imported the site "([^"]*)"$/
	 */
	public function iImportedTheSite($packageKey) {
		/** @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository $nodeDataRepository */
		$nodeDataRepository = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository');
		/** @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface $contextFactory */
		$contextFactory = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface');
		$contentContext = $contextFactory->create(array('workspace' => 'live'));
		\TYPO3\Flow\Reflection\ObjectAccess::setProperty($nodeDataRepository, 'context', $contentContext, TRUE);

		/** @var \TYPO3\Neos\Domain\Service\SiteImportService $siteImportService */
		$siteImportService = $this->objectManager->get('TYPO3\Neos\Domain\Service\SiteImportService');
		$siteImportService->importFromPackage($packageKey, $contentContext);

		$this->getSubcontext('flow')->persistAll();
	}

	/**
	 * @When /^I go to the "([^"]*)" module$/
	 */
	public function iGoToTheModule($module) {
		switch ($module) {
			case 'Administration / Site Management':
				$this->visit('/neos/administration/sites');
				break;
			default:
				throw new PendingException();
		}
	}

	/**
	 * @BeforeScenario @fixtures
	 */
	public function removeTestSitePackages() {
		$directories = glob(FLOW_PATH_PACKAGES . 'Sites/Test.*');
		if (is_array($directories)) {
			foreach ($directories as $directory) {
				\TYPO3\Flow\Utility\Files::removeDirectoryRecursively($directory);
			}
		}
	}

	/**
	 * @BeforeScenario @fixtures
	 */
	public function resetContextFactory() {
		/** @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface $contextFactory */
		$contextFactory = $this->objectManager->get('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface');
		\TYPO3\Flow\Reflection\ObjectAccess::setProperty($contextFactory, 'contextInstances', array(), TRUE);
	}

	/**
	 * @Then /^I should see the following sites in a table:$/
	 */
	public function iShouldSeeTheFollowingSitesInATable(TableNode $table) {
		$sites = $table->getHash();

		$tableLocator = '.neos-module-container table.neos-table';
		$sitesTable = $this->assertSession()->elementExists('css', $tableLocator);

		$siteRows = $sitesTable->findAll('css', 'tbody tr');
		$actualSites = array_map(function($row) {
			return array(
				'name' => $row->find('css', 'td:first-child')->getText()
			);
		}, $siteRows);

		$sortByName = function($a, $b) {
			return strcmp($a['name'], $b['name']);
		};
		usort($sites, $sortByName);
		usort($actualSites, $sortByName);

		Assert::assertEquals($sites, $actualSites);
	}

	/**
     * @Given /^I follow "([^"]*)" for site "([^"]*)"$/
     */
    public function iFollowForSite($link, $siteName) {
		$rowLocator = sprintf("//table[@class='neos-table']//tr[td/text()='%s']", $siteName);
		$siteRow = $this->assertSession()->elementExists('xpath', $rowLocator);
		$siteRow->findLink($link)->click();
    }

	/**
	 * @When /^I select the first content element$/
	 */
	public function iSelectTheFirstContentElement() {
		$element = $this->assertSession()->elementExists('css', '.neos-contentelement');
		$element->click();

		$this->selectedContentElement = $element;
	}

	/**
	 * @When /^I select the first headline content element$/
	 */
	public function iSelectTheFirstHeadlineContentElement() {
		$element = $this->assertSession()->elementExists('css', '.typo3-neos-nodetypes-headline');
		$element->click();

		$this->selectedContentElement = $element;
	}

	/**
	 * @Given /^I set the content to "([^"]*)"$/
	 */
	public function iSetTheContentTo($content) {
		$editable = $this->assertSession()->elementExists('css', '.neos-inline-editable', $this->selectedContentElement);

		$this->spinWait(function() use ($editable) { return $editable->hasAttribute('contenteditable'); }, 10000, 'editable has contenteditable attribute set');

		$editable->setValue($content);
	}

	/**
	 * @Given /^I wait for the changes to be saved$/
	 */
	public function iWaitForTheChangesToBeSaved() {
		$this->getSession()->wait(30000, '$(".neos-indicator-saved").length > 0');
		$this->assertSession()->elementExists('css', '.neos-indicator-saved');
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public function locatePath($path) {
		return parent::locatePath($this->getSubcontext('flow')->resolvePath($path));
	}

	/**
	 * @param callable $callback
	 * @param integer $timeout Timeout in milliseconds
	 * @param string $message
	 */
	public function spinWait($callback, $timeout, $message = '') {
		$waited = 0;
		while ($callback() !== TRUE) {
			if ($waited > $timeout) {
				Assert::fail($message);
				return;
			}
			usleep(50000);
			$waited += 50;
		}
	}

}
