<?php

/**
 * @file
 * Definition of Drupal\securepages\Tests\SettingsFormTest.
 */

namespace Drupal\securepages\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the Drupal 8 securepages module functionality
 *
 * @group securepages
 */
class SecurePagesTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('securepages', 'node', 'block', 'taxonomy_menu', 'taxonomy');

  /**
   * A simple user with 'access content' permission
   */
  private $user;

  /**
   * Perform any initial set up tasks that run before every test method
   */
  public function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser(array('administer site configuration', 'administer taxonomy'));
    $this->drupalLogin($this->user);
  }
  /**
   * Tests that the 'history/' path returns the right content
   */
  public function testHistoryExists() {
    $this->drupalLogin($this->user);
    $this->drupalGet('history');
    $this->assertResponse(200);
    $this->assertText(sprintf('Hello %s!', 'World'), 'Correct message is shown.');
  }

  /**
   * Tests the securepages_match() function.
   */
  function testMatch() {
    global $is_https;
    $config = \Drupal::configFactory()->getEditable('securepages.settings');
    $config->set('securepages_ignore', '*/autocomplete/*')->save();

    $securepagesservice = \Drupal::service('securepages.securepagesservice');
    $this->assertTrue($securepagesservice->securePagesMatch('user'), 'path user matches.');
    $this->assertTrue($securepagesservice->securePagesMatch('user/login'), 'path user/login matches.');
    $this->assertTrue($securepagesservice->securePagesMatch('admin/modules'), 'path admin/modules matches.');
    $this->assertFalse($securepagesservice->securePagesMatch('node'), 'path node does not match.');
    $this->assertTrue($securepagesservice->securePagesMatch('user/autocomplete/alice') == $is_https ? 1 : 0, 'autocomplete path is ignored.');

    // Clean up
    $config->clear('securepages_ignore')->save();
  }

  /**
   * Logs in a user using HTTPS.
   */
  function loginHTTPS($user) {
    $edit = array(
      'name' => $user->name,
      'pass' => $user->pass_raw,
    );
    $this->drupalPost('user', $edit, t('Log in'), array('https' => TRUE));
  }

}