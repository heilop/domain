<?php

namespace Drupal\Tests\domain\Functional;

use Drupal\Tests\domain\Functional\DomainTestBase;

/**
 * Tests behavior for the weight element of the domain list builder.
 *
 * @group domain
 */
class DomainListWeightTest extends DomainTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'user');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create 60 domains. We paginate at 50.
    $this->domainCreateTestDomains(60);
  }

  /**
   * Basic test setup.
   */
  public function testDomainWeight() {
    // Test the default sort values. Should be 1 to 60.
    $domains = $this->getDomainsSorted();
    $i = 1;
    foreach ($domains as $domain) {
      $this->assert($domain->getWeight() == $i, 'Weight set to ' . $i);
      $i++;
    }
    // The last domain should be test59_example_com.
    $this->assert($domain->id() == 'test59_example_com', 'Last domain is test59');
    $domains_old = $domains;

    $admin = $this->drupalCreateUser(array(
      'bypass node access',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer domains',
    ));
    $this->drupalLogin($admin);

    $this->drupalGet('admin/config/domain');
    $this->assertSession()->statusCodeEquals(200);

    // Set one weight to 61.
    $locator = 'edit-domains-one-example-com-weight';
    $this->fillField($locator, 61);

    // Save the form.
    $this->pressButton('edit-submit');

    $domains = $this->getDomainsSorted();
    $i = 1;
    foreach ($domains as $domain) {
      // Weights should be the same one page 1 except for the one we changed.
      if ($domain->id() == 'one_example_com') {
        $this->assert($domain->getWeight() == 61, 'Weight set to 61 ' . $domain->getWeight());
      }
      elseif ($domain->getWeight() < 50) {
        $this->assert($domain->getWeight() == $i, 'Weight set to ' . $i . $domain->getWeight());
      }
      // These values should not change.
      else {
        $this->assert($domain->getWeight() == $domains_old[$domain->id()]->getWeight(). 'Weights unchanged');
      }
      $i++;
    }
    // The last domain should be one_example_com.
    $this->assert($domain->id() == 'one_example_com', 'Last domain is one');

    // Go to page two.
    $this->clickLink('Next');
    $this->assertSession()->statusCodeEquals(200);
    // Set one weight to 2.
    $locator = 'edit-domains-one-example-com-weight';
    $this->fillField($locator, 2);
    // Save the form.
    $this->pressButton('edit-submit');

    $this->drupalGet('admin/config/domain');
    $this->assertSession()->statusCodeEquals(200);

    // Go to page two.
    $this->clickLink('Next');
    $this->assertSession()->statusCodeEquals(200);

    // Check the domain sort order.
    $domains = $this->getDomainsSorted();
    $i = 1;
    foreach ($domains as $domain) {
      if ($domain->id() == 'one_example_com') {
       $this->assert($domain->getWeight() == 2, 'Weight set to 2');
      }
      elseif ($domain->getWeight() < 51) {
        $this->assert($domain->getWeight() == $i, 'Weight set to ' . $i);
        $i++;
      }
      elseif ($domain->getWeight() == 51) {
        $this->assert($domain->getWeight() == 51, 'Two domains weighted 51.');
      }
      // These values should decrease by 1.
      else {
        $this->assert($domain->getWeight() == $domains_old[$domain->id()]->getWeight() - 1, 'Weights decreased by 1');
      }
    }
    // The last domain should be test59_example_com.
    $this->assert($domain->id() == 'test59_example_com', 'Last domain is test59' . $domain->id());
  }
}