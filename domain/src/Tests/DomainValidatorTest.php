<?php

namespace Drupal\domain\Tests;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ConfigValueException;
use Drupal\domain\Entity\Domain;

/**
 * Tests domain record validation.
 *
 * @group domain
 */
class DomainValidatorTest extends DomainTestBase {

  /**
   * Tests that a domain hostname validates.
   */
  public function testDomainValidator() {
    // No domains should exist.
    $this->domainTableIsEmpty();
    $validator = \Drupal::service('domain.validator');

    // Create a domain.
    $this->domainCreateTestDomains(1, 'foo.com');
    // Check the created domain based on its known id value.
    $key = 'foo.com';
    /** @var \Drupal\domain\Entity\Domain $domain */
    $domain = \Drupal::service('domain.loader')->loadByHostname($key);
    $this->assertTrue(!empty($domain), 'Test domain created.');

    // Valid hostnames to test. Valid is the boolean value.
    $hostnames = [
      'localhost' => 1,
      'example.com' => 1,
      'www.example.com' => 1, // see www-prefix test, below.
      'one.example.com' => 1,
      'example.com:8080' => 1,
      'example.com::8080' => 0, // only one colon.
      'example.com:abc' => 0, // no letters after a colon.
      '.example.com' => 0, // cannot begin with a dot.
      'example.com.' => 0, // cannot end with a dot.
      'EXAMPLE.com' => 0, // lowercase only.
      'éxample.com' => 0, // ascii-only.
    ];
    foreach ($hostnames as $hostname => $valid) {
      $errors = $validator->validate($hostname);
      if ($valid) {
        $this->assertTrue(empty($errors), new FormattableMarkup('Validation test for @hostname passed.', array('@hostname' => $hostname)));
      }
      else {
        $this->assertTrue(!empty($errors), new FormattableMarkup('Validation test for @hostname failed.', array('@hostname' => $hostname)));
      }
    }
    // Test duplicate hostname creation.
    $test_hostname = 'foo.com';
    $test_domain = Domain::create([
      'hostname' => $test_hostname,
      'name' => 'Test domain',
      'id' => 'test_domain',
    ]);
    try {
      $test_domain->save();
      $this->fail('Duplicate hostname validation');
    }
    catch (ConfigValueException $e) {
      $expected_message = "The hostname ($test_hostname) is already registered.";
      $this->assertEqual($expected_message, $e->getMessage());
    }
    // Test the two configurable options.
    $config = \Drupal::configFactory()->getEditable('domain.settings');
    $config->set('www_prefix', TRUE)->save();
    $config->set('allow_non_ascii', TRUE)->save();
    drupal_flush_all_caches();
    // Valid hostnames to test. Valid is the boolean value.
    $hostnames = [
      'www.example.com' => 0, // no www-prefix allowed
      'éxample.com' => 1, // ascii-only allowed.
    ];
    foreach ($hostnames as $hostname => $valid) {
      $errors = $validator->validate($hostname);
      if ($valid) {
        $this->assertTrue(empty($errors), new FormattableMarkup('Validation test for @hostname passed.', array('@hostname' => $hostname)));
      }
      else {
        $this->assertTrue(!empty($errors), new FormattableMarkup('Validation test for @hostname failed.', array('@hostname' => $hostname)));
      }
    }

  }

}
